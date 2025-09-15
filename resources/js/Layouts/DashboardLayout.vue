<template>
  <div class="min-h-screen bg-gray-50 flex">
    <Sidebar />
    <div class="flex-1 flex flex-col">
      <HeaderBar />
      <main class="p-6 lg:p-8">
        <slot />
      </main>
    </div>
  </div>
</template>

<script setup>
import Sidebar from '@/Components/Sidebar.vue'
import HeaderBar from '@/Components/HeaderBar.vue'
</script>
