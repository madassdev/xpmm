<template>
  <div class="space-y-1.5">
    <div class="text-xs text-gray-500">{{ label }}</div>
    <div class="flex items-baseline gap-2">
      <div class="text-2xl font-bold text-gray-900">
        <slot name="value">{{ value }}</slot>
      </div>
      <Chip v-if="delta !== undefined" :color="deltaColor">
        <slot name="delta-prefix" />
        {{ deltaText }}
      </Chip>
    </div>
    <div v-if="helpText" class="text-xs text-gray-400">{{ helpText }}</div>
  </div>
</template>
<script setup>
import { computed } from 'vue'
import Chip from './Chip.vue'
const props = defineProps({
  label: String,
  value: [String, Number],
  delta: { type: Number, default: undefined },
  helpText: String
})
const deltaColor = computed(() => props.delta > 0 ? 'success' : props.delta < 0 ? 'danger' : 'neutral')
const deltaText = computed(() => props.delta !== undefined ? `${props.delta > 0 ? '↑' : props.delta < 0 ? '↓' : ''} ${Math.abs(props.delta)}%` : '')
</script>
