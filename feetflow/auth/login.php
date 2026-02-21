<?php
/**
 * FleetFlow – Login Page
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/helpers.php';

// If already logged in, go to dashboard
if (isLoggedIn()) {
    redirect(baseUrl() . '/dashboard/');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE username = :u LIMIT 1");
        $stmt->execute([':u' => $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Set session
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role']      = $user['role'];

            redirect(baseUrl() . '/dashboard/');
        } else {
            $error = 'Invalid username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login – FleetFlow</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="<?= baseUrl() ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="login-page">
    <div class="login-card fade-in-up">
        <div class="logo">
            <i class="bi bi-truck text-accent"></i> Fleet<span class="text-accent">Flow</span>
        </div>
        <p class="subtitle">Modular Fleet & Logistics Management</p>

        <?php if ($error): ?>
            <div class="alert alert-danger py-2 px-3" style="font-size:0.85rem;">
                <i class="bi bi-exclamation-circle me-1"></i><?= sanitize($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="ff-form" autocomplete="off">
            <div class="mb-3">
                <label class="form-label" for="username">Username</label>
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-end-0" style="border-color: var(--ff-border);">
                        <i class="bi bi-person text-muted"></i>
                    </span>
                    <input type="text" class="form-control border-start-0" id="username" name="username"
                           value="<?= sanitize($_POST['username'] ?? '') ?>" placeholder="Enter username" required autofocus>
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label" for="password">Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-end-0" style="border-color: var(--ff-border);">
                        <i class="bi bi-lock text-muted"></i>
                    </span>
                    <input type="password" class="form-control border-start-0" id="password" name="password"
                           placeholder="Enter password" required>
                </div>
            </div>
            <button type="submit" class="btn btn-ff w-100 py-2">
                <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
            </button>
        </form>

        <div class="mt-4 p-3" style="background: var(--ff-surface-elevated); border-radius: 8px;">
            <p class="mb-1" style="font-size:0.75rem; color:#64748b; text-transform:uppercase; letter-spacing:0.05em;">Demo Credentials</p>
            <table class="table table-sm table-borderless mb-0" style="font-size:0.8rem;">
                <tr><td class="text-muted py-0">Fleet Manager</td><td class="py-0"><code>manager1</code></td></tr>
                <tr><td class="text-muted py-0">Dispatcher</td><td class="py-0"><code>dispatcher1</code></td></tr>
                <tr><td class="text-muted py-0">Safety Officer</td><td class="py-0"><code>safety1</code></td></tr>
                <tr><td class="text-muted py-0">Financial Analyst</td><td class="py-0"><code>analyst1</code></td></tr>
                <tr><td class="text-muted py-0">Password (all)</td><td class="py-0"><code>password123</code></td></tr>
            </table>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
