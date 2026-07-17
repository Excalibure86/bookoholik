<template>
  <div id="app" class="min-h-screen bg-gray-50">
    <!-- Navigation -->
    <nav v-if="isAuthenticated" class="bg-white shadow-sm border-b border-gray-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex items-center">
            <router-link to="/" class="flex items-center space-x-2 rtl:space-x-reverse">
              <img src="/logo.png" alt="Bookoholik" class="h-9 w-9 object-contain" />
              <span class="text-xl font-bold text-primary-700">{{ t('app_name') }}</span>
            </router-link>
            
            <div class="hidden md:flex ltr:ml-10 rtl:mr-10 space-x-4 rtl:space-x-reverse">
              <router-link to="/books" class="nav-link">{{ t('nav.books') }}</router-link>
              <router-link to="/writers" class="nav-link">{{ t('nav.writers') }}</router-link>
              <router-link to="/genres" class="nav-link">{{ t('nav.genres') }}</router-link>
              <router-link to="/lending" class="nav-link">{{ t('nav.lending') }}</router-link>
              <router-link to="/reports" class="nav-link">{{ t('nav.reports') }}</router-link>
              <router-link v-if="isAdmin" to="/users" class="nav-link">{{ t('nav.users') }}</router-link>
              <router-link v-if="isAdmin" to="/backup" class="nav-link">{{ t('nav.backup') }}</router-link>
            </div>
          </div>
          
          <div class="flex items-center space-x-4 rtl:space-x-reverse">
            <!-- Language Switcher -->
            <select v-model="currentLocale" @change="switchLanguage" 
                    class="text-sm border border-gray-300 rounded-md px-2 py-1 bg-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none">
              <option value="en">🇬🇧 English</option>
              <option value="ar">🇸🇦 العربية</option>
              <option value="fr">🇫🇷 Français</option>
            </select>
            <router-link to="/settings" class="text-sm text-gray-600 hover:text-primary-700">
              ⚙️ {{ user?.full_name }}
            </router-link>
            <button @click="logout" class="text-sm text-red-600 hover:text-red-800">{{ t('logout') }}</button>
          </div>
        </div>
      </div>

      <!-- Mobile menu -->
      <div class="md:hidden border-t border-gray-200 px-4 py-2 space-y-1">
        <router-link to="/books" class="block nav-link">{{ t('nav.books') }}</router-link>
        <router-link to="/writers" class="block nav-link">{{ t('nav.writers') }}</router-link>
        <router-link to="/genres" class="block nav-link">{{ t('nav.genres') }}</router-link>
        <router-link to="/lending" class="block nav-link">{{ t('nav.lending') }}</router-link>
        <router-link to="/reports" class="block nav-link">{{ t('nav.reports') }}</router-link>
        <router-link v-if="isAdmin" to="/users" class="block nav-link">{{ t('nav.users') }}</router-link>
        <router-link v-if="isAdmin" to="/backup" class="block nav-link">{{ t('nav.backup') }}</router-link>
        <router-link to="/settings" class="block nav-link">⚙️ {{ t('nav.settings') }}</router-link>
      </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
      <router-view />
    </main>

    <!-- Toast Notifications -->
    <div class="fixed top-4 ltr:right-4 rtl:left-4 z-50 space-y-2">
      <transition-group name="toast">
        <div v-for="toast in toasts" :key="toast.id"
             :class="['px-4 py-3 rounded-lg shadow-lg text-white text-sm max-w-sm',
                      toast.type === 'success' ? 'bg-green-500' :
                      toast.type === 'error' ? 'bg-red-500' : 'bg-blue-500']">
          {{ toast.message }}
        </div>
      </transition-group>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from './store/auth'
import { useToastStore } from './store/toast'
import { setLocale } from './i18n'

const { t } = useI18n()
const router = useRouter()
const authStore = useAuthStore()
const toastStore = useToastStore()

const currentLocale = ref(localStorage.getItem('app_locale') || 'en')
const isAuthenticated = computed(() => authStore.isAuthenticated)
const user = computed(() => authStore.user)
const isAdmin = computed(() => authStore.user?.role === 'admin')
const toasts = computed(() => toastStore.toasts)

function switchLanguage() {
  setLocale(currentLocale.value)
}

function logout() {
  authStore.logout()
  router.push('/login')
}
</script>

<style scoped>
.nav-link {
  @apply px-3 py-2 rounded-md text-sm font-medium text-gray-600 hover:text-primary-700 hover:bg-primary-50 transition-colors;
}

.router-link-active {
  @apply text-primary-700 bg-primary-50;
}
</style>
