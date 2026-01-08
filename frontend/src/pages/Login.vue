<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { api } from '../services/api'
import { useAuthStore } from '@/stores/auth'
import MainLayout from "@/components/MainLayout.vue";

const email = ref('')
const password = ref('')
const loading = ref(false)
const error = ref(null)
const router = useRouter()
const authStore = useAuthStore()

async function submit() {
  loading.value = true
  error.value = null

  try {
    const data = await api('/login', {
      method: 'POST',
      body: {
        email: email.value,
        password: password.value
      }
    })

    // Сохраняем токен и данные пользователя
    authStore.setToken(data.token)
    authStore.setUser(data.user)

    // Редиректим на главную или на страницу, с которой пришли
    router.push('/muscles')
  } catch (e) {
    error.value = e.message || e.errors?.email?.[0] || 'Ошибка входа'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <MainLayout>
  <h1>Login</h1>

  <form @submit.prevent="submit">
    <input v-model="email" placeholder="Email" />
    <input v-model="password" type="password" placeholder="Password" />

    <button :disabled="loading">
      {{ loading ? 'Loading...' : 'Login' }}
    </button>

    <p v-if="error" style="color:red">{{ error }}</p>
  </form>
  </MainLayout>
</template>
