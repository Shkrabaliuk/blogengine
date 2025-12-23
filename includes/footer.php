<?php
/**
 * Footer Template
 * Футер сайту - копірайт, іконки, скрипти
 */
?>

</main>

<footer class="footer">
  <div class="footer-content">
    <div class="footer-left">
      © <?= e(get_setting('site_name', 'Мій Блог')) ?>, 2018–<?= date('Y') ?>
    </div>
    
    <div class="footer-right">
      <?php if (!IS_ADMIN): ?>
        <button class="footer-icon" id="loginBtnFooter" title="Вхід">
          <i class="fa-solid fa-lock"></i>
        </button>
      <?php endif; ?>
      
      <?php if (IS_ADMIN): ?>
        <a href="/admin.php" class="footer-icon" title="Налаштування">
          <i class="fa-solid fa-gear"></i>
        </a>
      <?php endif; ?>
      
      <a href="/rss" class="footer-icon" title="RSS" target="_blank">
        <i class="fa-solid fa-rss"></i>
      </a>
      
      <button class="footer-icon" id="darkBtn" title="Темна тема">
        <i class="fa-solid fa-moon"></i>
      </button>
    </div>
  </div>
</footer>

</div>

</div>

<script>
// Темна тема
if(localStorage.getItem('theme')==='dark') document.body.classList.add('dark');
const darkBtn = document.getElementById('darkBtn');
if (darkBtn) {
  darkBtn.onclick = () => {
    document.body.classList.toggle('dark');
    localStorage.setItem('theme', document.body.classList.contains('dark')?'dark':'light');
  };
}

// Highlight.js для коду
document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('pre code').forEach((block) => { hljs.highlightBlock(block); });
});

// Пошук
const searchToggle = document.getElementById('searchToggle');
const searchForm = document.getElementById('searchForm');
const searchInput = document.getElementById('searchInput');
const searchClose = document.getElementById('searchClose');

if (searchToggle && searchForm) {
  searchToggle.onclick = () => {
    searchForm.classList.add('active');
    searchInput.focus();
  };
  
  if (searchClose) {
    searchClose.onclick = () => {
      searchForm.classList.remove('active');
      searchInput.value = '';
    };
  }
  
  document.addEventListener('click', (e) => {
    if (!searchForm.contains(e.target) && !searchToggle.contains(e.target)) {
      searchForm.classList.remove('active');
    }
  });
}

// Drag & Drop для завантаження зображень
const tx = document.querySelector('.input-textarea');
if(tx) {
  tx.ondragover = (e) => e.preventDefault();
  tx.ondrop = (e) => {
    e.preventDefault();
    for (let f of e.dataTransfer.files) {
      let fd = new FormData(); 
      fd.append('img', f);
      fetch('/system/upload.php', {method:'POST', body:fd})
      .then(r=>r.json())
      .then(d => { if (d.url) tx.value += "\n" + d.url + "\n"; });
    }
  };
}

<?php if (!IS_ADMIN): ?>
// Модалка логіну
const modal = document.getElementById('loginModal');
const loginBtnFooter = document.getElementById('loginBtnFooter');
const span = document.getElementsByClassName('modal-close')[0];

if (loginBtnFooter) {
  loginBtnFooter.onclick = () => { 
    modal.style.display = 'flex'; 
    document.getElementById('loginPassword').focus(); 
  }
}

if (span) {
  span.onclick = () => modal.style.display = 'none';
}

window.onclick = (e) => { if (e.target == modal) modal.style.display = 'none'; }

document.getElementById('loginForm').onsubmit = function(e) {
  e.preventDefault();
  const password = document.getElementById('loginPassword').value;
  const errorDiv = document.getElementById('loginError');
  
  fetch('/', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'ajax_login=1&password=' + encodeURIComponent(password)
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      window.location.href = '/admin.php';
    } else {
      errorDiv.textContent = data.error;
      errorDiv.style.display = 'block';
    }
  });
};
<?php endif; ?>
</script>
</body>
</html>
