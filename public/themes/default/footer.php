</main>
<footer class="site-footer" role="contentinfo">
  <div class="container">
    <p class="footer-copy">&copy; <?php echo date('Y'); ?> <?php echo e(function_exists('get_site_name') ? get_site_name() : 'Modular'); ?>. All rights reserved.</p>
  </div>
</footer>
<script src="<?php echo e(function_exists('theme_uri') ? theme_uri('js/theme.js') : 'js/theme.js'); ?>"></script>
</body>
</html>
