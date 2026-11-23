<section class="page-hero"><div class="container"><span class="eyebrow">Customer dashboard</span><h1>Order history.</h1><p>Orders associated with <?= $this->escape($user['email']) ?>.</p></div></section>
<section class="content-section"><div class="container">
  <p><a class="text-link" href="<?= $this->escape(appUrl('/account')) ?>"><i data-lucide="arrow-left"></i> Back to your dashboard</a></p>
  <?php if (!$orders): ?>
    <div class="empty-state"><i data-lucide="receipt-text"></i><h2>No orders yet</h2><p>Your completed purchases will appear here.</p><a class="button" href="<?= $this->escape(appUrl('/products')) ?>">Explore products</a></div>
  <?php else: ?>
    <div class="table-wrap account-orders-table"><table><thead><tr><th>Order</th><th>Date</th><th>Type</th><th>Status</th><th>Total</th></tr></thead><tbody>
      <?php foreach ($orders as $order): ?><tr><td>#<?= (int) $order['id'] ?></td><td><?= $this->escape(date('M j, Y', strtotime($order['created_at']))) ?></td><td><?= $this->escape(ucfirst($order['order_type'])) ?></td><td><span class="status-badge"><?= $this->escape(ucfirst($order['status'])) ?></span></td><td><?= $this->escape($order['currency']) ?> <?= number_format((float) $order['subtotal'], 2) ?></td></tr><?php endforeach ?>
    </tbody></table></div>
  <?php endif ?>
</div></section>
