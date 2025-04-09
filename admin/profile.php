<?php
session_start();
require_once dirname(__FILE__) . '/config/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /modular/admin/auth/login.php');
    exit;
}

// Get current user data
try {
    $stmt = $db->prepare("SELECT id, name, email, bio, profile_image, created_at, role, password FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $user = [
        'id' => $_SESSION['user_id'],
        'name' => '',
        'email' => '',
        'bio' => '',
        'profile_image' => '',
        'created_at' => '',
        'role' => '',
        'password' => ''
    ];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['full_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $bio = $_POST['bio'] ?? '';
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    $errors = [];

    // Validate required fields
    if (empty($name)) {
        $errors[] = 'Full name is required';
    }
    if (empty($email)) {
        $errors[] = 'Email is required';
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    }

    // Check if email already exists for another user
    if ($email !== $user['email']) {
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'Email already registered';
        }
    }

    // Handle password change if provided
    if (!empty($new_password)) {
        // Validate current password
        if (empty($current_password)) {
            $errors[] = 'Current password is required to change password';
        } else {
            $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user_data = $stmt->fetch();
            
            if (!password_verify($current_password, $user_data['password'])) {
                $errors[] = 'Current password is incorrect';
            }
        }

        // Validate new password
        if (strlen($new_password) < 8) {
            $errors[] = 'New password must be at least 8 characters long';
        }
        if (!preg_match('/[A-Z]/', $new_password)) {
            $errors[] = 'New password must contain at least one uppercase letter';
        }
        if (!preg_match('/[a-z]/', $new_password)) {
            $errors[] = 'New password must contain at least one lowercase letter';
        }
        if (!preg_match('/[0-9]/', $new_password)) {
            $errors[] = 'New password must contain at least one number';
        }
        if (!preg_match('/[^A-Za-z0-9]/', $new_password)) {
            $errors[] = 'New password must contain at least one special character';
        }
        if ($new_password !== $confirm_password) {
            $errors[] = 'New passwords do not match';
        }
    }

    // Handle profile image upload
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
        $max_size = 5 * 1024 * 1024; // 5MB

        if (!in_array($_FILES['profile_image']['type'], $allowed_types)) {
            $errors[] = 'Invalid file type. Only JPEG, PNG, and WebP are allowed.';
        }
        if ($_FILES['profile_image']['size'] > $max_size) {
            $errors[] = 'File size too large. Maximum allowed is 5MB.';
        }

        if (empty($errors)) {
            $upload_dir = dirname(dirname(__FILE__)) . '/public/uploads/profiles/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_extension = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
            $new_filename = 'profile_' . $_SESSION['user_id'] . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;

            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
                // Delete old profile image if it exists
                if (!empty($user['profile_image']) && file_exists($upload_dir . $user['profile_image'])) {
                    unlink($upload_dir . $user['profile_image']);
                }
            } else {
                $errors[] = 'Failed to upload profile image';
            }
        }
    }

    if (empty($errors)) {
        try {
            // First update basic info
            $stmt = $db->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
            $params = [$name, $email, $_SESSION['user_id']];
            
            if (!empty($new_password)) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $params[] = $hashed_password;
                $stmt = $db->prepare("UPDATE users SET name = ?, email = ?, password = ? WHERE id = ?");
            }
            
            $stmt->execute($params);
            
            // Try to update bio if column exists
            try {
                $stmt = $db->prepare("UPDATE users SET bio = ? WHERE id = ?");
                $stmt->execute([$bio, $_SESSION['user_id']]);
            } catch (PDOException $e) {
                // Bio column doesn't exist, skip updating it
            }
            
            // Try to update profile image if column exists
            if (!empty($_FILES['profile_image']) && $_FILES['profile_image']['error'] === 0) {
                try {
                    $file_extension = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
                    $new_filename = 'profile_' . $_SESSION['user_id'] . '.' . $file_extension;
                    $stmt = $db->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
                    $stmt->execute([$new_filename, $_SESSION['user_id']]);
                } catch (PDOException $e) {
                    // Profile image column doesn't exist, skip updating it
                }
            }
            
            $_SESSION['success'] = 'Profile updated successfully!';
            header('Location: /modular/admin/profile.php');
            exit;
        } catch (PDOException $e) {
            $errors[] = 'An error occurred while updating your profile';
        }
    }
}

// Set page title and additional styles
$page_title = 'Profile';
$additional_styles = '
    <link rel="stylesheet" href="' . ADMIN_CSS_PATH . '/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="' . ADMIN_CSS_PATH . '/profile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/croppie/2.6.5/croppie.min.css">
    <style>
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1rem;
        }

        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.24);
            padding: 1.5rem;
        }

        .space-y-6 > * + * {
            margin-top: 1.5rem;
        }

        .space-y-4 > * + * {
            margin-top: 1rem;
        }

        .mt-4 {
            margin-top: 1rem;
        }

        .mt-6 {
            margin-top: 1.5rem;
        }

        .flex {
            display: flex;
        }

        .items-center {
            align-items: center;
        }

        .justify-between {
            justify-content: space-between;
        }

        .p-4 {
            padding: 1rem;
        }

        .bg-blue-50 {
            background-color: #f0f9ff;
        }

        .bg-green-50 {
            background-color: #f0fdf4;
        }

        .bg-yellow-50 {
            background-color: #fef9c3;
        }

        .bg-purple-50 {
            background-color: #f5f3ff;
        }

        .rounded-lg {
            border-radius: 0.5rem;
        }

        .text-blue-600 {
            color: #3b82f6;
        }

        .text-green-600 {
            color: #22c55e;
        }

        .text-yellow-600 {
            color: #ca8a04;
        }

        .text-purple-600 {
            color: #9333ea;
        }

        .text-sm {
            font-size: 0.875rem;
        }

        .text-lg {
            font-size: 1.125rem;
        }

        .font-semibold {
            font-weight: 600;
        }

        .hidden {
            display: none;
        }

        .h-4 {
            height: 1rem;
        }

        .w-4 {
            width: 1rem;
        }

        .ml-2 {
            margin-left: 0.5rem;
        }

        .flex-shrink-0 {
            flex-shrink: 0;
        }

        .relative {
            position: relative;
        }

        .w-32 {
            width: 8rem;
        }

        .h-32 {
            height: 8rem;
        }

        .mb-4 {
            margin-bottom: 1rem;
        }

        .rounded-full {
            border-radius: 9999px;
        }

        .object-cover {
            object-fit: cover;
        }

        .cursor-pointer {
            cursor: pointer;
        }

        .hover:bg-blue-700 {
            transition: background-color 0.2s;
        }

        .hover:bg-blue-700:hover {
            background-color: #2563eb;
        }
    </style>
';

// Set content
$content = '
    <div class="dashboard">
        <!-- Top Navigation Bar -->
        <nav class="top-nav">
            <div class="nav-left">
                <a href="/modular" target="_blank" class="nav-link">
                    <i class="fas fa-external-link-alt"></i> View Homepage
                </a>
            </div>
            <div class="nav-right">
                <a href="/modular/admin/auth/logout.php" class="nav-link">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </nav>

        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>Modular CMS</h2>
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <li>
                        <a href="/modular/admin/">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="#">
                            <i class="fas fa-plus-circle"></i>
                            <span>Add Type</span>
                        </a>
                    </li>
                    <li>
                        <a href="#">
                            <i class="fas fa-puzzle-piece"></i>
                            <span>Modules</span>
                        </a>
                    </li>
                    <li>
                        <a href="#">
                            <i class="fas fa-cog"></i>
                            <span>Settings</span>
                        </a>
                    </li>
                    <li class="active">
                        <a href="/modular/admin/profile.php">
                            <i class="fas fa-user"></i>
                            <span>Profile</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="content-header">
                <h1>Profile Settings</h1>
                <p>Manage your account information and preferences</p>
            </div>

            ' . (isset($_SESSION['success']) ? '<div class="alert alert-success mb-4">' . htmlspecialchars($_SESSION['success']) . '</div>' : '') . '
            ' . (isset($_SESSION['error']) ? '<div class="alert alert-error mb-4">' . htmlspecialchars($_SESSION['error']) . '</div>' : '') . '

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Left Column - Profile Form -->
                <div class="col-span-1">
                    <div class="card">
                        <form action="/modular/admin/profile.php" method="POST" enctype="multipart/form-data" class="space-y-6">
                            <!-- Profile Image -->
                            <div class="flex flex-col items-center">
                                <div class="relative w-32 h-32 mb-4">
                                    <img src="' . (!empty($user['profile_image']) ? '/modular/public/uploads/profiles/' . $user['profile_image'] : '/modular/admin/assets/images/default-avatar.png') . '" 
                                         alt="Profile Picture" class="w-full h-full rounded-full object-cover">
                                    <label for="profile_image" class="absolute bottom-0 right-0 bg-blue-600 text-white rounded-full w-8 h-8 flex items-center justify-center cursor-pointer hover:bg-blue-700">
                                        <i class="fas fa-camera"></i>
                                    </label>
                                </div>
                                <input type="file" id="profile_image" name="profile_image" class="hidden" accept="image/jpeg,image/png,image/webp">
                            </div>

                            <!-- Basic Information -->
                            <div class="space-y-4">
                                <div>
                                    <label for="full_name" class="block text-sm font-medium text-gray-700">Full Name</label>
                                    <input type="text" name="full_name" id="full_name" 
                                           value="' . htmlspecialchars($user['name']) . '"
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </div>

                                <div>
                                    <label for="email" class="block text-sm font-medium text-gray-700">Email address</label>
                                    <input type="email" name="email" id="email" 
                                           value="' . htmlspecialchars($user['email']) . '"
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </div>

                                <div>
                                    <label for="bio" class="block text-sm font-medium text-gray-700">Bio</label>
                                    <textarea name="bio" id="bio" rows="3"
                                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">' . htmlspecialchars($user['bio'] ?? '') . '</textarea>
                                </div>

                                <!-- Password Change Section -->
                                <div class="mt-4">
                                    <div class="flex items-center">
                                        <input type="checkbox" id="change_password" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                        <label for="change_password" class="ml-2 block text-sm font-medium text-gray-700">Change Password</label>
                                    </div>

                                    <div id="password_fields" class="mt-4 hidden">
                                        <div>
                                            <label for="current_password" class="block text-sm font-medium text-gray-700">Current Password</label>
                                            <input type="password" name="current_password" id="current_password"
                                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        </div>

                                        <div>
                                            <label for="new_password" class="block text-sm font-medium text-gray-700">New Password</label>
                                            <input type="password" name="new_password" id="new_password"
                                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        </div>

                                        <div>
                                            <label for="confirm_password" class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                                            <input type="password" name="confirm_password" id="confirm_password"
                                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-6">
                                    <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                        Update Profile
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Right Column - User Statistics -->
                <div class="col-span-1">
                    <div class="card">
                        <h2 class="text-xl font-semibold mb-4">User Statistics</h2>
                        <div class="space-y-4">
                            <div class="flex items-center justify-between p-4 bg-blue-50 rounded-lg">
                                <div>
                                    <p class="text-sm text-gray-500">Account Created</p>
                                    <p class="text-lg font-semibold text-gray-900">' . date('F j, Y', strtotime($user['created_at'])) . '</p>
                                </div>
                                <div class="flex-shrink-0">
                                    <i class="fas fa-calendar-day text-blue-600"></i>
                                </div>
                            </div>

                            <div class="flex items-center justify-between p-4 bg-green-50 rounded-lg">
                                <div>
                                    <p class="text-sm text-gray-500">Account Status</p>
                                    <p class="text-lg font-semibold text-green-800">Active</p>
                                </div>
                                <div class="flex-shrink-0">
                                    <i class="fas fa-check-circle text-green-600"></i>
                                </div>
                            </div>

                            <div class="flex items-center justify-between p-4 bg-yellow-50 rounded-lg">
                                <div>
                                    <p class="text-sm text-gray-500">Last Login</p>
                                    <p class="text-lg font-semibold text-gray-900">' . date('F j, Y g:i A', strtotime($_SESSION['last_login'] ?? $user['created_at'])) . '</p>
                                </div>
                                <div class="flex-shrink-0">
                                    <i class="fas fa-clock text-yellow-600"></i>
                                </div>
                            </div>

                            <div class="flex items-center justify-between p-4 bg-purple-50 rounded-lg">
                                <div>
                                    <p class="text-sm text-gray-500">Role</p>
                                    <p class="text-lg font-semibold text-gray-900">' . ucfirst($user['role'] ?? 'user') . '</p>
                                </div>
                                <div class="flex-shrink-0">
                                    <i class="fas fa-user-shield text-purple-600"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
';

// Add JavaScript to handle password toggle and image cropping
$additional_scripts = '
    <script src="https://cdnjs.cloudflare.com/ajax/libs/croppie/2.6.5/croppie.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Password toggle functionality
            const changePasswordCheckbox = document.getElementById("change_password");
            const passwordFields = document.getElementById("password_fields");

            if (changePasswordCheckbox && passwordFields) {
                changePasswordCheckbox.addEventListener("change", function() {
                    passwordFields.classList.toggle("hidden");
                });
            }

            // Image cropping functionality
            const profileImageInput = document.getElementById("profile_image");
            const profileImageLabel = profileImageInput.previousElementSibling;
            const profileImage = profileImageLabel.querySelector("img");

            profileImageLabel.addEventListener("click", function() {
                profileImageInput.click();
            });

            profileImageInput.addEventListener("change", function(e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(event) {
                        // Create and show modal
                        const modal = document.createElement("div");
                        modal.id = "imageModal";
                        modal.className = "fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center";
                        
                        const modalContent = `
                            <div class="bg-white rounded-lg p-6 max-w-md w-full">
                                <div id="imageCropper"></div>
                                <div class="mt-4 flex justify-end space-x-4">
                                    <button id="cancelButton" class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">Cancel</button>
                                    <button id="cropButton" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Crop</button>
                                </div>
                            </div>
                        `;
                        
                        modal.innerHTML = modalContent;
                        document.body.appendChild(modal);

                        // Initialize Croppie
                        const croppie = new Croppie(document.getElementById("imageCropper"), {
                            viewport: { width: 200, height: 200, type: "square" },
                            boundary: { width: 300, height: 300 },
                            enableExif: true,
                            enableOrientation: true
                        });

                        croppie.bind({
                            url: event.target.result
                        });

                        // Handle crop and cancel
                        document.getElementById("cropButton").addEventListener("click", function() {
                            croppie.result({
                                type: "base64",
                                size: "viewport"
                            }).then((base64) => {
                                profileImage.src = base64;
                                modal.remove();
                            });
                        });

                        document.getElementById("cancelButton").addEventListener("click", function() {
                            modal.remove();
                        });

                        // Close on outside click
                        modal.addEventListener("click", function(e) {
                            if (e.target === modal) {
                                modal.remove();
                            }
                        });
                    };
                    reader.readAsDataURL(file);
                }
            });
        });
    </script>
';

// Load the theme
require_once dirname(dirname(__FILE__)) . '/public/themes/' . DEFAULT_THEME . '/index.php';
