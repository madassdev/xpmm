<script setup>
import { ref, computed } from 'vue'
import {
    PhoneIcon,
    WifiIcon,
    GlobeIcon,
    LightbulbIcon,
    TvIcon,
    Dice5Icon,
} from 'lucide-vue-next'

import MobileDataFlow from './MobileDataFlow.vue'
import AirtimeFlow from './AirtimeFlow.vue'

const tabs = [
    { name: 'Airtime', value: 'airtime', icon: PhoneIcon, color: 'bg-pink-100 text-pink-600' },
    { name: 'Mobile Data', value: 'data', icon: WifiIcon, color: 'bg-blue-100 text-blue-600' },
    { name: 'Internet', value: 'internet', icon: GlobeIcon, color: 'bg-green-100 text-green-600' },
    { name: 'Electricity', value: 'electricity', icon: LightbulbIcon, color: 'bg-yellow-100 text-yellow-700' },
    { name: 'Tv Bills', value: 'tv', icon: TvIcon, color: 'bg-purple-100 text-purple-600' },
    { name: 'Betting', value: 'betting', icon: Dice5Icon, color: 'bg-orange-100 text-orange-600' },
]

const providers = {
    data: [
        { name: 'MTN', logo: '/img/mtn.png' },
        { name: 'Airtel', logo: '/img/airtel.png' },
        { name: 'Glo', logo: '/img/glo.png' },
        { name: '9mobile', logo: '/img/9mobile.png' },
    ],
    airtime: [
        { name: 'MTN', logo: '/img/mtn.png' },
        { name: 'Airtel', logo: '/img/airtel.png' },
        { name: 'Glo', logo: '/img/glo.png' },
        { name: '9mobile', logo: '/img/9mobile.png' },
    ],
    internet: [],
    electricity: [],
    tv: [],
    betting: [],
}

const activeTab = ref('data')
const activeProviders = computed(() => providers[activeTab.value] || [])
</script>

<template>
    <div class="space-y-8">
        <!-- Tabs -->
        <div class="grid grid-cols-6 gap-2">
            <button v-for="tab in tabs" :key="tab.value" @click="activeTab = tab.value"
                class="flex items-center justify-center px-3 py-2 rounded-lg font-medium transition-colors"
                :class="activeTab === tab.value ? tab.color : 'bg-gray-100 text-gray-400 hover:text-gray-500'">
                <component :is="tab.icon" class="w-4 h-4 mr-2" />
                {{ tab.name }}
            </button>
        </div>

        <!-- Render component based on selected service -->
        <MobileDataFlow v-if="activeTab === 'data'" :providers="activeProviders" />
        <AirtimeFlow v-if="activeTab === 'airtime'" :providers="activeProviders" :processing="form.processing"
            @submit="payload => form.post(route('airtime.purchase'), { data: payload })" />
        <div v-else class="text-gray-400 text-sm italic">
            Component for {{tabs.find(t => t.value === activeTab)?.name}} not implemented yet.
        </div>
    </div>
</template>
