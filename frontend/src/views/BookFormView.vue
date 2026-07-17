<template>
  <div class="max-w-3xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold text-gray-900">
        {{ isEditing ? '✏️ ' + t('books.edit_book') : '➕ ' + t('books.add_new_book') }}
      </h1>
      <router-link to="/books" class="text-sm text-gray-500 hover:text-gray-700">{{ t('books.back_to_books') }}</router-link>
    </div>

    <!-- ISBN Lookup -->
    <div class="bg-blue-50 rounded-xl p-4 border border-blue-100">
      <h3 class="text-sm font-medium text-blue-800 mb-2">📷 {{ t('books.isbn_lookup') }}</h3>
      <div class="flex gap-2">
        <input v-model="isbnLookup" type="text" :placeholder="t('books.isbn_placeholder')"
               class="flex-1 input-field" />
        <button @click="lookupISBN" :disabled="lookingUp" class="btn-primary whitespace-nowrap">
          {{ lookingUp ? t('books.looking_up') : t('books.look_up') }}
        </button>
      </div>
    </div>

    <!-- Book Form -->
    <form @submit.prevent="saveBook" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-6">
      <!-- Basic Info -->
      <fieldset class="space-y-4">
        <legend class="text-lg font-semibold text-gray-800">📖 {{ t('books.basic_info') }}</legend>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="md:col-span-2">
            <label class="label">{{ t('books.title_label') }} *</label>
            <input v-model="form.title" type="text" required class="input-field"
                   :placeholder="t('books.title_placeholder')" />
          </div>
          <div class="md:col-span-2">
            <label class="label">{{ t('books.author_label') }} *</label>
            <div class="flex gap-2">
              <select v-model="form.author" class="input-field flex-1">
                <option value="">{{ t('writers.select_writer') }}</option>
                <option v-for="w in writers" :key="w.id" :value="w.name">
                  {{ w.name }}{{ w.name_ar ? ' / ' + w.name_ar : '' }}
                </option>
              </select>
              <input v-model="form.author" type="text" class="input-field flex-1"
                     :placeholder="t('books.author_placeholder')" />
            </div>
            <p class="text-xs text-gray-500 mt-1">{{ t('writers.select_or_type') }}</p>
          </div>
          <div>
            <label class="label">{{ t('books.genre') }}</label>
            <select v-model="form.genre" class="input-field">
              <option value="">{{ t('books.select_genre') }}</option>
              <option v-for="g in genres" :key="g.id" :value="g.name">{{ g.name }}</option>
            </select>
          </div>
          <div>
            <label class="label">{{ t('books.language') }}</label>
            <select v-model="form.language" class="input-field">
              <option value="arabic">{{ t('languages.arabic') }}</option>
              <option value="english">{{ t('languages.english') }}</option>
              <option value="french">{{ t('languages.french') }}</option>
              <option value="other">{{ t('languages.other') }}</option>
            </select>
          </div>
          <div>
            <label class="label">{{ t('books.publication_year') }}</label>
            <input v-model.number="form.publication_year" type="number" min="1000" max="2030"
                   class="input-field" placeholder="e.g. 2020" />
          </div>
          <div>
            <label class="label">{{ t('books.num_pages') }}</label>
            <input v-model.number="form.num_pages" type="number" min="1"
                   class="input-field" placeholder="e.g. 350" />
          </div>
        </div>
      </fieldset>

      <!-- Publishing Info -->
      <fieldset class="space-y-4">
        <legend class="text-lg font-semibold text-gray-800">🏢 {{ t('books.publishing_details') }}</legend>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="label">{{ t('books.publisher') }}</label>
            <input v-model="form.edition_house" type="text" class="input-field"
                   :placeholder="t('books.publisher_placeholder')" />
          </div>
          <div>
            <label class="label">{{ t('books.isbn') }}</label>
            <input v-model="form.isbn" type="text" class="input-field"
                   placeholder="978-3-16-148410-0" />
          </div>
        </div>
      </fieldset>

      <!-- Series Info -->
      <fieldset class="space-y-4">
        <legend class="text-lg font-semibold text-gray-800">📚 {{ t('books.series') }}</legend>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="label">{{ t('books.series_name') }}</label>
            <input v-model="form.series_name" type="text" class="input-field"
                   :placeholder="t('books.series_name')" />
          </div>
          <div>
            <label class="label">{{ t('books.series_position') }}</label>
            <input v-model.number="form.series_position" type="number" min="1"
                   class="input-field" placeholder="e.g. 1" />
          </div>
        </div>
      </fieldset>

      <!-- Location & Status -->
      <fieldset class="space-y-4">
        <legend class="text-lg font-semibold text-gray-800">📍 {{ t('books.location_status') }}</legend>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="label">{{ t('books.room') }}</label>
            <input v-model="form.location_room" type="text" class="input-field"
                   :placeholder="t('books.room_placeholder')" />
          </div>
          <div>
            <label class="label">{{ t('books.shelf') }}</label>
            <input v-model="form.location_shelf" type="text" class="input-field"
                   :placeholder="t('books.shelf_placeholder')" />
          </div>
          <div class="flex items-center space-x-3 rtl:space-x-reverse">
            <input v-model="form.read_status" type="checkbox" id="read_status"
                   class="w-4 h-4 text-primary-600 rounded" />
            <label for="read_status" class="text-sm text-gray-700">{{ t('books.read_this_book') }}</label>
          </div>
        </div>
      </fieldset>

      <!-- Notes -->
      <fieldset>
        <legend class="text-lg font-semibold text-gray-800">📝 {{ t('books.notes_label') }}</legend>
        <textarea v-model="form.notes" rows="3" class="input-field mt-2"
                  :placeholder="t('books.notes_placeholder')"></textarea>
      </fieldset>

      <!-- Submit -->
      <div class="flex justify-end space-x-3 rtl:space-x-reverse pt-4 border-t">
        <router-link to="/books" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
          {{ t('cancel') }}
        </router-link>
        <button type="submit" :disabled="saving" class="btn-primary">
          {{ saving ? t('books.saving') : (isEditing ? t('books.update_book') : t('books.add_book')) }}
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import api from '../services/api'
import { useToastStore } from '../store/toast'

const { t } = useI18n()
const router = useRouter()
const route = useRoute()
const toastStore = useToastStore()

const isEditing = computed(() => !!route.params.id)
const saving = ref(false)
const lookingUp = ref(false)
const isbnLookup = ref('')
const genres = ref([])
const writers = ref([])

const form = reactive({
  title: '',
  author: '',
  genre: '',
  language: 'arabic',
  publication_year: null,
  num_pages: null,
  edition_house: '',
  isbn: '',
  series_name: '',
  series_position: null,
  location_room: '',
  location_shelf: '',
  read_status: false,
  notes: '',
  cover_image_url: '',
})

async function loadBook() {
  if (!route.params.id) return
  try {
    const response = await api.get(`/books/${route.params.id}`)
    Object.assign(form, response.data.data)
  } catch (err) {
    toastStore.error(t('books.save_failed'))
    router.push('/books')
  }
}

async function loadGenres() {
  try {
    const response = await api.get('/genres')
    genres.value = response.data.data
  } catch (err) {
    console.error('Failed to load genres:', err)
  }
}

async function loadWriters() {
  try {
    const response = await api.get('/writers')
    writers.value = response.data.data
  } catch (err) {
    console.error('Failed to load writers:', err)
  }
}

async function lookupISBN() {
  if (!isbnLookup.value) return
  lookingUp.value = true
  try {
    const response = await api.post('/books/isbn-lookup', { isbn: isbnLookup.value })
    const data = response.data.data
    if (data.title) form.title = data.title
    if (data.author) form.author = data.author
    if (data.publication_year) form.publication_year = data.publication_year
    if (data.num_pages) form.num_pages = data.num_pages
    if (data.edition_house) form.edition_house = data.edition_house
    if (data.isbn) form.isbn = data.isbn
    if (data.cover_image_url) form.cover_image_url = data.cover_image_url
    toastStore.success(t('books.book_info_found'))
  } catch (err) {
    toastStore.error(err.response?.data?.error || t('books.isbn_lookup_failed'))
  } finally {
    lookingUp.value = false
  }
}

async function saveBook() {
  saving.value = true
  try {
    if (isEditing.value) {
      await api.put(`/books/${route.params.id}`, form)
      toastStore.success(t('books.book_updated'))
    } else {
      await api.post('/books', form)
      toastStore.success(t('books.book_added'))
    }
    router.push('/books')
  } catch (err) {
    toastStore.error(err.response?.data?.error || err.response?.data?.errors?.[0] || t('books.save_failed'))
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  loadGenres()
  loadWriters()
  loadBook()
})
</script>

<style scoped>
.input-field {
  @apply w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none text-sm;
}
.btn-primary {
  @apply px-4 py-2 bg-primary-600 text-white rounded-lg font-medium hover:bg-primary-700 transition-colors text-sm disabled:opacity-50;
}
.label {
  @apply block text-sm font-medium text-gray-700 mb-1;
}
</style>
