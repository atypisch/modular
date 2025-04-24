<?php
// This file will contain the form to register the first admin user.
// After the form is submitted, it will save the user into the database, with role = "admin", and email_verified = true.
// It will then log the user in automatically (session-based) and redirect to /admin/dashboard.php. 

session_start();
require_once __DIR__ . '/../../core/config-check.php';

// Check if config exists, if not redirect to step 1
if (!file_exists('../config/config.php')) {
    header('Location: step1.php');
    exit;
}

// Load config
$config = require '../config/config.php';

// Process form submission
$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate inputs
    if (empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        try {
            // Connect to database
            $conn = new PDO(
                "mysql:host={$config['db_host']};dbname={$config['db_name']}",
                $config['db_user'],
                $config['db_pass']
            );
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Check if user already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'This email is already registered.';
            } else {
                // Create admin user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare(
                    "INSERT INTO users (email, password, role, email_verified) 
                     VALUES (?, ?, 'admin', TRUE)"
                );
                if ($stmt->execute([$email, $hashed_password])) {
                    // Start session and redirect to admin dashboard
                    $_SESSION['user_id'] = $conn->lastInsertId();
                    $_SESSION['user_email'] = $email;
                    $_SESSION['user_role'] = 'admin';
                    
                    header('Location: ../index.php');
                    exit;
                } else {
                    $error = 'Failed to create admin user.';
                }
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modular CMS Setup - Step 2</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div>
                <h1 class="text-center text-3xl font-extrabold text-gray-900">
                    Modular CMS Setup
                </h1>
                <p class="mt-2 text-center text-sm text-gray-600">
                    Step 2: Create Admin User
                </p>
            </div>

            <?php if ($error): ?>
                <div class="rounded-md bg-red-50 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-red-700">
                                <?php echo htmlspecialchars($error); ?>
                            </p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" class="mt-8 space-y-6">
                <div class="rounded-md shadow-sm -space-y-px">
                    <div>
                        <label for="email" class="sr-only">Admin Email</label>
                        <input type="email" id="email" name="email" required
                               class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                               placeholder="Admin Email">
                    </div>
                    <div>
                        <label for="password" class="sr-only">Password</label>
                        <input type="password" id="password" name="password" 
                               required minlength="8"
                               class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                               placeholder="Password (minimum 8 characters)">
                    </div>
                    <div>
                        <label for="confirm_password" class="sr-only">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" 
                               required minlength="8"
                               class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                               placeholder="Confirm Password">
                    </div>
                </div>

                <div>
                    <button type="submit" class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Complete Setup
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html> 