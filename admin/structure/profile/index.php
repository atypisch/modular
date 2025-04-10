<?php
session_start();
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../data/init.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /modular/admin/auth/login.php');
    exit;
}

// Get user data
$userData = new userdata();
$user = $userData->getUserById($_SESSION['user_id']);

// Set page title
$page_title = 'Profile';

// Set content
$content = '
<div class="dashboard">
    <!-- Main Content -->
    <main class="main-content">
        <div class="content-header">
            <h1>Profile</h1>
            <p>Manage your account information</p>
        </div>

        <!-- Two Column Layout -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Account Information -->
            <div class="col-span-1">
                <div class="card">
                    <h2>Account Information</h2>
                    <form action="/modular/admin/profile/update" method="POST" enctype="multipart/form-data" class="space-y-4">
                        <!-- Profile Picture -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Profile Picture</label>
                            <div class="mt-1 flex items-center">
                                <img src="' . ($user['profile_picture'] ? '/modular/uploads/users/' . $user['profile_picture'] : '/modular/admin/assets/images/default-avatar.png') . '" 
                                     alt="Profile Picture" 
                                     class="w-24 h-24 rounded-full object-cover">
                                <input type="file" name="profile_picture" class="ml-4" accept="image/*">
                            </div>
                        </div>

                        <!-- Name -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Full Name</label>
                            <input type="text" 
                                   name="name" 
                                   value="' . htmlspecialchars($user['name']) . '" 
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <!-- Email -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Email Address</label>
                            <input type="email" 
                                   name="email" 
                                   value="' . htmlspecialchars($user['email']) . '" 
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <!-- Password -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Password</label>
                            <div class="mt-1">
                                <input type="password" 
                                       name="password" 
                                       placeholder="Leave blank to keep current password"
                                       class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                        </div>

                        <!-- Biography -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Biography</label>
                            <textarea name="biography" 
                                      rows="4"
                                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                ' . htmlspecialchars($user['biography']) . '
                            </textarea>
                        </div>

                        <!-- Submit Button -->
                        <div>
                            <button type="submit" class="btn btn-primary w-full">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Additional Information -->
            <div class="col-span-1">
                <div class="card">
                    <h2>Additional Information</h2>
                    <div class="space-y-4">
                        <!-- User Role -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">User Role</label>
                            <div class="mt-1">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                    ' . htmlspecialchars($user['role']) . '
                                </span>
                            </div>
                        </div>

                        <!-- Account Created -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Account Created</label>
                            <div class="mt-1">
                                <span class="text-sm text-gray-600">
                                    ' . date('F j, Y', strtotime($user['created_at'])) . '
                                </span>
                            </div>
                        </div>

                        <!-- Last Login -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Last Login</label>
                            <div class="mt-1">
                                <span class="text-sm text-gray-600">
                                    ' . ($user['last_login'] ? date('F j, Y H:i', strtotime($user['last_login'])) : 'Never') . '
                                </span>
                            </div>
                        </div>

                        <!-- API Access -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">API Access</label>
                            <div class="mt-1">
                                <span class="text-sm text-gray-600">
                                    ' . ($user['api_access'] ? 'Enabled' : 'Disabled') . '
                                </span>
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
