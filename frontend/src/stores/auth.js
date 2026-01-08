import { ref, computed } from 'vue'
import { defineStore } from 'pinia'
import { api } from '../services/api'
import { useRouter } from 'vue-router'

export const useAuthStore = defineStore('auth', () => {
  const token = ref(localStorage.getItem('auth_token'))
  const user = ref(null)
  const router = useRouter()

  // Проверка, авторизован ли пользователь
  const isAuthenticated = computed(() => {
    return !!token.value
  })

  // Установка токена
  function setToken(newToken) {
    token.value = newToken
    if (newToken) {
      localStorage.setItem('auth_token', newToken)
    } else {
      localStorage.removeItem('auth_token')
    }
  }

  // Установка пользователя
  function setUser(userData) {
    user.value = userData
  }

  // Выход из системы
  async function logout() {
    try {
      await api('/logout', {
        method: 'POST'
      })
    } catch (error) {
      console.error('Logout error:', error)
    } finally {
      // В любом случае очищаем токен и пользователя
      setToken(null)
      setUser(null)
      router.push('/login')
    }
  }

  // Загрузка данных пользователя
  async function fetchUser() {
    try {
      const response = await api('/user')
      setUser(response.user)
      return response.user
    } catch (error) {
      setToken(null)
      setUser(null)
      throw error
    }
  }

  // Инициализация: загружаем пользователя, если есть токен
  async function init() {
    if (token.value && !user.value) {
      try {
        await fetchUser()
      } catch (error) {
        // Если не удалось загрузить пользователя, токен невалидный
        setToken(null)
      }
    }
  }

  return {
    token,
    user,
    isAuthenticated,
    setToken,
    setUser,
    logout,
    fetchUser,
    init
  }
})

