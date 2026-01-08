<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { api } from '../services/api'
import { useAuthStore } from '@/stores/auth'
import MainLayout from "@/components/MainLayout.vue";

const name = ref('')
const email = ref('')
const password = ref('')
const passwordConfirmation = ref('')
const loading = ref(false)
const error = ref(null)
const success = ref(false)
const router = useRouter()
const authStore = useAuthStore()

async function submit() {
  loading.value = true
  error.value = null
  success.value = false

  // Проверка совпадения паролей
  if (password.value !== passwordConfirmation.value) {
    error.value = 'Пароли не совпадают'
    loading.value = false
    return
  }

  try {
    const data = await api('/register', {
      method: 'POST',
      body: {
        name: name.value,
        email: email.value,
        password: password.value,
        password_confirmation: passwordConfirmation.value
      }
    })

    // Сохраняем токен и данные пользователя
    authStore.setToken(data.token)
    authStore.setUser(data.user)

    success.value = true
    
    // Редиректим на главную после успешной регистрации
    setTimeout(() => {
      router.push('/muscles')
    }, 1000)
  } catch (e) {
    // Обработка ошибок валидации от Laravel
    if (e.errors) {
      const firstError = Object.values(e.errors)[0]
      error.value = Array.isArray(firstError) ? firstError[0] : firstError
    } else {
      error.value = e.message || 'Ошибка регистрации'
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <MainLayout>
  <h1>Register</h1>

  <form @submit.prevent="submit">
    <input v-model="name" placeholder="Имя" required />
    <input v-model="email" type="email" placeholder="Email" required />
    <input v-model="password" type="password" placeholder="Пароль" required />
    <input v-model="passwordConfirmation" type="password" placeholder="Подтверждение пароля" required />

    <button :disabled="loading">
      {{ loading ? 'Регистрация...' : 'Зарегистрироваться' }}
    </button>

    <p v-if="error" style="color:red">{{ error }}</p>
    <p v-if="success" style="color:green">Аккаунт успешно создан! Перенаправление...</p>
  </form>
  </MainLayout>
</template>
