<?php
/**
 * Приклад конфігураційного файлу
 * 
 * Скопіюйте цей файл як config.php та відредагуйте налаштування
 */

return [
    // Пароль адміністратора
    'admin_password' => '123',
    
    // Назва блогу
    'blog_title' => 'Мій Блог',
    
    // URL сайту (для RSS та Open Graph)
    'site_url' => 'https://example.com',
    
    // Опис блогу
    'blog_description' => 'Особистий блог про життя, технології та креатив',
    
    // Мова сайту
    'language' => 'uk',
    
    // Кількість постів на сторінці
    'posts_per_page' => 10,
    
    // Увімкнути коментарі
    'comments_enabled' => true,
    
    // Модерація коментарів (якщо true - коментарі потребують схвалення)
    'comments_moderation' => false,
    
    // Максимальний розмір файлу для завантаження (в байтах)
    'max_upload_size' => 10 * 1024 * 1024, // 10MB
    
    // Дозволені типи файлів
    'allowed_file_types' => ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'],
    
    // Часова зона
    'timezone' => 'Europe/Kiev',
    
    // Формат дати
    'date_format' => 'd.m.Y',
    'datetime_format' => 'd.m.Y H:i',
];
