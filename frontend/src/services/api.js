/**
 * API сервис для работы с бэкендом
 * 
 * Централизованная функция для всех HTTP запросов
 * - Автоматически добавляет базовый URL
 * - Добавляет токен авторизации в заголовки
 * - Обрабатывает ошибки (особенно 401)
 * - Преобразует объекты в JSON
 */

// Базовый URL API (можно вынести в .env файл)
const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8033/api/v1'

/**
 * Получение токена авторизации из localStorage
 * Store синхронизирует localStorage при изменении токена
 */
function getToken() {
  return localStorage.getItem('auth_token')
}

/**
 * Основная функция для выполнения API запросов
 * 
 * @param {string} url - путь относительно базового URL (например, '/login')
 * @param {object} options - опции для fetch (method, body, headers и т.д.)
 * @returns {Promise} Promise с данными ответа
 */
export async function api(url, options = {}) {
  // Формируем полный URL
  const fullUrl = API_BASE_URL + url
  
  // Получаем токен
  const token = getToken()
  
  // Подготавливаем заголовки
  const headers = {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    ...(options.headers || {})
  }
  
  // Добавляем токен авторизации, если он есть
  if (token) {
    headers['Authorization'] = `Bearer ${token}`
  }
  
  // Обрабатываем body: если передан объект, преобразуем в JSON
  let body = options.body
  if (body && typeof body === 'object' && !(body instanceof FormData)) {
    body = JSON.stringify(body)
  }
  
  try {
    // Выполняем запрос
    const response = await fetch(fullUrl, {
      ...options,
      headers,
      body
    })
    
    // Парсим ответ
    const data = await response.json()
    
    // Проверяем статус ответа
    if (!response.ok) {
      // Если ошибка 401 (неавторизован), очищаем токен
      if (response.status === 401) {
        localStorage.removeItem('auth_token')
        // Store автоматически обновится при следующем обращении
      }
      
      // Пробрасываем ошибку дальше
      throw data
    }
    
    return data
  } catch (error) {
    // Обработка сетевых ошибок или ошибок парсинга JSON
    // Если это уже объект ошибки от сервера - пробрасываем как есть
    if (error && typeof error === 'object' && 'message' in error) {
      throw error
    }
    
    // Иначе создаем объект ошибки
    throw {
      message: error.message || 'Network error',
      errors: {}
    }
  }
}
