<script setup>
import { computed } from 'vue'
import Button from '@/Components/ui/Button.vue'

const props = defineProps({
  open: { type: Boolean, default: false },
  phase: { type: String, default: 'processing' }, // 'processing' | 'success' | 'error'
  title: { type: String, default: '' },           // optional override for success/error title
  message: { type: String, default: '' },         // optional extra copy
  details: { type: Array, default: () => [] },    // [{label,value}]
})
const emit = defineEmits(['close','primary','secondary'])
const isSuccess = computed(() => props.phase === 'success')
const isError   = computed(() => props.phase === 'error')
</script>

<template>
  <transition name="fade">
    <div v-if="open" class="fixed inset-0 z-50">
      <div class="absolute inset-0 bg-black/40 backdrop-blur-[1px]" @click="$emit('close')"></div>

      <div class="absolute inset-0 grid place-items-center p-4">
        <div class="w-full max-w-md rounded-2xl bg-white shadow-xl border border-gray-200 overflow-hidden">
          <!-- Processing -->
          <div v-if="phase==='processing'" class="px-6 py-8 text-center">
            <div class="mx-auto w-14 h-14 rounded-full border-2 border-gray-200 grid place-items-center mb-4">
              <div class="w-6 h-6 rounded-full border-2 border-primary border-t-transparent animate-spin"></div>
            </div>
            <h3 class="text-lg font-semibold text-gray-900">Processing payment…</h3>
            <p class="mt-1 text-sm text-gray-500">Hold on while we complete your purchase.</p>
          </div>

          <!-- Success -->
          <div v-else-if="isSuccess" class="px-6 py-6">
            <div class="mx-auto w-14 h-14 rounded-full bg-green-100 text-green-700 grid place-items-center mb-3">
              <svg class="w-7 h-7" viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.17 4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 text-center">{{ title || 'Payment successful' }}</h3>
            <p v-if="message" class="mt-1 text-sm text-gray-500 text-center">{{ message }}</p>

            <div v-if="details.length" class="mt-4 border-y divide-y">
              <div v-for="(d,i) in details" :key="i" class="flex items-center justify-between py-2 text-sm">
                <span class="text-gray-500">{{ d.label }}</span>
                <span class="font-medium text-gray-900">{{ d.value }}</span>
              </div>
            </div>

            <div class="mt-5 flex items-center gap-2">
              <Button class="flex-1" @click="$emit('primary')">Done</Button>
            </div>
          </div>

          <!-- Error -->
          <div v-else class="px-6 py-6">
            <div class="mx-auto w-14 h-14 rounded-full bg-red-100 text-red-700 grid place-items-center mb-3">
              <svg class="w-7 h-7" viewBox="0 0 24 24" fill="currentColor"><path d="M6 6l12 12M6 18L18 6"/></svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 text-center">{{ title || 'Payment failed' }}</h3>
            <p v-if="message" class="mt-1 text-sm text-gray-500 text-center">{{ message }}</p>

            <div v-if="details.length" class="mt-4 border-y divide-y">
              <div v-for="(d,i) in details" :key="i" class="flex items-center justify-between py-2 text-sm">
                <span class="text-gray-500">{{ d.label }}</span>
                <span class="font-medium text-gray-900">{{ d.value }}</span>
              </div>
            </div>

            <div class="mt-5 flex items-center gap-2">
              <Button variant="outline" class="flex-1" @click="$emit('secondary')">Try again</Button>
              <Button class="flex-1" @click="$emit('primary')">Close</Button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </transition>
</template>

<style>
.fade-enter-active,.fade-leave-active{transition:opacity .15s ease}
.fade-enter-from,.fade-leave-to{opacity:0}
</style>
