<?php
require_once 'system/core.php';

if (!IS_ADMIN) {
    header("Location: /");
    exit;
}

// Обробка форм
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    
    // Основні налаштування
    if (isset($_POST['save_general'])) {
        set_setting('site_name', trim($_POST['site_name']));
        set_setting('site_description', trim($_POST['site_description']));
        
        // Логотип
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed)) {
                $filename = 'logo.' . $ext;
                move_uploaded_file($_FILES['logo']['tmp_name'], $filename);
                set_setting('logo_path', $filename);
            }
        }
        
        // Фавікон
        if (isset($_FILES['favicon']) && $_FILES['favicon']['error'] === 0) {
            $allowed = ['ico', 'png'];
            $ext = strtolower(pathinfo($_FILES['favicon']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed)) {
                $filename = 'favicon.' . $ext;
                move_uploaded_file($_FILES['favicon']['tmp_name'], $filename);
                set_setting('favicon_path', $filename);
            }
        }
        
        $success = "Основні налаштування збережено!";
    }
    
    // Колір
    if (isset($_POST['save_color'])) {
        set_setting('accent_color', $_POST['accent_color']);
        $success = "Колір збережено!";
    }
    
    // Google Analytics
    if (isset($_POST['save_analytics'])) {
        set_setting('google_analytics', trim($_POST['google_analytics']));
        $success = "Google Analytics збережено!";
    }
    
    // Зміна паролю
    if (isset($_POST['change_password'])) {
        $current = $_POST['current_password'];
        $new = $_POST['new_password'];
        $confirm = $_POST['confirm_password'];
        
        if (password_verify($current, $admin_pass)) {
            if ($new === $confirm && strlen($new) >= 6) {
                $new_hash = password_hash($new, PASSWORD_DEFAULT);
                file_put_contents('system/.admin_pass', $new_hash);
                $success = "Пароль змінено!";
            } else {
                $error = "Нові паролі не співпадають або закороткі (мін. 6 символів)";
            }
        } else {
            $error = "Невірний поточний пароль";
        }
    }
}

$site_name = get_setting('site_name', 'Мій Блог');
$site_description = get_setting('site_description', 'Особистий блог');
$logo_path = get_setting('logo_path');
$favicon_path = get_setting('favicon_path');
$accent_color = get_setting('accent_color', '#0066cc');
$ga_code = get_setting('google_analytics');

// Палітра кольорів
$color_palette = [
    '#0066cc' => 'Синій',
    '#2ecc71' => 'Зелений',
    '#e74c3c' => 'Червоний',
    '#9b59b6' => 'Фіолетовий',
    '#f39c12' => 'Помаранчевий',
    '#34495e' => 'Сірий',
    '#1abc9c' => 'Бірюзовий',
];

// Змінні для header
$is_home = false;
$page_title = 'Налаштування - ' . $site_name;

include 'includes/header.php';
?>

<style>
.admin-panel {
  /* Без max-width - на всю ширину контейнера */
}

.admin-header {
  margin-bottom: 30px;
  padding-bottom: 20px;
  border-bottom: 1px solid var(--border);
}

.admin-header h2 {
  margin: 0 0 8px;
  font: 600 28px/1.3 -apple-system, sans-serif;
}

.admin-header p {
  margin: 0;
  color: var(--gray);
  font-size: 14px;
}

.message {
  padding: 14px 18px;
  border-radius: 6px;
  margin-bottom: 24px;
  font-size: 14px;
}

.message.success {
  background: #d4edda;
  color: #155724;
  border: 1px solid #c3e6cb;
}

body.dark .message.success {
  background: #1e4620;
  color: #9fdf9f;
  border-color: #2d5a2f;
}

.message.error {
  background: #f8d7da;
  color: #721c24;
  border: 1px solid #f5c6cb;
}

body.dark .message.error {
  background: #4a1f1f;
  color: #f5a3a3;
  border-color: #6b2f2f;
}

.accordion {
  margin-bottom: 12px;
}

.accordion-header {
  background: var(--light-gray);
  border: 1px solid var(--border);
  padding: 16px 20px;
  cursor: pointer;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-radius: 6px;
  transition: background 0.2s;
  font: 600 15px/1.4 -apple-system, sans-serif;
}

.accordion-header:hover {
  background: var(--border);
}

.accordion-header i {
  transition: transform 0.3s;
  opacity: 0.5;
  font-size: 14px;
}

.accordion.active .accordion-header i {
  transform: rotate(180deg);
}

.accordion-content {
  max-height: 0;
  overflow: hidden;
  transition: max-height 0.3s ease;
  border: 1px solid var(--border);
  border-top: none;
  border-radius: 0 0 6px 6px;
  margin-top: -6px;
}

.accordion.active .accordion-content {
  max-height: 2000px;
  padding: 24px 20px;
}

.form-row {
  margin-bottom: 20px;
}

.form-row:last-child {
  margin-bottom: 0;
}

.form-row label {
  display: block;
  margin-bottom: 8px;
  font: 600 14px/1.4 -apple-system, sans-serif;
}

.form-row input[type="text"],
.form-row input[type="password"],
.form-row textarea {
  width: 100%;
  padding: 10px 14px;
  border: 1px solid var(--border);
  border-radius: 6px;
  background: var(--bg);
  color: var(--text);
  font: 14px/1.5 -apple-system, sans-serif;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.form-row input[type="text"]:focus,
.form-row input[type="password"]:focus,
.form-row textarea:focus {
  outline: none;
  border-color: var(--accent);
  box-shadow: 0 0 0 3px rgba(0, 102, 204, 0.1);
}

body.dark .form-row input[type="text"]:focus,
body.dark .form-row input[type="password"]:focus,
body.dark .form-row textarea:focus {
  box-shadow: 0 0 0 3px rgba(0, 102, 204, 0.2);
}

.form-row textarea {
  resize: vertical;
  min-height: 80px;
}

.form-row small {
  display: block;
  margin-top: 6px;
  color: var(--gray);
  font-size: 13px;
}

.file-input-wrapper {
  display: flex;
  gap: 12px;
  align-items: center;
}

.file-input-wrapper input[type="file"] {
  flex: 1;
  padding: 8px 12px;
  border: 1px solid var(--border);
  border-radius: 6px;
  background: var(--bg);
  font-size: 13px;
  color: var(--text);
  cursor: pointer;
  transition: border-color 0.2s;
}

.file-input-wrapper input[type="file"]:hover {
  border-color: var(--accent);
}

.file-input-wrapper input[type="file"]::file-selector-button {
  background: var(--light-gray);
  border: 1px solid var(--border);
  border-radius: 4px;
  padding: 6px 14px;
  margin-right: 12px;
  cursor: pointer;
  font: 500 13px/1.4 -apple-system, sans-serif;
  color: var(--text);
  transition: background 0.2s;
}

.file-input-wrapper input[type="file"]::file-selector-button:hover {
  background: var(--border);
}

.current-file {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 14px;
  background: var(--light-gray);
  border: 1px solid var(--border);
  border-radius: 6px;
  font-size: 13px;
  margin-bottom: 10px;
}

.current-file img {
  width: 40px;
  height: 40px;
  border-radius: 6px;
  object-fit: cover;
  border: 1px solid var(--border);
}

.color-palette {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
}

.color-option {
  width: 52px;
  height: 52px;
  border-radius: 8px;
  cursor: pointer;
  border: 3px solid transparent;
  transition: all 0.2s;
  position: relative;
}

.color-option:hover {
  transform: scale(1.1);
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.color-option.active {
  border-color: var(--text);
  box-shadow: 0 0 0 2px var(--bg), 0 0 0 4px var(--text);
}

.color-option::after {
  content: '✓';
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  color: white;
  font-size: 20px;
  font-weight: bold;
  opacity: 0;
  text-shadow: 0 1px 3px rgba(0,0,0,0.3);
}

.color-option.active::after {
  opacity: 1;
}

.form-submit {
  margin-top: 20px;
  padding-top: 20px;
  border-top: 1px solid var(--border);
}

.btn-admin {
  background: var(--accent);
  color: #fff;
  border: none;
  padding: 10px 24px;
  border-radius: 6px;
  cursor: pointer;
  font: 500 14px/1.4 -apple-system, sans-serif;
  transition: opacity 0.2s;
}

.btn-admin:hover {
  opacity: 0.85;
}
</style>

<div class="admin-panel">

<div class="admin-header">
  <h2>Налаштування</h2>
  <p>Керування блогом</p>
</div>

<?php if (isset($success)): ?>
<div class="message success"><?= e($success) ?></div>
<?php endif; ?>

<?php if (isset($error)): ?>
<div class="message error"><?= e($error) ?></div>
<?php endif; ?>

<!-- Основні налаштування -->
<div class="accordion active">
  <div class="accordion-header">
    <span>Основні налаштування</span>
    <i class="fa-solid fa-chevron-down"></i>
  </div>
  <div class="accordion-content">
    <form method="POST" enctype="multipart/form-data">
      <?= csrf_field() ?>
      
      <div class="form-row">
        <label>Назва сайту</label>
        <input type="text" name="site_name" value="<?= e($site_name) ?>" required>
      </div>
      
      <div class="form-row">
        <label>Опис сайту</label>
        <textarea name="site_description" rows="2"><?= e($site_description) ?></textarea>
        <small>Відображається в шапці та мета-тегах</small>
      </div>
      
      <div class="form-row">
        <label>Логотип</label>
        <?php if ($logo_path && file_exists($logo_path)): ?>
        <div class="current-file">
          <img src="/<?= e($logo_path) ?>" alt="Логотип">
          <span><?= basename($logo_path) ?></span>
        </div>
        <?php endif; ?>
        <div class="file-input-wrapper">
          <input type="file" name="logo" accept="image/*">
        </div>
        <small>JPG, PNG, GIF, WEBP (рекомендовано: квадратне зображення)</small>
      </div>
      
      <div class="form-row">
        <label>Фавікон</label>
        <?php if ($favicon_path && file_exists($favicon_path)): ?>
        <div class="current-file">
          <img src="/<?= e($favicon_path) ?>" alt="Фавікон">
          <span><?= basename($favicon_path) ?></span>
        </div>
        <?php endif; ?>
        <div class="file-input-wrapper">
          <input type="file" name="favicon" accept=".ico,.png">
        </div>
        <small>ICO або PNG (рекомендовано: 32x32px)</small>
      </div>
      
      <div class="form-submit">
        <button type="submit" name="save_general" class="btn-admin">Зберегти</button>
      </div>
    </form>
  </div>
</div>

<!-- Кольори -->
<div class="accordion">
  <div class="accordion-header">
    <span>Акцентний колір</span>
    <i class="fa-solid fa-chevron-down"></i>
  </div>
  <div class="accordion-content">
    <form method="POST" id="colorForm">
      <?= csrf_field() ?>
      <input type="hidden" name="accent_color" id="accentColorInput" value="<?= e($accent_color) ?>">
      
      <div class="form-row">
        <label>Оберіть колір для посилань та акцентів</label>
        <div class="color-palette">
          <?php foreach ($color_palette as $color => $name): ?>
          <div class="color-option <?= $color === $accent_color ? 'active' : '' ?>" 
               style="background-color: <?= $color ?>" 
               data-color="<?= $color ?>"
               title="<?= $name ?>"></div>
          <?php endforeach; ?>
        </div>
      </div>
      
      <div class="form-submit">
        <button type="submit" name="save_color" class="btn-admin">Зберегти колір</button>
      </div>
    </form>
  </div>
</div>

<!-- Інтеграції -->
<div class="accordion">
  <div class="accordion-header">
    <span>Інтеграції</span>
    <i class="fa-solid fa-chevron-down"></i>
  </div>
  <div class="accordion-content">
    <form method="POST">
      <?= csrf_field() ?>
      
      <div class="form-row">
        <label>Google Analytics</label>
        <input type="text" name="google_analytics" value="<?= e($ga_code) ?>" placeholder="G-XXXXXXXXXX">
        <small>Measurement ID з Google Analytics 4</small>
      </div>
      
      <div class="form-submit">
        <button type="submit" name="save_analytics" class="btn-admin">Зберегти</button>
      </div>
    </form>
  </div>
</div>

<!-- Безпека -->
<div class="accordion">
  <div class="accordion-header">
    <span>Безпека</span>
    <i class="fa-solid fa-chevron-down"></i>
  </div>
  <div class="accordion-content">
    <form method="POST">
      <?= csrf_field() ?>
      
      <div class="form-row">
        <label>Поточний пароль</label>
        <input type="password" name="current_password" required>
      </div>
      
      <div class="form-row">
        <label>Новий пароль</label>
        <input type="password" name="new_password" required minlength="6">
        <small>Мінімум 6 символів</small>
      </div>
      
      <div class="form-row">
        <label>Підтвердження нового паролю</label>
        <input type="password" name="confirm_password" required minlength="6">
      </div>
      
      <div class="form-submit">
        <button type="submit" name="change_password" class="btn-admin">Змінити пароль</button>
      </div>
    </form>
  </div>
</div>

</div>

<script>
// Accordion
document.querySelectorAll('.accordion-header').forEach(header => {
  header.addEventListener('click', () => {
    const accordion = header.parentElement;
    const wasActive = accordion.classList.contains('active');
    
    // Закрити всі
    document.querySelectorAll('.accordion').forEach(acc => {
      acc.classList.remove('active');
    });
    
    // Відкрити поточний (якщо був закритий)
    if (!wasActive) {
      accordion.classList.add('active');
    }
  });
});

// Вибір кольору
document.querySelectorAll('.color-option').forEach(option => {
  option.addEventListener('click', () => {
    // Прибрати active з усіх
    document.querySelectorAll('.color-option').forEach(opt => {
      opt.classList.remove('active');
    });
    
    // Додати active до вибраного
    option.classList.add('active');
    
    // Встановити значення
    document.getElementById('accentColorInput').value = option.dataset.color;
  });
});
</script>

<?php include 'includes/footer.php'; ?>
