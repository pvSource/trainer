<template>
  <div class="muscle-filter-tree">
    <div class="muscle-tree">
      <div
        v-for="firstLevelMuscle in allMuscles"
        :key="firstLevelMuscle.id"
        class="muscle-item"
      >
        <div class="muscle-row">
          <button
            v-if="firstLevelMuscle.children && firstLevelMuscle.children.length > 0"
            type="button"
            @click.stop="toggleMuscle(firstLevelMuscle.id)"
            class="expand-btn"
          >
            {{ isExpanded(firstLevelMuscle.id) ? '−' : '+' }}
          </button>
          <span v-else class="expand-placeholder"></span>
          <button
            type="button"
            @click.stop="selectMuscle(firstLevelMuscle.id)"
            :class="['muscle-btn', { 'selected': isSelected(firstLevelMuscle.id) }]"
          >
            {{ firstLevelMuscle.name }}
          </button>
        </div>
        <div
          v-if="firstLevelMuscle.children && firstLevelMuscle.children.length > 0 && isExpanded(firstLevelMuscle.id)"
          class="muscle-children"
        >
          <div
            v-for="secondLevelMuscle in firstLevelMuscle.children"
            :key="secondLevelMuscle.id"
            class="muscle-item"
          >
            <div class="muscle-row">
              <button
                v-if="secondLevelMuscle.children && secondLevelMuscle.children.length > 0"
                type="button"
                @click.stop="toggleMuscle(secondLevelMuscle.id)"
                class="expand-btn"
              >
                {{ isExpanded(secondLevelMuscle.id) ? '−' : '+' }}
              </button>
              <span v-else class="expand-placeholder"></span>
              <button
                type="button"
                @click.stop="selectMuscle(secondLevelMuscle.id)"
                :class="['muscle-btn', { 'selected': isSelected(secondLevelMuscle.id) }]"
              >
                {{ secondLevelMuscle.name }}
              </button>
            </div>
            <div
              v-if="secondLevelMuscle.children && secondLevelMuscle.children.length > 0 && isExpanded(secondLevelMuscle.id)"
              class="muscle-children"
            >
              <div
                v-for="thirdLevelMuscle in secondLevelMuscle.children"
                :key="thirdLevelMuscle.id"
                class="muscle-item"
              >
                <div class="muscle-row">
                  <button
                    v-if="thirdLevelMuscle.children && thirdLevelMuscle.children.length > 0"
                    type="button"
                    @click.stop="toggleMuscle(thirdLevelMuscle.id)"
                    class="expand-btn"
                  >
                    {{ isExpanded(thirdLevelMuscle.id) ? '−' : '+' }}
                  </button>
                  <span v-else class="expand-placeholder"></span>
                  <button
                    type="button"
                    @click.stop="selectMuscle(thirdLevelMuscle.id)"
                    :class="['muscle-btn', { 'selected': isSelected(thirdLevelMuscle.id) }]"
                  >
                    {{ thirdLevelMuscle.name }}
                  </button>
                </div>
                <div
                  v-if="thirdLevelMuscle.children && thirdLevelMuscle.children.length > 0 && isExpanded(thirdLevelMuscle.id)"
                  class="muscle-children"
                >
                  <div
                    v-for="fourthLevelMuscle in thirdLevelMuscle.children"
                    :key="fourthLevelMuscle.id"
                    class="muscle-item"
                  >
                        <div class="muscle-row">
                          <span class="expand-placeholder"></span>
                          <button
                            type="button"
                            @click.stop="selectMuscle(fourthLevelMuscle.id)"
                            :class="['muscle-btn', { 'selected': isSelected(fourthLevelMuscle.id) }]"
                          >
                            {{ fourthLevelMuscle.name }}
                          </button>
                        </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useMusclesStore } from '@/stores/muscles'

const props = defineProps({
  modelValue: {
    type: [Number, String, null],
    default: null
  }
})

const emit = defineEmits(['update:modelValue', 'select'])

const musclesStore = useMusclesStore()
const expandedMuscles = ref({})

// Используем данные из store
const allMuscles = computed(() => musclesStore.musclesTree)

// Переключение раскрытия/сворачивания мышцы
function toggleMuscle(muscleId) {
  expandedMuscles.value[muscleId] = !expandedMuscles.value[muscleId]
}

// Выбор мышцы для фильтрации
function selectMuscle(muscleId) {
  const newValue = props.modelValue === muscleId ? null : muscleId
  emit('update:modelValue', newValue)
  emit('select', newValue)
}

// Проверка, раскрыта ли мышца
function isExpanded(muscleId) {
  return !!expandedMuscles.value[muscleId]
}

// Проверка, выбрана ли мышца
function isSelected(muscleId) {
  return props.modelValue === muscleId
}

onMounted(() => {
  // Загружаем мышцы, если они еще не загружены
  if (!musclesStore.loaded) {
    musclesStore.fetchMuscles()
  }
})
</script>

<style scoped>
.muscle-filter-tree {
  margin-top: 15px;
}

.muscle-tree {
  background: white;
  border: 1px solid #ddd;
  border-radius: 4px;
  padding: 15px;
  max-height: 400px;
  overflow-y: auto;
}

.muscle-item {
  margin-bottom: 4px;
}

.muscle-row {
  display: flex;
  align-items: center;
  gap: 8px;
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
  font-size: 14px;
  font-weight: bold;
  color: #333;
  flex-shrink: 0;
}

.expand-btn:hover {
  background-color: #f0f0f0;
}

.expand-placeholder {
  width: 24px;
  flex-shrink: 0;
}

.muscle-btn {
  flex: 1;
  padding: 8px 12px;
  border: 1px solid #ddd;
  background: white;
  border-radius: 4px;
  cursor: pointer;
  text-align: left;
  font-size: 14px;
  color: #333;
  transition: all 0.2s;
}

.muscle-btn:hover {
  background-color: #f8f9fa;
  border-color: #3498db;
}

.muscle-btn.selected {
  background-color: #3498db;
  color: white;
  border-color: #3498db;
  font-weight: 500;
}

.muscle-children {
  margin-left: 32px;
  margin-top: 4px;
  border-left: 2px solid #e9ecef;
  padding-left: 12px;
}
</style>

