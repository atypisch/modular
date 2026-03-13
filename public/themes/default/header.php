<?php
if (!defined('MODULAR_PATH')) {
    return;
}
$site_name = function_exists('get_site_name') ? get_site_name() : 'Modular';
$menu_items = function_exists('get_menu') ? get_menu('main') : [];
$title = isset($page_title) ? e($page_title) : (isset($post) && !empty($post['title']) ? e($post['title']) : $site_name);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $title; ?></title>
  <link rel="stylesheet" href="<?php echo e(function_exists('theme_uri') ? theme_uri('css/style.css') : 'css/style.css'); ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,600;0,9..40,700;1,9..40,400&display=swap" rel="stylesheet">
</head>
<body>
<header class="site-header" id="site-header" role="banner">
  <div class="header-inner container">
    <a href="<?php echo e(function_exists('theme_uri') ? rtrim(BASE_PATH ?? '', '/') . '/' : '#'); ?>" class="site-name"><?php echo e($site_name); ?></a>
    <nav class="main-nav main-nav--right" aria-label="Main navigation">
      <ul class="nav-list">
        <?php foreach ($menu_items as $item): ?>
          <li class="<?php echo !empty($item['children']) ? 'has-dropdown' : ''; ?>">
            <a href="<?php echo e($item['url'] ?? '#'); ?>"><?php echo e($item['label'] ?? ''); ?></a>
            <?php if (!empty($item['children'])): ?>
              <ul class="dropdown">
                <?php foreach ($item['children'] as $child): ?>
                  <li><a href="<?php echo e($child['url'] ?? '#'); ?>"><?php echo e($child['label'] ?? ''); ?></a></li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    </nav>
    <button type="button" class="mobile-nav-toggle" id="mobile-nav-toggle" aria-label="Toggle menu" aria-expanded="false">
      <span class="hamburger-bar"></span>
      <span class="hamburger-bar"></span>
      <span class="hamburger-bar"></span>
    </button>
  </div>
</header>
<nav class="mobile-nav" id="mobile-nav" aria-hidden="true">
  <ul class="mobile-nav-list">
    <?php foreach ($menu_items as $item): ?>
      <li><a href="<?php echo e($item['url'] ?? '#'); ?>"><?php echo e($item['label'] ?? ''); ?></a></li>
    <?php endforeach; ?>
  </ul>
</nav>
<main id="main" class="main-content">