<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../data/init.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /modular/admin/auth/login.php');
    exit;
}

// Set page title
$page_title = 'Media';

// Set content
$content = '
<div class="dashboard">

    <!-- Main Content -->
    <main class="main-content">
        <div class="content-header">
            <h1>Media Library</h1>
            <p>Manage your website media files</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Upload Form -->
            <div class="col-span-1">
                <div class="card">
                    <h2>Upload Media</h2>
                    <div class="mt-4">
                        <form action="/modular/admin/media/upload" method="POST" enctype="multipart/form-data" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Select Files</label>
                                <input type="file" name="files[]" multiple class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Description</label>
                                <textarea name="description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                            </div>
                            <div>
                                <button type="submit" class="btn btn-primary w-full">
                                    <i class="fas fa-upload"></i> Upload Files
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Media Grid -->
            <div class="col-span-2">
                <div class="card">
                    <h2>Media Files</h2>
                    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <!-- Add media items here -->
                        <div class="relative">
                            <img src="https://via.placeholder.com/300" alt="Sample image" class="w-full h-48 object-cover rounded-lg">
                            <div class="absolute top-2 right-2">
                                <button class="btn btn-primary">
                                    <i class="fas fa-download"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>
';

// Include header
require_once __DIR__ . '/../../templates/header.php';
?>

<?php echo $content; ?>

<?php
// Include footer
require_once __DIR__ . '/../../templates/footer.php';
?>
