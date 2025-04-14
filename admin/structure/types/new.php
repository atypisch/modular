<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../data/init.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /modular/admin/auth/login.php');
    exit;
}

// Check if user has admin privileges
if ($_SESSION['user_role'] !== 'admin') {
    header('Location: /modular/admin');
    exit;
}

// Initialize page title
$page_title = 'New Content Type';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    
    // Validate type name
    $name = trim($_POST['name'] ?? '');
    if (empty($name)) {
        $errors[] = 'Type name is required';
    }
    
    // Validate slug
    $slug = trim($_POST['slug'] ?? '');
    if (empty($slug)) {
        $errors[] = 'Slug is required';
    }
    
    // Validate fields
    $fields = json_decode($_POST['fields'] ?? '[]', true);
    if (empty($fields)) {
        $errors[] = 'At least one field is required';
    }
    
    // If no errors, save the type
    if (empty($errors)) {
        $typeData = [
            'name' => $name,
            'slug' => $slug,
            'fields' => json_encode($fields)
        ];
        
        // Save to database (implement this in your data layer)
        // $typeId = saveContentType($typeData);
        
        // Redirect to types list
        header('Location: /modular/admin/types?success=1');
        exit;
    }
}

// Set content
$content = '
<div class="dashboard">
    <!-- Main Content -->
    <main class="main-content">
        <div class="content-header">
            <h1>New Content Type</h1>
            <p>Create a new content type with custom fields</p>
        </div>

        <div class="grid grid-cols-1 gap-6">
            <div class="col-span-1">
                <div class="card">
                    <form action="/modular/admin/types/new" method="POST" class="space-y-6">
                        <!-- Type Name -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Type Name</label>
                            <input type="text" 
                                   name="name" 
                                   value="' . htmlspecialchars($name ?? '') . '" 
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <!-- Slug -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Slug</label>
                            <input type="text" 
                                   name="slug" 
                                   value="' . htmlspecialchars($slug ?? '') . '" 
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <!-- Fields -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Fields</label>
                            <div id="fields-container" class="mt-2 space-y-4">
                                <!-- Add fields dynamically with JavaScript -->
                            </div>
                            <button type="button" 
                                    class="btn btn-secondary" 
                                    onclick="addField()">
                                <i class="fas fa-plus"></i> Add Field
                            </button>
                        </div>

                        <!-- Submit Button -->
                        <div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Content Type
                            </button>
                        </div>
                    </form>
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

<!-- Add JavaScript for field management -->
<script>
    let fieldCount = 0;

    function addField() {
        const container = document.getElementById('fields-container');
        const field = document.createElement('div');
        field.className = 'grid grid-cols-2 gap-4';
        
        // Field Name
        const nameInput = document.createElement('input');
        nameInput.type = 'text';
        nameInput.name = `fields[${fieldCount}][name]`;
        nameInput.className = 'form-control';
        nameInput.placeholder = 'Field Name';
        
        // Field Type
        const typeSelect = document.createElement('select');
        typeSelect.name = `fields[${fieldCount}][type]`;
        typeSelect.className = 'form-control';
        
        const types = ['text', 'textarea', 'number', 'date', 'image', 'checkbox', 'select'];
        types.forEach(type => {
            const option = document.createElement('option');
            option.value = type;
            option.textContent = type.charAt(0).toUpperCase() + type.slice(1);
            typeSelect.appendChild(option);
        });
        
        field.appendChild(nameInput);
        field.appendChild(typeSelect);
        container.appendChild(field);
        
        fieldCount++;
    }
</script>
