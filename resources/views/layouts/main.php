<?php
$basePath = appBasePath();
$csrf = $_SESSION['csrf'] ?? '';
$currentUser = $_SESSION['user'] ?? null;
$flash = $_SESSION['flash'] ?? null;
$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash'], $_SESSION['flash_error']);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Time Healers Digital Hub | Ideas Today, Impact Tomorrow</title>
  <meta name="description" content="Digital products, web development, mobile apps, design, marketing, video editing and corporate training.">
  <link rel="canonical" href="https://thdhubs.com/">
  <meta property="og:title" content="Time Healers Digital Hub">
  <meta property="og:description" content="Your time. Our expertise. A better digital tomorrow.">
  <meta property="og:image" content="https://thdhubs.com/og-image.jpg">
  <link rel="icon" href="<?= $basePath ?>/assets/images/favicon-32.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= $basePath ?>/assets/css/style.css">
  <script src="https://unpkg.com/lucide@latest" defer></script>
</head>
<body>
  <a class="skip-link" href="#main">Skip to content</a>
  <div class="announcement">Your time. Our expertise. A better digital tomorrow.</div>
  <header class="site-header">
    <div class="container nav-wrap">
      <a class="brand" href="<?= $basePath ?>/">
        <img src="<?= $basePath ?>/assets/images/logo.webp" alt="Time Healers Digital Hub">
      </a>
      <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="main-nav" aria-label="Open navigation">
        <span></span><span></span><span></span>
      </button>
      <nav id="main-nav" class="main-nav" aria-label="Main navigation">
        <a href="<?= $basePath ?>/">Home</a>
        <a href="<?= $basePath ?>/services">Services</a>
        <a href="<?= $basePath ?>/products">Digital Products</a>
        <a href="<?= $basePath ?>/blog">Knowledge Hub</a>
        <a href="<?= $basePath ?>/about">About</a>
        <?php if ($currentUser): ?>
          <a href="<?= $basePath ?><?= in_array($currentUser['role'] ?? '', ['super_admin', 'administrator'], true) ? '/admin' : '/account' ?>"><?= in_array($currentUser['role'] ?? '', ['super_admin', 'administrator'], true) ? 'Admin dashboard' : 'My account' ?></a>
          <form class="nav-logout-form" method="post" action="<?= $basePath ?>/logout"><input type="hidden" name="csrf" value="<?= $csrf ?>"><button class="nav-logout" type="submit">Sign out</button></form>
        <?php else: ?>
          <a href="<?= $basePath ?>/login">Sign in</a>
          <a href="<?= $basePath ?>/register">Create account</a>
        <?php endif ?>
      </nav>
      <a class="button button-small" href="<?= $basePath ?>/contact">Start a project</a>
    </div>
  </header>

  <?php if ($flash): ?><div class="notice" role="status"><?= $this->escape($flash) ?></div><?php endif ?>
  <?php if ($flashError): ?><div class="notice notice-error" role="alert"><?= $this->escape($flashError) ?></div><?php endif ?>

  <main id="main">
    <section class="hero">
      <div class="hero-grid container">
        <div class="hero-copy">
          <span class="eyebrow">A digital studio for ambitious people</span>
          <h1>Your time.<br>Our expertise.<br><em>A better digital tomorrow.</em></h1>
          <p>We build websites and apps, grow brands online, shape visual identities, edit engaging video and train teams—so you can spend more time on what matters.</p>
          <div class="hero-actions">
            <a class="button" href="<?= $basePath ?>/contact">Start a project <i data-lucide="arrow-right"></i></a>
            <a class="button button-ghost" href="<?= $basePath ?>/products">Browse our products</a>
          </div>
          <div class="trust-row">
            <span><i data-lucide="badge-check"></i> Practical solutions</span>
            <span><i data-lucide="clock-3"></i> Clear timelines</span>
            <span><i data-lucide="shield-check"></i> Secure delivery</span>
          </div>
        </div>
        <div class="hero-visual">
          <div class="timepiece-card" data-local-clock>
            <div class="clock-bezel">
              <div class="clock-face" role="img" aria-label="Analog clock showing your local time">
                <span class="clock-number clock-number-12">XII</span><span class="clock-number clock-number-1">I</span>
                <span class="clock-number clock-number-2">II</span><span class="clock-number clock-number-3">III</span>
                <span class="clock-number clock-number-4">IV</span><span class="clock-number clock-number-5">V</span>
                <span class="clock-number clock-number-6">VI</span><span class="clock-number clock-number-7">VII</span>
                <span class="clock-number clock-number-8">VIII</span><span class="clock-number clock-number-9">IX</span>
                <span class="clock-number clock-number-10">X</span><span class="clock-number clock-number-11">XI</span>
                <span class="clock-hand clock-hand-hour" data-clock-hour></span>
                <span class="clock-hand clock-hand-minute" data-clock-minute></span>
                <span class="clock-hand clock-hand-second" data-clock-second></span>
                <span class="clock-pivot"></span>
              </div>
            </div>
            <div class="timepiece-reading">
              <span class="timepiece-label" data-local-zone>Your local time</span>
              <time class="timepiece-time" data-local-time>--:--:--</time>
              <time class="timepiece-date" data-local-date></time>
            </div>
            <p class="timepiece-motto">Make every hour meaningful.</p>
          </div>
        </div>
      </div>
    </section>

    <section class="section">
      <div class="container">
        <div class="section-heading">
          <div><span class="eyebrow">What we do</span><h2>One hub. Many ways forward.</h2></div>
          <p>From a first conversation to a considered digital solution, find the creative and technical expertise you need under one roof.</p>
        </div>
        <div class="category-grid">
          <?php
          $categories = [
            ['code','Web Development','Responsive websites and web platforms designed to explain your offer and support your goals.'],
            ['smartphone','App Development','Mobile and cross-platform apps shaped around real users and everyday workflows.'],
            ['megaphone','Digital Marketing','Useful content and considered campaigns that help the right audience find you.'],
            ['pen-tool','Graphic Design','Logos, brand kits and consistent visuals that give your business a clear identity.'],
            ['video','Video Editing','Promos, social clips and event highlights with thoughtful pacing, sound and motion.'],
            ['users','Corporate Training','Live and self-paced digital skills training tailored to your team.'],
            ['sparkles','Something more?','Tell us about the digital challenge you have in mind and we will discuss the right next step.'],
          ];
          foreach ($categories as $category):
          ?>
          <a class="category-card" href="<?= $basePath ?>/services">
            <span class="icon-box"><i data-lucide="<?= $category[0] ?>"></i></span>
            <h3><?= $this->escape($category[1]) ?></h3>
            <p><?= $this->escape($category[2]) ?></p>
            <span class="card-link">Learn more <i data-lucide="arrow-up-right"></i></span>
          </a>
          <?php endforeach ?>
        </div>
      </div>
    </section>

    <section class="section section-tint">
      <div class="container">
        <div class="section-heading">
          <div><span class="eyebrow">Digital marketplace</span><h2>Featured products</h2></div>
          <a class="text-link" href="<?= $basePath ?>/products">View all products <i data-lucide="arrow-right"></i></a>
        </div>
        <?php if (!$featured): ?>
          <div class="empty-state">
            <i data-lucide="package-open"></i>
            <h3>Fresh resources are on the way</h3>
            <p>Administrators can publish downloadable products and vendor listings from the dashboard.</p>
          </div>
        <?php else: ?>
          <div class="product-grid">
            <?php foreach ($featured as $product): ?>
              <article class="product-card">
                <?php $productImage = $this->imageUrl($product['thumbnail'] ?? ''); ?>
                <div class="product-image"><?php if ($productImage !== ''): ?><img src="<?= $this->escape($productImage) ?>" alt=""><?php else: ?><span><?= $this->escape(substr($product['name'], 0, 1)) ?></span><?php endif ?></div>
                <div class="product-body">
                  <span class="product-category"><?= $this->escape($product['category_name'] ?: 'Digital Product') ?></span>
                  <h3><?= $this->escape($product['name']) ?></h3>
                  <p><?= $this->escape($product['short_description'] ?: '') ?></p>
                  <div class="product-footer"><strong><?= $this->escape($product['currency']) ?> <?= number_format((float)$product['price'], 2) ?></strong><a href="<?= $this->escape($this->productUrl($product)) ?>">Details</a></div>
                </div>
              </article>
            <?php endforeach ?>
          </div>
        <?php endif ?>
      </div>
    </section>

    <section class="section studio-section">
      <div class="container studio-grid">
        <div class="studio-mark"><img src="<?= $basePath ?>/assets/images/mark.webp" alt="Time Healers Digital Hub emblem"></div>
        <div class="studio-copy">
          <span class="eyebrow">Ideas today, impact tomorrow</span>
          <h2>Thoughtful work that gives your time back.</h2>
          <p>We take on the technical and creative work that can slow a business down, then shape it into something useful, considered and ready to grow with you.</p>
          <div class="studio-points">
            <article><span>01</span><div><h3>Start with your goals</h3><p>We listen, clarify what matters and agree on a practical direction.</p></div></article>
            <article><span>02</span><div><h3>Make the work visible</h3><p>Clear scope and regular communication keep the next steps understandable.</p></div></article>
            <article><span>03</span><div><h3>Build for what comes next</h3><p>We aim for useful solutions that can adapt as your needs change.</p></div></article>
          </div>
          <a class="text-link" href="<?= $basePath ?>/about">Get to know us <i data-lucide="arrow-right"></i></a>
        </div>
      </div>
    </section>

    <section class="section promise">
      <div class="container promise-grid">
        <div>
          <span class="eyebrow">Our promise</span>
          <h2>Respect for your time.<br>Confidence in the result.</h2>
        </div>
        <div class="promise-items">
          <article><span>01</span><h3>Your time</h3><p>Clear timelines, regular updates and no surprises.</p></article>
          <article><span>02</span><h3>Our expertise</h3><p>Development, design, marketing, video and training in one connected team.</p></article>
          <article><span>03</span><h3>A better tomorrow</h3><p>Solutions built to grow with you, not to be replaced next year.</p></article>
        </div>
      </div>
    </section>

    <section class="section cta">
      <div class="container cta-card">
        <div><span class="eyebrow">Have a project in mind?</span><h2>Let’s turn the idea into impact.</h2></div>
        <a class="button button-light" href="<?= $basePath ?>/contact">Tell us what you need <i data-lucide="arrow-right"></i></a>
      </div>
    </section>
  </main>

  <footer class="site-footer">
    <div class="container footer-grid">
      <div class="footer-brand">
        <img src="<?= $basePath ?>/assets/images/mark.webp" alt="">
        <p>Digital products, creative solutions and technology that move you forward.</p>
      </div>
      <div><h2>Explore</h2><a href="<?= $basePath ?>/services">Services</a><a href="<?= $basePath ?>/products">Products</a><a href="<?= $basePath ?>/blog">Knowledge Hub</a></div>
      <div><h2>Company</h2><a href="<?= $basePath ?>/about">About us</a><a href="<?= $basePath ?>/contact">Contact</a><a href="<?= $basePath ?>/faq">FAQ</a></div>
      <div>
        <h2>Stay informed</h2>
        <form class="newsletter" method="post" action="<?= $basePath ?>/newsletter">
          <input type="hidden" name="csrf" value="<?= $csrf ?>">
          <label class="sr-only" for="newsletter-email">Email address</label>
          <input id="newsletter-email" type="email" name="email" placeholder="Email address" required>
          <button type="submit" aria-label="Subscribe"><i data-lucide="arrow-right"></i></button>
        </form>
      </div>
    </div>
    <div class="container footer-bottom"><span>&copy; <?= date('Y') ?> Time Healers Digital Hub</span><span>Ideas Today · Impact Tomorrow</span></div>
  </footer>
  <?php if (!empty($chatbotSettings['is_enabled'])): ?>
    <a class="support-button" href="<?= $basePath ?>/contact" data-chatbot-toggle aria-controls="chatbot-panel" aria-expanded="false" aria-label="Open customer support chat"><i data-lucide="message-circle"></i></a>
    <section class="chatbot-widget" id="chatbot-panel" data-chatbot-panel role="dialog" aria-modal="false" aria-labelledby="chatbot-title" hidden
      data-chatbot-endpoint="<?= $this->escape($basePath . '/chatbot/message') ?>"
      data-external-processing="<?= !empty($chatbotExternalProcessing) ? '1' : '0' ?>">
      <header class="chatbot-header">
        <div><span class="chatbot-status"></span><h2 id="chatbot-title"><?= $this->escape($chatbotSettings['bot_name']) ?></h2><p>Customer support</p></div>
        <button class="chatbot-close" type="button" data-chatbot-close aria-label="Close chat"><i data-lucide="x"></i></button>
      </header>
      <div class="chatbot-messages" data-chatbot-messages aria-live="polite" aria-relevant="additions text">
        <article class="chatbot-message chatbot-message-assistant"><p><?= $this->escape($chatbotSettings['greeting']) ?></p></article>
      </div>
      <div class="chatbot-disclosure">
        <?php if (!empty($chatbotExternalProcessing)): ?>
          <p>Messages may be sent to the configured AI provider to prepare replies and may be handled under its privacy terms. Do not share passwords or payment details. Chat history is not stored by this site.</p>
        <?php else: ?>
          <p>Answers use THDH’s public information. Chat history is not stored.</p>
        <?php endif ?>
      </div>
      <form class="chatbot-form" data-chatbot-form>
        <input type="hidden" name="csrf" value="<?= $this->escape($csrf) ?>">
        <?php if (!empty($chatbotExternalProcessing)): ?>
          <label class="chatbot-consent"><input type="checkbox" name="consent" value="1" required> I understand that my message may be processed by the configured AI provider under its privacy terms.</label>
        <?php endif ?>
        <label class="sr-only" for="chatbot-message">Your message</label>
        <textarea id="chatbot-message" name="message" rows="2" maxlength="1000" placeholder="How can we help?" required></textarea>
        <button type="submit"><span>Send</span><i data-lucide="send"></i></button>
      </form>
    </section>
  <?php else: ?>
    <a class="support-button" href="<?= $basePath ?>/contact" aria-label="Contact Time Healers Digital Hub"><i data-lucide="message-circle"></i></a>
  <?php endif ?>
  <script type="module" src="<?= $basePath ?>/assets/js/app.js"></script>
</body>
</html>
