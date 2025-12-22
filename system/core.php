<?php
session_start();

// 🔐 БЕЗПЕКА: Змініть цей пароль!
$admin_pass = password_hash('ваш_сильний_пароль_тут', PASSWORD_DEFAULT);
define('IS_ADMIN', isset($_SESSION['admin']));

// CSRF токен
if (IS_ADMIN && !isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function verify_csrf() {
    if (!IS_ADMIN) return true;
    return isset($_POST['csrf_token']) && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}

function csrf_field() {
    if (!IS_ADMIN) return '';
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($_SESSION['csrf_token']) . '">';
}

// База даних
try {
    $db = new PDO('sqlite:' . __DIR__ . '/../data/blog.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Таблиці
    $db->exec("CREATE TABLE IF NOT EXISTS notes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        text TEXT,
        url_name TEXT UNIQUE NOT NULL,
        stamp INTEGER NOT NULL,
        is_draft INTEGER DEFAULT 0,
        snippet TEXT
    )");
    
    $db->exec("CREATE TABLE IF NOT EXISTS comments (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        post_id INTEGER NOT NULL,
        author TEXT NOT NULL,
        text TEXT NOT NULL,
        stamp INTEGER NOT NULL,
        FOREIGN KEY (post_id) REFERENCES notes(id) ON DELETE CASCADE
    )");
    
    $db->exec("CREATE TABLE IF NOT EXISTS tags (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT UNIQUE NOT NULL,
        url_name TEXT UNIQUE NOT NULL
    )");
    
    $db->exec("CREATE TABLE IF NOT EXISTS post_tags (
        post_id INTEGER NOT NULL,
        tag_id INTEGER NOT NULL,
        PRIMARY KEY (post_id, tag_id),
        FOREIGN KEY (post_id) REFERENCES notes(id) ON DELETE CASCADE,
        FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
    )");
    
} catch (PDOException $e) {
    die("DB Error: " . htmlspecialchars($e->getMessage()));
}

// 🎨 ПОКРАЩЕНА ТИПОГРАФІКА
function smart_typography($text) {
    // Базові правила
    $rules = [
        // Лапки
        '/(^|\s|>)"([^"]+)"/' => '$1«$2»',
        '/«([^»]+)«([^»]+)»([^»]+)»/' => '«$1„$2"$3»', // Вкладені лапки
        
        // Тире та дефіси
        '/ -- /' => ' — ',
        '/(\d+)-(\d+)/' => '$1–$2', // Цифрове тире (діапазон)
        '/(\s)—(\s)/' => '$1—$2', // Довге тире з пробілами
        
        // Спецсимволи
        '/\(c\)/i' => '©',
        '/\(r\)/i' => '®',
        '/\(tm\)/i' => '™',
        '/\.{3}/' => '…',
        
        // Пробіли
        '/\s+/' => ' ', // Подвійні пробіли
        '/(\d)\s+(грн|₴|USD|EUR|км|м|см|кг|г)/' => '$1 $2', // Нерозривний пробіл
    ];
    
    $text = preg_replace(array_keys($rules), array_values($rules), $text);
    
    // Обробка параграфів та галерей
    $lines = explode("\n", $text);
    $res = [];
    $gal = [];
    $code_block = false;
    
    foreach ($lines as $l) {
        $l = trim($l);
        
        // Блоки коду
        if (preg_match('/^```(\w+)?/', $l, $m)) {
            if (!empty($gal)) {
                $res[] = render_fotorama($gal);
                $gal = [];
            }
            if (!$code_block) {
                $lang = $m[1] ?? '';
                $res[] = '<pre><code class="language-' . htmlspecialchars($lang) . '">';
                $code_block = true;
            } else {
                $res[] = '</code></pre>';
                $code_block = false;
            }
            continue;
        }
        
        if ($code_block) {
            $res[] = htmlspecialchars($l);
            continue;
        }
        
        // Зображення для галереї
        if (preg_match('/^\/uploads\/.*\.(jpg|jpeg|png|gif|webp)$/i', $l)) {
            $gal[] = $l;
        } else {
            if (!empty($gal)) {
                $res[] = render_fotorama($gal);
                $gal = [];
            }
            if ($l !== '') {
                $res[] = '<p>' . $l . '</p>';
            }
        }
    }
    
    if (!empty($gal)) {
        $res[] = render_fotorama($gal);
    }
    
    return implode("\n", $res);
}

function render_fotorama($imgs) {
    $h = '<div class="fotorama" data-nav="thumbs" data-width="100%" data-ratio="16/9" data-allowfullscreen="true">';
    foreach ($imgs as $i) {
        $h .= '<img src="' . htmlspecialchars($i) . '">';
    }
    return $h . '</div>';
}

// Функції для тегів
function get_all_tags() {
    global $db;
    return $db->query("SELECT * FROM tags ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
}

function get_post_tags($post_id) {
    global $db;
    $st = $db->prepare("SELECT t.* FROM tags t 
                        INNER JOIN post_tags pt ON t.id = pt.tag_id 
                        WHERE pt.post_id = ?");
    $st->execute([$post_id]);
    return $st->fetchAll(PDO::FETCH_ASSOC);
}

function save_post_tags($post_id, $tag_names) {
    global $db;
    
    // Видаляємо старі зв'язки
    $db->prepare("DELETE FROM post_tags WHERE post_id = ?")->execute([$post_id]);
    
    if (empty($tag_names)) return;
    
    $tags = array_map('trim', explode(',', $tag_names));
    foreach ($tags as $tag) {
        if (empty($tag)) continue;
        
        $url = strtolower(preg_replace('/[^A-Za-zА-Яа-яІіЇїЄєҐґ0-9-]+/u', '-', $tag));
        $url = trim($url, '-');
        
        // Створюємо тег якщо не існує
        try {
            $db->prepare("INSERT INTO tags (name, url_name) VALUES (?, ?)")->execute([$tag, $url]);
            $tag_id = $db->lastInsertId();
        } catch (PDOException $e) {
            // Тег вже існує
            $st = $db->prepare("SELECT id FROM tags WHERE url_name = ?");
            $st->execute([$url]);
            $tag_id = $st->fetchColumn();
        }
        
        // Зв'язуємо пост з тегом
        try {
            $db->prepare("INSERT INTO post_tags (post_id, tag_id) VALUES (?, ?)")->execute([$post_id, $tag_id]);
        } catch (PDOException $e) {
            // Зв'язок вже існує
        }
    }
}

// Генерація RSS
function generate_rss() {
    global $db;
    $posts = $db->query("SELECT * FROM notes WHERE is_draft = 0 ORDER BY stamp DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
    
    $rss = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $rss .= '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">' . "\n";
    $rss .= '<channel>' . "\n";
    $rss .= '<title>Мій Блог</title>' . "\n";
    $rss .= '<link>http://' . $_SERVER['HTTP_HOST'] . '/</link>' . "\n";
    $rss .= '<description>Особистий блог</description>' . "\n";
    $rss .= '<language>uk</language>' . "\n";
    
    foreach ($posts as $p) {
        $rss .= '<item>' . "\n";
        $rss .= '<title>' . htmlspecialchars($p['title']) . '</title>' . "\n";
        $rss .= '<link>http://' . $_SERVER['HTTP_HOST'] . '/' . htmlspecialchars($p['url_name']) . '</link>' . "\n";
        $rss .= '<description>' . htmlspecialchars($p['snippet'] ?? substr(strip_tags($p['text']), 0, 200)) . '</description>' . "\n";
        $rss .= '<pubDate>' . date('r', $p['stamp']) . '</pubDate>' . "\n";
        $rss .= '<guid>http://' . $_SERVER['HTTP_HOST'] . '/' . htmlspecialchars($p['url_name']) . '</guid>' . "\n";
        $rss .= '</item>' . "\n";
    }
    
    $rss .= '</channel>' . "\n";
    $rss .= '</rss>';
    
    return $rss;
}

// Escape функції для безпеки
function e($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}
?>
