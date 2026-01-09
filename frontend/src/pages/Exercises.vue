<script setup>
import {ref, onMounted, reactive, computed} from 'vue'
import { useRouter } from 'vue-router'
import { api } from '../services/api'
import { useMusclesStore } from '@/stores/muscles'
import { useAuthStore } from '@/stores/auth'
import MainLayout from '@/components/MainLayout.vue'
import MuscleFilter from '@/components/MuscleFilter.vue'

const exercises = ref([])
const loading = ref(false)
const error = ref(null)
const router = useRouter()
const currentPage = ref(1)
const lastPage = ref(1)

// Stores
const musclesStore = useMusclesStore()
const authStore = useAuthStore()

// Поиск и фильтры
const searchQuery = ref('')
const selectedMuscleId = ref(null)

// Определяем нужный уровень мышц для отображения
const targetMuscleLevel = computed(() => {
  if (selectedMuscleId.value) {
    // Если выбран фильтр - берем уровень выбранной мышцы
    const selectedMuscle = musclesStore.findMuscleById(selectedMuscleId.value)
    return selectedMuscle?.level || 3
  } else {
    // Иначе берем уровень из настроек пользователя
    return authStore.user?.muscles_level || 3
  }
})

// Функция для обработки мышц упражнения
function processExerciseMuscles(exerciseMuscles) {
  if (!exerciseMuscles || exerciseMuscles.length === 0) {
    return []
  }

  // Группируем мышцы по родителям с нужным уровнем
  const groupedMuscles = new Map()

  exerciseMuscles.forEach(muscle => {
    // Находим родителя с нужным уровнем
    const parentMuscle = musclesStore.getMuscleParentByLevel(muscle.id, targetMuscleLevel.value)
    
    if (parentMuscle) {
      const parentId = parentMuscle.id
      
      // Если родитель уже есть в группе
      if (groupedMuscles.has(parentId)) {
        const existing = groupedMuscles.get(parentId)
        // Если текущая мышца основная, то родитель тоже основной
        if (muscle.pivot?.is_primary) {
          existing.is_primary = true
        }
      } else {
        // Создаем новую запись для родителя
        groupedMuscles.set(parentId, {
          id: parentId,
          name: parentMuscle.name,
          is_primary: muscle.pivot?.is_primary || false
        })
      }
    }
  })

  // Преобразуем Map в массив
  return Array.from(groupedMuscles.values())
}

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

// Функция загрузки мышц (использует store)
async function fetchMuscles() {
  try {
    await musclesStore.fetchMuscles()
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

// Обработчик выбора мышцы из фильтра
function onMuscleSelect(muscleId) {
  selectedMuscleId.value = muscleId
  handleSearch()
}

onMounted(() => {
  fetchExercises()
  fetchMuscles()
  // Загружаем мышцы в store, если они еще не загружены
  if (!musclesStore.loaded) {
    fetchMuscles()
  }
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
          <button @click="clearFilters" class="clear-btn">Сбросить фильтры</button>
        </div>
        <MuscleFilter
          v-model="selectedMuscleId"
          @select="onMuscleSelect"
        />
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
                v-for="muscle in processExerciseMuscles(exercise.muscles)"
                :key="muscle.id"
                :class="['muscle-tag', { 'primary': muscle.is_primary }]"
              >
                {{ muscle.name }}
                <span v-if="muscle.is_primary" class="primary-badge">основная</span>
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

