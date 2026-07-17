<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold text-gray-900">👥 {{ t('users.title') }}</h1>
      <button @click="showCreateModal = true" class="btn-primary">➕ {{ t('users.add_user') }}</button>
    </div>

    <!-- Users Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-gray-50">
          <tr>
            <th class="text-start py-3 px-4 font-medium text-gray-600">{{ t('users.name') }}</th>
            <th class="text-start py-3 px-4 font-medium text-gray-600">{{ t('users.username') }}</th>
            <th class="text-start py-3 px-4 font-medium text-gray-600">{{ t('users.email') }}</th>
            <th class="text-start py-3 px-4 font-medium text-gray-600">{{ t('users.role') }}</th>
            <th class="text-start py-3 px-4 font-medium text-gray-600">{{ t('users.status') }}</th>
            <th class="text-start py-3 px-4 font-medium text-gray-600">{{ t('users.actions') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="user in users" :key="user.id" class="border-t hover:bg-gray-50">
            <td class="py-3 px-4 font-medium">{{ user.full_name }}</td>
            <td class="py-3 px-4">{{ user.username }}</td>
            <td class="py-3 px-4">{{ user.email }}</td>
            <td class="py-3 px-4">
              <span :class="['px-2 py-1 rounded-full text-xs',
                            user.role === 'admin' ? 'bg-purple-100 text-purple-700' :
                            user.role === 'viewer' ? 'bg-gray-100 text-gray-700' : 'bg-blue-100 text-blue-700']">
                {{ user.role }}
              </span>
            </td>
            <td class="py-3 px-4">
              <span :class="user.is_active ? 'text-green-600' : 'text-red-600'">
                {{ user.is_active ? t('users.active') : t('users.inactive') }}
              </span>
            </td>
            <td class="py-3 px-4 space-x-2 rtl:space-x-reverse">
              <button @click="toggleUserStatus(user)" class="text-xs text-blue-600 hover:text-blue-800">
                {{ user.is_active ? t('users.disable') : t('users.enable') }}
              </button>
              <button @click="deleteUser(user)" class="text-xs text-red-600 hover:text-red-800">{{ t('delete') }}</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Create User Modal -->
    <div v-if="showCreateModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl max-w-md w-full p-6 space-y-4">
        <h2 class="text-lg font-semibold">➕ {{ t('users.create_new_user') }}</h2>
        <form @submit.prevent="createUser" class="space-y-4">
          <div>
            <label class="label">{{ t('users.name') }} *</label>
            <input v-model="newUser.full_name" type="text" required class="input-field" />
          </div>
          <div>
            <label class="label">{{ t('users.username') }} *</label>
            <input v-model="newUser.username" type="text" required class="input-field" />
          </div>
          <div>
            <label class="label">{{ t('users.email') }} *</label>
            <input v-model="newUser.email" type="email" required class="input-field" />
          </div>
          <div>
            <label class="label">{{ t('auth.password') }} *</label>
            <input v-model="newUser.password" type="password" required class="input-field" />
          </div>
          <div>
            <label class="label">{{ t('users.role') }}</label>
            <select v-model="newUser.role" class="input-field">
              <option value="user">{{ t('users.role_user') }}</option>
              <option value="admin">{{ t('users.role_admin') }}</option>
              <option value="viewer">{{ t('users.role_viewer') }}</option>
            </select>
          </div>
          <div class="flex justify-end space-x-3 rtl:space-x-reverse">
            <button type="button" @click="showCreateModal = false" class="px-4 py-2 border rounded-lg">{{ t('cancel') }}</button>
            <button type="submit" class="btn-primary">{{ t('users.add_user') }}</button>
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
const users = ref([])
const showCreateModal = ref(false)
const newUser = reactive({ full_name: '', username: '', email: '', password: '', role: 'user' })

async function loadUsers() {
  try {
    const response = await api.get('/users')
    users.value = response.data.data
  } catch (err) {
    toastStore.error(t('users.load_failed'))
  }
}

async function createUser() {
  try {
    await api.post('/auth/register', newUser)
    toastStore.success(t('users.user_created'))
    showCreateModal.value = false
    Object.assign(newUser, { full_name: '', username: '', email: '', password: '', role: 'user' })
    loadUsers()
  } catch (err) {
    toastStore.error(err.response?.data?.error || t('users.create_failed'))
  }
}

async function toggleUserStatus(user) {
  try {
    await api.put(`/users/${user.id}`, { is_active: !user.is_active })
    toastStore.success(t('users.status_updated'))
    loadUsers()
  } catch (err) {
    toastStore.error(t('users.update_failed'))
  }
}

async function deleteUser(user) {
  if (!confirm(t('users.delete_confirm', { name: user.full_name }))) return
  try {
    await api.delete(`/users/${user.id}`)
    toastStore.success(t('users.user_deleted'))
    loadUsers()
  } catch (err) {
    toastStore.error(err.response?.data?.error || t('users.delete_failed'))
  }
}

onMounted(loadUsers)
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
