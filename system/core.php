<?php
session_start();

// БЕЗПЕКА: Пароль зберігається в окремому файлі
$admin_pass_file = __DIR__ . '/.admin_pass';
if (!file_exists($admin_pass_file)) {
    // Дефолтний пароль: admin (ЗМІНІТЬ!)
    file_put_contents($admin_pass_file, password_hash('admin', PASSWORD_DEFAULT));
}
$admin_pass = trim(file_get_contents($admin_pass_file));

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
    
    $db->exec("CREATE TABLE IF NOT EXISTS settings (
        key TEXT PRIMARY KEY,
        value TEXT
    )");
    
    // Дефолтні налаштування
    $defaults = [
        'site_name' => 'Мій Блог',
        'site_description' => 'Особистий блог',
        'logo_path' => '',
        'favicon_path' => '',
        'accent_color' => '#0066cc',
        'google_analytics' => ''
    ];
    
    foreach ($defaults as $key => $value) {
        $check = $db->query("SELECT value FROM settings WHERE key = " . $db->quote($key))->fetch();
        if (!$check) {
            $db->exec("INSERT INTO settings (key, value) VALUES (" . $db->quote($key) . ", " . $db->quote($value) . ")");
        }
    }
    
} catch (PDOException $e) {
    die("DB Error: " . htmlspecialchars($e->getMessage()));
}

/**
 * MARKDOWN PARSER
 * Перетворює Markdown в HTML
 */
function smart_typography($text) {
    if (empty($text)) {
        return '';
    }
    
    // Зберігаємо оригінальний текст для обробки
    $text = trim($text);
    
    // 1. БЛОКИ КОДУ (``` код ```)
    $text = preg_replace_callback('/```(\w+)?\n(.*?)\n```/s', function($matches) {
        $lang = $matches[1] ?? '';
        $code = htmlspecialchars($matches[2], ENT_NOQUOTES);
        return "\n<pre><code class=\"language-" . htmlspecialchars($lang) . "\">" . $code . "</code></pre>\n";
    }, $text);
    
    // 2. INLINE КОД (`код`)
    $text = preg_replace_callback('/`([^`]+)`/', function($matches) {
        return '<code>' . htmlspecialchars($matches[1]) . '</code>';
    }, $text);
    
    // 3. ЗОБРАЖЕННЯ ДЛЯ ГАЛЕРЕЇ (окремі рядки з /uploads/)
    $lines = explode("\n", $text);
    $processed_lines = [];
    $gallery_images = [];
    
    foreach ($lines as $line) {
        $trimmed = trim($line);
        
        // Пропускаємо вже оброблені HTML теги
        if (preg_match('/^<(pre|code)/', $trimmed)) {
            if (!empty($gallery_images)) {
                $processed_lines[] = render_fotorama($gallery_images);
                $gallery_images = [];
            }
            $processed_lines[] = $line;
            continue;
        }
        
        // Зображення для галереї
        if (preg_match('/^\/uploads\/.*\.(jpg|jpeg|png|gif|webp)$/i', $trimmed)) {
            $gallery_images[] = $trimmed;
        } else {
            // Виводимо накопичену галерею
            if (!empty($gallery_images)) {
                $processed_lines[] = render_fotorama($gallery_images);
                $gallery_images = [];
            }
            $processed_lines[] = $line;
        }
    }
    
    // Остання галерея
    if (!empty($gallery_images)) {
        $processed_lines[] = render_fotorama($gallery_images);
    }
    
    $text = implode("\n", $processed_lines);
    
    // 4. ЗАГОЛОВКИ (# ## ###)
    $text = preg_replace('/^### (.+)$/m', '<h3>$1</h3>', $text);
    $text = preg_replace('/^## (.+)$/m', '<h2>$1</h2>', $text);
    $text = preg_replace('/^# (.+)$/m', '<h1>$1</h1>', $text);
    
    // 5. ГОРИЗОНТАЛЬНА ЛІНІЯ (---)
    $text = preg_replace('/^---$/m', '<hr>', $text);
    
    // 6. ЦИТАТИ (> текст)
    $text = preg_replace_callback('/((?:^> .+$\n?)+)/m', function($matches) {
        $lines = explode("\n", trim($matches[1]));
        $content = '';
        foreach ($lines as $line) {
            $content .= preg_replace('/^> (.+)$/', '$1<br>', $line);
        }
        return '<blockquote>' . rtrim($content, '<br>') . '</blockquote>';
    }, $text);
    
    // 7. СПИСКИ (МАРКОВАНИЙ: - * +)
    $text = preg_replace_callback('/((?:^[\*\-\+] .+$\n?)+)/m', function($matches) {
        $items = preg_replace('/^[\*\-\+] (.+)$/m', '<li>$1</li>', trim($matches[1]));
        return '<ul>' . $items . '</ul>';
    }, $text);
    
    // 8. СПИСКИ (НУМЕРОВАНИЙ: 1. 2. 3.)
    $text = preg_replace_callback('/((?:^\d+\. .+$\n?)+)/m', function($matches) {
        $items = preg_replace('/^\d+\. (.+)$/m', '<li>$1</li>', trim($matches[1]));
        return '<ol>' . $items . '</ol>';
    }, $text);
    
    // 9. ЗОБРАЖЕННЯ ![alt](url)
    $text = preg_replace('/!\[([^\]]*)\]\(([^\)]+)\)/', '<img src="$2" alt="$1">', $text);
    
    // 10. ПОСИЛАННЯ [текст](url)
    $text = preg_replace('/\[([^\]]+)\]\(([^\)]+)\)/', '<a href="$2">$1</a>', $text);
    
    // 11. ЖИРНИЙ ТЕКСТ (**текст** або __текст__)
    $text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);
    $text = preg_replace('/__(.+?)__/', '<strong>$1</strong>', $text);
    
    // 12. КУРСИВ (*текст* або _текст_)
    // Але не чіпаємо * в списках та __ в посиланнях
    $text = preg_replace('/(?<!\*)\*(?!\*)(.+?)(?<!\*)\*(?!\*)/', '<em>$1</em>', $text);
    $text = preg_replace('/(?<!_)_(?!_)(.+?)(?<!_)_(?!_)/', '<em>$1</em>', $text);
    
    // 13. ПАРАГРАФИ (подвійний enter = новий параграф)
    $lines = explode("\n", $text);
    $output = '';
    $paragraph = '';
    
    foreach ($lines as $line) {
        $trimmed = trim($line);
        
        // Порожній рядок = кінець параграфу
        if ($trimmed === '') {
            if ($paragraph !== '') {
                // Перевіряємо чи це не HTML тег
                if (!preg_match('/^<(h[1-6]|ul|ol|blockquote|pre|hr|img|div)/', trim($paragraph))) {
                    $output .= '<p>' . trim($paragraph) . '</p>' . "\n";
                } else {
                    $output .= trim($paragraph) . "\n";
                }
                $paragraph = '';
            }
            continue;
        }
        
        // HTML теги виводимо відразу
        if (preg_match('/^<(h[1-6]|ul|ol|blockquote|pre|hr|img|div)/', $trimmed)) {
            if ($paragraph !== '') {
                if (!preg_match('/^<(h[1-6]|ul|ol|blockquote|pre|hr|img|div)/', trim($paragraph))) {
                    $output .= '<p>' . trim($paragraph) . '</p>' . "\n";
                } else {
                    $output .= trim($paragraph) . "\n";
                }
                $paragraph = '';
            }
            $output .= $line . "\n";
        } else {
            // Додаємо до параграфу
            $paragraph .= ($paragraph !== '' ? ' ' : '') . $trimmed;
        }
    }
    
    // Останній параграф
    if ($paragraph !== '') {
        if (!preg_match('/^<(h[1-6]|ul|ol|blockquote|pre|hr|img|div)/', trim($paragraph))) {
            $output .= '<p>' . trim($paragraph) . '</p>';
        } else {
            $output .= trim($paragraph);
        }
    }
    
    return $output;
}

/**
 * Рендер Fotorama галереї
 */
function render_fotorama($imgs) {
    $html = '<div class="fotorama" data-nav="thumbs" data-width="100%" data-ratio="16/9" data-allowfullscreen="true">';
    foreach ($imgs as $img) {
        $html .= '<img src="' . htmlspecialchars($img) . '" alt="">';
    }
    return $html . '</div>';
}

/**
 * ФУНКЦІЇ ДЛЯ ТЕГІВ
 */
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

/**
 * ГЕНЕРАЦІЯ RSS
 */
function generate_rss() {
    global $db;
    $site_name = get_setting('site_name', 'Мій Блог');
    $site_description = get_setting('site_description', 'Особистий блог');
    $posts = $db->query("SELECT * FROM notes WHERE is_draft = 0 ORDER BY stamp DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
    
    $rss = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $rss .= '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">' . "\n";
    $rss .= '<channel>' . "\n";
    $rss .= '<title>' . htmlspecialchars($site_name) . '</title>' . "\n";
    $rss .= '<link>https://' . $_SERVER['HTTP_HOST'] . '/</link>' . "\n";
    $rss .= '<description>' . htmlspecialchars($site_description) . '</description>' . "\n";
    $rss .= '<language>uk</language>' . "\n";
    
    foreach ($posts as $p) {
        $rss .= '<item>' . "\n";
        $rss .= '<title>' . htmlspecialchars($p['title']) . '</title>' . "\n";
        $rss .= '<link>https://' . $_SERVER['HTTP_HOST'] . '/' . htmlspecialchars($p['url_name']) . '</link>' . "\n";
        $rss .= '<description>' . htmlspecialchars($p['snippet'] ?? substr(strip_tags($p['text']), 0, 200)) . '</description>' . "\n";
        $rss .= '<pubDate>' . date('r', $p['stamp']) . '</pubDate>' . "\n";
        $rss .= '<guid>https://' . $_SERVER['HTTP_HOST'] . '/' . htmlspecialchars($p['url_name']) . '</guid>' . "\n";
        $rss .= '</item>' . "\n";
    }
    
    $rss .= '</channel>' . "\n";
    $rss .= '</rss>';
    
    return $rss;
}

/**
 * ESCAPE ФУНКЦІЯ ДЛЯ БЕЗПЕКИ
 */
function e($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * ФУНКЦІЇ НАЛАШТУВАНЬ
 */
function get_setting($key, $default = '') {
    global $db;
    $st = $db->prepare("SELECT value FROM settings WHERE key = ?");
    $st->execute([$key]);
    $result = $st->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['value'] : $default;
}

function set_setting($key, $value) {
    global $db;
    $check = $db->prepare("SELECT value FROM settings WHERE key = ?");
    $check->execute([$key]);
    
    if ($check->fetch()) {
        $db->prepare("UPDATE settings SET value = ? WHERE key = ?")->execute([$value, $key]);
    } else {
        $db->prepare("INSERT INTO settings (key, value) VALUES (?, ?)")->execute([$key, $value]);
    }
}

function get_all_settings() {
    global $db;
    $settings = [];
    $result = $db->query("SELECT key, value FROM settings")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($result as $row) {
        $settings[$row['key']] = $row['value'];
    }
    return $settings;
}
?>