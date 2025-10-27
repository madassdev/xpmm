<template>
  <AdminLayout>
    <section class="max-w-3xl space-y-4">
      <div>
        <p class="text-xs uppercase tracking-[0.35em] text-orange-500 font-semibold">
          Admin Console
        </p>
        <h1 class="mt-2 text-3xl font-semibold text-gray-900">{{ title }}</h1>
        <p v-if="description" class="mt-2 text-base text-gray-500">
          {{ description }}
        </p>
      </div>
      <slot />
    </section>
  </AdminLayout>
</template>

<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

import AdminLayout from '@/Layouts/AdminLayout.vue'

const page = usePage()

const title = computed(() => page.props.meta?.title ?? 'Admin')
const description = computed(() => page.props.meta?.description ?? '')
</script>
