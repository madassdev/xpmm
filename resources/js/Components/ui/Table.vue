<template>
  <div class="overflow-hidden border border-gray-200 rounded-xl bg-white">
    <table class="min-w-full divide-y divide-gray-200">
      <thead class="bg-gray-50">
        <tr>
          <th v-for="col in columns" :key="col.key"
              :class="['px-4 py-3 text-left text-xs font-semibold text-gray-600', alignClass(col.align)]">
            <button v-if="col.sortable" class="inline-flex items-center gap-1 hover:text-gray-900"
                    @click="$emit('sort', col)">
              {{ col.header }}
              <svg class="w-3 h-3" viewBox="0 0 20 20" fill="currentColor"><path d="M10 6l4 4H6l4-4z"/></svg>
            </button>
            <span v-else>{{ col.header }}</span>
          </th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-200">
        <template v-if="loading">
          <SkeletonRow v-for="n in skeletonRows" :key="n" :colspan="columns.length" />
        </template>
        <template v-else-if="rows && rows.length">
          <tr v-for="(row, idx) in rows" :key="rowKey ? row[rowKey] : idx"
              class="hover:bg-gray-50">
            <td v-for="col in columns" :key="col.key"
                :class="['px-4 py-3 text-sm text-gray-700', alignClass(col.align)]">
              <slot :name="`cell:${col.key}`" :row="row" :value="row[col.key]">
                {{ row[col.key] }}
              </slot>
            </td>
          </tr>
        </template>
        <tr v-else>
          <td :colspan="columns.length" class="p-8">
            <EmptyState :title="empty?.title || 'No data'"
                        :subtitle="empty?.subtitle || 'Nothing to show yet.'" />
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
<script setup>
import EmptyState from './EmptyState.vue'
import SkeletonRow from './SkeletonRow.vue'
const props = defineProps({
  columns: { type: Array, required: true },
  rows: { type: Array, default: () => [] },
  rowKey: { type: String, default: null },
  loading: { type: Boolean, default: false },
  skeletonRows: { type: Number, default: 4 },
  empty: { type: Object, default: null }
})
const alignClass = (align) => align === 'right' ? 'text-right' : align === 'center' ? 'text-center' : 'text-left'
</script>
