<script setup>
import { computed, nextTick, onBeforeUnmount, reactive, ref, watch } from 'vue'
import Card from '@/Components/ui/Card.vue'
import Modal from '@/Components/ui/Modal.vue'
import { Building2, Loader2, Pencil, Plus, Trash2 } from 'lucide-vue-next'
import { destroy, post, put } from '@/lib/api'

const props = defineProps({
  bankAccounts: { type: Array, default: () => [] },
  banks: { type: Array, default: () => [] },
})

const accounts = ref([...props.bankAccounts])
const modalOpen = ref(false)
const editingId = ref(null)
const submitting = ref(false)
const deletingId = ref(null)
const verifying = ref(false)
const verifyError = ref('')
const submitError = ref('')
const formErrors = ref({})
const skipVerify = ref(false)

const form = reactive({
  bank_code: '',
  account_number: '',
  account_name: '',
  is_primary: false,
})

const bankOptions = computed(() => props.banks)
const canSubmit = computed(() =>
  !!form.bank_code &&
  form.account_number.length === 10 &&
  !!form.account_name &&
  !submitting.value &&
  !verifying.value
)

let verifyTimer
let verifyToken

watch(
  () => [form.bank_code, form.account_number],
  ([bankCode, accountNumber]) => {
    if (skipVerify.value) {
      verifyError.value = ''
      verifying.value = false
      return
    }

    formErrors.value = { ...formErrors.value, account_number: undefined }
    submitError.value = ''

    if (verifyTimer) {
      clearTimeout(verifyTimer)
      verifyTimer = undefined
    }
    verifying.value = false
    verifyError.value = ''

    if (!bankCode || accountNumber.length !== 10) {
      if (accountNumber.length < 10) {
        form.account_name = ''
      }
      return
    }

    form.account_name = ''
    verifying.value = true
    verifyTimer = setTimeout(() => verifyAccount(bankCode, accountNumber), 400)
  }
)

onBeforeUnmount(() => {
  if (verifyTimer) {
    clearTimeout(verifyTimer)
    verifyTimer = undefined
  }
})

function resetForm(values = null) {
  skipVerify.value = true
  form.bank_code = values?.bank_code ?? ''
  form.account_number = values?.account_number ?? ''
  form.account_name = values?.account_name ?? ''
  form.is_primary = values?.is_primary ?? false
  verifying.value = false
  submitError.value = ''
  verifyError.value = ''
  formErrors.value = {}

  nextTick(() => {
    skipVerify.value = false
  })
}

function openCreateModal() {
  editingId.value = null
  resetForm()
  modalOpen.value = true
}

function openEditModal(account) {
  editingId.value = account.id
  resetForm(account)
  modalOpen.value = true
}

function closeModal() {
  modalOpen.value = false
  resetForm()
}

function handleAccountNumberInput(value) {
  const digits = value.replace(/\D/g, '').slice(0, 10)
  form.account_number = digits
}

async function verifyAccount(bankCode, accountNumber) {
  const currentToken = Symbol('verify')
  verifyToken = currentToken

  try {
    const { data } = await post('/api/settings/bank-accounts/verify', {
      bank_code: bankCode,
      account_number: accountNumber,
    })

    if (verifyToken !== currentToken) return

    form.account_name = data.account_name || ''
    verifyError.value = ''
  } catch (error) {
    if (verifyToken !== currentToken) return

    form.account_name = ''
    verifyError.value = error.response?.data?.message || 'Verification failed. Please try again.'
  } finally {
    if (verifyToken === currentToken) {
      verifying.value = false
    }
  }
}

function extractAccount(payload) {
  if (!payload) return null
  return payload.data ?? payload
}

async function submit() {
  if (!canSubmit.value) return

  submitting.value = true
  submitError.value = ''
  formErrors.value = {}

  const payload = {
    bank_code: form.bank_code,
    account_number: form.account_number,
    account_name: form.account_name,
    is_primary: form.is_primary,
  }

  try {
    if (editingId.value) {
      const { data } = await put(`/api/settings/bank-accounts/${editingId.value}`, payload)
      const updated = extractAccount(data)
      if (updated) {
        const index = accounts.value.findIndex(acc => acc.id === updated.id)
        if (index !== -1) {
          accounts.value.splice(index, 1, updated)
        }
      }
    } else {
      const { data } = await post('/api/settings/bank-accounts', payload)
      const created = extractAccount(data)
      if (created) {
        accounts.value.unshift(created)
      }
    }

    closeModal()
  } catch (error) {
    if (error.response?.data?.errors) {
      formErrors.value = error.response.data.errors
    } else {
      submitError.value = error.response?.data?.message || 'Unable to save bank account.'
    }
  } finally {
    submitting.value = false
  }
}

async function deleteAccount(account) {
  if (deletingId.value || !window.confirm(`Remove ${account.bank_name} • ${account.account_number}?`)) {
    return
  }

  deletingId.value = account.id
  submitError.value = ''

  try {
    await destroy(`/api/settings/bank-accounts/${account.id}`)
    accounts.value = accounts.value.filter(acc => acc.id !== account.id)
  } catch (error) {
    submitError.value = error.response?.data?.message || 'Unable to remove bank account.'
  } finally {
    deletingId.value = null
  }
}
</script>

<template>
  <div class="space-y-5">
    <Card class="p-6">
      <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
          <h3 class="text-[15px] font-semibold text-[#0E3A63] mb-1">Bank Accounts</h3>
          <p class="text-sm text-gray-500">
            Manage the accounts we can send your payouts to. Keep your details up to date.
          </p>
        </div>

        <button
          type="button"
          class="inline-flex items-center gap-2 rounded-[18px] bg-primary px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:opacity-90 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40 focus-visible:ring-offset-2 focus-visible:ring-offset-white"
          @click="openCreateModal"
        >
          <Plus class="h-4 w-4" />
          Add account
        </button>
      </div>

      <div class="mt-6 space-y-4">
        <div v-if="submitError" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-600">
          {{ submitError }}
        </div>

        <div v-if="accounts.length" class="grid gap-4 sm:grid-cols-2">
          <article
            v-for="account in accounts"
            :key="account.id"
            class="group flex flex-col gap-3 rounded-2xl border border-gray-200 bg-[#F7F8FA] p-4 transition hover:border-primary/40 hover:bg-white"
          >
            <div class="flex items-center justify-between gap-3">
              <div class="flex items-center gap-3">
                <div class="grid h-11 w-11 place-items-center rounded-xl bg-white text-primary shadow-sm">
                  <Building2 class="h-5 w-5" />
                </div>
                <div>
                  <p class="text-sm font-semibold text-gray-900">{{ account.bank_name }}</p>
                  <p class="text-xs text-gray-500 uppercase tracking-wide">Bank Code • {{ account.bank_code }}</p>
                </div>
              </div>
              <span
                v-if="account.is_primary"
                class="rounded-full bg-primary/10 px-3 py-1 text-[11px] font-medium uppercase text-primary"
              >
                Primary
              </span>
            </div>

            <div class="flex flex-col gap-1">
              <p class="text-sm font-medium text-gray-800">{{ account.account_name || '—' }}</p>
              <p class="text-xs text-gray-500">Acct No: {{ account.account_number }}</p>
            </div>

            <div class="mt-auto flex flex-wrap gap-2 pt-2">
              <button
                type="button"
                class="inline-flex items-center gap-2 rounded-full border border-gray-300 px-3 py-1.5 text-sm text-gray-700 transition hover:border-primary/40 hover:text-primary focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40 focus-visible:ring-offset-2 focus-visible:ring-offset-white"
                @click="openEditModal(account)"
              >
                <Pencil class="h-4 w-4" />
                Edit
              </button>
              <button
                type="button"
                class="inline-flex items-center gap-2 rounded-full border border-red-200 px-3 py-1.5 text-sm text-red-600 transition hover:bg-red-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-300 focus-visible:ring-offset-2 focus-visible:ring-offset-white disabled:opacity-60"
                :disabled="deletingId === account.id"
                @click="deleteAccount(account)"
              >
                <Trash2 class="h-4 w-4" />
                <span v-if="deletingId === account.id">Removing…</span>
                <span v-else>Delete</span>
              </button>
            </div>
          </article>
        </div>

        <div
          v-else
          class="flex flex-col items-center justify-center gap-3 rounded-2xl border border-dashed border-gray-300 bg-white px-6 py-10 text-center"
        >
          <Building2 class="h-10 w-10 text-gray-300" />
          <div>
            <p class="text-sm font-semibold text-gray-800">No bank accounts yet</p>
            <p class="text-sm text-gray-500">Add your first payout destination to get started.</p>
          </div>
          <button
            type="button"
            class="inline-flex items-center gap-2 rounded-full border border-primary/30 bg-primary/10 px-4 py-2 text-sm font-medium text-primary transition hover:bg-primary/20 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40 focus-visible:ring-offset-2 focus-visible:ring-offset-white"
            @click="openCreateModal"
          >
            <Plus class="h-4 w-4" />
            Add account
          </button>
        </div>
      </div>
    </Card>

    <Modal
      title="Bank account"
      size="lg"
      :open="modalOpen"
      :disabled="submitting || verifying"
      @close="closeModal"
      @submit="submit"
    >
      <div class="space-y-4">
        <div>
          <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Bank</label>
          <div class="mt-1">
            <select
              v-model="form.bank_code"
              class="w-full rounded-[18px] border border-[#E7EBF2] bg-[#F7F8FA] px-4 py-3 text-[15px] text-gray-800 focus:border-primary/60 focus:outline-none"
            >
              <option value="" disabled>Select a bank</option>
              <option v-for="bank in bankOptions" :key="bank.code" :value="bank.code">
                {{ bank.name }}
              </option>
            </select>
            <p v-if="formErrors.bank_code" class="mt-1 text-xs text-red-500">
              {{ formErrors.bank_code[0] }}
            </p>
          </div>
        </div>

        <div>
          <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Account number</label>
          <div class="mt-1">
            <input
              :value="form.account_number"
              inputmode="numeric"
              maxlength="10"
              class="w-full rounded-[18px] border border-[#E7EBF2] bg-[#F7F8FA] px-4 py-3 text-[15px] text-gray-800 focus:border-primary/60 focus:outline-none"
              placeholder="0123456789"
              @input="handleAccountNumberInput($event.target.value)"
            />
            <p v-if="formErrors.account_number" class="mt-1 text-xs text-red-500">
              {{ formErrors.account_number[0] }}
            </p>
          </div>
        </div>

        <div>
          <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Account name</label>
          <div class="mt-1">
            <input
              :value="form.account_name"
              readonly
              class="w-full cursor-not-allowed rounded-[18px] border border-[#E7EBF2] bg-gray-100 px-4 py-3 text-[15px] text-gray-700"
              placeholder="Will auto-fill after verification"
            />
          </div>
          <div class="mt-1 flex items-center gap-2 text-xs">
            <Loader2 v-if="verifying" class="h-4 w-4 animate-spin text-primary" />
            <span v-if="verifying" class="text-primary">Verifying account…</span>
            <span v-else-if="verifyError" class="text-red-500">{{ verifyError }}</span>
            <span
              v-else-if="form.account_name"
              class="inline-flex items-center gap-1 rounded-full bg-green-100 px-2 py-1 text-[11px] font-medium uppercase text-green-600"
            >
              Verified
            </span>
            <span v-else class="text-gray-400">Enter a valid bank and 10-digit account number to verify.</span>
          </div>
        </div>
      </div>

      <template #primary>
        <span v-if="submitting">{{ editingId ? 'Updating…' : 'Saving…' }}</span>
        <span v-else>{{ editingId ? 'Save changes' : 'Add account' }}</span>
      </template>
    </Modal>
  </div>
</template>
