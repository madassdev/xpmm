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

// Providers (ensure logos exist under /public/img)
const providers = [
  { value: 'dstv',      label: 'DStv',      logo: '/img/dstv.png' },
  { value: 'gotv',      label: 'GOtv',      logo: '/img/gotv.png' },
  { value: 'startimes', label: 'StarTimes', logo: '/img/startimes.png' },
]

const provider = ref('')
const smartcard = ref('')
const bundleId = ref('')

const bundles = ref([])
const loadingBundles = ref(false)
const bundlesError = ref('')

const validating = ref(false)
const validationError = ref('')
const customer = ref(null)

async function loadBundles(providerId) {
  bundlesError.value = ''
  if (!providerId) {
    bundles.value = []
    bundleId.value = ''
    return
  }
  loadingBundles.value = true
  try {
    const { data } = await get('/bills/tv/plans', { params: { provider: providerId } })
    const list = Array.isArray(data?.plans) ? data.plans : []
    bundles.value = list.map(p => ({
      id: p.id ?? p.code ?? Math.random().toString(36).slice(2),
      name: p.name ?? p.plan ?? 'Bundle',
      price: Number(p.price ?? p.amount ?? 0),
    }))
  } catch (error) {
    bundlesError.value = error?.response?.data?.message || 'Failed to load bundles. Please try again.'
    bundles.value = []
  } finally {
    loadingBundles.value = false
    if (!bundles.value.find(b => b.id === bundleId.value)) bundleId.value = ''
  }
}

onMounted(async () => {
  if (!provider.value && providers.length) provider.value = providers[0].value
  await loadBundles(provider.value)
})

watch(provider, async (nv, ov) => {
  if (nv === ov) return
  bundleId.value = ''
  customer.value = null
  validationError.value = ''
  await loadBundles(nv)
})

watch(smartcard, () => {
  customer.value = null
  validationError.value = ''
})

const selectedBundle = computed(() => bundles.value.find(b => b.id === bundleId.value) || null)
const fiatBalance = computed(() => Number.isFinite(props.balance) ? Number(props.balance) : 0)
const bundlePrice = computed(() => Number(selectedBundle.value?.price || 0))
const insufficient = computed(() => bundlePrice.value > fiatBalance.value)

const canSubmit = computed(() =>
  provider.value && smartcard.value.length >= 6 && bundleId.value && !insufficient.value && customer.value
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

const modalOpen  = ref(false)
const modalPhase = ref('confirm')
const modalTitle = ref('')
const modalMsg   = ref('')
const modalLines = ref([])
const modalErrorCode = ref('')

const saveBeneficiary = ref(true)
const { add } = useBeneficiaries()

const validateSmartcard = async () => {
  validationError.value = ''
  customer.value = null
  if (!provider.value || smartcard.value.length < 6) {
    validationError.value = 'Select provider and enter a valid smartcard/IUC number.'
    return
  }
  validating.value = true
  try {
    const { data } = await post('/bills/tv/validate', {
      provider: provider.value,
      smartcard: smartcard.value,
    })
    customer.value = data?.customer || null
  } catch (error) {
    validationError.value = error?.response?.data?.message || 'Unable to validate smartcard.'
    customer.value = null
  } finally {
    validating.value = false
  }
}

const submit = async () => {
  if (!canSubmit.value) return
  if (insufficient.value) return
  const provLabel = providers.find(p => p.value === provider.value)?.label || provider.value

  modalOpen.value = true
  modalPhase.value = 'confirm'
  modalTitle.value = ''
  modalMsg.value = ''
  modalErrorCode.value = ''
  modalLines.value = [
    { label: 'Provider',  value: provLabel },
    { label: 'Smartcard', value: smartcard.value },
    customer.value?.name ? { label: 'Customer', value: customer.value.name } : null,
    { label: 'Bundle',    value: selectedBundle.value?.name || '' },
    { label: 'Amount',    value: '₦' + Number(selectedBundle.value?.price || 0).toLocaleString() },
    { label: 'Payment Source',    value: 'Fiat Balance (NGN)' },
  ].filter(Boolean)
}

const onSubmitPin = async (pin) => {
  modalErrorCode.value = ''
  modalTitle.value = ''
  modalMsg.value = ''
  try {
    modalPhase.value = 'processing'
    const { data } = await post('/bills/tv', {
      provider: provider.value,
      smartcard: smartcard.value,
      planId: bundleId.value,
      pin,
    })

    modalPhase.value = 'success'
    modalTitle.value  = 'TV subscription created'
    modalMsg.value    = data?.message || 'Subscription request sent successfully.'

    if (saveBeneficiary.value) {
      const provLabel = providers.find(p => p.value === provider.value)?.label || provider.value
      const label = `${provLabel} • ${smartcard.value.slice(0,6)}…`
      add({
        service: 'tv',
        kind: 'smartcard',
        providerId: provider.value,
        label,
        value: smartcard.value,
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
    <div class="text-sm font-medium text-gray-800 mb-3">TV Bills</div>

    <!-- Provider -->
    <BrandSelect v-model="provider" :options="providers" placeholder="Select Provider" />

    <!-- Smartcard with beneficiaries -->
    <div class="mt-4 space-y-3">
      <AccountInputWithBeneficiaries
        v-model="smartcard"
        service="tv"
        :providerId="provider"
        kind="smartcard"
        placeholder="Smartcard / IUC Number"
        :digitsOnly="true"
      />
      <div class="flex items-start gap-3">
        <Button size="sm" variant="outline" class="shrink-0" :disabled="validating" @click="validateSmartcard">
          <span v-if="validating" class="inline-flex items-center gap-2">
            <span class="w-3 h-3 rounded-full border-2 border-primary border-t-transparent animate-spin" />
            Validating…
          </span>
          <span v-else>Validate</span>
        </Button>
        <div class="text-xs text-gray-600 flex-1">
          <template v-if="customer">
            <div class="font-medium text-gray-800">{{ customer.name }}</div>
            <div v-if="customer.address" class="text-gray-500">{{ customer.address }}</div>
          </template>
          <p v-else-if="validationError" class="text-red-600">{{ validationError }}</p>
          <p v-else class="text-gray-500">Validate beneficiary before paying.</p>
        </div>
      </div>
      <label class="inline-flex items-center gap-2 text-sm text-gray-600">
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
        :placeholder="loadingBundles ? 'Loading bundles…' : (bundlesError ? 'Failed to load' : 'Select Bundle')"
        :disabled="!!bundlesError"
      />
      <p v-if="bundlesError" class="mt-2 text-xs text-red-600">{{ bundlesError }}</p>
      <p v-else class="mt-2 text-xs" :class="insufficient ? 'text-red-600' : 'text-gray-500'">
        {{ balanceHint }}
      </p>
    </div>

    <!-- CTA -->
    <Button class="w-full mt-6" :disabled="!canSubmit || loadingBundles" @click="submit">
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
