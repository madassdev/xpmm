<script setup>
import { computed } from 'vue'
import Card from '@/Components/ui/Card.vue'
import Chip from '@/Components/ui/Chip.vue'
const props = defineProps({
  icon: { type: [String, Object], default: null },
  name: { type: String, required: true },
  code: { type: String, required: true },
  value: { type: Number, default: 0 },
  symbol: { type: String, default: '₦' },
  deltaPct: { type: Number, default: 0 },
})
const fmt = (n) => new Intl.NumberFormat('en-NG', { maximumFractionDigits: 2 }).format(n)
const isUp = computed(() => props.deltaPct > 0)
</script>
<template>
  <Card padding="sm">
    <div class="flex items-start justify-between">
      <div class="flex items-center gap-3">
        <div class="w-9 h-9 rounded-full bg-gray-100 grid place-items-center overflow-hidden">
          <component v-if="typeof icon === 'object' && icon" :is="icon" class="w-5 h-5 text-gray-700" />
          <img v-else-if="typeof icon === 'string' && icon" :src="icon" :alt="name" class="w-9 h-9 object-contain" />
          <span v-else class="text-xs text-gray-500">{{ code }}</span>
        </div>
        <div>
          <div class="text-gray-900 font-medium leading-tight">{{ name }}</div>
          <div class="text-xs text-gray-500">{{ code }}</div>
        </div>
      </div>
      <Chip :color="isUp ? 'success' : deltaPct < 0 ? 'danger' : 'neutral'">{{ isUp ? '+' : '' }}{{ deltaPct }}%</Chip>
    </div>
    <div class="mt-3 text-lg font-semibold text-gray-900">{{ symbol }}{{ fmt(value) }}</div>
  </Card>
</template>
