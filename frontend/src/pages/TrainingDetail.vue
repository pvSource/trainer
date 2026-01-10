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

const training = ref(null)
const loading = ref(false)
const error = ref(null)
const router = useRouter()

async function fetchTraining() {
  loading.value = true
  error.value = null

  try {
    const data = await api(`/trainings/${props.id}`)
    training.value = data
  } catch (e) {
    error.value = e.message || 'Ошибка загрузки тренировки'
  } finally {
    loading.value = false
  }
}

function goBack() {
  router.push('/trainings')
}

function goToEdit() {
  router.push(`/trainings/${props.id}/edit`)
}

async function handleDelete() {
  if (!confirm('Вы уверены, что хотите удалить эту тренировку?')) {
    return
  }

  try {
    await api(`/trainings/${props.id}`, {
      method: 'DELETE'
    })
    router.push('/trainings')
  } catch (e) {
    alert('Ошибка при удалении: ' + (e.message || 'Неизвестная ошибка'))
  }
}

function formatDate(dateString) {
  if (!dateString) return '—'
  const date = new Date(dateString)
  return date.toLocaleString('ru-RU', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit'
  })
}

function getDuration(startAt, finishAt) {
  if (!startAt || !finishAt) return '—'
  const start = new Date(startAt)
  const finish = new Date(finishAt)
  const diff = Math.round((finish - start) / 1000 / 60) // минуты
  if (diff < 60) {
    return `${diff} мин`
  }
  const hours = Math.floor(diff / 60)
  const minutes = diff % 60
  return `${hours} ч ${minutes} мин`
}

onMounted(() => {
  fetchTraining()
})
</script>

<template>
  <MainLayout>
    <div class="training-detail-page">
      <button @click="goBack" class="back-btn">← Назад к списку</button>

      <div v-if="loading" class="loading">Загрузка...</div>

      <div v-if="error" class="error">
        {{ error }}
      </div>

      <div v-if="!loading && !error && training" class="training-detail">
        <div class="training-header">
          <h1>{{ training.name || 'Тренировка без названия' }}</h1>
          <div class="actions">
            <button @click="goToEdit" class="btn-secondary">Редактировать</button>
            <button @click="handleDelete" class="btn-danger">Удалить</button>
          </div>
        </div>

        <div v-if="training.description" class="description">
          <h2>Описание</h2>
          <p>{{ training.description }}</p>
        </div>

        <div class="info-grid">
          <div class="info-item">
            <strong>Начало:</strong>
            <span>{{ formatDate(training.start_at) }}</span>
          </div>
          
          <div v-if="training.finish_at" class="info-item">
            <strong>Окончание:</strong>
            <span>{{ formatDate(training.finish_at) }}</span>
          </div>
          
          <div v-if="training.finish_at" class="info-item">
            <strong>Длительность:</strong>
            <span>{{ getDuration(training.start_at, training.finish_at) }}</span>
          </div>
        </div>

        <div v-if="training.exercises && training.exercises.length > 0" class="exercises">
          <h2>Упражнения ({{ training.exercises.length }})</h2>
          <div class="exercises-list">
            <div
              v-for="exercise in training.exercises"
              :key="exercise.id"
              class="exercise-item"
            >
              <div class="exercise-header">
                <router-link :to="`/exercises/${exercise.id}`" class="exercise-link">
                  <h3>{{ exercise.name }}</h3>
                </router-link>
              </div>
              <div class="exercise-details">
                <div v-if="exercise.pivot.approaches_count" class="detail">
                  <strong>Подходов:</strong> {{ exercise.pivot.approaches_count }}
                </div>
                <div v-if="exercise.pivot.repetitions_count" class="detail">
                  <strong>Повторений:</strong> {{ exercise.pivot.repetitions_count }}
                </div>
                <div v-if="exercise.pivot.weight" class="detail">
                  <strong>Вес:</strong> {{ exercise.pivot.weight }} кг
                </div>
              </div>
            </div>
          </div>
        </div>

        <div v-else class="no-exercises">
          <p>В этой тренировке нет упражнений</p>
        </div>
      </div>
    </div>
  </MainLayout>
</template>

<style scoped>
.training-detail-page {
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

.training-detail {
  background: white;
  border-radius: 8px;
  padding: 30px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.training-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  padding-bottom: 20px;
  border-bottom: 2px solid #e9ecef;
}

h1 {
  margin: 0;
  color: #2c3e50;
  font-size: 2rem;
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

.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 15px;
  margin-bottom: 30px;
  padding: 20px;
  background-color: #f8f9fa;
  border-radius: 4px;
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.info-item strong {
  color: #2c3e50;
  font-size: 0.9rem;
}

.info-item span {
  color: #555;
  font-size: 1rem;
}

.exercises {
  margin-top: 30px;
  padding-top: 20px;
  border-top: 2px solid #e9ecef;
}

.exercises-list {
  display: grid;
  gap: 16px;
  margin-top: 20px;
}

.exercise-item {
  padding: 16px;
  background-color: #f8f9fa;
  border-radius: 4px;
  border-left: 4px solid #3498db;
}

.exercise-header {
  margin-bottom: 12px;
}

.exercise-link {
  text-decoration: none;
  color: inherit;
  transition: color 0.2s;
}

.exercise-link:hover h3 {
  color: #3498db;
}

.exercise-header h3 {
  margin: 0;
  color: #2c3e50;
  font-size: 1.1rem;
  transition: color 0.2s;
}

.exercise-details {
  display: flex;
  gap: 20px;
  flex-wrap: wrap;
}

.detail {
  color: #666;
  font-size: 0.9rem;
}

.detail strong {
  color: #2c3e50;
  margin-right: 5px;
}

.no-exercises {
  margin-top: 30px;
  padding: 20px;
  text-align: center;
  color: #666;
  background-color: #f8f9fa;
  border-radius: 4px;
}
</style>

