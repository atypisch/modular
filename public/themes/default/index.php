<?php
if (!defined('MODULAR_PATH')) return;
include __DIR__ . '/header.php';
$base = defined('BASE_PATH') ? rtrim(BASE_PATH, '/') : '';
?>
<section class="hero">
  <div class="container">
    <h1><?php echo isset($page_title) ? e($page_title) : 'Welcome'; ?></h1>
    <p>Modular CMS – hybrid SSR with PHP templates and optional Vue for interactivity.</p>
    <a href="<?php echo e($base); ?>/admin" class="btn">Go to Admin</a>
  </div>
</section>
<?php if (!empty($content)): ?>
<section class="section">
  <div class="container">
    <div class="content-area"><?php echo $content; ?></div>
  </div>
</section>
<?php else: ?>
<!-- Block: 1 column -->
<section class="section section--alt">
  <div class="container">
    <div class="block block--1col">
      <div class="row">
        <div class="col col-12 card">
          <h2>Welcome to Modular CMS</h2>
          <p class="text-muted">This is a simple content management system built with PHP. Theme-based design, modular architecture, and a clean interface.</p>
        </div>
      </div>
    </div>
  </div>
</section>
<!-- Block: 2 columns -->
<section class="section">
  <div class="container">
    <div class="block block--2col">
      <div class="row">
        <div class="col col-12 col-tablet-2 col-mobile-2 card">
          <h2>Theme-based design</h2>
          <p class="text-muted">Templates live in <code>public/themes/default</code>. Sticky header, dynamic menu, responsive layout.</p>
        </div>
        <div class="col col-12 col-tablet-2 col-mobile-2 card">
          <h2>Modular architecture</h2>
          <p class="text-muted">Core, admin, and public are separated. Use <code>get_posts()</code>, <code>get_menu()</code>, <code>get_site_name()</code> in templates.</p>
        </div>
      </div>
    </div>
  </div>
</section>
<!-- Block: 3 columns -->
<section class="section section--alt">
  <div class="container">
    <div class="block block--3col">
      <div class="row">
        <div class="col col-12 col-tablet-3 col-mobile-3 card">
          <h3>Simple</h3>
          <p class="text-muted">Clean and minimal. Focus on your content.</p>
        </div>
        <div class="col col-12 col-tablet-3 col-mobile-3 card">
          <h3>Flexible</h3>
          <p class="text-muted">Build with PHP templates and optional Vue for interactivity.</p>
        </div>
        <div class="col col-12 col-tablet-3 col-mobile-3 card">
          <h3>Extensible</h3>
          <p class="text-muted">Modules and themes extend the core without touching it.</p>
        </div>
      </div>
    </div>
  </div>
</section>
<!-- Block: 2 columns (extra) -->
<section class="section">
  <div class="container">
    <div class="block block--2col">
      <div class="row">
        <div class="col col-12 col-tablet-2 col-mobile-2 card">
          <h3>Documentation</h3>
          <p class="text-muted">Internal docs for API, hooks, and theme development.</p>
        </div>
        <div class="col col-12 col-tablet-2 col-mobile-2 card">
          <h3>Admin</h3>
          <p class="text-muted">Manage content, navigation, and settings from one place.</p>
        </div>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>
<?php include __DIR__ . '/footer.php'; ?>
