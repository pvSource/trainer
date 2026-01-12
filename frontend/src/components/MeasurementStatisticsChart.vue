<template>
  <div class="statistics-chart">
    <h2>Прогресс по размерам тела</h2>

    <div v-if="loading" class="loading">Загрузка статистики...</div>
    <div v-if="error" class="error">{{ error }}</div>
    <div v-if="!loading && !error && !hasData" class="no-data">
      Нет данных для отображения статистики по замерам. Проверьте, что у упражнения указаны задействованные мышцы.
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
import { ref, computed, onMounted, watch } from 'vue'
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
  },
  dateFrom: {
    type: String,
    default: ''
  },
  dateTo: {
    type: String,
    default: ''
  }
})

const loading = ref(false)
const error = ref(null)
const hasData = ref(false)
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
  '#16a085', // Зеленый морской
  '#c0392b', // Темно-красный
]

// Подготовка данных для графика
const chartData = computed(() => {
  if (!statisticsData.value || !statisticsData.value.measurements || statisticsData.value.measurements.length === 0) {
    return { datasets: [] }
  }

  const measurements = statisticsData.value.measurements

  // Собираем все уникальные даты из всех замеров
  const allDates = new Set()
  measurements.forEach(measurement => {
    if (measurement.data && Array.isArray(measurement.data)) {
      measurement.data.forEach(point => {
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
  // Если указаны фильтры, используем их, иначе используем даты из данных
  let minDate, maxDate
  
  if (props.dateFrom && props.dateTo) {
    // Используем фильтры как границы диапазона
    minDate = new Date(props.dateFrom + 'T00:00:00')
    maxDate = new Date(props.dateTo + 'T00:00:00')
  } else if (sortedDates.length > 0) {
    // Используем даты из данных
    minDate = new Date(sortedDates[0] + 'T00:00:00')
    maxDate = new Date(sortedDates[sortedDates.length - 1] + 'T00:00:00')
  } else {
    return { datasets: [] }
  }

  // Создаем массив всех дат в диапазоне (непрерывный)
  const allDatesInRange = []
  const currentDate = new Date(minDate)
  while (currentDate <= maxDate) {
    const dateStr = currentDate.toISOString().split('T')[0]
    allDatesInRange.push(dateStr)
    currentDate.setDate(currentDate.getDate() + 1)
  }

  // Создаем datasets для каждого замера
  const datasets = measurements.map((measurement, index) => {
    // Создаем массив точек данных для всех дат в диапазоне
    const dataPoints = allDatesInRange.map(date => {
      const point = measurement.data?.find(p => p && p.date === date)
      return {
        x: date + 'T00:00:00', // Добавляем время для корректного парсинга временной шкалой
        y: point ? point.value : null
      }
    })

    return {
      label: `${measurement.measurement_name} (${measurement.unit})`,
      data: dataPoints,
      borderColor: colors[index % colors.length],
      backgroundColor: colors[index % colors.length] + '20', // С прозрачностью
      borderWidth: 2,
      fill: false,
      tension: 0.1, // Плавные линии
      pointRadius: 4,
      pointHoverRadius: 6,
      spanGaps: true, // Соединять точки через пропуски (даты без замеров)
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
  plugins: {
    title: {
      display: true,
      text: 'Прогресс по размерам тела',
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
          const measurement = statisticsData.value.measurements[context.datasetIndex]
          const unit = measurement?.unit || ''
          return `${context.dataset.label}: ${context.parsed.y.toFixed(2)} ${unit}`
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
        text: 'Значение (см/кг)'
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
    let url = `/exercises/${props.exerciseId}/measurements-statistics`
    const params = new URLSearchParams()

    if (props.dateFrom) {
      params.append('date_from', props.dateFrom)
    }
    if (props.dateTo) {
      params.append('date_to', props.dateTo)
    }

    if (params.toString()) {
      url += '?' + params.toString()
    }

    const data = await api(url)
    console.log('Measurements statistics data:', data) // Отладка

    if (data && data.measurements && data.measurements.length > 0) {
      statisticsData.value = data
      hasData.value = true
    } else {
      hasData.value = false
    }
  } catch (e) {
    console.error('Error fetching measurements statistics:', e) // Отладка
    error.value = e.message || 'Ошибка загрузки статистики по замерам'
    if (e.errors) {
      // Обработка ошибок валидации от Laravel
      error.value = Object.values(e.errors).flat().join(' ')
    }
    hasData.value = false
  } finally {
    loading.value = false
  }
}

// Следим за изменением фильтров и автоматически обновляем данные
watch(() => [props.dateFrom, props.dateTo], () => {
  fetchStatistics()
}, { deep: true })

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

