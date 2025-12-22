<?php
require_once 'core.php';

header('Content-Type: application/json');

if (!IS_ADMIN) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if (!isset($_FILES['img'])) {
    echo json_encode(['error' => 'No file uploaded']);
    exit;
}

$file = $_FILES['img'];

// Перевірка помилок
if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['error' => 'Upload error: ' . $file['error']]);
    exit;
}

// Дозволені типи файлів
$allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mime, $allowed_types)) {
    echo json_encode(['error' => 'Invalid file type. Only images allowed.']);
    exit;
}

// Перевірка розміру (макс 10MB)
if ($file['size'] > 10 * 1024 * 1024) {
    echo json_encode(['error' => 'File too large. Max 10MB.']);
    exit;
}

// Генеруємо безпечне ім'я файлу
$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$safe_name = time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
$upload_path = '../uploads/' . $safe_name;

// Переміщуємо файл
if (move_uploaded_file($file['tmp_name'], $upload_path)) {
    echo json_encode(['url' => '/uploads/' . $safe_name]);
} else {
    echo json_encode(['error' => 'Failed to move uploaded file']);
}
?>
