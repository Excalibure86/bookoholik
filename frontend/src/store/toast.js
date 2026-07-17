import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useToastStore = defineStore('toast', () => {
  const toasts = ref([])
  let nextId = 0

  function addToast(message, type = 'info', duration = 4000) {
    const id = nextId++
    toasts.value.push({ id, message, type })
    setTimeout(() => {
      toasts.value = toasts.value.filter(t => t.id !== id)
    }, duration)
  }

  function success(message) { addToast(message, 'success') }
  function error(message) { addToast(message, 'error', 6000) }
  function info(message) { addToast(message, 'info') }

  return { toasts, addToast, success, error, info }
})
