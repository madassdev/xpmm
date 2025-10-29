<script setup>
import { ref, computed, watch } from 'vue'
import { post } from '@/lib/api'
import Card from '@/Components/ui/Card.vue'
import Button from '@/Components/ui/Button.vue'

import SoftSelect from '@/Components/bills/SoftSelect.vue'
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

// 2) Discos list (value matches backend expectation)
const discos = [
  { value: 'aedc', name: 'Abuja Electricity (AEDC)' },
  { value: 'ikedc', name: 'Ikeja Electricity (IKEDC)' },
  { value: 'ekedc', name: 'Eko Electricity (EKEDC)' },
  { value: 'ibedc', name: 'Ibadan Electricity (IBEDC)' },
  { value: 'bedc', name: 'Benin Electricity (BEDC)' },
  { value: 'eedc', name: 'Enugu Electricity (EEDC)' },
  { value: 'jed',  name: 'Jos Electricity (JED)' },
  { value: 'kedco', name: 'Kano Electricity (KEDCO)' },
  { value: 'kaedco', name: 'Kaduna Electricity (KAEDCO)' },
  { value: 'phed', name: 'Port Harcourt Electricity (PHED)' },
]

// Form state
const meterType = ref('')
const provider  = ref('')
const meterNo   = ref('')
const amount    = ref('')

const validating = ref(false)
const validationError = ref('')
const customer = ref(null)

const chips = [1000, 2000, 5000, 10000, 20000]
const onlyDigits = (v) => (v || '').replace(/[^\d]/g, '')
const setChip = (n) => (amount.value = String(n))

const providerOptions = computed(() => discos.map(d => ({ id: d.value, name: d.name })))

watch([provider, meterType, meterNo], () => {
  validationError.value = ''
  customer.value = null
})

const fiatBalance = computed(() => Number.isFinite(props.balance) ? Number(props.balance) : 0)
const amountValue = computed(() => Number(amount.value || 0))
const insufficient = computed(() => amountValue.value > fiatBalance.value)

const canSubmit = computed(() =>
  meterType.value && provider.value && meterNo.value.length >= 6 && amountValue.value > 0 && !insufficient.value && customer.value
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

const modalOpen  = ref(false)
const modalPhase = ref('confirm')
const modalTitle = ref('')
const modalMsg   = ref('')
const modalLines = ref([])
const modalErrorCode = ref('')

// Save beneficiary (meter)
const saveBeneficiary = ref(true)
const { add } = useBeneficiaries()

const validateMeter = async () => {
  validationError.value = ''
  customer.value = null
  if (!provider.value || !meterType.value || meterNo.value.length < 6) {
    validationError.value = 'Select provider, meter type and enter a valid meter number.'
    return
  }
  validating.value = true
  try {
    const { data } = await post('/bills/electricity/validate', {
      disco: provider.value,
      type: meterType.value,
      meter_no: meterNo.value,
    })
    customer.value = data?.customer || null
    validationError.value = ''
  } catch (error) {
    validationError.value = error?.response?.data?.message || 'Unable to validate meter.'
    customer.value = null
  } finally {
    validating.value = false
  }
}

const submit = async () => {
  if (!canSubmit.value) return
  if (insufficient.value) return
  const providerName = providerOptions.value.find(o => o.id === provider.value)?.name || provider.value

  modalOpen.value = true
  modalPhase.value = 'confirm'
  modalTitle.value = ''
  modalMsg.value = ''
  modalErrorCode.value = ''
  modalLines.value = [
    { label: 'Meter Type', value: meterTypes.find(m => m.value === meterType.value)?.label || meterType.value },
    { label: 'Provider',   value: providerName },
    { label: 'Meter',      value: meterNo.value },
    customer.value?.name ? { label: 'Customer', value: customer.value.name } : null,
    { label: 'Amount',     value: '₦' + Number(amount.value).toLocaleString() },
    { label: 'Payment Source',     value: 'Fiat Balance (NGN)' },
  ].filter(Boolean)
}

const onSubmitPin = async (pin) => {
  modalErrorCode.value = ''
  modalTitle.value = ''
  modalMsg.value = ''
  try {
    modalPhase.value = 'processing'
    const providerName = providerOptions.value.find(o => o.id === provider.value)?.name || provider.value
    const { data } = await post('/bills/electricity', {
      disco: provider.value,
      type: meterType.value,
      meter_no: meterNo.value,
      amount: Number(amount.value),
      pin,
    })

    modalPhase.value = 'success'
    modalTitle.value = 'Electricity purchase created'
    modalMsg.value = data?.message || 'Meter purchase initiated successfully.'

    if (Array.isArray(data?.tokens) && data.tokens.length) {
      modalLines.value = [
        ...modalLines.value,
        ...data.tokens.map((token, idx) => ({
          label: `Token ${idx + 1}`,
          value: token.units ? `${token.token} (${token.units} units)` : token.token,
        })),
      ]
    }

    if (saveBeneficiary.value) {
      const label = `${providerName} • ${meterNo.value.slice(0, 6)}…`
      add({
        service: 'electricity',
        kind: 'meter',
        providerId: provider.value,
        label,
        value: meterNo.value,
      })
    }
  } catch (error) {
    const msg = error?.response?.data?.message || 'Payment failed.'
    modalMsg.value = msg
    if (msg.toLowerCase().includes('pin')) {
      modalErrorCode.value = 'invalid_pin'
    }
    modalPhase.value = 'error'
  }
}

const closeModal = () => (modalOpen.value = false)
const retry = () => {
  modalPhase.value = 'confirm'
  modalTitle.value = ''
  modalMsg.value = ''
  modalErrorCode.value = ''
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
    <div class="mt-4 space-y-3">
      <AccountInputWithBeneficiaries
        v-model="meterNo"
        service="electricity"
        :providerId="provider"
        kind="meter"
        placeholder="Meter Number"
        :digitsOnly="true"
      />
      <div class="flex items-start gap-3">
        <Button size="sm" variant="outline" class="shrink-0" :disabled="validating" @click="validateMeter">
          <span v-if="validating" class="inline-flex items-center gap-2">
            <span class="w-3 h-3 rounded-full border-2 border-primary border-t-transparent animate-spin" />
            Validating…
          </span>
          <span v-else>Validate Meter</span>
        </Button>
        <div class="text-xs text-gray-600 flex-1">
          <template v-if="customer">
            <div class="font-medium text-gray-800">{{ customer.name }}</div>
            <div v-if="customer.address" class="text-gray-500">{{ customer.address }}</div>
          </template>
          <p v-else-if="validationError" class="text-red-600">{{ validationError }}</p>
          <p v-else class="text-gray-500">Confirm beneficiary details before payment.</p>
        </div>
      </div>
      <label class="inline-flex items-center gap-2 text-sm text-gray-600">
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

    <!-- Chips -->
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
      :errorCode="modalErrorCode"
      @close="closeModal"
      @submit="onSubmitPin"
      @primary="closeModal"
      @secondary="retry"
    />
  </Card>
</template>
