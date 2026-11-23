<?php
$basePath = appBasePath();
$csrf = $_SESSION['csrf'] ?? '';
$currentUser = $_SESSION['user'] ?? null;
$flash = $_SESSION['flash'] ?? null;
$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash'], $_SESSION['flash_error']);
$pageTitle = $title ?? 'Time Healers Digital Hub';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= $this->escape($pageTitle) ?> | Time Healers Digital Hub</title>
  <link rel="icon" href="<?= $basePath ?>/assets/images/favicon-32.png">
  <link rel="stylesheet" href="<?= $basePath ?>/assets/css/style.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/lucide@latest" defer></script>
</head>
<body>
  <a class="skip-link" href="#main">Skip to content</a>
  <div class="announcement">Your time. Our expertise. A better digital tomorrow.</div>
  <header class="site-header"><div class="container nav-wrap">
    <a class="brand" href="<?= $basePath ?>/"><img src="<?= $basePath ?>/assets/images/logo.webp" alt="Time Healers Digital Hub"></a>
    <button class="nav-toggle" type="button" aria-expanded="false" aria-label="Open navigation"><span></span><span></span><span></span></button>
    <nav class="main-nav" aria-label="Main navigation"><a href="<?= $basePath ?>/">Home</a><a href="<?= $basePath ?>/services">Services</a><a href="<?= $basePath ?>/products">Digital Products</a><a href="<?= $basePath ?>/blog">Knowledge Hub</a><a href="<?= $basePath ?>/about">About</a>
      <?php if ($currentUser): ?><a href="<?= $basePath ?><?= in_array($currentUser['role'] ?? '', ['super_admin', 'administrator'], true) ? '/admin' : '/account' ?>"><?= in_array($currentUser['role'] ?? '', ['super_admin', 'administrator'], true) ? 'Admin dashboard' : 'My account' ?></a><form class="nav-logout-form" method="post" action="<?= $basePath ?>/logout"><input type="hidden" name="csrf" value="<?= $csrf ?>"><button class="nav-logout" type="submit">Sign out</button></form>
      <?php else: ?><a href="<?= $basePath ?>/login">Sign in</a><a href="<?= $basePath ?>/register">Create account</a><?php endif ?>
    </nav>
    <a class="button button-small" href="<?= $basePath ?>/contact">Start a project</a>
  </div></header>
  <?php if ($flash): ?><div class="notice" role="status"><?= $this->escape($flash) ?></div><?php endif ?>
  <?php if ($flashError): ?><div class="notice notice-error" role="alert"><?= $this->escape($flashError) ?></div><?php endif ?>
  <main id="main">
    <?= $pageContent ?? '' ?>
  </main>
  <footer class="site-footer"><div class="container footer-grid">
    <div class="footer-brand"><img src="<?= $basePath ?>/assets/images/mark.webp" alt=""><p>Digital products, creative solutions and technology that move you forward.</p></div>
    <div><h2>Explore</h2><a href="<?= $basePath ?>/services">Services</a><a href="<?= $basePath ?>/products">Products</a><a href="<?= $basePath ?>/blog">Knowledge Hub</a></div>
    <div><h2>Company</h2><a href="<?= $basePath ?>/about">About us</a><a href="<?= $basePath ?>/contact">Contact</a><a href="<?= $basePath ?>/faq">FAQ</a></div>
    <div><h2>Stay informed</h2><form class="newsletter" method="post" action="<?= $basePath ?>/newsletter"><input type="hidden" name="csrf" value="<?= $csrf ?>"><label class="sr-only" for="newsletter-email">Email</label><input id="newsletter-email" type="email" name="email" placeholder="Email address" required><button type="submit"><i data-lucide="arrow-right"></i></button></form></div>
  </div><div class="container footer-bottom"><span>&copy; <?= date('Y') ?> Time Healers Digital Hub</span><span>Ideas Today · Impact Tomorrow</span></div></footer>
  <script type="module" src="<?= $basePath ?>/assets/js/app.js"></script>
</body>
</html>
