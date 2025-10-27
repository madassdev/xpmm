<script setup>
import GiftcardItem from './GiftcardItem.vue'

const props = defineProps({
  cards:  { type: Array,  default: () => [] },
  loading:{ type: Boolean, default: false },
  mode:   { type: String, default: 'sell' }, // 'sell' | 'buy'
})
const emit = defineEmits(['sell', 'buy'])
</script>

<template>
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
    <!-- skeletons -->
    <template v-if="loading">
      <div v-for="i in 8" :key="i" class="h-44 rounded-2xl bg-white border border-gray-200 p-3">
        <div class="h-full rounded-xl bg-gray-100 animate-pulse"></div>
      </div>
    </template>

    <!-- Empty state -->
    <div v-else-if="!loading && cards.length === 0" class="col-span-full">
      <div class="rounded-2xl bg-white border border-gray-200 p-10 text-center text-gray-500">
        <p>No giftcards available.</p>
      </div>
    </div>

    <!-- cards -->
    <GiftcardItem
      v-for="card in cards"
      v-else
      :key="card.id"
      :card="card"
      :mode="mode"
      @sell="emit('sell', card)"
      @buy="emit('buy', card)"
    />
  </div>
</template>
