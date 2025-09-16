<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import ServicesStackNav from '@/Components/bills/ServicesStackNav.vue'
import AirtimeForm from '@/Components/bills/AirtimeForm.vue'
import HeroBalanceCard from '@/Components/composite/HeroBalanceCard.vue';
</script>

<template>
  <AppLayout title="Bills" :navSections="[]" activeKey="bills" :user="{ name: 'Welcome, Muztirs', avatar: '' }">
    <!-- Balance banner -->
    <div class="mb-6">
        <HeroBalanceCard :balance="0" currency="NGN" symbol="₦" />
    </div>

    <!-- Two-column grid -->
    <div class="grid grid-cols-12 gap-6">
      <!-- Left stack -->
      <div class="col-span-12 md:col-span-5 lg:col-span-4 xl:col-span-3">
        <ServicesStackNav active="airtime" />
      </div>

      <!-- Right form -->
      <div class="col-span-12 md:col-span-7 lg:col-span-8 xl:col-span-9">
        <AirtimeForm />
      </div>
    </div>
  </AppLayout>
</template>
