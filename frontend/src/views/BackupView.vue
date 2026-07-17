<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold text-gray-900">💾 {{ t('backup.title') }}</h1>
      <button @click="createBackup" :disabled="creatingBackup" class="btn-primary">
        {{ creatingBackup ? t('backup.creating') : '➕ ' + t('backup.create_backup') }}
      </button>
    </div>

    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 text-sm text-blue-800">
      <p><strong>ℹ️</strong> {{ t('backup.auto_info') }}</p>
    </div>

    <!-- Backup List -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-gray-50">
          <tr>
            <th class="text-start py-3 px-4 font-medium text-gray-600">{{ t('backup.filename') }}</th>
            <th class="text-start py-3 px-4 font-medium text-gray-600">{{ t('backup.size') }}</th>
            <th class="text-start py-3 px-4 font-medium text-gray-600">{{ t('backup.created') }}</th>
            <th class="text-start py-3 px-4 font-medium text-gray-600">{{ t('backup.actions') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="backup in backups" :key="backup.filename" class="border-t hover:bg-gray-50">
            <td class="py-3 px-4 font-mono text-xs">{{ backup.filename }}</td>
            <td class="py-3 px-4">{{ backup.size_human }}</td>
            <td class="py-3 px-4">{{ backup.created_at }}</td>
            <td class="py-3 px-4 space-x-3 rtl:space-x-reverse">
              <a :href="apiUrl + '/backup/download/' + backup.filename" class="text-blue-600 hover:text-blue-800 text-xs">
                {{ t('backup.download') }}
              </a>
              <button @click="deleteBackup(backup.filename)" class="text-red-600 hover:text-red-800 text-xs">
                {{ t('backup.delete') }}
              </button>
            </td>
          </tr>
          <tr v-if="backups.length === 0">
            <td colspan="4" class="py-8 text-center text-gray-500">{{ t('backup.no_backups') }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import api from '../services/api'
import { useToastStore } from '../store/toast'

const { t } = useI18n()
const toastStore = useToastStore()
const apiUrl = import.meta.env.VITE_API_URL || '/api'
const backups = ref([])
const creatingBackup = ref(false)

async function loadBackups() {
  try {
    const response = await api.get('/backup/list')
    backups.value = response.data.data
  } catch (err) {
    toastStore.error(t('backup.load_failed'))
  }
}

async function createBackup() {
  creatingBackup.value = true
  try {
    await api.post('/backup/create')
    toastStore.success(t('backup.created_success'))
    loadBackups()
  } catch (err) {
    toastStore.error(err.response?.data?.error || t('backup.create_failed'))
  } finally {
    creatingBackup.value = false
  }
}

async function deleteBackup(filename) {
  if (!confirm(t('backup.delete_confirm'))) return
  try {
    await api.delete(`/backup/${filename}`)
    toastStore.success(t('backup.deleted'))
    loadBackups()
  } catch (err) {
    toastStore.error(t('backup.delete_failed'))
  }
}

onMounted(loadBackups)
</script>

<style scoped>
.btn-primary {
  @apply px-4 py-2 bg-primary-600 text-white rounded-lg font-medium hover:bg-primary-700 transition-colors text-sm disabled:opacity-50;
}
</style>
