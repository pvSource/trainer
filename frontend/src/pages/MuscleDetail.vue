<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { api } from '../services/api'
import MainLayout from '@/components/MainLayout.vue'

// Получаем id из props (благодаря props: true в роутере)
const props = defineProps({
  id: {
    type: [String, Number],
    required: true
  }
})

const muscle = ref(null)
const loading = ref(false)
const error = ref(null)
const router = useRouter()

async function fetchMuscle() {
  loading.value = true
  error.value = null

  try {
    const data = await api(`/muscles/${props.id}`)
    muscle.value = data
  } catch (e) {
    error.value = e.message || 'Ошибка загрузки мышцы'
  } finally {
    loading.value = false
  }
}

function goBack() {
  router.push('/muscles')
}

onMounted(() => {
  fetchMuscle()
})
</script>

<template>
  <MainLayout>
    <div class="muscle-detail-page">
      <button @click="goBack" class="back-btn">← Назад к списку</button>

      <div v-if="loading" class="loading">Загрузка...</div>

      <div v-if="error" class="error">
        {{ error }}
      </div>

      <div v-if="!loading && !error && muscle" class="muscle-detail">
        <h1>{{ muscle.name }}</h1>

        <div v-if="muscle.description" class="description">
          <h2>Описание</h2>
          <p>{{ muscle.description }}</p>
        </div>

        <div v-if="muscle.slug" class="slug">
          <strong>Slug:</strong> {{ muscle.slug }}
        </div>

        <div class="info-grid">
          <div class="info-item">
            <strong>ID:</strong> {{ muscle.id }}
          </div>
          
          <div v-if="muscle.parent_id" class="info-item">
            <strong>Родительская мышца ID:</strong> {{ muscle.parent_id }}
          </div>
          
          <div v-else class="info-item">
            <strong>Уровень:</strong> Корневая мышца
          </div>
        </div>

        <div v-if="muscle.children && muscle.children.length > 0" class="children">
          <h2>Дочерние мышцы ({{ muscle.children.length }})</h2>
          <ul class="children-list">
            <li v-for="child in muscle.children" :key="child.id">
              <router-link :to="`/muscles/${child.id}`" class="child-link">
                {{ child.name }}
              </router-link>
            </li>
          </ul>
        </div>

        <div class="actions">
          <button @click="goBack" class="btn-secondary">Назад</button>
        </div>
      </div>
    </div>
  </MainLayout>
</template>

<style scoped>
.muscle-detail-page {
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

.muscle-detail {
  background: white;
  border-radius: 8px;
  padding: 30px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

h1 {
  margin-bottom: 20px;
  color: #2c3e50;
  font-size: 2rem;
}

h2 {
  margin-top: 30px;
  margin-bottom: 15px;
  color: #34495e;
  font-size: 1.5rem;
}

.description {
  margin-bottom: 20px;
}

.description p {
  line-height: 1.6;
  color: #555;
  font-size: 16px;
}

.slug {
  margin-bottom: 20px;
  padding: 10px;
  background-color: #f8f9fa;
  border-radius: 4px;
  color: #666;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 15px;
  margin-bottom: 30px;
}

.info-item {
  padding: 12px;
  background-color: #f8f9fa;
  border-radius: 4px;
  border-left: 3px solid #3498db;
}

.info-item strong {
  color: #2c3e50;
  display: block;
  margin-bottom: 5px;
}

.children {
  margin-top: 30px;
  padding-top: 20px;
  border-top: 2px solid #e9ecef;
}

.children-list {
  list-style: none;
  padding: 0;
  margin: 15px 0;
}

.children-list li {
  padding: 10px;
  margin-bottom: 8px;
  background-color: #f8f9fa;
  border-radius: 4px;
  transition: background-color 0.2s;
}

.children-list li:hover {
  background-color: #e9ecef;
}

.child-link {
  color: #3498db;
  text-decoration: none;
  font-weight: 500;
}

.child-link:hover {
  text-decoration: underline;
}

.actions {
  margin-top: 30px;
  padding-top: 20px;
  border-top: 2px solid #e9ecef;
}

.btn-secondary {
  padding: 10px 20px;
  background-color: #6c757d;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 16px;
  transition: background-color 0.2s;
}

.btn-secondary:hover {
  background-color: #5a6268;
}
</style>

