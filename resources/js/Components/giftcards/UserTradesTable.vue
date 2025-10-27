<script setup>
import { computed, ref } from 'vue'
import Modal from '@/Components/ui/Modal.vue'

const props = defineProps({
  trades: { type: Array, default: () => [] },
})

const statusClasses = {
  pending: 'bg-yellow-100 text-yellow-800',
  processing: 'bg-blue-100 text-blue-800',
  completed: 'bg-green-100 text-green-800',
  rejected: 'bg-red-100 text-red-700',
}

const selected = ref(null)
const detailOpen = ref(false)
const previewImage = ref(null)
const showImageModal = ref(false)

const detailItems = computed(() => {
  if (!selected.value) return []
  return [
    { label: 'Giftcard', value: selected.value.giftcard },
    { label: 'Type', value: selected.value.type },
    { label: 'Currency', value: selected.value.currency },
    { label: 'Amount', value: `${selected.value.currency} ${Number(selected.value.amount).toLocaleString()}` },
    { label: 'Quantity', value: selected.value.quantity },
    { label: 'Wallet', value: selected.value.payment_method ? selected.value.payment_method.toUpperCase() : 'FIAT (NGN)' },
    { label: 'Status', value: selected.value.status_label },
    { label: 'Card kind', value: selected.value.card_type ? selected.value.card_type : '—' },
  ]
})

function openDetail(trade) {
  selected.value = trade
  detailOpen.value = true
}

function openImage(src) {
  previewImage.value = src
  showImageModal.value = true
}
</script>

<template>
  <div class="rounded-2xl border border-gray-200 bg-white overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
      <div>
        <p class="text-base font-semibold text-gray-900">Giftcard trades</p>
        <p class="text-sm text-gray-500">Latest buy & sell activity.</p>
      </div>
    </div>

    <div v-if="trades.length" class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-100 text-sm">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left font-semibold text-gray-500 uppercase text-xs tracking-wide">Date</th>
            <th class="px-6 py-3 text-left font-semibold text-gray-500 uppercase text-xs tracking-wide">Giftcard</th>
            <th class="px-6 py-3 text-left font-semibold text-gray-500 uppercase text-xs tracking-wide">Type</th>
            <th class="px-6 py-3 text-left font-semibold text-gray-500 uppercase text-xs tracking-wide">Amount</th>
            <th class="px-6 py-3 text-left font-semibold text-gray-500 uppercase text-xs tracking-wide">Qty</th>
            <th class="px-6 py-3 text-left font-semibold text-gray-500 uppercase text-xs tracking-wide">Wallet</th>
            <th class="px-6 py-3 text-left font-semibold text-gray-500 uppercase text-xs tracking-wide">Status</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 text-gray-700">
          <tr
            v-for="trade in trades"
            :key="trade.id"
            class="hover:bg-gray-50/60 cursor-pointer"
            @click="openDetail(trade)"
          >
            <td class="px-6 py-4">{{ trade.date }}</td>
            <td class="px-6 py-4">
              <div class="font-medium text-gray-900">{{ trade.giftcard }}</div>
            </td>
            <td class="px-6 py-4">
              <span class="inline-flex items-center rounded-full bg-orange-50 px-2.5 py-0.5 text-xs font-semibold text-orange-600">
                {{ trade.type }}
              </span>
            </td>
            <td class="px-6 py-4">
              {{ trade.currency }} {{ Number(trade.amount).toLocaleString() }}
            </td>
            <td class="px-6 py-4">{{ trade.quantity }}</td>
            <td class="px-6 py-4 text-gray-500 text-sm uppercase">{{ trade.payment_method || 'FIAT (NGN)' }}</td>
            <td class="px-6 py-4">
              <span
                class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold capitalize"
                :class="statusClasses[trade.status] || 'bg-gray-100 text-gray-700'"
              >
                {{ trade.status_label }}
              </span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-else class="px-6 py-10 text-center text-gray-500">
      <p class="font-medium text-gray-700">No trades yet</p>
      <p class="text-sm text-gray-500">Your completed and pending giftcard orders will show up here.</p>
    </div>

    <Modal
      :open="detailOpen"
      title="Trade details"
      size="xl"
      @close="detailOpen = false"
    >
      <template #default>
        <div v-if="selected" class="space-y-3 text-sm text-gray-700">
          <div class="rounded-2xl border border-gray-100 bg-gray-50 px-4 py-3">
            <p class="text-xs uppercase text-gray-500">Placed on</p>
            <p class="text-base font-semibold text-gray-900">{{ selected.date }}</p>
          </div>
          <dl class="space-y-2">
            <div v-for="item in detailItems" :key="item.label" class="flex justify-between gap-4">
              <dt class="text-gray-500">{{ item.label }}</dt>
              <dd class="text-gray-900 font-medium text-right">{{ item.value || '—' }}</dd>
            </div>
          </dl>
          <div v-if="selected.images?.length" class="pt-3 border-t border-gray-100">
            <p class="text-sm font-semibold text-gray-800 mb-2">Attachments</p>
            <div class="grid grid-cols-3 gap-3">
              <img
                v-for="(img, i) in selected.images"
                :key="i"
                :src="`/storage/${img}`"
                class="rounded-xl border border-gray-200 object-cover h-20 w-full"
                alt="Giftcard attachment"
                @click.stop="openImage(`/storage/${img}`)"
              />
            </div>
          </div>
          <p v-if="selected.notes" class="text-sm text-gray-500">{{ selected.notes }}</p>
        </div>
      </template>
      <template #primary>
        Close
      </template>
    </Modal>

    <Modal
      :open="showImageModal"
      title="Attachment preview"
      size="xl"
      @close="showImageModal = false"
    >
      <template #default>
        <div class="w-full">
          <img
            v-if="previewImage"
            :src="previewImage"
            class="w-full rounded-2xl object-contain"
            alt="Giftcard attachment preview"
          />
        </div>
      </template>
      <template #primary>
        Close
      </template>
    </Modal>
  </div>
</template>
