<script setup>
import { ref } from 'vue'
import { Menu, Bell, ChevronDown } from 'lucide-vue-next'
import { Link } from '@inertiajs/vue3'
import Avatar from '@/Components/ui/Avatar.vue'

const props = defineProps({
  title: { type: String, default: '' },
  user: { type: Object, default: () => ({ name: 'User', avatar: '' }) },
})
const emit = defineEmits(['toggle-sidebar'])

const openUser = ref(false)
const toggleUser = () => (openUser.value = !openUser.value)
const closeUser = () => (openUser.value = false)
</script>

<template>
  <header class="sticky top-0 z-30 bg-white/70 backdrop-blur border-b">
    <div class="mx-auto max-w-[1400px] px-4 sm:px-6 lg:px-8 h-16 flex items-center gap-4">
      <!-- Mobile menu -->
      <button
        class="lg:hidden inline-flex items-center justify-center w-10 h-10 rounded-lg hover:bg-gray-100"
        aria-label="Open menu"
        @click="$emit('toggle-sidebar')"
      >
        <Menu class="w-5 h-5 text-gray-700" />
      </button>

      <!-- Title -->
      <div class="flex items-center gap-2">
        <span class="text-sm text-gray-500">Overview</span>
        <span v-if="title" class="text-gray-900 font-semibold">{{ title }}</span>
      </div>

      <!-- Spacer -->
      <div class="ml-auto flex items-center gap-2 sm:gap-3">
        <!-- Search (visual only; wire later) -->
        <div class="hidden md:flex items-center">
          <input
            type="text"
            placeholder="Search anything…"
            class="w-64 px-3 py-2 rounded-lg border border-gray-200 bg-gray-50 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30"
          />
        </div>

        <button class="inline-flex items-center justify-center w-10 h-10 rounded-full hover:bg-gray-100" aria-label="Notifications">
          <Bell class="w-5 h-5 text-gray-700" />
        </button>

        <!-- User -->
        <div class="relative">
          <button
            class="inline-flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-gray-100"
            @click="toggleUser"
          >
            <Avatar :src="user.avatar" :name="user.name" size="sm" />
            <span class="hidden sm:block text-sm text-gray-800">{{ user.name }}</span>
            <ChevronDown class="w-4 h-4 text-gray-500" />
          </button>

          <transition enter-active-class="transition" enter-from-class="opacity-0 -translate-y-1"
                      enter-to-class="opacity-100 translate-y-0"
                      leave-active-class="transition" leave-from-class="opacity-100 translate-y-0"
                      leave-to-class="opacity-0 -translate-y-1">
            <div
              v-if="openUser"
              class="absolute right-0 mt-2 w-48 bg-white rounded-lg border border-gray-200 shadow-lg p-1"
              @mouseleave="closeUser"
            >
              <Link href="#" class="block px-3 py-2 text-sm rounded-md hover:bg-gray-50">Profile</Link>
              <Link href="#" class="block px-3 py-2 text-sm rounded-md hover:bg-gray-50">Settings</Link>
              <hr class="my-1" />
              <Link href="#" class="block px-3 py-2 text-sm rounded-md hover:bg-gray-50 text-red-600">Sign out</Link>
            </div>
          </transition>
        </div>
      </div>
    </div>
  </header>
</template>
