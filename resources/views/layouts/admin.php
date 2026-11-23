<?php
$basePath = appBasePath();
$flash = $_SESSION['flash'] ?? null;
$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash'], $_SESSION['flash_error']);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $this->escape($title) ?> | Time Healers Digital Hub</title>
  <link rel="icon" href="<?= $basePath ?>/assets/images/favicon-32.png">
  <link rel="stylesheet" href="<?= $basePath ?>/assets/css/style.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/lucide@latest" defer></script>
</head>
<body>
  <a class="skip-link" href="#admin-main">Skip to administration</a>
  <?php if ($flash): ?><div class="notice" role="status"><?= $this->escape($flash) ?></div><?php endif ?>
  <?php if ($flashError): ?><div class="notice notice-error" role="alert"><?= $this->escape($flashError) ?></div><?php endif ?>
  <main id="admin-main"><?= $pageContent ?></main>
  <script type="module" src="<?= $basePath ?>/assets/js/app.js"></script>
</body>
</html>
