<script setup>
import { computed, ref, onBeforeUnmount } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import {
  ArrowRightLeft,
  CreditCard,
  Globe2,
  Image as ImageIcon,
  Pencil,
  Plus,
  Tags,
  Trash2,
  UploadCloud,
  X,
} from 'lucide-vue-next'

const props = defineProps({
  giftcards: {
    type: Object,
    required: true,
  },
  countries: {
    type: Array,
    default: () => [],
  },
  valueOptions: {
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
const editingGiftcard = ref(null)
const createPreview = ref(null)
const editPreview = ref(null)

const createForm = useForm({
  name: '',
  rate: '',
  available_regions: [],
  available_values: [],
  image: null,
})

const editForm = useForm({
  name: '',
  rate: '',
  available_regions: [],
  available_values: [],
  image: null,
})

const regionLookup = computed(() =>
  Object.fromEntries(props.countries.map((country) => [country.value, country.label]))
)

const nairaFormatter = new Intl.NumberFormat('en-NG', {
  style: 'currency',
  currency: 'NGN',
})

const stats = computed(() => {
  const cards = props.giftcards?.data ?? []
  const totalBrands = cards.length
  const averageRate =
    totalBrands > 0
      ? cards.reduce((sum, card) => sum + Number(card.rate ?? 0), 0) / totalBrands
      : 0

  const uniqueRegions = new Set()
  const uniqueDenominations = new Set()

  cards.forEach((card) => {
    ;(card.available_regions ?? []).forEach((region) => uniqueRegions.add(region))
    ;(card.available_values ?? []).forEach((value) => uniqueDenominations.add(value))
  })

  return {
    totalBrands,
    averageRate,
    regionsCount: uniqueRegions.size,
    denominationsCount: uniqueDenominations.size,
  }
})

const resetCreateForm = () => {
  createForm.reset()
  createForm.clearErrors()
  createForm.image = null
  if (createPreview.value) {
    URL.revokeObjectURL(createPreview.value)
    createPreview.value = null
  }
}

const resetEditForm = () => {
  editForm.reset()
  editForm.clearErrors()
  editForm.image = null
  editingGiftcard.value = null
  if (editPreview.value) {
    URL.revokeObjectURL(editPreview.value)
    editPreview.value = null
  }
}

const openCreateModal = () => {
  resetCreateForm()
  showCreateModal.value = true
}

const closeCreateModal = () => {
  resetCreateForm()
  showCreateModal.value = false
}

const openEditModal = (giftcard) => {
  editingGiftcard.value = giftcard
  if (editPreview.value) {
    URL.revokeObjectURL(editPreview.value)
    editPreview.value = null
  }
  editForm.name = giftcard.name
  editForm.rate = giftcard.rate
  editForm.available_regions = giftcard.available_regions ? [...giftcard.available_regions] : []
  editForm.available_values = giftcard.available_values
    ? giftcard.available_values.map((value) => value.toString())
    : []
  editForm.image = null
  editForm.clearErrors()
  showEditModal.value = true
}

const closeEditModal = () => {
  resetEditForm()
  showEditModal.value = false
}

const handleFileChange = (form, event, previewRef) => {
  const [file] = event.target.files || []
  form.image = file ?? null
  if (previewRef && previewRef.value) {
    URL.revokeObjectURL(previewRef.value)
  }
  if (previewRef) {
    previewRef.value = file ? URL.createObjectURL(file) : null
  }
}

const submitCreate = () => {
  createForm.post('/admin/giftcards', {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => closeCreateModal(),
  })
}

const submitEdit = () => {
  if (!editingGiftcard.value) return

  editForm.transform((data) => {
    const payload = { ...data }
    if (!payload.image) {
      delete payload.image
    }
    return payload
  })

  editForm.post(`/admin/giftcards/${editingGiftcard.value.id}`, {
    method: 'put',
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => closeEditModal(),
    onFinish: () => {
      editForm.transform((data) => data)
    },
  })
}

const destroyGiftcard = (giftcard) => {
  if (!confirm(`Delete ${giftcard.name}?`)) return

  router.delete(`/admin/giftcards/${giftcard.id}`, {
    preserveScroll: true,
    onSuccess: () => {
      if (editingGiftcard.value?.id === giftcard.id) {
        closeEditModal()
      }
    },
  })
}

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
    <Head :title="meta?.title ?? 'Admin Giftcards'" />

    <section class="space-y-12">
      <header class="flex flex-wrap items-start justify-between gap-4">
        <div class="space-y-3">
          <p class="text-xs font-semibold uppercase tracking-[0.35em] text-orange-500">
            Giftcards
          </p>
          <h1 class="text-3xl font-semibold text-gray-900">Giftcard performance overview</h1>
          <p class="max-w-2xl text-sm leading-relaxed text-gray-500">
            Track catalogue availability, supported regions, and pricing signals. Manage updates to
            each giftcard brand without leaving the dashboard.
          </p>
        </div>

        <div class="flex flex-wrap gap-3">
          <Link
            :href="route('admin.giftcard-transactions.index')"
            class="inline-flex items-center gap-2 rounded-xl border border-gray-200 px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:border-orange-200 hover:bg-orange-50 hover:text-orange-600"
          >
            <ArrowRightLeft class="h-4 w-4" />
            View transactions
          </Link>

          <button
            type="button"
            @click="openCreateModal"
            class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-orange-500 to-orange-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-orange-500/40 transition hover:shadow-md hover:shadow-orange-500/40"
          >
            <Plus class="h-4 w-4" />
            New giftcard
          </button>
        </div>
      </header>

      <section class="grid gap-6 sm:grid-cols-2 xl:grid-cols-4">
        <article
          class="rounded-3xl border border-gray-100 bg-white p-6 shadow-sm shadow-gray-100/40"
        >
          <div class="flex items-center justify-between">
            <p class="text-xs font-semibold uppercase tracking-widest text-gray-400">
              Active brands
            </p>
            <CreditCard class="h-5 w-5 text-orange-500" />
          </div>
          <p class="mt-4 text-3xl font-semibold text-gray-900">
            {{ stats.totalBrands.toLocaleString() }}
          </p>
          <p class="mt-1 text-xs text-gray-400">
            Total giftcard brands currently available.
          </p>
        </article>

        <article
          class="rounded-3xl border border-gray-100 bg-white p-6 shadow-sm shadow-gray-100/40"
        >
          <div class="flex items-center justify-between">
            <p class="text-xs font-semibold uppercase tracking-widest text-gray-400">
              Average rate
            </p>
            <Tags class="h-5 w-5 text-orange-500" />
          </div>
          <p class="mt-4 text-3xl font-semibold text-gray-900">
            {{ nairaFormatter.format(stats.averageRate) }}
          </p>
          <p class="mt-1 text-xs text-gray-400">
            Mean buy rate across all giftcards.
          </p>
        </article>

        <article
          class="rounded-3xl border border-gray-100 bg-white p-6 shadow-sm shadow-gray-100/40"
        >
          <div class="flex items-center justify-between">
            <p class="text-xs font-semibold uppercase tracking-widest text-gray-400">
              Supported regions
            </p>
            <Globe2 class="h-5 w-5 text-orange-500" />
          </div>
          <p class="mt-4 text-3xl font-semibold text-gray-900">
            {{ stats.regionsCount.toLocaleString() }}
          </p>
          <p class="mt-1 text-xs text-gray-400">
            Unique countries supported across the catalogue.
          </p>
        </article>

        <article
          class="rounded-3xl border border-gray-100 bg-white p-6 shadow-sm shadow-gray-100/40"
        >
          <div class="flex items-center justify-between">
            <p class="text-xs font-semibold uppercase tracking-widest text-gray-400">
              Denominations
            </p>
            <Tags class="h-5 w-5 text-orange-500" />
          </div>
          <p class="mt-4 text-3xl font-semibold text-gray-900">
            {{ stats.denominationsCount.toLocaleString() }}
          </p>
          <p class="mt-1 text-xs text-gray-400">
            Unique card values configured.
          </p>
        </article>
      </section>

      <section
        class="mt-4 rounded-3xl border border-gray-100 bg-white/90 shadow-sm shadow-gray-100/50 backdrop-blur-sm"
      >
        <header
          class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-6 py-5"
        >
          <div>
            <h2 class="text-lg font-semibold text-gray-900">Giftcard catalogue</h2>
            <p class="text-sm text-gray-500">
              Review current offerings and update their configuration instantly.
            </p>
          </div>
          <button
            type="button"
            @click="openCreateModal"
            class="inline-flex items-center gap-2 rounded-xl border border-orange-200 bg-orange-50 px-3 py-2 text-xs font-semibold text-orange-600 transition hover:bg-orange-100"
          >
            <Plus class="h-4 w-4" />
            Add giftcard
          </button>
        </header>

        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-100 text-left">
            <thead class="bg-gray-50/80 text-xs font-semibold uppercase tracking-wide text-gray-400">
              <tr>
                <th class="px-6 py-4">Giftcard</th>
                <th class="px-6 py-4">Rate</th>
                <th class="px-6 py-4">Regions</th>
                <th class="px-6 py-4">Values</th>
                <th class="px-6 py-4 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 text-sm text-gray-700">
              <tr
                v-for="giftcard in giftcards.data"
                :key="giftcard.id"
                class="transition hover:bg-orange-50/40"
              >
                <td class="px-6 py-4">
                  <div class="flex items-center gap-4">
                    <div
                      class="flex h-12 w-12 items-center justify-center rounded-xl border border-dashed border-gray-200 bg-gray-50"
                    >
                      <img
                        v-if="giftcard.image_url"
                        :src="giftcard.image_url"
                        :alt="giftcard.name"
                        class="h-12 w-12 rounded-xl object-cover"
                      />
                      <ImageIcon v-else class="h-5 w-5 text-gray-400" />
                    </div>
                    <div>
                      <p class="font-semibold text-gray-900">{{ giftcard.name }}</p>
                      <p class="text-xs text-gray-400">
                        Updated
                        {{
                          giftcard.created_at
                            ? new Date(giftcard.created_at).toLocaleDateString()
                            : '—'
                        }}
                      </p>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 font-semibold text-gray-900">
                  {{ nairaFormatter.format(Number(giftcard.rate ?? 0)) }}
                </td>
                <td class="px-6 py-4">
                  <div class="flex flex-wrap gap-2">
                    <span
                      v-for="region in giftcard.available_regions"
                      :key="region"
                      class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600"
                    >
                      {{ regionLookup[region] ?? region }}
                    </span>
                  </div>
                </td>
                <td class="px-6 py-4">
                  <div class="flex flex-wrap gap-2">
                    <span
                      v-for="value in giftcard.available_values"
                      :key="value"
                      class="inline-flex items-center rounded-full bg-orange-50 px-2.5 py-1 text-xs font-semibold text-orange-600"
                    >
                      ${{ value }}
                    </span>
                  </div>
                </td>
                <td class="px-6 py-4">
                  <div class="flex justify-end gap-2">
                    <button
                      type="button"
                      @click="openEditModal(giftcard)"
                      class="inline-flex items-center gap-2 rounded-xl border border-gray-200 px-3 py-1.5 text-xs font-semibold text-gray-600 transition hover:border-orange-200 hover:text-orange-600"
                    >
                      <Pencil class="h-4 w-4" />
                      Edit
                    </button>
                    <button
                      type="button"
                      @click="destroyGiftcard(giftcard)"
                      class="inline-flex items-center gap-2 rounded-xl border border-rose-200 px-3 py-1.5 text-xs font-semibold text-rose-600 transition hover:bg-rose-50"
                    >
                      <Trash2 class="h-4 w-4" />
                      Delete
                    </button>
                  </div>
                </td>
              </tr>

              <tr v-if="!giftcards.data.length">
                <td colspan="5" class="px-6 py-12 text-center text-sm text-gray-500">
                  No giftcards found. Create a new one to get started.
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <footer
          v-if="giftcards.links && giftcards.links.length > 1"
          class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-100 px-6 py-4 text-sm text-gray-500"
        >
          <span>
            Showing
            <strong>{{ giftcards.from ?? 0 }}-{{ giftcards.to ?? 0 }}</strong>
            of
            <strong>{{ giftcards.total ?? 0 }}</strong>
          </span>
          <nav class="flex items-center gap-2">
            <a
              v-for="link in giftcards.links"
              :key="link.url ?? link.label"
              :href="link.url ?? '#'"
              v-html="link.label"
              :class="[
                'rounded-lg px-3 py-1.5 text-xs font-semibold transition',
                link.active
                  ? 'bg-orange-500 text-white shadow-sm shadow-orange-500/30'
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
          <div class="w-full max-w-3xl mx-4 rounded-3xl bg-white p-10 shadow-xl shadow-gray-900/10">
            <header class="flex items-start justify-between gap-4">
              <div>
                <p class="text-xs font-semibold uppercase tracking-[0.35em] text-orange-500">
                  New giftcard
                </p>
                <h2 class="mt-1 text-2xl font-semibold text-gray-900">Create a giftcard</h2>
                <p class="mt-2 text-sm text-gray-500">
                  Configure regions, denominations, and upload artwork to add a new giftcard to the
                  catalogue.
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

            <form class="mt-6 space-y-5" @submit.prevent="submitCreate">
              <div
                v-if="Object.keys(createForm.errors).length"
                class="rounded-xl border border-rose-200 bg-rose-50/70 px-4 py-3 text-sm text-rose-600"
              >
                Please fix the highlighted fields before saving this giftcard.
              </div>
              <div class="grid gap-4 sm:grid-cols-2">
                <label class="space-y-2 text-sm font-semibold text-gray-700">
                  <span>Name</span>
                  <input
                    v-model="createForm.name"
                    type="text"
                    class="w-full rounded-xl border border-gray-200 px-4 py-3 text-base text-gray-700 focus:border-orange-500 focus:ring-2 focus:ring-orange-100"
                    placeholder="e.g., Amazon USA"
                  />
                  <span v-if="createForm.errors.name" class="block text-xs text-rose-600">
                    {{ createForm.errors.name }}
                  </span>
                </label>

                <label class="space-y-2 text-sm font-semibold text-gray-700">
                  <span>Rate</span>
                  <input
                    v-model="createForm.rate"
                    type="number"
                    step="0.01"
                    class="w-full rounded-xl border border-gray-200 px-4 py-3 text-base text-gray-700 focus:border-orange-500 focus:ring-2 focus:ring-orange-100"
                    placeholder="0.00"
                  />
                  <span v-if="createForm.errors.rate" class="block text-xs text-rose-600">
                    {{ createForm.errors.rate }}
                  </span>
                </label>
              </div>

              <div class="grid gap-4 sm:grid-cols-2">
                <div class="space-y-2 text-sm font-semibold text-gray-700">
                  <span>Available regions</span>
                  <div
                    :class="[
                      'grid grid-cols-2 gap-2 rounded-xl border p-3',
                      createForm.errors.available_regions
                        ? 'border-rose-200 bg-rose-50/60'
                        : 'border-gray-200',
                    ]"
                  >
                    <label
                      v-for="country in countries"
                      :key="`create-region-${country.value}`"
                      class="flex items-center gap-2 rounded-lg border border-transparent px-3 py-2 text-xs font-semibold text-gray-600 transition hover:border-orange-200 hover:bg-orange-50 hover:text-orange-600"
                    >
                      <input
                        v-model="createForm.available_regions"
                        type="checkbox"
                        :value="country.value"
                        class="rounded border-gray-300 text-orange-600 focus:ring-orange-500"
                      />
                      {{ country.label }}
                    </label>
                  </div>
                  <span v-if="createForm.errors.available_regions" class="block text-xs text-rose-600">
                    {{ createForm.errors.available_regions }}
                  </span>
                </div>

                <div class="space-y-2 text-sm font-semibold text-gray-700">
                  <span>Available values</span>
                  <div
                    :class="[
                      'grid grid-cols-2 gap-2 rounded-xl border p-3',
                      createForm.errors.available_values
                        ? 'border-rose-200 bg-rose-50/60'
                        : 'border-gray-200',
                    ]"
                  >
                    <label
                      v-for="value in valueOptions"
                      :key="`create-${value}`"
                      class="flex items-center gap-2 rounded-lg border border-transparent px-3 py-2 text-xs font-semibold text-gray-600 transition hover:border-orange-200 hover:bg-orange-50 hover:text-orange-600"
                    >
                      <input
                        v-model="createForm.available_values"
                        type="checkbox"
                        :value="value.toString()"
                        class="rounded border-gray-300 text-orange-600 focus:ring-orange-500"
                      />
                      ${{ value }}
                    </label>
                  </div>
                  <span v-if="createForm.errors.available_values" class="block text-xs text-rose-600">
                    {{ createForm.errors.available_values }}
                  </span>
                </div>
              </div>

              <label class="space-y-2 text-sm font-semibold text-gray-700">
                <span>Artwork</span>
                <div
                  class="relative flex items-center justify-between gap-3 rounded-xl border border-dashed border-gray-300 bg-gray-50 px-4 py-3"
                >
                  <div class="flex items-center gap-3 text-sm text-gray-500">
                    <UploadCloud class="h-5 w-5 text-orange-500" />
                    <div>
                      <p class="font-semibold text-gray-700">Upload artwork</p>
                      <p class="text-xs text-gray-400">
                        PNG or JPG up to 2MB. Recommended square image.
                      </p>
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
                <span v-if="createForm.errors.image" class="block text-xs text-rose-600">
                  {{ createForm.errors.image }}
                </span>
              </label>

              <div
                v-if="createPreview"
                class="flex items-center gap-3 rounded-xl border border-orange-200 bg-orange-50/60 p-3 text-xs text-orange-600"
              >
                <img
                  :src="createPreview"
                  alt="New giftcard artwork preview"
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
                  Save giftcard
                </button>
              </footer>
            </form>
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
          <div class="w-full max-w-3xl mx-4 rounded-3xl bg-white p-10 shadow-xl shadow-gray-900/10">
            <header class="flex items-start justify-between gap-4">
              <div>
                <p class="text-xs font-semibold uppercase tracking-[0.35em] text-orange-500">
                  Edit giftcard
                </p>
                <h2 class="mt-1 text-2xl font-semibold text-gray-900">
                  Update {{ editingGiftcard?.name }}
                </h2>
                <p class="mt-2 text-sm text-gray-500">
                  Adjust availability or pricing. Leave artwork blank to keep the current image.
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

            <form class="mt-6 space-y-5" @submit.prevent="submitEdit">
              <div
                v-if="Object.keys(editForm.errors).length"
                class="rounded-xl border border-rose-200 bg-rose-50/70 px-4 py-3 text-sm text-rose-600"
              >
                Please resolve the highlighted fields before saving your changes.
              </div>
              <div class="grid gap-4 sm:grid-cols-2">
                <label class="space-y-2 text-sm font-semibold text-gray-700">
                  <span>Name</span>
                  <input
                    v-model="editForm.name"
                    type="text"
                    class="w-full rounded-xl border border-gray-200 px-4 py-3 text-base text-gray-700 focus:border-orange-500 focus:ring-2 focus:ring-orange-100"
                    placeholder="e.g., Amazon USA"
                  />
                  <span v-if="editForm.errors.name" class="block text-xs text-rose-600">
                    {{ editForm.errors.name }}
                  </span>
                </label>

                <label class="space-y-2 text-sm font-semibold text-gray-700">
                  <span>Rate</span>
                  <input
                    v-model="editForm.rate"
                    type="number"
                    step="0.01"
                    class="w-full rounded-xl border border-gray-200 px-4 py-3 text-base text-gray-700 focus:border-orange-500 focus:ring-2 focus:ring-orange-100"
                    placeholder="0.00"
                  />
                  <span v-if="editForm.errors.rate" class="block text-xs text-rose-600">
                    {{ editForm.errors.rate }}
                  </span>
                </label>
              </div>

              <div class="grid gap-4 sm:grid-cols-2">
                <div class="space-y-2 text-sm font-semibold text-gray-700">
                  <span>Available regions</span>
                  <div
                    :class="[
                      'grid grid-cols-2 gap-2 rounded-xl border p-3',
                      editForm.errors.available_regions
                        ? 'border-rose-200 bg-rose-50/60'
                        : 'border-gray-200',
                    ]"
                  >
                    <label
                      v-for="country in countries"
                      :key="`edit-region-${country.value}`"
                      class="flex items-center gap-2 rounded-lg border border-transparent px-3 py-2 text-xs font-semibold text-gray-600 transition hover:border-orange-200 hover:bg-orange-50 hover:text-orange-600"
                    >
                      <input
                        v-model="editForm.available_regions"
                        type="checkbox"
                        :value="country.value"
                        class="rounded border-gray-300 text-orange-600 focus:ring-orange-500"
                      />
                      {{ country.label }}
                    </label>
                  </div>
                  <span v-if="editForm.errors.available_regions" class="block text-xs text-rose-600">
                    {{ editForm.errors.available_regions }}
                  </span>
                </div>

                <div class="space-y-2 text-sm font-semibold text-gray-700">
                  <span>Available values</span>
                  <div
                    :class="[
                      'grid grid-cols-2 gap-2 rounded-xl border p-3',
                      editForm.errors.available_values
                        ? 'border-rose-200 bg-rose-50/60'
                        : 'border-gray-200',
                    ]"
                  >
                    <label
                      v-for="value in valueOptions"
                      :key="`edit-${value}`"
                      class="flex items-center gap-2 rounded-lg border border-transparent px-3 py-2 text-xs font-semibold text-gray-600 transition hover:border-orange-200 hover:bg-orange-50 hover:text-orange-600"
                    >
                      <input
                        v-model="editForm.available_values"
                        type="checkbox"
                        :value="value.toString()"
                        class="rounded border-gray-300 text-orange-600 focus:ring-orange-500"
                      />
                      ${{ value }}
                    </label>
                  </div>
                  <span v-if="editForm.errors.available_values" class="block text-xs text-rose-600">
                    {{ editForm.errors.available_values }}
                  </span>
                </div>
              </div>

              <label class="space-y-2 text-sm font-semibold text-gray-700">
                <span>Artwork</span>
                <div
                  class="relative flex items-center justify-between gap-3 rounded-xl border border-dashed border-gray-300 bg-gray-50 px-4 py-3"
                >
                  <div class="flex items-center gap-3 text-sm text-gray-500">
                    <UploadCloud class="h-5 w-5 text-orange-500" />
                    <div>
                      <p class="font-semibold text-gray-700">Upload new artwork</p>
                      <p class="text-xs text-gray-400">
                        Leave blank to keep the current image.
                      </p>
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
                <span v-if="editForm.errors.image" class="block text-xs text-rose-600">
                  {{ editForm.errors.image }}
                </span>
              </label>

              <div
                v-if="editPreview"
                class="flex items-center gap-3 rounded-xl border border-orange-200 bg-orange-50/60 p-3 text-xs text-orange-600"
              >
                <img
                  :src="editPreview"
                  alt="Replacement giftcard artwork preview"
                  class="h-14 w-14 rounded-xl object-cover"
                />
                Preview of the new image that will replace the current artwork.
              </div>
              <div
                v-if="editingGiftcard?.image_url && !editPreview"
                class="flex items-center gap-3 rounded-xl border border-gray-100 bg-white p-3 text-xs text-gray-500"
              >
                <img
                  :src="editingGiftcard.image_url"
                  :alt="editingGiftcard.name"
                  class="h-12 w-12 rounded-xl object-cover"
                />
                Current artwork preview
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
