<template>
  <div class="space-y-6">
    <h1 class="text-2xl font-bold text-gray-900">📊 {{ t('dashboard.title') }}</h1>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-500">{{ t('dashboard.total_books') }}</p>
            <p class="text-3xl font-bold text-gray-900">{{ stats.total_books || 0 }}</p>
          </div>
          <span class="text-4xl">📚</span>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-500">{{ t('dashboard.books_read') }}</p>
            <p class="text-3xl font-bold text-green-600">{{ stats.books_read || 0 }}</p>
          </div>
          <span class="text-4xl">✅</span>
        </div>
        <div class="mt-2">
          <div class="w-full bg-gray-200 rounded-full h-2">
            <div class="bg-green-500 h-2 rounded-full" :style="{ width: readPercentage + '%' }"></div>
          </div>
          <p class="text-xs text-gray-500 mt-1">{{ t('dashboard.read_percentage', { percentage: readPercentage }) }}</p>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-500">{{ t('dashboard.lent_out') }}</p>
            <p class="text-3xl font-bold text-orange-600">{{ stats.books_lent || 0 }}</p>
          </div>
          <span class="text-4xl">🤝</span>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-500">{{ t('dashboard.overdue') }}</p>
            <p class="text-3xl font-bold text-red-600">{{ stats.books_overdue || 0 }}</p>
          </div>
          <span class="text-4xl">⚠️</span>
        </div>
      </div>
    </div>

    <!-- Language Distribution -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">📖 {{ t('dashboard.by_language') }}</h2>
        <div class="space-y-3">
          <div v-for="lang in stats.by_language" :key="lang.language" class="flex items-center">
            <span class="w-24 text-sm text-gray-600">{{ t('languages.' + lang.language) }}</span>
            <div class="flex-1 mx-3">
              <div class="w-full bg-gray-200 rounded-full h-4">
                <div class="h-4 rounded-full transition-all"
                     :class="getLanguageColor(lang.language)"
                     :style="{ width: getLanguagePercentage(lang.count) + '%' }"></div>
              </div>
            </div>
            <span class="text-sm font-medium text-gray-700">{{ lang.count }}</span>
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">⚡ {{ t('dashboard.quick_actions') }}</h2>
        <div class="grid grid-cols-2 gap-3">
          <router-link to="/books/add" class="quick-action-btn bg-primary-50 text-primary-700 hover:bg-primary-100">
            <span class="text-2xl">➕</span>
            <span class="text-sm font-medium">{{ t('dashboard.add_book') }}</span>
          </router-link>
          <router-link to="/books?search=" class="quick-action-btn bg-green-50 text-green-700 hover:bg-green-100">
            <span class="text-2xl">🔍</span>
            <span class="text-sm font-medium">{{ t('dashboard.search') }}</span>
          </router-link>
          <router-link to="/lending" class="quick-action-btn bg-orange-50 text-orange-700 hover:bg-orange-100">
            <span class="text-2xl">📤</span>
            <span class="text-sm font-medium">{{ t('dashboard.lend_book') }}</span>
          </router-link>
          <router-link to="/reports" class="quick-action-btn bg-purple-50 text-purple-700 hover:bg-purple-100">
            <span class="text-2xl">📊</span>
            <span class="text-sm font-medium">{{ t('dashboard.reports') }}</span>
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import api from '../services/api'

const { t } = useI18n()
const stats = ref({})

const readPercentage = computed(() => {
  if (!stats.value.total_books) return 0
  return Math.round((stats.value.books_read / stats.value.total_books) * 100)
})

function getLanguagePercentage(count) {
  if (!stats.value.total_books) return 0
  return Math.round((count / stats.value.total_books) * 100)
}

function getLanguageColor(lang) {
  const colors = {
    arabic: 'bg-emerald-500',
    english: 'bg-blue-500',
    french: 'bg-indigo-500',
    other: 'bg-gray-500',
  }
  return colors[lang] || 'bg-gray-500'
}

onMounted(async () => {
  try {
    const response = await api.get('/reports/summary')
    stats.value = response.data.data
  } catch (err) {
    console.error('Failed to load dashboard stats:', err)
  }
})
</script>

<style scoped>
.quick-action-btn {
  @apply flex flex-col items-center justify-center p-4 rounded-lg transition-colors cursor-pointer;
}
</style>
