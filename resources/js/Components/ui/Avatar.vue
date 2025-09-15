<template>
  <div class="inline-flex items-center justify-center rounded-full bg-gray-200 text-gray-700 overflow-hidden"
       :class="sizeClass" :title="alt">
    <img v-if="src && !failed" :src="src" :alt="alt" class="w-full h-full object-cover" @error="onError" />
    <span v-else class="font-medium">{{ initials }}</span>
  </div>
</template>
<script setup>
import { ref, computed } from 'vue'
const props = defineProps({
  src: { type: String, default: '' },
  alt: { type: String, default: '' },
  name: { type: String, default: '' },
  size: { type: String, default: 'md' } // sm, md, lg
})
const failed = ref(false)
const onError = () => { failed.value = true }
const initials = computed(() => (props.name || props.alt || '?').split(' ').map(x => x?.[0] || '').slice(0,2).join('').toUpperCase())
const sizeClass = computed(() => ({ sm:'w-8 h-8 text-xs', md:'w-10 h-10 text-sm', lg:'w-12 h-12 text-base' }[props.size] || 'w-10 h-10 text-sm'))
</script>
