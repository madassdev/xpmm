<template>
  <ul class="space-y-2">
    <li v-for="item in items" :key="item.code" class="flex items-center justify-between text-sm">
      <div class="flex items-center gap-2">
        <span class="inline-block w-3 h-3 rounded-sm" :style="{ backgroundColor: item.color }" />
        <div class="text-gray-700">{{ item.label }}</div>
      </div>
      <div class="text-gray-500">{{ item.pct }}%</div>
    </li>
  </ul>
</template>
<script setup>
const props = defineProps({ items: { type: Array, default: () => [] } })
</script>
