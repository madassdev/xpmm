<script setup>
import { ref, computed } from 'vue'
import Card from '@/Components/ui/Card.vue'
import { Pencil, ChevronDown, Check } from 'lucide-vue-next'
import Button from '@/Components/ui/Button.vue'

/**
 * Props – feed these from server/page props later
 */
const props = defineProps({
  initialName: { type: String, default: 'Mustapha Sanusi' },
  initialEmail: { type: String, default: 'muztirsan@gmail.com' },
  initialPhone: { type: String, default: '07082137552' },
  initialUsername: { type: String, default: 'q71rrdf1' },
  avatar: { type: String, default: '/img/avatar-placeholder.png' },
})

/**
 * Local state
 */
const name = ref(props.initialName)
const email = ref(props.initialEmail)
const phone = ref(props.initialPhone)
const username = ref(props.initialUsername)

const canEditEmail = ref(false)
const canEditPhone = ref(false)

const changed = computed(() =>
  name.value !== props.initialName ||
  email.value !== props.initialEmail ||
  phone.value !== props.initialPhone ||
  username.value !== props.initialUsername
)

function toggleEmail() { canEditEmail.value = !canEditEmail.value }
function togglePhone() { canEditPhone.value = !canEditPhone.value }

function save() {
  // Wire this to Inertia useForm later; UI only for now
}
</script>

<template>
  <Card class="p-6">
    <!-- Heading -->
    <h3 class="text-[15px] font-semibold text-[#0E3A63] mb-5">Edit Profile</h3>

    <!-- Avatar row -->
    <div class="mb-6">
      <div class="relative w-20 h-20">
        <img :src="avatar" alt="avatar"
             class="w-20 h-20 rounded-full object-cover ring-2 ring-white shadow-md" />
        <button
          type="button"
          class="absolute -bottom-1 -right-1 w-8 h-8 rounded-full bg-white border border-orange-300 grid place-items-center shadow
                 outline-none focus:outline-none focus-visible:ring-2 focus-visible:ring-orange-300"
          title="Change photo"
        >
          <Pencil class="w-4 h-4 text-orange-500" />
        </button>
      </div>
    </div>

    <!-- Form fields -->
    <div class="space-y-4">
      <!-- Full Name -->
      <div>
        <label class="block text-[11px] uppercase tracking-wide text-gray-500 mb-1">Full Name</label>
        <div class="rounded-[18px] bg-[#F7F8FA] border border-[#E7EBF2] px-4 py-3">
          <input v-model="name"
                 class="w-full bg-transparent text-[15px] text-gray-800 placeholder-gray-400 focus:outline-none" />
        </div>
      </div>

      <!-- Email (right pencil) -->
      <div>
        <label class="block text-[11px] uppercase tracking-wide text-gray-500 mb-1">Email</label>
        <div class="relative rounded-[18px] bg-[#F7F8FA] border border-[#E7EBF2] px-4 py-3">
          <input :disabled="!canEditEmail" v-model="email"
                 class="w-full pr-10 bg-transparent text-[15px] text-gray-800 placeholder-gray-400
                        focus:outline-none disabled:opacity-70" />
          <button
            type="button"
            @click="toggleEmail"
            class="absolute right-2 top-1/2 -translate-y-1/2 w-9 h-9 grid place-items-center rounded-lg
                   bg-white/70 border border-orange-200 text-orange-500 hover:bg-white
                   outline-none focus:outline-none focus-visible:ring-2 focus-visible:ring-orange-300"
            title="Edit email"
          >
            <Pencil class="w-4 h-4" />
          </button>
        </div>
      </div>

      <!-- Phone (flag pill + green verify + right pencil) -->
      <div>
        <label class="block text-[11px] uppercase tracking-wide text-gray-500 mb-1">Phone Number</label>
        <div class="relative flex items-center gap-3 rounded-[18px] bg-[#F7F8FA] border border-[#E7EBF2] px-3 py-2.5">
          <!-- Flag pill -->
          <button type="button"
                  class="shrink-0 inline-flex items-center gap-1.5 rounded-xl bg-white border border-[#E7EBF2] px-3 py-2
                         outline-none focus:outline-none focus-visible:ring-2 focus-visible:ring-orange-300">
            <span class="text-lg leading-none">🇳🇬</span>
            <ChevronDown class="w-4 h-4 text-gray-500" />
          </button>

          <!-- Phone input -->
          <input :disabled="!canEditPhone" v-model="phone" inputmode="tel"
                 class="flex-1 bg-transparent text-[15px] text-gray-800 placeholder-gray-400
                        focus:outline-none disabled:opacity-70" />

          <!-- Green verify chip -->
          <div class="shrink-0 w-9 h-9 rounded-full bg-green-100 border border-green-200 grid place-items-center">
            <Check class="w-4 h-4 text-green-600" />
          </div>

          <!-- Right pencil -->
          <button
            type="button"
            @click="togglePhone"
            class="absolute right-2 top-1/2 -translate-y-1/2 w-9 h-9 grid place-items-center rounded-lg
                   bg-white/70 border border-orange-200 text-orange-500 hover:bg-white
                   outline-none focus:outline-none focus-visible:ring-2 focus-visible:ring-orange-300"
            title="Edit phone"
          >
            <Pencil class="w-4 h-4" />
          </button>
        </div>
      </div>

      <!-- Username -->
      <div>
        <label class="block text-[11px] uppercase tracking-wide text-gray-500 mb-1">Username</label>
        <div class="rounded-[18px] bg-[#F7F8FA] border border-[#E7EBF2] px-4 py-3">
          <input v-model="username"
                 class="w-full bg-transparent text-[15px] text-gray-800 placeholder-gray-400 focus:outline-none" />
        </div>
      </div>
    </div>

    <!-- Save button (disabled like mock) -->
    <div class="mt-6">
      <button
        class="w-full rounded-[18px] px-5 py-3 font-medium
               bg-[#F1F2F5] text-gray-400 cursor-not-allowed"
        :class="changed ? 'cursor-pointer bg-primary text-white hover:opacity-90' : ''"
        :disabled="!changed"
        @click="changed && save()"
      >
        Save Changes
      </button>
      <!-- If you prefer using your <Button/>:
      <Button class="w-full mt-6" :disabled="!changed">Save Changes</Button>
      -->
    </div>
  </Card>
</template>
