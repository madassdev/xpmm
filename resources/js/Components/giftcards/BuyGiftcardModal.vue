<script setup>
import { ref, computed, watch } from 'vue'
import Modal from '@/Components/ui/Modal.vue'
import Button from '@/Components/ui/Button.vue'
import { post } from '@/lib/api'
import { Plus, Minus } from 'lucide-vue-next'

const props = defineProps({
    open: { type: Boolean, default: false },
    card: { type: Object, default: null }, // {id, name, logo, rate, available_values}
})
const emit = defineEmits(['close', 'submitted'])

const currency = ref('NGN')
const amount = ref('')
const qty = ref(1)

const submitting = ref(false)
const error = ref('')

watch(() => props.open, (o) => {
    if (!o) reset()
})

function reset() {
    currency.value = 'NGN'
    amount.value = ''
    qty.value = 1
    error.value = ''
}

const valid = computed(() =>
    props.card &&
    currency.value &&
    Number(amount.value) > 0 &&
    qty.value > 0
)

// Calculate total cost
const totalCost = computed(() => {
    if (!valid.value || !props.card?.rate) return 0
    const baseAmount = Number(amount.value) * qty.value
    const rate = props.card.rate / 100 // rate is stored as percentage
    return baseAmount * rate
})

const formattedTotal = computed(() => {
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN',
    }).format(totalCost.value)
})

async function submit() {
    if (!valid.value) return
    submitting.value = true
    error.value = ''

    try {
        const { data } = await post('/giftcards/buy', {
            giftcard_id: props.card.id,
            currency: currency.value,
            amount: Number(amount.value),
            quantity: qty.value,
            payment_method: 'NGN',
        })

        emit('submitted', data)
    } catch (e) {
        error.value =
            e?.response?.data?.message ||
            (e?.response?.status === 422
                ? Object.values(e.response.data.errors || {}).flat().join(', ')
                : 'Unable to process purchase. Please try again.')
    } finally {
        submitting.value = false
    }
}
</script>

<template>
    <Modal 
        :open="open" 
        :title="`Buy Giftcard${card ? ' (' + card.name + ')' : ''}`" 
        :disabled="!valid || submitting"
        @close="$emit('close')" 
        @submit="submit"
    >
        <template #default>
            <div class="space-y-4">
                <!-- Rate Info -->
                <div v-if="card?.rate" class="rounded-xl bg-orange-50 border border-orange-200 p-3">
                    <p class="text-sm text-gray-700">
                        <span class="font-medium">Exchange Rate:</span> {{ card.rate }}%
                    </p>
                </div>

                <!-- Currency -->
                <div>
                    <label class="block text-sm text-gray-700 mb-1">Select Currency</label>
                    <div class="relative">
                        <select 
                            v-model="currency"
                            class="w-full rounded-xl border-2 border-orange-200 bg-white px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-orange-200"
                        >
                            <option value="NGN">NGN</option>
                            <option value="USD">USD</option>
                            <option value="EUR">EUR</option>
                        </select>
                    </div>
                </div>

                <!-- Amount + Qty -->
                <div class="grid grid-cols-3 gap-3">
                    <div class="col-span-2">
                        <label class="block text-sm text-gray-700 mb-1">Giftcard Amount</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 text-sm">
                                {{ currency }}
                            </span>
                            <input 
                                v-model="amount" 
                                inputmode="decimal" 
                                placeholder="Enter amount"
                                class="w-full rounded-xl border-2 border-orange-200 bg-white pl-12 pr-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-orange-200" 
                            />
                        </div>
                        <p v-if="card?.available_values?.length" class="text-xs text-gray-500 mt-1">
                            Available: {{ card.available_values.join(', ') }}
                        </p>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-700 mb-1">Quantity</label>
                        <div class="flex items-stretch rounded-xl overflow-hidden border-2 border-orange-200">
                            <button 
                                type="button" 
                                class="px-3 hover:bg-orange-50 transition"
                                @click="qty = Math.max(1, qty - 1)"
                            >
                                <Minus class="w-4 h-4" />
                            </button>
                            <div class="min-w-[3rem] grid place-items-center text-sm font-medium">
                                {{ qty }}
                            </div>
                            <button 
                                type="button" 
                                class="px-3 hover:bg-orange-50 transition"
                                @click="qty++"
                            >
                                <Plus class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-orange-200 bg-orange-50/40 px-4 py-3 text-xs text-orange-700">
                    Purchases are debited from your fiat (NGN) balance.
                </div>

                <!-- Total Cost -->
                <div v-if="totalCost > 0" class="rounded-xl bg-gray-50 border border-gray-200 p-4">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-700">Total Cost:</span>
                        <span class="text-lg font-bold text-primary">{{ formattedTotal }}</span>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">
                        {{ qty }} × {{ currency }} {{ amount }} × {{ card?.rate }}% rate
                    </p>
                </div>

                <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
            </div>
        </template>

        <template #primary>
            <span class="flex items-center gap-2">
                <svg v-if="submitting" class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none">
                    <circle cx="12" cy="12" r="10" stroke="currentColor" class="opacity-25"></circle>
                    <path d="M4 12a8 8 0 018-8" stroke="currentColor" class="opacity-75"></path>
                </svg>
                {{ submitting ? 'Processing…' : 'Buy Giftcard' }}
            </span>
        </template>
    </Modal>
</template>
