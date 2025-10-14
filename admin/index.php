<?php
session_start();
include_once '../includes/config.php';
include_once 'includes/functions.php';

// Redirect if already logged in
if (isAdminLoggedIn()) {
    header('Location: admin/dashboard.php');
    exit;
}

$login_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $login_error = 'Please enter both username and password.';
    } else {
        $result = loginAdmin($username, $password);
        if ($result['success']) {
            header('Location: dashboard.php');
            exit;
        } else {
            $login_error = $result['message'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - NatureBD</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .login-bg {
            background: linear-gradient(135deg, #10b981 0%, #059669 50%, #047857 100%);
        }
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
    </style>
</head>
<body class="login-bg min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full">
        <!-- Logo and Title -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-white rounded-full shadow-lg mb-4">
                <i class="fas fa-leaf text-green-600 text-3xl"></i>
            </div>
            <h1 class="text-3xl font-bold text-white mb-2">NatureBD Admin</h1>
            <p class="text-green-100">Management Panel Login</p>
        </div>

        <!-- Login Form -->
        <div class="login-card rounded-2xl shadow-2xl p-8">
            <form method="POST" action="">
                <?php if ($login_error): ?>
                    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <?php echo $login_error; ?>
                    </div>
                <?php endif; ?>

                <div class="space-y-6">
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-user mr-2"></i>Username
                        </label>
                        <input type="text"
                               id="username"
                               name="username"
                               required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition-colors"
                               placeholder="Enter your username">
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-lock mr-2"></i>Password
                        </label>
                        <input type="password"
                               id="password"
                               name="password"
                               required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition-colors"
                               placeholder="Enter your password">
                    </div>

                    <button type="submit"
                            class="w-full bg-green-600 text-white py-3 px-4 rounded-lg hover:bg-green-700 focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors font-medium">
                        <i class="fas fa-sign-in-alt mr-2"></i>Login to Admin Panel
                    </button>
                </div>
            </form>

            <!-- Default Credentials Info -->
            <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <h3 class="text-sm font-medium text-blue-800 mb-2">
                    <i class="fas fa-info-circle mr-1"></i>Default Credentials
                </h3>
                <p class="text-sm text-blue-700">
                    <strong>Username:</strong> admin<br>
                    <strong>Password:</strong> admin123
                </p>
            </div>
        </div>

        <!-- Back to Site Link -->
        <div class="text-center mt-6">
            <a href="../index.php" class="text-green-100 hover:text-white transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>Back to NatureBD
            </a>
        </div>
    </div>

    <script>
        // Focus on username field
        document.getElementById('username').focus();

        // Auto-hide error message after 5 seconds
        setTimeout(function() {
            const errorDiv = document.querySelector('.bg-red-50');
            if (errorDiv) {
                errorDiv.style.transition = 'opacity 0.5s';
                errorDiv.style.opacity = '0';
                setTimeout(() => errorDiv.remove(), 500);
            }
        }, 5000);
    </script>
</body>
</html>