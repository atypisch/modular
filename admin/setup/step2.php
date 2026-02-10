<?php
// Setup Step 2: Create first admin user and run migrations.
// - Assumes DB connection is configured in admin/config/config.php (+config.local.php)
// - On POST: run migrations (creates tables) and create an admin user, then log in and mark installed.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../core/config-check.php';

$configFile = __DIR__ . '/../config/config.php';

if (!file_exists($configFile)) {
    header('Location: step1.php');
    exit;
}

require_once $configFile;

// If we still don't have a DB connection, redirect back to step1
if (!isset($GLOBALS['db'])) {
    $_SESSION['error'] = 'Database connection is not configured. Please complete Step 1 first.';
    header('Location: step1.php');
    exit;
}

$db = $GLOBALS['db'];

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email            = $_POST['email'] ?? '';
    $password         = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($email === '' || $password === '' || $confirm_password === '') {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        try {
            // 1) Run core migrations (creates all tables)
            require_once CORE_PATH . '/functions/database.php';
            if (function_exists('run_pending_migrations')) {
                $result = run_pending_migrations();
                if (!empty($result['error'])) {
                    throw new Exception('Migration error: ' . $result['error']);
                }
            }

            // 2) Ensure there is no existing user with this email
            $stmt = $db->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            if ($stmt->fetchColumn()) {
                throw new Exception('This email is already registered.');
            }

            // 3) Create admin user
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $username = strstr($email, '@', true) ?: $email;

            $stmt = $db->prepare("
                INSERT INTO users (username, email, password, status, email_verified, created_at, updated_at)
                VALUES (?, ?, ?, 'active', 1, NOW(), NOW())
            ");
            $stmt->execute([$username, $email, $hashed]);
            $userId = (int) $db->lastInsertId();

            // 4) Attach admin role if roles table exists
            try {
                $roleId = $db->query("SELECT id FROM roles WHERE name = 'admin' LIMIT 1")->fetchColumn();
                if ($roleId) {
                    $linkStmt = $db->prepare("INSERT IGNORE INTO user_roles (user_id, role_id) VALUES (?, ?)");
                    $linkStmt->execute([$userId, $roleId]);
                }
            } catch (Throwable $ignored) {
                // If roles table doesn't exist for some reason, we still proceed;
                // is_admin() will fall back to session role.
            }

            // 5) Mark system as installed
            $installedPath = __DIR__ . '/../config/installed.php';
            @file_put_contents($installedPath, "<?php\nreturn true;\n");

            // 6) Log the user in as admin
            $_SESSION['user_id']    = $userId;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_name']  = $username;
            $_SESSION['user_role']  = 'admin';

            header('Location: ../index.php');
            exit;
        } catch (Throwable $e) {
            $error = $e->getMessage();
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
                        <input type="password" id="password" name="password" required minlength="8"
                               class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                               placeholder="Password (minimum 8 characters)">
                    </div>
                    <div>
                        <label for="confirm_password" class="sr-only">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required minlength="8"
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

