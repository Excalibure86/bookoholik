<template>
  <div class="max-w-3xl mx-auto space-y-8">
    <h1 class="text-2xl font-bold text-gray-900">⚙️ {{ t('settings.title') }}</h1>

    <!-- Profile Information -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
      <h2 class="text-lg font-semibold text-gray-800 mb-4">👤 {{ t('settings.profile_info') }}</h2>
      <form @submit.prevent="updateProfile" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="label">{{ t('settings.full_name') }}</label>
            <input v-model="profileForm.full_name" type="text" class="input-field"
                   :placeholder="t('settings.full_name')" />
          </div>
          <div>
            <label class="label">{{ t('settings.username') }}</label>
            <input v-model="profileForm.username" type="text" class="input-field"
                   :placeholder="t('settings.username')" />
          </div>
          <div class="md:col-span-2">
            <label class="label">{{ t('settings.email') }}</label>
            <input v-model="profileForm.email" type="email" class="input-field"
                   :placeholder="t('settings.email')" />
          </div>
        </div>

        <div v-if="profileMsg" :class="['text-sm p-3 rounded-lg', profileSuccess ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700']">
          {{ profileMsg }}
        </div>

        <div class="flex justify-end">
          <button type="submit" :disabled="profileLoading" class="btn-primary">
            {{ profileLoading ? t('loading') : t('settings.update_profile') }}
          </button>
        </div>
      </form>
    </div>

    <!-- Change Password -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
      <h2 class="text-lg font-semibold text-gray-800 mb-4">🔒 {{ t('settings.change_password') }}</h2>
      <form @submit.prevent="changePassword" class="space-y-4">
        <div>
          <label class="label">{{ t('settings.current_password') }}</label>
          <input v-model="passwordForm.current_password" type="password" required class="input-field"
                 :placeholder="t('settings.current_password')" />
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="label">{{ t('settings.new_password') }}</label>
            <input v-model="passwordForm.new_password" type="password" required class="input-field"
                   :placeholder="t('settings.new_password')" />
          </div>
          <div>
            <label class="label">{{ t('settings.confirm_password') }}</label>
            <input v-model="passwordForm.confirm_password" type="password" required class="input-field"
                   :placeholder="t('settings.confirm_password')" />
          </div>
        </div>

        <p class="text-xs text-gray-500">{{ t('settings.password_requirements') }}</p>

        <div v-if="passwordMsg" :class="['text-sm p-3 rounded-lg', passwordSuccess ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700']">
          {{ passwordMsg }}
        </div>

        <div class="flex justify-end">
          <button type="submit" :disabled="passwordLoading" class="btn-primary">
            {{ passwordLoading ? t('loading') : t('settings.update_password') }}
          </button>
        </div>
      </form>
    </div>

    <!-- Account Info (read-only) -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
      <h2 class="text-lg font-semibold text-gray-800 mb-4">ℹ️ {{ t('settings.account_info') }}</h2>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
        <div>
          <p class="text-gray-500">{{ t('settings.role') }}</p>
          <p class="font-medium capitalize">{{ user?.role }}</p>
        </div>
        <div>
          <p class="text-gray-500">{{ t('settings.member_since') }}</p>
          <p class="font-medium">{{ formatDate(user?.created_at) }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import api from '../services/api'
import { useAuthStore } from '../store/auth'
import { useToastStore } from '../store/toast'

const { t } = useI18n()
const authStore = useAuthStore()
const toastStore = useToastStore()

const user = computed(() => authStore.user)

// Profile form
const profileForm = reactive({
  full_name: '',
  username: '',
  email: '',
})
const profileLoading = ref(false)
const profileMsg = ref('')
const profileSuccess = ref(false)

// Password form
const passwordForm = reactive({
  current_password: '',
  new_password: '',
  confirm_password: '',
})
const passwordLoading = ref(false)
const passwordMsg = ref('')
const passwordSuccess = ref(false)

function formatDate(dateStr) {
  if (!dateStr) return '—'
  return new Date(dateStr).toLocaleDateString()
}

async function loadProfile() {
  try {
    const response = await api.get('/auth/me')
    const userData = response.data.user
    profileForm.full_name = userData.full_name || ''
    profileForm.username = userData.username || ''
    profileForm.email = userData.email || ''
  } catch (err) {
    console.error('Failed to load profile:', err)
  }
}

async function updateProfile() {
  profileLoading.value = true
  profileMsg.value = ''
  profileSuccess.value = false

  try {
    const response = await api.put('/auth/profile', profileForm)
    profileMsg.value = response.data.message || t('settings.profile_updated')
    profileSuccess.value = true
    // Update local store
    if (response.data.user) {
      authStore.user = response.data.user
      localStorage.setItem('auth_user', JSON.stringify(response.data.user))
    }
  } catch (err) {
    profileMsg.value = err.response?.data?.error || err.response?.data?.errors?.[0] || t('settings.update_failed')
    profileSuccess.value = false
  } finally {
    profileLoading.value = false
  }
}

async function changePassword() {
  passwordLoading.value = true
  passwordMsg.value = ''
  passwordSuccess.value = false

  if (passwordForm.new_password !== passwordForm.confirm_password) {
    passwordMsg.value = t('settings.passwords_no_match')
    passwordSuccess.value = false
    passwordLoading.value = false
    return
  }

  if (passwordForm.new_password.length < 10) {
    passwordMsg.value = t('settings.password_too_short')
    passwordSuccess.value = false
    passwordLoading.value = false
    return
  }

  try {
    const response = await api.put('/auth/password', {
      current_password: passwordForm.current_password,
      new_password: passwordForm.new_password,
    })
    passwordMsg.value = response.data.message || t('settings.password_updated')
    passwordSuccess.value = true
    // Clear form
    Object.assign(passwordForm, { current_password: '', new_password: '', confirm_password: '' })
  } catch (err) {
    passwordMsg.value = err.response?.data?.error || err.response?.data?.errors?.[0] || t('settings.update_failed')
    passwordSuccess.value = false
  } finally {
    passwordLoading.value = false
  }
}

onMounted(loadProfile)
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
