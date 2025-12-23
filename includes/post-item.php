<?php
/**
 * Post Item Template
 * Відображення одного поста
 * Використання: include 'includes/post-item.php'; (змінна $p повинна бути доступна)
 */

if (!isset($p)) {
    return;
}
?>
<article class="post">

<div class="post-meta">
  <span class="post-date"><?= date('d.m.Y', $p['stamp']) ?></span>
  <?php if ($p['is_draft']): ?>
    <span class="draft-badge">Чернетка</span>
  <?php endif; ?>
  <?php if(IS_ADMIN): ?>
    <a href="/<?= e($p['url_name']) ?>?edit" class="post-edit">Редагувати</a>
  <?php endif; ?>
</div>

<?php if(IS_ADMIN && isset($_GET['edit']) && $is_single): ?>
  <?php include __DIR__ . '/../templates/editor-form.php'; ?>
<?php else: ?>

<h2 class="post-title">
  <?php if (!$is_single): ?>
    <a href="/<?= e($p['url_name']) ?>"><?= e($p['title']) ?></a>
  <?php else: ?>
    <?= e($p['title']) ?>
  <?php endif; ?>
</h2>

<div class="post-content">
<?= smart_typography($p['text']) ?>
</div>

<?php 
$post_tags = get_post_tags($p['id']);
if (!empty($post_tags)): 
?>
<div class="post-tags">
<?php foreach($post_tags as $t): ?>
<a href="/tag/<?= e($t['url_name']) ?>" class="tag"><?= e($t['name']) ?></a>
<?php endforeach; ?>
</div>
<?php endif; ?>

<?php endif; ?>

</article>
