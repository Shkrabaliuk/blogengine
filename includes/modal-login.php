<?php
/**
 * Modal Login Template
 * Модальне вікно для входу
 */
?>
<div id="loginModal" class="modal">
<div class="modal-content">
<span class="modal-close">&times;</span>
<h2>🔐 Вхід</h2>
<form id="loginForm">
<input type="password" id="loginPassword" placeholder="Пароль" required autofocus>
<div id="loginError" class="login-error"></div>
<button type="submit" class="btn">Увійти</button>
</form>
</div>
</div>
