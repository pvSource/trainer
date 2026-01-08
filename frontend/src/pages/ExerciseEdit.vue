<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { api } from '../services/api'
import MainLayout from '@/components/MainLayout.vue'

const props = defineProps({
  id: {
    type: [String, Number],
    required: true
  }
})

const router = useRouter()

const form = ref({
  name: '',
  description: '',
  muscles: []
})

const availableMuscles = ref([])
const loadingExercise = ref(false)
const loadingMuscles = ref(false)
const loading = ref(false)
const error = ref(null)
const searchQuery = ref('')

async function fetchExercise() {
  loadingExercise.value = true
  error.value = null

  try {
    const data = await api(`/exercises/${props.id}`)
    form.value = {
      name: data.name || '',
      description: data.description || '',
      muscles: (data.muscles || []).map(m => ({
        id: m.id,
        name: m.name,
        is_primary: m.pivot?.is_primary || false
      }))
    }
  } catch (e) {
    error.value = e.message || 'Ошибка загрузки упражнения'
  } finally {
    loadingExercise.value = false
  }
}

async function fetchMuscles() {
  loadingMuscles.value = true
  try {
    const data = await api('/muscles')
    const flattenMuscles = (muscles) => {
      let result = []
      muscles.forEach(muscle => {
        result.push({
          id: muscle.id,
          name: muscle.name,
          level: muscle.level || 1
        })
        if (muscle.children && muscle.children.length > 0) {
          result = result.concat(flattenMuscles(muscle.children))
        }
      })
      return result
    }
    availableMuscles.value = flattenMuscles(data)
  } catch (e) {
    console.error('Ошибка загрузки мышц:', e)
  } finally {
    loadingMuscles.value = false
  }
}

function addMuscle(muscle) {
  if (form.value.muscles.find(m => m.id === muscle.id)) {
    return
  }
  
  form.value.muscles.push({
    id: muscle.id,
    name: muscle.name,
    is_primary: false
  })
}

function removeMuscle(index) {
  form.value.muscles.splice(index, 1)
}

function togglePrimary(index) {
  form.value.muscles[index].is_primary = !form.value.muscles[index].is_primary
}

const filteredMuscles = ref([])

function filterMuscles() {
  if (!searchQuery.value) {
    filteredMuscles.value = availableMuscles.value
    return
  }
  
  const query = searchQuery.value.toLowerCase()
  filteredMuscles.value = availableMuscles.value.filter(muscle =>
    muscle.name.toLowerCase().includes(query)
  )
}

async function handleSubmit() {
  loading.value = true
  error.value = null

  if (!form.value.name.trim()) {
    error.value = 'Название упражнения обязательно'
    loading.value = false
    return
  }

  try {
    const payload = {
      name: form.value.name.trim(),
      description: form.value.description || null,
      muscles: form.value.muscles.map(m => ({
        id: m.id,
        is_primary: m.is_primary || false
      }))
    }

    const data = await api(`/exercises/${props.id}`, {
      method: 'PUT',
      body: payload
    })

    router.push(`/exercises/${data.id}`)
  } catch (e) {
    if (e.errors) {
      const firstError = Object.values(e.errors)[0]
      error.value = Array.isArray(firstError) ? firstError[0] : firstError
    } else {
      error.value = e.message || 'Ошибка обновления упражнения'
    }
  } finally {
    loading.value = false
  }
}

function goBack() {
  router.push(`/exercises/${props.id}`)
}

onMounted(() => {
  fetchExercise()
  fetchMuscles()
  filterMuscles()
})
</script>

<template>
  <MainLayout>
    <div class="exercise-form-page">
      <button @click="goBack" class="back-btn">← Назад</button>

      <h1>Редактировать упражнение</h1>

      <div v-if="loadingExercise" class="loading">Загрузка упражнения...</div>

      <form v-else @submit.prevent="handleSubmit" class="exercise-form">
        <div class="form-section">
          <h2>Основная информация</h2>
          
          <div class="form-group">
            <label for="name">Название *</label>
            <input
              id="name"
              v-model="form.name"
              type="text"
              required
              placeholder="Например: Жим штанги лежа"
              class="form-input"
            />
          </div>

          <div class="form-group">
            <label for="description">Описание (необязательно)</label>
            <textarea
              id="description"
              v-model="form.description"
              rows="4"
              placeholder="Описание техники выполнения упражнения"
              class="form-input"
            ></textarea>
          </div>
        </div>

        <div class="form-section">
          <h2>Задействованные мышцы</h2>

          <div class="muscle-search">
            <input
              v-model="searchQuery"
              @input="filterMuscles"
              type="text"
              placeholder="Поиск мышц..."
              class="form-input"
            />
          </div>

          <div v-if="loadingMuscles" class="loading-small">Загрузка мышц...</div>

          <div v-if="!loadingMuscles && filteredMuscles.length > 0" class="muscles-list">
            <div
              v-for="muscle in filteredMuscles"
              :key="muscle.id"
              class="muscle-option"
              @click="addMuscle(muscle)"
            >
              <span>{{ muscle.name }}</span>
              <button type="button" class="add-btn">+ Добавить</button>
            </div>
          </div>

          <div v-if="form.muscles.length > 0" class="selected-muscles">
            <h3>Выбранные мышцы</h3>
            <div
              v-for="(muscle, index) in form.muscles"
              :key="muscle.id"
              class="selected-muscle"
            >
              <div class="muscle-name">{{ muscle.name }}</div>
              <div class="muscle-actions">
                <label class="checkbox-label">
                  <input
                    type="checkbox"
                    :checked="muscle.is_primary"
                    @change="togglePrimary(index)"
                  />
                  Основная мышца
                </label>
                <button
                  type="button"
                  @click="removeMuscle(index)"
                  class="remove-btn"
                >
                  Удалить
                </button>
              </div>
            </div>
          </div>
        </div>

        <div v-if="error" class="error-message">
          {{ error }}
        </div>

        <div class="form-actions">
          <button type="button" @click="goBack" class="btn-secondary">Отмена</button>
          <button type="submit" :disabled="loading" class="btn-primary">
            {{ loading ? 'Сохранение...' : 'Сохранить изменения' }}
          </button>
        </div>
      </form>
    </div>
  </MainLayout>
</template>

<style scoped>
.exercise-form-page {
  padding: 20px;
  max-width: 1000px;
  margin: 0 auto;
}

.back-btn {
  margin-bottom: 20px;
  padding: 8px 16px;
  background-color: #6c757d;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 14px;
}

h1 {
  margin-bottom: 30px;
}

.loading {
  padding: 40px;
  text-align: center;
  color: #666;
}

.exercise-form {
  background: white;
  border-radius: 8px;
  padding: 30px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.form-section {
  margin-bottom: 30px;
  padding-bottom: 30px;
  border-bottom: 2px solid #e9ecef;
}

.form-section:last-of-type {
  border-bottom: none;
}

h2 {
  margin-bottom: 20px;
  color: #2c3e50;
  font-size: 1.5rem;
}

h3 {
  margin-bottom: 15px;
  color: #34495e;
  font-size: 1.2rem;
}

.form-group {
  margin-bottom: 20px;
}

label {
  display: block;
  margin-bottom: 8px;
  font-weight: 500;
  color: #333;
}

.form-input {
  width: 100%;
  padding: 10px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 16px;
  transition: border-color 0.2s;
}

.form-input:focus {
  outline: none;
  border-color: #3498db;
}

textarea.form-input {
  resize: vertical;
  min-height: 100px;
}

.muscle-search {
  margin-bottom: 20px;
}

.muscles-list {
  max-height: 300px;
  overflow-y: auto;
  border: 1px solid #e9ecef;
  border-radius: 4px;
  margin-bottom: 20px;
}

.muscle-option {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px;
  border-bottom: 1px solid #e9ecef;
  cursor: pointer;
  transition: background-color 0.2s;
}

.muscle-option:hover {
  background-color: #f8f9fa;
}

.muscle-option:last-child {
  border-bottom: none;
}

.add-btn {
  padding: 6px 12px;
  background-color: #3498db;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 14px;
}

.selected-muscles {
  margin-top: 20px;
}

.selected-muscle {
  padding: 16px;
  background-color: #f8f9fa;
  border-radius: 4px;
  margin-bottom: 12px;
  border-left: 4px solid #3498db;
}

.muscle-name {
  font-weight: 600;
  margin-bottom: 12px;
  color: #2c3e50;
}

.muscle-actions {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  font-weight: normal;
}

.checkbox-label input[type="checkbox"] {
  width: auto;
  cursor: pointer;
}

.remove-btn {
  padding: 8px 16px;
  background-color: #e74c3c;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 14px;
}

.error-message {
  background-color: #fee;
  color: #c33;
  padding: 12px;
  border-radius: 4px;
  margin-bottom: 20px;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 30px;
  padding-top: 20px;
  border-top: 2px solid #e9ecef;
}

.btn-primary,
.btn-secondary {
  padding: 10px 20px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 16px;
  transition: background-color 0.2s;
}

.btn-primary {
  background-color: #3498db;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background-color: #2980b9;
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-secondary {
  background-color: #6c757d;
  color: white;
}

.btn-secondary:hover {
  background-color: #5a6268;
}

.loading-small {
  padding: 10px;
  text-align: center;
  color: #666;
}
</style>

