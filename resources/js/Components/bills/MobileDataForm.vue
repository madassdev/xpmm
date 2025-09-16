<script setup>
import { ref, computed, watchEffect } from 'vue'
import Card from '@/Components/ui/Card.vue'
import Button from '@/Components/ui/Button.vue'

import BrandSelect from '@/Components/bills/BrandSelect.vue'
import SoftSelect from '@/Components/bills/SoftSelect.vue'          // for "Select Wallet"
import DataBundleSelect from '@/Components/bills/DataBundleSelect.vue'
import BillsPayModal from '@/Components/bills/shared/BillsPayModal.vue'
import PhoneInputWithBeneficiaries from '@/Components/bills/shared/PhoneInputWithBeneficiaries.vue'
import { useBeneficiaries } from '@/composables/useBeneficiaries'
import AssetSelect from './AssetSelect.vue'

// Providers (ensure logos exist)
const networks = [
  { value: 'mtn',     label: 'MTN',     logo: '/img/mtn.png' },
  { value: 'airtel',  label: 'Airtel',  logo: '/img/airtel.png' },
  { value: 'glo',     label: 'Glo',     logo: '/img/glo.png' },
  { value: '9mobile', label: '9mobile', logo: '/img/9mobile.png' },
]

// Wallet / Asset options (simple)
const wallets = [
  { value: 'main', label: 'Main Wallet' },
  { value: 'btc',  label: 'BTC Wallet'  },
  { value: 'usdt', label: 'USDT Wallet' },
]

const assets = [
  { code: 'BTC',  label: 'BTC',  balance: '0.00', logo: '/img/btc.png'  },
  { code: 'USDT', label: 'USDT', balance: '0.00', logo: '/img/usdt.png' },
  { code: 'NGN',  label: 'NGN',  balance: '0.00', logo: '/img/ngn.png'  },
]

// Mock fetch plans (replace when API is ready)
async function fetchPlans(provider) {
  await new Promise(r => setTimeout(r, 400))
  return [
    { id: 'p1', name: '120mb - 20 days',      price: 150 },
    { id: 'p2', name: '12.5gb - 30 days',     price: 150 },
    { id: 'p3', name: '30gb - 30 days',       price: 9000 },
    { id: 'p4', name: '1.5TB mb - 365 days',  price: 225000 },
  ]
}

// Form state
const provider = ref('') // start unselected
const phone    = ref('')
const wallet   = ref('')
const bundleId = ref('')
const plans    = ref([])
const loadingPlans = ref(false)
const asset   = ref('BTC')


// Load plans when provider changes
watchEffect(async () => {
  if (!provider.value) { plans.value = []; return }
  loadingPlans.value = true
  plans.value = await fetchPlans(provider.value)
  loadingPlans.value = false
})

const canSubmit = computed(() =>
  provider.value && phone.value.length >= 7 && wallet.value && bundleId.value
)

// Modal state
const modalOpen  = ref(false)
const modalPhase = ref('processing') // 'processing'|'success'|'error'
const modalTitle = ref('')
const modalMsg   = ref('')
const modalLines = ref([])

function randomMsg(ok) {
  const okMsgs  = ['Data purchase completed.', 'Plan delivered successfully.', 'Beneficiary has been credited.']
  const errMsgs = ['We could not complete this payment.', 'Provider timeout — please retry.', 'Insufficient balance on wallet.']
  return ok ? okMsgs[Math.floor(Math.random()*okMsgs.length)]
            : errMsgs[Math.floor(Math.random()*errMsgs.length)]
}

// Save beneficiary
const saveRecipient = ref(true)
const { add } = useBeneficiaries()

const submit = async () => {
  if (!canSubmit.value) return
  const plan = plans.value.find(p => p.id === bundleId.value)
  modalOpen.value = true
  modalPhase.value = 'processing'
  modalTitle.value = ''
  modalMsg.value = ''
  modalLines.value = [
    { label: 'Network', value: networks.find(n=>n.value===provider.value)?.label || provider.value },
    { label: 'Phone',   value: phone.value },
    { label: 'Bundle',  value: plan?.name || '' },
    { label: 'Amount',  value: '₦' + (plan?.price ?? 0).toLocaleString() },
    { label: 'Wallet',  value: wallets.find(w=>w.value===wallet.value)?.label || wallet.value },
  ]

  // mock API
  await new Promise(r => setTimeout(r, 1200))
  const ok = Math.random() < 0.7
  modalPhase.value = ok ? 'success' : 'error'
  modalTitle.value  = ok ? 'Data purchased' : 'Payment failed'
  modalMsg.value    = randomMsg(ok)

  if (ok && saveRecipient.value) {
    const net = networks.find(n => n.value === provider.value)
    const label = `${net ? net.label : provider.value} • ${phone.value.slice(0,7)}…`
    add({
      service: 'data',
      kind: 'phone',
      providerId: provider.value,
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
    modalTitle.value  = ok ? 'Data purchased' : 'Payment failed'
    modalMsg.value    = randomMsg(ok)
  }, 900)
}
</script>

<template>
  <Card class="p-5">
    <div class="text-sm font-medium text-gray-800 mb-3">Data</div>

    <!-- 1) Network -->
    <div class="rounded-2xl ring-1 ring-transparent focus-within:ring-orange-300 transition">
      <BrandSelect v-model="provider" :options="networks" placeholder="Select Network" />
    </div>

    <!-- 2) Phone with beneficiaries -->
    <div class="mt-4">
      <PhoneInputWithBeneficiaries
        v-model="phone"
        service="data"
        :providerId="provider"
        placeholder="Enter Phone Number"
      />
      <label class="mt-2 inline-flex items-center gap-2 text-sm text-gray-600">
        <input type="checkbox" v-model="saveRecipient" class="rounded border-gray-300 text-primary focus:ring-primary" />
        Save recipient
      </label>
    </div>

    <!-- 3) Wallet -->
    <div class="mt-4">
      <AssetSelect v-model="asset" :options="assets" placeholder="BTC" />
    </div>

    <!-- 4) Bundle -->
    <div class="mt-4">
      <DataBundleSelect v-model="bundleId" :options="plans" placeholder="Select Bundle" />
    </div>

    <!-- CTA -->
    <Button class="w-full mt-6" :disabled="!canSubmit" @click="submit">
      Purchase Data
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
