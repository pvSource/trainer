<template>
  <MainLayout>
    <div class="home-page">
      <div class="slogan-container">
        <h1 class="slogan">
          <span class="typed-text">{{ displayText }}</span>
          <span class="cursor" :class="{ 'blink': !isTyping }">|</span>
        </h1>
        <button
          v-if="!isTyping"
          @click="goToCreateTraining"
          class="start-button"
          :class="{ 'fade-in': !isTyping }"
        >
          Начать тренировку
        </button>
      </div>
    </div>
  </MainLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import MainLayout from '@/components/MainLayout.vue'

const router = useRouter()
const slogan = 'Тренировки. Ничего лишнего.'
const displayText = ref('')
const isTyping = ref(true)

function typeText() {
  let index = 0
  isTyping.value = true
  
  const typeInterval = setInterval(() => {
    if (index < slogan.length) {
      displayText.value += slogan[index]
      index++
    } else {
      clearInterval(typeInterval)
      isTyping.value = false
    }
  }, 100) // Скорость печати: 100ms на символ
}

function goToCreateTraining() {
  router.push('/trainings/create')
}

onMounted(() => {
  // Небольшая задержка перед началом анимации
  setTimeout(() => {
    typeText()
  }, 300)
})
</script>

<style scoped>
.home-page {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: calc(100vh - 60px); /* Вычитаем высоту навбара */
  padding: 20px;
}

.slogan-container {
  text-align: center;
}

.slogan {
  font-size: 3rem;
  font-weight: 300;
  color: #2c3e50;
  margin: 0;
  line-height: 1.2;
}

.typed-text {
  display: inline-block;
}

.cursor {
  display: inline-block;
  margin-left: 4px;
  color: #3498db;
  font-weight: 400;
  animation: blink 1s infinite;
}

.cursor.blink {
  animation: blink 1s infinite;
}

@keyframes blink {
  0%, 50% {
    opacity: 1;
  }
  51%, 100% {
    opacity: 0;
  }
}

.start-button {
  margin-top: 40px;
  padding: 16px 32px;
  font-size: 1.2rem;
  font-weight: 500;
  color: white;
  background-color: #000000;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  transition: background-color 0.3s, transform 0.2s;
  opacity: 0;
  display: inline-block;
  width: fit-content;
}

.start-button:hover {
  background-color: #333333;
  transform: translateY(-2px);
}

.start-button:active {
  transform: translateY(0);
}

.start-button.fade-in {
  animation: fadeInUp 0.6s ease-out forwards;
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Адаптивность для мобильных устройств */
@media (max-width: 768px) {
  .slogan {
    font-size: 2rem;
  }
}

@media (max-width: 480px) {
  .slogan {
    font-size: 1.5rem;
  }
}
</style>

