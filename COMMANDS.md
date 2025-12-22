# Корисні команди для роботи з блогом

## Встановлення

```bash
# Базове встановлення
./install.sh

# Або вручну
mkdir -p data uploads
chmod 777 data uploads
```

## Створення демо-контенту

```bash
# Створити демонстраційний пост (після авторизації)
php demo_post.php
```

## Резервне копіювання

```bash
# Створити бекап бази даних
cp data/blog.db data/blog.db.backup.$(date +%Y%m%d-%H%M%S)

# Створити бекап файлів
tar -czf backup-$(date +%Y%m%d).tar.gz data/ uploads/

# Відновити з бекапу
cp data/blog.db.backup.YYYYMMDD-HHMMSS data/blog.db
```

## Очищення

```bash
# Видалити всі пости (УВАГА!)
rm data/blog.db

# Видалити тільки коментарі
sqlite3 data/blog.db "DELETE FROM comments;"

# Видалити всі завантажені файли (УВАГА!)
rm uploads/*
```

## Налаштування веб-сервера

### Apache

```apache
<VirtualHost *:80>
    ServerName myblog.com
    DocumentRoot /var/www/blog
    
    <Directory /var/www/blog>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/blog_error.log
    CustomLog ${APACHE_LOG_DIR}/blog_access.log combined
</VirtualHost>
```

### Nginx

```nginx
server {
    listen 80;
    server_name myblog.com;
    root /var/www/blog;
    index index.php;
    
    location / {
        try_files $uri $uri/ /index.php?note=$uri&$args;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
    
    location ~ /\.(ht|git|svn) {
        deny all;
    }
    
    location ~* \.(jpg|jpeg|png|gif|webp|css|js)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

## Експорт/Імпорт

```bash
# Експорт постів в JSON
sqlite3 data/blog.db "SELECT json_group_array(json_object(
    'title', title, 
    'text', text, 
    'url_name', url_name, 
    'stamp', stamp
)) FROM notes" > posts_export.json

# Показати всі пости
sqlite3 data/blog.db "SELECT id, title, stamp FROM notes;"

# Показати всі теги
sqlite3 data/blog.db "SELECT * FROM tags;"
```

## Оптимізація

```bash
# Оптимізувати базу даних
sqlite3 data/blog.db "VACUUM;"

# Показати розмір бази
du -h data/blog.db

# Показати кількість постів
sqlite3 data/blog.db "SELECT COUNT(*) FROM notes;"
```

## Перевірка помилок PHP

```bash
# Перевірити синтаксис
php -l index.php
php -l system/core.php

# Показати помилки PHP
tail -f /var/log/apache2/error.log
# або
tail -f /var/log/php8.1-fpm.log
```

## Оновлення

```bash
# Зробити бекап перед оновленням
./install.sh backup

# Оновити файли (якщо це git репозиторій)
git pull origin main

# Очистити кеш
php -r "opcache_reset();"
```

## Налаштування прав

```bash
# Правильні права для продакшену
chown -R www-data:www-data /var/www/blog
chmod 755 /var/www/blog
chmod 777 /var/www/blog/data
chmod 777 /var/www/blog/uploads
chmod 644 /var/www/blog/*.php
chmod 644 /var/www/blog/system/*.php
```

## Тестування

```bash
# Перевірити доступність SQLite
php -r "echo class_exists('PDO') ? 'PDO доступно' : 'PDO не знайдено'; echo PHP_EOL;"
php -r "echo in_array('sqlite', PDO::getAvailableDrivers()) ? 'SQLite доступно' : 'SQLite не знайдено'; echo PHP_EOL;"

# Перевірити права запису
touch data/test && rm data/test && echo "✅ Права data/ OK" || echo "❌ Немає прав на data/"
touch uploads/test && rm uploads/test && echo "✅ Права uploads/ OK" || echo "❌ Немає прав на uploads/"
```

## Зміна пароля

```bash
# Відкрийте system/core.php та змініть:
# $admin_pass = 'новий_пароль';

# Або через sed (на вашу відповідальність!)
sed -i "s/\$admin_pass = '.*'/\$admin_pass = 'новий_пароль'/" system/core.php
```

## Моніторинг

```bash
# Показати останні коментарі
sqlite3 data/blog.db "SELECT author, text, datetime(stamp, 'unixepoch') FROM comments ORDER BY stamp DESC LIMIT 10;"

# Показати популярні теги
sqlite3 data/blog.db "SELECT t.name, COUNT(pt.post_id) as count FROM tags t JOIN post_tags pt ON t.id = pt.tag_id GROUP BY t.id ORDER BY count DESC;"
```
