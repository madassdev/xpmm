<script setup>
import { ref, computed, watch, nextTick, toRefs, defineExpose } from 'vue'

const props = defineProps({
  modelValue: { type: String, default: '' },
  length: { type: Number, default: 4 },
  mask: { type: Boolean, default: true },     // password masking
  disabled: { type: Boolean, default: false },
  autoFocus: { type: Boolean, default: true },
  inputClass: { type: String, default: '' },  // optional tailwind overrides for each box
  name: { type: String, default: 'pin' },     // base name for inputs (useful for forms)
  ariaLabel: { type: String, default: 'PIN digit' },
})

const emit = defineEmits(['update:modelValue', 'change', 'complete'])

const boxes = computed(() => Array.from({ length: props.length }))
const digits = ref(Array(props.length).fill(''))
const inputs = ref([])

function setInputRef(el, i) { if (el) inputs.value[i] = el }

function onlyDigits(v) { return (v || '').replace(/[^\d]/g, '') }

function syncFromModel(v) {
  const clean = onlyDigits(v).slice(0, props.length)
  const arr = Array(props.length).fill('')
  for (let i = 0; i < clean.length; i++) arr[i] = clean[i]
  digits.value = arr
}

watch(() => props.modelValue, (v) => syncFromModel(v), { immediate: true })
watch(digits, (arr) => {
  const value = arr.join('')
  emit('update:modelValue', value)
  emit('change', value)
  if (value.length === props.length) emit('complete', value)
}, { deep: true })

function focusBox(i) { nextTick(() => inputs.value[i]?.focus() ) }

function onInput(i, e) {
  if (props.disabled) return
  const val = onlyDigits(e.target.value).slice(-1)
  digits.value[i] = val
  if (val && i < props.length - 1) focusBox(i + 1)
}

function onKeydown(i, e) {
  if (props.disabled) return
  if (e.key === 'Backspace') {
    if (digits.value[i]) {
      digits.value[i] = ''
    } else if (i > 0) {
      focusBox(i - 1)
      digits.value[i - 1] = ''
    }
    e.preventDefault()
  } else if (e.key === 'ArrowLeft' && i > 0) {
    focusBox(i - 1); e.preventDefault()
  } else if (e.key === 'ArrowRight' && i < props.length - 1) {
    focusBox(i + 1); e.preventDefault()
  } else if (e.key === 'Enter') {
    if (digits.value.join('').length === props.length) emit('complete', digits.value.join(''))
  }
}

function onPaste(e) {
  if (props.disabled) return
  const txt = onlyDigits(e.clipboardData?.getData('text') || '')
  if (!txt) return
  e.preventDefault()
  for (let i = 0; i < props.length; i++) digits.value[i] = txt[i] || ''
  const nextEmpty = digits.value.findIndex(d => !d)
  focusBox(nextEmpty === -1 ? props.length - 1 : nextEmpty)
}

function focus() { focusBox(0) }
function clear() { digits.value = Array(props.length).fill(''); focusBox(0) }
defineExpose({ focus, clear })
</script>

<template>
  <div class="grid grid-cols-4 gap-3" :style="{ gridTemplateColumns: `repeat(${length}, minmax(0, 1fr))` }">
    <input
      v-for="(_, i) in boxes"
      :key="i"
      :ref="el => setInputRef(el, i)"
      :type="mask ? 'password' : 'text'"
      inputmode="numeric"
      pattern="[0-9]*"
      maxlength="1"
      :value="digits[i]"
      :name="`${name}-${i+1}`"
      :aria-label="ariaLabel"
      :disabled="disabled"
      @input="e => onInput(i, e)"
      @keydown="e => onKeydown(i, e)"
      @paste="onPaste"
      class="h-12 text-center text-xl tracking-widest rounded-xl border border-gray-300 bg-white
             focus:outline-none focus:ring-2 focus:ring-primary/30 disabled:opacity-60 disabled:cursor-not-allowed
             select-none"
      :class="inputClass"
    />
  </div>
</template>
