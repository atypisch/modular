<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/core/classes/PostType.php';
require_once dirname(dirname(__DIR__)) . '/core/functions/auth.php';
require_once dirname(dirname(__DIR__)) . '/core/functions/admin.php';

// Require authentication
require_auth();

$postType = new PostType();
$message = '';
$errors = [];
$postTypeData = [
    'name' => '',
    'nicename' => '',
    'single_name' => '',
    'plural_name' => '',
    'description' => '',
    'is_public' => true,
    'supports_title' => true,
    'supports_editor' => true,
    'supports_thumbnail' => false
];

// If editing existing post type, load its data
if (isset($_GET['id'])) {
    try {
        $postTypeData = $postType->getById($_GET['id']);
        if (!$postTypeData) {
            header('Location: index.php');
            exit;
        }
    } catch (Exception $e) {
        $message = "Error loading post type: " . $e->getMessage();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect form data
    $postTypeData = [
        'name' => $_POST['name'] ?? '',
        'nicename' => $_POST['nicename'] ?? '',
        'single_name' => $_POST['single_name'] ?? '',
        'plural_name' => $_POST['plural_name'] ?? '',
        'description' => $_POST['description'] ?? '',
        'is_public' => isset($_POST['is_public']),
        'supports_title' => isset($_POST['supports_title']),
        'supports_editor' => isset($_POST['supports_editor']),
        'supports_thumbnail' => isset($_POST['supports_thumbnail'])
    ];

    // Validate required fields
    $required = ['name', 'nicename', 'single_name', 'plural_name'];
    foreach ($required as $field) {
        if (empty($postTypeData[$field])) {
            $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
        }
    }

    if (empty($errors)) {
        try {
            if (isset($_GET['id'])) {
                // Update existing post type
                $postType->update($_GET['id'], $postTypeData);
                $_SESSION['success'] = 'Post type updated successfully';
            } else {
                // Create new post type
                $postType->create($postTypeData);
                $_SESSION['success'] = 'Post type created successfully';
            }
            header('Location: index.php');
            exit;
        } catch (Exception $e) {
            $message = "Error: " . $e->getMessage();
        }
    }
}

$title = isset($_GET['id']) ? 'Edit Post Type' : 'Create New Post Type';

$content = '
<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold">' . $title . '</h1>
            <a href="index.php" class="text-blue-500 hover:text-blue-700">← Back to Post Types</a>
        </div>
        
        ' . ($message ? '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">' . htmlspecialchars($message) . '</div>' : '') . '
        
        <form method="POST" class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4">
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="name">
                    Name
                </label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline' . (isset($errors['name']) ? ' border-red-500' : '') . '"
                    id="name" type="text" name="name" value="' . htmlspecialchars($postTypeData['name']) . '">
                ' . (isset($errors['name']) ? '<p class="text-red-500 text-xs italic">' . $errors['name'] . '</p>' : '') . '
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="nicename">
                    Slug
                </label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline' . (isset($errors['nicename']) ? ' border-red-500' : '') . '"
                    id="nicename" type="text" name="nicename" value="' . htmlspecialchars($postTypeData['nicename']) . '">
                ' . (isset($errors['nicename']) ? '<p class="text-red-500 text-xs italic">' . $errors['nicename'] . '</p>' : '') . '
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="single_name">
                    Single Name
                </label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline' . (isset($errors['single_name']) ? ' border-red-500' : '') . '"
                    id="single_name" type="text" name="single_name" value="' . htmlspecialchars($postTypeData['single_name']) . '">
                ' . (isset($errors['single_name']) ? '<p class="text-red-500 text-xs italic">' . $errors['single_name'] . '</p>' : '') . '
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="plural_name">
                    Plural Name
                </label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline' . (isset($errors['plural_name']) ? ' border-red-500' : '') . '"
                    id="plural_name" type="text" name="plural_name" value="' . htmlspecialchars($postTypeData['plural_name']) . '">
                ' . (isset($errors['plural_name']) ? '<p class="text-red-500 text-xs italic">' . $errors['plural_name'] . '</p>' : '') . '
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="description">
                    Description
                </label>
                <textarea class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                    id="description" name="description" rows="3">' . htmlspecialchars($postTypeData['description']) . '</textarea>
            </div>
            
            <div class="mb-4">
                <div class="flex items-center">
                    <input class="mr-2" type="checkbox" id="is_public" name="is_public" ' . ($postTypeData['is_public'] ? 'checked' : '') . '>
                    <label class="text-gray-700 text-sm font-bold" for="is_public">
                        Public
                    </label>
                </div>
            </div>
            
            <div class="mb-4">
                <div class="flex items-center">
                    <input class="mr-2" type="checkbox" id="supports_title" name="supports_title" ' . ($postTypeData['supports_title'] ? 'checked' : '') . '>
                    <label class="text-gray-700 text-sm font-bold" for="supports_title">
                        Supports Title
                    </label>
                </div>
            </div>
            
            <div class="mb-4">
                <div class="flex items-center">
                    <input class="mr-2" type="checkbox" id="supports_editor" name="supports_editor" ' . ($postTypeData['supports_editor'] ? 'checked' : '') . '>
                    <label class="text-gray-700 text-sm font-bold" for="supports_editor">
                        Supports Editor
                    </label>
                </div>
            </div>
            
            <div class="mb-6">
                <div class="flex items-center">
                    <input class="mr-2" type="checkbox" id="supports_thumbnail" name="supports_thumbnail" ' . ($postTypeData['supports_thumbnail'] ? 'checked' : '') . '>
                    <label class="text-gray-700 text-sm font-bold" for="supports_thumbnail">
                        Supports Thumbnail
                    </label>
                </div>
            </div>
            
            <div class="flex items-center justify-between">
                <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" type="submit">
                    ' . (isset($_GET['id']) ? 'Update' : 'Create') . ' Post Type
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById("name").addEventListener("input", function() {
    // Only auto-generate slug if it\'s empty or matches the previous auto-generated value
    var nicenameInput = document.getElementById("nicename");
    if (!nicenameInput.value || nicenameInput.value === this.value.toLowerCase().replace(/[^a-z0-9]+/g, "-")) {
        nicenameInput.value = this.value.toLowerCase().replace(/[^a-z0-9]+/g, "-");
    }
});
</script>';

// Include header and output content
require_once dirname(__DIR__) . '/templates/header.php';
echo $content;
