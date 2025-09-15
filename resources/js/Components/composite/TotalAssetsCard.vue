<script setup>
import Card from '@/Components/ui/Card.vue'
import Chip from '@/Components/ui/Chip.vue'
import SegmentedProgress from '@/Components/ui/SegmentedProgress.vue'

const props = defineProps({
  total: { type: Number, default: 0 },
  symbol: { type: String, default: '₦' },
  monthPct: { type: Number, default: 0 },
  monthDeltaFiat: { type: Number, default: 0 },
  distribution: {
    type: Array,
    default: () => ([
      { label: 'BTC',  value: 0, color: '#17B1A3' },
      { label: 'ETH',  value: 0, color: '#F59E0B' },
      { label: 'USDT', value: 0, color: '#DC2626' },
    ]),
  },
  legend: {
    type: Array,
    default: () => ([
      { code: 'BTC', label: 'BTC', color: '#17B1A3', pct: 0, fiat: 0 },
      { code: 'ETH', label: 'ETH', color: '#F59E0B', pct: 0, fiat: 0 },
      { code: 'USDT',label: 'USDT',color: '#DC2626', pct: 0, fiat: 0 },
    ]),
  },
})
const fmt = (n) => new Intl.NumberFormat('en-NG', { maximumFractionDigits: 2 }).format(n)
</script>

<template>
  <Card as="section">
    <div class="p-5 border-b border-gray-100">
      <div class="text-sm text-gray-500">Total Assets</div>
      <div class="mt-1 text-2xl font-bold text-gray-900">{{ symbol }}{{ fmt(total) }}</div>
      <div class="mt-3 flex items-center gap-2">
        <Chip color="warning">{{ monthPct }}%</Chip>
        <Chip color="neutral">+{{ symbol }}{{ fmt(monthDeltaFiat) }} <span class="ml-1">This month</span></Chip>
      </div>
    </div>
    <div class="p-5">
      <div class="text-sm font-medium text-gray-700 mb-2">Distribution</div>
      <SegmentedProgress :segments="distribution" />
      <div class="mt-4 space-y-2">
        <div v-for="item in legend" :key="item.code" class="grid grid-cols-12 items-center text-sm">
          <div class="col-span-6 flex items-center gap-2">
            <span class="inline-block w-3 h-3 rounded-sm" :style="{ backgroundColor: item.color }" />
            <span class="text-gray-700">{{ item.label }}</span>
            <span class="text-gray-400 text-xs">{{ item.pct }}%</span>
          </div>
          <div class="col-span-6 text-right text-gray-700">{{ symbol }}{{ fmt(item.fiat) }}</div>
        </div>
      </div>
    </div>
  </Card>
</template>
