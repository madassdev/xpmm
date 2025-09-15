<script setup>
import { reactive, ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { ChevronDown, Bitcoin, Loader2 } from 'lucide-vue-next'

/** Props */
const props = defineProps({
  providers: {
    type: Array,
    default: () => [
      { name: 'MTN',     logo: '/img/mtn.png' },
      { name: 'Airtel',  logo: '/img/airtel.png' },
      { name: 'Glo',     logo: '/img/glo.png' },
      { name: '9mobile', logo: '/img/9mobile.png' },
    ],
  },
  assets: {
    type: Array,
    default: () => [{ code: 'BTC', icon: Bitcoin }],
  },
  countryCodes: {
    type: Array,
    default: () => [{ label: '🇳🇬', code: '+234' }],
  },
  /** Use this to show the spinner/disable button from a parent (e.g. form.processing) */
  processing: { type: Boolean, default: false },
})

/** Local state */
const showProviderMenu = ref(false)
const showAssetMenu    = ref(false)
const showCountryMenu  = ref(false)

const form = reactive({
  provider: props.providers[0] || null,
  country:  props.countryCodes[0] || null,
  phone: '',
  asset:    props.assets[0] || null,
  amount: '',
})

const quickAmounts = [200, 500, 1000, 2000]

/** Utils */
const onlyDigits = (v) => v.replace(/[^\d]/g, '')

const chooseProvider = (p) => { form.provider = p; showProviderMenu.value = false }
const chooseAsset    = (a) => { form.asset = a;   showAssetMenu.value = false }
const chooseCountry  = (c) => { form.country = c; showCountryMenu.value = false }
const setQuickAmount = (n) => { form.amount = String(n) }

const isValid = computed(() =>
  !!(form.provider && form.asset && form.country &&
     form.phone.trim().length >= 7 && Number(form.amount) > 0)
)

/** Emit payload upward */
const emit = defineEmits(['submit'])
const submit = () => {
  if (!isValid.value || props.processing) return
  emit('submit', {
    provider: form.provider.name,
    msisdn: `${form.country.code}${form.phone}`,
    asset: form.asset.code,
    amount: Number(form.amount),
  })
}

/** Click-outside directive */
const vClickOutside = {
  mounted(el, binding) {
    el.__clickOutside__ = (e) => { if (!el.contains(e.target)) binding.value?.(e) }
    document.addEventListener('click', el.__clickOutside__, true)
  },
  unmounted(el) {
    document.removeEventListener('click', el.__clickOutside__, true)
    delete el.__clickOutside__
  },
}

/** Close all menus with Esc */
const onKey = (e) => {
  if (e.key === 'Escape') {
    showProviderMenu.value = false
    showAssetMenu.value    = false
    showCountryMenu.value  = false
  }
}
onMounted(() => document.addEventListener('keydown', onKey))
onBeforeUnmount(() => document.removeEventListener('keydown', onKey))
</script>

<template>
  <div class="space-y-6">
    <h3 class="text-sm font-medium text-gray-700">Airtime</h3>

    <!-- Top row: Provider | Phone | Asset -->
    <div class="grid grid-cols-12 gap-3">
      <!-- Provider select -->
      <div class="col-span-12 md:col-span-5">
        <div class="relative" v-click-outside="() => (showProviderMenu = false)">
          <button
            type="button"
            class="w-full rounded-2xl bg-gray-50 border border-gray-200 px-4 py-4 flex items-center justify-between text-left"
            @click="showProviderMenu = !showProviderMenu"
          >
            <div class="flex items-center gap-3">
              <span class="font-semibold text-gray-800">{{ form.provider?.name || 'Select Provider' }}</span>
              <img v-if="form.provider" :src="form.provider.logo" :alt="form.provider.name" class="w-8 h-8 rounded-full object-contain" />
            </div>
            <ChevronDown class="w-4 h-4 text-gray-500" />
          </button>

          <!-- Provider dropdown -->
          <div
            v-show="showProviderMenu"
            class="absolute z-20 mt-2 w-full bg-white border border-gray-200 rounded-xl shadow-lg overflow-hidden"
          >
            <ul class="max-h-72 overflow-auto">
              <li
                v-for="p in providers"
                :key="p.name"
                @click="chooseProvider(p)"
                class="flex items-center justify-between px-4 py-3 hover:bg-gray-50 cursor-pointer"
              >
                <div class="flex items-center gap-3">
                  <img :src="p.logo" :alt="p.name" class="w-7 h-7 rounded-full object-contain" />
                  <span class="text-gray-800">{{ p.name }}</span>
                </div>
              </li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Phone (country + number) -->
      <div class="col-span-12 md:col-span-5">
        <div class="rounded-2xl bg-gray-50 border border-gray-200 px-3 py-2 flex items-center gap-2">
          <!-- Country -->
          <div class="relative" v-click-outside="() => (showCountryMenu = false)">
            <button
              type="button"
              class="flex items-center gap-2 px-3 py-2 rounded-xl bg-white/60 border border-gray-200"
              @click="showCountryMenu = !showCountryMenu"
            >
              <span class="text-lg">{{ form.country?.label }}</span>
              <ChevronDown class="w-4 h-4 text-gray-500" />
            </button>

            <div
              v-show="showCountryMenu"
              class="absolute mt-2 bg-white border border-gray-200 rounded-xl shadow-lg z-20"
            >
              <ul>
                <li
                  v-for="c in countryCodes"
                  :key="c.code"
                  @click="chooseCountry(c)"
                  class="px-3 py-2 hover:bg-gray-50 cursor-pointer flex items-center gap-2"
                >
                  <span class="text-lg">{{ c.label }}</span>
                  <span class="text-gray-600">{{ c.code }}</span>
                </li>
              </ul>
            </div>
          </div>

          <!-- Phone input (no inner border/ring) -->
          <input
            v-model="form.phone"
            @input="form.phone = onlyDigits(form.phone)"
            type="tel"
            inputmode="numeric"
            placeholder="Enter Phone Number"
            class="flex-1 bg-transparent px-2 py-3 placeholder-gray-400 text-gray-800
                   border-none ring-0 focus:ring-0 focus:border-0 focus:outline-none outline-none
                   shadow-none !ring-0 !shadow-none !border-0
                   appearance-none [-webkit-appearance:none] [appearance:none]"
          />
        </div>
      </div>

      <!-- Asset select -->
      <div class="col-span-12 md:col-span-2">
        <div class="relative" v-click-outside="() => (showAssetMenu = false)">
          <button
            type="button"
            class="w-full rounded-2xl bg-gray-50 border border-gray-200 px-4 py-4 flex items-center justify-between"
            @click="showAssetMenu = !showAssetMenu"
          >
            <div class="text-left">
              <div class="text-gray-400 text-sm leading-tight">{{ form.asset?.code || 'Asset' }}</div>
              <div class="text-gray-900 text-sm">0.00</div>
            </div>
            <div class="flex items-center gap-2">
              <component :is="form.asset?.icon || Bitcoin" class="w-5 h-5 text-gray-700" />
              <ChevronDown class="w-4 h-4 text-gray-500" />
            </div>
          </button>

          <!-- Asset dropdown -->
          <div
            v-show="showAssetMenu"
            class="absolute z-20 mt-2 w-full bg-white border border-gray-200 rounded-xl shadow-lg overflow-hidden"
          >
            <ul>
              <li
                v-for="a in assets"
                :key="a.code"
                @click="chooseAsset(a)"
                class="px-4 py-3 hover:bg-gray-50 cursor-pointer flex items-center justify-between"
              >
                <span class="text-gray-800">{{ a.code }}</span>
                <component :is="a.icon" class="w-5 h-5 text-gray-700" />
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <!-- Amount input (no inner border/ring) -->
    <div class="rounded-2xl bg-gray-50 border border-gray-200 px-4 py-4">
      <input
        v-model="form.amount"
        @input="form.amount = onlyDigits(form.amount)"
        type="text"
        inputmode="numeric"
        placeholder="Enter Amount"
        class="w-full bg-transparent placeholder-gray-400 text-gray-800
               border-none ring-0 focus:ring-0 focus:border-0 focus:outline-none outline-none
               shadow-none !ring-0 !shadow-none !border-0
               appearance-none [-webkit-appearance:none] [appearance:none]"
      />
    </div>

    <!-- Quick amounts -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
      <button
        v-for="amt in quickAmounts"
        :key="amt"
        @click="setQuickAmount(amt)"
        class="rounded-2xl border border-gray-200 bg-white px-5 py-3 text-gray-800 hover:bg-gray-50"
      >
        ₦{{ amt.toLocaleString() }}
      </button>
    </div>

    <!-- CTA with spinner and bg-primary -->
    <button
      :disabled="!isValid || processing"
      @click="submit"
      class="w-full h-14 rounded-2xl text-white font-semibold transition-colors inline-flex items-center justify-center gap-2"
      :aria-busy="processing ? 'true' : 'false'"
      :class="(!isValid || processing)
              ? 'bg-primary/60 cursor-not-allowed'
              : 'bg-primary hover:bg-primary/90'"
    >
      <Loader2 v-if="processing" class="w-5 h-5 animate-spin" />
      <span>{{ processing ? 'Processing...' : 'Purchase Airtime' }}</span>
    </button>
  </div>
</template>
