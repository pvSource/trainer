<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { api } from '../services/api'
import { useAuthStore } from '@/stores/auth'
import MainLayout from '@/components/MainLayout.vue'
import ExerciseStatisticsChart from '@/components/ExerciseStatisticsChart.vue'
import MeasurementStatisticsChart from '@/components/MeasurementStatisticsChart.vue'

const props = defineProps({
  id: {
    type: [String, Number],
    required: true
  }
})

const exercise = ref(null)
const loading = ref(false)
const error = ref(null)
const router = useRouter()
const authStore = useAuthStore()

// Функция для вычисления даты 3 месяца назад
function getDateThreeMonthsAgo() {
  const date = new Date()
  date.setMonth(date.getMonth() - 3)
  return date.toISOString().split('T')[0]
}

// Функция для получения текущей даты в формате YYYY-MM-DD
function getTodayDate() {
  return new Date().toISOString().split('T')[0]
}

// Общие фильтры по дате для графиков статистики (по умолчанию - последние 3 месяца)
const dateFrom = ref(getDateThreeMonthsAgo())
const dateTo = ref(getTodayDate())

async function fetchExercise() {
  loading.value = true
  error.value = null

  try {
    const data = await api(`/exercises/${props.id}`)
    exercise.value = data
  } catch (e) {
    error.value = e.message || 'Ошибка загрузки упражнения'
  } finally {
    loading.value = false
  }
}

function goBack() {
  router.push('/exercises')
}

function goToEdit() {
  router.push(`/exercises/${props.id}/edit`)
}

async function handleDelete() {
  if (!confirm('Вы уверены, что хотите удалить это упражнение?')) {
    return
  }

  try {
    await api(`/exercises/${props.id}`, {
      method: 'DELETE'
    })
    router.push('/exercises')
  } catch (e) {
    alert('Ошибка при удалении: ' + (e.message || 'Неизвестная ошибка'))
  }
}

// Применить фильтры
function applyFilters() {
  // Валидация: если указана дата окончания, она должна быть не раньше даты начала
  if (dateFrom.value && dateTo.value && dateTo.value < dateFrom.value) {
    alert('Дата окончания не может быть раньше даты начала')
    return
  }
  // Фильтры применяются автоматически через props в дочерних компонентах
}

// Сбросить фильтры
function resetFilters() {
  dateFrom.value = ''
  dateTo.value = ''
}

onMounted(() => {
  fetchExercise()
})
</script>

<template>
  <MainLayout>
    <div class="exercise-detail-page">
      <button @click="goBack" class="back-btn">← Назад к списку</button>

      <div v-if="loading" class="loading">Загрузка...</div>

      <div v-if="error" class="error">
        {{ error }}
      </div>

      <div v-if="!loading && !error && exercise" class="exercise-detail">
        <div class="exercise-header">
          <div class="exercise-title-section">
            <h1>{{ exercise.name }}</h1>
            <div v-if="exercise.creator" class="creator-info">
              <span class="creator-label">Создатель:</span>
              <span class="creator-name">{{ exercise.creator.name }}</span>
            </div>
          </div>
          <div class="actions">
            <button @click="goToEdit" class="btn-secondary">Редактировать</button>
            <button @click="handleDelete" class="btn-danger">Удалить</button>
          </div>
        </div>

        <div v-if="exercise.description" class="description">
          <h2>Описание</h2>
          <p>{{ exercise.description }}</p>
        </div>

        <div v-if="exercise.muscles && exercise.muscles.length > 0" class="muscles">
          <h2>Задействованные мышцы ({{ exercise.muscles.length }})</h2>
          <div class="muscles-list">
            <div
              v-for="muscle in exercise.muscles"
              :key="muscle.id"
              :class="['muscle-item', { 'primary': muscle.pivot?.is_primary }]"
            >
              <div class="muscle-info">
                <router-link :to="`/muscles/${muscle.id}`" class="muscle-link">
                  {{ muscle.name }}
                </router-link>
                <span v-if="muscle.pivot?.is_primary" class="primary-badge">Основная</span>
                <span v-else class="secondary-badge">Вспомогательная</span>
              </div>
            </div>
          </div>
        </div>

        <div v-else class="no-muscles">
          <p>Для этого упражнения не указаны мышцы</p>
        </div>

        <!-- Общие фильтры по дате для статистики -->
        <div v-if="authStore.isAuthenticated" class="statistics-filters">
          <h2>Фильтры по дате</h2>
          <div class="filters">
            <div class="filter-group">
              <label for="date_from">От даты:</label>
              <input
                id="date_from"
                v-model="dateFrom"
                type="date"
                class="form-input"
              />
            </div>
            <div class="filter-group">
              <label for="date_to">До даты:</label>
              <input
                id="date_to"
                v-model="dateTo"
                type="date"
                class="form-input"
                :min="dateFrom"
              />
            </div>
            <div class="filter-actions">
              <button @click="applyFilters" class="btn-apply">
                Применить
              </button>
              <button @click="resetFilters" class="btn-reset">
                Сбросить
              </button>
            </div>
          </div>
        </div>

        <!-- Статистика (только для авторизованных пользователей) -->
        <ExerciseStatisticsChart 
          v-if="authStore.isAuthenticated"
          :exercise-id="id"
          :date-from="dateFrom"
          :date-to="dateTo"
        />

        <!-- Статистика по размерам тела (только для авторизованных пользователей) -->
        <MeasurementStatisticsChart 
          v-if="authStore.isAuthenticated"
          :exercise-id="id"
          :date-from="dateFrom"
          :date-to="dateTo"
        />

        <div class="actions-bottom">
          <button @click="goBack" class="btn-secondary">Назад</button>
        </div>
      </div>
    </div>
  </MainLayout>
</template>

<style scoped>
.exercise-detail-page {
  padding: 20px;
  max-width: 1200px;
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
  transition: background-color 0.2s;
}

.back-btn:hover {
  background-color: #5a6268;
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
}

.exercise-detail {
  background: white;
  border-radius: 8px;
  padding: 30px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.exercise-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 20px;
  padding-bottom: 20px;
  border-bottom: 2px solid #e9ecef;
}

.exercise-title-section {
  flex: 1;
}

h1 {
  margin: 0 0 8px 0;
  color: #2c3e50;
  font-size: 2rem;
}

.creator-info {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 0.9rem;
  color: #6c757d;
}

.creator-label {
  font-weight: 500;
}

.creator-name {
  color: #3498db;
  font-weight: 500;
}

.actions {
  display: flex;
  gap: 10px;
}

.btn-secondary,
.btn-danger {
  padding: 8px 16px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 14px;
  transition: background-color 0.2s;
}

.btn-secondary {
  background-color: #6c757d;
  color: white;
}

.btn-secondary:hover {
  background-color: #5a6268;
}

.btn-danger {
  background-color: #e74c3c;
  color: white;
}

.btn-danger:hover {
  background-color: #c0392b;
}

.description {
  margin-bottom: 30px;
}

h2 {
  margin-top: 30px;
  margin-bottom: 15px;
  color: #34495e;
  font-size: 1.5rem;
}

.description p {
  line-height: 1.6;
  color: #555;
  font-size: 16px;
}

.muscles {
  margin-top: 30px;
  padding-top: 20px;
  border-top: 2px solid #e9ecef;
}

.muscles-list {
  display: grid;
  gap: 12px;
  margin-top: 20px;
}

.muscle-item {
  padding: 12px;
  background-color: #f8f9fa;
  border-radius: 4px;
  border-left: 4px solid #6c757d;
}

.muscle-item.primary {
  border-left-color: #28a745;
  background-color: #d4edda;
}

.muscle-info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.muscle-link {
  flex: 1;
  color: #3498db;
  text-decoration: none;
  font-weight: 500;
}

.muscle-link:hover {
  text-decoration: underline;
}

.primary-badge,
.secondary-badge {
  padding: 4px 10px;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 500;
}

.primary-badge {
  background-color: #28a745;
  color: white;
}

.secondary-badge {
  background-color: #6c757d;
  color: white;
}

.no-muscles {
  margin-top: 30px;
  padding: 20px;
  text-align: center;
  color: #666;
  background-color: #f8f9fa;
  border-radius: 4px;
}

.statistics-filters {
  margin-top: 30px;
  padding-top: 20px;
  border-top: 2px solid #e9ecef;
}

.statistics-filters h2 {
  margin: 0 0 20px 0;
  color: #34495e;
  font-size: 1.5rem;
}

.filters {
  display: flex;
  gap: 15px;
  align-items: flex-end;
  margin-bottom: 20px;
  padding: 15px;
  background-color: #f8f9fa;
  border-radius: 4px;
  flex-wrap: wrap;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.filter-group label {
  font-weight: 500;
  color: #34495e;
  font-size: 14px;
}

.form-input {
  padding: 8px 12px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 14px;
  min-width: 150px;
}

.form-input:focus {
  outline: none;
  border-color: #3498db;
}

.filter-actions {
  display: flex;
  gap: 10px;
}

.btn-apply,
.btn-reset {
  padding: 8px 16px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 14px;
  transition: background-color 0.2s;
}

.btn-apply {
  background-color: #3498db;
  color: white;
}

.btn-apply:hover:not(:disabled) {
  background-color: #2980b9;
}

.btn-apply:disabled {
  background-color: #95a5a6;
  cursor: not-allowed;
}

.btn-reset {
  background-color: #95a5a6;
  color: white;
}

.btn-reset:hover:not(:disabled) {
  background-color: #7f8c8d;
}

.btn-reset:disabled {
  background-color: #bdc3c7;
  cursor: not-allowed;
}

.actions-bottom {
  margin-top: 30px;
  padding-top: 20px;
  border-top: 2px solid #e9ecef;
}
</style>

