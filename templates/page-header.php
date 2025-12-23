<?php
/**
 * Page Header Template
 * Заголовки для спеціальних сторінок (тег, пошук)
 * Використання: include 'templates/page-header.php';
 */

if (isset($tag_filter) && $tag_filter): ?>
<div class="page-header">
  <h2>Тег: <?= e($tag_filter['name']) ?></h2>
</div>
<?php endif; ?>

<?php if (isset($search_query) && $search_query): ?>
<div class="page-header">
  <h2>Пошук: "<?= e($search_query) ?>"</h2>
  <p>Знайдено: <?= count($posts ?? []) ?></p>
</div>
<?php endif; ?>

<?php if (empty($posts ?? [])): ?>
<p class="no-results">Нічого не знайдено</p>
<?php endif; ?>
