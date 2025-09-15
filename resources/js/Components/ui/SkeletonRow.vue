<template>
  <tr>
    <td :colspan="colspan" class="px-4 py-3">
      <div class="h-4 w-full animate-pulse rounded bg-gray-100"></div>
    </td>
  </tr>
</template>
<script setup>
const props = defineProps({ colspan: { type: Number, required: true } })
</script>
