<section class="page-hero"><div class="container"><span class="eyebrow">Customer dashboard</span><h1>Welcome back, <?= $this->escape($user['name']) ?>.</h1><p>Your account, orders, and profile settings in one place.</p></div></section>
<section class="content-section"><div class="container">
  <div class="account-summary-grid"><article class="stat-card"><span>Orders</span><strong><?= (int) $orderCount ?></strong></article><article class="stat-card"><span>Signed in as</span><strong class="account-email"><?= $this->escape($user['email']) ?></strong></article></div>
  <div class="category-grid account-cards">
    <a class="category-card" href="<?= $this->escape(appUrl('/account/orders')) ?>"><span class="icon-box"><i data-lucide="receipt-text"></i></span><h2>Order history</h2><p>Review your orders and their current status.</p><span class="card-link">View orders <i data-lucide="arrow-right"></i></span></a>
    <a class="category-card" href="<?= $this->escape(appUrl('/account/downloads')) ?>"><span class="icon-box"><i data-lucide="download"></i></span><h2>Downloads</h2><p>Check for files made available with your purchases.</p><span class="card-link">View downloads <i data-lucide="arrow-right"></i></span></a>
    <a class="category-card" href="<?= $this->escape(appUrl('/account/settings')) ?>"><span class="icon-box"><i data-lucide="settings"></i></span><h2>Profile & security</h2><p>Update your contact details and password.</p><span class="card-link">Manage profile <i data-lucide="arrow-right"></i></span></a>
  </div>
</div></section>
