        </main>
        </div>
    </div>

    <?php if (!$is_auth_page): ?>
    <footer class="cms-footer">
        <a href="<?php echo SITE_URL; ?>/public/" class="cms-footer-logo" title="Modular CMS">
            <img src="<?php echo ADMIN_URL; ?>/assets/images/logo_modular.svg" alt="Modular CMS" width="80" height="24">
        </a>
    </footer>
    <?php endif; ?>

    <!-- Additional Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Sidebar collapse toggle: persist state, works even if base.js fails (e.g. module export)
            (function() {
                var btn = document.getElementById('sidebar-toggle');
                if (!btn) return;
                var collapsed = localStorage.getItem('modular-sidebar-collapsed') === '1';
                if (collapsed) document.body.classList.add('sidebar-collapsed');
                btn.addEventListener('click', function() {
                    document.body.classList.toggle('sidebar-collapsed');
                    localStorage.setItem('modular-sidebar-collapsed', document.body.classList.contains('sidebar-collapsed') ? '1' : '0');
                });
            })();

            // Keep subnavs with active subpages expanded on page load
            $('.sub-nav').each(function() {
                if ($(this).find('a.active').length > 0) {
                    $(this).addClass('active').show();
                }
            });

            // Main nav links always navigate to their href (first subpage when they have sub-nav).
            $('.sidebar-nav > ul > li').each(function() {
                var $li = $(this);
                var $mainLink = $li.children('a').first();
                var $subNav = $li.find('.sub-nav');
                if ($subNav.length > 0) {
                    var $firstSubLink = $subNav.find('a').first();
                    if ($firstSubLink.length && $firstSubLink.attr('href')) {
                        $mainLink.attr('href', $firstSubLink.attr('href'));
                    }
                }
            });

            // Optional: clicking the li (e.g. padding) toggles sub-nav; clicking the link navigates
            $('.sidebar-nav > ul > li').click(function(e) {
                if ($(e.target).closest('a').length) return;
                var $subNav = $(this).find('.sub-nav');
                if ($subNav.length > 0) {
                    e.preventDefault();
                    $('.sub-nav').not($subNav).slideUp(100).removeClass('active');
                    $subNav.slideToggle(100).toggleClass('active');
                    return false;
                }
            });
        });
    </script>
    <?php 
    // Load additional scripts if defined
    if (isset($additional_scripts)) {
        echo $additional_scripts;
    }
    ?>
</body>
</html>
