<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>NatureBD Admin Panel</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom Admin Styles -->
    <link rel="stylesheet" href="css/admin.css">
</head>
<body class="bg-gray-100">
    <?php
    // Session and config are now handled in the main PHP file before including this header
    // include_once '../includes/config.php';
    // include_once 'includes/functions.php';
    // requireAdminLogin(); // Moved to main PHP file
    ?>

    <div class="flex h-screen overflow-hidden">
        <!-- Mobile Menu Overlay -->
        <div class="mobile-menu-overlay no-print" id="mobile-menu-overlay"></div>

        <!-- Sidebar -->
        <div class="admin-sidebar w-64 no-print" id="sidebar">
            <div class="p-6 border-b border-gray-600">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-green-500 rounded-lg flex items-center justify-center">
                        <i class="fas fa-cog text-white"></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold">NatureBD Admin</h1>
                        <p class="text-sm text-gray-300">Management Panel</p>
                    </div>
                </div>
            </div>

            <nav class="mt-6">
                <div class="px-4 space-y-2">
                    <a href="dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?> flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-300 hover:text-white">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>

                    <a href="products.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : ''; ?> flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-300 hover:text-white">
                        <i class="fas fa-box"></i>
                        <span>Products</span>
                    </a>

                    <a href="categories.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : ''; ?> flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-300 hover:text-white">
                        <i class="fas fa-tags"></i>
                        <span>Categories</span>
                    </a>

                    <a href="orders.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : ''; ?> flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-300 hover:text-white">
                        <i class="fas fa-shopping-cart"></i>
                        <span>Orders</span>
                    </a>

                    <a href="users.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?> flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-300 hover:text-white">
                        <i class="fas fa-users"></i>
                        <span>Users</span>
                    </a>

                    <a href="banners.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'banners.php' ? 'active' : ''; ?> flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-300 hover:text-white">
                        <i class="fas fa-image"></i>
                        <span>Banners</span>
                    </a>

                    <a href="promo_codes.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'promo_codes.php' ? 'active' : ''; ?> flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-300 hover:text-white">
                        <i class="fas fa-percent"></i>
                        <span>Promo Codes</span>
                    </a>
                </div>
            </nav>

            <div class="absolute bottom-0 w-64 p-4 border-t border-gray-600">
                <div class="flex items-center space-x-3 mb-3">
                    <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                        <i class="fas fa-user text-white text-sm"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium"><?php echo isset($_SESSION['admin_username']) ? $_SESSION['admin_username'] : 'Admin'; ?></p>
                        <p class="text-xs text-gray-400"><?php echo isset($_SESSION['admin_role']) ? ucfirst($_SESSION['admin_role']) : 'User'; ?></p>
                    </div>
                </div>
                <a href="logout.php" class="flex items-center space-x-3 px-4 py-2 rounded-lg text-gray-300 hover:text-white hover:bg-red-600 transition-colors">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Top Bar -->
            <header class="bg-white shadow-sm border-b border-gray-200 px-4 md:px-6 py-4 no-print">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <!-- Mobile Menu Button -->
                        <button class="md:hidden p-2 rounded-lg text-gray-600 hover:text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500" id="mobile-menu-button">
                            <i class="fas fa-bars text-xl"></i>
                        </button>
                        <h2 class="text-xl md:text-2xl font-semibold text-gray-800">
                            <?php echo isset($page_title) ? $page_title : 'Dashboard'; ?>
                        </h2>
                    </div>
                    <div class="text-sm text-gray-500 hidden sm:block">
                        <?php echo date('l, F j, Y'); ?>
                    </div>
                </div>
            </header>

            <!-- Content Area -->
            <main class="flex-1 overflow-y-auto p-4 md:p-6 admin-content min-h-0">