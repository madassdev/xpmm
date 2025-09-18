<script setup>
import * as Lucide from 'lucide-vue-next'

const props = defineProps({
    active: { type: String, default: 'profile' },
})
const emit = defineEmits(['select'])

const items = [
    { key: 'profile', label: 'Profile', icon: 'User' },
    { key: 'account', label: 'My Account', icon: 'Users' },
    { key: 'security', label: 'Security', icon: 'Shield' },
    { key: 'kyc', label: 'Identity Verification', icon: 'ScanFace' },
    { key: 'upgrade', label: 'Upgrade', icon: 'Rocket' },
    { key: 'support', label: 'Support', icon: 'MessageCircle' },
]
</script>

<template>
    <div class="bg-white rounded-2xl border border-gray-200 p-3">
        <div class="space-y-3">
            <button v-for="it in items" :key="it.key" type="button"
                class="ring-primary w-full rounded-2xl border px-5 py-4 flex items-center gap-3 transition
         outline-none focus:outline-none focus-visible:outline-none
         focus-visible:ring-2 focus-visible:ring-primary/30 focus-visible:ring-offset-2 focus-visible:ring-offset-white" :class="it.key === active
            ? 'bg-primary/10 text-primary border-transparent ring-1 ring-primary/30'
            : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50 hover:border-gray-300'" @click="emit('select', it.key)">
                <component :is="Lucide[it.icon] || Lucide.Circle" class="w-5 h-5" />
                <span class="font-medium">{{ it.label }}</span>
            </button>

        </div>
    </div>
</template>
