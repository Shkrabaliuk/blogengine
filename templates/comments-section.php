<?php
/**
 * Comments Section Template
 * Секція коментарів для окремого поста
 * Використання: include 'templates/comments-section.php';
 */

if (!isset($post) || !$post) {
    return;
}

$cms = $db->prepare("SELECT * FROM comments WHERE post_id=? ORDER BY stamp ASC"); 
$cms->execute([$post['id']]); 
$comments = $cms->fetchAll(PDO::FETCH_ASSOC);
?>

<section class="comments">
<h3>Коментарі (<?= count($comments) ?>)</h3>

<?php foreach($comments as $c): ?>
<div class="comment">
  <strong><?= e($c['author']) ?>:</strong> 
  <?= nl2br(e($c['text'])) ?>
  <span class="comment-date"><?= date('d.m.Y H:i', $c['stamp']) ?></span>
</div>
<?php endforeach; ?>

<form method="POST" class="comment-form">
<?= csrf_field() ?>
<input type="hidden" name="post_id" value="<?= $post['id'] ?>">
<input type="text" name="author" placeholder="Ім'я" required maxlength="50">
<textarea name="text" placeholder="Коментар" required maxlength="1000" rows="4"></textarea>
<button type="submit" name="add_comment" class="btn">Додати коментар</button>
</form>

</section>
