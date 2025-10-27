<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import FeaturePlaceholder from '@/Components/layout/FeaturePlaceholder.vue'

const user = { name: 'Welcome, Muztirs', avatar: '' }
</script>

<template>
  <AppLayout title="Transactions" :navSections="[]" activeKey="txns" :user="user">
    <FeaturePlaceholder
      title="Transactions"
      description="Come back to view every payment, swap, and settlement with powerful filters and exports."
      icon="ArrowRightLeft"
    />
  </AppLayout>
</template>
