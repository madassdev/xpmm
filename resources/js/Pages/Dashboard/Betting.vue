<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import FeaturePlaceholder from '@/Components/layout/FeaturePlaceholder.vue'

const user = { name: 'Welcome, Muztirs', avatar: '' }
</script>

<template>
  <AppLayout title="Bet Top-up" :navSections="[]" activeKey="bet" :user="user">
    <FeaturePlaceholder
      title="Bet Top-up"
      description="Top-up your favourite betting platforms directly from your XPM wallet. The integrations are on the way."
      icon="TicketPercent"
    />
  </AppLayout>
</template>
