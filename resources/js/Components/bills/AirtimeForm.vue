<script setup>
import { ref, computed } from 'vue'
import { post } from '@/lib/api'

import Card from '@/Components/ui/Card.vue'
import Button from '@/Components/ui/Button.vue'

import BrandSelect from '@/Components/bills/BrandSelect.vue'
import BillsPayModal from '@/Components/bills/shared/BillsPayModal.vue'
import PhoneInputWithBeneficiaries from '@/Components/bills/shared/PhoneInputWithBeneficiaries.vue'
import { useBeneficiaries } from '@/composables/useBeneficiaries'

const props = defineProps({
  balance: { type: Number, default: 0 },
})

// Select data (ensure the logos exist in /public/img)
const networks = [
  { value: 'mtn', label: 'MTN', logo: '/img/mtn.png' },
  { value: 'airtel', label: 'Airtel', logo: '/img/airtel.png' },
  { value: 'glo', label: 'Glo', logo: '/img/glo.png' },
  { value: '9mobile', label: '9mobile', logo: '/img/9mobile.png' },
]

// Form state
const network = ref('')      // start empty; shows placeholder until picked
const phone = ref('')
const amount = ref('')

const chips = [200, 500, 1000, 2000, 5000]
const onlyDigits = (v) => (v || '').replace(/[^\d]/g, '')
const setChip = (n) => (amount.value = String(n))

const fiatBalance = computed(() => Number.isFinite(props.balance) ? Number(props.balance) : 0)
const amountValue = computed(() => Number(amount.value || 0))
const insufficient = computed(() => amountValue.value > fiatBalance.value)

const valid = computed(() =>
  network.value && phone.value?.length >= 7 && amountValue.value > 0 && !insufficient.value
)

const balanceHint = computed(() => {
  if (!amount.value) {
    return 'Payment will be deducted from your fiat balance.'
  }
  if (insufficient.value) {
    return `Insufficient funds. Available balance: ₦${fiatBalance.value.toLocaleString()}`
  }
  return 'Fiat balance selected.'
})

// Modal state
const modalOpen = ref(false)
const modalPhase = ref('processing') // 'processing'|'success'|'error'
const modalTitle = ref('')
const modalMsg = ref('')
const modalLines = ref([])
const modalErrorCode = ref('')

function randomMsg(ok) {
  const okMsgs = ['Your airtime has been delivered.', 'Purchase completed successfully.', 'We’ve credited the recipient’s number.']
  const errMsgs = ['We could not complete this payment right now.', 'Network timeout. Please try again.', 'Insufficient fiat balance.']
  return ok ? okMsgs[Math.floor(Math.random() * okMsgs.length)]
    : errMsgs[Math.floor(Math.random() * errMsgs.length)]
}

// Save beneficiary
const saveRecipient = ref(true)
const { add } = useBeneficiaries()

const submit = async () => {
  if (!valid.value) return
  if (insufficient.value) return
  modalOpen.value = true
  modalPhase.value = 'confirm'
  modalTitle.value = ''
  modalMsg.value = ''
  modalLines.value = [
    { label: 'Network', value: networks.find(n => n.value === network.value)?.label || network.value },
    { label: 'Phone', value: phone.value },
    { label: 'Amount', value: `₦${Number(amount.value).toLocaleString()}` },
    { label: 'Payment Source', value: 'Fiat Balance (NGN)' },
  ]
}

const onSubmitPin = async (pin) => {
  modalErrorCode.value = ''
  modalMsg.value = ''
  modalTitle.value = ''
  try {
    const payload = {
      network: network.value,
      phone: phone.value,
      amount: Number(amount.value),
      pin,
    }

    const { data } = await post('/bills/airtime', payload)
    // expect: { status: 'ok', tx_ref: '...', message: '...' }

    modalPhase.value = 'success'
    modalTitle.value = 'Airtime purchased'
    modalMsg.value = data?.message || 'Your airtime has been delivered.'

    if (saveRecipient.value) {
      const net = networks.find(n => n.value === network.value)
      const label = `${net ? net.label : network.value} • ${phone.value.slice(0, 7)}…`
      add({ service: 'airtime', kind: 'phone', providerId: network.value, label, value: phone.value })
    }
  } catch (err) {
    const msg = err?.response?.data?.message || 'Payment failed.'
    modalMsg.value = msg

    // If backend rejected the PIN, mark the error:
    if (msg.toLowerCase().includes('pin')) {
      modalErrorCode.value = 'invalid_pin'
    } else {
      modalErrorCode.value = ''
    }

    // Show error (modal will bounce back to PIN if invalid_pin)
    modalPhase.value = 'error'
  }


  return;
  // -> send to backend with the rest of your payload + pin
  // await api...
  // then set success/error + title/message as you already do

  // mock network call
  await new Promise(r => setTimeout(r, 1200))
  const ok = Math.random() < 0.7
  modalPhase.value = ok ? 'success' : 'error'
  modalTitle.value = ok ? 'Airtime purchased' : 'Payment failed'
  modalMsg.value = randomMsg(ok)

  if (ok && saveRecipient.value) {
    const net = networks.find(n => n.value === network.value)
    const label = `${net ? net.label : network.value} • ${phone.value.slice(0, 7)}…`
    add({
      service: 'airtime',
      kind: 'phone',
      providerId: network.value,
      label,
      value: phone.value,
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
    modalTitle.value = ok ? 'Airtime purchased' : 'Payment failed'
    modalMsg.value = randomMsg(ok)
  }, 900)
}
</script>

<template>
  <Card class="p-5">
    <div class="text-sm font-medium text-gray-800 mb-3">Airtime</div>

    <!-- 1) Network -->
    <BrandSelect v-model="network" :options="networks" placeholder="Select Network" />

    <!-- 2) Phone with beneficiaries -->
    <div class="mt-4">
      <PhoneInputWithBeneficiaries v-model="phone" service="airtime" :providerId="network"
        placeholder="Enter Phone Number" />
      <label class="mt-2 inline-flex items-center gap-2 text-sm text-gray-600">
        <input type="checkbox" v-model="saveRecipient"
          class="rounded border-gray-300 text-primary focus:ring-primary" />
        Save recipient
      </label>
    </div>

    <!-- 3) Fiat balance -->
    <div class="mt-4 rounded-2xl border border-primary/20 bg-primary/5 px-4 py-4">
      <div class="text-xs uppercase tracking-wide text-primary/80">Available Balance</div>
      <div class="mt-1 text-lg font-semibold text-primary">₦{{ fiatBalance.toLocaleString() }}</div>
      <p class="mt-1 text-xs text-primary/70">
        Bills will be charged from your fiat wallet.
      </p>
    </div>

    <!-- 4) Amount -->
    <div class="mt-4 rounded-2xl bg-gray-50 border border-gray-200 px-4 py-4">
      <input :value="amount" @input="e => amount = onlyDigits(e.target.value)" type="text" inputmode="numeric"
        placeholder="Enter Amount"
        class="w-full bg-transparent placeholder-gray-400 text-gray-900 border-none ring-0 focus:ring-0 focus:border-0 focus:outline-none outline-none" />
    </div>
    <p class="mt-2 text-xs" :class="insufficient ? 'text-red-600' : 'text-gray-500'">
      {{ balanceHint }}
    </p>

    <!-- Chips -->
    <div class="mt-3 grid grid-cols-2 sm:grid-cols-5 gap-3">
      <button v-for="c in chips" :key="c" @click="setChip(c)"
        class="rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-semibold text-gray-800 hover:bg-gray-50">
        ₦{{ c.toLocaleString() }}
      </button>
    </div>

    <!-- CTA -->
    <Button class="w-full mt-6" :disabled="!valid" @click="submit">
      Purchase Airtime
    </Button>

    <!-- Modal -->
    <BillsPayModal 
      :open="modalOpen" 
      :phase="modalPhase" 
      :errorCode="modalErrorCode" 
      :title="modalTitle"
      :message="modalMsg" 
      :details="modalLines" 
      @submit="onSubmitPin" 
      @close="closeModal" 
      @primary="closeModal"
      @secondary="retry" 
    />
  </Card>
</template>
