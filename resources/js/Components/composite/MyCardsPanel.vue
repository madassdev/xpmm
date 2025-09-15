<script setup>
import { computed } from 'vue'
import Card from '@/Components/ui/Card.vue'
import EmptyState from '@/Components/ui/EmptyState.vue'
import Button from '@/Components/ui/Button.vue'
const props = defineProps({ cards: { type: Array, default: () => [] } })
const emit = defineEmits(['create'])
const hasCards = computed(() => props.cards.length > 0)
</script>
<template>
  <Card>
    <template v-if="!hasCards">
      <div class="py-10">
        <EmptyState title="No Cards Yet" subtitle="You don't have any cards yet. Create your first virtual card to get started.">
          <template #action>
            <Button @click="$emit('create')">Create Virtual Card</Button>
          </template>
        </EmptyState>
      </div>
    </template>
    <template v-else>
      <div class="space-y-3">
        <div v-for="c in cards" :key="c.last4" class="flex items-center justify-between border rounded-lg p-3">
          <div class="flex items-center gap-3">
            <div class="w-10 h-7 rounded bg-gray-900"></div>
            <div>
              <div class="text-sm font-medium text-gray-900">{{ c.brand }} •••• {{ c.last4 }}</div>
              <div class="text-xs text-gray-500">Card</div>
            </div>
          </div>
          <div class="text-right text-sm text-gray-700">{{ c.currency || '₦' }}{{ c.balance ?? 0 }}</div>
        </div>
      </div>
    </template>
  </Card>
</template>
