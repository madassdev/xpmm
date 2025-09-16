<script setup>
import { ref, computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import HeroBalanceCard from '@/Components/composite/HeroBalanceCard.vue'
import ServicesStackNav from '@/Components/bills/ServicesStackNav.vue'

// Forms
import AirtimeForm from '@/Components/bills/AirtimeForm.vue'
import MobileDataForm from '@/Components/bills/MobileDataForm.vue'

// Simple placeholder component for not-yet-built pages
const ComingSoon = {
  props: { label: { type: String, default: 'Coming soon' } },
  template: `
    <div class="p-6 rounded-2xl border border-dashed border-gray-300 bg-white text-gray-500">
      {{ label }}
    </div>
  `,
}

// active tab
const active = ref('airtime')

// map service key -> component
const componentMap = {
  airtime: AirtimeForm,
  data: MobileDataForm,
  internet: ComingSoon,
  electricity: ComingSoon,
  tv: ComingSoon,
  betting: ComingSoon,
}

const currentComponent = computed(() => componentMap[active.value] || ComingSoon)
const currentLabel = computed(() => ({
  airtime: 'Airtime',
  data: 'Mobile Data',
  internet: 'Internet',
  electricity: 'Electricity',
  tv: 'TV Bills',
  betting: 'Betting Top-up',
}[active.value] || 'Bills'))
</script>

<template>
  <AppLayout :title="currentLabel" :navSections="[]" activeKey="bills" :user="{ name: 'Welcome, Muztirs', avatar: '' }">
    <!-- Balance banner -->
    <div class="mb-6">
      <!-- <HeroBalanceCard :balance="0" currency="NGN" symbol="₦" /> -->
    </div>

    <!-- Two-column grid -->
    <div class="grid grid-cols-12 gap-6">
      <!-- Left stack -->
      <div class="col-span-12 md:col-span-5 lg:col-span-4 xl:col-span-3">
        <ServicesStackNav :active="active" @select="active = $event" />
      </div>

      <!-- Right dynamic form -->
      <div class="col-span-12 md:col-span-7 lg:col-span-8 xl:col-span-9">
        <component :is="currentComponent" :label="currentLabel" />
      </div>
    </div>
  </AppLayout>
</template>
