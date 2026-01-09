<script setup>
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { api } from '../services/api'
import { useMusclesStore } from '@/stores/muscles'
import MainLayout from '@/components/MainLayout.vue'
import MuscleFilter from '@/components/MuscleFilter.vue'

const router = useRouter()
const route = useRoute()
const musclesStore = useMusclesStore()

const form = ref({
  name: '',
  description: '',
  start_at: new Date().toISOString().slice(0, 16),
  finish_at: '',
  exercises: []
})

const availableExercises = ref([])
const loadingExercises = ref(false)
const loading = ref(false)
const error = ref(null)

// Поиск и фильтры
const searchQuery = ref('')
const selectedMuscleId = ref(null)

async function fetchExercises() {
  loadingExercises.value = true
  try {
    let url = '/exercises'
    const params = new URLSearchParams()
    
    if (searchQuery.value) {
      params.append('search', searchQuery.value)
    }
    if (selectedMuscleId.value) {
      params.append('muscle_id', selectedMuscleId.value)
    }
    
    if (params.toString()) {
      url += '?' + params.toString()
    }
    
    const data = await api(url)
    availableExercises.value = data.data || data
  } catch (e) {
    console.error('Ошибка загрузки упражнений:', e)
  } finally {
    loadingExercises.value = false
  }
}

// Обработчик выбора мышцы из фильтра
function onMuscleSelect() {
  fetchExercises()
}

function addExercise(exercise) {
  // Проверяем, не добавлено ли уже это упражнение
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

    const data = await api('/trainings', {
      method: 'POST',
      body: payload
    })

    router.push(`/trainings/${data.id}`)
  } catch (e) {
    if (e.errors) {
      const firstError = Object.values(e.errors)[0]
      error.value = Array.isArray(firstError) ? firstError[0] : firstError
    } else {
      error.value = e.message || 'Ошибка создания тренировки'
    }
  } finally {
    loading.value = false
  }
}

function goBack() {
  router.push('/trainings')
}

// Функция для заполнения формы данными из копируемой тренировки
function fillFormFromTraining(training) {
  if (!training) return
  
  form.value.name = training.name || ''
  form.value.description = training.description || ''
  form.value.start_at = new Date().toISOString().slice(0, 16) // Текущая дата
  form.value.finish_at = '' // Пустая дата окончания
  
  // Заполняем упражнения
  if (training.exercises && Array.isArray(training.exercises)) {
    form.value.exercises = training.exercises.map(exercise => ({
      exercise_id: exercise.id,
      exercise_name: exercise.name,
      approaches_count: exercise.pivot?.approaches_count || 1,
      repetitions_count: exercise.pivot?.repetitions_count || 10,
      weight: exercise.pivot?.weight || null
    }))
  }
}

// Загрузка данных тренировки для копирования
async function loadTrainingForCopy(trainingId) {
  try {
    const training = await api(`/trainings/${trainingId}`)
    fillFormFromTraining(training)
    // Удаляем query параметр после загрузки
    router.replace({ query: {} })
  } catch (e) {
    console.error('Ошибка при загрузке тренировки для копирования:', e)
    error.value = 'Ошибка при загрузке данных тренировки'
  }
}

onMounted(() => {
  // Проверяем, есть ли параметр copy_from в query
  const copyFromId = route.query.copy_from
  if (copyFromId) {
    loadTrainingForCopy(copyFromId)
  }
  
  fetchExercises()
  // Загружаем мышцы в store, если они еще не загружены
  if (!musclesStore.loaded) {
    musclesStore.fetchMuscles()
  }
})
</script>

<template>
  <MainLayout>
    <div class="training-form-page">
      <button @click="goBack" class="back-btn">← Назад</button>

      <h1>Создать тренировку</h1>

      <form @submit.prevent="handleSubmit" class="training-form">
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

          <!-- Добавленные упражнения -->
          <div v-if="form.exercises.length > 0" class="selected-exercises">
            <h3>Добавленные упражнения</h3>
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

          <!-- Поиск и фильтры -->
          <div class="exercise-filters">
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
            <MuscleFilter
              v-model="selectedMuscleId"
              @select="onMuscleSelect"
            />
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
        </div>

        <div v-if="error" class="error-message">
          {{ error }}
        </div>

        <div class="form-actions">
          <button type="button" @click="goBack" class="btn-secondary">Отмена</button>
          <button type="submit" :disabled="loading || form.exercises.length === 0" class="btn-primary">
            {{ loading ? 'Создание...' : 'Создать тренировку' }}
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

.exercise-filters {
  margin-bottom: 20px;
}

.exercise-search {
  display: flex;
  gap: 10px;
  margin-bottom: 15px;
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

