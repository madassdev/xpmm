<template>
  <aside class="w-48 bg-white border-r min-h-screen flex flex-col">
    <div class="p-6 flex items-center space-x-3">
      <div class="w-8 h-8 rounded-md bg-orange-500 flex items-center justify-center text-white font-bold">X</div>
      <div class="text-sm font-semibold">XPMEXCHANGE</div>
    </div>

    <nav class="px-4 py-6 space-y-2 flex-1">
      <a href="#" class="flex items-center gap-3 px-4 py-3 rounded-lg bg-orange-50 text-orange-600">
        <House class="w-5 h-5" />
        <span>Overview</span>
      </a>

      <a href="/bills" class="flex items-center gap-3 px-4 py-3 rounded-lg text-gray-600 hover:bg-gray-100">
        <Gift class="w-5 h-5" />
        <span>Bills</span>
      </a>

      <a href="#" class="flex items-center gap-3 px-4 py-3 rounded-lg text-gray-600 hover:bg-gray-100">
        <Gift class="w-5 h-5" />
        <span>Giftcards</span>
      </a>

      <a href="#" class="flex items-center gap-3 px-4 py-3 rounded-lg text-gray-600 hover:bg-gray-100">
        <Wallet class="w-5 h-5" />
        <span>Wallets</span>
      </a>

      <a href="#" class="flex items-center gap-3 px-4 py-3 rounded-lg text-gray-600 hover:bg-gray-100">
        <CreditCard class="w-5 h-5" />
        <span>Cards</span>
      </a>

      <a href="#" class="flex items-center gap-3 px-4 py-3 rounded-lg text-gray-600 hover:bg-gray-100">
        <Network class="w-5 h-5" />
        <span>Referrals</span>
      </a>

      <a href="#" class="flex items-center gap-3 px-4 py-3 rounded-lg text-gray-600 hover:bg-gray-100">
        <ArrowRightLeft class="w-5 h-5" />
        <span>Transactions</span>
      </a>
    </nav>

    <div class="p-4 border-t">
      <a href="#" class="flex items-center gap-3 text-gray-600 hover:text-red-600">
        <LogOut />
        <span>Sign Out</span>
      </a>
    </div>
  </aside>
</template>

<script setup>
import { ArrowRightLeft, CreditCard, Gift, House, LogOut, Network, Wallet } from 'lucide-vue-next'
</script>
