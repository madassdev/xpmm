<script setup>
import { ref, computed, watch, onUnmounted } from 'vue'
import Card from '@/Components/ui/Card.vue'
import Button from '@/Components/ui/Button.vue'
import PinModal from './PinModal.vue'
import { post } from '@/lib/api'
import { Plus, Minus, Upload, X } from 'lucide-vue-next'

const props = defineProps({
  card: { type: Object, default: null },
  mode: { type: String, required: true }, // 'buy' | 'sell'
})

const emit = defineEmits(['success', 'cancel'])

const currency = ref('NGN')
const type = ref('e-card')
const amount = ref('')
const qty = ref(1)
const files = ref([])
const fileUrls = ref([])

const pinModalOpen = ref(false)
const processing = ref(false)
const error = ref('')
const pinError = ref('')

const currencyOptions = [
  { value: 'NGN', label: 'Naira (NGN)' },
  { value: 'USD', label: 'Dollar (USD)' },
  { value: 'EUR', label: 'Euro (EUR)' },
]

onUnmounted(() => {
  fileUrls.value.forEach((url) => url && URL.revokeObjectURL(url))
})

watch(
  () => props.card?.id,
  () => {
    amount.value = ''
    qty.value = 1
    files.value = []
    fileUrls.value.forEach((url) => url && URL.revokeObjectURL(url))
    fileUrls.value = []
  }
)

const hasCard = computed(() => Boolean(props.card))
const quickValues = computed(() => props.card?.available_values?.slice(0, 5) ?? [25, 50, 100, 200, 500])
const rateDisplay = computed(() => (props.card?.rate ? `${props.card.rate}%` : '—'))

const valid = computed(() => {
  if (!hasCard.value) return false
  if (props.mode === 'buy') {
    return currency.value && Number(amount.value) > 0 && qty.value > 0
  }
  return (
    currency.value &&
    type.value &&
    Number(amount.value) > 0 &&
    qty.value > 0 &&
    (!files.value.length || files.value.length <= 6)
  )
})

const totalFaceValue = computed(() => Number(amount.value || 0) * qty.value)
const estimatedValue = computed(() => {
  if (!props.card?.rate || !totalFaceValue.value) return 0
  const rate = props.card.rate / 100
  return totalFaceValue.value * rate
})

const totalLabel = computed(() => (props.mode === 'buy' ? 'Estimated cost' : 'Expected payout'))
const formattedEstimate = computed(() =>
  new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN' }).format(estimatedValue.value)
)

function setChip(value) {
  amount.value = value.toString()
}

function adjustQty(delta) {
  const next = qty.value + delta
  if (next < 1 || next > 10) return
  qty.value = next
}

function onPick(e) {
  const list = Array.from(e.target.files || [])
  const newFiles = [...files.value, ...list].slice(0, 6)
  files.value = newFiles
  fileUrls.value.forEach((url) => url && URL.revokeObjectURL(url))
  fileUrls.value = newFiles.map((file) => (file instanceof File ? URL.createObjectURL(file) : null))
}

function removeFile(index) {
  if (fileUrls.value[index]) URL.revokeObjectURL(fileUrls.value[index])
  files.value.splice(index, 1)
  fileUrls.value.splice(index, 1)
}

function handleSubmit() {
  if (!valid.value) return
  error.value = ''
  pinError.value = ''
  pinModalOpen.value = true
}

async function handlePinSubmit(pin) {
  if (processing.value) return

  if (!props.card) return
  processing.value = true
  pinError.value = ''

  try {
    if (props.mode === 'buy') {
      const { data } = await post('/giftcards/buy', {
        giftcard_id: props.card.id,
        currency: currency.value,
        amount: Number(amount.value),
        quantity: qty.value,
        payment_method: 'NGN',
        pin,
      })
      pinModalOpen.value = false
      emit('success', data)
    } else {
      const fd = new FormData()
      fd.append('giftcard_id', props.card.id)
      fd.append('currency', currency.value)
      fd.append('type', type.value)
      fd.append('amount', String(Number(amount.value)))
      fd.append('quantity', String(qty.value))
      fd.append('method', 'NGN')
      fd.append('pin', pin)
      files.value.forEach((file, index) => file && fd.append('images[]', file, file.name || `upload-${index}.jpg`))

      const { data } = await post('/giftcards/sell', fd)
      pinModalOpen.value = false
      emit('success', data)
    }
  } catch (e) {
    const msg = e?.response?.data?.message || 'Transaction failed'
    if (msg.toLowerCase().includes('pin')) {
      pinError.value = msg
    } else {
      error.value = msg
      pinModalOpen.value = false
    }
  } finally {
    processing.value = false
  }
}
</script>

<template>
  <Card class="p-6 space-y-6">
    <header class="flex flex-wrap items-start justify-between gap-4">
      <div>
        <p class="text-xs uppercase tracking-[0.3em] text-orange-500 font-semibold">{{ mode === 'buy' ? 'Buy' : 'Sell' }} giftcard</p>
        <h3 class="mt-1 text-2xl font-semibold text-gray-900">{{ card?.name || 'Select a card' }}</h3>
        <p class="text-sm text-gray-500">Rate • {{ rateDisplay }}</p>
      </div>
      <button type="button" class="rounded-full border border-gray-200 p-2 text-gray-500 hover:text-gray-700" @click="$emit('cancel')">
        <X class="w-4 h-4" />
      </button>
    </header>

    <div class="grid gap-6 lg:grid-cols-2">
      <section class="space-y-4">
        <div>
          <p class="text-sm font-medium text-gray-700 mb-2">Currency</p>
          <div class="grid grid-cols-3 gap-2">
            <button
              v-for="option in currencyOptions"
              :key="option.value"
              type="button"
              class="rounded-2xl border px-3 py-2 text-sm font-medium"
              :class="currency === option.value ? 'border-orange-400 bg-orange-50 text-orange-600' : 'border-gray-200 text-gray-700'"
              @click="currency = option.value"
            >
              {{ option.label }}
            </button>
          </div>
        </div>

        <div>
          <div class="flex items-center justify-between mb-2">
            <p class="text-sm font-medium text-gray-700">Card value</p>
            <span class="text-xs text-gray-400">Per denomination</span>
          </div>
          <div class="rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3 flex items-center gap-3">
            <span class="text-gray-500 text-sm">{{ currency }}</span>
            <input
              v-model="amount"
              inputmode="decimal"
              placeholder="0.00"
              class="flex-1 bg-transparent text-gray-900 placeholder-gray-400 focus:outline-none"
            />
          </div>
          <div class="mt-3 grid grid-cols-3 sm:grid-cols-5 gap-2">
            <button
              v-for="value in quickValues"
              :key="value"
              type="button"
              class="rounded-2xl border px-3 py-1.5 text-sm font-semibold transition"
              :class="Number(amount) === Number(value) ? 'border-orange-400 bg-orange-50 text-orange-600' : 'border-gray-200 text-gray-600 hover:border-orange-200'"
              @click="setChip(value)"
            >
              {{ currency }} {{ value }}
            </button>
          </div>
        </div>

        <div class="grid sm:grid-cols-2 gap-4">
          <div>
            <p class="text-sm font-medium text-gray-700 mb-2">Quantity</p>
            <div class="flex items-center rounded-2xl border border-gray-200 overflow-hidden">
              <button type="button" class="px-4 py-2 hover:bg-gray-50" @click="adjustQty(-1)">
                <Minus class="w-4 h-4" />
              </button>
              <div class="flex-1 text-center text-lg font-semibold">{{ qty }}</div>
              <button type="button" class="px-4 py-2 hover:bg-gray-50" @click="adjustQty(1)">
                <Plus class="w-4 h-4" />
              </button>
            </div>
          </div>

          <div class="rounded-2xl border border-orange-200 bg-orange-50/40 px-4 py-3 text-xs text-orange-700">
            All giftcard trades settle from and to your fiat (NGN) balance.
          </div>
        </div>
      </section>

      <section class="space-y-4">
        <div v-if="mode === 'sell'">
          <p class="text-sm font-medium text-gray-700 mb-2">Giftcard type</p>
          <div class="grid grid-cols-2 gap-3">
            <label
              v-for="option in ['e-card', 'physical']"
              :key="option"
              class="inline-flex items-center justify-center gap-2 rounded-2xl border px-3 py-2 text-sm font-medium cursor-pointer"
              :class="type === option ? 'border-orange-400 bg-orange-50 text-orange-600' : 'border-gray-200 text-gray-600'"
            >
              <input class="sr-only" type="radio" :value="option" v-model="type" />
              {{ option === 'e-card' ? 'E-card' : 'Physical card' }}
            </label>
          </div>
        </div>

        <div class="rounded-2xl border border-gray-100 bg-white shadow-sm p-4">
          <p class="text-sm font-semibold text-gray-800">Order summary</p>
          <dl class="mt-3 space-y-2 text-sm text-gray-600">
            <div class="flex justify-between">
              <dt>Face value</dt>
              <dd>{{ currency }} {{ totalFaceValue.toLocaleString(undefined, { minimumFractionDigits: 2 }) }}</dd>
            </div>
            <div class="flex justify-between">
              <dt>Quantity</dt>
              <dd>{{ qty }}</dd>
            </div>
            <div class="flex justify-between">
              <dt>Rate</dt>
              <dd>{{ rateDisplay }}</dd>
            </div>
            <div class="flex justify-between font-semibold text-gray-900">
              <dt>{{ totalLabel }}</dt>
              <dd>{{ formattedEstimate }}</dd>
            </div>
          </dl>
        </div>

        <div v-if="mode === 'sell'" class="space-y-3">
          <p class="text-sm font-medium text-gray-700">Attachments</p>
          <label class="flex flex-col items-center justify-center gap-2 rounded-2xl border-2 border-dashed border-orange-200 bg-orange-50/50 px-4 py-6 text-center text-sm text-gray-500 cursor-pointer hover:border-orange-300">
            <Upload class="w-5 h-5 text-orange-500" />
            <span>Upload up to 6 images / receipts</span>
            <input type="file" class="hidden" accept="image/*" multiple @change="onPick" />
          </label>

          <div v-if="fileUrls.length" class="grid grid-cols-3 gap-3">
            <div v-for="(url, idx) in fileUrls" :key="idx" class="relative rounded-xl border border-gray-200 overflow-hidden">
              <img :src="url" class="w-full h-24 object-cover" />
              <button type="button" class="absolute top-1 right-1 rounded-full bg-black/70 text-white p-1" @click="removeFile(idx)">
                <X class="w-3 h-3" />
              </button>
            </div>
          </div>
        </div>
      </section>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-gray-50 px-4 py-4 flex items-center justify-between">
      <div>
        <p class="text-xs uppercase tracking-wide text-gray-500">{{ totalLabel }}</p>
        <p class="text-xl font-bold text-gray-900">{{ formattedEstimate }}</p>
      </div>
      <p class="text-xs text-gray-500">Rate based on card catalog.</p>
    </div>

    <div class="flex flex-col gap-3">
      <Button variant="outline" class="w-full" @click="$emit('cancel')">Cancel</Button>
      <Button class="w-full" :disabled="!valid || processing" @click="handleSubmit">
        {{ mode === 'buy' ? 'Proceed to buy' : 'Proceed to sell' }}
      </Button>
      <p v-if="error" class="text-sm text-red-600 text-center">{{ error }}</p>
    </div>

    <PinModal
      :open="pinModalOpen"
      :processing="processing"
      :error="pinError"
      @close="pinModalOpen = false"
      @submit="handlePinSubmit"
    />
  </Card>
</template>
