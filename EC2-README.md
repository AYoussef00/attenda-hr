# Docker Deployment to EC2 - دليل شامل

## نظرة عامة

هذا الدليل يشرح كيفية نشر تطبيق Laravel + Vue على EC2 باستخدام Docker Compose.

## الملفات المهمة

### ملفات الإنتاج
- `docker-compose.prod.yml` - إعدادات Docker Compose للإنتاج (port 80)
- `Dockerfile` - بناء Laravel PHP-FPM container
- `docker/nginx/nginx.conf` - إعدادات Nginx

### Scripts النشر
- `deploy-to-ec2.sh` - Script لنقل الملفات من المحلي إلى EC2
- `setup-ec2.sh` - Script لإعداد Docker على EC2 server

### التوثيق
- `QUICK-START.md` - دليل سريع للبدء
- `DEPLOYMENT.md` - دليل مفصل للنشر
- `ec2-deploy-instructions.txt` - تعليمات خطوة بخطوة

## البدء السريع

### 1. من جهازك المحلي

```bash
# تأكد من وجود system-pair.pem في نفس المجلد
chmod +x deploy-to-ec2.sh
./deploy-to-ec2.sh
```

### 2. على EC2 Server

```bash
# اتصل بالسيرفر
ssh -i system-pair.pem ec2-user@16.16.63.104

# ثبت Docker (إذا لم يكن مثبت)
chmod +x setup-ec2.sh
./setup-ec2.sh

# أو يدوياً
sudo yum install docker -y
sudo systemctl start docker
sudo systemctl enable docker
sudo usermod -a -G docker ec2-user
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose

# اذهب للمشروع
cd /var/www/hrm_system

# أنشئ .env
nano .env
# أضف الإعدادات (راجع DEPLOYMENT.md)

# بناء وتشغيل
docker compose build --no-cache
docker compose up -d
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --force
```

### 3. فتح في المتصفح

```
http://16.16.63.104
```

## الفروقات بين المحلي والإنتاج

| الإعداد | المحلي | الإنتاج |
|---------|--------|---------|
| Port | 8000 | 80 |
| docker-compose.yml | docker-compose.yml | docker-compose.prod.yml |
| Bind Mounts | نعم (للتطوير) | لا (الكود في image) |
| APP_ENV | local | production |
| APP_DEBUG | true | false |

## إعدادات مهمة في .env

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=http://16.16.63.104

DB_HOST=mysql          # مهم جداً: يجب أن يكون "mysql" وليس "127.0.0.1"
DB_DATABASE=hrm_system
DB_USERNAME=hr_admin
DB_PASSWORD=YOUR_PASSWORD
```

## الأوامر المفيدة

### فحص الحالة
```bash
docker compose ps
docker compose logs -f
docker compose logs app
docker compose logs nginx
```

### إعادة التشغيل
```bash
docker compose restart
docker compose restart app
docker compose restart nginx
```

### التحديث
```bash
# بعد تحديث الكود
docker compose up -d --build
docker compose exec app php artisan migrate --force
docker compose exec app php artisan config:cache
```

### Backup
```bash
# Backup database
docker compose exec mysql mysqldump -u hr_admin -p hrm_system > backup.sql

# Backup files
tar -czf storage-backup.tar.gz storage/
```

## استكشاف الأخطاء

### 502 Bad Gateway
```bash
docker compose logs app
docker compose logs nginx
docker compose restart app nginx
```

### Database Connection Error
- تحقق من `DB_HOST=mysql` في .env
- تحقق من MySQL container: `docker compose ps mysql`
- اختبر الاتصال: `docker compose exec app php artisan db:show`

### Permission Denied
```bash
docker compose exec app chown -R www-data:www-data /var/www/storage
docker compose exec app chmod -R 775 /var/www/storage
```

## Security Checklist

- [ ] `APP_DEBUG=false` في الإنتاج
- [ ] `APP_ENV=production`
- [ ] كلمات مرور قوية لقاعدة البيانات
- [ ] فتح port 80 فقط في Security Group
- [ ] إغلاق port 3306 (MySQL داخلي فقط)
- [ ] استخدام HTTPS (Let's Encrypt) إذا كان لديك domain

## الدعم

للمزيد من التفاصيل:
- `QUICK-START.md` - للبدء السريع
- `DEPLOYMENT.md` - للدليل المفصل
- `ec2-deploy-instructions.txt` - للتعليمات خطوة بخطوة

