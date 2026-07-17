<template>
  <div v-if="book" class="max-w-4xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <router-link to="/books" class="text-sm text-gray-500 hover:text-gray-700">{{ t('books.back_to_books') }}</router-link>
      <div class="flex space-x-2 rtl:space-x-reverse">
        <router-link :to="`/books/${book.id}/edit`" class="btn-secondary">✏️ {{ t('edit') }}</router-link>
        <button @click="toggleRead" class="btn-secondary">
          {{ book.read_status ? '📖 ' + t('books.mark_unread') : '✅ ' + t('books.mark_read') }}
        </button>
        <button @click="deleteBook" class="px-3 py-2 text-sm text-red-600 border border-red-300 rounded-lg hover:bg-red-50">
          🗑️ {{ t('delete') }}
        </button>
      </div>
    </div>

    <!-- Book Details -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
      <div class="flex flex-col md:flex-row gap-6">
        <!-- Cover placeholder -->
        <div class="w-32 h-44 bg-gradient-to-br from-primary-100 to-primary-200 rounded-lg flex items-center justify-center flex-shrink-0">
          <span class="text-4xl">📕</span>
        </div>

        <div class="flex-1 space-y-4">
          <div>
            <h1 class="text-2xl font-bold text-gray-900" :dir="book.language === 'arabic' ? 'rtl' : 'ltr'">
              {{ book.title }}
            </h1>
            <p class="text-lg text-gray-600 mt-1" :dir="book.language === 'arabic' ? 'rtl' : 'ltr'">
              {{ book.author }}
            </p>
          </div>

          <div class="flex flex-wrap gap-2">
            <span :class="['px-3 py-1 rounded-full text-sm font-medium',
                          book.read_status ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700']">
              {{ book.read_status ? '✅ ' + t('books.read') : '📖 ' + t('books.unread') }}
            </span>
            <span v-if="book.is_borrowed" class="px-3 py-1 rounded-full text-sm font-medium bg-orange-100 text-orange-700">
              📤 {{ t('books.lent_to', { name: book.borrower_name }) }}
            </span>
            <span v-if="book.language" class="px-3 py-1 rounded-full text-sm bg-blue-100 text-blue-700">
              {{ t('languages.' + book.language) }}
            </span>
            <span v-if="book.genre" class="px-3 py-1 rounded-full text-sm bg-purple-100 text-purple-700">
              {{ book.genre }}
            </span>
          </div>

          <!-- Details Grid -->
          <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mt-4">
            <div v-if="book.publication_year">
              <p class="text-xs text-gray-500">{{ t('books.publication_year') }}</p>
              <p class="text-sm font-medium">{{ book.publication_year }}</p>
            </div>
            <div v-if="book.edition_house">
              <p class="text-xs text-gray-500">{{ t('books.publisher') }}</p>
              <p class="text-sm font-medium">{{ book.edition_house }}</p>
            </div>
            <div v-if="book.isbn">
              <p class="text-xs text-gray-500">{{ t('books.isbn') }}</p>
              <p class="text-sm font-medium font-mono">{{ book.isbn }}</p>
            </div>
            <div v-if="book.num_pages">
              <p class="text-xs text-gray-500">{{ t('books.pages') }}</p>
              <p class="text-sm font-medium">{{ book.num_pages }}</p>
            </div>
            <div v-if="book.location_room">
              <p class="text-xs text-gray-500">{{ t('books.location') }}</p>
              <p class="text-sm font-medium">📍 {{ book.location_room }}{{ book.location_shelf ? ' / ' + book.location_shelf : '' }}</p>
            </div>
            <div v-if="book.series_name">
              <p class="text-xs text-gray-500">{{ t('books.series_name') }}</p>
              <p class="text-sm font-medium">{{ book.series_name }} #{{ book.series_position }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Notes -->
      <div v-if="book.notes" class="mt-6 pt-4 border-t">
        <h3 class="text-sm font-medium text-gray-500 mb-2">{{ t('notes') }}</h3>
        <p class="text-gray-700 whitespace-pre-wrap">{{ book.notes }}</p>
      </div>
    </div>

    <!-- Lending History -->
    <div v-if="book.lending_history?.length" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
      <h2 class="text-lg font-semibold text-gray-800 mb-4">📋 {{ t('books.lending_history') }}</h2>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b">
              <th class="text-start py-2 px-3">{{ t('books.borrower') }}</th>
              <th class="text-start py-2 px-3">{{ t('books.lent_date') }}</th>
              <th class="text-start py-2 px-3">{{ t('books.due_date') }}</th>
              <th class="text-start py-2 px-3">{{ t('books.returned') }}</th>
              <th class="text-start py-2 px-3">{{ t('status') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="record in book.lending_history" :key="record.id" class="border-b last:border-0">
              <td class="py-2 px-3">{{ record.borrower_name }}</td>
              <td class="py-2 px-3">{{ record.lent_date }}</td>
              <td class="py-2 px-3">{{ record.due_date }}</td>
              <td class="py-2 px-3">{{ record.returned_date || '—' }}</td>
              <td class="py-2 px-3">
                <span :class="['px-2 py-0.5 rounded-full text-xs',
                              record.status === 'returned' ? 'bg-green-100 text-green-700' :
                              record.status === 'overdue' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700']">
                  {{ record.status }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div v-else class="text-center py-12">
    <div class="animate-spin inline-block w-8 h-8 border-4 border-primary-500 border-t-transparent rounded-full"></div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import api from '../services/api'
import { useToastStore } from '../store/toast'

const { t } = useI18n()
const router = useRouter()
const route = useRoute()
const toastStore = useToastStore()
const book = ref(null)

async function loadBook() {
  try {
    const response = await api.get(`/books/${route.params.id}`)
    book.value = response.data.data
  } catch (err) {
    toastStore.error(t('books.save_failed'))
    router.push('/books')
  }
}

async function toggleRead() {
  try {
    const response = await api.post(`/books/${route.params.id}/toggle-read`)
    book.value.read_status = response.data.data.read_status
    toastStore.success(t('books.read_status_updated'))
  } catch (err) {
    toastStore.error(t('books.save_failed'))
  }
}

async function deleteBook() {
  if (!confirm(t('books.delete_confirm'))) return
  try {
    await api.delete(`/books/${route.params.id}`)
    toastStore.success(t('books.deleted'))
    router.push('/books')
  } catch (err) {
    toastStore.error(t('books.save_failed'))
  }
}

onMounted(loadBook)
</script>

<style scoped>
.btn-secondary {
  @apply px-3 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors;
}
</style>
