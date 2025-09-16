<script setup>
import * as Lucide from 'lucide-vue-next'

const props = defineProps({
  /** which tile is active */
  active: { type: String, default: 'airtime' },
  /** allow clicking to switch (for future use) */
  clickable: { type: Boolean, default: true },
})
const emit = defineEmits(['select'])

/** order and icons match the design */
const items = [
  { key: 'airtime',     label: 'Airtime',        icon: 'Phone' },
  { key: 'data',        label: 'Mobile Data',    icon: 'Wifi' },
  { key: 'internet',    label: 'Internet',       icon: 'Globe' },
  { key: 'electricity', label: 'Electricity',    icon: 'Zap' },
  { key: 'tv',          label: 'Tv Bills',       icon: 'Tv' },
  { key: 'betting',     label: 'Betting Top-up', icon: 'BadgeDollarSign' },
]

/** per-service active accent; tweak to your token if desired */
const activeClasses = (key) => ({
  airtime:     'bg-teal-100 text-teal-700 ring-1 ring-teal-200',
  data:        'bg-sky-100 text-sky-700 ring-1 ring-sky-200',
  internet:    'bg-amber-100 text-amber-700 ring-1 ring-amber-200',
  electricity: 'bg-purple-100 text-purple-700 ring-1 ring-purple-200',
  tv:          'bg-indigo-100 text-indigo-700 ring-1 ring-indigo-200',
  betting:     'bg-pink-100 text-pink-700 ring-1 ring-pink-200',
}[key] ?? 'bg-primary/10 text-primary ring-1 ring-primary/20')
</script>

<template>
  <div class="space-y-4">
    <button
      v-for="it in items"
      :key="it.key"
      type="button"
      class="w-full rounded-2xl border px-4 py-4 flex items-center gap-3 select-none transition-colors"
      :class="[
        it.key === active
          ? activeClasses(it.key) + ' border-transparent'
          : 'bg-white text-gray-400 border-gray-200 hover:bg-gray-50 hover:text-gray-700 hover:border-gray-300 cursor-pointer'
      ]"
      @click="clickable && emit('select', it.key)"
      :aria-current="it.key === active ? 'page' : undefined"
    >
      <component :is="Lucide[it.icon] || Lucide.Square" class="w-5 h-5" />
      <span class="font-medium">{{ it.label }}</span>
    </button>
  </div>
</template>
