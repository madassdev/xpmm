<script setup>
import Card from '@/Components/ui/Card.vue'
import Button from '@/Components/ui/Button.vue'
import BrandSelect from '@/Components/bills/BrandSelect.vue'
import AssetSelect from '@/Components/bills/AssetSelect.vue'
import BillsPayModal from '@/Components/bills/shared/BillsPayModal.vue'
import { ChevronDown } from 'lucide-vue-next'
import { ref, computed } from 'vue'

// Select data (adjust logos to your /public/img paths)
const networks = [
  { value: 'mtn',     label: 'MTN',     logo: '/img/mtn.png' },
  { value: 'airtel',  label: 'Airtel',  logo: '/img/airtel.png' },
  { value: 'glo',     label: 'Glo',     logo: '/img/glo.png' },
  { value: '9mobile', label: '9mobile', logo: '/img/9mobile.png' },
]
const assets = [
  { code: 'BTC',  label: 'BTC',  balance: '0.00', logo: '/img/btc.png'  },
  { code: 'USDT', label: 'USDT', balance: '0.00', logo: '/img/usdt.png' },
  { code: 'NGN',  label: 'NGN',  balance: '0.00', logo: '/img/ngn.png'  },
]

// State
const network = ref('mtn')   // screenshot shows MTN selected
const phone   = ref('+2347082137552') // shows +234 prefix and digits
const asset   = ref('BTC')
const amount  = ref('2000')

const chips = [200, 500, 1000, 2000, 5000]
const digits = (v) => (v || '').replace(/[^\d]/g, '')
const setChip = (n) => (amount.value = String(n))

const valid = computed(() =>
  network.value && asset.value && (phone.value?.length >= 7) && Number(amount.value) > 0
)

// Modal state
const modalOpen = ref(false)
const modalPhase = ref('processing') // 'processing'|'success'|'error'
const modalTitle = ref('')
const modalMsg = ref('')
const modalLines = ref([])

function randomMsg(ok) {
  const okMsgs  = ['Your airtime has been delivered.', 'Purchase completed successfully.', 'We’ve credited the recipient’s number.']
  const errMsgs = ['We could not complete this payment right now.', 'Network timeout. Please try again.', 'Insufficient balance on selected asset.']
  return ok ? okMsgs[Math.floor(Math.random()*okMsgs.length)]
            : errMsgs[Math.floor(Math.random()*errMsgs.length)]
}

const submit = async () => {
  if (!valid.value) return
  modalOpen.value = true
  modalPhase.value = 'processing'
  modalTitle.value = ''
  modalMsg.value = ''
  modalLines.value = [
    { label: 'Network', value: networks.find(n => n.value===network.value)?.label || network.value },
    { label: 'Phone',   value: phone.value },
    { label: 'Amount',  value: `₦${Number(amount.value).toLocaleString()}` },
    { label: 'Method',  value: asset.value },
  ]
  // mock fetch
  await new Promise(r => setTimeout(r, 1200))
  const ok = Math.random() < 0.7
  modalPhase.value = ok ? 'success' : 'error'
  modalTitle.value = ok ? 'Airtime purchased' : 'Payment failed'
  modalMsg.value = randomMsg(ok)
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
  }, 1000)
}
</script>

<template>
  <Card class="p-5">
    <div class="text-sm font-medium text-gray-800 mb-3">Airtime</div>

    <!-- 1) Network -->
    <BrandSelect v-model="network" :options="networks" placeholder="Select Network" />

    <!-- 2) Phone (flag + prefix + number) -->
    <div class="mt-4 rounded-2xl bg-gray-50 border border-gray-200 px-3 py-2 flex items-center gap-3">
      <!-- Flag pill -->
      <button type="button" class="flex items-center gap-2 px-3 py-2 rounded-xl bg-white/60 border border-gray-200">
        <span class="text-lg">🇳🇬</span>
        <ChevronDown class="w-4 h-4 text-gray-500" />
      </button>
      <!-- Number with visible +234 prefix (kept inside the value per screenshot) -->
      <input
        :value="phone"
        @input="e => phone = e.target.value.replace(/[^\\d+]/g,'')"
        type="tel"
        inputmode="numeric"
        placeholder="+234XXXXXXXXXX"
        class="flex-1 bg-transparent px-2 py-3 placeholder-gray-400 text-gray-900 border-none ring-0 focus:ring-0 focus:border-0 focus:outline-none outline-none"
      />
    </div>

    <!-- 3) Asset / Wallet -->
    <div class="mt-4">
      <AssetSelect v-model="asset" :options="assets" placeholder="BTC" />
    </div>

    <!-- 4) Amount -->
    <div class="mt-4 rounded-2xl bg-gray-50 border border-gray-200 px-4 py-4">
      <input
        :value="amount"
        @input="e => amount = digits(e.target.value)"
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
    <Button class="w-full mt-6" @click="submit" :disabled="!valid">
      Purchase Airtime
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
