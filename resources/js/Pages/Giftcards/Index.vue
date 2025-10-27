<script setup>
import { ref, computed, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import GiftcardToolbar from '@/Components/giftcards/GiftcardToolbar.vue'
import GiftcardGrid from '@/Components/giftcards/GiftcardGrid.vue'
import TradeForm from '@/Components/giftcards/TradeForm.vue'
import UserTradesTable from '@/Components/giftcards/UserTradesTable.vue'
import { useGiftcards } from '@/composables/useGiftcards'

const props = defineProps({
  transactions: { type: Array, default: () => [] },
})

// state
const activeTab = ref('sell') // 'sell' | 'buy' | 'trades'
const search = ref('')
const { loading, items, error, fetchGiftcards } = useGiftcards()

// Selected card and form state
const selectedCard = ref(null)
const showForm = ref(false)
const successMessage = ref('')

function openTrade(card, mode) {
  selectedCard.value = card
  activeTab.value = mode
  showForm.value = true
  successMessage.value = ''
}

function handleSuccess(data) {
  showForm.value = false
  selectedCard.value = null
  successMessage.value = data?.message || 'Transaction submitted successfully!'
  refreshTrades()
  setTimeout(() => {
    successMessage.value = ''
  }, 5000)
}

function handleCancel() {
  showForm.value = false
  selectedCard.value = null
}

onMounted(() => fetchGiftcards())

const trades = computed(() => props.transactions ?? [])

const filtered = computed(() => {
  const q = search.value.trim().toLowerCase()
  if (!q) return items.value
  return items.value.filter(i => i.name.toLowerCase().includes(q))
})

function refreshTrades() {
  router.reload({ only: ['transactions'] })
}
</script>

<template>
  <AppLayout title="Giftcards" :navSections="[]" activeKey="giftcards" :user="{ name: 'Welcome, Muztirs', avatar: '' }">
    <div class="space-y-4">
      <GiftcardToolbar v-model:tab="activeTab" v-model:search="search"
                       @refresh="fetchGiftcards" />

      <!-- Success message -->
      <div v-if="successMessage" class="rounded-2xl bg-green-50 border border-green-200 p-4">
        <p class="text-sm text-green-600">{{ successMessage }}</p>
      </div>

      <!-- Error message -->
      <div v-if="error" class="rounded-2xl bg-red-50 border border-red-200 p-4">
        <p class="text-sm text-red-600">{{ error }}</p>
      </div>

      <!-- Trade Form -->
      <TradeForm
        v-if="showForm && selectedCard"
        :card="selectedCard"
        :mode="activeTab"
        @success="handleSuccess"
        @cancel="handleCancel"
      />

      <!-- Giftcard Grid -->
      <GiftcardGrid
        v-else-if="activeTab !== 'trades'"
        :cards="filtered"
        :loading="loading"
        :mode="activeTab"
        @sell="(card) => openTrade(card, 'sell')"
        @buy="(card) => openTrade(card, 'buy')"
      />

      <!-- Trades -->
      <UserTradesTable v-else :trades="trades" />
    </div>
  </AppLayout>
</template>
