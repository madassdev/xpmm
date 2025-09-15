<template>
  <div class="relative inline-block" ref="root">
    <button
      type="button"
      class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-800 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40"
      @click="toggle"
    >
      <slot name="trigger">{{ label }}</slot>
    </button>
    <transition name="fade">
      <div
        v-if="open"
        class="absolute z-40 mt-2 min-w-[10rem] rounded-lg border border-gray-200 bg-white shadow-lg p-1"
        :class="alignClass"
        role="menu"
        @keydown.esc="close"
      >
        <slot>
          <button v-for="item in items" :key="item.value"
                  @click="select(item)"
                  class="w-full text-left px-3 py-2 rounded-md text-sm hover:bg-gray-100">
            {{ item.label }}
          </button>
        </slot>
      </div>
    </transition>
  </div>
</template>
<script setup>
import { ref, onMounted, onBeforeUnmount, computed } from 'vue'
const props = defineProps({
  label: { type: String, default: 'Options' },
  items: { type: Array, default: () => [] },
  align: { type: String, default: 'left' }
})
const emit = defineEmits(['select', 'open', 'close'])
const open = ref(false)
const root = ref(null)
const alignClass = computed(() => props.align === 'right' ? 'right-0' : 'left-0')
const toggle = () => { open.value = !open.value; emit(open.value ? 'open' : 'close') }
const close = () => { if (open.value) { open.value = false; emit('close') } }
const onDoc = (e) => { if (!root.value) return; if (!root.value.contains(e.target)) close() }
onMounted(() => document.addEventListener('click', onDoc, true))
onBeforeUnmount(() => document.removeEventListener('click', onDoc, true))
const select = (item) => { emit('select', item); close() }
</script>
<style>
.fade-enter-active,.fade-leave-active{transition:opacity .12s ease}
.fade-enter-from,.fade-leave-to{opacity:0}
</style>
