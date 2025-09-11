<script setup>
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'

const showPassword = ref(false)

const form = useForm({
  username: '',
  email: '',
  phone: '',
  password: '',
})

const submit = () => {
  form.post(route('login'), {
    onFinish: () => form.reset('password'),
  })
}
</script>

<template>
  <div class="flex min-h-screen bg-white">
    <!-- Left form section -->
    <div class="w-full md:w-1/2 flex items-center justify-center px-8 md:px-16">
      <div class="max-w-md w-full">
        <!-- Header -->
        <h2 class="text-2xl font-bold text-gray-900 mb-1">Login</h2>
        <p class="text-sm text-gray-500 mb-8">Be sure to provide correct details</p>

        <!-- Form -->
        <form @submit.prevent="submit" class="space-y-5">
          <!-- Email -->
          <div>
            <input v-model="form.email" type="email" placeholder="Enter Email"
                   class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-orange-500 outline-none" />
            <div v-if="form.errors.email" class="text-sm text-red-500 mt-1">{{ form.errors.email }}</div>
          </div>

          <!-- Password -->
          <div>
            <div class="relative">
              <input v-model="form.password" :type="showPassword ? 'text' : 'password'" placeholder="Enter Password"
                     class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-orange-500 outline-none" />
              <button type="button" @click="showPassword = !showPassword"
                      class="absolute inset-y-0 right-4 text-gray-500 text-sm">
                {{ showPassword ? 'Hide' : 'Show' }}
              </button>
            </div>
            <div v-if="form.errors.password" class="text-sm text-red-500 mt-1">{{ form.errors.password }}</div>
          </div>

          <!-- Submit Button -->
          <button type="submit"
                  :disabled="form.processing"
                  :class="[
                    'w-full py-3 rounded-lg font-semibold transition-colors',
                    form.processing ? 'bg-gray-200 text-gray-500 cursor-not-allowed' : 'bg-orange-500 text-white hover:bg-orange-600'
                  ]">
            <span v-if="form.processing">Processing...</span>
            <span v-else>Sign In</span>
          </button>
        </form>

        <!-- Divider -->
        <div class="flex items-center my-6">
          <hr class="flex-grow border-gray-300" />
          <span class="px-3 text-sm text-gray-500">or Sign In</span>
          <hr class="flex-grow border-gray-300" />
        </div>

        <!-- Google Button -->
        <button type="button" class="w-full flex items-center justify-center gap-3 py-3 border border-gray-300 rounded-lg hover:bg-gray-50">
          <img src="https://www.svgrepo.com/show/475656/google-color.svg" alt="Google" class="w-5 h-5"/>
          Continue with Google
        </button>

        <!-- Login Link -->
        <p class="text-sm text-center text-gray-500 mt-6">
          Don't have an account?
          <a :href="route('login')" class="text-orange-500 hover:underline">Sign Up</a>
        </p>
      </div>
    </div>

    <!-- Right graphic section -->
    <div class="hidden md:flex w-1/2 bg-black relative items-center justify-center rounded-l-3xl overflow-hidden">
      <!-- Background -->
      <img src="https://images.unsplash.com/photo-1604079628040-94307c2bcf77?auto=format&fit=crop&w=900&q=80"
           alt="bg" class="absolute inset-0 w-full h-full object-cover opacity-70" />

      <!-- Overlay logo -->
      <div class="absolute top-6 right-6 text-white font-semibold flex items-center gap-2">
        <span class="text-orange-500 font-bold text-xl">X</span> XPM Exchange
      </div>

      <!-- Stats card -->
      <div class="absolute bottom-8 left-8 bg-black/70 text-white p-5 rounded-xl space-y-4 w-72 shadow-lg">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3">
            <img src="https://cryptologos.cc/logos/tether-usdt-logo.png" alt="usdt" class="w-7 h-7" />
            <div>
              <p class="font-semibold">USDT</p>
              <p class="text-xs text-gray-400">PNL for today</p>
            </div>
          </div>
          <div class="text-right">
            <p class="text-sm">+0.0021</p>
            <p class="text-green-500 text-sm font-medium">+72.5%</p>
          </div>
        </div>

        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3">
            <img src="https://cryptologos.cc/logos/ethereum-eth-logo.png" alt="eth" class="w-7 h-7" />
            <div>
              <p class="font-semibold">ETH</p>
              <p class="text-xs text-gray-400">PNL for today</p>
            </div>
          </div>
          <div class="text-right">
            <p class="text-sm">+0.0021</p>
            <p class="text-green-500 text-sm font-medium">+72.5%</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
