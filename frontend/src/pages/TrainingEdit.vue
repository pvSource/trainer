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
  start_at: '',
  finish_at: '',
  exercises: []
})

const availableExercises = ref([])
const loadingTraining = ref(false)
const loadingExercises = ref(false)
const loading = ref(false)
const error = ref(null)
const searchQuery = ref('')

async function fetchTraining() {
  loadingTraining.value = true
  error.value = null

  try {
    const data = await api(`/trainings/${props.id}`)
    form.value = {
      name: data.name || '',
      description: data.description || '',
      start_at: data.start_at ? new Date(data.start_at).toISOString().slice(0, 16) : '',
      finish_at: data.finish_at ? new Date(data.finish_at).toISOString().slice(0, 16) : '',
      exercises: (data.exercises || []).map(ex => ({
        exercise_id: ex.id,
        exercise_name: ex.name,
        approaches_count: ex.pivot.approaches_count || 1,
        repetitions_count: ex.pivot.repetitions_count || 10,
        weight: ex.pivot.weight || null
      }))
    }
  } catch (e) {
    error.value = e.message || 'Ошибка загрузки тренировки'
  } finally {
    loadingTraining.value = false
  }
}

async function fetchExercises() {
  loadingExercises.value = true
  try {
    const params = searchQuery.value ? `?search=${searchQuery.value}` : ''
    const data = await api(`/exercises${params}`)
    availableExercises.value = data.data || data
  } catch (e) {
    console.error('Ошибка загрузки упражнений:', e)
  } finally {
    loadingExercises.value = false
  }
}

function addExercise(exercise) {
  if (form.value.exercises.find(e => e.exercise_id === exercise.id)) {
    return
  }
  
  form.value.exercises.push({
    exercise_id: exercise.id,
    exercise_name: exercise.name,
    approaches_count: 1,
    repetitions_count: 10,
    weight: null
  })
}

function removeExercise(index) {
  form.value.exercises.splice(index, 1)
}

async function handleSubmit() {
  loading.value = true
  error.value = null

  try {
    const payload = {
      name: form.value.name || null,
      description: form.value.description || null,
      start_at: form.value.start_at || null,
      finish_at: form.value.finish_at || null,
      exercises: form.value.exercises.map(e => ({
        exercise_id: e.exercise_id,
        approaches_count: e.approaches_count,
        repetitions_count: e.repetitions_count,
        weight: e.weight || null
      }))
    }

    const data = await api(`/trainings/${props.id}`, {
      method: 'PUT',
      body: payload
    })

    router.push(`/trainings/${data.id}`)
  } catch (e) {
    if (e.errors) {
      const firstError = Object.values(e.errors)[0]
      error.value = Array.isArray(firstError) ? firstError[0] : firstError
    } else {
      error.value = e.message || 'Ошибка обновления тренировки'
    }
  } finally {
    loading.value = false
  }
}

function goBack() {
  router.push(`/trainings/${props.id}`)
}

onMounted(() => {
  fetchTraining()
  fetchExercises()
})
</script>

<template>
  <MainLayout>
    <div class="training-form-page">
      <button @click="goBack" class="back-btn">← Назад</button>

      <h1>Редактировать тренировку</h1>

      <div v-if="loadingTraining" class="loading">Загрузка тренировки...</div>

      <form v-else @submit.prevent="handleSubmit" class="training-form">
        <div class="form-section">
          <h2>Основная информация</h2>
          
          <div class="form-group">
            <label for="name">Название (необязательно)</label>
            <input
              id="name"
              v-model="form.name"
              type="text"
              placeholder="Например: Тренировка груди"
              class="form-input"
            />
          </div>

          <div class="form-group">
            <label for="description">Описание (необязательно)</label>
            <textarea
              id="description"
              v-model="form.description"
              rows="3"
              placeholder="Описание тренировки"
              class="form-input"
            ></textarea>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="start_at">Начало</label>
              <input
                id="start_at"
                v-model="form.start_at"
                type="datetime-local"
                class="form-input"
              />
            </div>

            <div class="form-group">
              <label for="finish_at">Окончание (необязательно)</label>
              <input
                id="finish_at"
                v-model="form.finish_at"
                type="datetime-local"
                class="form-input"
              />
            </div>
          </div>
        </div>

        <div class="form-section">
          <h2>Упражнения</h2>

          <div class="exercise-search">
            <input
              v-model="searchQuery"
              @input="fetchExercises"
              type="text"
              placeholder="Поиск упражнений..."
              class="form-input"
            />
            <button type="button" @click="fetchExercises" class="search-btn">Поиск</button>
          </div>

          <div v-if="loadingExercises" class="loading-small">Загрузка упражнений...</div>

          <div v-if="!loadingExercises && availableExercises.length > 0" class="exercises-list">
            <div
              v-for="exercise in availableExercises"
              :key="exercise.id"
              class="exercise-option"
              @click="addExercise(exercise)"
            >
              <span>{{ exercise.name }}</span>
              <button type="button" class="add-btn">+ Добавить</button>
            </div>
          </div>

          <div v-if="form.exercises.length > 0" class="selected-exercises">
            <h3>Упражнения в тренировке</h3>
            <div
              v-for="(exercise, index) in form.exercises"
              :key="index"
              class="selected-exercise"
            >
              <div class="exercise-name">{{ exercise.exercise_name }}</div>
              <div class="exercise-fields">
                <div class="field">
                  <label>Подходы</label>
                  <input
                    v-model.number="exercise.approaches_count"
                    type="number"
                    min="1"
                    class="form-input small"
                  />
                </div>
                <div class="field">
                  <label>Повторения</label>
                  <input
                    v-model.number="exercise.repetitions_count"
                    type="number"
                    min="1"
                    class="form-input small"
                  />
                </div>
                <div class="field">
                  <label>Вес (кг)</label>
                  <input
                    v-model.number="exercise.weight"
                    type="number"
                    min="0"
                    step="0.5"
                    class="form-input small"
                  />
                </div>
                <button
                  type="button"
                  @click="removeExercise(index)"
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
          <button type="submit" :disabled="loading || form.exercises.length === 0" class="btn-primary">
            {{ loading ? 'Сохранение...' : 'Сохранить изменения' }}
          </button>
        </div>
      </form>
    </div>
  </MainLayout>
</template>

<style scoped>
.training-form-page {
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

.training-form {
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

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
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

.form-input.small {
  width: 100px;
}

textarea.form-input {
  resize: vertical;
  min-height: 80px;
}

.exercise-search {
  display: flex;
  gap: 10px;
  margin-bottom: 20px;
}

.search-btn {
  padding: 10px 20px;
  background-color: #6c757d;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}

.exercises-list {
  max-height: 300px;
  overflow-y: auto;
  border: 1px solid #e9ecef;
  border-radius: 4px;
  margin-bottom: 20px;
}

.exercise-option {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px;
  border-bottom: 1px solid #e9ecef;
  cursor: pointer;
  transition: background-color 0.2s;
}

.exercise-option:hover {
  background-color: #f8f9fa;
}

.exercise-option:last-child {
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

.selected-exercises {
  margin-top: 20px;
}

.selected-exercise {
  padding: 16px;
  background-color: #f8f9fa;
  border-radius: 4px;
  margin-bottom: 12px;
  border-left: 4px solid #3498db;
}

.exercise-name {
  font-weight: 600;
  margin-bottom: 12px;
  color: #2c3e50;
}

.exercise-fields {
  display: flex;
  gap: 15px;
  align-items: flex-end;
  flex-wrap: wrap;
}

.field {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.field label {
  font-size: 0.9rem;
  margin: 0;
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

