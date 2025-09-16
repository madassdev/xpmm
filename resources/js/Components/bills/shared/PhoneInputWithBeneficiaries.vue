<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { ChevronDown, Users, Search, Trash2, X } from 'lucide-vue-next'
import { useBeneficiaries } from '@/composables/useBeneficiaries'

const props = defineProps({
  modelValue: { type: String, default: '' },
  service: { type: String, default: '' },     // 'airtime' | 'data' | ...
  providerId: { type: [String, null], default: null },
  placeholder: { type: String, default: 'Enter Phone Number' },
})

const emit = defineEmits(['update:modelValue'])

const { list, remove } = useBeneficiaries()

const open = ref(false)
const q = ref('')
const root = ref(null)

const digitsPlus = (v) => v.replace(/[^\d+]/g, '')

const items = computed(() => {
  const arr = list({ service: props.service, kind: 'phone', providerId: props.providerId })
  if (!q.value) return arr
  const term = q.value.toLowerCase()
  return arr.filter((b) => (b.value + ' ' + b.label).toLowerCase().includes(term))
})

function pick(v) {
  emit('update:modelValue', v)
  open.value = false
}

function onDoc(e) {
  if (!root.value) return
  if (!root.value.contains(e.target)) open.value = false
}
onMounted(() => document.addEventListener('click', onDoc, true))
onBeforeUnmount(() => document.removeEventListener('click', onDoc, true))
</script>

<template>
  <div ref="root" class="relative">
    <!-- Soft phone field -->
    <div class="rounded-2xl bg-gray-50 border border-gray-200 px-3 py-2 flex items-center gap-3">
      <button type="button" class="flex items-center gap-2 px-3 py-2 rounded-xl bg-white/60 border border-gray-200">
        <span class="text-lg">🇳🇬</span>
        <ChevronDown class="w-4 h-4 text-gray-500" />
      </button>

      <input
        :value="modelValue"
        @input="e => emit('update:modelValue', digitsPlus(e.target.value))"
        type="tel"
        inputmode="numeric"
        :placeholder="placeholder"
        class="flex-1 bg-transparent px-2 py-3 placeholder-gray-400 text-gray-900 border-none ring-0 focus:ring-0 focus:border-0 focus:outline-none outline-none"
      />

      <!-- Beneficiaries button -->
      <button
        type="button"
        class="ml-1 w-9 h-9 grid place-items-center rounded-lg border border-gray-200 bg-white hover:bg-gray-50"
        @click="open = !open"
        title="Pick from beneficiaries"
      >
        <Users class="w-4 h-4 text-gray-600" />
      </button>
    </div>

    <!-- Popover -->
    <div
      v-if="open"
      class="absolute z-40 mt-2 w-full rounded-xl border border-gray-200 bg-white shadow-lg overflow-hidden"
    >
      <div class="p-2 border-b bg-gray-50 flex items-center gap-2">
        <Search class="w-4 h-4 text-gray-500" />
        <input
          v-model="q"
          type="text"
          placeholder="Search beneficiaries"
          class="flex-1 bg-transparent text-sm focus:outline-none"
        />
        <button class="w-7 h-7 grid place-items-center rounded-md hover:bg-gray-200/50" @click="open=false">
          <X class="w-4 h-4 text-gray-500" />
        </button>
      </div>

      <div class="max-h-64 overflow-auto">
        <div v-if="!items.length" class="px-4 py-8 text-sm text-gray-500 text-center">
          No beneficiaries yet.
        </div>

        <div
          v-for="b in items"
          :key="b.id"
          class="px-3 py-2 flex items-center justify-between hover:bg-gray-50"
        >
          <button class="text-left flex-1 pr-3" @click="pick(b.value)">
            <div class="text-sm text-gray-900 font-medium">{{ b.label }}</div>
            <div class="text-xs text-gray-500">{{ b.value }}</div>
          </button>
          <button
            class="w-8 h-8 grid place-items-center rounded-md hover:bg-red-50"
            title="Remove"
            @click="remove(b.id)"
          >
            <Trash2 class="w-4 h-4 text-red-500" />
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
