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
  <?php
  // Simuze Community styles & scripts for community routes only
  $base_path  = defined('BASE_PATH') ? rtrim(BASE_PATH, '/') : '';
  $path       = function_exists('current_path') ? current_path() : '';
  $firstSeg   = $path !== '' ? explode('/', trim($path, '/'))[0] : '';
  $communityPrefixes = ['profile', 'user', 'group', 'groups', 'messages', 'discography', 'activity', 'friends'];
  $isCommunityRoute  = in_array($firstSeg, $communityPrefixes, true);

  if ($isCommunityRoute) {
      $community_css     = $base_path . '/public/modules/simuze-community/assets/css/community.css';
      $community_js_base = $base_path . '/public/modules/simuze-community/assets/js';
      $jquery_url        = $base_path . '/public/themes/simuze/js/jquery-4.0.0.min.js';
      $simuze_theme_uri  = $base_path . '/public/themes/simuze';
  ?>
  <!-- Simuze theme layout styles so profile/activity pages match Simuze UI -->
  <link rel="stylesheet" href="<?php echo e($simuze_theme_uri . '/style.css'); ?>">
  <link rel="stylesheet" href="<?php echo e($simuze_theme_uri . '/css/layout.css'); ?>">
  <link rel="stylesheet" href="<?php echo e($simuze_theme_uri . '/css/components.css'); ?>">
  <link rel="stylesheet" href="<?php echo e($simuze_theme_uri . '/css/forms.css'); ?>">
  <link rel="stylesheet" href="<?php echo e($simuze_theme_uri . '/css/effects.css'); ?>">
  <link rel="stylesheet" href="<?php echo e($simuze_theme_uri . '/css/menu.css'); ?>">
  <link rel="stylesheet" href="<?php echo e($simuze_theme_uri . '/css/calender.css'); ?>">
  <link rel="stylesheet" href="<?php echo e($simuze_theme_uri . '/css/carousel.css'); ?>">
  <link rel="stylesheet" href="<?php echo e($simuze_theme_uri . '/css/subnav.css'); ?>">
  <link rel="stylesheet" href="<?php echo e($simuze_theme_uri . '/css/hero.css'); ?>">
  <link rel="stylesheet" href="<?php echo e($simuze_theme_uri . '/css/music.css'); ?>">
  <link rel="stylesheet" href="<?php echo e($simuze_theme_uri . '/css/responsive.css'); ?>">
  <!-- Community-specific styles and JS -->
  <link rel="stylesheet" href="<?php echo e($community_css); ?>">
  <script src="<?php echo e($jquery_url); ?>"></script>
  <script src="<?php echo e($community_js_base . '/community.js'); ?>"></script>
  <script src="<?php echo e($community_js_base . '/mentions.js'); ?>"></script>
  <script src="<?php echo e($community_js_base . '/activity.js'); ?>"></script>
  <script src="<?php echo e($community_js_base . '/group-wizard.js'); ?>"></script>
  <script src="<?php echo e($community_js_base . '/create-group.js'); ?>"></script>
  <!-- Cropper.js for image cropping (CDN, matches WP community plugin) -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/cropperjs@1.6.1/dist/cropper.min.css">
  <script src="https://cdn.jsdelivr.net/npm/cropperjs@1.6.1/dist/cropper.min.js"></script>
  <script>
    window.simuzeCommunity = window.simuzeCommunity || {};
    (function(cfg) {
      cfg.apiUrl   = cfg.apiUrl   || '<?php echo e($base_path . '/api/simuze/v1/'); ?>';
      cfg.ajaxUrl  = cfg.ajaxUrl  || '<?php echo e($base_path . '/api/simezu-ajax.php'); ?>';
      cfg.homeUrl  = cfg.homeUrl  || '<?php echo e($base_path); ?>/';
      cfg.nonce    = cfg.nonce    || '<?php echo e(md5('simuze-community-' . (session_id() ?: ''))); ?>';
      cfg.defaultAvatar = cfg.defaultAvatar || '<?php echo e(function_exists('theme_uri') ? theme_uri('images/profile_default.jpg') : ($base_path . '/public/themes/default/images/profile_default.jpg')); ?>';
    })(window.simuzeCommunity);
    window.ajaxurl = '<?php echo e($base_path . '/api/simezu-ajax.php'); ?>';
    window.simuze_ajax = window.simuze_ajax || {};
    window.simuze_ajax.ajaxurl = window.ajaxurl;
  </script>
  <?php } ?>
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