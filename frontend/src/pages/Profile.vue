<template>
  <MainLayout>
    <div class="profile-page">
      <h1>Настройки профиля</h1>

      <div v-if="loading" class="loading">Загрузка...</div>
      <div v-if="error" class="error">{{ error }}</div>

      <form v-if="!loading && !error" @submit.prevent="handleSubmit" class="profile-form">
        <div class="form-section">
          <h2>Общие настройки</h2>
          <div class="form-group">
            <label for="name">Имя</label>
            <input
              id="name"
              v-model="form.name"
              type="text"
              class="form-input"
              required
              :disabled="saving"
            />
          </div>

          <div class="form-group">
            <label for="email">Email</label>
            <input
              id="email"
              v-model="form.email"
              type="email"
              class="form-input"
              required
              :disabled="saving"
            />
          </div>
        </div>

        <div class="form-section">
          <h2>Настройки тренировок</h2>
          <div class="form-group">
            <label for="muscles_level">Уровень мышц по умолчанию</label>
            <select
              id="muscles_level"
              v-model="form.muscles_level"
              class="form-input"
            >
              <option :value="1">1 - Ничего не знаю про зал</option>
              <option :value="2">2 - Новичок в зале</option>
              <option :value="3">3 - Опытный</option>
              <option :value="4">4 - Профессионал</option>
            </select>
          </div>
        </div>


        <div class="form-section">
          <h2>Изменить пароль</h2>
          <p class="form-hint">Оставьте поля пустыми, если не хотите менять пароль</p>

          <div class="form-group">
            <label for="current_password">Текущий пароль</label>
            <input
              id="current_password"
              v-model="form.current_password"
              type="password"
              class="form-input"
              :disabled="saving"
            />
          </div>

          <div class="form-group">
            <label for="password">Новый пароль</label>
            <input
              id="password"
              v-model="form.password"
              type="password"
              class="form-input"
              :disabled="saving"
              minlength="8"
            />
          </div>

          <div class="form-group">
            <label for="password_confirmation">Подтвердите новый пароль</label>
            <input
              id="password_confirmation"
              v-model="form.password_confirmation"
              type="password"
              class="form-input"
              :disabled="saving"
              minlength="8"
            />
          </div>
        </div>

        <div v-if="successMessage" class="success-message">
          {{ successMessage }}
        </div>

        <div v-if="formError" class="error-message">
          {{ formError }}
        </div>

        <div class="form-actions">
          <button type="submit" :disabled="saving || !isFormValid" class="submit-btn">
            {{ saving ? 'Сохранение...' : 'Сохранить изменения' }}
          </button>
          <button type="button" @click="resetForm" :disabled="saving" class="cancel-btn">
            Отмена
          </button>
        </div>
      </form>
    </div>
  </MainLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { api } from '@/services/api'
import MainLayout from '@/components/MainLayout.vue'

const authStore = useAuthStore()

const loading = ref(false)
const saving = ref(false)
const error = ref(null)
const formError = ref(null)
const successMessage = ref(null)

// Реактивный объект формы с начальными значениями.
const form = reactive({
  name: '',
  email: '',
  current_password: '',
  password: '',
  password_confirmation: '',
  muscles_level: 3
})

// Реактивный объект исходных значений для сброса.
const originalForm = reactive({
  name: '',
  email: '',
  muscles_level: 2
})

// Проверка валидности формы
const isFormValid = computed(() => {
  if (!form.name || !form.email) {
    return false
  }

  // Если указан новый пароль, должны быть заполнены все поля пароля
  if (form.password || form.password_confirmation || form.current_password) {
    if (!form.current_password || !form.password || !form.password_confirmation) {
      return false
    }
    if (form.password !== form.password_confirmation) {
      return false
    }
    if (form.password.length < 8) {
      return false
    }
  }

  return true
})

// Загрузка данных пользователя
async function loadUser() {
  loading.value = true
  error.value = null

  try {
    await authStore.fetchUser()
    if (authStore.user) {
      form.name = authStore.user.name || ''
      form.email = authStore.user.email || ''
      form.muscles_level = authStore.user.muscles_level ?? 3
      originalForm.name = authStore.user.name || ''
      originalForm.email = authStore.user.email || ''
      originalForm.muscles_level = authStore.user.muscles_level ?? 3
    }
  } catch (e) {
    error.value = e.message || 'Ошибка загрузки данных профиля'
  } finally {
    loading.value = false
  }
}

// Сброс формы
function resetForm() {
  form.name = originalForm.name
  form.email = originalForm.email
  form.muscles_level = originalForm.muscles_level
  form.current_password = ''
  form.password = ''
  form.password_confirmation = ''
  formError.value = null
  successMessage.value = null
}

// Сохранение изменений
async function handleSubmit() {
  saving.value = true
  formError.value = null
  successMessage.value = null

  try {
    const payload = {
      name: form.name,
      email: form.email,
      muscles_level: form.muscles_level
    }

    // Добавляем пароль только если он указан
    if (form.password) {
      payload.current_password = form.current_password
      payload.password = form.password
      payload.password_confirmation = form.password_confirmation
    }

    const response = await api('/user', {
      method: 'PUT',
      body: payload,
    })

    // Обновляем данные пользователя в store
    if (response.user) {
      authStore.setUser(response.user)
      originalForm.name = response.user.name
      originalForm.email = response.user.email
      originalForm.muscles_level = response.user.muscles_level ?? 3
    }

    successMessage.value = 'Профиль успешно обновлен!'

    // Очищаем поля пароля после успешного сохранения
    form.current_password = ''
    form.password = ''
    form.password_confirmation = ''

    // Скрываем сообщение об успехе через 3 секунды
    setTimeout(() => {
      successMessage.value = null
    }, 3000)
  } catch (e) {
    formError.value = e.message || 'Ошибка обновления профиля'
    if (e.errors) {
      formError.value = Object.values(e.errors).flat().join(' ')
    }
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  loadUser()
})
</script>

<style scoped>
.profile-page {
  padding: 20px;
  max-width: 800px;
  margin: 0 auto;
}

h1 {
  margin-bottom: 30px;
  color: #2c3e50;
}

.loading,
.error {
  padding: 20px;
  text-align: center;
}

.error {
  color: #e74c3c;
  background-color: #fee;
  border-radius: 4px;
  margin-bottom: 20px;
}

.profile-form {
  background: white;
  border-radius: 8px;
  padding: 30px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.form-section {
  margin-top: 30px;
  padding-top: 30px;
  border-top: 1px solid #eee;
}

.form-section h2 {
  margin-bottom: 10px;
  color: #2c3e50;
  font-size: 20px;
}

.form-hint {
  color: #666;
  font-size: 14px;
  margin-bottom: 20px;
}

.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  margin-bottom: 8px;
  font-weight: bold;
  color: #34495e;
}

.form-input {
  width: 100%;
  padding: 10px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 16px;
  box-sizing: border-box;
  transition: border-color 0.2s;
}

.form-input:focus {
  outline: none;
  border-color: #3498db;
}

.form-input:disabled {
  background-color: #f5f5f5;
  cursor: not-allowed;
}

.success-message {
  color: #27ae60;
  background-color: #d4edda;
  padding: 12px;
  border-radius: 4px;
  margin-bottom: 20px;
}

.error-message {
  color: #e74c3c;
  background-color: #fee;
  padding: 12px;
  border-radius: 4px;
  margin-bottom: 20px;
}

.form-actions {
  display: flex;
  gap: 12px;
  margin-top: 30px;
}

.submit-btn {
  background-color: #3498db;
  color: white;
  border: none;
  padding: 12px 24px;
  border-radius: 4px;
  cursor: pointer;
  font-size: 16px;
  transition: background-color 0.2s;
  flex: 1;
}

.submit-btn:hover:not(:disabled) {
  background-color: #2980b9;
}

.submit-btn:disabled {
  background-color: #ccc;
  cursor: not-allowed;
}

.cancel-btn {
  background-color: #95a5a6;
  color: white;
  border: none;
  padding: 12px 24px;
  border-radius: 4px;
  cursor: pointer;
  font-size: 16px;
  transition: background-color 0.2s;
}

.cancel-btn:hover:not(:disabled) {
  background-color: #7f8c8d;
}

.cancel-btn:disabled {
  background-color: #ccc;
  cursor: not-allowed;
}
</style>

