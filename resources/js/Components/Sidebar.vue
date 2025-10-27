<template>
  <aside class="w-52 bg-white border-r min-h-screen flex flex-col">
    <div class="p-6 flex items-center gap-3">
      <div class="w-8 h-8 rounded-md bg-orange-500 flex items-center justify-center text-white font-bold">X</div>
      <div class="text-sm font-semibold">XPMEXCHANGE</div>
    </div>

    <nav class="px-4 py-6 space-y-2 flex-1">
      <Link
        v-for="item in navItems"
        :key="item.key"
        :href="item.href"
        class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors"
        :class="isActive(item.href) ? activeClasses : inactiveClasses"
      >
        <component :is="getIcon(item.icon)" class="w-5 h-5" />
        <span>{{ item.label }}</span>
      </Link>
    </nav>

    <div class="p-4 border-t">
      <Link href="/logout" method="post" as="button" class="flex items-center gap-3 text-gray-600 hover:text-red-600">
        <LogOut class="w-5 h-5" />
        <span>Sign Out</span>
      </Link>
    </div>
  </aside>
</template>

<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import * as Icons from 'lucide-vue-next'
import { getDashboardNavSections } from '@/constants/dashboardNav'

const page = usePage()
const { LogOut } = Icons
const navItems = computed(() =>
  getDashboardNavSections()
    .flatMap((section) => section.items)
)

const normalizePath = (href) => {
  if (!href) return ''
  try {
    const base = typeof window !== 'undefined' ? window.location.origin : 'http://localhost'
    const url = new URL(href, base)
    return url.pathname
  } catch {
    return href
  }
}

const isActive = (href) => normalizePath(page.url?.split('?')[0] ?? '') === normalizePath(href)
const getIcon = (name) => Icons[name] || Icons.Circle

const activeClasses = 'bg-orange-50 text-orange-600 font-medium'
const inactiveClasses = 'text-gray-600 hover:bg-gray-100'
</script>
