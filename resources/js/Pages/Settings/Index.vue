<script setup>
import { ref, computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import SettingsHeaderCard from '@/Components/settings/SettingsHeaderCard.vue'
import SettingsNav from '@/Components/settings/SettingsNav.vue'

// Panels
import ProfilePanel from '@/Components/settings/ProfilePanel.vue'
import SecurityPanel from '@/Components/settings/SecurityPanel.vue'
import SupportPanel from '@/Components/settings/SupportPanel.vue'

// Optional placeholder panels
const SimplePanel = (label) => ({ template: `<div class="p-6 rounded-2xl border border-gray-200 bg-white text-gray-600">${label} — Coming soon</div>` })
const AccountPanel   = SimplePanel('My Account')
const KycPanel       = SimplePanel('Identity Verification')
const UpgradePanel   = SimplePanel('Upgrade')

const active = ref('profile')

const componentMap = {
  profile:  ProfilePanel,
  account:  AccountPanel,
  security: SecurityPanel,
  kyc:      KycPanel,
  upgrade:  UpgradePanel,
  support:  SupportPanel,
}
const currentComponent = computed(() => componentMap[active.value] || ProfilePanel)
</script>

<template>
  <AppLayout title="Settings" :navSections="[]" activeKey="overview" :user="{ name: 'Welcome, Muztirs', avatar: '' }">
    <div class="space-y-6">
      <!-- Header -->
      <SettingsHeaderCard :verified="false" :balance="0" />

      <!-- Body -->
      <div class="grid grid-cols-12 gap-6">
        <div class="col-span-12 md:col-span-4 lg:col-span-4 xl:col-span-3">
          <SettingsNav :active="active" @select="active = $event" />
        </div>

        <div class="col-span-12 md:col-span-8 lg:col-span-8 xl:col-span-9">
          <component :is="currentComponent" />
        </div>
      </div>
    </div>
  </AppLayout>
</template>
