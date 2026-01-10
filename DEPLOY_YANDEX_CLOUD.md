# Развертывание на Yandex Cloud

## Вариант 1: Виртуальная машина (Compute Cloud) - Рекомендуется для начала

### Шаг 1: Создание виртуальной машины

1. Войдите в [Yandex Cloud Console](https://console.cloud.yandex.ru/)
2. Создайте новый каталог (если нужно)
3. Перейдите в **Compute Cloud** → **Виртуальные машины**
4. Нажмите **Создать ВМ**

**Рекомендуемые параметры:**
- **Имя**: `trainer-production`
- **Зона доступности**: `ru-central1-a` (или другая по вашему выбору)
- **Образ**: `Ubuntu 22.04 LTS` или `Ubuntu 24.04 LTS`
- **Платформа**: Intel Ice Lake
- **vCPU**: 2-4 ядра
- **RAM**: 4-8 GB
- **Диск**: SSD, 20-40 GB
- **Публичный IP**: Включить
- **Пользователь**: `ubuntu` (или другой)

### Шаг 2: Настройка безопасности

1. В разделе **Безопасность** создайте или выберите группу безопасности
2. Добавьте правила для входящего трафика:
   - **HTTP (80)**: `0.0.0.0/0`
   - **HTTPS (443)**: `0.0.0.0/0`
   - **SSH (22)**: Ваш IP адрес (для безопасности)

### Шаг 3: Подключение к серверу

```bash
ssh ubuntu@<PUBLIC_IP>
```

### Шаг 4: Установка Docker и Docker Compose

```bash
# Обновление системы
sudo apt update && sudo apt upgrade -y

# Установка зависимостей
sudo apt install -y apt-transport-https ca-certificates curl software-properties-common

# Добавление официального GPG ключа Docker
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /usr/share/keyrings/docker-archive-keyring.gpg

# Добавление репозитория Docker
echo "deb [arch=$(dpkg --print-architecture) signed-by=/usr/share/keyrings/docker-archive-keyring.gpg] https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable" | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null

# Установка Docker
sudo apt update
sudo apt install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin

# Добавление пользователя в группу docker
sudo usermod -aG docker $USER

# Проверка установки
docker --version
docker compose version
```

**Важно**: После добавления в группу docker, переподключитесь к серверу.

### Шаг 5: Клонирование проекта

```bash
# Установка Git (если не установлен)
sudo apt install -y git

# Клонирование репозитория
git clone <YOUR_REPO_URL> /var/www/trainer
cd /var/www/trainer

# Или загрузите проект через SCP/SFTP
```

### Шаг 6: Настройка переменных окружения

```bash
# Создание .env файла для production
cp .env.example .env

# Редактирование .env файла
nano .env
```

**Важные переменные для production:**
```env
# MySQL
MYSQL_DATABASE=trainer_db
MYSQL_USERNAME=trainer_user
MYSQL_PASSWORD=<СИЛЬНЫЙ_ПАРОЛЬ>
MYSQL_ROOT_PASSWORD=<СИЛЬНЫЙ_ПАРОЛЬ>

# Laravel
LARAVEL_APP_ENV=production
LARAVEL_APP_DEBUG=false
LARAVEL_APP_URL=https://yourdomain.com
LARAVEL_APP_KEY=<СГЕНЕРИРОВАТЬ_НОВЫЙ>

# Nginx
NGINX_HOST_PORT=80

# Redis (опционально)
REDIS_PASSWORD=<СИЛЬНЫЙ_ПАРОЛЬ>
```

**Генерация APP_KEY:**
```bash
cd backend
php artisan key:generate
```

### Шаг 7: Настройка backend/.env

```bash
cd backend
cp .env.example .env
nano .env
```

**Настройте:**
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=trainer_db
DB_USERNAME=trainer_user
DB_PASSWORD=<СИЛЬНЫЙ_ПАРОЛЬ>

REDIS_HOST=redis
REDIS_PASSWORD=<СИЛЬНЫЙ_ПАРОЛЬ>
```

### Шаг 8: Настройка frontend/.env

```bash
cd frontend
nano .env
```

```env
VITE_API_BASE_URL=https://yourdomain.com/api/v1
```

### Шаг 9: Сборка и запуск

```bash
cd /var/www/trainer

# Сборка образов
docker compose build

# Запуск контейнеров
docker compose up -d

# Проверка статуса
docker compose ps

# Просмотр логов
docker compose logs -f
```

### Шаг 10: Настройка Laravel

```bash
# Вход в контейнер backend
docker compose exec backend bash

# Установка зависимостей
composer install --optimize-autoloader --no-dev

# Генерация ключа приложения
php artisan key:generate

# Запуск миграций
php artisan migrate --force

# Запуск сидеров
php artisan db:seed --force

# Оптимизация для production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Установка прав на storage
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

exit
```

### Шаг 11: Сборка frontend

```bash
# Вход в контейнер frontend (или на хосте, если Node.js установлен)
cd frontend
npm install
npm run build

# Копирование собранных файлов в backend/public
# Или настройка nginx для раздачи статики из frontend/dist
```

### Шаг 12: Настройка домена и SSL

1. **Настройка DNS:**
   - Создайте A-запись, указывающую на публичный IP вашей ВМ
   - Например: `yourdomain.com` → `<PUBLIC_IP>`

2. **Установка Certbot для SSL:**
```bash
sudo apt install -y certbot python3-certbot-nginx

# Получение сертификата (если используете nginx на хосте)
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com
```

**Или настройте SSL в nginx контейнере** (см. раздел "Настройка Nginx для HTTPS")

### Шаг 13: Настройка Nginx для production

Обновите `docker/nginx/nginx.conf` для работы с HTTPS и статикой frontend.

---

## Вариант 2: Managed Services (Более надежно, но дороже)

### Использование Yandex Managed MySQL

1. Создайте **Managed Service for MySQL** в консоли
2. Получите endpoint подключения
3. Обновите `DB_HOST` в `.env` на endpoint Managed MySQL
4. Удалите сервис `mysql` из `docker-compose.yaml`

### Использование Yandex Object Storage для статики

1. Создайте бакет в **Object Storage**
2. Настройте CDN (опционально)
3. Загрузите собранный frontend в бакет
4. Настройте публичный доступ

---

## Настройка Nginx для HTTPS

Создайте файл `docker/nginx/nginx-ssl.conf`:

```nginx
server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name yourdomain.com www.yourdomain.com;

    ssl_certificate /etc/letsencrypt/live/yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;
    
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;

    root /var/www/trainer/backend/public;
    index index.php index.html;

    charset utf-8;

    # Логи
    access_log /var/log/nginx/access.log;
    error_log /var/log/nginx/error.log;

    # Статика frontend
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # API
    location /api {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP
    location ~ \.php$ {
        fastcgi_pass backend:9000;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    # Безопасность
    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

---

## Мониторинг и обслуживание

### Просмотр логов

```bash
# Все сервисы
docker compose logs -f

# Конкретный сервис
docker compose logs -f backend
docker compose logs -f nginx
```

### Обновление приложения

```bash
cd /var/www/trainer

# Получение обновлений
git pull

# Пересборка и перезапуск
docker compose build
docker compose up -d

# Обновление миграций
docker compose exec backend php artisan migrate --force

# Очистка кеша
docker compose exec backend php artisan cache:clear
docker compose exec backend php artisan config:clear
docker compose exec backend php artisan route:clear
```

### Резервное копирование базы данных

```bash
# Создание бэкапа
docker compose exec mysql mysqldump -u trainer_user -p trainer_db > backup_$(date +%Y%m%d_%H%M%S).sql

# Восстановление
docker compose exec -T mysql mysql -u trainer_user -p trainer_db < backup.sql
```

---

## Безопасность

1. **Измените все пароли по умолчанию**
2. **Настройте firewall (ufw):**
```bash
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
```

3. **Регулярно обновляйте систему:**
```bash
sudo apt update && sudo apt upgrade -y
```

4. **Используйте сильные пароли для БД и Redis**
5. **Настройте регулярные бэкапы**
6. **Ограничьте доступ к phpMyAdmin** (используйте VPN или удалите в production)

---

## Оценка стоимости

**Минимальная конфигурация:**
- ВМ: 2 vCPU, 4 GB RAM, 20 GB SSD ≈ 1500-2000 ₽/мес
- Публичный IP: ~100 ₽/мес
- Трафик: первые 10 GB бесплатно, далее ~1-2 ₽/GB

**Итого**: ~1600-2100 ₽/мес

---

## Полезные ссылки

- [Документация Yandex Cloud](https://cloud.yandex.ru/docs/)
- [Compute Cloud](https://cloud.yandex.ru/docs/compute/)
- [Managed MySQL](https://cloud.yandex.ru/docs/managed-mysql/)


