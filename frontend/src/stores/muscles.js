import { ref, computed } from 'vue'
import { defineStore } from 'pinia'
import { api } from '../services/api'

export const useMusclesStore = defineStore('muscles', () => {
  // Состояние
  const musclesTree = ref([]) // Дерево мышц (иерархическая структура)
  const loading = ref(false)
  const error = ref(null)
  const loaded = ref(false) // Флаг, что мышцы уже загружены

  // Плоский список мышц (вычисляемое свойство)
  const flatMuscles = computed(() => {
    return flattenMuscles(musclesTree.value)
  })

  // Функция для преобразования дерева в плоский список
  function flattenMuscles(muscles) {
    let result = []
    muscles.forEach(muscle => {
      result.push({
        id: muscle.id,
        name: muscle.name,
        code: muscle.code,
        level: muscle.level,
        parent_id: muscle.parent_id
      })
      if (muscle.children && muscle.children.length > 0) {
        result = result.concat(flattenMuscles(muscle.children))
      }
    })
    return result
  }

  // Загрузка мышц (загружает только один раз)
  async function fetchMuscles(force = false) {
    // Если уже загружены и не принудительная загрузка - не загружаем снова
    if (loaded.value && !force) {
      return musclesTree.value
    }

    loading.value = true
    error.value = null

    try {
      const data = await api('/muscles')
      musclesTree.value = data
      loaded.value = true
      return data
    } catch (e) {
      error.value = e.message || 'Ошибка загрузки мышц'
      console.error('Ошибка загрузки мышц:', e)
      throw e
    } finally {
      loading.value = false
    }
  }

  // Поиск мышцы по ID в плоском списке
  function findMuscleById(id) {
    return flatMuscles.value.find(muscle => muscle.id === id)
  }

  // Поиск мышцы по коду
  function findMuscleByCode(code) {
    return flatMuscles.value.find(muscle => muscle.code === code)
  }

  // Получить все дочерние мышцы для заданной мышцы (рекурсивно)
  function getMuscleChildren(muscleId) {
    const findInTree = (muscles, targetId) => {
      for (const muscle of muscles) {
        if (muscle.id === targetId) {
          return muscle
        }
        if (muscle.children && muscle.children.length > 0) {
          const found = findInTree(muscle.children, targetId)
          if (found) return found
        }
      }
      return null
    }

    const muscle = findInTree(musclesTree.value, muscleId)
    return muscle ? flattenMuscles([muscle]) : []
  }

  // Получить мышцу с указанным level (текущую или родителя)
  function getMuscleParentByLevel(muscleId, level) {
    // Находим мышцу по ID
    const muscle = findMuscleById(muscleId)
    
    if (!muscle) {
      return null
    }

    // Если уровень текущей мышцы совпадает с запрошенным - возвращаем её
    if (muscle.level === level) {
      return muscle
    }

    // Если уровень текущей мышцы меньше запрошенного - родителя с таким level не существует
    if (muscle.level < level) {
      return null
    }

    // Поднимаемся вверх по иерархии, пока не найдем мышцу с нужным level
    let currentMuscle = muscle
    
    while (currentMuscle) {
      // Если достигли нужного уровня - возвращаем
      if (currentMuscle.level === level) {
        return currentMuscle
      }

      // Если уровень стал меньше нужного - родителя с таким level не существует
      if (currentMuscle.level < level) {
        return null
      }

      // Если есть родитель - переходим к нему
      if (currentMuscle.parent_id) {
        currentMuscle = findMuscleById(currentMuscle.parent_id)
      } else {
        // Достигли корня дерева
        break
      }
    }

    return null
  }

  // Сброс store (для тестирования или принудительной перезагрузки)
  function reset() {
    musclesTree.value = []
    loaded.value = false
    error.value = null
  }

  return {
    // Состояние
    musclesTree,
    loading,
    error,
    loaded,

    // Вычисляемые свойства
    flatMuscles,

    // Методы
    fetchMuscles,
    findMuscleById,
    findMuscleByCode,
    getMuscleChildren,
    getMuscleParentByLevel,
    reset
  }
})

