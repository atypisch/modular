        </main>
        </div>
    </div>

    <?php if (!$is_auth_page): ?>
    <footer class="cms-footer">
        <a href="<?php echo SITE_URL; ?>/public/" class="cms-footer-logo" title="Modular CMS">
            <img src="<?php echo ADMIN_ASSETS_PATH; ?>/images/logo_modular.svg" alt="Modular CMS" width="80" height="24">
        </a>
    </footer>
    <?php endif; ?>

    <!-- Additional Scripts (vanilla JS, no jQuery) -->
    <script>
        (function() {
            // Sidebar collapse toggle: persist state
            var btn = document.getElementById('sidebar-toggle');
            if (btn) {
                var collapsed = localStorage.getItem('modular-sidebar-collapsed') === '1';
                if (collapsed) document.body.classList.add('sidebar-collapsed');
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    document.body.classList.toggle('sidebar-collapsed');
                    localStorage.setItem('modular-sidebar-collapsed', document.body.classList.contains('sidebar-collapsed') ? '1' : '0');
                });
            }

            // Keep subnavs with active subpages expanded on page load
            document.querySelectorAll('.sub-nav').forEach(function(subNav) {
                if (subNav.querySelector('a.active')) {
                    subNav.classList.add('active');
                    subNav.style.display = '';
                }
            });

            // Main nav links: point to first subpage when they have sub-nav
            document.querySelectorAll('.sidebar-nav > ul > li').forEach(function(li) {
                var mainLink = li.querySelector(':scope > a');
                var subNav = li.querySelector('.sub-nav');
                if (mainLink && subNav) {
                    var firstSub = subNav.querySelector('a');
                    if (firstSub && firstSub.getAttribute('href'))
                        mainLink.setAttribute('href', firstSub.getAttribute('href'));
                }
            });

            // Clicking the li (e.g. padding) toggles sub-nav; clicking the link navigates
            document.querySelectorAll('.sidebar-nav > ul > li').forEach(function(li) {
                li.addEventListener('click', function(e) {
                    if (e.target.closest('a')) return;
                    var subNav = li.querySelector('.sub-nav');
                    if (!subNav) return;
                    e.preventDefault();
                    document.querySelectorAll('.sidebar-nav .sub-nav').forEach(function(n) {
                        if (n === subNav) {
                            n.classList.toggle('active');
                            n.style.display = n.classList.contains('active') ? '' : 'none';
                        } else {
                            n.classList.remove('active');
                            n.style.display = 'none';
                        }
                    });
                    return false;
                });
            });
        })();
    </script>
    <?php 
    // Load additional scripts if defined
    if (isset($additional_scripts)) {
        echo $additional_scripts;
    }
    ?>
</body>
</html>
