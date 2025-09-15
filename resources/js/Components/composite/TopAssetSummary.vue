<script setup>
import { reactive, watch } from 'vue'
import Select from '@/Components/ui/Select.vue'
import AssetMiniCard from './AssetMiniCard.vue'
const props = defineProps({
  assets: { type: Array, default: () => [] },
  filters: { type: Object, default: () => ({ range: '24H', rank: 'Gainers', sort: 'DESC' }) },
})
const emit = defineEmits(['update:filters', 'open'])
const state = reactive({ range: props.filters.range, rank: props.filters.rank, sort: props.filters.sort })
watch(state, () => emit('update:filters', { ...state }))
const rangeOpts = [{ label: '24H', value: '24H' }, { label: '7D', value: '7D' }, { label: '30D', value: '30D' }]
const rankOpts  = [{ label: 'Gainers', value: 'Gainers' }, { label: 'Losers', value: 'Losers' }, { label: 'All', value: 'All' }]
const sortOpts  = [{ label: 'ASC', value: 'ASC' }, { label: 'DESC', value: 'DESC' }]
</script>
<template>
  <div class="bg-white border border-gray-200 rounded-xl">
    <div class="p-4 flex items-center justify-between gap-3 border-b border-gray-100">
      <div class="text-sm font-medium text-gray-800">Top Asset Summary</div>
      <div class="flex items-center gap-2">
        <Select v-model="state.range" :options="rangeOpts" />
        <Select v-model="state.rank"  :options="rankOpts" />
        <Select v-model="state.sort"  :options="sortOpts" />
      </div>
    </div>
    <div class="p-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <AssetMiniCard v-for="a in assets" :key="a.code" :icon="a.icon" :name="a.name" :code="a.code" :value="a.value" :symbol="a.symbol || '₦'" :deltaPct="a.deltaPct" @click="$emit('open', a.code)" />
    </div>
  </div>
</template>
