<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { get, post } from '@/lib/api' // get is used here; keep post if you'll call backend to pay
import Card from '@/Components/ui/Card.vue'
import Button from '@/Components/ui/Button.vue'

import BrandSelect from '@/Components/bills/BrandSelect.vue'
import DataBundleSelect from '@/Components/bills/DataBundleSelect.vue'
import BillsPayModal from '@/Components/bills/shared/BillsPayModal.vue'
import PhoneInputWithBeneficiaries from '@/Components/bills/shared/PhoneInputWithBeneficiaries.vue'
import { useBeneficiaries } from '@/composables/useBeneficiaries'

const props = defineProps({
  balance: { type: Number, default: 0 },
})

// Providers (ensure logos exist)
const networks = [
  { value: 'mtn',     label: 'MTN',     logo: '/img/mtn.png' },
  { value: 'airtel',  label: 'Airtel',  logo: '/img/airtel.png' },
  { value: 'glo',     label: 'Glo',     logo: '/img/glo.png' },
  { value: '9mobile', label: '9mobile', logo: '/img/9mobile.png' },
]

// --- API fetch for plans ---
const plans = ref([])
const loadingPlans = ref(false)
const plansError = ref('')
const plansCache = ref({}) // { [provider]: Plan[] }

async function fetchPlansFromApi(provider) {
  const { data } = await get('/bills/data/plans', { params: { network:provider } })
  // Normalize shape: accept {data:[...]} or [...]
  const raw = Array.isArray(data?.plans) ? data.plans : Array.isArray(data) ? data : []
  return raw.map(p => ({
    id:     p.id ?? p.code ?? String(p.plan_id ?? p.slug ?? Math.random()),
    name:   p.name ?? p.plan_name ?? p.title ?? 'Plan',
    price:  Number(p.price ?? p.amount ?? 0),
  }))
}

async function loadPlans(provider) {
  plansError.value = ''
  if (!provider) { plans.value = []; return }
  // cache
  if (plansCache.value[provider]) {
    plans.value = plansCache.value[provider]
    return
  }
  loadingPlans.value = true
  try {
    const list = await fetchPlansFromApi(provider)
    plansCache.value[provider] = list
    plans.value = list
  } catch (e) {
    plansError.value = 'Failed to load plans. Please try another network or retry.'
    plans.value = []
  } finally {
    loadingPlans.value = false
  }
}

// --- Form state ---
const provider = ref('') // default empty; we’ll set on mount
const phone = ref('')
const bundleId = ref('')
const selectedPlan = computed(() => plans.value.find(p => p.id === bundleId.value) || null)
const planPrice = computed(() => Number(selectedPlan.value?.price || 0))
const fiatBalance = computed(() => Number.isFinite(props.balance) ? Number(props.balance) : 0)
const insufficient = computed(() => planPrice.value > fiatBalance.value)

const valid = computed(() =>
  provider.value && phone.value?.length >= 7 && bundleId.value && !insufficient.value
)
// Load on mount + on provider change
onMounted(async () => {
  if (!provider.value && networks.length) provider.value = networks[0].value
  await loadPlans(provider.value)
})
watch(provider, async (nv, ov) => {
  bundleId.value = ''
  await loadPlans(nv)
})

const canSubmit = computed(() =>
  provider.value && phone.value.length >= 7 && bundleId.value && !insufficient.value
)

const balanceHint = computed(() => {
  if (!bundleId.value) {
    return `Available balance: ₦${fiatBalance.value.toLocaleString()}`
  }
  if (insufficient.value) {
    return `Insufficient funds. Available balance: ₦${fiatBalance.value.toLocaleString()}`
  }
  return 'Fiat balance will be debited for this purchase.'
})

const modalOpen = ref(false)
const modalPhase = ref('processing') // 'processing'|'success'|'error'
const modalTitle = ref('')
const modalMsg = ref('')
const modalLines = ref([])
const modalErrorCode = ref('')

function randomMsg(ok) {
  const okMsgs = ['Data purchase completed.', 'Plan delivered successfully.', 'Beneficiary has been credited.']
  const errMsgs = ['We could not complete this payment.', 'Provider timeout — please retry.', 'Insufficient fiat balance.']
  return ok ? okMsgs[Math.floor(Math.random() * okMsgs.length)]
            : errMsgs[Math.floor(Math.random() * errMsgs.length)]
}

// Save beneficiary
const saveRecipient = ref(true)
const { add } = useBeneficiaries()

const submit = async () => {
  if (!valid.value) return
  const plan = selectedPlan.value

  modalOpen.value = true
  modalPhase.value = 'confirm'
  modalTitle.value = ''
  modalMsg.value = ''
  modalLines.value = [
    { label: 'Network', value: networks.find(n => n.value === provider.value)?.label || provider.value },
    { label: 'Phone',   value: phone.value },
    { label: 'Bundle',  value: plan?.name || '' },
    { label: 'Amount',  value: '₦' + (plan?.price ?? 0).toLocaleString() },
    { label: 'Payment Source',  value: 'Fiat Balance (NGN)' },
  ]
}

const onSubmitPin = async (pin) => {
  modalErrorCode.value = ''
  modalMsg.value = ''
  modalTitle.value = ''
  try {
    const payload = {
      provider: provider.value,
      phone: phone.value,
      planId: bundleId.value,
      pin,
    }

    const { data } = await post('/bills/data', payload)

    modalPhase.value = 'success'
    modalTitle.value = 'Data purchased'
    modalMsg.value = data?.message || 'Your data has been delivered.'

    if (saveRecipient.value) {
      const net = networks.find(n => n.value === provider.value)
      const label = `${net ? net.label : provider.value} • ${phone.value.slice(0, 7)}…`
      add({ service: 'data', kind: 'phone', providerId: provider.value, label, value: phone.value })
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
}

const closeModal = () => (modalOpen.value = false)
const retry = () => {
  modalOpen.value = true
  modalPhase.value = 'confirm'
  modalTitle.value = ''
  modalMsg.value = ''
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

    <!-- 3) Fiat balance -->
    <div class="mt-4 rounded-2xl border border-primary/20 bg-primary/5 px-4 py-4">
      <div class="text-xs uppercase tracking-wide text-primary/80">Available Balance</div>
      <div class="mt-1 text-lg font-semibold text-primary">₦{{ fiatBalance.toLocaleString() }}</div>
      <p class="mt-1 text-xs text-primary/70">
        All data purchases are charged from your fiat wallet.
      </p>
    </div>

    <!-- 4) Bundle -->
    <div class="mt-4">
      <DataBundleSelect
        v-model="bundleId"
        :options="plans"
        :placeholder="loadingPlans ? 'Loading plans…' : (plansError ? 'Failed to load' : 'Select Bundle')"
        :disabled="loadingPlans || !!plansError"
      />
      <p v-if="plansError" class="mt-2 text-xs text-red-600">{{ plansError }}</p>
      <p v-else class="mt-2 text-xs" :class="insufficient ? 'text-red-600' : 'text-gray-500'">
        {{ balanceHint }}
      </p>
    </div>

    <!-- CTA -->
    <Button class="w-full mt-6" :disabled="!canSubmit || loadingPlans" @click="submit">
      Purchase Data
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
