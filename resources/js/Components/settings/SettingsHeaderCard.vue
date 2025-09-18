<script setup>
import { CalendarDays } from 'lucide-vue-next'

defineProps({
  name: { type: String, default: 'Mustapha!' },
  verified: { type: Boolean, default: false },
  balance: { type: Number, default: 0 },
  currency: { type: String, default: 'NGN' },
  symbol: { type: String, default: '₦' },
})
const today = new Date()
const dateLabel = new Intl.DateTimeFormat('en-GB', { day: '2-digit', month: 'long', year: 'numeric' }).format(today)
</script>

<template>
  <div class="bg-white rounded-2xl border border-gray-200 p-4 sm:p-5 flex items-center justify-between">
    <div class="flex items-center gap-3">
      <CalendarDays class="w-5 h-5 text-gray-500" />
      <span class="text-sm text-gray-600">{{ dateLabel }}</span>
      <span
        class="ml-3 inline-flex items-center text-xs font-semibold rounded-full px-2.5 py-1"
        :class="verified ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700'"
      >
        {{ verified ? 'Verified' : 'Not Verified' }}
      </span>
    </div>

    <div class="flex items-center gap-3">
      <div class="text-right">
        <div class="text-xs text-gray-500">Wallet Balance:</div>
        <div class="font-semibold text-gray-900">{{ symbol }}{{ (balance || 0).toLocaleString() }}</div>
      </div>
      <div class="w-11 h-11 rounded-xl bg-teal-50 grid place-items-center border border-teal-100">
        <!-- placeholder wallet icon -->
        <span class="text-teal-600 font-bold">💳</span>
      </div>
    </div>
  </div>
</template>
