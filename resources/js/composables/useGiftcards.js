import { ref } from 'vue'
import { get } from '@/lib/api'

export function useGiftcards() {
  const loading = ref(false)
  const items = ref([])
  const error = ref(null)

  async function fetchGiftcards() {
    loading.value = true
    error.value = null
    try {
      const { data } = await get('/giftcards')
      const raw = Array.isArray(data?.data) ? data.data : Array.isArray(data) ? data : []
      items.value = raw.map(r => ({
        id: r.id,
        name: r.name ?? 'Giftcard',
        logo: r.logo ?? r.image_url ?? '/img/giftcard-placeholder.png',
        rate: r.rate ?? 0,
        available_regions: r.available_regions ?? [],
        available_values: r.available_values ?? [],
      }))
    } catch (e) {
      error.value = e?.response?.data?.message || 'Failed to load giftcards'
      console.error('Error fetching giftcards:', e)
      // Fallback to empty array instead of demo data
      items.value = []
    } finally {
      loading.value = false
    }
  }

  return { loading, items, error, fetchGiftcards }
}
