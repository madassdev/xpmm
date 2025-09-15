<script setup>
defineProps({
  icon: { type: String, default: '/_placeholder_coin.svg' },
  name: { type: String, required: true },
  code: { type: String, default: 'BTC' },
  balance: { type: String, default: '$0.00' },
  small: { type: String, default: '0.00' },
  price: { type: String, default: '$0.00' },
  change: { type: String, default: '+0.00' },
  positive: { type: Boolean, default: true }
})
</script>

<template>
  <div class="bg-white rounded-lg border p-4 flex items-center justify-between">
    <div class="flex items-center gap-3">
      <img :src="icon" alt="" class="w-10 h-10 rounded-full" />
      <div>
        <div class="font-semibold text-gray-800">{{ name }}</div>
        <div class="text-xs text-gray-400">{{ code }}</div>
      </div>
    </div>
    <div class="text-right">
      <div class="font-semibold text-gray-800">{{ balance }}</div>
      <div class="text-xs text-gray-400">{{ small }}</div>
      <div :class="[positive ? 'text-green-500' : 'text-red-500', 'text-sm font-medium']">{{ price }} · {{ change }}</div>
    </div>
  </div>
</template>
