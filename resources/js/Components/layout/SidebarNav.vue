<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import * as Lucide from 'lucide-vue-next'

const props = defineProps({
  sections: { type: Array, required: true }, // [{label, items:[{key,label,href,icon}]}]
  activeKey: { type: String, default: '' },
})
const emit = defineEmits(['navigate'])

const isActive = (key) => key === props.activeKey

const itemClass = (active) =>
  [
    'w-full flex items-center gap-3 px-3 py-2 rounded-lg transition-colors',
    active
      ? 'bg-primary/10 text-primary'
      : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900',
  ]
</script>

<template>
  <nav class="space-y-6">
    <!-- Brand -->
    <div class="flex items-center gap-2 px-2">
      <div class="w-8 h-8 rounded-md bg-primary text-white grid place-items-center font-bold">X</div>
      <div class="text-sm font-semibold tracking-wide">XPMEXCHANGE</div>
    </div>

    <div v-for="section in sections" :key="section.label" class="space-y-2">
      <div class="px-2 text-xs uppercase tracking-wide text-gray-400">{{ section.label }}</div>

      <div class="space-y-1">
        <Link
          v-for="item in section.items"
          :key="item.key"
          :href="item.href"
          :aria-current="isActive(item.key) ? 'page' : undefined"
          :class="itemClass(isActive(item.key))"
          @click="emit('navigate')"
        >
          <component
            :is="Lucide[item.icon] || Lucide.Circle"
            class="w-5 h-5"
          />
          <span class="font-medium">{{ item.label }}</span>
        </Link>
      </div>
    </div>

    <!-- Footer (optional slot) -->
    <slot />
  </nav>
</template>
