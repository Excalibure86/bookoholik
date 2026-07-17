<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
      <h1 class="text-2xl font-bold text-gray-900">📚 {{ t('books.title') }}</h1>
      <router-link to="/books/add" class="btn-primary inline-flex items-center">
        <span class="ltr:mr-2 rtl:ml-2">➕</span> {{ t('books.add_book') }}
      </router-link>
    </div>

    <!-- Search & Filters -->
    <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-100">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="md:col-span-2">
          <input v-model="filters.search" @input="debouncedSearch" type="text"
                 :placeholder="t('books.search_placeholder')"
                 class="input-field" />
        </div>
        <select v-model="filters.language" @change="loadBooks" class="input-field">
          <option value="">{{ t('books.all_languages') }}</option>
          <option value="arabic">{{ t('languages.arabic') }}</option>
          <option value="english">{{ t('languages.english') }}</option>
          <option value="french">{{ t('languages.french') }}</option>
          <option value="other">{{ t('languages.other') }}</option>
        </select>
        <select v-model="filters.genre" @change="loadBooks" class="input-field">
          <option value="">{{ t('books.all_genres') }}</option>
          <option v-for="g in genres" :key="g.id" :value="g.name">{{ g.name }}</option>
        </select>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-3">
        <select v-model="filters.read_status" @change="loadBooks" class="input-field">
          <option value="">{{ t('books.all_status') }}</option>
          <option value="true">{{ t('books.read') }} ✅</option>
          <option value="false">{{ t('books.unread') }} 📖</option>
        </select>
        <select v-model="filters.borrowed" @change="loadBooks" class="input-field">
          <option value="">{{ t('all') }}</option>
          <option value="true">{{ t('books.borrowed') }} 📤</option>
          <option value="false">{{ t('books.available') }} 📥</option>
        </select>
        <select v-model="filters.sort_by" @change="loadBooks" class="input-field">
          <option value="created_at">{{ t('books.recently_added') }}</option>
          <option value="title">{{ t('books.title_label') }}</option>
          <option value="author">{{ t('books.author_label') }}</option>
          <option value="publication_year">{{ t('books.publication_year') }}</option>
        </select>
        <select v-model="filters.sort_dir" @change="loadBooks" class="input-field">
          <option value="DESC">{{ t('books.descending') }}</option>
          <option value="ASC">{{ t('books.ascending') }}</option>
        </select>
      </div>
    </div>

    <!-- Results Info -->
    <div class="flex items-center justify-between text-sm text-gray-600">
      <span>{{ t('books.books_found', { count: pagination.total }) }}</span>
      <span>{{ t('books.page_info', { current: pagination.current_page, total: pagination.last_page }) }}</span>
    </div>

    <!-- Books Grid -->
    <div v-if="loading" class="text-center py-12">
      <div class="animate-spin inline-block w-8 h-8 border-4 border-primary-500 border-t-transparent rounded-full"></div>
      <p class="mt-2 text-gray-500">{{ t('books.loading_books') }}</p>
    </div>

    <div v-else-if="books.length === 0" class="text-center py-12 bg-white rounded-xl">
      <span class="text-6xl">📭</span>
      <p class="mt-4 text-gray-500">{{ t('books.no_books') }}</p>
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div v-for="book in books" :key="book.id"
           class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 hover:shadow-md transition-shadow cursor-pointer"
           @click="$router.push(`/books/${book.id}`)">
        <div class="flex justify-between items-start">
          <div class="flex-1 min-w-0">
            <h3 class="font-semibold text-gray-900 truncate" :dir="book.language === 'arabic' ? 'rtl' : 'ltr'">
              {{ book.title }}
            </h3>
            <p class="text-sm text-gray-600 mt-1" :dir="book.language === 'arabic' ? 'rtl' : 'ltr'">
              {{ book.author }}
            </p>
          </div>
          <div class="flex flex-col items-end ltr:ml-2 rtl:mr-2 space-y-1">
            <span v-if="book.read_status" class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full">{{ t('books.read') }}</span>
            <span v-else class="text-xs bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded-full">{{ t('books.unread') }}</span>
            <span v-if="book.is_borrowed" class="text-xs bg-orange-100 text-orange-700 px-2 py-0.5 rounded-full">{{ t('books.lent') }}</span>
          </div>
        </div>

        <div class="mt-3 flex flex-wrap gap-2 text-xs text-gray-500">
          <span v-if="book.genre" class="bg-gray-100 px-2 py-0.5 rounded">{{ book.genre }}</span>
          <span v-if="book.language" class="bg-blue-50 text-blue-600 px-2 py-0.5 rounded">{{ t('languages.' + book.language) }}</span>
          <span v-if="book.publication_year" class="bg-gray-100 px-2 py-0.5 rounded">{{ book.publication_year }}</span>
          <span v-if="book.location_room" class="bg-purple-50 text-purple-600 px-2 py-0.5 rounded">
            📍 {{ book.location_room }}{{ book.location_shelf ? ' / ' + book.location_shelf : '' }}
          </span>
        </div>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="pagination.last_page > 1" class="flex justify-center space-x-2 rtl:space-x-reverse">
      <button @click="goToPage(pagination.current_page - 1)"
              :disabled="pagination.current_page <= 1"
              class="px-3 py-2 rounded-lg border border-gray-300 text-sm disabled:opacity-50 hover:bg-gray-50">
        {{ t('books.previous') }}
      </button>
      <button v-for="page in visiblePages" :key="page" @click="goToPage(page)"
              :class="['px-3 py-2 rounded-lg text-sm border',
                       page === pagination.current_page ? 'bg-primary-600 text-white border-primary-600' : 'border-gray-300 hover:bg-gray-50']">
        {{ page }}
      </button>
      <button @click="goToPage(pagination.current_page + 1)"
              :disabled="pagination.current_page >= pagination.last_page"
              class="px-3 py-2 rounded-lg border border-gray-300 text-sm disabled:opacity-50 hover:bg-gray-50">
        {{ t('books.next') }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import api from '../services/api'

const { t } = useI18n()
const books = ref([])
const genres = ref([])
const loading = ref(false)
const pagination = ref({ total: 0, current_page: 1, last_page: 1, per_page: 25 })

const filters = reactive({
  search: '',
  language: '',
  genre: '',
  read_status: '',
  borrowed: '',
  sort_by: 'created_at',
  sort_dir: 'DESC',
  page: 1,
})

const visiblePages = computed(() => {
  const pages = []
  const current = pagination.value.current_page
  const last = pagination.value.last_page
  const start = Math.max(1, current - 2)
  const end = Math.min(last, current + 2)
  for (let i = start; i <= end; i++) pages.push(i)
  return pages
})

let searchTimeout = null
function debouncedSearch() {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    filters.page = 1
    loadBooks()
  }, 300)
}

async function loadBooks() {
  loading.value = true
  try {
    const params = {}
    Object.entries(filters).forEach(([key, value]) => {
      if (value !== '') params[key] = value
    })
    const response = await api.get('/books', { params })
    books.value = response.data.data
    pagination.value = response.data.pagination
  } catch (err) {
    console.error('Failed to load books:', err)
  } finally {
    loading.value = false
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

function goToPage(page) {
  if (page < 1 || page > pagination.value.last_page) return
  filters.page = page
  loadBooks()
}

onMounted(() => {
  loadBooks()
  loadGenres()
})
</script>

<style scoped>
.input-field {
  @apply w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none text-sm;
}

.btn-primary {
  @apply px-4 py-2 bg-primary-600 text-white rounded-lg font-medium hover:bg-primary-700 transition-colors text-sm;
}
</style>
