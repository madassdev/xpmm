<script setup>
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import HeroBalanceCard from '@/Components/composite/HeroBalanceCard.vue'
import TotalAssetsCard from '@/Components/composite/TotalAssetsCard.vue'
import TopAssetSummary from '@/Components/composite/TopAssetSummary.vue'
import RecentTransactions from '@/Components/composite/RecentTransactions.vue'
import MyCardsPanel from '@/Components/composite/MyCardsPanel.vue'
import { getDashboardNavSections } from '@/constants/dashboardNav'

const props = defineProps({
  giftcardTransactions: { type: Array, default: () => [] },
})

const navSections = getDashboardNavSections()

// Mock data
const assets = [
  { code: 'BTC', name: 'Bitcoin', value: 0, deltaPct: +0.81, icon: null, symbol: '₦' },
  { code: 'ETH', name: 'Ethereum', value: 0, deltaPct: -0.20, icon: null, symbol: '₦' },
  { code: 'USDT', name: 'Tether', value: 0, deltaPct: 0.00, icon: null, symbol: '₦' },
]
const filters = { range: '24H', rank: 'Gainers', sort: 'DESC' }
const cards = []

const txns = computed(() => props.giftcardTransactions ?? [])

const viewGiftcardTransactions = () => {
  const href = typeof route === 'function' ? route('giftcards.index') : '/gift-cards'
  router.visit(href)
}

const emptyState = {
  title: 'No giftcard transactions yet',
  subtitle: 'Your giftcard trades will appear here as soon as you complete one.',
}
</script>

<template>
  <AppLayout title="Overview" :navSections="navSections" activeKey="overview" :user="{ name: 'Welcome, Muztirs', avatar: '' }">
    <div class="grid grid-cols-12 gap-6">
      <div class="col-span-12">
        <HeroBalanceCard :balance="0" currency="NGN" symbol="₦" />
      </div>

      <div class="col-span-12 lg:col-span-4">
        <TotalAssetsCard
          :total="0"
          :monthPct="0"
          :monthDeltaFiat="0"
          :distribution="[
            { label: 'BTC', value: 0, color: '#17B1A3' },
            { label: 'ETH', value: 0, color: '#F59E0B' },
            { label: 'USDT', value: 0, color: '#DC2626' },
          ]"
          :legend="[
            { code: 'BTC', label: 'BTC', color: '#17B1A3', pct: 0, fiat: 0 },
            { code: 'ETH', label: 'ETH', color: '#F59E0B', pct: 0, fiat: 0 },
            { code: 'USDT', label: 'USDT', color: '#DC2626', pct: 0, fiat: 0 },
          ]"
          symbol="₦"
        />
      </div>

      <div class="col-span-12 lg:col-span-8">
        <TopAssetSummary :assets="assets" :filters="filters" @update:filters="f => console.log('filters', f)" />
      </div>

      <div class="col-span-12 lg:col-span-8">
        <RecentTransactions
          :rows="txns"
          :empty="emptyState"
          @viewAll="viewGiftcardTransactions"
        />
      </div>
      <div class="col-span-12 lg:col-span-4">
        <MyCardsPanel :cards="cards" @create="() => console.log('create card')" />
      </div>
    </div>
  </AppLayout>
</template>
