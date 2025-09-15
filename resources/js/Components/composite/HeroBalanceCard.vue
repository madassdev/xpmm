<script setup>
import { ref, computed } from 'vue'
import Card from '@/Components/ui/Card.vue'
import Button from '@/Components/ui/Button.vue'
import { Eye, EyeOff, ChevronDown, Banknote, Wallet, Repeat } from 'lucide-vue-next'

const props = defineProps({
  balance: { type: Number, default: 0 },
  currency: { type: String, default: 'NGN' },
  symbol:   { type: String, default: '₦' },
  loading:  { type: Boolean, default: false },
  masked:   { type: Boolean, default: false },
  actions:  {
    type: Array,
    default: () => ([
      { id: 'deposit',  label: 'Deposit',  icon: Banknote },
      { id: 'withdraw', label: 'Withdraw', icon: Wallet   },
      { id: 'trade',    label: 'Trade',    icon: Repeat   },
    ])
  },
})
const emit = defineEmits(['action', 'toggle-visibility'])
const show = ref(!props.masked)
const toggle = () => { show.value = !show.value; emit('toggle-visibility', show.value) }
const fmt = (n) => new Intl.NumberFormat('en-NG', { maximumFractionDigits: 2 }).format(n)
const displayBalance = computed(() => show.value ? `${props.symbol}${fmt(props.balance)}` : '••••••')
</script>

<template>
  <Card as="section" padding="lg">
    <div class="relative overflow-hidden rounded-2xl bg-[#0E1116] text-white">
      <div class="absolute inset-y-0 right-0 w-1/2 pointer-events-none">
        <slot name="media">
          <div class="h-full w-full bg-[radial-gradient(80%_60%_at_70%_30%,#2C7BE5_0%,transparent_60%),radial-gradient(60%_60%_at_95%_75%,#38BDF8_0%,transparent_55%)] opacity-40"></div>
        </slot>
      </div>

      <div class="relative z-10 p-6 md:p-8">
        <div class="flex items-start justify-between gap-4">
          <div>
            <div class="flex items-center gap-2 text-sm text-white/70">
              <span>Total Balance</span>
              <button class="inline-flex items-center justify-center w-8 h-8 rounded-full hover:bg-white/10"
                      :aria-label="show ? 'Hide balance' : 'Show balance'"
                      @click="toggle">
                <component :is="show ? EyeOff : Eye" class="w-4 h-4" />
              </button>
              <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-white/10 text-xs">
                {{ currency }} <ChevronDown class="w-3 h-3" />
              </span>
            </div>
            <div class="mt-2 text-4xl md:text-5xl font-semibold tracking-tight">
              {{ displayBalance }}
            </div>
          </div>
          <div class="flex items-center gap-2">
            <Button v-for="a in actions" :key="a.id" size="sm" variant="outline"
                    class="border-white/20 text-white hover:bg-white/10"
                    @click="$emit('action', a.id)">
              <component :is="a.icon" class="w-4 h-4" />
              <span class="hidden sm:inline">{{ a.label }}</span>
            </Button>
          </div>
        </div>
      </div>

      <div v-if="loading" class="absolute inset-0 z-20 bg-black/40 backdrop-blur-sm flex items-center p-8">
        <div class="w-2/3 max-w-sm h-7 rounded bg-white/20 animate-pulse"></div>
      </div>
    </div>
  </Card>
</template>
