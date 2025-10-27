<script setup>
import { computed, onBeforeUnmount, ref } from 'vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import {
  ArrowDownRight,
  ArrowUpRight,
  CircleDashed,
  DollarSign,
  Image as ImageIcon,
  Pencil,
  Plus,
  ShieldCheck,
  Trash2,
  UploadCloud,
  X,
} from 'lucide-vue-next'

const props = defineProps({
  assets: {
    type: Object,
    required: true,
  },
  stats: {
    type: Object,
    default: () => ({
      totalAssets: 0,
      activeAssets: 0,
      buyCount: 0,
      sellCount: 0,
      averageRate: 0,
      averageFees: 0,
    }),
  },
  saleTypes: {
    type: Array,
    default: () => [],
  },
  meta: {
    type: Object,
    default: () => ({}),
  },
})

const showCreateModal = ref(false)
const showEditModal = ref(false)
const editingAsset = ref(null)
const createPreview = ref(null)
const editPreview = ref(null)

const defaultSaleType = props.saleTypes?.[0]?.value ?? 'buy'

const createDefaults = {
  name: '',
  symbol: '',
  rate: '',
  wallet_address: '',
  sale_type: defaultSaleType,
  charges: '',
  is_active: true,
  logo: null,
}

const editDefaults = {
  name: '',
  symbol: '',
  rate: '',
  wallet_address: '',
  sale_type: defaultSaleType,
  charges: '',
  is_active: true,
  logo: null,
}

const createForm = useForm({ ...createDefaults })
const editForm = useForm({ ...editDefaults })

createForm.defaults({ ...createDefaults })
editForm.defaults({ ...editDefaults })

const nairaFormatter = new Intl.NumberFormat('en-NG', {
  style: 'currency',
  currency: 'NGN',
})

const percentFormatter = new Intl.NumberFormat('en-US', {
  minimumFractionDigits: 0,
  maximumFractionDigits: 2,
})

const totalDirectionCount = computed(() => props.stats.buyCount + props.stats.sellCount)

const buyShare = computed(() => {
  const total = totalDirectionCount.value
  if (!total) return 0
  return Math.round((props.stats.buyCount / total) * 100)
})

const sellShare = computed(() => 100 - buyShare.value)

const resetCreateForm = () => {
  if (createPreview.value) {
    URL.revokeObjectURL(createPreview.value)
    createPreview.value = null
  }
  createForm.reset()
  createForm.clearErrors()
}

const resetEditForm = () => {
  if (editPreview.value) {
    URL.revokeObjectURL(editPreview.value)
    editPreview.value = null
  }
  editForm.reset()
  editForm.clearErrors()
  editingAsset.value = null
}

const openCreateModal = () => {
  resetCreateForm()
  showCreateModal.value = true
}

const closeCreateModal = () => {
  resetCreateForm()
  showCreateModal.value = false
}

const openEditModal = (asset) => {
  if (editPreview.value) {
    URL.revokeObjectURL(editPreview.value)
    editPreview.value = null
  }
  editingAsset.value = asset
  editForm.clearErrors()
  editForm.name = asset.name ?? ''
  editForm.symbol = asset.symbol ?? ''
  editForm.rate =
    asset.rate !== undefined && asset.rate !== null ? String(asset.rate) : ''
  editForm.wallet_address = asset.wallet_address ?? ''
  editForm.sale_type = asset.sale_type ?? defaultSaleType
  editForm.charges =
    asset.charges !== undefined && asset.charges !== null
      ? String(asset.charges)
      : ''
  editForm.is_active = asset.is_active ?? true
  editForm.logo = null
  showEditModal.value = true
}

const closeEditModal = () => {
  resetEditForm()
  showEditModal.value = false
}

const handleFileChange = (form, event, previewRef) => {
  const [file] = event.target.files || []
  form.logo = file ?? null
  if (previewRef?.value) {
    URL.revokeObjectURL(previewRef.value)
  }
  if (previewRef) {
    previewRef.value = file ? URL.createObjectURL(file) : null
  }
}

const transformPayload = (data, { pruneLogo } = { pruneLogo: false }) => {
  const payload = {
    ...data,
    is_active: data.is_active ? 1 : 0,
  }

  if (pruneLogo && !payload.logo) {
    delete payload.logo
  }

  return payload
}

const submitCreate = () => {
  createForm.symbol = createForm.symbol?.toUpperCase() ?? ''
  createForm.transform((data) => transformPayload(data, { pruneLogo: true }))
  createForm.post('/admin/crypto-exchange', {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => closeCreateModal(),
    onFinish: () => {
      createForm.transform((data) => data)
    },
  })
}

const submitEdit = () => {
  if (!editingAsset.value) return

  editForm.symbol = editForm.symbol?.toUpperCase() ?? ''
  editForm.transform((data) => transformPayload(data, { pruneLogo: true }))

  editForm.post(`/admin/crypto-exchange/${editingAsset.value.id}`, {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => closeEditModal(),
    onFinish: () => {
      editForm.transform((data) => data)
    },
  })
}

const destroyAsset = (asset) => {
  if (!confirm(`Delete ${asset.name}?`)) return

  router.delete(`/admin/crypto-exchange/${asset.id}`, {
    preserveScroll: true,
    onSuccess: () => {
      if (editingAsset.value?.id === asset.id) {
        closeEditModal()
      }
    },
  })
}

const saleTypeBadge = (saleType) => {
  return saleType === 'buy'
    ? 'bg-emerald-50 text-emerald-600 border border-emerald-200'
    : 'bg-sky-50 text-sky-600 border border-sky-200'
}

const statusBadge = (isActive) =>
  isActive
    ? 'bg-emerald-50 text-emerald-600 border border-emerald-200'
    : 'bg-gray-100 text-gray-500 border border-gray-200'

onBeforeUnmount(() => {
  if (createPreview.value) {
    URL.revokeObjectURL(createPreview.value)
  }
  if (editPreview.value) {
    URL.revokeObjectURL(editPreview.value)
  }
})
</script>

<template>
  <AdminLayout>
    <Head :title="meta?.title ?? 'Admin Crypto Exchange'" />

    <section class="space-y-12">
      <header class="flex flex-wrap items-start justify-between gap-4">
        <div class="space-y-3">
          <p class="text-xs font-semibold uppercase tracking-[0.35em] text-orange-500">
            Crypto Exchange
          </p>
          <h1 class="text-3xl font-semibold text-gray-900">Crypto marketplace controls</h1>
          <p class="max-w-2xl text-sm leading-relaxed text-gray-500">
            Monitor supported digital assets, configure pricing, and keep wallet routing accurate so
            operations can execute trades at speed.
          </p>
        </div>

        <button
          type="button"
          @click="openCreateModal"
          class="inline-flex items-center gap-2 rounded-xl bg-orange-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-orange-600/50 transition hover:bg-orange-500"
        >
          <Plus class="h-4 w-4" />
          New asset
        </button>
      </header>

      <section class="grid gap-6 sm:grid-cols-2 xl:grid-cols-4">
        <article class="rounded-3xl border border-gray-100 bg-white p-6 shadow-sm shadow-gray-100/40">
          <div class="flex items-center justify-between">
            <p class="text-xs font-semibold uppercase tracking-widest text-gray-400">
              Active listings
            </p>
            <ShieldCheck class="h-5 w-5 text-orange-500" />
          </div>
          <p class="mt-4 text-3xl font-semibold text-gray-900">
            {{ stats.activeAssets.toLocaleString() }} /
            <span class="text-base font-medium text-gray-400">{{
              stats.totalAssets.toLocaleString()
            }}</span>
          </p>
          <p class="mt-1 text-xs text-gray-400">Assets currently tradable in the marketplace.</p>
        </article>

        <article class="rounded-3xl border border-gray-100 bg-white p-6 shadow-sm shadow-gray-100/40">
          <div class="flex items-center justify-between">
            <p class="text-xs font-semibold uppercase tracking-widest text-gray-400">
              Average rate
            </p>
            <DollarSign class="h-5 w-5 text-orange-500" />
          </div>
          <p class="mt-4 text-3xl font-semibold text-gray-900">
            {{ nairaFormatter.format(stats.averageRate || 0) }}
          </p>
          <p class="mt-1 text-xs text-gray-400">Mean NGN exchange rate across configured assets.</p>
        </article>

        <article class="rounded-3xl border border-gray-100 bg-white p-6 shadow-sm shadow-gray-100/40">
          <div class="flex items-center justify-between">
            <p class="text-xs font-semibold uppercase tracking-widest text-gray-400">
              Trade mix
            </p>
            <ArrowUpRight class="h-5 w-5 text-emerald-500" />
          </div>
          <p class="mt-4 text-3xl font-semibold text-gray-900">
            {{ buyShare }}% /
            <span class="text-base font-medium text-gray-400">{{ sellShare }}%</span>
          </p>
          <p class="mt-1 text-xs text-gray-400">Buy vs sell listing distribution.</p>
        </article>

        <article class="rounded-3xl border border-gray-100 bg-white p-6 shadow-sm shadow-gray-100/40">
          <div class="flex items-center justify-between">
            <p class="text-xs font-semibold uppercase tracking-widest text-gray-400">
              Average fees
            </p>
            <ArrowDownRight class="h-5 w-5 text-sky-500" />
          </div>
          <p class="mt-4 text-3xl font-semibold text-gray-900">
            {{ percentFormatter.format(stats.averageFees || 0) }}%
          </p>
          <p class="mt-1 text-xs text-gray-400">Current blended fee across all active assets.</p>
        </article>
      </section>

      <section
        class="mt-4 rounded-3xl border border-gray-100 bg-white/90 shadow-sm shadow-gray-100/50 backdrop-blur-sm"
      >
        <header
          class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-6 py-5"
        >
          <div>
            <h2 class="text-lg font-semibold text-gray-900">Exchange catalogue</h2>
            <p class="text-sm text-gray-500">
              Manage supported cryptocurrencies and keep wallet data accurate.
            </p>
          </div>
          <button
            type="button"
            @click="openCreateModal"
            class="inline-flex items-center gap-2 rounded-xl border border-orange-200 bg-orange-50 px-3 py-2 text-xs font-semibold text-orange-600 transition hover:bg-orange-100"
          >
            <Plus class="h-4 w-4" />
            Add asset
          </button>
        </header>

        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-100 text-left">
            <thead class="bg-gray-50/80 text-xs font-semibold uppercase tracking-wide text-gray-400">
              <tr>
                <th class="px-6 py-4">Asset</th>
                <th class="px-6 py-4">Rate</th>
                <th class="px-6 py-4">Sale type</th>
                <th class="px-6 py-4">Charges</th>
                <th class="px-6 py-4">Wallet</th>
                <th class="px-6 py-4">Status</th>
                <th class="px-6 py-4 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 text-sm text-gray-700">
              <tr
                v-for="asset in assets.data"
                :key="asset.id"
                class="transition hover:bg-orange-50/40"
              >
                <td class="px-6 py-4">
                  <div class="flex items-center gap-4">
                    <div
                      class="flex h-12 w-12 items-center justify-center rounded-xl border border-dashed border-gray-200 bg-gray-50"
                    >
                      <img
                        v-if="asset.logo_url"
                        :src="asset.logo_url"
                        :alt="`${asset.name} logo`"
                        class="h-12 w-12 rounded-xl object-cover"
                      />
                      <ImageIcon v-else class="h-5 w-5 text-gray-400" />
                    </div>
                    <div>
                      <p class="font-semibold text-gray-900">{{ asset.name }}</p>
                      <p class="text-xs text-gray-400">{{ asset.symbol }}</p>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 font-semibold text-gray-900">
                  {{ nairaFormatter.format(Number(asset.rate ?? 0)) }}
                </td>
                <td class="px-6 py-4">
                  <span
                    :class="[
                      'inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold',
                      saleTypeBadge(asset.sale_type),
                    ]"
                  >
                    {{ asset.sale_type_label }}
                  </span>
                </td>
                <td class="px-6 py-4 font-semibold text-gray-900">
                  {{ percentFormatter.format(Number(asset.charges ?? 0)) }}%
                </td>
                <td class="px-6 py-4">
                  <div class="max-w-xs truncate text-xs text-gray-500">
                    {{ asset.wallet_address }}
                  </div>
                </td>
                <td class="px-6 py-4">
                  <span
                    :class="[
                      'inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold',
                      statusBadge(asset.is_active),
                    ]"
                  >
                    <ShieldCheck v-if="asset.is_active" class="h-3.5 w-3.5" />
                    <CircleDashed v-else class="h-3.5 w-3.5" />
                    {{ asset.is_active ? 'Active' : 'Disabled' }}
                  </span>
                </td>
                <td class="px-6 py-4">
                  <div class="flex justify-end gap-2">
                    <button
                      type="button"
                      @click="openEditModal(asset)"
                      class="inline-flex items-center gap-2 rounded-xl border border-gray-200 px-3 py-1.5 text-xs font-semibold text-gray-600 transition hover:border-orange-200 hover:text-orange-600"
                    >
                      <Pencil class="h-4 w-4" />
                      Edit
                    </button>
                    <button
                      type="button"
                      @click="destroyAsset(asset)"
                      class="inline-flex items-center gap-2 rounded-xl border border-rose-200 px-3 py-1.5 text-xs font-semibold text-rose-600 transition hover:bg-rose-50"
                    >
                      <Trash2 class="h-4 w-4" />
                      Delete
                    </button>
                  </div>
                </td>
              </tr>

              <tr v-if="!assets.data.length">
                <td colspan="7" class="px-6 py-12 text-center text-sm text-gray-500">
                  No crypto assets configured yet. Start by creating one.
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <footer
          v-if="assets.links && assets.links.length > 1"
          class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-100 px-6 py-4 text-sm text-gray-500"
        >
          <span>
            Showing
            <strong>{{ assets.from ?? 0 }}-{{ assets.to ?? 0 }}</strong>
            of
            <strong>{{ assets.total ?? 0 }}</strong>
          </span>
          <nav class="flex items-center gap-2">
            <a
              v-for="link in assets.links"
              :key="link.url ?? link.label"
              :href="link.url ?? '#'"
              v-html="link.label"
              :class="[
                'rounded-lg px-3 py-1.5 text-xs font-semibold transition',
                link.active
                  ? 'bg-orange-600 text-white shadow-sm shadow-orange-600/50'
                  : 'text-gray-500 hover:bg-gray-100',
                !link.url ? 'cursor-not-allowed opacity-40' : '',
              ]"
            />
          </nav>
        </footer>
      </section>
    </section>

    <Teleport to="body">
      <transition name="fade">
        <div
          v-if="showCreateModal"
          class="fixed inset-0 z-40 bg-gray-950/40 backdrop-blur-sm"
          @click.self="closeCreateModal"
        ></div>
      </transition>
      <transition name="fade">
        <div
          v-if="showEditModal"
          class="fixed inset-0 z-40 bg-gray-950/40 backdrop-blur-sm"
          @click.self="closeEditModal"
        ></div>
      </transition>

      <transition name="scale">
        <div
          v-if="showCreateModal"
          class="fixed inset-0 z-50 flex items-center justify-center px-4"
          role="dialog"
          aria-modal="true"
        >
          <div class="mx-4 w-full max-w-3xl">
            <div
              class="flex max-h-[90vh] flex-col overflow-hidden rounded-3xl bg-white shadow-xl shadow-gray-900/10"
            >
              <header class="flex items-start justify-between gap-4 px-8 pt-8">
                <div>
                  <p class="text-xs font-semibold uppercase tracking-[0.35em] text-orange-500">
                    New asset
                  </p>
                  <h2 class="mt-1 text-2xl font-semibold text-gray-900">Create crypto asset</h2>
                <p class="mt-2 text-sm text-gray-500">
                  Add a new crypto listing, configure base rate, wallet, and fee schedule. Assets
                  marked active appear instantly in the exchange module.
                </p>
              </div>
              <button
                type="button"
                @click="closeCreateModal"
                class="rounded-full border border-gray-200 p-2 text-gray-400 transition hover:text-gray-600"
              >
                <X class="h-4 w-4" />
              </button>
              </header>

              <form
                class="mt-6 space-y-5 overflow-y-auto px-8 pb-8"
                @submit.prevent="submitCreate"
              >
                <div
                  v-if="Object.keys(createForm.errors).length"
                  class="rounded-xl border border-rose-200 bg-rose-50/70 px-4 py-3 text-sm text-rose-600"
                >
                  Please fix the highlighted fields before saving this asset.
              </div>

              <div class="grid gap-4 sm:grid-cols-2">
                <label class="space-y-2 text-sm font-semibold text-gray-700">
                  <span>Name</span>
                  <input
                    v-model="createForm.name"
                    type="text"
                    class="w-full rounded-xl border border-gray-200 px-4 py-3 text-base text-gray-700 focus:border-orange-500 focus:ring-2 focus:ring-orange-100"
                    placeholder="e.g., Bitcoin"
                  />
                  <span v-if="createForm.errors.name" class="block text-xs text-rose-600">
                    {{ createForm.errors.name }}
                  </span>
                </label>

                <label class="space-y-2 text-sm font-semibold text-gray-700">
                  <span>Symbol</span>
                  <input
                    v-model="createForm.symbol"
                    type="text"
                    class="w-full rounded-xl border border-gray-200 px-4 py-3 text-base text-gray-700 focus:border-orange-500 focus:ring-2 focus:ring-orange-100 uppercase"
                    placeholder="BTC"
                  />
                  <span v-if="createForm.errors.symbol" class="block text-xs text-rose-600">
                    {{ createForm.errors.symbol }}
                  </span>
                </label>
              </div>

              <div class="grid gap-4 sm:grid-cols-2">
                <label class="space-y-2 text-sm font-semibold text-gray-700">
                  <span>Base rate (NGN)</span>
                  <input
                    v-model="createForm.rate"
                    type="number"
                    step="0.00000001"
                    class="w-full rounded-xl border border-gray-200 px-4 py-3 text-base text-gray-700 focus:border-orange-500 focus:ring-2 focus:ring-orange-100"
                    placeholder="0.00"
                  />
                  <span v-if="createForm.errors.rate" class="block text-xs text-rose-600">
                    {{ createForm.errors.rate }}
                  </span>
                </label>

                <label class="space-y-2 text-sm font-semibold text-gray-700">
                  <span>Charges (Fee %)</span>
                  <input
                    v-model="createForm.charges"
                    type="number"
                    step="0.01"
                    class="w-full rounded-xl border border-gray-200 px-4 py-3 text-base text-gray-700 focus:border-orange-500 focus:ring-2 focus:ring-orange-100"
                    placeholder="0.00"
                  />
                  <span v-if="createForm.errors.charges" class="block text-xs text-rose-600">
                    {{ createForm.errors.charges }}
                  </span>
                </label>
              </div>

              <label class="space-y-2 text-sm font-semibold text-gray-700">
                <span>Wallet address</span>
                <input
                  v-model="createForm.wallet_address"
                  type="text"
                  class="w-full rounded-xl border border-gray-200 px-4 py-3 text-base text-gray-700 focus:border-orange-500 focus:ring-2 focus:ring-orange-100"
                  placeholder="Wallet destination for settlements"
                />
                <span v-if="createForm.errors.wallet_address" class="block text-xs text-rose-600">
                  {{ createForm.errors.wallet_address }}
                </span>
              </label>

              <div class="grid gap-4 sm:grid-cols-2">
                <div class="space-y-2 text-sm font-semibold text-gray-700">
                  <span>Sale type</span>
                  <div class="flex gap-2 rounded-xl border border-gray-200 p-2">
                    <button
                      v-for="type in saleTypes"
                      :key="type.value"
                      type="button"
                      @click="createForm.sale_type = type.value"
                      :class="[
                        'flex-1 rounded-lg px-3 py-2 text-xs font-semibold transition',
                        createForm.sale_type === type.value
                          ? 'bg-orange-600 text-white shadow-sm shadow-orange-600/40'
                          : 'bg-gray-50 text-gray-600 hover:bg-gray-100',
                      ]"
                    >
                      {{ type.label }}
                    </button>
                  </div>
                  <span v-if="createForm.errors.sale_type" class="block text-xs text-rose-600">
                    {{ createForm.errors.sale_type }}
                  </span>
                </div>

                <div class="space-y-3 text-sm font-semibold text-gray-700">
                  <span>Status</span>
                  <button
                    type="button"
                    @click="createForm.is_active = !createForm.is_active"
                    :class="[
                      'inline-flex w-full items-center justify-between rounded-xl border px-4 py-3 text-sm font-semibold transition',
                      createForm.is_active
                        ? 'border-emerald-200 bg-emerald-50 text-emerald-600'
                        : 'border-gray-200 bg-gray-50 text-gray-500',
                    ]"
                  >
                    <span>{{ createForm.is_active ? 'Active' : 'Disabled' }}</span>
                    <ShieldCheck v-if="createForm.is_active" class="h-4 w-4" />
                    <CircleDashed v-else class="h-4 w-4" />
                  </button>
                </div>
              </div>

              <label class="space-y-2 text-sm font-semibold text-gray-700">
                <span>Logo</span>
                <div
                  class="relative flex items-center justify-between gap-3 rounded-xl border border-dashed border-gray-300 bg-gray-50 px-4 py-3"
                >
                  <div class="flex items-center gap-3 text-sm text-gray-500">
                    <UploadCloud class="h-5 w-5 text-orange-500" />
                    <div>
                      <p class="font-semibold text-gray-700">Upload logo</p>
                      <p class="text-xs text-gray-400">PNG or JPG up to 2MB. Prefer square artwork.</p>
                    </div>
                  </div>
                  <label
                    class="inline-flex cursor-pointer items-center gap-2 rounded-lg bg-orange-500/10 px-3 py-1.5 text-xs font-semibold text-orange-600 transition hover:bg-orange-500/20"
                  >
                    Browse
                    <input
                      type="file"
                      accept="image/*"
                      class="hidden"
                      @change="handleFileChange(createForm, $event, createPreview)"
                    />
                  </label>
                </div>
                <span v-if="createForm.errors.logo" class="block text-xs text-rose-600">
                  {{ createForm.errors.logo }}
                </span>
              </label>

              <div
                v-if="createPreview"
                class="flex items-center gap-3 rounded-xl border border-orange-200 bg-orange-50/60 p-3 text-xs text-orange-600"
              >
                <img
                  :src="createPreview"
                  alt="New crypto logo preview"
                  class="h-14 w-14 rounded-xl object-cover"
                />
                  Preview of the image you just selected.
                </div>

                <footer class="flex items-center justify-end gap-3 pt-2">
                  <button
                  type="button"
                  @click="closeCreateModal"
                  class="rounded-xl border border-gray-200 px-5 py-2.5 text-sm font-semibold text-gray-600 transition hover:border-orange-200 hover:text-orange-600"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  class="rounded-xl bg-orange-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm shadow-orange-600/50 transition hover:bg-orange-500 disabled:opacity-60"
                    :disabled="createForm.processing"
                  >
                    Save asset
                  </button>
                </footer>
              </form>
            </div>
          </div>
        </div>
      </transition>

      <transition name="scale">
        <div
          v-if="showEditModal"
          class="fixed inset-0 z-50 flex items-center justify-center px-4"
          role="dialog"
          aria-modal="true"
        >
          <div class="mx-4 w-full max-w-3xl">
            <div
              class="flex max-h-[90vh] flex-col overflow-hidden rounded-3xl bg-white shadow-xl shadow-gray-900/10"
            >
              <header class="flex items-start justify-between gap-4 px-8 pt-8">
                <div>
                  <p class="text-xs font-semibold uppercase tracking-[0.35em] text-orange-500">
                    Edit asset
                  </p>
                  <h2 class="mt-1 text-2xl font-semibold text-gray-900">
                  Update {{ editingAsset?.name }}
                </h2>
                <p class="mt-2 text-sm text-gray-500">
                  Tweak the exchange configuration or disable trading without removing historical
                  data.
                </p>
              </div>
              <button
                type="button"
                @click="closeEditModal"
                class="rounded-full border border-gray-200 p-2 text-gray-400 transition hover:text-gray-600"
              >
                <X class="h-4 w-4" />
              </button>
              </header>

              <form
                class="mt-6 space-y-5 overflow-y-auto px-8 pb-8"
                @submit.prevent="submitEdit"
              >
                <div
                  v-if="Object.keys(editForm.errors).length"
                  class="rounded-xl border border-rose-200 bg-rose-50/70 px-4 py-3 text-sm text-rose-600"
                >
                  Please resolve the highlighted fields before saving changes.
              </div>

              <div class="grid gap-4 sm:grid-cols-2">
                <label class="space-y-2 text-sm font-semibold text-gray-700">
                  <span>Name</span>
                  <input
                    v-model="editForm.name"
                    type="text"
                    class="w-full rounded-xl border border-gray-200 px-4 py-3 text-base text-gray-700 focus:border-orange-500 focus:ring-2 focus:ring-orange-100"
                    placeholder="e.g., Bitcoin"
                  />
                  <span v-if="editForm.errors.name" class="block text-xs text-rose-600">
                    {{ editForm.errors.name }}
                  </span>
                </label>

                <label class="space-y-2 text-sm font-semibold text-gray-700">
                  <span>Symbol</span>
                  <input
                    v-model="editForm.symbol"
                    type="text"
                    class="w-full rounded-xl border border-gray-200 px-4 py-3 text-base text-gray-700 focus:border-orange-500 focus:ring-2 focus:ring-orange-100 uppercase"
                    placeholder="BTC"
                  />
                  <span v-if="editForm.errors.symbol" class="block text-xs text-rose-600">
                    {{ editForm.errors.symbol }}
                  </span>
                </label>
              </div>

              <div class="grid gap-4 sm:grid-cols-2">
                <label class="space-y-2 text-sm font-semibold text-gray-700">
                  <span>Base rate (NGN)</span>
                  <input
                    v-model="editForm.rate"
                    type="number"
                    step="0.00000001"
                    class="w-full rounded-xl border border-gray-200 px-4 py-3 text-base text-gray-700 focus:border-orange-500 focus:ring-2 focus:ring-orange-100"
                    placeholder="0.00"
                  />
                  <span v-if="editForm.errors.rate" class="block text-xs text-rose-600">
                    {{ editForm.errors.rate }}
                  </span>
                </label>

                <label class="space-y-2 text-sm font-semibold text-gray-700">
                  <span>Charges (Fee %)</span>
                  <input
                    v-model="editForm.charges"
                    type="number"
                    step="0.01"
                    class="w-full rounded-xl border border-gray-200 px-4 py-3 text-base text-gray-700 focus:border-orange-500 focus:ring-2 focus:ring-orange-100"
                    placeholder="0.00"
                  />
                  <span v-if="editForm.errors.charges" class="block text-xs text-rose-600">
                    {{ editForm.errors.charges }}
                  </span>
                </label>
              </div>

              <label class="space-y-2 text-sm font-semibold text-gray-700">
                <span>Wallet address</span>
                <input
                  v-model="editForm.wallet_address"
                  type="text"
                  class="w-full rounded-xl border border-gray-200 px-4 py-3 text-base text-gray-700 focus:border-orange-500 focus:ring-2 focus:ring-orange-100"
                  placeholder="Wallet destination for settlements"
                />
                <span v-if="editForm.errors.wallet_address" class="block text-xs text-rose-600">
                  {{ editForm.errors.wallet_address }}
                </span>
              </label>

              <div class="grid gap-4 sm:grid-cols-2">
                <div class="space-y-2 text-sm font-semibold text-gray-700">
                  <span>Sale type</span>
                  <div class="flex gap-2 rounded-xl border border-gray-200 p-2">
                    <button
                      v-for="type in saleTypes"
                      :key="`edit-sale-${type.value}`"
                      type="button"
                      @click="editForm.sale_type = type.value"
                      :class="[
                        'flex-1 rounded-lg px-3 py-2 text-xs font-semibold transition',
                        editForm.sale_type === type.value
                          ? 'bg-orange-600 text-white shadow-sm shadow-orange-600/40'
                          : 'bg-gray-50 text-gray-600 hover:bg-gray-100',
                      ]"
                    >
                      {{ type.label }}
                    </button>
                  </div>
                  <span v-if="editForm.errors.sale_type" class="block text-xs text-rose-600">
                    {{ editForm.errors.sale_type }}
                  </span>
                </div>

                <div class="space-y-3 text-sm font-semibold text-gray-700">
                  <span>Status</span>
                  <button
                    type="button"
                    @click="editForm.is_active = !editForm.is_active"
                    :class="[
                      'inline-flex w-full items-center justify-between rounded-xl border px-4 py-3 text-sm font-semibold transition',
                      editForm.is_active
                        ? 'border-emerald-200 bg-emerald-50 text-emerald-600'
                        : 'border-gray-200 bg-gray-50 text-gray-500',
                    ]"
                  >
                    <span>{{ editForm.is_active ? 'Active' : 'Disabled' }}</span>
                    <ShieldCheck v-if="editForm.is_active" class="h-4 w-4" />
                    <CircleDashed v-else class="h-4 w-4" />
                  </button>
                </div>
              </div>

              <label class="space-y-2 text-sm font-semibold text-gray-700">
                <span>Logo</span>
                <div
                  class="relative flex items-center justify-between gap-3 rounded-xl border border-dashed border-gray-300 bg-gray-50 px-4 py-3"
                >
                  <div class="flex items-center gap-3 text-sm text-gray-500">
                    <UploadCloud class="h-5 w-5 text-orange-500" />
                    <div>
                      <p class="font-semibold text-gray-700">Upload new logo</p>
                      <p class="text-xs text-gray-400">Leave blank to retain the current artwork.</p>
                    </div>
                  </div>
                  <label
                    class="inline-flex cursor-pointer items-center gap-2 rounded-lg bg-orange-500/10 px-3 py-1.5 text-xs font-semibold text-orange-600 transition hover:bg-orange-500/20"
                  >
                    Browse
                    <input
                      type="file"
                      accept="image/*"
                      class="hidden"
                      @change="handleFileChange(editForm, $event, editPreview)"
                    />
                  </label>
                </div>
                <span v-if="editForm.errors.logo" class="block text-xs text-rose-600">
                  {{ editForm.errors.logo }}
                </span>
              </label>

              <div
                v-if="editPreview"
                class="flex items-center gap-3 rounded-xl border border-orange-200 bg-orange-50/60 p-3 text-xs text-orange-600"
              >
                <img
                  :src="editPreview"
                  alt="Replacement crypto logo preview"
                  class="h-14 w-14 rounded-xl object-cover"
                />
                Preview of the new logo that will replace the current artwork.
              </div>

              <div
                v-if="editingAsset?.logo_url && !editPreview"
                class="flex items-center gap-3 rounded-xl border border-gray-100 bg-white p-3 text-xs text-gray-500"
              >
                <img
                  :src="editingAsset.logo_url"
                  :alt="editingAsset.name"
                  class="h-12 w-12 rounded-xl object-cover"
                />
                Current logo preview
              </div>

              <footer class="flex items-center justify-end gap-3 pt-2">
                <button
                  type="button"
                  @click="closeEditModal"
                  class="rounded-xl border border-gray-200 px-5 py-2.5 text-sm font-semibold text-gray-600 transition hover:border-orange-200 hover:text-orange-600"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  class="rounded-xl bg-orange-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm shadow-orange-600/50 transition hover:bg-orange-500 disabled:opacity-60"
                    :disabled="editForm.processing"
                  >
                    Save changes
                  </button>
                </footer>
              </form>
            </div>
          </div>
        </div>
      </transition>
    </Teleport>
  </AdminLayout>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
.scale-enter-active,
.scale-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}
.scale-enter-from,
.scale-leave-to {
  opacity: 0;
  transform: scale(0.98);
}
</style>
