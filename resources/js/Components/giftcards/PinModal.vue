<script setup>
import { ref, computed, watch, nextTick } from 'vue'
import Modal from '@/Components/ui/Modal.vue'
import PinInput from '@/Components/form/PinInput.vue'

const props = defineProps({
  open: { type: Boolean, default: false },
  processing: { type: Boolean, default: false },
  error: { type: String, default: '' },
})

const emit = defineEmits(['close', 'submit'])

const pin = ref('')
const pinError = ref('')
const pinRef = ref(null)

const pinValid = computed(() => pin.value.length === 4)

watch(() => props.open, (open) => {
  if (open) {
    pin.value = ''
    pinError.value = ''
    nextTick(() => pinRef.value?.focus())
  }
})

watch(() => props.error, (error) => {
  if (error && props.open) {
    pinError.value = error
    pin.value = ''
    nextTick(() => {
      pinRef.value?.clear()
      pinRef.value?.focus()
    })
  }
})

function handleSubmit() {
  if (!pinValid.value) {
    pinError.value = 'Enter 4-digit PIN'
    return
  }
  pinError.value = ''
  emit('submit', pin.value)
}

function handleClose() {
  pin.value = ''
  pinError.value = ''
  emit('close')
}
</script>

<template>
  <Modal 
    :open="open" 
    title="Enter Transaction PIN" 
    :disabled="!pinValid || processing"
    @close="handleClose"
    @submit="handleSubmit"
  >
    <template #default>
      <div class="space-y-4">
        <p class="text-sm text-gray-600 text-center">
          Please enter your 4-digit transaction PIN to proceed
        </p>
        
        <div class="flex justify-center">
          <PinInput 
            ref="pinRef"
            v-model="pin"
            :length="4"
            :disabled="processing"
            @complete="handleSubmit"
          />
        </div>
        
        <p v-if="pinError" class="text-sm text-red-600 text-center">{{ pinError }}</p>
      </div>
    </template>

    <template #primary>
      <span class="flex items-center gap-2">
        <svg v-if="processing" class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none">
          <circle cx="12" cy="12" r="10" stroke="currentColor" class="opacity-25"></circle>
          <path d="M4 12a8 8 0 018-8" stroke="currentColor" class="opacity-75"></path>
        </svg>
        {{ processing ? 'Processing…' : 'Confirm' }}
      </span>
    </template>
  </Modal>
</template>

