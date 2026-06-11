# Deploy VPS - RuangBelajar AI

Domain target:

```text
ruangbelajar.yasti.site
```

Panduan ini untuk VPS polos tanpa panel.

## 1. Install Paket Server

Contoh Ubuntu:

```bash
sudo apt update
sudo apt install -y nginx mysql-server php8.3-fpm php8.3-cli php8.3-mysql php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip php8.3-gd php8.3-bcmath php8.3-intl unzip git composer certbot python3-certbot-nginx
```

Jika PHP 8.3 belum tersedia di repo VPS, aktifkan repository PHP yang sesuai dengan OS server kamu.

## 2. Buat Database

Masuk MySQL:

```bash
sudo mysql
```

Lalu jalankan:

```sql
CREATE DATABASE ruangbelajar CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'ruangbelajar'@'localhost' IDENTIFIED BY 'GANTI_PASSWORD_KUAT';
GRANT ALL PRIVILEGES ON ruangbelajar.* TO 'ruangbelajar'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

## 3. Clone Project

```bash
sudo mkdir -p /var/www
sudo chown -R $USER:$USER /var/www
git clone https://github.com/USERNAME/NAMA_REPO.git /var/www/ruangbelajar
cd /var/www/ruangbelajar
```

Ganti `USERNAME/NAMA_REPO` dengan repo GitHub kamu.

## 4. Install Dependency

```bash
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
```

Edit `.env`:

```bash
nano .env
```

Isi minimal:

```env
APP_NAME="RuangBelajar AI"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://ruangbelajar.yasti.site

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ruangbelajar
DB_USERNAME=ruangbelajar
DB_PASSWORD=GANTI_PASSWORD_KUAT

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
CACHE_STORE=database
QUEUE_CONNECTION=database

AI_PROVIDER=kimchi
AI_FALLBACK_PROVIDER=none
KIMCHI_API_KEY=ISI_API_KEY_BARU
KIMCHI_BASE_URL=https://llm.cast.ai/openai/v1
KIMCHI_MODEL=minimax-m2.7
KIMCHI_CHAT_MODEL=minimax-m2.7
KIMCHI_SUMMARY_MODEL=minimax-m2.7
KIMCHI_QUIZ_MODEL=minimax-m2.7
KIMCHI_FLASHCARD_MODEL=minimax-m2.7
```

Jangan pakai API key lama yang pernah dibagikan.

## 5. Migrasi dan Admin

```bash
php artisan migrate --force
php artisan db:seed --force
```

Admin default:

```text
username: admin
password: password
```

Langsung ganti password admin setelah login.

## 6. Permission Folder

```bash
sudo chown -R www-data:www-data /var/www/ruangbelajar/storage /var/www/ruangbelajar/bootstrap/cache
sudo chmod -R 775 /var/www/ruangbelajar/storage /var/www/ruangbelajar/bootstrap/cache
```

## 7. Nginx

Buat config:

```bash
sudo nano /etc/nginx/sites-available/ruangbelajar.yasti.site
```

Isi:

```nginx
server {
    listen 80;
    server_name ruangbelajar.yasti.site;
    root /var/www/ruangbelajar/public;

    index index.php index.html;
    client_max_body_size 25M;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Aktifkan:

```bash
sudo ln -s /etc/nginx/sites-available/ruangbelajar.yasti.site /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

## 8. SSL

Pastikan DNS `ruangbelajar.yasti.site` sudah mengarah ke IP VPS, lalu:

```bash
sudo certbot --nginx -d ruangbelajar.yasti.site
```

## 9. Cache Production

```bash
cd /var/www/ruangbelajar
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 10. Update Dari GitHub

Setiap ada update:

```bash
cd /var/www/ruangbelajar
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
sudo chown -R www-data:www-data storage bootstrap/cache
```

## 11. Cek Keamanan Cepat

URL berikut tidak boleh terbuka:

```text
https://ruangbelajar.yasti.site/.env
https://ruangbelajar.yasti.site/composer.json
https://ruangbelajar.yasti.site/storage/app/private/documents
```

Pastikan `.env` server:

```env
APP_ENV=production
APP_DEBUG=false
```
