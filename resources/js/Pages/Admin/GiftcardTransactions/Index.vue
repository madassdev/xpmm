<script setup>
import { ref, computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { post } from '@/lib/api'
import { CheckCircle, XCircle, Eye, Loader2 } from 'lucide-vue-next'
import Modal from '@/Components/ui/Modal.vue'

const props = defineProps({
  transactions: {
    type: Object,
    required: true,
  },
  stats: {
    type: Object,
    required: true,
  },
})

const previewTransaction = ref(null)
const approveTarget = ref(null)
const rejectTarget = ref(null)
const detailTransaction = ref(null)
const showImageModal = ref(false)
const showApproveModal = ref(false)
const showRejectModal = ref(false)
const showDetailModal = ref(false)
const rejectReason = ref('')
const processing = ref(false)
const detailFields = computed(() => {
  if (!detailTransaction.value) return []
  const tx = detailTransaction.value
  return [
    { label: 'Giftcard', value: tx.giftcard_name },
    { label: 'User', value: tx.user_name },
    { label: 'Email', value: tx.user_email },
    { label: 'Type', value: tx.type_label },
    { label: 'Currency', value: tx.currency },
    { label: 'Amount', value: `${tx.currency} ${tx.amount.toLocaleString()} × ${tx.quantity}` },
    { label: 'Payment method', value: tx.payment_method?.toUpperCase() },
    { label: 'Card type', value: tx.card_type || '—' },
    { label: 'Status', value: tx.status_label },
    { label: 'Date', value: tx.created_at ? new Date(tx.created_at).toLocaleString() : '—' },
  ]
})

function getStatusColor(status) {
  const colors = {
    pending: 'bg-yellow-100 text-yellow-800',
    processing: 'bg-blue-100 text-blue-800',
    completed: 'bg-green-100 text-green-800',
    rejected: 'bg-red-100 text-red-800',
  }
  return colors[status] || 'bg-gray-100 text-gray-800'
}

function getTypeColor(type) {
  return type === 'buy' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800'
}

function canMarkCompleted(transaction) {
  return transaction.status !== 'completed'
}

function canMarkRejected(transaction) {
  return transaction.status !== 'rejected'
}

function openApproveModal(transaction) {
  approveTarget.value = transaction
  showApproveModal.value = true
}

async function submitApprove() {
  if (!approveTarget.value) return
  processing.value = true
  try {
    await post(`/admin/giftcard-transactions/${approveTarget.value.id}/approve`)
    showApproveModal.value = false
    approveTarget.value = null
    router.reload()
  } catch (e) {
    console.error('Failed to approve:', e)
  } finally {
    processing.value = false
  }
}

function openRejectModal(transaction) {
  rejectTarget.value = transaction
  rejectReason.value = transaction.notes ?? ''
  showRejectModal.value = true
}

async function submitReject() {
  if (!rejectTarget.value) return
  processing.value = true
  try {
    await post(`/admin/giftcard-transactions/${rejectTarget.value.id}/reject`, {
      reason: rejectReason.value,
    })
    showRejectModal.value = false
    rejectTarget.value = null
    router.reload()
  } catch (e) {
    console.error('Failed to reject:', e)
  } finally {
    processing.value = false
  }
}

function viewImages(transaction) {
  previewTransaction.value = transaction
  showImageModal.value = true
}

function openSingleImage(path) {
  const src = path.startsWith('/storage/') ? path : `/storage/${path}`
  previewTransaction.value = { images: [src] }
  showImageModal.value = true
}

function openDetail(transaction) {
  detailTransaction.value = transaction
  showDetailModal.value = true
}
</script>

<template>
  <AdminLayout>
    <Head title="Giftcard Transactions" />

    <div class="space-y-6">
      <!-- Stats -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-gray-200 p-4">
          <p class="text-sm text-gray-600">Total Transactions</p>
          <p class="text-2xl font-bold text-gray-900">{{ stats.total }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
          <p class="text-sm text-gray-600">Pending</p>
          <p class="text-2xl font-bold text-yellow-600">{{ stats.pending }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
          <p class="text-sm text-gray-600">Completed</p>
          <p class="text-2xl font-bold text-green-600">{{ stats.completed }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
          <p class="text-sm text-gray-600">Rejected</p>
          <p class="text-2xl font-bold text-red-600">{{ stats.rejected }}</p>
        </div>
      </div>

      <!-- Transactions Table -->
      <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
          <h2 class="text-lg font-semibold">Giftcard Transactions</h2>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Giftcard</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr
                v-for="tx in transactions.data"
                :key="tx.id"
                class="hover:bg-gray-50 cursor-pointer"
                @click="openDetail(tx)"
              >
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">#{{ tx.id }}</td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm text-gray-900">{{ tx.user_name }}</div>
                  <div class="text-sm text-gray-500">{{ tx.user_email }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ tx.giftcard_name }}</td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span :class="getTypeColor(tx.type)" class="px-2 py-1 rounded-full text-xs font-medium">
                    {{ tx.type_label }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {{ tx.currency }} {{ tx.amount.toLocaleString() }} × {{ tx.quantity }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span :class="getStatusColor(tx.status)" class="px-2 py-1 rounded-full text-xs font-medium">
                    {{ tx.status_label }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ new Date(tx.created_at).toLocaleDateString() }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                  <div class="flex items-center justify-end gap-2">
                    <button
                      v-if="tx.images && tx.images.length > 0"
                      @click.stop="viewImages(tx)"
                      class="text-blue-600 hover:text-blue-900"
                      title="View Images"
                    >
                      <Eye class="w-4 h-4" />
                    </button>
                    <button
                      v-if="canMarkCompleted(tx)"
                      @click.stop="openApproveModal(tx)"
                      :disabled="processing"
                      class="text-green-600 hover:text-green-900 disabled:opacity-50"
                      title="Mark as completed"
                    >
                      <CheckCircle class="w-4 h-4" />
                    </button>
                    <button
                      v-if="canMarkRejected(tx)"
                      @click.stop="openRejectModal(tx)"
                      :disabled="processing"
                      class="text-red-600 hover:text-red-900 disabled:opacity-50"
                      title="Reject"
                    >
                      <XCircle class="w-4 h-4" />
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="transactions.links && transactions.links.length > 3" class="px-6 py-4 border-t border-gray-200">
          <div class="flex items-center justify-between">
            <div class="text-sm text-gray-700">
              Showing {{ transactions.from }} to {{ transactions.to }} of {{ transactions.total }} results
            </div>
            <div class="flex gap-2">
              <button
                v-for="link in transactions.links"
                :key="link.label"
                @click="link.url && router.visit(link.url)"
                :disabled="!link.url"
                v-html="link.label"
                :class="[
                  'px-3 py-1 rounded border',
                  link.active ? 'bg-primary text-white border-primary' : 'bg-white text-gray-700 border-gray-300',
                  !link.url ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-50'
                ]"
              />
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Image Modal -->
    <Modal :open="showImageModal" title="Giftcard Images" size="xl" @close="showImageModal = false">
      <template #default>
        <div v-if="previewTransaction?.images" class="grid grid-cols-2 gap-4">
          <div v-for="(img, i) in previewTransaction.images" :key="i" class="relative">
            <img :src="`/storage/${img}`" :alt="`Image ${i + 1}`" class="w-full h-48 object-cover rounded-lg" />
          </div>
        </div>
        <p v-else class="text-gray-500">No images available</p>
      </template>
    </Modal>

    <!-- Detail Modal -->
    <Modal
      :open="showDetailModal"
      title="Order details"
      size="xl"
      @close="showDetailModal = false"
    >
      <template #default>
        <div v-if="detailTransaction" class="space-y-4 text-sm text-gray-700">
          <div class="rounded-2xl border border-gray-100 bg-gray-50 px-4 py-3">
            <p class="text-xs uppercase text-gray-500">Reference</p>
            <p class="text-base font-semibold text-gray-900">#{{ detailTransaction.id }}</p>
          </div>
          <dl class="space-y-2">
            <div v-for="field in detailFields" :key="field.label" class="flex justify-between gap-4">
              <dt class="text-gray-500">{{ field.label }}</dt>
              <dd class="text-gray-900 font-semibold text-right">{{ field.value }}</dd>
            </div>
          </dl>
          <div v-if="detailTransaction.images?.length" class="pt-3 border-t border-gray-100">
            <p class="text-sm font-semibold text-gray-800 mb-2">Attachments</p>
            <div class="grid grid-cols-3 gap-3">
              <img
                v-for="(img, i) in detailTransaction.images"
                :key="i"
                :src="`/storage/${img}`"
                class="rounded-xl border border-gray-200 object-cover h-20 w-full"
                alt="Attachment"
                @click.stop="openSingleImage(img)"
              />
            </div>
          </div>
          <p v-if="detailTransaction.notes" class="text-sm text-gray-500">{{ detailTransaction.notes }}</p>
        </div>
      </template>
      <template #primary>
        Close
      </template>
    </Modal>

    <!-- Approve Modal -->
    <Modal
      :open="showApproveModal"
      title="Mark transaction as completed"
      :disabled="processing"
      @close="showApproveModal = false"
      @submit="submitApprove"
    >
      <template #default>
        <p class="text-sm text-gray-600">
          Mark order #{{ approveTarget?.id }} for {{ approveTarget?.user_name }} as completed? This will notify the user and record the settlement.
        </p>
      </template>
      <template #primary>
        <span class="flex items-center gap-2">
          <Loader2 v-if="processing" class="w-4 h-4 animate-spin" />
          {{ processing ? 'Processing…' : 'Mark as completed' }}
        </span>
      </template>
    </Modal>

    <Modal
      :open="showRejectModal"
      title="Reject Transaction"
      :disabled="processing"
      @close="showRejectModal = false"
      @submit="submitReject"
    >
      <template #default>
        <div class="space-y-4">
          <p class="text-sm text-gray-600">
            Are you sure you want to reject order #{{ rejectTarget?.id }}?
          </p>
          <div>
            <label class="block text-sm text-gray-700 mb-1">Reason (optional)</label>
            <textarea
              v-model="rejectReason"
              rows="3"
              class="w-full rounded-xl border-2 border-gray-200 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-primary/20"
              placeholder="Enter rejection reason..."
            />
          </div>
        </div>
      </template>
      <template #primary>
        <span class="flex items-center gap-2">
          <Loader2 v-if="processing" class="w-4 h-4 animate-spin" />
          {{ processing ? 'Processing…' : 'Reject' }}
        </span>
      </template>
    </Modal>
  </AdminLayout>
</template>
