<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import FeaturePlaceholder from '@/Components/layout/FeaturePlaceholder.vue'

const user = { name: 'Welcome, Muztirs', avatar: '' }
</script>

<template>
  <AppLayout title="Cards" :navSections="[]" activeKey="cards" :user="user">
    <FeaturePlaceholder
      title="Virtual & Physical Cards"
      description="Issue virtual cards, monitor limits, and manage spending controls. We’re putting the finishing touches on this experience."
      icon="CreditCard"
    />
  </AppLayout>
</template>
