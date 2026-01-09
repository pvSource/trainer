<template>
  <div class="statistics-chart">
    <h2>Статистика прогресса</h2>
    
    <!-- Фильтры по дате -->
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
        <button @click="applyFilters" class="btn-apply" :disabled="loading">
          Применить
        </button>
        <button @click="resetFilters" class="btn-reset" :disabled="loading">
          Сбросить
        </button>
      </div>
    </div>

    <div v-if="loading" class="loading">Загрузка статистики...</div>
    <div v-if="error" class="error">{{ error }}</div>
    <div v-if="!loading && !error && !hasData" class="no-data">
      Нет данных для отображения статистики. Выполните это упражнение в тренировке, чтобы увидеть прогресс.
    </div>
    <div v-if="!loading && !error && hasData" class="chart-wrapper">
      <Line
        :data="chartData"
        :options="chartOptions"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { Line } from 'vue-chartjs'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  TimeScale
} from 'chart.js'
import 'chartjs-adapter-date-fns'

// Регистрируем компоненты Chart.js
ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  TimeScale
)

import { api } from '../services/api'

const props = defineProps({
  exerciseId: {
    type: [String, Number],
    required: true
  }
})

const router = useRouter()
const loading = ref(false)
const error = ref(null)
const hasData = ref(false)
const dateFrom = ref('')
const dateTo = ref('')
const statisticsData = ref(null)

// Цвета для линий графика
const colors = [
  '#3498db', // Синий
  '#e74c3c', // Красный
  '#2ecc71', // Зеленый
  '#f39c12', // Оранжевый
  '#9b59b6', // Фиолетовый
  '#1abc9c', // Бирюзовый
  '#e67e22', // Темно-оранжевый
  '#34495e', // Темно-серый
]

// Подготовка данных для графика
const chartData = computed(() => {
  if (!statisticsData.value || !statisticsData.value.series || statisticsData.value.series.length === 0) {
    return { datasets: [] }
  }

  const series = statisticsData.value.series

  // Собираем все уникальные даты из всех серий
  const allDates = new Set()
  series.forEach(serie => {
    if (serie.data && Array.isArray(serie.data)) {
      serie.data.forEach(point => {
        if (point && point.date) {
          allDates.add(point.date)
        }
      })
    }
  })

  if (allDates.size === 0) {
    return { datasets: [] }
  }

  const sortedDates = Array.from(allDates).sort()

  // Определяем минимальную и максимальную даты для создания непрерывного диапазона
  const minDate = new Date(sortedDates[0] + 'T00:00:00')
  const maxDate = new Date(sortedDates[sortedDates.length - 1] + 'T00:00:00')

  // Создаем массив всех дат в диапазоне (непрерывный)
  const allDatesInRange = []
  const currentDate = new Date(minDate)
  while (currentDate <= maxDate) {
    const dateStr = currentDate.toISOString().split('T')[0]
    allDatesInRange.push(dateStr)
    currentDate.setDate(currentDate.getDate() + 1)
  }

  // Создаем datasets для каждой серии (количество повторений)
  const datasets = series.map((serie, index) => {
    // Создаем массив точек данных для всех дат в диапазоне
    const dataPoints = allDatesInRange.map(date => {
      const point = serie.data?.find(p => p && p.date === date)
      return {
        x: date + 'T00:00:00', // Добавляем время для корректного парсинга временной шкалой
        y: point ? point.weight : null,
        training_id: point ? point.training_id : null // Сохраняем training_id для клика
      }
    })

    return {
      label: `${serie.repetitions} повторений`,
      data: dataPoints,
      borderColor: colors[index % colors.length],
      backgroundColor: colors[index % colors.length] + '20', // С прозрачностью
      borderWidth: 2,
      fill: false,
      tension: 0.1, // Плавные линии
      pointRadius: 4,
      pointHoverRadius: 6,
      spanGaps: true, // Соединять точки через пропуски (даты без тренировок)
    }
  })

  return {
    datasets: datasets
  }
})

// Настройки графика
const chartOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  onClick: (event, elements) => {
    // Обработчик клика на точку графика
    if (elements.length > 0) {
      const element = elements[0]
      const datasetIndex = element.datasetIndex
      const index = element.index
      const dataset = chartData.value.datasets[datasetIndex]
      const point = dataset.data[index]
      
      // Если у точки есть training_id, переходим на страницу тренировки
      if (point && point.training_id) {
        router.push(`/trainings/${point.training_id}`)
      }
    }
  },
  onHover: (event, elements) => {
    // Меняем курсор на pointer при наведении на точку с данными
    if (elements.length > 0) {
      const element = elements[0]
      const datasetIndex = element.datasetIndex
      const index = element.index
      const dataset = chartData.value.datasets[datasetIndex]
      const point = dataset.data[index]
      
      if (point && point.training_id) {
        event.native.target.style.cursor = 'pointer'
      } else {
        event.native.target.style.cursor = 'default'
      }
    } else {
      event.native.target.style.cursor = 'default'
    }
  },
  plugins: {
    title: {
      display: true,
      text: 'Прогресс по весу',
      font: {
        size: 16
      }
    },
    legend: {
      display: true,
      position: 'top',
    },
    tooltip: {
      mode: 'index',
      intersect: false,
      callbacks: {
        label: function(context) {
          if (context.parsed.y === null) return ''
          const point = context.raw
          let label = `${context.dataset.label}: ${context.parsed.y} кг`
          if (point && point.training_id) {
            label += ' (кликните для просмотра тренировки)'
          }
          return label
        },
        title: function(context) {
          // Форматируем дату для отображения в tooltip
          const date = new Date(context[0].parsed.x)
          return date.toLocaleDateString('ru-RU', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
          })
        }
      }
    }
  },
  scales: {
    y: {
      beginAtZero: false,
      title: {
        display: true,
        text: 'Вес (кг)'
      },
      ticks: {
        callback: function(value) {
          return value + ' кг'
        }
      }
    },
    x: {
      type: 'time',
      time: {
        unit: 'day',
        displayFormats: {
          day: 'dd.MM'
        },
        tooltipFormat: 'dd.MM.yyyy'
      },
      title: {
        display: true,
        text: 'Дата'
      },
      ticks: {
        maxRotation: 45,
        minRotation: 45
      }
    }
  },
  interaction: {
    mode: 'nearest',
    axis: 'x',
    intersect: false
  }
}))

// Загрузка статистики
async function fetchStatistics() {
  loading.value = true
  error.value = null
  hasData.value = false

  try {
    // Формируем URL с параметрами фильтрации
    let url = `/exercises/${props.exerciseId}/statistics`
    const params = new URLSearchParams()

    if (dateFrom.value) {
      params.append('date_from', dateFrom.value)
    }
    if (dateTo.value) {
      params.append('date_to', dateTo.value)
    }

    if (params.toString()) {
      url += '?' + params.toString()
    }

    const data = await api(url)
    console.log('Statistics data:', data) // Отладка

    if (data && data.series && data.series.length > 0) {
      statisticsData.value = data
      hasData.value = true
    } else {
      hasData.value = false
    }
  } catch (e) {
    console.error('Error fetching statistics:', e) // Отладка
    error.value = e.message || 'Ошибка загрузки статистики'
    if (e.errors) {
      // Обработка ошибок валидации от Laravel
      error.value = Object.values(e.errors).flat().join(' ')
    }
    hasData.value = false
  } finally {
    loading.value = false
  }
}

// Применить фильтры
function applyFilters() {
  // Валидация: если указана дата окончания, она должна быть не раньше даты начала
  if (dateFrom.value && dateTo.value && dateTo.value < dateFrom.value) {
    error.value = 'Дата окончания не может быть раньше даты начала'
    return
  }

  fetchStatistics()
}

// Сбросить фильтры
function resetFilters() {
  dateFrom.value = ''
  dateTo.value = ''
  error.value = null
  fetchStatistics()
}

onMounted(() => {
  fetchStatistics()
})
</script>

<style scoped>
.statistics-chart {
  margin-top: 30px;
  padding-top: 20px;
  border-top: 2px solid #e9ecef;
}

.statistics-chart h2 {
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

.loading,
.error,
.no-data {
  padding: 20px;
  text-align: center;
}

.error {
  color: #e74c3c;
  background-color: #fee;
  border-radius: 4px;
}

.no-data {
  color: #666;
  background-color: #f8f9fa;
  border-radius: 4px;
}

.chart-wrapper {
  position: relative;
  height: 400px;
  margin-top: 20px;
}
</style>
