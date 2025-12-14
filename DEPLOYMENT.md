# دليل النشر على EC2

## المتطلبات

- EC2 instance running (16.16.63.104)
- SSH key file: `system-pair.pem`
- Docker و Docker Compose مثبتين على EC2

## الطريقة 1: استخدام Deployment Script (موصى به)

### الخطوة 1: تشغيل Script النشر

```bash
chmod +x deploy-to-ec2.sh
./deploy-to-ec2.sh
```

### الخطوة 2: الاتصال بالسيرفر

```bash
ssh -i system-pair.pem ec2-user@16.16.63.104
```

### الخطوة 3: التحقق من Docker

```bash
docker --version
docker compose version
```

إذا لم يكن Docker مثبت:
```bash
# Amazon Linux 2
sudo yum update -y
sudo yum install docker -y
sudo systemctl start docker
sudo systemctl enable docker
sudo usermod -a -G docker ec2-user

# Install Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose
```

### الخطوة 4: إعداد .env

```bash
cd /var/www/hrm_system
cp .env.production.example .env
nano .env  # أو vi .env
```

تأكد من:
- `APP_ENV=production`
- `APP_DEBUG=false`
- `DB_HOST=mysql`
- `APP_URL=http://16.16.63.104` (أو domain name)
- `DB_PASSWORD` صحيح

### الخطوة 5: بناء وتشغيل

```bash
# Build images
docker compose build --no-cache

# Start containers
docker compose up -d

# Generate app key
docker compose exec app php artisan key:generate

# Run migrations
docker compose exec app php artisan migrate --force

# Set permissions
docker compose exec app chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
docker compose exec app chmod -R 775 /var/www/storage /var/www/bootstrap/cache
```

## الطريقة 2: نقل يدوي

### نقل الملفات

```bash
# نقل Docker files
scp -i system-pair.pem docker-compose.prod.yml ec2-user@16.16.63.104:/var/www/hrm_system/docker-compose.yml
scp -i system-pair.pem Dockerfile ec2-user@16.16.63.104:/var/www/hrm_system/
scp -i system-pair.pem -r docker/ ec2-user@16.16.63.104:/var/www/hrm_system/

# نقل الكود (rsync)
rsync -avz -e "ssh -i system-pair.pem" \
  --exclude 'node_modules' \
  --exclude 'vendor' \
  --exclude '.git' \
  ./ ec2-user@16.16.63.104:/var/www/hrm_system/
```

## إعدادات EC2 Security Group

افتح المنافذ التالية في AWS Console:

1. **Port 80** (HTTP) - من 0.0.0.0/0
2. **Port 443** (HTTPS) - من 0.0.0.0/0 (إذا كان لديك SSL)
3. **Port 22** (SSH) - من IP الخاص بك فقط

## Firewall (إذا كان مفعل)

```bash
sudo firewall-cmd --permanent --add-port=80/tcp
sudo firewall-cmd --permanent --add-port=443/tcp
sudo firewall-cmd --reload
```

## التحقق من التشغيل

```bash
# Check containers
docker compose ps

# Check logs
docker compose logs -f

# Test application
curl http://localhost/api/health
```

## الصيانة

### إعادة تشغيل Containers

```bash
docker compose restart
```

### تحديث الكود

```bash
# Pull latest code (if using Git)
git pull

# Rebuild and restart
docker compose up -d --build
docker compose exec app php artisan migrate --force
docker compose exec app php artisan config:cache
```

### Backup Database

```bash
docker compose exec mysql mysqldump -u hr_admin -p hrm_system > backup.sql
```

### View Logs

```bash
# Application logs
docker compose logs app

# Nginx logs
docker compose logs nginx

# MySQL logs
docker compose logs mysql
```

## استكشاف الأخطاء

### مشكلة: Cannot connect to database

```bash
# Check MySQL container
docker compose ps mysql

# Check network
docker network inspect hrm_system_app-network

# Test connection
docker compose exec app php artisan db:show
```

### مشكلة: 502 Bad Gateway

```bash
# Check PHP-FPM
docker compose logs app

# Check Nginx
docker compose logs nginx

# Restart services
docker compose restart app nginx
```

### مشكلة: Permission denied

```bash
docker compose exec app chown -R www-data:www-data /var/www/storage
docker compose exec app chmod -R 775 /var/www/storage
```

## SSL/HTTPS (اختياري)

إذا كان لديك domain name، يمكنك إعداد Let's Encrypt:

```bash
# Install certbot
sudo yum install certbot -y

# Get certificate
sudo certbot certonly --standalone -d yourdomain.com

# Update nginx.conf to use SSL
```

