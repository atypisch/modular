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
    $stmt = $db->prepare("SELECT id, name, email, bio, profile_image FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    // If bio column doesn't exist, initialize it as empty
    $user = [
        'id' => $_SESSION['user_id'],
        'name' => '',
        'email' => '',
        'bio' => '',
        'profile_image' => ''
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

            <div class="card">
                <form action="/modular/admin/profile.php" method="POST" enctype="multipart/form-data" class="space-y-6">
                    <!-- Profile Image -->
                    <div class="flex flex-col items-center">
                        <div class="relative w-32 h-32 mb-4">
                            <img src="' . (!empty($user['profile_image']) ? '/modular/public/uploads/profiles/' . $user['profile_image'] : '/modular/public/images/default-avatar.png') . '" 
                                 alt="Profile" 
                                 class="w-full h-full rounded-full object-cover">
                            <label for="profile_image" class="absolute bottom-0 right-0 bg-blue-500 text-white px-2 py-1 rounded cursor-pointer">
                                Change
                            </label>
                        </div>
                        <input type="file" id="profile_image" name="profile_image" class="hidden" accept="image/jpeg,image/png,image/webp">
                    </div>

                    <!-- Full Name -->
                    <div class="form-group">
                        <label for="full_name" class="block text-sm font-medium text-gray-700">Full Name</label>
                        <input type="text" 
                               id="full_name" 
                               name="full_name" 
                               value="' . htmlspecialchars($user['name']) . '" 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                               required>
                    </div>

                    <!-- Email -->
                    <div class="form-group">
                        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" 
                               id="email" 
                               name="email" 
                               value="' . htmlspecialchars($user['email']) . '" 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                               required>
                    </div>

                    <!-- Bio -->
                    <div class="form-group">
                        <label for="bio" class="block text-sm font-medium text-gray-700">Biography</label>
                        <textarea id="bio" 
                                  name="bio" 
                                  rows="4" 
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                  placeholder="Write something about yourself...">' . htmlspecialchars($user['bio'] ?? '') . '</textarea>
                    </div>

                    <!-- Password Change Section -->
                    <div class="border-t border-gray-200 pt-6">
                        <h2 class="text-lg font-medium mb-4">Change Password</h2>
                        
                        <div class="form-group">
                            <label for="current_password" class="block text-sm font-medium text-gray-700">Current Password</label>
                            <input type="password" 
                                   id="current_password" 
                                   name="current_password" 
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <div class="form-group">
                            <label for="new_password" class="block text-sm font-medium text-gray-700">New Password</label>
                            <input type="password" 
                                   id="new_password" 
                                   name="new_password" 
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <div class="form-group">
                            <label for="confirm_password" class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                            <input type="password" 
                                   id="confirm_password" 
                                   name="confirm_password" 
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                    </div>

                    <div class="mt-6">
                        <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Update Profile
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>
';

// Load the theme
require_once dirname(dirname(__FILE__)) . '/public/themes/' . DEFAULT_THEME . '/index.php';
