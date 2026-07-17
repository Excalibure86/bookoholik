<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold text-gray-900">🤝 {{ t('lending.title') }}</h1>
      <button @click="showLendModal = true" class="btn-primary">📤 {{ t('lending.lend_a_book') }}</button>
    </div>

    <!-- Overdue Alert -->
    <div v-if="overdueBooks.length" class="bg-red-50 border border-red-200 rounded-xl p-4">
      <h3 class="text-red-800 font-semibold mb-2">⚠️ {{ t('lending.overdue_books', { count: overdueBooks.length }) }}</h3>
      <div class="space-y-2">
        <div v-for="record in overdueBooks" :key="record.id" class="flex items-center justify-between text-sm">
          <span class="text-red-700">{{ record.book_title }} → {{ record.borrower_name }}</span>
          <span class="text-red-600">{{ t('lending.due', { date: record.due_date }) }}</span>
        </div>
      </div>
    </div>

    <!-- Filter -->
    <div class="flex gap-4">
      <select v-model="statusFilter" @change="loadLending" class="input-field max-w-xs">
        <option value="all">{{ t('lending.filter_all') }}</option>
        <option value="active">{{ t('lending.filter_active') }}</option>
        <option value="overdue">{{ t('lending.filter_overdue') }}</option>
        <option value="returned">{{ t('lending.filter_returned') }}</option>
      </select>
    </div>

    <!-- Lending Records Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-gray-50">
            <tr>
              <th class="text-start py-3 px-4 font-medium text-gray-600">{{ t('lending.book') }}</th>
              <th class="text-start py-3 px-4 font-medium text-gray-600">{{ t('lending.borrower') }}</th>
              <th class="text-start py-3 px-4 font-medium text-gray-600">{{ t('lending.lent_date') }}</th>
              <th class="text-start py-3 px-4 font-medium text-gray-600">{{ t('lending.due_date') }}</th>
              <th class="text-start py-3 px-4 font-medium text-gray-600">{{ t('lending.status') }}</th>
              <th class="text-start py-3 px-4 font-medium text-gray-600">{{ t('lending.actions') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="record in records" :key="record.id" class="border-t hover:bg-gray-50">
              <td class="py-3 px-4">
                <p class="font-medium text-gray-900">{{ record.book_title }}</p>
                <p class="text-xs text-gray-500">{{ record.book_author }}</p>
              </td>
              <td class="py-3 px-4">{{ record.borrower_name }}</td>
              <td class="py-3 px-4">{{ record.lent_date }}</td>
              <td class="py-3 px-4">{{ record.due_date }}</td>
              <td class="py-3 px-4">
                <span :class="['px-2 py-1 rounded-full text-xs font-medium',
                              record.status === 'returned' ? 'bg-green-100 text-green-700' :
                              record.status === 'overdue' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700']">
                  {{ record.status }}
                </span>
              </td>
              <td class="py-3 px-4">
                <button v-if="record.status !== 'returned'" @click="returnBook(record.id)"
                        class="text-green-600 hover:text-green-800 text-xs font-medium">
                  {{ t('lending.return_book') }}
                </button>
              </td>
            </tr>
            <tr v-if="records.length === 0">
              <td colspan="6" class="py-8 text-center text-gray-500">{{ t('lending.no_records') }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Lend Book Modal -->
    <div v-if="showLendModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl max-w-md w-full p-6 space-y-4">
        <h2 class="text-lg font-semibold">📤 {{ t('lending.lend_a_book') }}</h2>
        <form @submit.prevent="lendBook" class="space-y-4">
          <div>
            <label class="label">{{ t('lending.book_id') }} *</label>
            <input v-model="lendForm.book_id" type="text" required class="input-field"
                   :placeholder="t('lending.book_id_placeholder')" />
          </div>
          <div>
            <label class="label">{{ t('lending.borrower_name') }} *</label>
            <input v-model="lendForm.borrower_name" type="text" required class="input-field"
                   :placeholder="t('lending.borrower_placeholder')" />
          </div>
          <div>
            <label class="label">{{ t('lending.contact') }}</label>
            <input v-model="lendForm.borrower_contact" type="text" class="input-field"
                   :placeholder="t('lending.contact_placeholder')" />
          </div>
          <div>
            <label class="label">{{ t('lending.due_date') }} *</label>
            <input v-model="lendForm.due_date" type="date" required class="input-field" />
          </div>
          <div>
            <label class="label">{{ t('lending.notes') }}</label>
            <textarea v-model="lendForm.notes" class="input-field" rows="2"></textarea>
          </div>
          <div class="flex justify-end space-x-3 rtl:space-x-reverse">
            <button type="button" @click="showLendModal = false" class="px-4 py-2 border rounded-lg">{{ t('cancel') }}</button>
            <button type="submit" class="btn-primary">{{ t('lending.confirm_lending') }}</button>
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
const records = ref([])
const overdueBooks = ref([])
const showLendModal = ref(false)
const statusFilter = ref('all')

const lendForm = reactive({
  book_id: '',
  borrower_name: '',
  borrower_contact: '',
  due_date: '',
  notes: '',
})

async function loadLending() {
  try {
    const response = await api.get('/lending', { params: { status: statusFilter.value } })
    records.value = response.data.data
  } catch (err) {
    console.error('Failed to load lending records:', err)
  }
}

async function loadOverdue() {
  try {
    const response = await api.get('/lending/overdue')
    overdueBooks.value = response.data.data
  } catch (err) {
    console.error('Failed to load overdue books:', err)
  }
}

async function lendBook() {
  try {
    await api.post('/lending', lendForm)
    toastStore.success(t('lending.lent_success'))
    showLendModal.value = false
    Object.assign(lendForm, { book_id: '', borrower_name: '', borrower_contact: '', due_date: '', notes: '' })
    loadLending()
  } catch (err) {
    toastStore.error(err.response?.data?.error || t('lending.lend_failed'))
  }
}

async function returnBook(id) {
  if (!confirm(t('lending.return_confirm'))) return
  try {
    await api.post(`/lending/${id}/return`)
    toastStore.success(t('lending.returned_success'))
    loadLending()
    loadOverdue()
  } catch (err) {
    toastStore.error(t('lending.return_failed'))
  }
}

onMounted(() => {
  loadLending()
  loadOverdue()
})
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
