<script setup>
import { onMounted, onBeforeUnmount, computed } from 'vue'

const props = defineProps({
  open: { type: Boolean, default: false },
  title: { type: String, default: '' },
  size: { type: String, default: 'md' }, // sm|md|lg
  disabled: { type: Boolean, default: false },
})
const emit = defineEmits(['close', 'submit'])

function onEsc(e) { if (e.key === 'Escape') emit('close') }
onMounted(() => document.addEventListener('keydown', onEsc))
onBeforeUnmount(() => document.removeEventListener('keydown', onEsc))

const maxW = computed(() => ({
  sm: 'max-w-md',
  md: 'max-w-lg',
  lg: 'max-w-2xl',
}[props.size] || 'max-w-lg'))
</script>

<template>
  <teleport to="body">
    <div v-if="open" class="fixed inset-0 z-[120] flex items-center justify-center p-4">
      <!-- Dark backdrop -->
      <div class="absolute inset-0 bg-black/40" @click="!disabled && $emit('close')" />

      <!-- Constrained modal card -->
      <div
        :class="[
          'relative mx-auto bg-white rounded-2xl shadow-xl border border-gray-200',
          maxW,
          'overflow-hidden'
        ]"
      >
        <!-- Header -->
        <div class="px-5 py-4 border-b border-gray-100">
          <h3 class="text-base font-semibold text-gray-900">{{ title }}</h3>
        </div>

        <!-- Body -->
        <div class="px-5 py-4 max-h-[70vh] overflow-y-auto">
          <slot />
        </div>

        <!-- Footer -->
        <div class="px-5 py-4 flex items-center justify-end gap-3 border-t border-gray-100">
          <button
            class="px-4 py-2 rounded-xl border border-gray-300 text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-60"
            @click="$emit('close')"
            :disabled="disabled"
          >
            Cancel
          </button>
          <button
            class="px-4 py-2 rounded-xl bg-primary text-white hover:opacity-90 disabled:opacity-60"
            @click="$emit('submit')"
            :disabled="disabled"
          >
            <slot name="primary">Save</slot>
          </button>
        </div>
      </div>
    </div>
  </teleport>
</template>
