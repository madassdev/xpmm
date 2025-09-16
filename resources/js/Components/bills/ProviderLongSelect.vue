<script setup>
import { ref, onMounted, onBeforeUnmount, computed } from 'vue'
import { ChevronDown } from 'lucide-vue-next'

const props = defineProps({
  modelValue: { type: String, default: '' }, // id
  options: { type: Array, default: () => [] }, // [{id,name}]
  placeholder: { type: String, default: 'Select Provider' },
  disabled: { type: Boolean, default: false },
})
const emit = defineEmits(['update:modelValue'])

const open = ref(false)
const root = ref(null)
const selected = computed(() => props.options.find(o => o.id === props.modelValue) || null)

function onDoc(e) { if (!root.value) return; if (!root.value.contains(e.target)) open.value = false }
onMounted(() => document.addEventListener('click', onDoc, true))
onBeforeUnmount(() => document.removeEventListener('click', onDoc, true))
</script>

<template>
  <div ref="root" class="relative">
    <button
      type="button"
      class="w-full rounded-2xl bg-gray-50 border border-gray-200 px-4 py-4 flex items-center justify-between text-left"
      :class="disabled ? 'opacity-60 cursor-not-allowed' : ''"
      @click="!disabled && (open = !open)"
    >
      <span class="truncate" :class="selected ? 'text-gray-900' : 'text-gray-400'">
        {{ selected?.name || placeholder }}
      </span>
      <ChevronDown class="w-4 h-4 text-gray-500" />
    </button>

    <div
      v-if="open"
      class="absolute z-40 mt-2 w-full rounded-xl border border-gray-200 bg-white shadow-lg overflow-hidden"
    >
      <div class="max-h-64 overflow-auto p-1">
        <button
          v-for="opt in options"
          :key="opt.id"
          class="w-full text-left px-3 py-2 rounded-md hover:bg-gray-50"
          @click="emit('update:modelValue', opt.id); open=false"
        >
          <span class="text-sm text-gray-800">{{ opt.name }}</span>
        </button>
      </div>
    </div>
  </div>
</template>
