<?php
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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    
    // Validate name
    $name = trim($_POST['name'] ?? '');
    if (empty($name)) {
        $errors[] = 'Name is required';
    }
    
    // Validate email
    $email = trim($_POST['email'] ?? '');
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    } elseif ($email !== $user['email'] && $userData->getUserByEmail($email)) {
        $errors[] = 'Email is already in use';
    }
    
    // Handle profile picture upload
    $profile_picture = $user['profile_picture'];
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (in_array($_FILES['profile_picture']['type'], $allowed_types)) {
            $filename = 'user_' . $_SESSION['user_id'] . '_' . time() . '.' . pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
            $upload_path = __DIR__ . '/../../uploads/users/' . $filename;
            
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_path)) {
                // Delete old profile picture if exists
                if ($user['profile_picture'] && file_exists(__DIR__ . '/../../uploads/users/' . $user['profile_picture'])) {
                    unlink(__DIR__ . '/../../uploads/users/' . $user['profile_picture']);
                }
                $profile_picture = $filename;
            } else {
                $errors[] = 'Failed to upload profile picture';
            }
        } else {
            $errors[] = 'Invalid file type. Only JPEG, PNG, and GIF are allowed';
        }
    }
    
    // Handle password change
    $password = $_POST['password'] ?? '';
    $current_password = $_POST['current_password'] ?? '';
    
    if (!empty($password)) {
        if (empty($current_password)) {
            $errors[] = 'Current password is required when changing password';
        } elseif (!password_verify($current_password, $user['password'])) {
            $errors[] = 'Current password is incorrect';
        }
    }
    
    // Update user data if no errors
    if (empty($errors)) {
        $update_data = [
            'name' => $name,
            'email' => $email,
            'profile_picture' => $profile_picture
        ];
        
        if (!empty($password)) {
            $update_data['password'] = password_hash($password, PASSWORD_DEFAULT);
        }
        
        if ($userData->updateUser($_SESSION['user_id'], $update_data)) {
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;
            header('Location: /modular/admin/profile?success=1');
            exit;
        } else {
            $errors[] = 'Failed to update profile';
        }
    }
}

// Redirect back to profile page with error messages
header('Location: /modular/admin/profile?' . http_build_query(['errors' => $errors]));
exit;
