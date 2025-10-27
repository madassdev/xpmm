<script setup>
import { ShoppingBag, ArrowUpRight } from 'lucide-vue-next'
import Card from '@/Components/ui/Card.vue'

const props = defineProps({
  card: { type: Object, required: true }, // { id, name, logo, rate }
  mode: { type: String, default: 'sell' }, // 'sell' | 'buy'
})
const emit = defineEmits(['sell', 'buy'])

function handleClick() {
  if (props.mode === 'buy') {
    emit('buy', props.card)
  } else {
    emit('sell', props.card)
  }
}
</script>

<template>
  <Card class="p-3 hover:shadow-md transition cursor-pointer group" @click="handleClick">
    <div class="aspect-[16/9] rounded-xl overflow-hidden bg-gray-100 grid place-items-center">
      <img 
        :src="card.logo || card.image_url || '/img/giftcard-placeholder.png'" 
        :alt="card.name" 
        class="object-contain max-h-full group-hover:scale-105 transition-transform" 
      />
    </div>
    <div class="mt-2 flex items-center justify-between">
      <div class="flex-1 min-w-0">
        <p class="text-sm font-medium text-gray-700 truncate">{{ card.name }}</p>
        <p v-if="card.rate" class="text-xs text-gray-500 mt-0.5">
          Rate: {{ card.rate }}%
        </p>
      </div>
      <span 
        class="shrink-0 inline-flex items-center justify-center w-6 h-6 rounded-full transition-colors"
        :class="mode === 'buy' ? 'bg-green-100 text-green-600 group-hover:bg-green-200' : 'bg-orange-100 text-orange-600 group-hover:bg-orange-200'"
      >
        <ShoppingBag v-if="mode === 'buy'" class="w-4 h-4" />
        <ArrowUpRight v-else class="w-4 h-4" />
      </span>
    </div>
  </Card>
</template>
