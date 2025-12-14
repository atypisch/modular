    <?php if (!$is_auth_page): ?>
        </main>
    </div>
    <?php endif; ?>
    
    <!-- Additional Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Keep subnavs with active subpages expanded on page load
            $('.sub-nav').each(function() {
                if ($(this).find('a.active').length > 0) {
                    $(this).addClass('active').show();
                }
            });
            
            // Toggle sub-navigation
            $('.sidebar-nav > ul > li').click(function(e) {
                // Don't prevent if clicking on a link directly
                if ($(e.target).is('a')) {
                    return;
                }
                
                e.preventDefault();
                
                var $subNav = $(this).find('.sub-nav');
                if ($subNav.length > 0) {
                    // Close all other sub-navs
                    $('.sub-nav').not($subNav).slideUp(100).removeClass('active');
                    
                    // Toggle this sub-nav with faster animation
                    $subNav.slideToggle(100).toggleClass('active');
                }
                
                // Prevent link from being followed
                return false;
            });

            // Handle sub-nav links - allow navigation
            $('.sub-nav a').click(function(e) {
                e.stopPropagation();
                // Allow normal navigation
            });
            
            // Handle main nav links that have subnavs
            $('.sidebar-nav > ul > li > a').click(function(e) {
                var $li = $(this).closest('li');
                var $subNav = $li.find('.sub-nav');
                
                // If there's a subnav, toggle it instead of navigating
                if ($subNav.length > 0 && !$(this).hasClass('active')) {
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
