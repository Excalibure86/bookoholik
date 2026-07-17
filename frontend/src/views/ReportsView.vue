<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold text-gray-900">📊 {{ t('reports.title') }}</h1>
      <div class="flex space-x-2 rtl:space-x-reverse">
        <a :href="apiUrl + '/reports/export/csv'" class="btn-secondary" target="_blank">{{ t('reports.export_csv') }}</a>
        <a :href="apiUrl + '/reports/export/pdf'" class="btn-secondary" target="_blank">{{ t('reports.export_pdf') }}</a>
      </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
        <h3 class="text-sm font-medium text-gray-500 mb-3">📖 {{ t('reports.by_genre') }}</h3>
        <div class="space-y-2 max-h-64 overflow-y-auto">
          <div v-for="item in genreReport" :key="item.genre" class="flex items-center justify-between text-sm">
            <span class="text-gray-700">{{ item.genre || t('unknown') }}</span>
            <div class="flex items-center space-x-2 rtl:space-x-reverse">
              <span class="font-medium">{{ item.count }}</span>
              <span class="text-xs text-green-600">{{ t('reports.read_count', { count: item.read_count }) }}</span>
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
        <h3 class="text-sm font-medium text-gray-500 mb-3">✍️ {{ t('reports.top_authors') }}</h3>
        <div class="space-y-2 max-h-64 overflow-y-auto">
          <div v-for="item in authorReport" :key="item.author" class="flex items-center justify-between text-sm">
            <span class="text-gray-700 truncate ltr:mr-2 rtl:ml-2">{{ item.author }}</span>
            <span class="font-medium flex-shrink-0">{{ item.count }}</span>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
        <h3 class="text-sm font-medium text-gray-500 mb-3">📅 {{ t('reports.by_year') }}</h3>
        <div class="space-y-2 max-h-64 overflow-y-auto">
          <div v-for="item in yearReport" :key="item.publication_year" class="flex items-center justify-between text-sm">
            <span class="text-gray-700">{{ item.publication_year || t('unknown') }}</span>
            <span class="font-medium">{{ item.count }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Location Map -->
    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
      <h3 class="text-lg font-semibold text-gray-800 mb-4">📍 {{ t('reports.by_location') }}</h3>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
        <div v-for="loc in locationReport" :key="loc.location_room + loc.location_shelf"
             class="p-3 bg-gray-50 rounded-lg border">
          <p class="font-medium text-gray-800">{{ loc.location_room || t('unknown') }}</p>
          <p class="text-sm text-gray-500">{{ loc.location_shelf || t('general') }}</p>
          <p class="text-lg font-bold text-primary-600 mt-1">{{ t('reports.books_count', { count: loc.count }) }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import api from '../services/api'

const { t } = useI18n()
const apiUrl = import.meta.env.VITE_API_URL || '/api'
const genreReport = ref([])
const authorReport = ref([])
const yearReport = ref([])
const locationReport = ref([])

async function loadReports() {
  try {
    const [genre, author, year, location] = await Promise.all([
      api.get('/reports/by-genre'),
      api.get('/reports/by-author', { params: { limit: 20 } }),
      api.get('/reports/by-year'),
      api.get('/reports/by-location'),
    ])
    genreReport.value = genre.data.data
    authorReport.value = author.data.data
    yearReport.value = year.data.data
    locationReport.value = location.data.data
  } catch (err) {
    console.error('Failed to load reports:', err)
  }
}

onMounted(loadReports)
</script>

<style scoped>
.btn-secondary {
  @apply px-3 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors;
}
</style>
