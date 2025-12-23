<?php
require_once 'system/core.php';

$req = trim($_GET['note'] ?? '', '/');
$post = null;
$is_single = false;
$tag_filter = null;
$search_query = null;

// AJAX логін
if (isset($_POST['ajax_login']) && isset($_POST['password'])) {
    header('Content-Type: application/json');
    if (password_verify($_POST['password'], $admin_pass)) {
        $_SESSION['admin'] = true;
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Невірний пароль']);
    }
    exit;
}

// RSS
if ($req === 'rss') {
    header('Content-Type: application/rss+xml; charset=utf-8');
    echo generate_rss();
    exit;
}

// Фільтр по тегу
if (preg_match('/^tag\/(.+)$/', $req, $m)) {
    $tag_url = $m[1];
    $st = $db->prepare("SELECT * FROM tags WHERE url_name = ?");
    $st->execute([$tag_url]);
    $tag_filter = $st->fetch(PDO::FETCH_ASSOC);
}

// Пошук
if (isset($_GET['s'])) {
    $search_query = trim($_GET['s']);
}

// Окремий пост
if ($req && $req !== 'index.php' && !$tag_filter && !$search_query) {
    $st = $db->prepare("SELECT * FROM notes WHERE url_name = ?");
    $st->execute([$req]);
    $post = $st->fetch(PDO::FETCH_ASSOC);
    if ($post) {
        if ($post['is_draft'] && !IS_ADMIN) {
            $post = null;
        } else {
            $is_single = true;
        }
    }
}

// Обробка форм
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Редагування поста
    if (IS_ADMIN && isset($_POST['save']) && verify_csrf()) {
        $title = trim($_POST['title']);
        $text = $_POST['text'];
        $is_draft = isset($_POST['is_draft']) ? 1 : 0;
        $snippet = trim($_POST['snippet'] ?? '');
        $tags = $_POST['tags'] ?? '';
        
        if ($is_single) {
            $db->prepare("UPDATE notes SET title=?, text=?, is_draft=?, snippet=? WHERE id=?")
               ->execute([$title, $text, $is_draft, $snippet, $post['id']]);
            $post_id = $post['id'];
        }
        
        save_post_tags($post_id, $tags);
        header("Location: /" . $post['url_name']); 
        exit;
    }
    
    // Видалення поста
    if (IS_ADMIN && isset($_POST['delete']) && verify_csrf()) {
        if ($is_single) {
            $db->prepare("DELETE FROM notes WHERE id = ?")->execute([$post['id']]);
            header("Location: /");
            exit;
        }
    }
    
    // Додавання коментаря
    if (isset($_POST['add_comment']) && verify_csrf()) {
        $author = trim($_POST['author']);
        $comment_text = trim($_POST['text']);
        if (!empty($author) && !empty($comment_text)) {
            $db->prepare("INSERT INTO comments (post_id, author, text, stamp) VALUES (?,?,?,?)")
               ->execute([$_POST['post_id'], $author, $comment_text, time()]);
        }
        header("Location: /$req"); 
        exit;
    }
}

// Вибірка постів
if ($is_single) {
    $posts = [$post];
} elseif ($tag_filter) {
    $st = $db->prepare("SELECT n.* FROM notes n INNER JOIN post_tags pt ON n.id = pt.post_id WHERE pt.tag_id = ? AND n.is_draft = 0 ORDER BY n.stamp DESC");
    $st->execute([$tag_filter['id']]);
    $posts = $st->fetchAll(PDO::FETCH_ASSOC);
} elseif ($search_query) {
    $st = $db->prepare("SELECT * FROM notes WHERE (title LIKE ? OR text LIKE ?) AND is_draft = 0 ORDER BY stamp DESC");
    $search = "%$search_query%";
    $st->execute([$search, $search]);
    $posts = $st->fetchAll(PDO::FETCH_ASSOC);
} else {
    $query = IS_ADMIN ? "SELECT * FROM notes ORDER BY stamp DESC" : "SELECT * FROM notes WHERE is_draft = 0 ORDER BY stamp DESC";
    $posts = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);
}

// Змінні для header
$is_home = !$is_single && !$tag_filter && !$search_query;
$page_title = $is_single ? $post['title'] : ($tag_filter ? 'Тег: ' . $tag_filter['name'] : ($search_query ? 'Пошук: ' . $search_query : get_setting('site_name', 'Мій Блог')));
$page_description = $is_single && $post ? ($post['snippet'] ?? substr(strip_tags($post['text']), 0, 160)) : get_setting('site_description', 'Особистий блог');

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

<?php include 'templates/page-header.php'; ?>

<?php foreach($posts as $p): ?>
  <?php include 'includes/post-item.php'; ?>
<?php endforeach; ?>

<?php if($is_single && $post && !isset($_GET['edit'])): ?>
  <?php include 'templates/comments-section.php'; ?>
<?php endif; ?>

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
