<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { ChevronDown } from 'lucide-vue-next'

const props = defineProps({
  modelValue: [String, Number],
  options: { type: Array, default: () => [] }, // [{value,label,logo}]
  placeholder: { type: String, default: 'Select Network' },
})
const emit = defineEmits(['update:modelValue'])

const open = ref(false)
const root = ref(null)
const selected = () => props.options.find(o => o.value === props.modelValue)

const onDoc = (e) => { if (!root.value) return; if (!root.value.contains(e.target)) open.value = false }
onMounted(() => document.addEventListener('click', onDoc, true))
onBeforeUnmount(() => document.removeEventListener('click', onDoc, true))
</script>

<template>
  <div ref="root" class="relative">
    <!-- Trigger -->
    <button
      type="button"
      class="w-full rounded-2xl bg-gray-50 border border-gray-200 px-4 py-4 flex items-center justify-between"
      @click="open = !open"
    >
      <!-- Left: label -->
      <span class="truncate text-gray-900 font-medium" :class="!modelValue ? 'text-gray-400 font-normal' : ''">
        {{ selected()?.label || placeholder }}
      </span>

      <!-- Right: logo bubble + chevron -->
      <div class="flex items-center gap-2">
        <div class="w-10 h-10 rounded-full bg-white border border-gray-200 grid place-items-center overflow-hidden">
          <img v-if="selected()?.logo" :src="selected()?.logo" class="w-7 h-7 object-contain" :alt="selected()?.label" />
          <span v-else class="text-xs text-gray-400">—</span>
        </div>
        <ChevronDown class="w-4 h-4 text-gray-500" />
      </div>
    </button>

    <!-- Menu -->
    <div v-if="open" class="absolute z-40 mt-2 w-full rounded-xl border border-gray-200 bg-white shadow-lg p-1">
      <button
        v-for="opt in options"
        :key="opt.value"
        class="w-full text-left px-3 py-2 rounded-md text-sm hover:bg-gray-50 flex items-center justify-between"
        @click="emit('update:modelValue', opt.value); open=false"
      >
        <span class="truncate text-gray-800">{{ opt.label }}</span>
        <div class="w-8 h-8 rounded-full bg-white border grid place-items-center overflow-hidden">
          <img v-if="opt.logo" :src="opt.logo" class="w-6 h-6 object-contain" :alt="opt.label" />
        </div>
      </button>
    </div>
  </div>
</template>
