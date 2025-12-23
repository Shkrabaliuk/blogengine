<?php
/**
 * Editor Form Template
 * Форма редагування/створення поста
 * Використання: include 'templates/editor-form.php';
 */

// Для редагування передається $p (пост)
// Для створення $p = null
$is_edit = isset($p) && $p;
?>
<form method="POST" class="editor">
<?= csrf_field() ?>

<div class="form-group">
  <label>Заголовок</label>
  <input type="text" name="title" value="<?= $is_edit ? e($p['title']) : '' ?>" placeholder="Заголовок статті" class="input-text" required <?= !$is_edit ? 'autofocus' : '' ?>>
</div>

<div class="form-group">
  <label>Текст (Markdown) 
    <button type="button" class="help-toggle" id="markdownHelpToggle">
      <i class="fa-solid fa-circle-question"></i> Підказка
    </button>
  </label>
  <textarea name="text" placeholder="Текст статті..." class="input-textarea" rows="20"><?= $is_edit ? e($p['text']) : '' ?></textarea>
  
  <!-- Підказка Markdown -->
  <div class="markdown-help" id="markdownHelp">
    <div class="markdown-help-content">
      <div class="help-section">
        <h4>Заголовки</h4>
        <code># Заголовок 1</code><br>
        <code>## Заголовок 2</code><br>
        <code>### Заголовок 3</code>
      </div>
      
      <div class="help-section">
        <h4>Форматування</h4>
        <code>**жирний текст**</code><br>
        <code>*курсив*</code><br>
        <code>***жирний курсив***</code>
      </div>
      
      <div class="help-section">
        <h4>Списки</h4>
        <code>- Пункт списку</code><br>
        <code>1. Нумерований</code>
      </div>
      
      <div class="help-section">
        <h4>Посилання і зображення</h4>
        <code>[текст](https://site.com)</code><br>
        <code>![опис](image.jpg)</code>
      </div>
      
      <div class="help-section">
        <h4>Цитата і код</h4>
        <code>&gt; Цитата</code><br>
        <code>`код в рядку`</code>
      </div>
      
      <div class="help-section">
        <h4>Параграфи</h4>
        <small>Подвійний Enter = новий параграф</small>
      </div>
    </div>
  </div>
</div>

<div class="form-group">
  <label>Короткий опис <small>(для соцмереж)</small></label>
  <input type="text" name="snippet" value="<?= $is_edit ? e($p['snippet']) : '' ?>" placeholder="Короткий опис" class="input-text">
</div>

<div class="form-group">
  <label>Теги <small>(через кому)</small></label>
  <input type="text" name="tags" value="<?= $is_edit ? e(implode(', ', array_column(get_post_tags($p['id']), 'name'))) : '' ?>" placeholder="дизайн, код, подорожі" class="input-text">
</div>

<div class="form-group">
  <label class="checkbox-label">
    <input type="checkbox" name="is_draft" value="1" <?= $is_edit && $p['is_draft'] ? 'checked' : '' ?>> 
    Зберегти як чернетку
  </label>
</div>

<div class="form-buttons">
  <button type="submit" name="save" class="btn btn-primary"><?= $is_edit ? 'Зберегти' : 'Опублікувати' ?></button>
  <?php if ($is_edit): ?>
    <button type="submit" name="delete" class="btn btn-danger" onclick="return confirm('Видалити назавжди?')">Видалити</button>
  <?php endif; ?>
  <a href="<?= $is_edit ? '/' . e($p['url_name']) : '/' ?>" class="btn btn-secondary">Скасувати</a>
</div>

</form>
