<script setup>
import Card from '@/Components/ui/Card.vue'
import { MessageSquare, Phone, MessageCircle } from 'lucide-vue-next'
</script>

<template>
  <Card class="p-5">
    <div class="text-sm font-medium text-gray-800 mb-4">Support</div>

    <div class="space-y-3">
      <div class="rounded-2xl bg-gray-50 border border-gray-200 px-4 py-3 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <MessageSquare class="w-5 h-5 text-gray-700" />
          <div>
            <div class="font-medium text-gray-800">Live Chat</div>
          </div>
        </div>
        <button class="px-3 py-1.5 rounded-xl bg-orange-100 text-orange-700 text-sm">Edit</button>
      </div>

      <div class="rounded-2xl bg-gray-50 border border-gray-200 px-4 py-3 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <MessageCircle class="w-5 h-5 text-gray-700" />
          <div>
            <div class="font-medium text-gray-800">WhatsApp Chat</div>
            <div class="text-xs text-gray-500">+2347082137552</div>
          </div>
        </div>
        <button class="px-3 py-1.5 rounded-xl bg-orange-100 text-orange-700 text-sm">Edit</button>
      </div>

      <div class="rounded-2xl bg-gray-50 border border-gray-200 px-4 py-3 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <Phone class="w-5 h-5 text-gray-700" />
          <div>
            <div class="font-medium text-gray-800">Phone Call</div>
            <div class="text-xs text-gray-500">+2347082137552</div>
          </div>
        </div>
        <button class="px-3 py-1.5 rounded-xl bg-orange-100 text-orange-700 text-sm">Edit</button>
      </div>
    </div>
  </Card>
</template>
