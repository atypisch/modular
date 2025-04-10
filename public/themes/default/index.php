<?php
// Ensure $page_title and $content are set
$page_title = $page_title ?? 'Modular CMS';
$content = $content ?? '';
$additional_styles = $additional_styles ?? '';
$additional_scripts = $additional_scripts ?? '';

// Get current path for active navigation
$current_path = $_SERVER['REQUEST_URI'];

// Include header
require_once __DIR__ . '/../../admin/templates/header.php';
?>

    <!-- Main Content -->
    <main class="main-content">
        <?php 
        // Load the main content
        if (isset($content)) {
            echo $content;
        }
        ?>
    </main>

<?php
// Include footer
require_once __DIR__ . '/../../admin/templates/footer.php';
?>