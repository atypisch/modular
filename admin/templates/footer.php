        </main>
    </div>

    <!-- Additional Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Toggle sub-navigation
            $('.sidebar-nav li').click(function(e) {
                e.preventDefault();
                
                // Close all other sub-navs
                $('.sub-nav').not($(this).find('.sub-nav')).slideUp().removeClass('active');
                
                // Toggle this sub-nav
                $(this).find('.sub-nav').slideToggle().toggleClass('active');
                
                // Prevent link from being followed
                return false;
            });

            // Handle sub-nav links
            $('.sub-nav a').click(function(e) {
                e.stopPropagation();
                window.location.href = $(this).attr('href');
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
