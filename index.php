<?php
require_once 'system/core.php';

$req = trim($_GET['note'] ?? '', '/');
$post = null;
$is_single = false;
$tag_filter = null;
$search_query = null;

// Роутинг для RSS
if ($req === 'rss') {
    header('Content-Type: application/rss+xml; charset=utf-8');
    echo generate_rss();
    exit;
}

// Роутинг для тегів
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
        // Якщо чернетка і не адмін - 404
        if ($post['is_draft'] && !IS_ADMIN) {
            $post = null;
        } else {
            $is_single = true;
        }
    }
}

// Дії адміна
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Збереження/створення поста
    if (IS_ADMIN && isset($_POST['save']) && verify_csrf()) {
        $title = trim($_POST['title']);
        $text = $_POST['text'];
        $is_draft = isset($_POST['is_draft']) ? 1 : 0;
        $snippet = trim($_POST['snippet'] ?? '');
        $tags = $_POST['tags'] ?? '';
        
        $url = $is_single ? $post['url_name'] : strtolower(trim(preg_replace('/[^A-Za-zА-Яа-яІіЇїЄєҐґ0-9-]+/u', '-', $title), '-'));
        
        if (empty($url)) {
            $url = time();
        }
        
        if ($is_single) {
            // Оновлення
            $db->prepare("UPDATE notes SET title=?, text=?, is_draft=?, snippet=? WHERE id=?")
               ->execute([$title, $text, $is_draft, $snippet, $post['id']]);
            $post_id = $post['id'];
        } else {
            // Створення
            $db->prepare("INSERT INTO notes (title, text, url_name, stamp, is_draft, snippet) VALUES (?,?,?,?,?,?)")
               ->execute([$title, $text, $url, time(), $is_draft, $snippet]);
            $post_id = $db->lastInsertId();
        }
        
        // Зберігаємо теги
        save_post_tags($post_id, $tags);
        
        header("Location: /$url"); 
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
    // Пости за тегом
    $st = $db->prepare("SELECT n.* FROM notes n
                        INNER JOIN post_tags pt ON n.id = pt.post_id
                        WHERE pt.tag_id = ? AND n.is_draft = 0
                        ORDER BY n.stamp DESC");
    $st->execute([$tag_filter['id']]);
    $posts = $st->fetchAll(PDO::FETCH_ASSOC);
} elseif ($search_query) {
    // Пошук
    $st = $db->prepare("SELECT * FROM notes 
                        WHERE (title LIKE ? OR text LIKE ?) AND is_draft = 0 
                        ORDER BY stamp DESC");
    $search = "%$search_query%";
    $st->execute([$search, $search]);
    $posts = $st->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Всі пости (без чернеток для не-адмінів)
    $query = IS_ADMIN ? "SELECT * FROM notes ORDER BY stamp DESC" : "SELECT * FROM notes WHERE is_draft = 0 ORDER BY stamp DESC";
    $posts = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);
}

$page_title = $is_single ? $post['title'] : ($tag_filter ? 'Тег: ' . $tag_filter['name'] : ($search_query ? 'Пошук: ' . $search_query : 'Мій Блог'));
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title) ?></title>
    <?php if ($is_single && $post): ?>
    <meta name="description" content="<?= e($post['snippet'] ?? substr(strip_tags($post['text']), 0, 160)) ?>">
    <meta property="og:title" content="<?= e($post['title']) ?>">
    <meta property="og:description" content="<?= e($post['snippet'] ?? substr(strip_tags($post['text']), 0, 200)) ?>">
    <meta property="og:type" content="article">
    <?php endif; ?>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/8.0.1/normalize.min.css">
    <link rel="alternate" type="application/rss+xml" title="RSS" href="/rss">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/fotorama/4.6.4/fotorama.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fotorama/4.6.4/fotorama.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
    <link rel="stylesheet" href="/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>
                <?php if($is_single || $tag_filter || $search_query): ?>
                    <a href="/">←</a>
                <?php endif; ?>
                Мій Блог
            </h1>
            
            <!-- Пошук -->
            <form method="GET" class="search-form" action="/">
                <input type="text" name="s" placeholder="🔍 Пошук..." value="<?= e($search_query) ?>">
            </form>
            
            <?php if (IS_ADMIN): ?>
                <div class="admin-bar">
                    <a href="/login.php?logout=1">Вийти</a>
                    <a href="/rss" target="_blank">RSS</a>
                </div>
            <?php endif; ?>
        </header>

        <?php if ($tag_filter): ?>
            <div class="tag-header">
                <h2>Тег: <?= e($tag_filter['name']) ?></h2>
            </div>
        <?php endif; ?>
        
        <?php if ($search_query): ?>
            <div class="search-header">
                <h2>Результати пошуку: "<?= e($search_query) ?>"</h2>
                <p>Знайдено: <?= count($posts) ?></p>
            </div>
        <?php endif; ?>

        <?php if(IS_ADMIN && !$is_single && !$tag_filter && !$search_query): ?>
            <form method="POST" class="editor">
                <?= csrf_field() ?>
                <input type="text" name="title" placeholder="Заголовок" class="ed-title" required>
                <textarea name="text" placeholder="Текст (кидайте сюди картинки)..." class="ed-text" rows="10"></textarea>
                <input type="text" name="snippet" placeholder="Короткий опис для соцмереж (необов'язково)" class="ed-snippet">
                <input type="text" name="tags" placeholder="Теги через кому: дизайн, код, подорожі" class="ed-tags">
                <label class="draft-label">
                    <input type="checkbox" name="is_draft" value="1"> Чернетка
                </label>
                <button type="submit" name="save" class="btn">Опублікувати</button>
            </form>
        <?php endif; ?>

        <?php if (empty($posts)): ?>
            <p class="no-results">Нічого не знайдено 😔</p>
        <?php endif; ?>

        <?php foreach($posts as $p): ?>
            <article class="post">
                <div class="meta">
                    <?= date('d.m.Y', $p['stamp']) ?>
                    <?php if ($p['is_draft']): ?>
                        <span class="draft-badge">Чернетка</span>
                    <?php endif; ?>
                    <?php if(IS_ADMIN): ?>
                        <a href="/<?= e($p['url_name']) ?>?edit" class="edit">edit</a>
                    <?php endif; ?>
                </div>
                
                <?php if(IS_ADMIN && isset($_GET['edit']) && $is_single): ?>
                    <form method="POST" class="editor">
                        <?= csrf_field() ?>
                        <input type="text" name="title" value="<?= e($p['title']) ?>" class="ed-title">
                        <textarea name="text" class="ed-text" id="area" rows="15"><?= e($p['text']) ?></textarea>
                        <input type="text" name="snippet" value="<?= e($p['snippet']) ?>" placeholder="Короткий опис" class="ed-snippet">
                        <input type="text" name="tags" value="<?= e(implode(', ', array_column(get_post_tags($p['id']), 'name'))) ?>" placeholder="Теги" class="ed-tags">
                        <label class="draft-label">
                            <input type="checkbox" name="is_draft" value="1" <?= $p['is_draft'] ? 'checked' : '' ?>> Чернетка
                        </label>
                        <button type="submit" name="save" class="btn">Зберегти</button>
                        <button type="submit" name="delete" class="btn btn-danger" onclick="return confirm('Видалити пост назавжди?')">Видалити</button>
                    </form>
                <?php else: ?>
                    <h2><a href="/<?= e($p['url_name']) ?>"><?= e($p['title']) ?></a></h2>
                    <div class="content"><?= smart_typography(e($p['text'])) ?></div>
                    
                    <?php 
                    $post_tags = get_post_tags($p['id']);
                    if (!empty($post_tags)): 
                    ?>
                        <div class="tags">
                            <?php foreach($post_tags as $t): ?>
                                <a href="/tag/<?= e($t['url_name']) ?>" class="tag">#<?= e($t['name']) ?></a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>

        <?php if($is_single && $post): 
            $cms = $db->prepare("SELECT * FROM comments WHERE post_id=? ORDER BY stamp ASC"); 
            $cms->execute([$post['id']]); 
            $comments = $cms->fetchAll(PDO::FETCH_ASSOC);
        ?>
            <section class="comm-sec">
                <h3>Коментарі (<?= count($comments) ?>)</h3>
                <?php foreach($comments as $c): ?>
                    <div class="comm">
                        <strong><?= e($c['author']) ?>:</strong> 
                        <?= nl2br(e($c['text'])) ?>
                        <span class="comm-date"><?= date('d.m.Y H:i', $c['stamp']) ?></span>
                    </div>
                <?php endforeach; ?>
                
                <form method="POST" class="comm-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                    <input type="text" name="author" placeholder="Ім'я" required maxlength="50"><br>
                    <textarea name="text" placeholder="Ваш коментар" required maxlength="1000" rows="4"></textarea><br>
                    <button type="submit" name="add_comment" class="btn">Додати коментар</button>
                </form>
            </section>
        <?php endif; ?>
        
        <?php if (!$is_single): ?>
            <footer class="blog-footer">
                <a href="/rss" class="rss-link">RSS стрічка</a>
            </footer>
        <?php endif; ?>
    </div>
    
    <button id="dark-btn" class="theme-btn">🌓</button>

    <script>
        // Dark Mode
        if(localStorage.getItem('theme')==='dark') document.body.classList.add('dark');
        document.getElementById('dark-btn').onclick = () => {
            document.body.classList.toggle('dark');
            localStorage.setItem('theme', document.body.classList.contains('dark')?'dark':'light');
        };

        // Підсвітка синтаксису коду
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('pre code').forEach((block) => {
                hljs.highlightBlock(block);
            });
        });

        // Drag&Drop Upload
        const tx = document.querySelector('.ed-text');
        if(tx) {
            tx.ondragover = (e) => e.preventDefault();
            tx.ondrop = (e) => {
                e.preventDefault();
                for (let f of e.dataTransfer.files) {
                    let fd = new FormData(); 
                    fd.append('img', f);
                    fetch('/system/upload.php', {method:'POST', body:fd})
                    .then(r=>r.json())
                    .then(d => {
                        if (d.url) {
                            tx.value += "\n" + d.url + "\n";
                        }
                    })
                    .catch(err => alert('Помилка завантаження: ' + err));
                }
            };
        }
    </script>
</body>
</html>
