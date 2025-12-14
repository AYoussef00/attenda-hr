# Quick Start - نشر سريع على EC2

## الخطوات السريعة

### 1. من جهازك المحلي - نقل الملفات

```bash
# تأكد من وجود system-pair.pem في نفس المجلد
chmod +x deploy-to-ec2.sh
./deploy-to-ec2.sh
```

### 2. على EC2 Server - الإعداد الأولي

```bash
# اتصل بالسيرفر
ssh -i system-pair.pem ec2-user@16.16.63.104

# شغّل script الإعداد (إذا لم يكن Docker مثبت)
# نقل setup-ec2.sh أولاً من المحلي:
# scp -i system-pair.pem setup-ec2.sh ec2-user@16.16.63.104:~/
chmod +x setup-ec2.sh
./setup-ec2.sh

# أو ثبت Docker يدوياً
sudo yum install docker -y
sudo systemctl start docker
sudo systemctl enable docker
sudo usermod -a -G docker ec2-user
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose
```

### 3. إعداد .env

```bash
cd /var/www/hrm_system
nano .env
```

أضف:
```
APP_ENV=production
APP_DEBUG=false
APP_URL=http://16.16.63.104
DB_HOST=mysql
DB_DATABASE=hrm_system
DB_USERNAME=hr_admin
DB_PASSWORD=YOUR_PASSWORD
```

### 4. بناء وتشغيل

```bash
cd /var/www/hrm_system
docker compose build --no-cache
docker compose up -d
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --force
docker compose exec app chown -R www-data:www-data /var/www/storage
```

### 5. فتح في المتصفح

```
http://16.16.63.104
```

## ملاحظات مهمة

- تأكد من فتح port 80 في EC2 Security Group
- DB_HOST يجب أن يكون "mysql" وليس "127.0.0.1"
- للتحديثات المستقبلية: `docker compose up -d --build`

## للمساعدة

راجع `DEPLOYMENT.md` للتفاصيل الكاملة
راجع `ec2-deploy-instructions.txt` للتعليمات المفصلة

