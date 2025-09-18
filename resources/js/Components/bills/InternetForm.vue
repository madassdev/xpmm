<script setup>
import { ref, computed, watchEffect } from 'vue'
import Card from '@/Components/ui/Card.vue'
import Button from '@/Components/ui/Button.vue'

import BrandSelect from '@/Components/bills/BrandSelect.vue'
import AssetSelect from '@/Components/bills/AssetSelect.vue'
import DataBundleSelect from '@/Components/bills/DataBundleSelect.vue'
import AccountInputWithBeneficiaries from '@/Components/bills/shared/AccountInputWithBeneficiaries.vue'
import BillsPayModal from '@/Components/bills/shared/BillsPayModal.vue'
import { useBeneficiaries } from '@/composables/useBeneficiaries'

// ISPs (add logos in /public/img; placeholders are fine)
const providers = [
  { value: 'spectranet', label: 'Spectranet', logo: '/img/spectranet.png' },
  { value: 'smile',      label: 'Smile',      logo: '/img/smile.png' },
  { value: 'swift',      label: 'Swift',      logo: '/img/swift.png' },
  { value: 'ipnx',       label: 'ipNX',       logo: '/img/ipnx.png' },
  { value: 'fiberone',   label: 'FiberOne',   logo: '/img/fiberone.png' },
]

// Assets to pay with (same pattern as Airtime/Electricity)
const assets = [
  { code: 'BTC',  label: 'BTC',  balance: '0.00', logo: '/img/btc.png'  },
  { code: 'USDT', label: 'USDT', balance: '0.00', logo: '/img/usdt.png' },
  { code: 'NGN',  label: 'NGN',  balance: '0.00', logo: '/img/ngn.png'  },
]

// Mock API: fetch plans by provider
async function fetchInternetPlans(provider) {
  await new Promise(r => setTimeout(r, 400))
  switch (provider) {
    case 'spectranet':
      return [
        { id: 'sp_10',  name: '10GB – 30 Days',    price: 3500 },
        { id: 'sp_30',  name: '30GB – 30 Days',    price: 9000 },
        { id: 'sp_unl', name: 'Unlimited – 30D',   price: 19000 },
      ]
    case 'smile':
      return [
        { id: 'sm_3',   name: '3GB – 7 Days',      price: 1200 },
        { id: 'sm_15',  name: '15GB – 30 Days',    price: 5500 },
        { id: 'sm_50',  name: '50GB – 30 Days',    price: 15000 },
      ]
    case 'swift':
      return [
        { id: 'sw_5',   name: '5GB – 7 Days',      price: 1800 },
        { id: 'sw_25',  name: '25GB – 30 Days',    price: 7800 },
        { id: 'sw_unl', name: 'Unlimited – 30D',   price: 21000 },
      ]
    case 'ipnx':
      return [
        { id: 'ip_12',  name: '12GB – 30 Days',    price: 4200 },
        { id: 'ip_40',  name: '40GB – 30 Days',    price: 11500 },
        { id: 'ip_100', name: '100GB – 60 Days',   price: 26500 },
      ]
    default: // fiberone or others
      return [
        { id: 'fb_basic',  name: 'Basic – 15Mbps – 30D',  price: 12000 },
        { id: 'fb_plus',   name: 'Plus – 30Mbps – 30D',   price: 18000 },
        { id: 'fb_premium',name: 'Premium – 50Mbps – 30D',price: 26000 },
      ]
  }
}

// Form state
const provider = ref('')      // selected ISP
const account  = ref('')      // account/customer/router id (alphanumeric)
const asset    = ref('BTC')   // payment asset
const bundleId = ref('')      // selected plan

const plans = ref([])
const loadingPlans = ref(false)

watchEffect(async () => {
  if (!provider.value) { plans.value = []; bundleId.value = ''; return }
  loadingPlans.value = true
  plans.value = await fetchInternetPlans(provider.value)
  loadingPlans.value = false
  if (!plans.value.find(p => p.id === bundleId.value)) bundleId.value = ''
})

const selectedPlan = computed(() => plans.value.find(p => p.id === bundleId.value) || null)

const canSubmit = computed(() =>
  provider.value && account.value.length >= 4 && asset.value && bundleId.value
)

// Modal state
const modalOpen  = ref(false)
const modalPhase = ref('processing') // 'processing'|'success'|'error'
const modalTitle = ref('')
const modalMsg   = ref('')
const modalLines = ref([])

function randomMsg(ok) {
  const okMsgs  = ['Internet plan activated.', 'Subscription completed successfully.', 'Account credited with selected plan.']
  const errMsgs = ['Payment could not be completed.', 'Provider timeout — please retry.', 'Insufficient balance on selected asset.']
  return ok ? okMsgs[Math.floor(Math.random()*okMsgs.length)]
            : errMsgs[Math.floor(Math.random()*errMsgs.length)]
}

// Beneficiaries (save account/customer ID)
const saveBeneficiary = ref(true)
const { add } = useBeneficiaries()

const submit = async () => {
  if (!canSubmit.value) return
  const provLabel = providers.find(p => p.value === provider.value)?.label || provider.value

  modalOpen.value = true
  modalPhase.value = 'processing'
  modalTitle.value = ''
  modalMsg.value = ''
  modalLines.value = [
    { label: 'Provider',  value: provLabel },
    { label: 'Account ID',value: account.value },
    { label: 'Plan',      value: selectedPlan.value?.name || '' },
    { label: 'Amount',    value: '₦' + Number(selectedPlan.value?.price || 0).toLocaleString() },
    { label: 'Method',    value: asset.value },
  ]

  // mock API call
  await new Promise(r => setTimeout(r, 1200))
  const ok = Math.random() < 0.7
  modalPhase.value = ok ? 'success' : 'error'
  modalTitle.value  = ok ? 'Internet paid' : 'Payment failed'
  modalMsg.value    = randomMsg(ok)

  if (ok && saveBeneficiary.value) {
    const label = `${provLabel} • ${account.value.slice(0,6)}…`
    add({
      service: 'internet',
      kind: 'accountId',
      providerId: provider.value,
      label,
      value: account.value,
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
    modalTitle.value  = ok ? 'Internet paid' : 'Payment failed'
    modalMsg.value    = randomMsg(ok)
  }, 900)
}
</script>

<template>
  <Card class="p-5">
    <div class="text-sm font-medium text-gray-800 mb-3">Internet</div>

    <!-- Provider -->
    <BrandSelect v-model="provider" :options="providers" placeholder="Select Provider" />

    <!-- Account / Customer ID with beneficiaries (alphanumeric) -->
    <div class="mt-4">
      <AccountInputWithBeneficiaries
        v-model="account"
        service="internet"
        :providerId="provider"
        kind="accountId"
        placeholder="Account / Customer ID"
        :digitsOnly="false"
      />
      <label class="mt-2 inline-flex items-center gap-2 text-sm text-gray-600">
        <input type="checkbox" v-model="saveBeneficiary" class="rounded border-gray-300 text-primary focus:ring-primary" />
        Save account
      </label>
    </div>

    <!-- Asset -->
    <div class="mt-4">
      <AssetSelect v-model="asset" :options="assets" placeholder="BTC" />
    </div>

    <!-- Plan -->
    <div class="mt-4">
      <DataBundleSelect
        v-model="bundleId"
        :options="plans"
        :placeholder="loadingPlans ? 'Loading plans…' : 'Select Plan'"
      />
    </div>

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
      @submit="onSubmitPin"
      @primary="closeModal"
      @secondary="retry"
    />
  </Card>
</template>
