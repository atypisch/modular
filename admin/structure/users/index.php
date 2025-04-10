<?php
session_start();
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

// Get user data
$userData = new userdata();
$users = $userData->getAllUsers();

// Set page title
$page_title = 'Users';

// Set content
$content = '
<div class="dashboard">
    <!-- Main Content -->
    <main class="main-content">
        <div class="content-header">
            <h1>Users Management</h1>
            <p>Manage all users in the system</p>
        </div>

        <div class="grid grid-cols-1 gap-6">
            <!-- Users List -->
            <div class="col-span-1">
                <div class="card">
                    <div class="flex justify-between items-center p-4">
                        <h2>Users</h2>
                        <a href="/modular/admin/users/new" class="btn btn-primary">
                            <i class="fas fa-plus"></i> New User
                        </a>
                    </div>
                    <div class="p-4">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php echo htmlspecialchars($user['name']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php echo htmlspecialchars($user['email']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php echo htmlspecialchars($user['role']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <a href="/modular/admin/users/edit/<?php echo htmlspecialchars($user['id']); ?>" class="text-blue-600 hover:text-blue-900">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="/modular/admin/users/delete/<?php echo htmlspecialchars($user['id']); ?>" class="ml-2 text-red-600 hover:text-red-900" onclick="return confirm(\'Are you sure you want to delete this user?\');">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- User Roles -->
            <div class="col-span-1">
                <div class="card">
                    <h2>User Roles</h2>
                    <div class="mt-4">
                        <!-- Add user roles form here -->
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
