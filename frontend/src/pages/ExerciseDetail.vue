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

const exercise = ref(null)
const loading = ref(false)
const error = ref(null)
const router = useRouter()

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
          <h1>{{ exercise.name }}</h1>
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

        <div v-if="exercise.trainings && exercise.trainings.length > 0" class="trainings">
          <h2>Используется в тренировках ({{ exercise.trainings.length }})</h2>
          <div class="trainings-list">
            <router-link
              v-for="training in exercise.trainings"
              :key="training.id"
              :to="`/trainings/${training.id}`"
              class="training-link"
            >
              {{ training.name || `Тренировка #${training.id}` }}
            </router-link>
          </div>
        </div>

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

.trainings {
  margin-top: 30px;
  padding-top: 20px;
  border-top: 2px solid #e9ecef;
}

.trainings-list {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-top: 15px;
}

.training-link {
  padding: 8px 16px;
  background-color: #3498db;
  color: white;
  text-decoration: none;
  border-radius: 4px;
  font-size: 0.9rem;
  transition: background-color 0.2s;
}

.training-link:hover {
  background-color: #2980b9;
}

.actions-bottom {
  margin-top: 30px;
  padding-top: 20px;
  border-top: 2px solid #e9ecef;
}
</style>

