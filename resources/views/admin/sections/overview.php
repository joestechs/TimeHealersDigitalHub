<section class="admin-content">
  <div class="admin-welcome"><div><span class="eyebrow">Your workspace</span><h2>Good day, <?= $this->escape($admin['name']) ?>.</h2><p>Here is a snapshot of the marketplace and the latest activity.</p></div><a class="button" href="<?= $this->escape(appUrl('/admin/products')) ?>"><i data-lucide="plus"></i> Add a product</a></div>
  <div class="admin-stat-grid">
    <?php foreach ([['Customers','customers','users'],['Products','products','package'],['New inquiries','inquiries','messages-square'],['Orders','orders','receipt'],['Published articles','published','newspaper']] as [$label,$key,$icon]): ?>
      <article class="admin-stat-card"><span class="admin-stat-icon"><i data-lucide="<?= $this->escape($icon) ?>"></i></span><span><?= $this->escape($label) ?></span><strong><?= (int) ($sectionData['stats'][$key] ?? 0) ?></strong></article>
    <?php endforeach ?>
  </div>
  <div class="admin-overview-grid">
    <section class="admin-panel"><div class="admin-panel-heading"><div><span class="eyebrow">Inbox</span><h2>Recent inquiries</h2></div><a class="text-link" href="<?= $this->escape(appUrl('/admin/inquiries')) ?>">View all</a></div>
      <?php if (!$sectionData['recentInquiries']): ?><p class="admin-empty">New customer inquiries will appear here.</p><?php else: ?><div class="admin-activity-list"><?php foreach ($sectionData['recentInquiries'] as $inquiry): ?><a href="<?= $this->escape(appUrl('/admin/inquiries')) ?>"><span class="activity-avatar"><?= $this->escape(strtoupper(substr($inquiry['name'], 0, 1))) ?></span><span class="activity-copy"><strong><?= $this->escape($inquiry['name']) ?></strong><small><?= $this->escape($inquiry['service_name'] ?: 'General inquiry') ?> · <?= $this->escape(date('M j', strtotime($inquiry['created_at']))) ?></small></span><span class="status-badge status-<?= $this->escape($inquiry['status']) ?>"><?= $this->escape(str_replace('_', ' ', $inquiry['status'])) ?></span></a><?php endforeach ?></div><?php endif ?>
    </section>
    <section class="admin-panel"><div class="admin-panel-heading"><div><span class="eyebrow">Commerce</span><h2>Latest orders</h2></div><a class="text-link" href="<?= $this->escape(appUrl('/admin/orders')) ?>">View all</a></div>
      <?php if (!$sectionData['recentOrders']): ?><p class="admin-empty">Order activity will appear here when available.</p><?php else: ?><div class="admin-activity-list"><?php foreach ($sectionData['recentOrders'] as $order): ?><a href="<?= $this->escape(appUrl('/admin/orders?id=' . (int) $order['id'])) ?>"><span class="activity-avatar"><i data-lucide="receipt"></i></span><span class="activity-copy"><strong>Order #<?= (int) $order['id'] ?></strong><small><?= $this->escape($order['customer_name'] ?: 'Guest') ?> · <?= $this->escape(date('M j', strtotime($order['created_at']))) ?></small></span><span class="activity-total"><?= $this->escape($order['currency']) ?> <?= number_format((float) $order['subtotal'], 2) ?></span></a><?php endforeach ?></div><?php endif ?>
    </section>
  </div>
</section>
