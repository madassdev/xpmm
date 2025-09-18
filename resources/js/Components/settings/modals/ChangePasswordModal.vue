<script setup>
import { ref, computed } from 'vue'
import Modal from '@/Components/ui/Modal.vue'

const props = defineProps({ open: Boolean })
const emit = defineEmits(['close'])

const current = ref('')
const next = ref('')
const confirm = ref('')
const loading = ref(false)

const valid = computed(() => current.value && next.value.length >= 6 && next.value === confirm.value)

async function submit() {
  if (!valid.value) return
  loading.value = true
  await new Promise(r => setTimeout(r, 1000)) // mock
  loading.value = false
  emit('close') // in real app, also toast success
}
</script>

<template>
  <Modal :open="open" title="Change Password" :disabled="loading" @close="$emit('close')" @submit="submit">
    <div class="space-y-3">
      <div>
        <label class="text-xs text-gray-600">Current Password</label>
        <input v-model="current" type="password" class="mt-1 w-full rounded-xl border border-gray-300 px-3 py-2 focus:outline-none" />
      </div>
      <div>
        <label class="text-xs text-gray-600">New Password</label>
        <input v-model="next" type="password" class="mt-1 w-full rounded-xl border border-gray-300 px-3 py-2 focus:outline-none" />
      </div>
      <div>
        <label class="text-xs text-gray-600">Confirm Password</label>
        <input v-model="confirm" type="password" class="mt-1 w-full rounded-xl border border-gray-300 px-3 py-2 focus:outline-none" />
      </div>
      <p class="text-xs text-gray-500">Password must be at least 6 characters.</p>
    </div>

    <template #primary>
      <span v-if="loading">Saving…</span>
      <span v-else :class="!valid ? 'opacity-60' : ''">Save</span>
    </template>
  </Modal>
</template>
