<script setup>
import { ref, computed, watch } from 'vue'
import Card from '@/Components/ui/Card.vue'
import Button from '@/Components/ui/Button.vue'

import SoftSelect from '@/Components/bills/SoftSelect.vue' // for Meter Type only
import ProviderLongSelect from '@/Components/bills/ProviderLongSelect.vue'
import AccountInputWithBeneficiaries from '@/Components/bills/shared/AccountInputWithBeneficiaries.vue'
import BillsPayModal from '@/Components/bills/shared/BillsPayModal.vue'
import { useBeneficiaries } from '@/composables/useBeneficiaries'

const props = defineProps({
  balance: { type: Number, default: 0 },
})

// 1) Meter types
const meterTypes = [
  { value: 'prepaid',  label: 'Prepaid'  },
  { value: 'postpaid', label: 'Postpaid' },
]

// 2) Discos list (base names)
const discos = [
  'Abuja Electricity', 'Ikeja Electricity', 'Eko Electricity',
  'Ibadan Electricity', 'Benin Electricity', 'Enugu Electricity',
  'Jos Electricity', 'Kano Electricity', 'Kaduna Electricity',
  'Port Harcourt Electricity'
]

// Form state
const meterType = ref('')        // prepaid | postpaid
const provider  = ref('')        // computed id
const meterNo   = ref('')
const amount    = ref('')

// Amount chips (tweak to your liking)
const chips = [1000, 2000, 5000, 10000, 20000]
const onlyDigits = (v) => (v || '').replace(/[^\d]/g, '')
const setChip = (n) => (amount.value = String(n))

// Derived provider options from meterType
const providerOptions = computed(() => {
  if (!meterType.value) return []
  const suffix = meterTypes.find(m => m.value === meterType.value)?.label || ''
  return discos.map(name => {
    const id = `${name.toLowerCase().replace(/\s+/g, '-')}-${meterType.value}`
    return { id, name: `${name} - ${suffix}` }
  })
})

// Reset provider when type changes
watch(meterType, () => { provider.value = '' })

// Validation
const fiatBalance = computed(() => Number.isFinite(props.balance) ? Number(props.balance) : 0)
const amountValue = computed(() => Number(amount.value || 0))
const insufficient = computed(() => amountValue.value > fiatBalance.value)
const canSubmit = computed(() =>
  meterType.value && provider.value && meterNo.value.length >= 6 && amountValue.value > 0 && !insufficient.value
)

const balanceHint = computed(() => {
  if (!amount.value) {
    return 'Payment will be deducted from your fiat balance.'
  }
  if (insufficient.value) {
    return `Insufficient funds. Available balance: ₦${fiatBalance.value.toLocaleString()}`
  }
  return 'Fiat balance selected for this payment.'
})

// Modal state
const modalOpen  = ref(false)
const modalPhase = ref('processing') // 'processing'|'success'|'error'
const modalTitle = ref('')
const modalMsg   = ref('')
const modalLines = ref([])

function randomMsg(ok) {
  const okMsgs  = ['Payment successful.', 'Meter recharged successfully.', 'Token delivered to your meter account.']
  const errMsgs = ['Payment could not be completed.', 'Disco timeout — please retry.', 'Insufficient fiat balance.']
  return ok ? okMsgs[Math.floor(Math.random()*okMsgs.length)]
            : errMsgs[Math.floor(Math.random()*errMsgs.length)]
}

// Save beneficiary (meter)
const saveBeneficiary = ref(true)
const { add } = useBeneficiaries()

const submit = async () => {
  if (!canSubmit.value) return
  if (insufficient.value) return
  modalOpen.value = true
  modalPhase.value = 'processing'
  modalTitle.value = ''
  modalMsg.value   = ''
  const providerName = providerOptions.value.find(o => o.id === provider.value)?.name || provider.value
  modalLines.value = [
    { label: 'Meter Type', value: meterTypes.find(m=>m.value===meterType.value)?.label || meterType.value },
    { label: 'Provider',   value: providerName },
    { label: 'Meter',      value: meterNo.value },
    { label: 'Amount',     value: '₦' + Number(amount.value).toLocaleString() },
    { label: 'Payment Source',     value: 'Fiat Balance (NGN)' },
  ]

  // mock API
  await new Promise(r => setTimeout(r, 1200))
  const ok = Math.random() < 0.7
  modalPhase.value = ok ? 'success' : 'error'
  modalTitle.value  = ok ? 'Electricity paid' : 'Payment failed'
  modalMsg.value    = randomMsg(ok)

  if (ok && saveBeneficiary.value) {
    const label = `${providerName} • ${meterNo.value.slice(0,6)}…`
    add({
      service: 'electricity',
      kind: 'meter',
      providerId: provider.value,
      label,
      value: meterNo.value,
    })
  }
}

const closeModal = () => (modalOpen.value = false)
const retry = () => {
  modalOpen.value = true
  modalPhase.value = 'processing'
  modalTitle.value = ''
  modalMsg.value = ''
  setTimeout(() => {
    const ok = Math.random() < 0.7
    modalPhase.value = ok ? 'success' : 'error'
    modalTitle.value  = ok ? 'Electricity paid' : 'Payment failed'
    modalMsg.value    = randomMsg(ok)
  }, 900)
}
</script>

<template>
  <Card class="p-5">
    <div class="text-sm font-medium text-gray-800 mb-3">Electricity</div>

    <!-- Meter Type (orange focus ring per design) -->
    <div class="rounded-2xl ring-1 ring-transparent focus-within:ring-orange-300 transition">
      <SoftSelect v-model="meterType" :options="meterTypes" placeholder="Meter Type" />
    </div>

    <!-- Service Provider (scroll list) -->
    <div class="mt-4">
      <ProviderLongSelect
        v-model="provider"
        :options="providerOptions"
        :disabled="!meterType"
        placeholder="Service Provider"
      />
    </div>

    <!-- Fiat balance -->
    <div class="mt-4 rounded-2xl border border-primary/20 bg-primary/5 px-4 py-4">
      <div class="text-xs uppercase tracking-wide text-primary/80">Available Balance</div>
      <div class="mt-1 text-lg font-semibold text-primary">₦{{ fiatBalance.toLocaleString() }}</div>
      <p class="mt-1 text-xs text-primary/70">
        Electricity payments draw from your fiat wallet.
      </p>
    </div>

    <!-- Meter Number with beneficiaries -->
    <div class="mt-4">
      <AccountInputWithBeneficiaries
        v-model="meterNo"
        service="electricity"
        :providerId="provider"
        kind="meter"
        placeholder="Meter Number"
        :digitsOnly="true"
      />
      <label class="mt-2 inline-flex items-center gap-2 text-sm text-gray-600">
        <input type="checkbox" v-model="saveBeneficiary" class="rounded border-gray-300 text-primary focus:ring-primary" />
        Save meter
      </label>
    </div>

    <!-- Amount -->
    <div class="mt-4 rounded-2xl bg-gray-50 border border-gray-200 px-4 py-4">
      <input
        :value="amount"
        @input="e => amount = onlyDigits(e.target.value)"
        type="text"
        inputmode="numeric"
        placeholder="Amount"
        class="w-full bg-transparent placeholder-gray-400 text-gray-900 border-none ring-0 focus:ring-0 focus:border-0 focus:outline-none outline-none"
      />
    </div>

    <!-- Chips (new) -->
    <div class="mt-3 grid grid-cols-2 sm:grid-cols-5 gap-3">
      <button
        v-for="c in chips"
        :key="c"
        @click="setChip(c)"
        class="rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-semibold text-gray-800 hover:bg-gray-50"
      >
        ₦{{ c.toLocaleString() }}
      </button>
    </div>

    <p class="mt-2 text-xs" :class="insufficient ? 'text-red-600' : 'text-gray-500'">
      {{ balanceHint }}
    </p>

    <!-- CTA -->
    <Button class="w-full mt-6" :disabled="!canSubmit" @click="submit">
      Purchase
    </Button>

    <!-- Modal -->
    <BillsPayModal
      :open="modalOpen"
      :phase="modalPhase"
      :title="modalTitle"
      :message="modalMsg"
      :details="modalLines"
      @close="closeModal"
      @primary="closeModal"
      @secondary="retry"
    />
  </Card>
</template>
