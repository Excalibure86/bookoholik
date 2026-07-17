import { createI18n } from 'vue-i18n'
import en from './en.js'
import ar from './ar.js'
import fr from './fr.js'

const savedLocale = localStorage.getItem('app_locale') || 'en'

const i18n = createI18n({
  legacy: false,
  locale: savedLocale,
  fallbackLocale: 'en',
  messages: { en, ar, fr },
})

export default i18n

export function setLocale(locale) {
  i18n.global.locale.value = locale
  localStorage.setItem('app_locale', locale)
  // Set document direction for RTL support
  document.documentElement.dir = locale === 'ar' ? 'rtl' : 'ltr'
  document.documentElement.lang = locale
}

export function getLocale() {
  return i18n.global.locale.value
}
