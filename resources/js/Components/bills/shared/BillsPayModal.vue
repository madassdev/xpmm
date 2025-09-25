<script setup>
import { ref, computed, watch, nextTick } from 'vue'
import Button from '@/Components/ui/Button.vue'
import PinInput from '@/Components/form/PinInput.vue'

const props = defineProps({
  open: { type: Boolean, default: false },

  // External phase prop (we'll mirror into local currentPhase)
  phase: { type: String, default: 'confirm' }, // 'confirm' | 'processing' | 'success' | 'error'

  // Optional titles/messages for success/error
  title: { type: String, default: '' },
  message: { type: String, default: '' },

  // Order details: [{label, value}]
  details: { type: Array, default: () => [] },

  // PIN config
  requirePin: { type: Boolean, default: true },
  pinLength: { type: Number, default: 4 },
  pinLabel:  { type: String, default: 'Enter Transaction PIN' },

  // Error metadata (use this for precise detection)
  // Set to "invalid_pin" when backend rejects PIN
  errorCode: { type: String, default: '' },
})

const emit = defineEmits(['close','primary','secondary','submit','update:phase'])

// Local phase we can control for UX without forcing parent changes
const currentPhase = ref(props.phase)
watch(() => props.phase, v => { currentPhase.value = v })

const isConfirm    = computed(() => currentPhase.value === 'confirm')
const isProcessing = computed(() => currentPhase.value === 'processing')
const isSuccess    = computed(() => currentPhase.value === 'success')
const isError      = computed(() => currentPhase.value === 'error')

// ---- PIN handling (via reusable PinInput) ----
const pin = ref('')
const pinError = ref('')
const pinRef = ref(null)

const pinValid = computed(() => !props.requirePin || pin.value.length === props.pinLength)

function resetPin({ focus = false, message = '' } = {}) {
  pin.value = ''
  pinError.value = message
  nextTick(() => {
    pinRef.value?.clear?.()
    if (focus) pinRef.value?.focus?.()
  })
}

function goToConfirm({ withPinError = '' } = {}) {
  currentPhase.value = 'confirm'
  emit('update:phase', 'confirm')
  resetPin({ focus: true, message: withPinError })
}

function trySubmit() {
  if (props.requirePin && !pinValid.value) {
    pinError.value = `Enter ${props.pinLength} digits`
    return
  }
  pinError.value = ''
  // Optional immediate feedback
  currentPhase.value = 'processing'
  emit('update:phase', 'processing')
  emit('submit', pin.value) // parent will complete with success/error
}

// When modal opens/closes
watch(() => props.open, (o) => {
  if (!o) {
    pin.value = ''
    pinError.value = ''
    // do not force phase here; parent may decide
  } else if (isConfirm.value) {
    nextTick(() => pinRef.value?.focus?.())
  }
})

// Auto-bounce back to PIN when the backend says invalid pin.
// Prefer errorCode === 'invalid_pin'. As a fallback, we detect common phrases.
watch(
  () => ({ phase: props.phase, errorCode: props.errorCode, message: props.message, open: props.open }),
  ({ phase, errorCode, message, open }) => {
    if (!open) return
    const looksLikeInvalid =
      String(errorCode).toLowerCase() === 'invalid_pin' ||
      /invalid\s*pin|incorrect\s*pin/i.test(String(message || ''))

    if (phase === 'error' && looksLikeInvalid) {
      goToConfirm({ withPinError: 'Incorrect PIN. Please try again.' })
    }
  },
  { immediate: true }
)

// “Try again” in generic errors → go back to PIN
function onTryAgain() {
  goToConfirm()
  emit('secondary') // still let parent know a retry was requested
}
</script>

<template>
  <transition name="fade">
    <div v-if="open" class="fixed inset-0 z-50">
      <div class="absolute inset-0 bg-black/40 backdrop-blur-[1px]" @click="$emit('close')" />
      <div class="absolute inset-0 grid place-items-center p-4">
        <div class="w-full max-w-md md:max-w-lg rounded-2xl bg-white shadow-xl border border-gray-200 overflow-hidden">
          <!-- Confirm (summary + PIN) -->
          <div v-if="isConfirm" class="px-6 py-6">
            <h3 class="text-lg font-semibold text-gray-900 text-center">Confirm Purchase</h3>
            <p v-if="message" class="mt-1 text-sm text-gray-500 text-center">{{ message }}</p>

            <div v-if="details.length" class="mt-5 border-y divide-y">
              <div v-for="(d,i) in details" :key="i" class="flex items-center justify-between py-2 text-sm">
                <span class="text-gray-500">{{ d.label }}</span>
                <span class="font-medium text-gray-900">{{ d.value }}</span>
              </div>
            </div>

            <div v-if="requirePin" class="mt-5">
              <label class="block text-xs font-medium text-gray-600 mb-2">{{ pinLabel }}</label>
              <PinInput ref="pinRef" v-model="pin" :length="pinLength" :mask="true" />
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
              <svg class="w-7 h-7" viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.17 4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 text-center">{{ title || 'Payment successful' }}</h3>
            <p v-if="message" class="mt-1 text-sm text-gray-500 text-center">{{ message }}</p>

            <div v-if="details.length" class="mt-4 border-y divide-y">
              <div v-for="(d,i) in details" :key="i" class="flex items-center justify-between py-2 text-sm">
                <span class="text-gray-500">{{ d.label }}</span>
                <span class="font-medium text-gray-900">{{ d.value }}</span>
              </div>
            </div>

            <div class="mt-5 flex items-center gap-2">
              <Button class="flex-1" @click="$emit('primary')">Done</Button>
            </div>
          </div>

          <!-- Error (generic) -->
          <div v-else class="px-6 py-6">
            <div class="mx-auto w-14 h-14 rounded-full bg-red-100 text-red-700 grid place-items-center mb-3">
              <svg class="w-7 h-7" viewBox="0 0 24 24" fill="currentColor"><path d="M6 6l12 12M6 18L18 6"/></svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 text-center">{{ title || 'Payment failed' }}</h3>
            <p v-if="message" class="mt-1 text-sm text-gray-500 text-center">{{ message }}</p>

            <div v-if="details.length" class="mt-4 border-y divide-y">
              <div v-for="(d,i) in details" :key="i" class="flex items-center justify-between py-2 text-sm">
                <span class="text-gray-500">{{ d.label }}</span>
                <span class="font-medium text-gray-900">{{ d.value }}</span>
              </div>
            </div>

            <div class="mt-5 flex items-center gap-2">
              <Button variant="outline" class="flex-1" @click="onTryAgain">Try again</Button>
              <Button class="flex-1" @click="$emit('primary')">Close</Button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </transition>
</template>

<style>
.fade-enter-active,.fade-leave-active{transition:opacity .15s ease}
.fade-enter-from,.fade-leave-to{opacity:0}
</style>
