<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-primary-50 to-blue-100">
    <div class="max-w-md w-full mx-4">
      <div class="text-center mb-8">
        <img src="/logo.png" alt="Bookoholik" class="w-32 h-32 mx-auto object-contain" />
        <h1 class="mt-4 text-3xl font-bold text-gray-900">{{ t('app_name') }}</h1>
        <p class="mt-2 text-gray-600">{{ t('app_subtitle') }}</p>
      </div>

      <!-- Language Switcher on Login -->
      <div class="flex justify-center mb-4">
        <select v-model="currentLocale" @change="switchLanguage"
                class="text-sm border border-gray-300 rounded-md px-3 py-1.5 bg-white focus:ring-2 focus:ring-primary-500 outline-none">
          <option value="en">🇬🇧 English</option>
          <option value="ar">🇸🇦 العربية</option>
          <option value="fr">🇫🇷 Français</option>
        </select>
      </div>

      <div class="bg-white rounded-xl shadow-lg p-8">
        <h2 class="text-xl font-semibold text-gray-800 mb-6">
          {{ isRegister ? t('auth.create_account') : t('auth.sign_in') }}
        </h2>

        <form @submit.prevent="handleSubmit" class="space-y-4">
          <div v-if="isRegister">
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('auth.full_name') }}</label>
            <input v-model="form.full_name" type="text" required
                   class="input-field" :placeholder="t('auth.full_name')" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('auth.username') }}</label>
            <input v-model="form.username" type="text" required
                   class="input-field" :placeholder="t('auth.username')" />
          </div>

          <div v-if="isRegister">
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('auth.email') }}</label>
            <input v-model="form.email" type="email" required
                   class="input-field" placeholder="email@example.com" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('auth.password') }}</label>
            <input v-model="form.password" type="password" required
                   class="input-field" placeholder="••••••••" />
          </div>

          <div v-if="errorMsg" class="text-red-600 text-sm bg-red-50 p-3 rounded-lg">
            {{ errorMsg }}
          </div>

          <button type="submit" :disabled="loading"
                  class="w-full btn-primary flex items-center justify-center">
            <svg v-if="loading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
            {{ isRegister ? t('auth.create_account') : t('auth.sign_in') }}
          </button>
        </form>

        <div class="mt-4 text-center">
          <button @click="isRegister = !isRegister" class="text-sm text-primary-600 hover:text-primary-800">
            {{ isRegister ? t('auth.already_have_account') : t('auth.no_account') }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '../store/auth'
import { useToastStore } from '../store/toast'
import { setLocale } from '../i18n'

const { t } = useI18n()
const router = useRouter()
const authStore = useAuthStore()
const toastStore = useToastStore()

const isRegister = ref(false)
const loading = ref(false)
const errorMsg = ref('')
const currentLocale = ref(localStorage.getItem('app_locale') || 'en')

const form = reactive({
  username: '',
  email: '',
  password: '',
  full_name: '',
})

function switchLanguage() {
  setLocale(currentLocale.value)
}

async function handleSubmit() {
  loading.value = true
  errorMsg.value = ''

  try {
    if (isRegister.value) {
      await authStore.register(form)
      toastStore.success(t('auth.account_created'))
      isRegister.value = false
    } else {
      await authStore.login({ username: form.username, password: form.password })
      toastStore.success(t('auth.welcome_back'))
      router.push('/')
    }
  } catch (err) {
    errorMsg.value = err.response?.data?.error || err.response?.data?.errors?.[0] || t('auth.error_occurred')
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.input-field {
  @apply w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none transition-colors;
}

.btn-primary {
  @apply px-4 py-2.5 bg-primary-600 text-white rounded-lg font-medium hover:bg-primary-700 focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-colors disabled:opacity-50;
}
</style>
