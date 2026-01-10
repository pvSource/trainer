<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'

const props = defineProps({
  muscle: {
    type: Object,
    required: true
  },
  expanded: {
    type: Object,
    required: true
  },
  level: {
    type: Number,
    default: 0
  }
})

const emit = defineEmits(['toggle', 'view'])
const router = useRouter()

const hasChildren = computed(() => {
  return props.muscle.children && props.muscle.children.length > 0
})

const isExpanded = computed(() => {
  return !!props.expanded[props.muscle.id]
})

function toggle() {
  emit('toggle', props.muscle.id)
}

function view() {
  emit('view', props.muscle.id)
}
</script>

<template>
  <div class="muscle-item">
    <div class="muscle-header" :style="{ paddingLeft: level * 20 + 'px' }">
      <button
        v-if="hasChildren"
        @click="toggle"
        class="expand-btn"
      >
        {{ isExpanded ? '−' : '+' }}
      </button>
      <span v-else class="expand-placeholder"></span>
      
      <span class="muscle-name" @click="view">{{ muscle.name }}</span>
      <button @click="view" class="view-btn">Подробнее</button>
    </div>
    
    <div v-if="hasChildren && isExpanded" class="muscle-children">
      <MuscleItem
        v-for="child in muscle.children"
        :key="child.id"
        :muscle="child"
        :expanded="expanded"
        :level="level + 1"
        @toggle="$emit('toggle', $event)"
        @view="$emit('view', $event)"
      />
    </div>
  </div>
</template>

<style scoped>
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


