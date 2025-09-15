<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
    providers: { type: Array, default: () => [] },
})

const selectedProvider = ref(null)
const loadingPlans = ref(false)
const dataPlans = ref([])

watch(selectedProvider, async (provider) => {
    if (!provider) return
    loadingPlans.value = true
    dataPlans.value = []

    // Mock backend request for data plans
    setTimeout(() => {
        dataPlans.value = [
            { name: `${provider.name} Daily 500MB`, price: '₦150' },
            { name: `${provider.name} Weekly 2GB`, price: '₦500' },
            { name: `${provider.name} Monthly 10GB`, price: '₦2500' },
        ]
        loadingPlans.value = false
    }, 1000)
})
</script>

<template>
    <div class="space-y-6">
        <!-- Provider selection -->
        <h3 class="text-sm font-medium text-gray-700">Mobile Data</h3>
        <div v-if="providers.length" class="grid grid-cols-4 gap-4">
            <div v-for="provider in providers" :key="provider.name" @click="selectedProvider = provider"
                class="flex flex-col items-center justify-center space-y-2 p-4 border rounded-xl hover:bg-gray-50 cursor-pointer"
                :class="{ 'ring-2 ring-blue-500': selectedProvider?.name === provider.name }">
                <img :src="provider.logo" alt="" class="w-10 h-10 object-contain" />
                <span class="text-sm font-medium text-gray-600">{{ provider.name }}</span>
            </div>
        </div>
        <div v-else class="text-gray-400 text-sm italic">No providers available</div>

        <!-- Data Plans -->
        <div v-if="selectedProvider" class="space-y-4">
            <h3 class="text-sm font-medium text-gray-700">
                Available Plans for {{ selectedProvider.name }}
            </h3>
            <div v-if="loadingPlans" class="text-gray-500 text-sm">Loading plans...</div>
            <div v-else-if="dataPlans.length" class="space-y-2">
                <div v-for="plan in dataPlans" :key="plan.name"
                    class="flex items-center justify-between p-3 border rounded-lg hover:bg-gray-50">
                    <span class="text-gray-700">{{ plan.name }}</span>
                    <span class="font-medium text-gray-900">{{ plan.price }}</span>
                </div>
            </div>
        </div>
    </div>
</template>
