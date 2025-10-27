<template>
  <aside class="flex flex-col w-64 lg:w-72 xl:w-80 bg-white border-r border-gray-100 min-h-screen">
    <div class="px-8 pt-8 pb-6 flex items-center gap-3">
      <div class="h-10 w-10 rounded-xl bg-orange-500 flex items-center justify-center">
        <Sparkles class="h-5 w-5 text-white" />
      </div>
      <div>
        <p class="font-semibold text-gray-900">XPM Exchange</p>
        <p class="text-xs font-medium uppercase tracking-[0.25em] text-gray-400">Admin</p>
      </div>
    </div>

    <nav class="flex-1 px-4 space-y-1">
      <Link
        v-for="item in navItems"
        :key="item.label"
        :href="item.href"
        class="group flex items-center gap-3 px-4 py-3 rounded-xl transition-colors"
        :class="item.isActive ? activeClasses : inactiveClasses"
      >
        <component
          :is="item.icon"
          class="h-5 w-5"
          :class="item.isActive ? 'text-white' : 'text-gray-400 group-hover:text-orange-500'"
        />
        <span class="font-medium" :class="item.isActive ? 'text-white' : 'text-gray-600'">
          {{ item.label }}
        </span>
        <ChevronRight
          class="ml-auto h-4 w-4"
          :class="item.isActive ? 'text-white/70' : 'text-gray-300 group-hover:text-orange-500'"
        />
      </Link>
    </nav>

    <div class="px-6 pb-10">
      <div class="rounded-2xl bg-orange-50 border border-orange-100 p-4">
        <p class="text-xs font-semibold text-orange-600 uppercase tracking-wide">Quick tip</p>
        <p class="mt-2 text-sm text-gray-600">
          Customize your admin controls and monitor activity directly from this dashboard.
        </p>
        <button
          type="button"
          class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-orange-600"
        >
          <Settings class="h-4 w-4" />
          Configure panel
        </button>
      </div>
    </div>
  </aside>
</template>

<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import {
  ArrowRightLeft,
  CreditCard,
  FileText,
  Gift,
  LayoutDashboard,
  Smartphone,
  Settings,
  ShieldCheck,
  Sparkles,
  Users,
  Wallet,
  Zap,
  ChevronRight,
} from 'lucide-vue-next'

const page = usePage()

const navItems = computed(() => {
  const currentUrl = (page.url || '').split('?')[0]
  const currentKey = page.props.meta?.current || null

  const items = [
    { label: 'Overview', href: '/admin', icon: LayoutDashboard, key: 'overview' },
    { label: 'Transactions', href: '/admin/transactions', icon: ArrowRightLeft, key: 'transactions' },
    { label: 'Cards', href: '/admin/cards', icon: CreditCard, key: 'cards' },
    { label: 'Bills management', href: '/admin/bills-management', icon: FileText, key: 'bills-management' },
    { label: 'Virtual Cards', href: '/admin/virtual-cards', icon: Smartphone, key: 'virtual-cards' },
    { label: 'Crypto Exchange', href: '/admin/crypto-exchange', icon: Zap, key: 'crypto-exchange' },
    { label: 'Crypto Wallets', href: '/admin/crypto-wallets', icon: Wallet, key: 'crypto-wallets' },
    { label: 'Users', href: '/admin/users', icon: Users, key: 'users' },
    { label: 'KYC', href: '/admin/kyc', icon: ShieldCheck, key: 'kyc' },
    { label: 'Settings', href: '/admin/settings', icon: Settings, key: 'settings' },
    { label: 'Giftcards', href: '/admin/giftcards', icon: Gift, key: 'giftcards' },
  ]

  return items.map((item) => {
    const isActive =
      (currentKey && currentKey === item.key) || currentUrl === item.href || (!currentUrl && item.key === 'overview')
    return { ...item, isActive }
  })
})

const activeClasses =
  'bg-orange-500 text-white shadow-sm shadow-orange-500/30 ring-1 ring-orange-500/60'
const inactiveClasses = 'text-gray-600 hover:bg-orange-50 hover:text-orange-600'
</script>
