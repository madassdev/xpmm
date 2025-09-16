<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { ChevronDown } from 'lucide-vue-next'

const props = defineProps({
  modelValue: String, // 'BTC' | 'USDT' | ...
  options: { type: Array, default: () => [] }, // [{code,label,balance,logo}]
  placeholder: { type: String, default: 'BTC' },
  fallbackBalance: { type: String, default: '0.00' },
})
const emit = defineEmits(['update:modelValue'])

const open = ref(false)
const root = ref(null)
const selected = () => props.options.find(o => o.code === props.modelValue)

const onDoc = (e) => { if (!root.value) return; if (!root.value.contains(e.target)) open.value = false }
onMounted(() => document.addEventListener('click', onDoc, true))
onBeforeUnmount(() => document.removeEventListener('click', onDoc, true))
</script>

<template>
  <div ref="root" class="relative">
    <!-- Trigger -->
    <button
      type="button"
      class="w-full rounded-2xl bg-gray-50 border border-gray-200 px-0 py-0 flex items-stretch justify-between overflow-hidden"
      @click="open = !open"
    >
      <!-- Left: two-line label -->
      <div class="flex-1 px-4 py-3.5 text-left">
        <div class="text-sm font-semibold"
             :class="selected() ? 'text-gray-900' : 'text-gray-400 font-medium'">
          {{ selected()?.label || placeholder }}
        </div>
        <div class="text-xs text-gray-500 leading-tight">
          {{ selected()?.balance || fallbackBalance }}
        </div>
      </div>

      <!-- Right: divider + coin bubble + chevron -->
      <div class="pl-4 pr-3 flex items-center gap-2 border-l border-gray-200">
        <div class="w-9 h-9 rounded-full bg-white grid place-items-center border overflow-hidden">
          <img v-if="selected()?.logo" :src="selected()?.logo" class="w-5 h-5 object-contain" :alt="selected()?.label" />
          <span v-else class="text-xs text-gray-400">₿</span>
        </div>
        <ChevronDown class="w-4 h-4 text-gray-500" />
      </div>
    </button>

    <!-- Menu -->
    <div v-if="open" class="absolute z-40 mt-2 w-full rounded-xl border border-gray-200 bg-white shadow-lg p-1">
      <button
        v-for="opt in options"
        :key="opt.code"
        class="w-full text-left px-3 py-2 rounded-md text-sm hover:bg-gray-50 flex items-center justify-between"
        @click="emit('update:modelValue', opt.code); open=false"
      >
        <div>
          <div class="text-gray-800 font-medium">{{ opt.label }}</div>
          <div class="text-xs text-gray-500">{{ opt.balance }}</div>
        </div>
        <div class="w-8 h-8 rounded-full bg-white border grid place-items-center overflow-hidden">
          <img v-if="opt.logo" :src="opt.logo" class="w-5 h-5 object-contain" :alt="opt.label" />
        </div>
      </button>
    </div>
  </div>
</template>
