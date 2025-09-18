<script setup>
import { ref, computed, watch, nextTick } from 'vue'
import Button from '@/Components/ui/Button.vue'
import PinInput from '@/Components/form/PinInput.vue'

const props = defineProps({
  open: { type: Boolean, default: false },

  // 'confirm' | 'processing' | 'success' | 'error'
  phase: { type: String, default: 'confirm' },

  // Optional titles/messages for success/error
  title: { type: String, default: '' },
  message: { type: String, default: '' },

  // Order details: [{label, value}]
  details: { type: Array, default: () => [] },

  // PIN
  requirePin: { type: Boolean, default: true },
  pinLength: { type: Number, default: 4 },       // <<— number of boxes
  // kept for backward compat (ignored when pinLength is set)
  pinMin: { type: Number, default: 4 },
  pinMax: { type: Number, default: 6 },
  pinLabel: { type: String, default: 'Enter Transaction PIN' },
})

const emit = defineEmits(['close', 'primary', 'secondary', 'submit'])

const isConfirm = computed(() => props.phase === 'confirm')
const isProcessing = computed(() => props.phase === 'processing')
const isSuccess = computed(() => props.phase === 'success')
const isError = computed(() => props.phase === 'error')

// ---------- PIN boxes ----------
const boxes = computed(() => Array.from({ length: props.pinLength }))
const digits = ref(Array(props.pinLength).fill(''))
const inputs = ref([]) // array of input refs

function setInputRef(el, idx) {
  if (el) inputs.value[idx] = el
}

function onlyDigits(v) { return (v || '').replace(/[^\d]/g, '') }

function focusBox(i) {
  nextTick(() => { inputs.value[i]?.focus() })
}

function onInput(i, e) {
  const val = onlyDigits(e.target.value).slice(-1) // keep last digit typed
  digits.value[i] = val
  if (val && i < props.pinLength - 1) focusBox(i + 1)
}

function onKeydown(i, e) {
  if (e.key === 'Backspace') {
    if (digits.value[i]) {
      digits.value[i] = ''
    } else if (i > 0) {
      focusBox(i - 1)
      digits.value[i - 1] = ''
    }
    e.preventDefault()
  } else if (e.key === 'ArrowLeft' && i > 0) {
    focusBox(i - 1)
    e.preventDefault()
  } else if (e.key === 'ArrowRight' && i < props.pinLength - 1) {
    focusBox(i + 1)
    e.preventDefault()
  } else if (e.key === 'Enter') {
    trySubmit()
  }
}

function onPaste(e) {
  const txt = onlyDigits(e.clipboardData.getData('text') || '')
  if (!txt) return
  e.preventDefault()
  for (let i = 0; i < props.pinLength; i++) {
    digits.value[i] = txt[i] || ''
  }
  const nextEmpty = digits.value.findIndex(d => !d)
  focusBox(nextEmpty === -1 ? props.pinLength - 1 : nextEmpty)
}

const pin = ref('')              // collect from PinInput
const pinError = ref('')
const pinLength = 4              // or get from prop
const pinValid = computed(() => !props.requirePin || pin.value.length === props.pinLength)

watch(() => props.open, (o) => {
  if (!o) { pin.value = ''; pinError.value = '' }
})

function trySubmit() {
  if (props.requirePin && !pinValid.value) {
    pinError.value = `Enter ${props.pinLength} digits`
    return
  }
  pinError.value = ''
  emit('submit', pin.value) // parent should set phase -> 'processing'
}
</script>

<template>
  <transition name="fade">
    <div v-if="open" class="fixed inset-0 z-50">
      <div class="absolute inset-0 bg-black/40 backdrop-blur-[1px]" @click="$emit('close')" />
      <div class="absolute inset-0 grid place-items-center p-4">
        <div class="w-full max-w-md md:max-w-lg rounded-2xl bg-white shadow-xl border border-gray-200 overflow-hidden">
          <!-- Confirm -->
          <div v-if="isConfirm" class="px-6 py-6">
            <h3 class="text-lg font-semibold text-gray-900 text-center">Confirm Purchase</h3>
            <p v-if="message" class="mt-1 text-sm text-gray-500 text-center">{{ message }}</p>

            <div v-if="details.length" class="mt-5 border-y divide-y">
              <div v-for="(d, i) in details" :key="i" class="flex items-center justify-between py-2 text-sm">
                <span class="text-gray-500">{{ d.label }}</span>
                <span class="font-medium text-gray-900">{{ d.value }}</span>
              </div>
            </div>

            <div v-if="requirePin" class="mt-5">
              <label class="block text-xs font-medium text-gray-600 mb-2">{{ pinLabel }}</label>
              <PinInput v-model="pin" :length="pinLength" :mask="true" />
              <p v-if="pinError" class="mt-2 text-xs text-red-600">{{ pinError }}</p>
            </div>

            <div class="mt-6 flex items-center gap-2">
              <Button variant="outline" class="flex-1" @click="$emit('close')">Cancel</Button>
              <Button class="flex-1" :disabled="requirePin && !pinValid" @click="trySubmit">
                Pay
              </Button>
            </div>
          </div>

          <!-- Processing -->
          <div v-else-if="isProcessing" class="px-6 py-8 text-center">
            <div class="mx-auto w-14 h-14 rounded-full border-2 border-gray-200 grid place-items-center mb-4">
              <div class="w-6 h-6 rounded-full border-2 border-primary border-t-transparent animate-spin" />
            </div>
            <h3 class="text-lg font-semibold text-gray-900">Processing payment…</h3>
            <p class="mt-1 text-sm text-gray-500">Hold on while we complete your purchase.</p>
          </div>

          <!-- Success -->
          <div v-else-if="isSuccess" class="px-6 py-6">
            <div class="mx-auto w-14 h-14 rounded-full bg-green-100 text-green-700 grid place-items-center mb-3">
              <svg class="w-7 h-7" viewBox="0 0 24 24" fill="currentColor">
                <path d="M9 16.17 4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z" />
              </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 text-center">{{ title || 'Payment successful' }}</h3>
            <p v-if="message" class="mt-1 text-sm text-gray-500 text-center">{{ message }}</p>

            <div v-if="details.length" class="mt-4 border-y divide-y">
              <div v-for="(d, i) in details" :key="i" class="flex items-center justify-between py-2 text-sm">
                <span class="text-gray-500">{{ d.label }}</span>
                <span class="font-medium text-gray-900">{{ d.value }}</span>
              </div>
            </div>

            <div class="mt-5 flex items-center gap-2">
              <Button class="flex-1" @click="$emit('primary')">Done</Button>
            </div>
          </div>

          <!-- Error -->
          <div v-else class="px-6 py-6">
            <div class="mx-auto w-14 h-14 rounded-full bg-red-100 text-red-700 grid place-items-center mb-3">
              <svg class="w-7 h-7" viewBox="0 0 24 24" fill="currentColor">
                <path d="M6 6l12 12M6 18L18 6" />
              </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 text-center">{{ title || 'Payment failed' }}</h3>
            <p v-if="message" class="mt-1 text-sm text-gray-500 text-center">{{ message }}</p>

            <div v-if="details.length" class="mt-4 border-y divide-y">
              <div v-for="(d, i) in details" :key="i" class="flex items-center justify-between py-2 text-sm">
                <span class="text-gray-500">{{ d.label }}</span>
                <span class="font-medium text-gray-900">{{ d.value }}</span>
              </div>
            </div>

            <div class="mt-5 flex items-center gap-2">
              <Button variant="outline" class="flex-1" @click="$emit('secondary')">Try again</Button>
              <Button class="flex-1" @click="$emit('primary')">Close</Button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </transition>
</template>

<style>
.fade-enter-active,
.fade-leave-active {
  transition: opacity .15s ease
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0
}
</style>
