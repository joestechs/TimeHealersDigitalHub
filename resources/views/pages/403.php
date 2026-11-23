<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width">
  <title>Request Not Verified</title>
  <link rel="stylesheet" href="<?= htmlspecialchars(appUrl('/assets/css/style.css'), ENT_QUOTES, 'UTF-8') ?>">
</head>
<body>
  <main class="container" style="padding:100px 0">
    <h1>Request could not be verified</h1>
    <p>Reload the page and try again. Your request was not processed.</p>
    <a class="button" href="<?= htmlspecialchars(appUrl('/'), ENT_QUOTES, 'UTF-8') ?>">Return home</a>
  </main>
</body>
</html>
