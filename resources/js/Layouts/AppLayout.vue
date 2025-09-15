<script setup>
import { ref, computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import TopNav from '@/Components/layout/TopNav.vue'
import SidebarNav from '@/Components/layout/SidebarNav.vue'

/**
 * Props:
 * - title: page title shown in TopNav
 * - navSections: grouped nav items (see defaultNav below for shape)
 * - activeKey: key of the active nav item (used by SidebarNav)
 * - user: { name, avatar } for the TopNav
 */
const props = defineProps({
  title: { type: String, default: '' },
  navSections: { type: Array, default: () => [] },
  activeKey: { type: String, default: '' },
  user: { type: Object, default: () => ({ name: 'Welcome', avatar: '' }) },
})

// Default nav (used if parent doesn’t pass one)
const defaultNav = [
  {
    label: 'General',
    items: [
      { key: 'overview',  label: 'Overview',   href: route?.('dashboard') ?? '#', icon: 'LayoutDashboard' },
      { key: 'giftcards', label: 'Giftcards',  href: '#', icon: 'Gift' },
      { key: 'wallets',   label: 'Wallets',    href: '#', icon: 'Wallet' },
      { key: 'cards',     label: 'Cards',      href: '#', icon: 'CreditCard' },
      { key: 'referrals', label: 'Referrals',  href: '#', icon: 'Users' },
      { key: 'txns',      label: 'Transactions', href: '#', icon: 'ReceiptText' },
    ],
  },
  {
    label: 'Utilities',
    items: [
      { key: 'bills',   label: 'Bills',    href: route?.('bills.index') ?? '#', icon: 'Receipt' },
      { key: 'transfer',label: 'Transfer', href: '#', icon: 'Send' },
      { key: 'bet',     label: 'Bet Top-up', href: '#', icon: 'TicketPercent' },
    ],
  },
]

const sections = computed(() => props.navSections.length ? props.navSections : defaultNav)

const mobileOpen = ref(false)
const toggleSidebar = () => (mobileOpen.value = !mobileOpen.value)
const closeSidebar = () => (mobileOpen.value = false)
</script>

<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Top nav -->
    <TopNav :title="title" :user="user" @toggle-sidebar="toggleSidebar" />

    <div class="mx-auto max-w-[1400px] px-4 sm:px-6 lg:px-8">
      <div class="grid grid-cols-12 gap-6 pt-6">
        <!-- Sidebar (desktop) -->
        <aside class="hidden lg:block col-span-3 xl:col-span-2">
          <SidebarNav :sections="sections" :activeKey="activeKey" />
        </aside>

        <!-- Sidebar drawer (mobile/tablet) -->
        <transition enter-active-class="transition" enter-from-class="opacity-0" enter-to-class="opacity-100"
                    leave-active-class="transition" leave-from-class="opacity-100" leave-to-class="opacity-0">
          <div v-if="mobileOpen" class="fixed inset-0 z-40 bg-black/40" @click="closeSidebar"></div>
        </transition>

        <transition enter-active-class="transition-transform duration-200" enter-from-class="-translate-x-full"
                    enter-to-class="translate-x-0" leave-active-class="transition-transform duration-200"
                    leave-from-class="translate-x-0" leave-to-class="-translate-x-full">
          <aside v-if="mobileOpen" class="fixed inset-y-0 left-0 z-50 w-80 bg-white border-r">
            <div class="h-16 border-b flex items-center px-4">
              <span class="font-semibold">Menu</span>
              <button class="ml-auto text-sm text-gray-600 hover:text-gray-900" @click="closeSidebar">Close</button>
            </div>
            <div class="p-4">
              <SidebarNav :sections="sections" :activeKey="activeKey" @navigate="closeSidebar" />
            </div>
          </aside>
        </transition>

        <!-- Main -->
        <main class="col-span-12 lg:col-span-9 xl:col-span-10 pb-10">
          <slot />
        </main>
      </div>
    </div>
  </div>
</template>
