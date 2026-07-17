<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold text-gray-900">📖 {{ t('genres.title') }}</h1>
      <button @click="openCreateModal" class="btn-primary">➕ {{ t('genres.add_genre') }}</button>
    </div>

    <!-- Genres Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-gray-50">
          <tr>
            <th class="text-start py-3 px-4 font-medium text-gray-600">{{ t('genres.name_en') }}</th>
            <th class="text-start py-3 px-4 font-medium text-gray-600">{{ t('genres.name_ar') }}</th>
            <th class="text-start py-3 px-4 font-medium text-gray-600">{{ t('genres.name_fr') }}</th>
            <th class="text-start py-3 px-4 font-medium text-gray-600">{{ t('actions') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="genre in genres" :key="genre.id" class="border-t hover:bg-gray-50">
            <td class="py-3 px-4 font-medium">{{ genre.name }}</td>
            <td class="py-3 px-4" dir="rtl">{{ genre.name_ar || '—' }}</td>
            <td class="py-3 px-4">{{ genre.name_fr || '—' }}</td>
            <td class="py-3 px-4 space-x-2 rtl:space-x-reverse">
              <button @click="openEditModal(genre)" class="text-xs text-blue-600 hover:text-blue-800">✏️ {{ t('edit') }}</button>
              <button @click="deleteGenre(genre)" class="text-xs text-red-600 hover:text-red-800">🗑️ {{ t('delete') }}</button>
            </td>
          </tr>
          <tr v-if="genres.length === 0">
            <td colspan="4" class="py-8 text-center text-gray-500">{{ t('genres.no_genres') }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Create/Edit Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl max-w-md w-full p-6 space-y-4">
        <h2 class="text-lg font-semibold">
          {{ editingGenre ? '✏️ ' + t('genres.edit_genre') : '➕ ' + t('genres.add_genre') }}
        </h2>
        <form @submit.prevent="saveGenre" class="space-y-4">
          <div>
            <label class="label">{{ t('genres.name_en') }} *</label>
            <input v-model="genreForm.name" type="text" required class="input-field"
                   placeholder="e.g. Fiction" />
          </div>
          <div>
            <label class="label">{{ t('genres.name_ar') }}</label>
            <input v-model="genreForm.name_ar" type="text" class="input-field" dir="rtl"
                   placeholder="مثال: رواية" />
          </div>
          <div>
            <label class="label">{{ t('genres.name_fr') }}</label>
            <input v-model="genreForm.name_fr" type="text" class="input-field"
                   placeholder="ex. Fiction" />
          </div>
          <div class="flex justify-end space-x-3 rtl:space-x-reverse">
            <button type="button" @click="showModal = false" class="px-4 py-2 border rounded-lg">{{ t('cancel') }}</button>
            <button type="submit" class="btn-primary">{{ editingGenre ? t('save') : t('create') }}</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import api from '../services/api'
import { useToastStore } from '../store/toast'

const { t } = useI18n()
const toastStore = useToastStore()
const genres = ref([])
const showModal = ref(false)
const editingGenre = ref(null)

const genreForm = reactive({
  name: '',
  name_ar: '',
  name_fr: '',
})

async function loadGenres() {
  try {
    const response = await api.get('/genres')
    genres.value = response.data.data
  } catch (err) {
    console.error('Failed to load genres:', err)
  }
}

function openCreateModal() {
  editingGenre.value = null
  Object.assign(genreForm, { name: '', name_ar: '', name_fr: '' })
  showModal.value = true
}

function openEditModal(genre) {
  editingGenre.value = genre
  Object.assign(genreForm, {
    name: genre.name || '',
    name_ar: genre.name_ar || '',
    name_fr: genre.name_fr || '',
  })
  showModal.value = true
}

async function saveGenre() {
  try {
    if (editingGenre.value) {
      await api.put(`/genres/${editingGenre.value.id}`, genreForm)
      toastStore.success(t('genres.updated'))
    } else {
      await api.post('/genres', genreForm)
      toastStore.success(t('genres.created'))
    }
    showModal.value = false
    loadGenres()
  } catch (err) {
    toastStore.error(err.response?.data?.error || t('genres.save_failed'))
  }
}

async function deleteGenre(genre) {
  if (!confirm(t('genres.delete_confirm', { name: genre.name }))) return
  try {
    await api.delete(`/genres/${genre.id}`)
    toastStore.success(t('genres.deleted'))
    loadGenres()
  } catch (err) {
    toastStore.error(err.response?.data?.error || t('genres.delete_failed'))
  }
}

onMounted(loadGenres)
</script>

<style scoped>
.input-field {
  @apply w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none text-sm;
}
.btn-primary {
  @apply px-4 py-2 bg-primary-600 text-white rounded-lg font-medium hover:bg-primary-700 transition-colors text-sm;
}
.label {
  @apply block text-sm font-medium text-gray-700 mb-1;
}
</style>
