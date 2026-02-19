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
    <link href="../assets/css/install.css" rel="stylesheet">
</head>
<body class="install-page setup-step">
    <div class="min-h-screen">
        <div class="setup-card space-y-8">
            <div>
                <h1 class="setup-title">Modular CMS Setup</h1>
                <p class="setup-subtitle">Step 2: Create Admin User</p>
            </div>

            <?php if ($error): ?>
                <div class="msg-error">
                    <p><?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php endif; ?>

            <div class="install-card">
            <form method="POST" class="space-y-6">
                <div>
                    <label for="email">Admin Email</label>
                    <input type="email" id="email" name="email" required placeholder="Admin Email">
                </div>
                <div>
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required minlength="8" placeholder="Password (minimum 8 characters)">
                </div>
                <div>
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="8" placeholder="Confirm Password">
                </div>
                <div>
                    <button type="submit">Complete Setup</button>
                </div>
            </form>
            </div>
        </div>
    </div>
</body>
</html>

