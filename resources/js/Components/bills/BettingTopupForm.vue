<script setup>
import { ref, computed } from 'vue'
import Card from '@/Components/ui/Card.vue'
import Button from '@/Components/ui/Button.vue'

import BrandSelect from '@/Components/bills/BrandSelect.vue'
import AssetSelect from '@/Components/bills/AssetSelect.vue'
import AccountInputWithBeneficiaries from '@/Components/bills/shared/AccountInputWithBeneficiaries.vue'
import BillsPayModal from '@/Components/bills/shared/BillsPayModal.vue'
import { useBeneficiaries } from '@/composables/useBeneficiaries'

// Betting providers (add logos in /public/img)
const providers = [
  { value: 'bet9ja',   label: 'Bet9ja',   logo: '/img/bet9ja.png' },
  { value: 'sporty',   label: 'SportyBet',logo: '/img/sportybet.png' },
  { value: '1xbet',    label: '1xBet',    logo: '/img/1xbet.png' },
  { value: 'betking',  label: 'BetKing',  logo: '/img/betking.png' },
  { value: 'nairabet', label: 'NairaBet', logo: '/img/nairabet.png' },
  { value: 'msport',   label: 'MSport',   logo: '/img/msport.png' },
]

// Assets (same as other pages)
const assets = [
  { code: 'BTC',  label: 'BTC',  balance: '0.00', logo: '/img/btc.png'  },
  { code: 'USDT', label: 'USDT', balance: '0.00', logo: '/img/usdt.png' },
  { code: 'NGN',  label: 'NGN',  balance: '0.00', logo: '/img/ngn.png'  },
]

// Form state
const provider = ref('')
const account  = ref('')     // account ID / username / phone (can be alphanumeric)
const asset    = ref('BTC')
const amount   = ref('')

// Quick-select chips
const chips = [500, 1000, 2000, 5000, 10000]
const onlyDigits = (v) => (v || '').replace(/[^\d]/g, '')
const setChip = (n) => (amount.value = String(n))

const canSubmit = computed(() =>
  provider.value && account.value.length >= 3 && asset.value && Number(amount.value) > 0
)

// Modal state
const modalOpen  = ref(false)
const modalPhase = ref('processing') // 'processing'|'success'|'error'
const modalTitle = ref('')
const modalMsg   = ref('')
const modalLines = ref([])

function randomMsg(ok) {
  const okMsgs  = ['Top-up successful.', 'Betting wallet funded.', 'Account credited instantly.']
  const errMsgs = ['Payment could not be completed.', 'Provider timeout — please retry.', 'Insufficient balance on selected asset.']
  return ok ? okMsgs[Math.floor(Math.random()*okMsgs.length)]
            : errMsgs[Math.floor(Math.random()*errMsgs.length)]
}

// Beneficiaries
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
    { label: 'Provider', value: provLabel },
    { label: 'Account',  value: account.value },
    { label: 'Amount',   value: '₦' + Number(amount.value).toLocaleString() },
    { label: 'Method',   value: asset.value },
  ]

  // mock API
  await new Promise(r => setTimeout(r, 1200))
  const ok = Math.random() < 0.7
  modalPhase.value = ok ? 'success' : 'error'
  modalTitle.value  = ok ? 'Betting top-up paid' : 'Payment failed'
  modalMsg.value    = randomMsg(ok)

  if (ok && saveBeneficiary.value) {
    const label = `${provLabel} • ${account.value.slice(0,6)}…`
    add({
      service: 'betting',
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
    modalTitle.value  = ok ? 'Betting top-up paid' : 'Payment failed'
    modalMsg.value    = randomMsg(ok)
  }, 900)
}
</script>

<template>
  <Card class="p-5">
    <div class="text-sm font-medium text-gray-800 mb-3">Betting Top-up</div>

    <!-- Provider -->
    <BrandSelect v-model="provider" :options="providers" placeholder="Select Provider" />

    <!-- Account / Username with beneficiaries (alphanumeric) -->
    <div class="mt-4">
      <AccountInputWithBeneficiaries
        v-model="account"
        service="betting"
        :providerId="provider"
        kind="accountId"
        placeholder="Account / Username / Phone"
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

    <!-- Amount -->
    <div class="mt-4 rounded-2xl bg-gray-50 border border-gray-200 px-4 py-4">
      <input
        :value="amount"
        @input="e => amount = onlyDigits(e.target.value)"
        type="text"
        inputmode="numeric"
        placeholder="Enter Amount"
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
