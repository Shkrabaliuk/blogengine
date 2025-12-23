<?php
require_once 'system/core.php';

// Перевірка доступу
if (!IS_ADMIN) {
    header("Location: /");
    exit;
}

// Створення нової статті
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save']) && verify_csrf()) {
    $title = trim($_POST['title']);
    $text = $_POST['text'];
    $is_draft = isset($_POST['is_draft']) ? 1 : 0;
    $snippet = trim($_POST['snippet'] ?? '');
    $tags = $_POST['tags'] ?? '';
    
    $url = strtolower(trim(preg_replace('/[^A-Za-zА-Яа-яІіЇїЄєҐґ0-9-]+/u', '-', $title), '-'));
    if (empty($url)) {
        $url = time();
    }
    
    $db->prepare("INSERT INTO notes (title, text, url_name, stamp, is_draft, snippet) VALUES (?,?,?,?,?,?)")
       ->execute([$title, $text, $url, time(), $is_draft, $snippet]);
    $post_id = $db->lastInsertId();
    
    save_post_tags($post_id, $tags);
    
    header("Location: /$url"); 
    exit;
}

// Змінні для header
$is_home = false;
$page_title = 'Нова стаття - ' . get_setting('site_name', 'Мій Блог');
$page_description = 'Створення нової статті';

// Підключаємо header
include 'includes/header.php';
?>

<style>
.help-toggle {
  background: transparent;
  border: none;
  color: var(--accent);
  cursor: pointer;
  font-size: 13px;
  margin-left: 8px;
  padding: 4px 8px;
  border-radius: 4px;
  transition: background 0.2s;
}

.help-toggle:hover {
  background: var(--light-gray);
}

.help-toggle i {
  font-size: 14px;
}

.markdown-help {
  max-height: 0;
  overflow: hidden;
  transition: max-height 0.3s ease;
  margin-top: 12px;
}

.markdown-help.active {
  max-height: 500px;
}

.markdown-help-content {
  background: var(--light-gray);
  border: 1px solid var(--border);
  border-radius: 6px;
  padding: 16px;
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
}

.help-section {
  font-size: 13px;
}

.help-section h4 {
  margin: 0 0 8px 0;
  font: 600 14px/1.3 -apple-system, sans-serif;
  color: var(--text);
}

.help-section code {
  display: block;
  background: var(--bg);
  padding: 4px 8px;
  border-radius: 4px;
  margin: 4px 0;
  font-family: 'Courier New', monospace;
  font-size: 12px;
  border: 1px solid var(--border);
}

.help-section small {
  color: var(--gray);
}

@media (max-width: 768px) {
  .markdown-help-content {
    grid-template-columns: 1fr;
  }
}
</style>

<h2 class="page-title">Нова стаття</h2>

<?php 
$p = null; // Для форми створення
include 'templates/editor-form.php'; 
?>

<?php include 'includes/footer.php'; ?>

<script>
// Markdown Help Toggle
const helpToggle = document.getElementById('markdownHelpToggle');
const helpContent = document.getElementById('markdownHelp');

if (helpToggle && helpContent) {
  helpToggle.addEventListener('click', () => {
    helpContent.classList.toggle('active');
    const icon = helpToggle.querySelector('i');
    if (helpContent.classList.contains('active')) {
      icon.className = 'fa-solid fa-circle-xmark';
    } else {
      icon.className = 'fa-solid fa-circle-question';
    }
  });
}
</script>

