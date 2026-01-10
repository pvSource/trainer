<script setup>
import { useAuthStore } from '@/stores/auth'
import Footer from '@/components/Footer.vue'

const authStore = useAuthStore()

async function handleLogout() {
  await authStore.logout()
}
</script>

<template>
  <div class="app-layout">
    <nav class="navbar">
      <div class="nav-left">
        <router-link to="/trainings">Тренировки</router-link>
        <router-link to="/exercises">Упражнения</router-link>
        <router-link to="/measurements">Замеры</router-link>
        <router-link to="/muscles">Мышцы</router-link>
      </div>
      
      <div class="nav-right">
        <template v-if="authStore.isAuthenticated">
          <router-link to="/profile" class="profile-link">
            {{ authStore.user?.name || 'Профиль' }}
          </router-link>
          <button @click="handleLogout" class="logout-btn">Выйти</button>
        </template>
        <template v-else>
          <router-link to="/login">Войти</router-link>
          <router-link to="/register">Регистрация</router-link>
        </template>
      </div>
    </nav>

    <main class="page">
      <slot />
    </main>

    <Footer />
  </div>
</template>

<style scoped>
.app-layout {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

.page {
  flex: 1;
}

.navbar {
  padding: 12px 20px;
  background: #222;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.nav-left {
  display: flex;
  gap: 12px;
}

.nav-right {
  display: flex;
  gap: 12px;
  align-items: center;
}

.navbar a {
  color: white;
  text-decoration: none;
  padding: 6px 12px;
  border-radius: 4px;
  transition: background-color 0.2s;
}

.navbar a:hover {
  background-color: rgba(255, 255, 255, 0.1);
}

.navbar a.router-link-active {
  font-weight: bold;
  background-color: rgba(255, 255, 255, 0.15);
}

.profile-link {
  color: white;
  text-decoration: none;
  padding: 6px 12px;
  border-radius: 4px;
  transition: background-color 0.2s;
  font-weight: 500;
}

.profile-link:hover {
  background-color: rgba(255, 255, 255, 0.1);
}

.profile-link.router-link-active {
  font-weight: bold;
  background-color: rgba(255, 255, 255, 0.15);
}

.logout-btn {
  padding: 6px 12px;
  background-color: #e74c3c;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 14px;
  transition: background-color 0.2s;
}

.logout-btn:hover {
  background-color: #c0392b;
}
</style>
