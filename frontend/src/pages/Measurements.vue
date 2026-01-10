<template>
  <MainLayout>
    <div class="measurements-page">
      <div class="page-header">
        <h1>Замеры</h1>
        <button @click="openAddDialog" class="btn-primary">
          + Добавить замер
        </button>
      </div>

      <div v-if="loading" class="loading">Загрузка данных...</div>

      <div v-if="error" class="error">
        {{ error }}
      </div>

      <div v-if="!loading && !error && measurements.length === 0" class="empty">
        Замеры не найдены
      </div>

      <div v-if="!loading && !error && measurements.length > 0" class="table-container">
        <table class="measurements-table">
          <thead>
            <tr>
              <th class="sticky-col">Замер</th>
              <th class="sticky-col unit-col">Ед.</th>
              <th
                v-for="date in dates"
                :key="date"
                class="date-col"
              >
                {{ formatDate(date) }}
              </th>
              <th class="add-col">
                <button
                  @click="showAddColumnDialog = true"
                  class="btn-add-column"
                  title="Добавить столбец (дату)"
                >
                  +
                </button>
              </th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="measurement in measurements"
              :key="measurement.id"
            >
              <td class="sticky-col measurement-name">
                <div class="name-cell">
                  <strong>{{ measurement.name }}</strong>
                  <span v-if="measurement.code" class="code">{{ measurement.code }}</span>
                </div>
              </td>
              <td class="sticky-col unit-col">{{ measurement.unit }}</td>
              <td
                v-for="(date, dateIndex) in dates"
                :key="`${measurement.id}-${date}`"
                class="value-cell"
                @click="openEditDialog(measurement, date)"
                :class="getValueCellClass(measurement, date, dateIndex)"
              >
                <span v-if="measurement.values[date]?.value !== null">
                  {{ measurement.values[date].value }}
                </span>
                <span v-else class="empty-value">—</span>
              </td>
              <td class="add-col"></td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Диалог добавления/редактирования значения -->
      <div v-if="showEditDialog" class="dialog-overlay" @click="closeEditDialog">
        <div class="dialog" @click.stop>
          <h2>{{ editDialog.measurement ? `Редактировать: ${editDialog.measurement.name}` : 'Добавить замер' }}</h2>
          
          <div class="form-group">
            <label>Тип замера</label>
            <select
              v-model="editDialog.measurementId"
              class="form-input"
              :disabled="!!editDialog.measurement"
            >
              <option value="">Выберите тип замера</option>
              <option
                v-for="m in measurements"
                :key="m.id"
                :value="m.id"
              >
                {{ m.name }} ({{ m.unit }})
              </option>
            </select>
          </div>

          <div class="form-group">
            <label>Дата</label>
            <input
              v-model="editDialog.date"
              type="date"
              class="form-input"
            />
          </div>

          <div class="form-group">
            <label>Значение</label>
            <input
              v-model.number="editDialog.value"
              type="number"
              step="0.01"
              min="0"
              class="form-input"
              placeholder="Введите значение"
            />
          </div>

          <div v-if="editDialog.error" class="error-message">
            {{ editDialog.error }}
          </div>

          <div class="dialog-actions">
            <button @click="closeEditDialog" class="btn-secondary">Отмена</button>
            <button @click="saveValue" class="btn-primary" :disabled="editDialog.saving">
              {{ editDialog.saving ? 'Сохранение...' : 'Сохранить' }}
            </button>
            <button
              v-if="editDialog.valueId"
              @click="deleteValue"
              class="btn-danger"
              :disabled="editDialog.saving"
            >
              Удалить
            </button>
          </div>
        </div>
      </div>

      <!-- Диалог добавления столбца (даты) -->
      <div v-if="showAddColumnDialog" class="dialog-overlay" @click="showAddColumnDialog = false">
        <div class="dialog" @click.stop>
          <h2>Добавить дату</h2>
          
          <div class="form-group">
            <label>Дата</label>
            <input
              v-model="newDate"
              type="date"
              class="form-input"
            />
          </div>

          <div class="dialog-actions">
            <button @click="showAddColumnDialog = false" class="btn-secondary">Отмена</button>
            <button @click="addDateColumn" class="btn-primary">
              Добавить
            </button>
          </div>
        </div>
      </div>
    </div>
  </MainLayout>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { api } from '../services/api'
import MainLayout from '@/components/MainLayout.vue'

const measurements = ref([])
const dates = ref([])
const loading = ref(false)
const error = ref(null)

const showEditDialog = ref(false)
const showAddColumnDialog = ref(false)
const newDate = ref(new Date().toISOString().split('T')[0])

const editDialog = ref({
  measurement: null,
  measurementId: null,
  date: null,
  value: null,
  valueId: null,
  saving: false,
  error: null
})

async function fetchTableData() {
  loading.value = true
  error.value = null

  try {
    const data = await api('/measurements/table')
    measurements.value = data.measurements
    dates.value = data.dates
  } catch (e) {
    error.value = e.message || 'Ошибка загрузки замеров'
    console.error('Ошибка загрузки замеров:', e)
  } finally {
    loading.value = false
  }
}

function formatDate(dateString) {
  if (!dateString) return ''
  const date = new Date(dateString)
  return date.toLocaleDateString('ru-RU', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  })
}

/**
 * Определяет класс для ячейки значения в зависимости от сравнения с предыдущим значением
 */
function getValueCellClass(measurement, currentDate, dateIndex) {
  const classes = {
    'has-value': measurement.values[currentDate]?.value !== null
  }

  // Если нет значения, не применяем цветовые классы
  const currentValue = measurement.values[currentDate]?.value
  if (currentValue === null || currentValue === undefined) {
    return classes
  }

  // Ищем предыдущую дату с существующим значением (не просто предыдущую дату в массиве)
  let previousValue = null
  
  // Проходим по всем предыдущим датам, чтобы найти первую с существующим значением
  for (let i = dateIndex - 1; i >= 0; i--) {
    const prevDate = dates.value[i]
    const prevVal = measurement.values[prevDate]?.value
    
    if (prevVal !== null && prevVal !== undefined) {
      previousValue = prevVal
      break
    }
  }

  // Если предыдущего значения нет, не окрашиваем (это первое значение для этого типа замера)
  if (previousValue === null) {
    return classes
  }

  // Для живота (belly) и талии (waist) логика обратная:
  // уменьшение - хорошо (зелёный), увеличение - плохо (красный)
  const isReverse = measurement.code === 'belly' || measurement.code === 'waist'

  // Сравниваем значения
  if (currentValue > previousValue) {
    if (isReverse) {
      classes['value-decreased'] = true // Красный - значение увеличилось (для belly/waist это плохо)
    } else {
      classes['value-increased'] = true // Зелёный - значение увеличилось (для остальных это хорошо)
    }
  } else if (currentValue < previousValue) {
    if (isReverse) {
      classes['value-increased'] = true // Зелёный - значение уменьшилось (для belly/waist это хорошо)
    } else {
      classes['value-decreased'] = true // Красный - значение уменьшилось (для остальных это плохо)
    }
  }
  // Если значения равны, класс не добавляется (остаётся нейтральным)

  return classes
}

function openAddDialog() {
  editDialog.value = {
    measurement: null,
    measurementId: null,
    date: new Date().toISOString().split('T')[0],
    value: null,
    valueId: null,
    saving: false,
    error: null
  }
  showEditDialog.value = true
}

function openEditDialog(measurement, date) {
  const existingValue = measurement.values[date]
  
  editDialog.value = {
    measurement: measurement,
    measurementId: measurement.id,
    date: date,
    value: existingValue?.value || null,
    valueId: existingValue?.id || null,
    saving: false,
    error: null
  }
  
  showEditDialog.value = true
}

function closeEditDialog() {
  showEditDialog.value = false
  editDialog.value = {
    measurement: null,
    measurementId: null,
    date: null,
    value: null,
    valueId: null,
    saving: false,
    error: null
  }
}

async function saveValue() {
  if (!editDialog.value.measurementId || !editDialog.value.date || editDialog.value.value === null) {
    editDialog.value.error = 'Заполните все поля'
    return
  }

  editDialog.value.saving = true
  editDialog.value.error = null

  try {
    const payload = {
      value: editDialog.value.value,
      measure_at: editDialog.value.date
    }

    let response
    if (editDialog.value.valueId) {
      // Обновляем существующее значение
      response = await api(`/measurements/${editDialog.value.measurementId}/value/${editDialog.value.valueId}`, {
        method: 'PUT',
        body: payload
      })
    } else {
      // Создаем новое значение
      response = await api(`/measurements/${editDialog.value.measurementId}/value`, {
        method: 'POST',
        body: payload
      })
    }

    // Перезагружаем данные таблицы
    await fetchTableData()
    closeEditDialog()
  } catch (e) {
    editDialog.value.error = e.message || 'Ошибка сохранения'
    if (e.errors) {
      const firstError = Object.values(e.errors)[0]
      editDialog.value.error = Array.isArray(firstError) ? firstError[0] : firstError
    }
  } finally {
    editDialog.value.saving = false
  }
}

async function deleteValue() {
  if (!editDialog.value.valueId || !confirm('Вы уверены, что хотите удалить это значение?')) {
    return
  }

  editDialog.value.saving = true
  editDialog.value.error = null

  try {
    await api(`/measurements/${editDialog.value.measurementId}/value/${editDialog.value.valueId}`, {
      method: 'DELETE'
    })

    // Перезагружаем данные таблицы
    await fetchTableData()
    closeEditDialog()
  } catch (e) {
    editDialog.value.error = e.message || 'Ошибка удаления'
  } finally {
    editDialog.value.saving = false
  }
}

function addDateColumn() {
  if (!newDate.value) {
    return
  }

  // Проверяем, что дата еще не добавлена
  if (!dates.value.includes(newDate.value)) {
    dates.value.push(newDate.value)
    dates.value.sort()
  }

  showAddColumnDialog.value = false
  newDate.value = new Date().toISOString().split('T')[0]
}

onMounted(() => {
  fetchTableData()
})
</script>

<style scoped>
.measurements-page {
  padding: 20px;
  max-width: 100%;
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
  color: #2c3e50;
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

.table-container {
  overflow-x: auto;
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  max-width: 100%;
}

.measurements-table {
  width: 100%;
  border-collapse: collapse;
  min-width: 600px;
}

.measurements-table th {
  background-color: #f8f9fa;
  padding: 12px;
  text-align: center;
  font-weight: 600;
  color: #2c3e50;
  border-bottom: 2px solid #dee2e6;
  white-space: nowrap;
  position: relative;
}

.measurements-table td {
  padding: 12px;
  text-align: center;
  border-bottom: 1px solid #e9ecef;
  transition: background-color 0.2s;
}

.measurements-table tbody tr:hover {
  background-color: #f8f9fa;
}

/* Липкие колонки */
.sticky-col {
  position: sticky;
  left: 0;
  background-color: white;
  z-index: 1;
}

.sticky-col:first-child {
  z-index: 2;
}

.measurements-table thead .sticky-col {
  background-color: #f8f9fa;
  z-index: 3;
}

.measurement-name {
  text-align: left;
  min-width: 200px;
  max-width: 250px;
}

.name-cell {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.name-cell strong {
  color: #2c3e50;
}

.name-cell .code {
  font-size: 0.85rem;
  color: #6c757d;
  font-style: italic;
}

.unit-col {
  min-width: 60px;
  text-align: center;
  font-weight: 500;
  color: #6c757d;
}

.date-col {
  min-width: 100px;
  font-size: 0.9rem;
}

.value-cell {
  cursor: pointer;
  min-width: 100px;
  user-select: none;
}

.value-cell:hover {
  background-color: #e3f2fd !important;
}

.value-cell.has-value {
  font-weight: 500;
  color: #2c3e50;
}

.value-cell.value-increased {
  background-color: #d4edda !important;
  color: #155724 !important;
  font-weight: 600;
}

.value-cell.value-increased:hover {
  background-color: #c3e6cb !important;
}

.value-cell.value-decreased {
  background-color: #f8d7da !important;
  color: #721c24 !important;
  font-weight: 600;
}

.value-cell.value-decreased:hover {
  background-color: #f5c6cb !important;
}

.value-cell .empty-value {
  color: #adb5bd;
  font-style: italic;
}

.add-col {
  min-width: 50px;
  background-color: #f8f9fa;
}

.btn-add-column {
  width: 100%;
  padding: 8px;
  background-color: #28a745;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 18px;
  font-weight: bold;
  transition: background-color 0.2s;
}

.btn-add-column:hover {
  background-color: #218838;
}

/* Диалоги */
.dialog-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.dialog {
  background: white;
  border-radius: 8px;
  padding: 30px;
  max-width: 500px;
  width: 90%;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
}

.dialog h2 {
  margin-top: 0;
  margin-bottom: 20px;
  color: #2c3e50;
}

.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  margin-bottom: 8px;
  font-weight: 500;
  color: #333;
}

.form-input {
  width: 100%;
  padding: 10px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 16px;
  box-sizing: border-box;
}

.form-input:focus {
  outline: none;
  border-color: #3498db;
  box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
}

.form-input:disabled {
  background-color: #f5f5f5;
  cursor: not-allowed;
}

.error-message {
  color: #e74c3c;
  background-color: #fee;
  padding: 10px;
  border-radius: 4px;
  margin-bottom: 20px;
}

.dialog-actions {
  display: flex;
  gap: 10px;
  justify-content: flex-end;
  margin-top: 30px;
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

.btn-primary:disabled {
  background-color: #cccccc;
  cursor: not-allowed;
}

.btn-danger {
  padding: 10px 20px;
  background-color: #e74c3c;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 16px;
  transition: background-color 0.2s;
}

.btn-danger:hover {
  background-color: #c0392b;
}

.btn-danger:disabled {
  background-color: #cccccc;
  cursor: not-allowed;
}

/* Адаптивность */
@media (max-width: 768px) {
  .page-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 15px;
  }

  .measurements-table {
    font-size: 0.9rem;
  }

  .measurements-table th,
  .measurements-table td {
    padding: 8px;
  }

  .measurement-name {
    min-width: 150px;
    max-width: 180px;
  }

  .date-col,
  .value-cell {
    min-width: 80px;
  }
}
</style>

