<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold text-gray-900">✍️ {{ t('writers.title') }}</h1>
      <button @click="openCreateModal" class="btn-primary">➕ {{ t('writers.add_writer') }}</button>
    </div>

    <!-- Search -->
    <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-100">
      <input v-model="searchQuery" @input="debouncedSearch" type="text"
             :placeholder="t('writers.search_placeholder')"
             class="input-field max-w-md" />
    </div>

    <!-- Writers Grid -->
    <div v-if="writers.length === 0" class="text-center py-12 bg-white rounded-xl">
      <span class="text-6xl">✍️</span>
      <p class="mt-4 text-gray-500">{{ t('writers.no_writers') }}</p>
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div v-for="writer in writers" :key="writer.id"
           class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 hover:shadow-md transition-shadow">
        <div class="flex justify-between items-start">
          <div class="flex-1 min-w-0">
            <h3 class="font-semibold text-gray-900">{{ writer.name }}</h3>
            <p v-if="writer.name_ar" class="text-sm text-gray-600 mt-0.5" dir="rtl">{{ writer.name_ar }}</p>
            <p v-if="writer.nationality" class="text-xs text-gray-500 mt-1">🌍 {{ writer.nationality }}</p>
            <p v-if="writer.birth_year" class="text-xs text-gray-500">
              📅 {{ writer.birth_year }}{{ writer.death_year ? ' - ' + writer.death_year : '' }}
            </p>
          </div>
          <span class="text-xs bg-primary-50 text-primary-700 px-2 py-1 rounded-full flex-shrink-0">
            {{ t('writers.books_count', { count: writer.books_count || 0 }) }}
          </span>
        </div>
        <div class="mt-3 flex space-x-2 rtl:space-x-reverse">
          <button @click="openEditModal(writer)" class="text-xs text-blue-600 hover:text-blue-800">✏️ {{ t('edit') }}</button>
          <button @click="deleteWriter(writer)" class="text-xs text-red-600 hover:text-red-800">🗑️ {{ t('delete') }}</button>
        </div>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl max-w-lg w-full p-6 space-y-4 max-h-[90vh] overflow-y-auto">
        <h2 class="text-lg font-semibold">
          {{ editingWriter ? '✏️ ' + t('writers.edit_writer') : '➕ ' + t('writers.add_writer') }}
        </h2>
        <form @submit.prevent="saveWriter" class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2">
              <label class="label">{{ t('writers.name_en') }} *</label>
              <input v-model="writerForm.name" type="text" required class="input-field"
                     :placeholder="t('writers.name_en')" />
            </div>
            <div>
              <label class="label">{{ t('writers.name_ar') }}</label>
              <input v-model="writerForm.name_ar" type="text" class="input-field" dir="rtl"
                     :placeholder="t('writers.name_ar')" />
            </div>
            <div>
              <label class="label">{{ t('writers.name_fr') }}</label>
              <input v-model="writerForm.name_fr" type="text" class="input-field"
                     :placeholder="t('writers.name_fr')" />
            </div>
            <div>
              <label class="label">{{ t('writers.nationality') }}</label>
              <input v-model="writerForm.nationality" type="text" class="input-field"
                     :placeholder="t('writers.nationality')" />
            </div>
            <div>
              <label class="label">{{ t('writers.birth_year') }}</label>
              <input v-model.number="writerForm.birth_year" type="number" class="input-field"
                     placeholder="e.g. 1920" />
            </div>
            <div>
              <label class="label">{{ t('writers.death_year') }}</label>
              <input v-model.number="writerForm.death_year" type="number" class="input-field"
                     placeholder="e.g. 2005" />
            </div>
          </div>
          <div>
            <label class="label">{{ t('writers.biography') }}</label>
            <textarea v-model="writerForm.biography" rows="3" class="input-field"
                      :placeholder="t('writers.biography_placeholder')"></textarea>
          </div>
          <div class="flex justify-end space-x-3 rtl:space-x-reverse">
            <button type="button" @click="showModal = false" class="px-4 py-2 border rounded-lg">{{ t('cancel') }}</button>
            <button type="submit" class="btn-primary">{{ editingWriter ? t('save') : t('create') }}</button>
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
const writers = ref([])
const showModal = ref(false)
const editingWriter = ref(null)
const searchQuery = ref('')

const writerForm = reactive({
  name: '',
  name_ar: '',
  name_fr: '',
  nationality: '',
  birth_year: null,
  death_year: null,
  biography: '',
})

let searchTimeout = null
function debouncedSearch() {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(loadWriters, 300)
}

async function loadWriters() {
  try {
    const params = searchQuery.value ? { search: searchQuery.value } : {}
    const response = await api.get('/writers', { params })
    writers.value = response.data.data
  } catch (err) {
    console.error('Failed to load writers:', err)
  }
}

function openCreateModal() {
  editingWriter.value = null
  Object.assign(writerForm, { name: '', name_ar: '', name_fr: '', nationality: '', birth_year: null, death_year: null, biography: '' })
  showModal.value = true
}

function openEditModal(writer) {
  editingWriter.value = writer
  Object.assign(writerForm, {
    name: writer.name || '',
    name_ar: writer.name_ar || '',
    name_fr: writer.name_fr || '',
    nationality: writer.nationality || '',
    birth_year: writer.birth_year || null,
    death_year: writer.death_year || null,
    biography: writer.biography || '',
  })
  showModal.value = true
}

async function saveWriter() {
  try {
    if (editingWriter.value) {
      await api.put(`/writers/${editingWriter.value.id}`, writerForm)
      toastStore.success(t('writers.updated'))
    } else {
      await api.post('/writers', writerForm)
      toastStore.success(t('writers.created'))
    }
    showModal.value = false
    loadWriters()
  } catch (err) {
    toastStore.error(err.response?.data?.error || t('writers.save_failed'))
  }
}

async function deleteWriter(writer) {
  if (!confirm(t('writers.delete_confirm', { name: writer.name }))) return
  try {
    await api.delete(`/writers/${writer.id}`)
    toastStore.success(t('writers.deleted'))
    loadWriters()
  } catch (err) {
    toastStore.error(err.response?.data?.error || t('writers.delete_failed'))
  }
}

onMounted(loadWriters)
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
