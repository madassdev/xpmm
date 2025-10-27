<script setup>
import { computed } from 'vue'
import * as Lucide from 'lucide-vue-next'

const props = defineProps({
  title: { type: String, required: true },
  description: { type: String, default: '' },
  icon: { type: String, default: 'Sparkles' },
})

const iconComponent = computed(() => Lucide[props.icon] || Lucide.Sparkles)
</script>

<template>
  <div class="rounded-2xl border border-dashed border-gray-200 bg-white px-6 py-10 text-center shadow-sm shadow-gray-100/40">
    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-orange-50 text-orange-600">
      <component :is="iconComponent" class="h-6 w-6" />
    </div>
    <h2 class="mt-4 text-2xl font-semibold text-gray-900">{{ title }}</h2>
    <p v-if="description" class="mt-2 text-gray-500 max-w-xl mx-auto">
      {{ description }}
    </p>

    <slot />
  </div>
</template>
