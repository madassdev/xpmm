<template>
  <div class="min-h-screen bg-gray-50 flex">
    <AdminSidebar />

    <div class="flex-1 flex flex-col">
      <header
        class="sticky top-0 z-20 bg-white/80 backdrop-blur supports-[backdrop-filter]:bg-white/70 border-b border-gray-100"
      >
        <div class="px-6 lg:px-8">
          <div class="flex h-16 items-center justify-between">
            <div class="hidden md:flex items-center gap-3 text-sm text-gray-500">
              <span class="px-3 py-1 rounded-full bg-orange-50 text-orange-600 font-semibold">
                Admin
              </span>
              <span class="text-gray-400">/</span>
              <span class="font-medium text-gray-700">{{ pageTitle }}</span>
            </div>
            <div class="flex-1 md:flex-none md:w-auto flex items-center gap-4">
              <label class="relative flex-1 md:w-64">
                <span class="sr-only">Search</span>
                <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                <input
                  type="search"
                  placeholder="Search insights, users, transactions..."
                  class="w-full rounded-xl border border-gray-200 bg-gray-50/60 py-2.5 pl-10 pr-4 text-sm text-gray-600 placeholder:text-gray-400 focus:border-orange-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-orange-100"
                />
              </label>
              <button
                type="button"
                class="hidden md:inline-flex items-center justify-center h-10 w-10 rounded-xl border border-gray-200 text-gray-500 hover:text-orange-600 hover:border-orange-200 transition-colors"
              >
                <Bell class="h-5 w-5" />
              </button>
              <div class="hidden md:flex items-center gap-3">
                <div class="text-right">
                  <p class="text-sm font-semibold text-gray-800">Olivia Rhye</p>
                  <p class="text-xs text-gray-400">Super Admin</p>
                </div>
                <div class="h-10 w-10 rounded-full bg-gradient-to-br from-orange-400 via-orange-500 to-orange-600 text-white flex items-center justify-center font-semibold">
                  OR
                </div>
              </div>
              <button
                type="button"
                class="inline-flex lg:hidden items-center justify-center h-10 w-10 rounded-xl border border-gray-200 text-gray-500 hover:text-orange-600 hover:border-orange-200 transition-colors"
              >
                <Menu class="h-5 w-5" />
              </button>
            </div>
          </div>
        </div>
      </header>

      <main class="flex-1 overflow-y-auto px-4 py-6 sm:px-6 lg:px-8 bg-gray-50">
        <slot />
      </main>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { Bell, Menu, Search } from 'lucide-vue-next'

import AdminSidebar from '@/Components/admin/AdminSidebar.vue'

const page = usePage()
const pageTitle = computed(() => page.props.meta?.title ?? 'Overview')
</script>
