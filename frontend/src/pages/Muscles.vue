<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useMusclesStore } from '@/stores/muscles'
import MainLayout from '@/components/MainLayout.vue'
import MuscleItem from '@/components/MuscleItem.vue'

const musclesStore = useMusclesStore()
const router = useRouter()

// Используем данные из store
const muscles = computed(() => musclesStore.musclesTree)
const loading = computed(() => musclesStore.loading)
const error = computed(() => musclesStore.error)

// Состояние раскрытых/свернутых узлов (используем объект вместо Set для реактивности)
const expanded = ref({})

async function fetchMuscles() {
  try {
    await musclesStore.fetchMuscles()
  } catch (e) {
    console.error('Ошибка загрузки мышц:', e)
  }
}

function toggleExpand(muscleId) {
  expanded.value[muscleId] = !expanded.value[muscleId]
}

function goToDetail(muscleId) {
  router.push(`/muscles/${muscleId}`)
}

onMounted(() => {
  fetchMuscles()
})
</script>

<template>
  <MainLayout>
    <div class="muscles-page">
      <h1>Мышцы</h1>

      <div v-if="loading" class="loading">Загрузка...</div>
      
      <div v-if="error" class="error">
        {{ error }}
      </div>

      <div v-if="!loading && !error && muscles.length === 0" class="empty">
        Мышцы не найдены
      </div>

      <div v-if="!loading && !error && muscles.length > 0" class="muscles-list">
        <MuscleItem
          v-for="muscle in muscles"
          :key="muscle.id"
          :muscle="muscle"
          :expanded="expanded"
          :level="0"
          @toggle="toggleExpand"
          @view="goToDetail"
        />
      </div>
    </div>
  </MainLayout>
</template>

<style scoped>
.muscles-page {
  padding: 20px;
  max-width: 1200px;
  margin: 0 auto;
}

h1 {
  margin-bottom: 20px;
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
}

.muscles-list {
  background: white;
  border-radius: 8px;
  padding: 20px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.muscle-item {
  margin-bottom: 8px;
}

.muscle-header {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px;
  border-radius: 4px;
  transition: background-color 0.2s;
}

.muscle-header:hover {
  background-color: #f5f5f5;
}

.expand-btn {
  width: 24px;
  height: 24px;
  border: 1px solid #ddd;
  background: white;
  border-radius: 4px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
  font-weight: bold;
  color: #333;
}

.expand-btn:hover {
  background-color: #e9ecef;
}

.expand-placeholder {
  width: 24px;
}

.muscle-name {
  flex: 1;
  font-size: 16px;
  cursor: pointer;
  color: #2c3e50;
  font-weight: 500;
}

.muscle-name:hover {
  color: #3498db;
  text-decoration: underline;
}

.view-btn {
  padding: 6px 12px;
  background-color: #3498db;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 14px;
  transition: background-color 0.2s;
}

.view-btn:hover {
  background-color: #2980b9;
}

.muscle-children {
  margin-left: 20px;
  margin-top: 8px;
  border-left: 2px solid #e9ecef;
  padding-left: 12px;
}
</style>

