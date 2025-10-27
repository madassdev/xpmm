<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import FeaturePlaceholder from '@/Components/layout/FeaturePlaceholder.vue'

const user = { name: 'Welcome, Muztirs', avatar: '' }
</script>

<template>
  <AppLayout title="Referrals" :navSections="[]" activeKey="referrals" :user="user">
    <FeaturePlaceholder
      title="Referrals & Rewards"
      description="Share your referral link, track signups, and monitor reward payouts. This dashboard will go live shortly."
      icon="Users"
    />
  </AppLayout>
</template>
