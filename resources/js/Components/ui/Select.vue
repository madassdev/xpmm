<template>
  <div class="relative inline-block" ref="root">
    <button type="button"
            class="inline-flex items-center justify-between gap-2 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40 min-w-[9rem]"
            @click="toggle">
      <div class="truncate text-left">
        <slot name="value" :option="selected">
          {{ selected?.label ?? placeholder }}
        </slot>
      </div>
      <svg class="w-4 h-4 text-gray-500" viewBox="0 0 20 20" fill="currentColor"><path d="M10 12l-4-4h8l-4 4z"/></svg>
    </button>
    <transition name="fade">
      <div v-if="open"
           class="absolute z-40 mt-2 min-w-full rounded-lg border border-gray-200 bg-white shadow-lg p-1"
           :class="alignClass"
           role="listbox"
           @keydown.esc="close">
        <button
          v-for="opt in options"
          :key="opt.value"
          @click="choose(opt)"
          class="w-full text-left px-3 py-2 rounded-md text-sm hover:bg-gray-100"
          :aria-selected="opt.value === modelValue"
          role="option"
        >
          <slot name="option" :option="opt">
            {{ opt.label }}
          </slot>
        </button>
      </div>
    </transition>
  </div>
</template>
<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
const props = defineProps({
  modelValue: [String, Number, Object],
  options: { type: Array, default: () => [] },
  placeholder: { type: String, default: 'Select...' },
  align: { type: String, default: 'left' }
})
const emit = defineEmits(['update:modelValue', 'change', 'open', 'close'])
const open = ref(false)
const root = ref(null)
const alignClass = computed(() => props.align === 'right' ? 'right-0' : 'left-0')
const selected = computed(() => props.options.find(o => o.value === props.modelValue))
const toggle = () => { open.value = !open.value; emit(open.value ? 'open' : 'close') }
const close = () => { if (open.value) { open.value = false; emit('close') } }
const onDoc = (e) => { if (!root.value) return; if (!root.value.contains(e.target)) close() }
onMounted(() => document.addEventListener('click', onDoc, true))
onBeforeUnmount(() => document.removeEventListener('click', onDoc, true))
const choose = (opt) => { emit('update:modelValue', opt.value); emit('change', opt); close() }
</script>
<style>
.fade-enter-active,.fade-leave-active{transition:opacity .12s ease}
.fade-enter-from,.fade-leave-to{opacity:0}
</style>
