<?php
// Ensure $page_title and $content are set
$page_title = $page_title ?? 'Modular CMS';
$content = $content ?? '';
$additional_styles = $additional_styles ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - Modular CMS' : 'Modular CMS'; ?></title>
    
    <!-- Base CSS -->
    <link rel="stylesheet" href="/modular/admin/assets/css/base.css">
    <link rel="stylesheet" href="/modular/admin/assets/css/responsive.css">
    
    <?php 
    // Load additional styles if defined
    if (isset($additional_styles)) {
        echo $additional_styles;
    }
    ?>
</head>
<body>
    <?php 
    // Load the main content
    if (isset($content)) {
        echo $content;
    }
    ?>
</body>
</html> 