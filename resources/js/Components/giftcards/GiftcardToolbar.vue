<script setup>
import { ref, watch } from 'vue'
import {
  Store,
  ShoppingBag,
  RefreshCcw,
  Search,
  Filter,
  SlidersHorizontal
} from 'lucide-vue-next'

const props = defineProps({
  tab: { type: String, default: 'sell' },     // 'sell' | 'buy' | 'trades'
  search: { type: String, default: '' },
})
const emit = defineEmits(['update:tab','update:search','refresh'])

const localTab = ref(props.tab)
const q = ref(props.search)

watch(() => props.tab, v => (localTab.value = v))
watch(() => props.search, v => (q.value = v))
watch(localTab, v => emit('update:tab', v))
watch(q, v => emit('update:search', v))

function tabBtnClass(active) {
  return [
    // base
    'inline-flex items-center gap-2 rounded-2xl border text-sm font-medium transition select-none',
    'px-4 py-2',
    active
      ? 'bg-primary text-white border-primary shadow-sm'
      : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'
  ].join(' ')
}

function iconWrapClass(active) {
  return [
    'inline-flex items-center justify-center w-6 h-6 rounded-full',
    active ? 'bg-white/20 text-white' : 'bg-primary/10 text-primary'
  ].join(' ')
}
</script>

<template>
  <div class="space-y-3">
    <!-- Segmented nav -->
    <div class="flex flex-wrap items-center gap-2">
      <button
        role="tab"
        aria-pressed="true"
        :class="tabBtnClass(localTab==='sell')"
        @click="localTab = 'sell'"
      >
        <span :class="iconWrapClass(localTab==='sell')">
          <Store class="w-4 h-4" />
        </span>
        <span>Sell Giftcards</span>
      </button>

      <button
        role="tab"
        :class="tabBtnClass(localTab==='buy')"
        @click="localTab = 'buy'"
      >
        <span :class="iconWrapClass(localTab==='buy')">
          <ShoppingBag class="w-4 h-4" />
        </span>
        <span>Buy Giftcards</span>
      </button>

      <button
        role="tab"
        :class="tabBtnClass(localTab==='trades')"
        @click="localTab = 'trades'"
      >
        <span :class="iconWrapClass(localTab==='trades')">
          <RefreshCcw class="w-4 h-4" />
        </span>
        <span>Giftcard Trades</span>
      </button>
    </div>

    <!-- Tools row -->
    <div class="flex items-center gap-2">
      <div class="relative w-full max-w-xs">
        <Search class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" />
        <input
          :value="q"
          @input="e => q = e.target.value"
          placeholder="Find a giftcard"
          class="w-full h-10 pl-9 pr-3 rounded-2xl border border-gray-200 bg-white text-sm
                 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary/20"
        />
      </div>

      <button
        class="inline-flex items-center justify-center w-10 h-10 rounded-2xl border border-gray-200 bg-white hover:bg-gray-50"
        title="Filter"
      >
        <Filter class="w-4 h-4 text-gray-700" />
      </button>

      <button
        class="inline-flex items-center justify-center w-10 h-10 rounded-2xl border border-gray-200 bg-white hover:bg-gray-50"
        title="Sort"
      >
        <SlidersHorizontal class="w-4 h-4 text-gray-700" />
      </button>

      <button
        class="hidden md:inline-flex items-center gap-2 rounded-2xl border border-gray-200 bg-white px-3 h-10 hover:bg-gray-50"
        @click="$emit('refresh')"
        title="Refresh"
      >
        <RefreshCcw class="w-4 h-4" />
        <span class="text-sm">Refresh</span>
      </button>
    </div>
  </div>
</template>
