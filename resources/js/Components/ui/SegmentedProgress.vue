<template>
  <div>
    <div class="h-2 w-full rounded-full bg-gray-100 overflow-hidden">
      <div v-for="(seg, i) in normalized" :key="i"
           class="h-full inline-block"
           :style="{ width: seg.width + '%', backgroundColor: seg.color }" />
    </div>
    <div v-if="showLabels" class="mt-2 grid grid-cols-2 gap-2">
      <div v-for="(seg, i) in segments" :key="'l'+i" class="flex items-center gap-2 text-sm">
        <span class="inline-block w-3 h-3 rounded-sm" :style="{ backgroundColor: seg.color }" />
        <span class="text-gray-700">{{ seg.label }}</span>
        <span class="text-gray-500 text-xs">{{ seg.value }}%</span>
      </div>
    </div>
  </div>
</template>
<script setup>
import { computed } from 'vue'
const props = defineProps({
  segments: { type: Array, default: () => [] },
  showLabels: { type: Boolean, default: false }
})
const normalized = computed(() => {
  const total = props.segments.reduce((s, x) => s + (Number(x.value) || 0), 0) || 1
  return props.segments.map(s => ({ ...s, width: (Number(s.value) || 0) / total * 100 }))
})
</script>
