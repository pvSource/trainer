<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { api } from '../services/api'
import MainLayout from '@/components/MainLayout.vue'

const trainings = ref([])
const loading = ref(false)
const error = ref(null)
const router = useRouter()
const currentPage = ref(1)
const lastPage = ref(1)

// Поиск
const searchQuery = ref('')

async function fetchTrainings(page = 1) {
  loading.value = true
  error.value = null

  try {
    let url = `/trainings?page=${page}`
    if (searchQuery.value) {
      url += `&search=${encodeURIComponent(searchQuery.value)}`
    }

    const data = await api(url)
    trainings.value = data.data || data
    currentPage.value = data.current_page || 1
    lastPage.value = data.last_page || 1
  } catch (e) {
    error.value = e.message || 'Ошибка загрузки тренировок'
  } finally {
    loading.value = false
  }
}

function goToDetail(trainingId) {
  router.push(`/trainings/${trainingId}`)
}

function goToCreate() {
  router.push('/trainings/create')
}

function copyTraining(trainingId, event) {
  // Останавливаем всплытие события, чтобы не открывалась детальная страница
  event.stopPropagation()
  
  // Переходим на страницу создания с параметром copy_from
  router.push({
    path: '/trainings/create',
    query: {
      copy_from: trainingId
    }
  })
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

function handleSearch() {
  currentPage.value = 1
  fetchTrainings(1)
}

function clearFilters() {
  searchQuery.value = ''
  currentPage.value = 1
  fetchTrainings(1)
}

onMounted(() => {
  fetchTrainings()
})
</script>

<template>
  <MainLayout>
    <div class="trainings-page">
      <div class="page-header">
        <h1>Мои тренировки</h1>
        <button @click="goToCreate" class="btn-primary">+ Создать тренировку</button>
      </div>

      <!-- Поиск -->
      <div class="filters">
        <div class="search-box">
          <input
            v-model="searchQuery"
            @keyup.enter="handleSearch"
            type="text"
            placeholder="Поиск по названию..."
            class="form-input"
          />
          <button @click="handleSearch" class="search-btn">Поиск</button>
          <button @click="clearFilters" class="clear-btn">Сбросить фильтры</button>
        </div>
      </div>

      <div v-if="loading" class="loading">Загрузка...</div>
      
      <div v-if="error" class="error">
        {{ error }}
      </div>

      <div v-if="!loading && !error && trainings.length === 0" class="empty">
        У вас пока нет тренировок. Создайте первую!
      </div>

      <div v-if="!loading && !error && trainings.length > 0" class="trainings-list">
        <div
          v-for="training in trainings"
          :key="training.id"
          class="training-card"
          @click="goToDetail(training.id)"
        >
          <div class="training-header">
            <h3>{{ training.name || 'Тренировка без названия' }}</h3>
            <div class="training-header-actions">
              <button
                type="button"
                @click.stop="copyTraining(training.id, $event)"
                class="btn-copy"
                title="Скопировать тренировку"
              >
                Скопировать
              </button>
              <span class="training-date">{{ formatDate(training.start_at) }}</span>
            </div>
          </div>
          
          <div v-if="training.description" class="training-description">
            {{ training.description }}
          </div>

          <div class="training-info">
            <div class="info-item">
              <strong>Начало:</strong> {{ formatDate(training.start_at) }}
            </div>
            <div v-if="training.finish_at" class="info-item">
              <strong>Окончание:</strong> {{ formatDate(training.finish_at) }}
            </div>
            <div v-if="training.finish_at" class="info-item">
              <strong>Длительность:</strong> {{ getDuration(training.start_at, training.finish_at) }}
            </div>
            <div class="info-item">
              <strong>Упражнений:</strong> {{ training.exercises?.length || 0 }}
            </div>
          </div>
        </div>
      </div>

      <!-- Пагинация -->
      <div v-if="!loading && !error && lastPage > 1" class="pagination">
        <button
          @click="fetchTrainings(currentPage - 1)"
          :disabled="currentPage === 1"
          class="page-btn"
        >
          Назад
        </button>
        <span class="page-info">
          Страница {{ currentPage }} из {{ lastPage }}
        </span>
        <button
          @click="fetchTrainings(currentPage + 1)"
          :disabled="currentPage === lastPage"
          class="page-btn"
        >
          Вперед
        </button>
      </div>
    </div>
  </MainLayout>
</template>

<style scoped>
.trainings-page {
  padding: 20px;
  max-width: 1200px;
  margin: 0 auto;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

h1 {
  margin: 0;
}

.btn-primary {
  padding: 10px 20px;
  background-color: #3498db;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 16px;
  transition: background-color 0.2s;
}

.btn-primary:hover {
  background-color: #2980b9;
}

.filters {
  background: white;
  border-radius: 8px;
  padding: 20px;
  margin-bottom: 20px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.search-box {
  flex: 1;
  min-width: 200px;
  display: flex;
  gap: 10px;
}

.form-input {
  padding: 10px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 16px;
  width: 100%;
}

.search-btn,
.clear-btn {
  padding: 10px 20px;
  background-color: #6c757d;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 14px;
  white-space: nowrap;
}

.search-btn:hover,
.clear-btn:hover {
  background-color: #5a6268;
}

.loading,
.error,
.empty {
  padding: 20px;
  text-align: center;
}

.error {
  color: #e74c3c;
  background-color: #fee;
  border-radius: 4px;
}

.empty {
  color: #666;
  padding: 40px;
}

.trainings-list {
  display: grid;
  gap: 16px;
}

.training-card {
  background: white;
  border-radius: 8px;
  padding: 20px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  cursor: pointer;
  transition: transform 0.2s, box-shadow 0.2s;
}

.training-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}

.training-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}

.training-header-actions {
  display: flex;
  align-items: center;
  gap: 12px;
}

.btn-copy {
  padding: 6px 12px;
  background-color: #28a745;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 14px;
  transition: background-color 0.2s;
}

.btn-copy:hover {
  background-color: #218838;
}

.training-header h3 {
  margin: 0;
  color: #2c3e50;
  font-size: 1.25rem;
}

.training-date {
  color: #666;
  font-size: 0.9rem;
}

.training-description {
  color: #555;
  margin-bottom: 12px;
  line-height: 1.5;
}

.training-info {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 8px;
  margin-top: 12px;
  padding-top: 12px;
  border-top: 1px solid #e9ecef;
}

.info-item {
  font-size: 0.9rem;
  color: #666;
}

.info-item strong {
  color: #2c3e50;
  margin-right: 4px;
}

.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 16px;
  margin-top: 30px;
  padding: 20px;
}

.page-btn {
  padding: 8px 16px;
  background-color: #6c757d;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  transition: background-color 0.2s;
}

.page-btn:hover:not(:disabled) {
  background-color: #5a6268;
}

.page-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.page-info {
  color: #666;
}
</style>

