<script setup>
import { ref, watch } from 'vue'
import Card from '@/Components/ui/Card.vue'
import { KeyRound, Hash, ShieldCheck, MonitorCheck } from 'lucide-vue-next'
import ChangePasswordModal from '@/Components/settings/modals/ChangePasswordModal.vue'
import ResetPinModal from '@/Components/settings/modals/ResetPinModal.vue'

const pwdOpen = ref(false)
const pinOpen = ref(false)

const twoFA = ref(JSON.parse(localStorage.getItem('xpm_twofa') || 'false'))
const trusted = ref(JSON.parse(localStorage.getItem('xpm_trusted') || 'false'))
watch(twoFA, v => localStorage.setItem('xpm_twofa', JSON.stringify(v)))
watch(trusted, v => localStorage.setItem('xpm_trusted', JSON.stringify(v)))
</script>

<template>
  <Card class="p-5">
    <div class="text-sm font-medium text-gray-800 mb-4">Security Settings</div>

    <div class="space-y-3">
      <button
        class="w-full rounded-2xl bg-gray-50 border border-gray-200 px-4 py-3 flex items-center justify-between hover:bg-gray-100"
        @click="pwdOpen = true"
      >
        <div class="flex items-center gap-3">
          <KeyRound class="w-5 h-5 text-gray-700" />
          <span class="font-medium text-gray-800">Change Password</span>
        </div>
        <span class="text-gray-400">›</span>
      </button>

      <button
        class="w-full rounded-2xl bg-gray-50 border border-gray-200 px-4 py-3 flex items-center justify-between hover:bg-gray-100"
        @click="pinOpen = true"
      >
        <div class="flex items-center gap-3">
          <Hash class="w-5 h-5 text-gray-700" />
          <span class="font-medium text-gray-800">Reset PIN</span>
        </div>
        <span class="text-gray-400">›</span>
      </button>

      <div class="w-full rounded-2xl bg-gray-50 border border-gray-200 px-4 py-3 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <ShieldCheck class="w-5 h-5 text-gray-700" />
          <span class="font-medium text-gray-800">2FA Settings</span>
        </div>
        <label class="inline-flex items-center cursor-pointer">
          <input type="checkbox" v-model="twoFA" class="sr-only peer" />
          <span class="w-10 h-6 bg-gray-300 rounded-full peer-checked:bg-green-500 relative after:absolute after:top-[2px] after:left-[2px] after:w-5 after:h-5 after:bg-white after:rounded-full after:transition-all peer-checked:after:translate-x-4"></span>
        </label>
      </div>

      <div class="w-full rounded-2xl bg-gray-50 border border-gray-200 px-4 py-3 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <MonitorCheck class="w-5 h-5 text-gray-700" />
          <span class="font-medium text-gray-800">Trust This Device</span>
        </div>
        <label class="inline-flex items-center cursor-pointer">
          <input type="checkbox" v-model="trusted" class="sr-only peer" />
          <span class="w-10 h-6 bg-gray-300 rounded-full peer-checked:bg-green-500 relative after:absolute after:top-[2px] after:left-[2px] after:w-5 after:h-5 after:bg-white after:rounded-full after:transition-all peer-checked:after:translate-x-4"></span>
        </label>
      </div>
    </div>

    <!-- Modals -->
    <ChangePasswordModal :open="pwdOpen" @close="pwdOpen=false" />
    <ResetPinModal :open="pinOpen" @close="pinOpen=false" />
  </Card>
</template>
