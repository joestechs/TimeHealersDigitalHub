<section class="page-hero"><div class="container"><span class="eyebrow">Professional services</span><h1>Expertise under one roof.</h1></div></section>
<section class="content-section">
  <div class="container">
    <div class="section-heading"><div><span class="eyebrow">From first idea to finished work</span><h2>Find the right place to begin.</h2></div><p>Choose a service to explore, or tell us what you are trying to accomplish. We will help shape a practical next step.</p></div>
    <div class="category-grid"><?php foreach($services as $service): ?><a class="category-card" href="<?= $this->escape(appUrl('/services/' . $service['slug'])) ?>"><span class="icon-box"><i data-lucide="<?= $this->escape($service['icon'] ?: 'sparkles') ?>"></i></span><h2><?= $this->escape($service['name']) ?></h2><p><?= $this->escape($service['summary'] ?: '') ?></p><span class="card-link">Explore this service <i data-lucide="arrow-right"></i></span></a><?php endforeach ?></div>
    <div class="service-invitation"><div><span class="eyebrow">Something different in mind?</span><h2>Let’s talk about what would help.</h2><p>Share your goals, constraints and the kind of outcome you are looking for. We will let you know how we can help.</p></div><a class="button" href="<?= $this->escape(appUrl('/contact')) ?>">Start a conversation <i data-lucide="arrow-right"></i></a></div>
  </div>
</section>
