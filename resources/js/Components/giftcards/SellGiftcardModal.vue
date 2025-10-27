<script setup>
import { ref, computed, watch, onUnmounted } from 'vue'
import Modal from '@/Components/ui/Modal.vue'
import Button from '@/Components/ui/Button.vue'
import { post } from '@/lib/api'
import { Upload, Plus, Minus, X } from 'lucide-vue-next'

const props = defineProps({
    open: { type: Boolean, default: false },
    card: { type: Object, default: null }, // {id, name, logo}
})
const emit = defineEmits(['close', 'submitted'])

const currency = ref('NGN')
const type = ref('e-card') // 'e-card' | 'physical'
const amount = ref('')
const qty = ref(1)
const files = ref([])     // File[]
const fileUrls = ref([])  // Object URLs for preview

const submitting = ref(false)
const error = ref('')

watch(() => props.open, (o) => {
    if (!o) reset()
})

function reset() {
    // Clean up object URLs
    fileUrls.value.forEach(url => {
        if (url) URL.revokeObjectURL(url)
    })
    currency.value = 'NGN'
    type.value = 'e-card'
    amount.value = ''
    qty.value = 1
    files.value = []
    fileUrls.value = []
    error.value = ''
}

// Clean up on unmount
onUnmounted(() => {
    fileUrls.value.forEach(url => {
        if (url) URL.revokeObjectURL(url)
    })
})

const valid = computed(() =>
    props.card &&
    currency.value &&
    ['e-card', 'physical'].includes(type.value) &&
    Number(amount.value) > 0 &&
    qty.value > 0
)

function onPick(e) {
    const list = Array.from(e.target.files || [])
    const newFiles = [...files.value, ...list].slice(0, 6) // cap at 6
    
    // Create object URLs for new files
    files.value = newFiles
    fileUrls.value = newFiles.map(file => {
        if (file instanceof File) {
            return URL.createObjectURL(file)
        }
        return null
    })
}

function removeFile(i) {
    // Revoke object URL before removing
    if (fileUrls.value[i]) {
        URL.revokeObjectURL(fileUrls.value[i])
    }
    files.value.splice(i, 1)
    fileUrls.value.splice(i, 1)
}

function getFileUrl(index) {
    return fileUrls.value[index] || ''
}

async function submit() {
    if (!valid.value) return
    submitting.value = true
    error.value = ''

    try {
        const fd = new FormData()
        fd.append('giftcard_id', props.card.id)
    fd.append('currency', currency.value)
    fd.append('type', type.value)
    fd.append('amount', String(Number(amount.value)))
    fd.append('quantity', String(qty.value))
    files.value.forEach((f, i) => fd.append('images[]', f, f.name || `upload-${i}.jpg`))

        // Don't set Content-Type header for FormData - axios sets it automatically with boundary
        await post('/giftcards/sell', fd)

        emit('submitted')
    } catch (e) {
        error.value =
            e?.response?.data?.message ||
            (e?.response?.status === 422
                ? Object.values(e.response.data.errors || {}).flat().join(', ')
                : 'Unable to submit trade. Please try again.')
    } finally {
        submitting.value = false
    }
}
</script>

<template>
    <Modal :open="open" :title="`Sell Giftcard${card ? ' (' + card.name + ')' : ''}`" :disabled="!valid || submitting"
        @close="$emit('close')" @submit="submit">
        <!-- ✅ Single default slot -->
        <template #default>
            <div class="space-y-4">
                <!-- Currency -->
                <div>
                    <label class="block text-sm text-gray-700 mb-1">Select Currency</label>
                    <div class="relative">
                        <select v-model="currency"
                            class="w-full rounded-xl border-2 border-orange-200 bg-white px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-orange-200">
                            <option value="NGN">NGN</option>
                            <option value="USD">USD</option>
                            <option value="EUR">EUR</option>
                        </select>
                    </div>
                </div>

                <!-- Type -->
                <div>
                    <label class="block text-sm text-gray-700 mb-1">Giftcard Type</label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="inline-flex items-center gap-2 rounded-xl border-2 px-3 py-2 cursor-pointer"
                            :class="type === 'e-card' ? 'border-orange-300 bg-orange-50' : 'border-orange-200'">
                            <input type="radio" class="sr-only" value="e-card" v-model="type" />
                            <span class="text-sm">E-Card</span>
                        </label>
                        <label class="inline-flex items-center gap-2 rounded-xl border-2 px-3 py-2 cursor-pointer"
                            :class="type === 'physical' ? 'border-orange-300 bg-orange-50' : 'border-orange-200'">
                            <input type="radio" class="sr-only" value="physical" v-model="type" />
                            <span class="text-sm">Physical Card</span>
                        </label>
                    </div>
                </div>

                <!-- Amount + Qty -->
                <div class="grid grid-cols-3 gap-3">
                    <div class="col-span-2">
                        <label class="block text-sm text-gray-700 mb-1">Amount</label>
                        <input v-model="amount" inputmode="decimal" placeholder="Enter amount"
                            class="w-full rounded-xl border-2 border-orange-200 bg-white px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-orange-200" />
                    </div>
                    <div>
                        <label class="block text-sm text-gray-700 mb-1">Qty</label>
                        <div class="flex items-stretch rounded-xl overflow-hidden border-2 border-orange-200">
                            <button type="button" class="px-3 hover:bg-orange-50"
                                @click="qty = Math.max(1, qty - 1)">-</button>
                            <div class="min-w-[3rem] grid place-items-center text-sm">{{ qty }}</div>
                            <button type="button" class="px-3 hover:bg-orange-50" @click="qty++">+</button>
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-orange-200 bg-orange-50/40 px-4 py-3 text-xs text-orange-700">
                    Proceeds will be settled to your fiat (NGN) balance automatically.
                </div>

                <!-- Upload -->
                <div>
                    <label class="block text-sm text-gray-700 mb-1">Upload Giftcard Image(s)</label>
                    <div class="rounded-xl border-2 border-dashed border-orange-200 p-4 bg-orange-50/40">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="file" class="sr-only" multiple accept="image/*" @change="onPick" />
                            <span
                                class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-white border border-orange-200">
                                <!-- your icon -->
                                ⬆️
                            </span>
                            <span class="text-sm text-gray-700">Click to upload (max 6 images)</span>
                        </label>

                        <div v-if="files.length" class="mt-3 flex flex-wrap gap-3">
                            <div v-for="(f, i) in files" :key="i"
                                class="relative w-20 h-16 rounded-lg overflow-hidden border border-orange-200 bg-white">
                                <img :src="getFileUrl(i)" class="w-full h-full object-cover" />
                                <button type="button" class="absolute -top-2 -right-2 bg-white rounded-full border p-1"
                                    @click="removeFile(i)">✕</button>
                            </div>
                        </div>
                    </div>
                </div>

                <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
            </div>
        </template>

        <!-- ✅ Primary slot only once -->
        <template #primary>
            <span class="flex items-center gap-2">
                <svg v-if="submitting" class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none">
                    <circle cx="12" cy="12" r="10" stroke="currentColor" class="opacity-25"></circle>
                    <path d="M4 12a8 8 0 018-8" stroke="currentColor" class="opacity-75"></path>
                </svg>
                {{ submitting ? 'Submitting…' : 'Sell Giftcard' }}
            </span>
        </template>
    </Modal>
</template>
