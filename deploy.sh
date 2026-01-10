#!/bin/bash

# Скрипт для автоматического развертывания на production сервере
# Использование: ./deploy.sh

set -e  # Остановка при ошибке

echo "🚀 Начало развертывания..."

# Цвета для вывода
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Проверка наличия .env файла
if [ ! -f .env ]; then
    echo -e "${RED}❌ Файл .env не найден!${NC}"
    echo "Скопируйте .env.production.example в .env и заполните значения"
    exit 1
fi

# Проверка Docker
if ! command -v docker &> /dev/null; then
    echo -e "${RED}❌ Docker не установлен!${NC}"
    exit 1
fi

if ! command -v docker compose &> /dev/null; then
    echo -e "${RED}❌ Docker Compose не установлен!${NC}"
    exit 1
fi

echo -e "${GREEN}✓ Docker и Docker Compose установлены${NC}"

# Остановка существующих контейнеров
echo -e "${YELLOW}⏸ Остановка контейнеров...${NC}"
docker compose -f docker-compose.prod.yaml down || true

# Получение обновлений из Git (если используется)
if [ -d .git ]; then
    echo -e "${YELLOW}📥 Получение обновлений из Git...${NC}"
    git pull || echo "⚠️ Не удалось получить обновления из Git"
fi

# Сборка образов
echo -e "${YELLOW}🔨 Сборка Docker образов...${NC}"
docker compose -f docker-compose.prod.yaml build --no-cache

# Запуск контейнеров
echo -e "${YELLOW}▶️ Запуск контейнеров...${NC}"
docker compose -f docker-compose.prod.yaml up -d

# Ожидание готовности MySQL
echo -e "${YELLOW}⏳ Ожидание готовности MySQL...${NC}"
sleep 10

# Установка зависимостей Laravel
echo -e "${YELLOW}📦 Установка зависимостей Laravel...${NC}"
docker compose -f docker-compose.prod.yaml exec -T backend composer install --optimize-autoloader --no-dev --no-interaction

# Запуск миграций
echo -e "${YELLOW}🗄️ Запуск миграций...${NC}"
docker compose -f docker-compose.prod.yaml exec -T backend php artisan migrate --force

# Очистка и кеширование
echo -e "${YELLOW}🧹 Очистка и оптимизация...${NC}"
docker compose -f docker-compose.prod.yaml exec -T backend php artisan config:cache
docker compose -f docker-compose.prod.yaml exec -T backend php artisan route:cache
docker compose -f docker-compose.prod.yaml exec -T backend php artisan view:cache
docker compose -f docker-compose.prod.yaml exec -T backend php artisan cache:clear

# Установка прав на storage
echo -e "${YELLOW}🔐 Установка прав доступа...${NC}"
docker compose -f docker-compose.prod.yaml exec -T backend chmod -R 775 storage bootstrap/cache
docker compose -f docker-compose.prod.yaml exec -T backend chown -R www-data:www-data storage bootstrap/cache

# Проверка статуса
echo -e "${YELLOW}📊 Проверка статуса контейнеров...${NC}"
docker compose -f docker-compose.prod.yaml ps

echo -e "${GREEN}✅ Развертывание завершено успешно!${NC}"
echo ""
echo "Проверьте логи: docker compose -f docker-compose.prod.yaml logs -f"
echo "Проверьте статус: docker compose -f docker-compose.prod.yaml ps"


