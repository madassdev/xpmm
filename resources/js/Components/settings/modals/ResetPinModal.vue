<script setup>
import { ref, computed } from 'vue'
import Modal from '@/Components/ui/Modal.vue'

const props = defineProps({ open: Boolean })
const emit = defineEmits(['close'])

const current = ref('')
const next = ref('')
const confirm = ref('')
const loading = ref(false)
const onlyDigits = (v) => (v || '').replace(/[^\d]/g, '')

const valid = computed(() =>
  current.value.length >= 4 && next.value.length >= 4 && next.value === confirm.value
)

async function submit() {
  if (!valid.value) return
  loading.value = true
  await new Promise(r => setTimeout(r, 900))
  loading.value = false
  emit('close')
}
</script>

<template>
  <Modal :open="open" title="Reset PIN" :disabled="loading" @close="$emit('close')" @submit="submit">
    <div class="space-y-3">
      <div>
        <label class="text-xs text-gray-600">Current PIN</label>
        <input :value="current" @input="e => current = onlyDigits(e.target.value)" inputmode="numeric" type="password"
               class="mt-1 w-full rounded-xl border border-gray-300 px-3 py-2 focus:outline-none" />
      </div>
      <div>
        <label class="text-xs text-gray-600">New PIN</label>
        <input :value="next" @input="e => next = onlyDigits(e.target.value)" inputmode="numeric" type="password"
               class="mt-1 w-full rounded-xl border border-gray-300 px-3 py-2 focus:outline-none" />
      </div>
      <div>
        <label class="text-xs text-gray-600">Confirm PIN</label>
        <input :value="confirm" @input="e => confirm = onlyDigits(e.target.value)" inputmode="numeric" type="password"
               class="mt-1 w-full rounded-xl border border-gray-300 px-3 py-2 focus:outline-none" />
      </div>
      <p class="text-xs text-gray-500">PIN should be 4–6 digits.</p>
    </div>

    <template #primary>
      <span v-if="loading">Saving…</span>
      <span v-else :class="!valid ? 'opacity-60' : ''">Save</span>
    </template>
  </Modal>
</template>
