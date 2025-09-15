<script setup>
import Table from '@/Components/ui/Table.vue'
import DropdownMenu from '@/Components/ui/DropdownMenu.vue'
import Button from '@/Components/ui/Button.vue'
const props = defineProps({
  rows: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  symbol: { type: String, default: '₦' },
  empty: { type: Object, default: () => ({ title: 'No Recent Transactions', subtitle: 'You\'t made any transactions yet.' }) }
})
const emit = defineEmits(['filter', 'sort', 'viewAll'])
const columns = [
  { key: 'createdAt', header: 'Date', sortable: true },
  { key: 'type',      header: 'Type', sortable: true },
  { key: 'amount',    header: 'Amount', align: 'right', sortable: true },
  { key: 'status',    header: 'Status' },
  { key: 'method',    header: 'Method' },
]
const filterItems = [{ label: 'All', value: 'all' }, { label: 'Deposits', value: 'deposit' }, { label: 'Withdrawals', value: 'withdraw' }, { label: 'Trades', value: 'trade' }]
const sortItems   = [{ label: 'Newest', value: 'desc' }, { label: 'Oldest', value: 'asc' }, { label: 'Amount High → Low', value: 'amount_desc' }, { label: 'Amount Low → High', value: 'amount_asc' }]
const fmt = (n) => new Intl.NumberFormat('en-NG', { maximumFractionDigits: 2 }).format(n)
</script>
<template>
  <div class="bg-white border border-gray-200 rounded-xl">
    <div class="p-4 flex items-center justify-between gap-3 border-b border-gray-100">
      <div class="text-sm font-medium text-gray-800">Recent Transactions</div>
      <div class="flex items-center gap-2">
        <DropdownMenu label="Filter" :items="filterItems" @select="(i) => $emit('filter', i.value)" />
        <DropdownMenu label="Sort"   :items="sortItems"   @select="(i) => $emit('sort', i.value)" />
        <Button size="sm" variant="outline" @click="$emit('viewAll')">View All</Button>
      </div>
    </div>
    <div class="p-4">
      <Table :columns="columns" :rows="rows" :loading="loading" :empty="empty">
        <template #cell:amount="{ value }">
          <span class="font-medium text-gray-900">{{ symbol }}{{ fmt(value) }}</span>
        </template>
        <template #cell:status="{ value }">
          <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                :class="{ 'bg-green-100 text-green-700': value === 'success', 'bg-yellow-100 text-yellow-800': value === 'pending', 'bg-red-100 text-red-700': value === 'failed' }">
            {{ value }}
          </span>
        </template>
      </Table>
    </div>
  </div>
</template>
