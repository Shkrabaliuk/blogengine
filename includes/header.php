<?php
/**
 * Header Template
 * Шапка сайту - <head>, логотип, навігація, пошук
 */

// Змінні які можуть передаватись:
$page_title = $page_title ?? get_setting('site_name', 'Мій Блог');
$page_description = $page_description ?? get_setting('site_description', 'Особистий блог');
$is_home = $is_home ?? false;

$site_name = get_setting('site_name', 'Мій Блог');
$site_description = get_setting('site_description', 'Особистий блог');
$logo_path = get_setting('logo_path');
$favicon_path = get_setting('favicon_path');
$accent_color = get_setting('accent_color', '#0066cc');
$ga_code = get_setting('google_analytics');
?>
<!DOCTYPE html>
<html lang="uk">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($page_title) ?></title>
<meta name="description" content="<?= e($page_description) ?>">
<?php if ($favicon_path && file_exists($favicon_path)): ?>
<link rel="shortcut icon" href="/<?= e($favicon_path) ?>">
<?php endif; ?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/8.0.1/normalize.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="https://fonts.tildacdn.com/tildasans/tildasans.css">
<link rel="alternate" type="application/rss+xml" title="RSS" href="/rss">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
<link href="https://cdnjs.cloudflare.com/ajax/libs/fotorama/4.6.4/fotorama.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/fotorama/4.6.4/fotorama.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
<link rel="stylesheet" href="/style.css">
<style>:root { --accent: <?= e($accent_color) ?>; }</style>
<?php if ($ga_code): ?>
<script async src="https://www.googletagmanager.com/gtag/js?id=<?= e($ga_code) ?>"></script>
<script>window.dataLayer = window.dataLayer || []; function gtag(){dataLayer.push(arguments);} gtag('js', new Date()); gtag('config', '<?= e($ga_code) ?>');</script>
<?php endif; ?>
</head>
<body>

<?php if (!IS_ADMIN): ?>
<?php include __DIR__ . '/modal-login.php'; ?>
<?php endif; ?>

<div class="page-wrapper">

<?php if ($logo_path && file_exists($logo_path)): ?>
<div class="logo-sidebar">
  <?php if ($is_home): ?>
    <img src="/<?= e($logo_path) ?>" alt="<?= e($site_name) ?>" class="logo-image">
  <?php else: ?>
    <a href="/">
      <img src="/<?= e($logo_path) ?>" alt="<?= e($site_name) ?>" class="logo-image">
    </a>
  <?php endif; ?>
</div>
<?php endif; ?>

<div class="container">

<header class="header">
  <div class="header-main">
    <div class="header-text">
      <h1 class="site-title">
        <?php if ($is_home): ?>
          <?= e($site_name) ?>
        <?php else: ?>
          <a href="/"><?= e($site_name) ?></a>
        <?php endif; ?>
      </h1>
      
      <div class="site-description">
        <p>
          <small><?= e($site_description) ?></small>
        </p>
      </div>
    </div>
    
    <div class="header-search">
      <?php if (IS_ADMIN): ?>
        <a href="/new.php" class="new-post-btn" title="Створити пост">
          <i class="fa-solid fa-plus"></i>
        </a>
      <?php endif; ?>
      
      <button class="search-toggle" id="searchToggle" title="Пошук">
        <i class="fa-solid fa-magnifying-glass"></i>
      </button>
      
      <form class="search-form" id="searchForm" action="/" method="get">
        <input type="search" name="s" value="<?= e($search_query ?? '') ?>" placeholder="Пошук..." id="searchInput">
        <button type="button" class="search-close" id="searchClose">&times;</button>
      </form>
    </div>
  </div>
</header>

<main class="content">
