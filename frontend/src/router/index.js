import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'

import Home from '../pages/Home.vue'
import Login from '../pages/Login.vue'
import Register from '../pages/Register.vue'
import Muscles from '../pages/Muscles.vue'
import MuscleDetail from '../pages/MuscleDetail.vue'
import Trainings from '../pages/Trainings.vue'
import TrainingDetail from '../pages/TrainingDetail.vue'
import TrainingCreate from '../pages/TrainingCreate.vue'
import TrainingEdit from '../pages/TrainingEdit.vue'
import Exercises from '../pages/Exercises.vue'
import ExerciseDetail from '../pages/ExerciseDetail.vue'
import ExerciseCreate from '../pages/ExerciseCreate.vue'
import ExerciseEdit from '../pages/ExerciseEdit.vue'
import Profile from '../pages/Profile.vue'

const routes = [
  { path: '/', component: Home },
  { path: '/login', component: Login },
  { path: '/register', component: Register },
  { path: '/muscles', component: Muscles },
  { path: '/muscles/:id', component: MuscleDetail, props: true },
  { 
    path: '/exercises', 
    component: Exercises,
    meta: { requiresAuth: true }
  },
  { 
    path: '/exercises/create', 
    component: ExerciseCreate,
    meta: { requiresAuth: true }
  },
  { 
    path: '/exercises/:id', 
    component: ExerciseDetail, 
    props: true,
    meta: { requiresAuth: true }
  },
  { 
    path: '/exercises/:id/edit', 
    component: ExerciseEdit, 
    props: true,
    meta: { requiresAuth: true }
  },
  { 
    path: '/trainings', 
    component: Trainings,
    meta: { requiresAuth: true }
  },
  { 
    path: '/trainings/create', 
    component: TrainingCreate,
    meta: { requiresAuth: true }
  },
  { 
    path: '/trainings/:id', 
    component: TrainingDetail, 
    props: true,
    meta: { requiresAuth: true }
  },
  { 
    path: '/trainings/:id/edit', 
    component: TrainingEdit, 
    props: true,
    meta: { requiresAuth: true }
  },
  { 
    path: '/profile', 
    component: Profile,
    meta: { requiresAuth: true }
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

// Защита маршрутов
router.beforeEach((to, from, next) => {
  const authStore = useAuthStore()
  
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    next('/login')
  } else {
    next()
  }
})

export default router
