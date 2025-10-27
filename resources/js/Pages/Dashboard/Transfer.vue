<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import FeaturePlaceholder from '@/Components/layout/FeaturePlaceholder.vue'

const user = { name: 'Welcome, Muztirs', avatar: '' }
</script>

<template>
  <AppLayout title="Transfer" :navSections="[]" activeKey="transfer" :user="user">
    <FeaturePlaceholder
      title="Transfers"
      description="Instant payouts between wallets and to bank accounts are almost ready. Stay tuned."
      icon="Send"
    />
  </AppLayout>
</template>
