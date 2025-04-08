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
    <title><?php echo htmlspecialchars($page_title); ?> - Modular CMS</title>
    <link rel="stylesheet" href="/modular/admin/assets/css/app.css">
    <style>
        /* Base styles */
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
            min-height: 100vh;
        }
        
        /* Additional styles from the page */
        <?php echo $additional_styles; ?>
    </style>
</head>
<body>
    <main>
        <?php echo $content; ?>
    </main>
</body>
</html> 