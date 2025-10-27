<template>
  <BasePage>
    <div class="grid gap-4 sm:grid-cols-2">
      <Link
        v-for="item in shortcutLinks"
        :key="item.label"
        :href="route(item.route)"
        class="group rounded-2xl border border-gray-200 bg-white p-5 shadow-sm transition hover:border-primary/40 hover:shadow-md"
      >
        <div class="flex items-center gap-3">
          <div class="rounded-2xl bg-orange-50 p-2 text-orange-600">
            <component :is="item.icon" class="h-5 w-5" />
          </div>
          <div>
            <p class="text-base font-semibold text-gray-900">{{ item.label }}</p>
            <p class="text-sm text-gray-500">{{ item.description }}</p>
          </div>
        </div>
      </Link>
    </div>
  </BasePage>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import BasePage from '@/Pages/Admin/Partials/BasePage.vue'
import { Gift, Receipt, ArrowRightLeft, CreditCard } from 'lucide-vue-next'

const shortcutLinks = [
  {
    label: 'Giftcard Transactions',
    description: 'Review buy & sell orders, images, and settlement notes.',
    icon: Gift,
    route: 'admin.giftcard-transactions.index',
  },
  {
    label: 'Bills & Utilities',
    description: 'Jump into airtime, data, and power fulfilment queues.',
    icon: Receipt,
    route: 'admin.bills',
  },
  {
    label: 'Crypto & Wallet Flows',
    description: 'Monitor crypto wallets, swaps, and payout requests.',
    icon: ArrowRightLeft,
    route: 'admin.crypto-wallets',
  },
  {
    label: 'Card Transactions',
    description: 'Track card loads, settlements, and disputes.',
    icon: CreditCard,
    route: 'admin.cards',
  },
]
</script>
