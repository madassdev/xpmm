<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { ChevronDown } from 'lucide-vue-next'

const props = defineProps({
  modelValue: [String, Number],
  options: { type: Array, default: () => [] }, // [{value,label}]
  placeholder: { type: String, default: 'Select...' },
})
const emit = defineEmits(['update:modelValue'])
const open = ref(false)
const root = ref(null)
const label = (v) => props.options.find(o => o.value === v)?.label

const onDoc = (e) => { if (!root.value) return; if (!root.value.contains(e.target)) open.value = false }
onMounted(() => document.addEventListener('click', onDoc, true))
onBeforeUnmount(() => document.removeEventListener('click', onDoc, true))
</script>

<template>
  <div ref="root" class="relative">
    <button
      type="button"
      class="w-full rounded-2xl bg-gray-50 border border-gray-200 px-4 py-4 text-left text-gray-700 flex items-center justify-between"
      @click="open = !open"
    >
      <span class="truncate" :class="!modelValue ? 'text-gray-400' : ''">
        {{ label(modelValue) || placeholder }}
      </span>
      <ChevronDown class="w-4 h-4 text-gray-500" />
    </button>

    <transition name="fade">
      <div
        v-if="open"
        class="absolute z-40 mt-2 w-full rounded-xl border border-gray-200 bg-white shadow-lg p-1"
      >
        <button
          v-for="opt in options"
          :key="opt.value"
          class="w-full text-left px-3 py-2 rounded-md text-sm hover:bg-gray-100"
          @click="emit('update:modelValue', opt.value); open=false"
        >
          {{ opt.label }}
        </button>
      </div>
    </transition>
  </div>
</template>

<style>
.fade-enter-active,.fade-leave-active{transition:opacity .12s ease}
.fade-enter-from,.fade-leave-to{opacity:0}
</style>
