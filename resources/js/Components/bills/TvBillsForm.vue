<script setup>
import { ref, computed, watchEffect } from 'vue'
import Card from '@/Components/ui/Card.vue'
import Button from '@/Components/ui/Button.vue'

import BrandSelect from '@/Components/bills/BrandSelect.vue'
import DataBundleSelect from '@/Components/bills/DataBundleSelect.vue'
import AccountInputWithBeneficiaries from '@/Components/bills/shared/AccountInputWithBeneficiaries.vue'
import BillsPayModal from '@/Components/bills/shared/BillsPayModal.vue'
import { useBeneficiaries } from '@/composables/useBeneficiaries'

const props = defineProps({
  balance: { type: Number, default: 0 },
})

// Providers (ensure logos exist under /public/img)
const providers = [
  { value: 'dstv',      label: 'DStv',      logo: '/img/dstv.png' },
  { value: 'gotv',      label: 'GOtv',      logo: '/img/gotv.png' },
  { value: 'startimes', label: 'StarTimes', logo: '/img/startimes.png' },
]

// --- Mock bundle fetcher (swap with API later) ---
async function fetchTvBundles(provider) {
  await new Promise(r => setTimeout(r, 350))
  if (provider === 'dstv') {
    return [
      { id: 'dstv_padi',     name: 'Padi (Monthly)',     price: 2500 },
      { id: 'dstv_yanga',    name: 'Yanga (Monthly)',    price: 3500 },
      { id: 'dstv_confam',   name: 'Confam (Monthly)',   price: 6200 },
      { id: 'dstv_compact',  name: 'Compact (Monthly)',  price: 10500 },
      { id: 'dstv_cplus',    name: 'Compact Plus (Monthly)', price: 16600 },
      { id: 'dstv_premium',  name: 'Premium (Monthly)',  price: 24500 },
    ]
  }
  if (provider === 'gotv') {
    return [
      { id: 'gotv_smallie', name: 'Smallie (Monthly)', price: 1100 },
      { id: 'gotv_jinja',   name: 'Jinja (Monthly)',   price: 2250 },
      { id: 'gotv_jolli',   name: 'Jolli (Monthly)',   price: 3300 },
      { id: 'gotv_max',     name: 'Max (Monthly)',     price: 4850 },
      { id: 'gotv_supa',    name: 'Supa (Monthly)',    price: 6500 },
    ]
  }
  // StarTimes
  return [
    { id: 'st_nova',    name: 'Nova (Monthly)',    price: 1200 },
    { id: 'st_basic',   name: 'Basic (Monthly)',   price: 1500 },
    { id: 'st_smart',   name: 'Smart (Monthly)',   price: 2500 },
    { id: 'st_classic', name: 'Classic (Monthly)', price: 3000 },
    { id: 'st_super',   name: 'Super (Monthly)',   price: 4500 },
  ]
}

// --- Form state ---
const provider = ref('')      // dstv/gotv/startimes
const smartcard = ref('')     // card/decoder number
const bundleId = ref('')      // selected bundle

const bundles = ref([])
const loadingBundles = ref(false)

watchEffect(async () => {
  if (!provider.value) { bundles.value = []; bundleId.value=''; return }
  loadingBundles.value = true
  bundles.value = await fetchTvBundles(provider.value)
  loadingBundles.value = false
  // keep prior selection if still exists
  if (!bundles.value.find(b => b.id === bundleId.value)) bundleId.value = ''
})

const selectedBundle = computed(() => bundles.value.find(b => b.id === bundleId.value) || null)
const fiatBalance = computed(() => Number.isFinite(props.balance) ? Number(props.balance) : 0)
const bundlePrice = computed(() => Number(selectedBundle.value?.price || 0))
const insufficient = computed(() => bundlePrice.value > fiatBalance.value)

const canSubmit = computed(() =>
  provider.value && smartcard.value.length >= 6 && bundleId.value && !insufficient.value
)

const balanceHint = computed(() => {
  if (!bundleId.value) {
    return `Available balance: ₦${fiatBalance.value.toLocaleString()}`
  }
  if (insufficient.value) {
    return `Insufficient funds. Available balance: ₦${fiatBalance.value.toLocaleString()}`
  }
  return 'Payment will be deducted from your fiat wallet.'
})

// --- Modal state ---
const modalOpen  = ref(false)
const modalPhase = ref('processing') // 'processing'|'success'|'error'
const modalTitle = ref('')
const modalMsg   = ref('')
const modalLines = ref([])

function randomMsg(ok) {
  const okMsgs  = ['Subscription completed.', 'TV bouquet activated.', 'Decoder credited successfully.']
  const errMsgs = ['Payment could not be completed.', 'Provider timeout — please retry.', 'Insufficient fiat balance.']
  return ok ? okMsgs[Math.floor(Math.random()*okMsgs.length)]
            : errMsgs[Math.floor(Math.random()*errMsgs.length)]
}

// Beneficiaries
const saveBeneficiary = ref(true)
const { add } = useBeneficiaries()

const submit = async () => {
  if (!canSubmit.value) return
  if (insufficient.value) return
  const provLabel = providers.find(p => p.value === provider.value)?.label || provider.value
  modalOpen.value = true
  modalPhase.value = 'processing'
  modalTitle.value = ''
  modalMsg.value = ''
  modalLines.value = [
    { label: 'Provider',  value: provLabel },
    { label: 'Smartcard', value: smartcard.value },
    { label: 'Bundle',    value: selectedBundle.value?.name || '' },
    { label: 'Amount',    value: '₦' + Number(selectedBundle.value?.price || 0).toLocaleString() },
    { label: 'Payment Source',    value: 'Fiat Balance (NGN)' },
  ]

  // mock API
  await new Promise(r => setTimeout(r, 1200))
  const ok = Math.random() < 0.7
  modalPhase.value = ok ? 'success' : 'error'
  modalTitle.value  = ok ? 'TV subscription paid' : 'Payment failed'
  modalMsg.value    = randomMsg(ok)

  if (ok && saveBeneficiary.value) {
    const label = `${provLabel} • ${smartcard.value.slice(0,6)}…`
    add({
      service: 'tv',
      kind: 'smartcard',
      providerId: provider.value,
      label,
      value: smartcard.value,
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
    modalTitle.value  = ok ? 'TV subscription paid' : 'Payment failed'
    modalMsg.value    = randomMsg(ok)
  }, 900)
}
</script>

<template>
  <Card class="p-5">
    <div class="text-sm font-medium text-gray-800 mb-3">TV Bills</div>

    <!-- Provider -->
    <BrandSelect v-model="provider" :options="providers" placeholder="Select Provider" />

    <!-- Smartcard with beneficiaries -->
    <div class="mt-4">
      <AccountInputWithBeneficiaries
        v-model="smartcard"
        service="tv"
        :providerId="provider"
        kind="smartcard"
        placeholder="Smartcard / IUC Number"
        :digitsOnly="true"
      />
      <label class="mt-2 inline-flex items-center gap-2 text-sm text-gray-600">
        <input type="checkbox" v-model="saveBeneficiary" class="rounded border-gray-300 text-primary focus:ring-primary" />
        Save smartcard
      </label>
    </div>

    <!-- Fiat balance -->
    <div class="mt-4 rounded-2xl border border-primary/20 bg-primary/5 px-4 py-4">
      <div class="text-xs uppercase tracking-wide text-primary/80">Available Balance</div>
      <div class="mt-1 text-lg font-semibold text-primary">₦{{ fiatBalance.toLocaleString() }}</div>
      <p class="mt-1 text-xs text-primary/70">
        TV subscriptions draw from your fiat wallet.
      </p>
    </div>

    <!-- Bundle -->
    <div class="mt-4">
      <DataBundleSelect
        v-model="bundleId"
        :options="bundles"
        :placeholder="loadingBundles ? 'Loading bundles…' : 'Select Bundle'"
      />
      <p class="mt-2 text-xs" :class="insufficient ? 'text-red-600' : 'text-gray-500'">
        {{ balanceHint }}
      </p>
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
      @primary="closeModal"
      @secondary="retry"
    />
  </Card>
</template>
