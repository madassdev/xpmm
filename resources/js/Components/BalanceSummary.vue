<template>
    <div class="flex items-center justify-between">
        <div>
            <div class="text-sm text-gray-500 flex items-center space-x-2 w-full">
                <span>Total Balance</span>
                <span>
                    <EyeOff class="h-4" @click="showBalance = !showBalance" v-if="showBalance" />
                </span>
                <span>
                    <Eye class="h-4" @click="showBalance = !showBalance" v-if="!showBalance" />
                </span>
            </div>
            <div class="text-2xl font-bold">{{ showBalance ? '₦0.00' : '********' }}</div>
        </div>
        <div>
            <button class="px-3 py-2 rounded-lg border">Balance Summary</button>
        </div>
    </div>
</template>

<script setup>
import { EyeOff, Eye } from 'lucide-vue-next'
import { ref } from 'vue';
const showBalance = ref(false)
</script>