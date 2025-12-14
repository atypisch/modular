<?php
/**
 * Media Upload Page - Japanese Style
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!defined('ADMIN_PATH')) {
    require_once dirname(__DIR__) . '/config/config.php';
}
require_once dirname(__DIR__) . '/bootstrap.php';
require_once CORE_PATH . '/functions/auth.php';

if (!is_logged_in()) {
    header('Location: /modular/admin/auth/login.php');
    exit;
}

$page_title = 'Upload Media';
$media_active = true;

require_once ADMIN_PATH . '/templates/header.php';
?>

<div class="container">
    <div class="page-header">
        <div class="flex justify-between items-center">
            <h1>Upload Media</h1>
            <a href="/modular/admin/media" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Media Library
            </a>
        </div>
    </div>

    <div class="card">
        <form id="upload-form" enctype="multipart/form-data" action="/modular/admin/media/upload-handler.php" method="POST">
            <div class="form-group">
                <label for="file">Select File <span style="color: var(--error-text);">*</span></label>
                <input type="file" id="file" name="file" class="form-control" required accept="image/*,application/pdf">
                <div class="help-text">Select an image or PDF file to upload.</div>
            </div>
            
            <div class="form-group">
                <label for="title">Title</label>
                <input type="text" id="title" name="title" class="form-control" placeholder="Media title">
                <div class="help-text">Optional title for this media file.</div>
            </div>
            
            <div class="form-group">
                <label for="alt_text">Alt Text</label>
                <input type="text" id="alt_text" name="alt_text" class="form-control" placeholder="Alternative text">
                <div class="help-text">Alternative text for accessibility (for images).</div>
            </div>
            
            <div class="form-actions">
                <a href="/modular/admin/media" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-upload"></i> Upload
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once ADMIN_PATH . '/templates/footer.php'; ?>
