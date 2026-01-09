<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { api } from '../services/api'
import { useAuthStore } from '@/stores/auth'
import MainLayout from "@/components/MainLayout.vue"

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
      router.push('/')
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

<style scoped>
.auth-page {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: calc(100vh - 200px);
  padding: 40px 20px;
}

.auth-container {
  width: 100%;
  max-width: 450px;
  background: white;
  border-radius: 12px;
  padding: 40px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.auth-title {
  font-size: 2rem;
  font-weight: 600;
  color: #2c3e50;
  margin: 0 0 8px 0;
  text-align: center;
}

.auth-subtitle {
  font-size: 0.95rem;
  color: #666;
  text-align: center;
  margin: 0 0 30px 0;
}

.auth-form {
  width: 100%;
}

.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  margin-bottom: 8px;
  font-weight: 500;
  color: #34495e;
  font-size: 14px;
}

.form-input {
  width: 100%;
  padding: 12px 16px;
  border: 2px solid #e0e0e0;
  border-radius: 8px;
  font-size: 16px;
  transition: border-color 0.2s, box-shadow 0.2s;
  background-color: #fff;
}

.form-input:focus {
  outline: none;
  border-color: #3498db;
  box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
}

.form-input:disabled {
  background-color: #f5f5f5;
  cursor: not-allowed;
  opacity: 0.6;
}

.error-message {
  background-color: #fee;
  color: #e74c3c;
  padding: 12px;
  border-radius: 6px;
  margin-bottom: 20px;
  font-size: 14px;
  border-left: 4px solid #e74c3c;
}

.success-message {
  background-color: #d4edda;
  color: #155724;
  padding: 12px;
  border-radius: 6px;
  margin-bottom: 20px;
  font-size: 14px;
  border-left: 4px solid #28a745;
}

.submit-btn {
  width: 100%;
  padding: 14px;
  background-color: #000000;
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 16px;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.2s, transform 0.1s;
  margin-top: 10px;
}

.submit-btn:hover:not(:disabled) {
  background-color: #333333;
  transform: translateY(-1px);
}

.submit-btn:active:not(:disabled) {
  transform: translateY(0);
}

.submit-btn:disabled {
  background-color: #95a5a6;
  cursor: not-allowed;
  transform: none;
}

.auth-footer {
  margin-top: 24px;
  text-align: center;
  padding-top: 24px;
  border-top: 1px solid #e9ecef;
}

.auth-footer p {
  margin: 0;
  color: #666;
  font-size: 14px;
}

.auth-link {
  color: #3498db;
  font-weight: 500;
  text-decoration: none;
  transition: color 0.2s;
}

.auth-link:hover {
  color: #2980b9;
  text-decoration: underline;
}

@media (max-width: 480px) {
  .auth-container {
    padding: 30px 20px;
  }

  .auth-title {
    font-size: 1.75rem;
  }
}
</style>

<template>
  <MainLayout>
    <div class="auth-page">
      <div class="auth-container">
        <h1 class="auth-title">Регистрация</h1>
        <p class="auth-subtitle">Создайте аккаунт, чтобы начать отслеживать свои тренировки</p>

        <form @submit.prevent="submit" class="auth-form">
          <div class="form-group">
            <label for="name">Имя</label>
            <input
              id="name"
              v-model="name"
              type="text"
              placeholder="Ваше имя"
              class="form-input"
              required
              :disabled="loading"
            />
          </div>

          <div class="form-group">
            <label for="email">Email</label>
            <input
              id="email"
              v-model="email"
              type="email"
              placeholder="your@email.com"
              class="form-input"
              required
              :disabled="loading"
            />
          </div>

          <div class="form-group">
            <label for="password">Пароль</label>
            <input
              id="password"
              v-model="password"
              type="password"
              placeholder="Минимум 8 символов"
              class="form-input"
              required
              minlength="8"
              :disabled="loading"
            />
          </div>

          <div class="form-group">
            <label for="password_confirmation">Подтверждение пароля</label>
            <input
              id="password_confirmation"
              v-model="passwordConfirmation"
              type="password"
              placeholder="Повторите пароль"
              class="form-input"
              required
              minlength="8"
              :disabled="loading"
            />
          </div>

          <div v-if="error" class="error-message">
            {{ error }}
          </div>

          <div v-if="success" class="success-message">
            Аккаунт успешно создан! Перенаправление...
          </div>

          <button type="submit" class="submit-btn" :disabled="loading">
            {{ loading ? 'Регистрация...' : 'Зарегистрироваться' }}
          </button>

          <div class="auth-footer">
            <p>
              Уже есть аккаунт?
              <router-link to="/login" class="auth-link">Войти</router-link>
            </p>
          </div>
        </form>
      </div>
    </div>
  </MainLayout>
</template>
