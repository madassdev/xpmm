<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { get, post } from '@/lib/api'
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

// ISPs (add logos in /public/img; placeholders are fine)
const providers = [
  { value: 'spectranet', label: 'Spectranet', logo: '/img/spectranet.png' },
  { value: 'smile',      label: 'Smile',      logo: '/img/smile.png' },
  { value: 'swift',      label: 'Swift',      logo: '/img/swift.png' },
  { value: 'ipnx',       label: 'ipNX',       logo: '/img/ipnx.png' },
  { value: 'fiberone',   label: 'FiberOne',   logo: '/img/fiberone.png' },
]

// Form state
const provider = ref('')      // selected ISP
const account  = ref('')      // account/customer/router id (alphanumeric)
const bundleId = ref('')      // selected plan

const plans = ref([])
const loadingPlans = ref(false)
const plansError = ref('')

async function loadPlans(providerId) {
  plansError.value = ''
  if (!providerId) {
    plans.value = []
    bundleId.value = ''
    return
  }
  loadingPlans.value = true
  try {
    const { data } = await get('/bills/internet/plans', { params: { provider: providerId } })
    const list = Array.isArray(data?.plans) ? data.plans : []
    plans.value = list.map(p => ({
      id: p.id ?? p.code ?? p.planId ?? Math.random().toString(36).slice(2),
      name: p.name ?? p.plan ?? 'Plan',
      price: Number(p.price ?? p.amount ?? 0),
    }))
  } catch (error) {
    plansError.value = error?.response?.data?.message || 'Failed to load plans. Please try again.'
    plans.value = []
  } finally {
    loadingPlans.value = false
    if (!plans.value.find(p => p.id === bundleId.value)) bundleId.value = ''
  }
}

onMounted(async () => {
  if (!provider.value && providers.length) {
    provider.value = providers[0].value
  }
  await loadPlans(provider.value)
})

watch(provider, async (nv, ov) => {
  if (nv === ov) return
  bundleId.value = ''
  await loadPlans(nv)
})

const selectedPlan = computed(() => plans.value.find(p => p.id === bundleId.value) || null)
const fiatBalance = computed(() => Number.isFinite(props.balance) ? Number(props.balance) : 0)
const planPrice = computed(() => Number(selectedPlan.value?.price || 0))
const insufficient = computed(() => planPrice.value > fiatBalance.value)

const canSubmit = computed(() =>
  provider.value && account.value.length >= 4 && bundleId.value && !insufficient.value
)

const balanceHint = computed(() => {
  if (!bundleId.value) {
    return `Available balance: ₦${fiatBalance.value.toLocaleString()}`
  }
  if (insufficient.value) {
    return `Insufficient funds. Available balance: ₦${fiatBalance.value.toLocaleString()}`
  }
  return 'Fiat balance will be used for this payment.'
})

// Modal state
const modalOpen  = ref(false)
const modalPhase = ref('confirm') // 'confirm'|'processing'|'success'|'error'
const modalTitle = ref('')
const modalMsg   = ref('')
const modalLines = ref([])
const modalErrorCode = ref('')

// Beneficiaries (save account/customer ID)
const saveBeneficiary = ref(true)
const { add } = useBeneficiaries()

const submit = async () => {
  if (!canSubmit.value || insufficient.value) return
  const provLabel = providers.find(p => p.value === provider.value)?.label || provider.value

  modalOpen.value = true
  modalPhase.value = 'confirm'
  modalTitle.value = ''
  modalMsg.value = ''
  modalErrorCode.value = ''
  modalLines.value = [
    { label: 'Provider',  value: provLabel },
    { label: 'Account ID',value: account.value },
    { label: 'Plan',      value: selectedPlan.value?.name || '' },
    { label: 'Amount',    value: '₦' + Number(selectedPlan.value?.price || 0).toLocaleString() },
    { label: 'Payment Source',    value: 'Fiat Balance (NGN)' },
  ]
}

const onSubmitPin = async (pin) => {
  modalErrorCode.value = ''
  modalTitle.value = ''
  modalMsg.value = ''
  try {
    modalPhase.value = 'processing'
    const { data } = await post('/bills/internet', {
      provider: provider.value,
      account: account.value,
      planId: bundleId.value,
      pin,
    })

    modalPhase.value = 'success'
    modalTitle.value = 'Internet plan purchased'
    modalMsg.value = data?.message || 'Your internet subscription has been activated.'

    if (saveBeneficiary.value) {
      const provLabel = providers.find(p => p.value === provider.value)?.label || provider.value
      const label = `${provLabel} • ${account.value.slice(0, 6)}…`
      add({
        service: 'internet',
        kind: 'accountId',
        providerId: provider.value,
        label,
        value: account.value,
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

    <!-- Fiat balance -->
    <div class="mt-4 rounded-2xl border border-primary/20 bg-primary/5 px-4 py-4">
      <div class="text-xs uppercase tracking-wide text-primary/80">Available Balance</div>
      <div class="mt-1 text-lg font-semibold text-primary">₦{{ fiatBalance.toLocaleString() }}</div>
      <p class="mt-1 text-xs text-primary/70">
        Internet subscriptions draw from your fiat wallet.
      </p>
    </div>

    <!-- Plan -->
    <div class="mt-4">
      <DataBundleSelect
        v-model="bundleId"
        :options="plans"
        :placeholder="loadingPlans ? 'Loading plans…' : (plansError ? 'Failed to load' : 'Select Plan')"
        :disabled="!!plansError"
      />
      <p v-if="plansError" class="mt-2 text-xs text-red-600">{{ plansError }}</p>
      <p v-else class="mt-2 text-xs" :class="insufficient ? 'text-red-600' : 'text-gray-500'">
        {{ balanceHint }}
      </p>
    </div>

    <!-- CTA -->
    <Button class="w-full mt-6" :disabled="!canSubmit || loadingPlans" @click="submit">
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
