<?php
require_once 'system/core.php';

header('Content-Type: application/xml; charset=utf-8');

$site_url = 'https://' . $_SERVER['HTTP_HOST'];
$posts = $db->query("SELECT * FROM notes WHERE is_draft=0 ORDER BY stamp DESC LIMIT 20")->fetchAll();

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
    <channel>
        <title>Мій Блог</title>
        <link><?= $site_url ?></link>
        <description>Особистий блог</description>
        <language>uk</language>
        <atom:link href="<?= $site_url ?>/rss.php" rel="self" type="application/rss+xml" />
        
        <?php foreach($posts as $p): ?>
        <item>
            <title><?= htmlspecialchars($p['title']) ?></title>
            <link><?= $site_url ?>/<?= $p['url_name'] ?></link>
            <guid><?= $site_url ?>/<?= $p['url_name'] ?></guid>
            <pubDate><?= date('r', $p['stamp']) ?></pubDate>
            <?php if($p['snippet']): ?>
            <description><?= htmlspecialchars($p['snippet']) ?></description>
            <?php endif; ?>
        </item>
        <?php endforeach; ?>
    </channel>
</rss>
