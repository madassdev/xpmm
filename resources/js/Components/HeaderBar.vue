<template>
  <header class="bg-white border-b">
    <div class="max-w-7xl mx-auto px-6 lg:px-8 py-4 flex items-center justify-between">
      <div class="flex items-center gap-4">
        <h3 class="text-sm text-gray-500 font-semibold uppercase">Overview</h3>
      </div>

      <div class="flex items-center gap-4">
        <div class="relative">
          <input type="text" placeholder="Search Anything..." class="w-96 px-4 py-2 rounded-full border bg-gray-50 focus:outline-none" />
        </div>
        <button class="p-2 rounded-full hover:bg-gray-100">
          <svg class="w-6 h-6 text-gray-500" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor"/></svg>
        </button>

        <img src="/_placeholder_avatar.svg" alt="avatar" class="w-9 h-9 rounded-full border" />
      </div>
    </div>
  </header>
</template>
