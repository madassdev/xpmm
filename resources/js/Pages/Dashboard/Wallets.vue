<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import FeaturePlaceholder from '@/Components/layout/FeaturePlaceholder.vue'

const user = { name: 'Welcome, Muztirs', avatar: '' }
</script>

<template>
  <AppLayout title="Wallets" :navSections="[]" activeKey="wallets" :user="user">
    <FeaturePlaceholder
      title="Wallets"
      description="Track and manage every fiat and crypto balance in one place. Funding, swaps, and settlements will live here soon."
      icon="Wallet"
    />
  </AppLayout>
</template>
