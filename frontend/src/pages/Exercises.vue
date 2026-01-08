<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { api } from '../services/api'
import MainLayout from '@/components/MainLayout.vue'

const exercises = ref([])
const loading = ref(false)
const error = ref(null)
const router = useRouter()
const currentPage = ref(1)
const lastPage = ref(1)

// Поиск и фильтры
const searchQuery = ref('')
const selectedMuscleId = ref(null)
const availableMuscles = ref([])

async function fetchExercises(page = 1) {
  loading.value = true
  error.value = null

  try {
    let url = `/exercises?page=${page}`
    if (searchQuery.value) {
      url += `&search=${encodeURIComponent(searchQuery.value)}`
    }
    if (selectedMuscleId.value) {
      url += `&muscle_id=${selectedMuscleId.value}`
    }

    const data = await api(url)
    exercises.value = data.data || data
    currentPage.value = data.current_page || 1
    lastPage.value = data.last_page || 1
  } catch (e) {
    error.value = e.message || 'Ошибка загрузки упражнений'
  } finally {
    loading.value = false
  }
}

async function fetchMuscles() {
  try {
    const data = await api('/muscles')
    // Преобразуем дерево мышц в плоский список для селекта
    const flattenMuscles = (muscles) => {
      let result = []
      muscles.forEach(muscle => {
        result.push({ id: muscle.id, name: muscle.name })
        if (muscle.children && muscle.children.length > 0) {
          result = result.concat(flattenMuscles(muscle.children))
        }
      })
      return result
    }
    availableMuscles.value = flattenMuscles(data)
  } catch (e) {
    console.error('Ошибка загрузки мышц:', e)
  }
}

function goToDetail(exerciseId) {
  router.push(`/exercises/${exerciseId}`)
}

function goToCreate() {
  router.push('/exercises/create')
}

function handleSearch() {
  currentPage.value = 1
  fetchExercises(1)
}

function clearFilters() {
  searchQuery.value = ''
  selectedMuscleId.value = null
  currentPage.value = 1
  fetchExercises(1)
}

onMounted(() => {
  fetchExercises()
  fetchMuscles()
})
</script>

<template>
  <MainLayout>
    <div class="exercises-page">
      <div class="page-header">
        <h1>Упражнения</h1>
        <button @click="goToCreate" class="btn-primary">+ Создать упражнение</button>
      </div>

      <!-- Фильтры и поиск -->
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
        </div>

        <div class="filter-box">
          <label for="muscle-filter">Фильтр по мышце:</label>
          <select
            id="muscle-filter"
            v-model="selectedMuscleId"
            @change="handleSearch"
            class="form-input"
          >
            <option :value="null">Все мышцы</option>
            <option
              v-for="muscle in availableMuscles"
              :key="muscle.id"
              :value="muscle.id"
            >
              {{ muscle.name }}
            </option>
          </select>
        </div>

        <button @click="clearFilters" class="clear-btn">Сбросить фильтры</button>
      </div>

      <div v-if="loading" class="loading">Загрузка...</div>
      
      <div v-if="error" class="error">
        {{ error }}
      </div>

      <div v-if="!loading && !error && exercises.length === 0" class="empty">
        Упражнения не найдены
      </div>

      <div v-if="!loading && !error && exercises.length > 0" class="exercises-list">
        <div
          v-for="exercise in exercises"
          :key="exercise.id"
          class="exercise-card"
          @click="goToDetail(exercise.id)"
        >
          <div class="exercise-header">
            <h3>{{ exercise.name }}</h3>
          </div>
          
          <div v-if="exercise.description" class="exercise-description">
            {{ exercise.description }}
          </div>

          <div v-if="exercise.muscles && exercise.muscles.length > 0" class="exercise-muscles">
            <strong>Мышцы:</strong>
            <div class="muscles-tags">
              <span
                v-for="muscle in exercise.muscles"
                :key="muscle.id"
                :class="['muscle-tag', { 'primary': muscle.pivot?.is_primary }]"
              >
                {{ muscle.name }}
                <span v-if="muscle.pivot?.is_primary" class="primary-badge">основная</span>
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Пагинация -->
      <div v-if="!loading && !error && lastPage > 1" class="pagination">
        <button
          @click="fetchExercises(currentPage - 1)"
          :disabled="currentPage === 1"
          class="page-btn"
        >
          Назад
        </button>
        <span class="page-info">
          Страница {{ currentPage }} из {{ lastPage }}
        </span>
        <button
          @click="fetchExercises(currentPage + 1)"
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
.exercises-page {
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
  display: flex;
  gap: 15px;
  align-items: flex-end;
  flex-wrap: wrap;
}

.search-box {
  flex: 1;
  min-width: 200px;
  display: flex;
  gap: 10px;
}

.filter-box {
  min-width: 200px;
}

.filter-box label {
  display: block;
  margin-bottom: 5px;
  font-weight: 500;
  color: #333;
  font-size: 0.9rem;
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

.exercises-list {
  display: grid;
  gap: 16px;
}

.exercise-card {
  background: white;
  border-radius: 8px;
  padding: 20px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  cursor: pointer;
  transition: transform 0.2s, box-shadow 0.2s;
}

.exercise-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}

.exercise-header h3 {
  margin: 0 0 12px 0;
  color: #2c3e50;
  font-size: 1.25rem;
}

.exercise-description {
  color: #555;
  margin-bottom: 12px;
  line-height: 1.5;
}

.exercise-muscles {
  margin-top: 12px;
  padding-top: 12px;
  border-top: 1px solid #e9ecef;
}

.exercise-muscles strong {
  display: block;
  margin-bottom: 8px;
  color: #2c3e50;
  font-size: 0.9rem;
}

.muscles-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.muscle-tag {
  padding: 4px 12px;
  background-color: #e9ecef;
  border-radius: 12px;
  font-size: 0.85rem;
  color: #555;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.muscle-tag.primary {
  background-color: #d4edda;
  color: #155724;
}

.primary-badge {
  font-size: 0.75rem;
  background-color: #28a745;
  color: white;
  padding: 2px 6px;
  border-radius: 8px;
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

