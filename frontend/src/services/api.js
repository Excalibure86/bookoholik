import axios from 'axios'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || '/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
  timeout: 30000, // 30 second timeout
})

// Check if JWT token is expired
function isTokenExpired(token) {
  try {
    const payload = JSON.parse(atob(token.split('.')[1]))
    return payload.exp * 1000 < Date.now()
  } catch {
    return true
  }
}

// Clear auth and redirect to login (only if not already there)
function forceLogout() {
  localStorage.removeItem('auth_token')
  localStorage.removeItem('auth_user')
  if (window.location.pathname !== '/login') {
    window.location.href = '/login'
  }
}

// Request interceptor - attach JWT token (with expiry check)
api.interceptors.request.use(config => {
  const token = localStorage.getItem('auth_token')
  if (token) {
    if (isTokenExpired(token)) {
      forceLogout()
      return Promise.reject(new Error('Token expired'))
    }
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

// Response interceptor - handle auth errors
api.interceptors.response.use(
  response => response,
  error => {
    if (error.response?.status === 401) {
      forceLogout()
    }
    return Promise.reject(error)
  }
)

export default api
