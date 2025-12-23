<?php
require_once 'system/core.php';

// Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: /");
    exit;
}

// Login
if (isset($_POST['p'])) {
    // Перший раз? Використовуйте це для генерації хешу:
    // echo password_hash('ваш_пароль', PASSWORD_DEFAULT);
    
    // Порівняння з хешем (безпечний спосіб)
    if (password_verify($_POST['p'], $admin_pass)) {
        $_SESSION['admin'] = true;
        header("Location: /");
        exit;
    } else {
        $error = "Невірний пароль";
    }
}
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вхід</title>
    <style>
        body {
            font-family: -apple-system, sans-serif;
            background: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
        }
        .login-box {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 300px;
            width: 100%;
        }
        h1 {
            margin: 0 0 30px;
            font-size: 24px;
        }
        input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            margin-bottom: 15px;
            box-sizing: border-box;
        }
        button {
            width: 100%;
            padding: 12px;
            background: #000;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
        }
        button:hover {
            background: #ff5a1f;
        }
        .error {
            color: #d9534f;
            margin-bottom: 15px;
            font-size: 14px;
        }
        .hint {
            margin-top: 20px;
            font-size: 12px;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="login-box">
        <h1> Вхід до блогу</h1>
        <?php if (isset($error)): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST">
            <input type="password" name="p" placeholder="Пароль" autofocus required>
            <button type="submit">Увійти</button>
        </form>
        <div class="hint">
            Не пам'ятаєте пароль? Перевірте system/core.php
        </div>
    </div>
</body>
</html>