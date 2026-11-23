<article class="page-hero"><div class="container article-heading"><span class="eyebrow">Knowledge Hub</span><h1><?= $this->escape($post['title']) ?></h1><?php if ($post['published_at']): ?><time datetime="<?= $this->escape(date('Y-m-d', strtotime($post['published_at']))) ?>"><?= $this->escape(date('F j, Y', strtotime($post['published_at']))) ?></time><?php endif ?><p><?= $this->escape($post['excerpt'] ?: '') ?></p></div></article>
<section class="content-section"><div class="container article-content">
  <?php $cover = $this->imageUrl($post['cover_image'] ?? ''); if ($cover !== ''): ?><img class="article-feature-image" src="<?= $this->escape($cover) ?>" alt=""><?php endif ?>
  <div class="article-body"><?= nl2br($this->escape($post['content'] ?: '')) ?></div>
  <a class="text-link" href="<?= $this->escape(appUrl('/blog')) ?>"><i data-lucide="arrow-left"></i> Back to the Knowledge Hub</a>
</div></section>
