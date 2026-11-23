<?php
$navigation = [
    'overview' => ['Overview', '/admin', 'layout-dashboard'],
    'products' => ['Products', '/admin/products', 'package'],
    'categories' => ['Categories', '/admin/categories', 'tags'],
    'vendors' => ['Vendors', '/admin/vendors', 'store'],
    'customers' => ['Customers', '/admin/customers', 'users'],
    'inquiries' => ['Inquiries', '/admin/inquiries', 'messages-square'],
    'services' => ['Services', '/admin/services', 'briefcase-business'],
    'articles' => ['Articles', '/admin/articles', 'newspaper'],
    'newsletter' => ['Newsletter', '/admin/newsletter', 'mail'],
    'chatbot' => ['Customer support bot', '/admin/chatbot', 'messages-square'],
    'orders' => ['Orders', '/admin/orders', 'receipt'],
];
if (($admin['role'] ?? '') === 'super_admin') {
    $navigation['staff'] = ['Staff & roles', '/admin/staff', 'shield-check'];
}
$titles = [
    'overview' => 'Overview',
    'products' => 'Product catalog',
    'categories' => 'Product categories',
    'vendors' => 'Vendor directory',
    'customers' => 'Customer accounts',
    'staff' => 'Staff & access',
    'inquiries' => 'Service inquiries',
    'services' => 'Service catalog',
    'articles' => 'Knowledge Hub',
    'newsletter' => 'Newsletter subscribers',
    'chatbot' => 'Customer support chatbot',
    'orders' => 'Order records',
];
?>
<section class="admin-page">
  <div class="container">
    <div class="admin-shell dashboard-shell">
      <aside class="admin-sidebar">
        <a class="admin-brand" href="<?= $this->escape(appUrl('/admin')) ?>"><span class="admin-brand-mark">TH</span><span><strong>THDH</strong><small>CONTROL ROOM</small></span></a>
        <nav class="admin-navigation" aria-label="Administration">
          <?php foreach ($navigation as $key => [$label, $url, $icon]): ?>
            <a class="<?= $section === $key ? 'is-active' : '' ?>" href="<?= $this->escape(appUrl($url)) ?>" <?= $section === $key ? 'aria-current="page"' : '' ?>><i data-lucide="<?= $this->escape($icon) ?>"></i><span><?= $this->escape($label) ?></span><?php if ($key === 'inquiries' && !empty($sectionData['stats']['inquiries'])): ?><span class="admin-nav-count"><?= (int) $sectionData['stats']['inquiries'] ?></span><?php endif ?></a>
          <?php endforeach ?>
        </nav>
        <div class="admin-sidebar-footer">
          <a href="<?= $this->escape(appUrl('/')) ?>"><i data-lucide="external-link"></i> View website</a>
          <form method="post" action="<?= $this->escape(appUrl('/logout')) ?>"><input type="hidden" name="csrf" value="<?= $this->escape($_SESSION['csrf'] ?? '') ?>"><button type="submit"><i data-lucide="log-out"></i> Sign out</button></form>
        </div>
      </aside>
      <div class="admin-main">
        <header class="admin-topbar"><div><span class="eyebrow">TIME HEALERS DIGITAL HUB</span><h1><?= $this->escape($titles[$section] ?? 'Administration') ?></h1></div><div class="admin-identity"><span class="admin-avatar"><?= $this->escape(strtoupper(substr($admin['name'], 0, 1))) ?></span><span><strong><?= $this->escape($admin['name']) ?></strong><small><?= $this->escape(str_replace('_', ' ', $admin['role'])) ?></small></span></div></header>
        <?php require __DIR__ . '/sections/' . $section . '.php'; ?>
      </div>
    </div>
  </div>
</section>
