<?php
// Start session and include config first, before any HTML output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once '../includes/config.php';
include_once 'includes/functions.php';

// Check admin authentication before any HTML output
requireAdminLogin();

// Now it's safe to include header and output HTML
$page_title = 'Dashboard';
include 'includes/header.php';
$stats = getAdminStats();
$recent_orders = getAllRecentOrders(5);
?>

<!-- Welcome Section -->
<div class="bg-gradient-to-r from-blue-600 via-purple-600 to-indigo-700 rounded-xl shadow-lg p-4 md:p-6 lg:p-8 mb-6 md:mb-8 text-white">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
        <div class="mb-4 lg:mb-0">
            <h1 class="text-xl md:text-2xl lg:text-3xl font-bold mb-2">Welcome back, <?php echo htmlspecialchars($_SESSION['admin_username']); ?>!</h1>
            <p class="text-blue-100 text-sm md:text-base lg:text-lg">Here's what's happening with your store today.</p>
        </div>
        <div class="flex flex-col sm:flex-row sm:items-center space-y-2 sm:space-y-0 sm:space-x-4">
            <div class="text-left sm:text-right order-2 sm:order-1">
                <p class="text-xs md:text-sm text-blue-100"><?php echo date('l, F j, Y'); ?></p>
                <p class="text-xs md:text-sm text-blue-100"><?php echo date('g:i A'); ?></p>
            </div>
            <div class="w-10 h-10 md:w-12 md:h-12 lg:w-16 lg:h-16 bg-white/20 rounded-full flex items-center justify-center backdrop-blur-sm order-1 sm:order-2">
                <i class="fas fa-chart-line text-lg md:text-xl lg:text-2xl"></i>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 mb-6 md:mb-8">
    <!-- Total Products -->
    <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl shadow-lg p-4 md:p-6 border border-blue-200 dashboard-card fade-in">
        <div class="flex items-center justify-between">
            <div class="flex-1">
                <p class="text-xs md:text-sm font-semibold text-blue-600 uppercase tracking-wide">Total Products</p>
                <p class="text-2xl md:text-3xl font-bold text-gray-900 mt-2 stat-number"><?php echo number_format($stats['total_products']); ?></p>
                <p class="text-xs text-blue-500 mt-1">Active listings</p>
            </div>
            <div class="w-10 h-10 md:w-14 md:h-14 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center shadow-lg stat-icon ml-3 md:ml-0">
                <i class="fas fa-box text-white text-lg md:text-xl"></i>
            </div>
        </div>
    </div>

    <!-- Total Orders -->
    <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl shadow-lg p-4 md:p-6 border border-green-200 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 dashboard-card fade-in">
        <div class="flex items-center justify-between">
            <div class="flex-1">
                <p class="text-xs md:text-sm font-semibold text-green-600 uppercase tracking-wide">Total Orders</p>
                <p class="text-2xl md:text-3xl font-bold text-gray-900 mt-2"><?php echo number_format($stats['total_orders']); ?></p>
                <p class="text-xs text-green-500 mt-1">All time</p>
            </div>
            <div class="w-10 h-10 md:w-14 md:h-14 bg-gradient-to-br from-green-500 to-green-600 rounded-xl flex items-center justify-center shadow-lg ml-3 md:ml-0">
                <i class="fas fa-shopping-cart text-white text-lg md:text-xl"></i>
            </div>
        </div>
    </div>

    <!-- Total Revenue -->
    <div class="bg-gradient-to-br from-yellow-50 to-orange-100 rounded-xl shadow-lg p-4 md:p-6 border border-yellow-200 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 dashboard-card fade-in">
        <div class="flex items-center justify-between">
            <div class="flex-1">
                <p class="text-xs md:text-sm font-semibold text-yellow-600 uppercase tracking-wide">Total Revenue</p>
                <p class="text-2xl md:text-3xl font-bold text-gray-900 mt-2">৳<?php echo number_format($stats['total_revenue'], 2); ?></p>
                <p class="text-xs text-yellow-500 mt-1">Lifetime earnings</p>
            </div>
            <div class="w-10 h-10 md:w-14 md:h-14 bg-gradient-to-br from-yellow-500 to-orange-500 rounded-xl flex items-center justify-center shadow-lg ml-3 md:ml-0">
                <i class="fas fa-dollar-sign text-white text-lg md:text-xl"></i>
            </div>
        </div>
    </div>

    <!-- Total Users -->
    <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl shadow-lg p-4 md:p-6 border border-purple-200 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 dashboard-card fade-in">
        <div class="flex items-center justify-between">
            <div class="flex-1">
                <p class="text-xs md:text-sm font-semibold text-purple-600 uppercase tracking-wide">Total Users</p>
                <p class="text-2xl md:text-3xl font-bold text-gray-900 mt-2"><?php echo number_format($stats['total_users']); ?></p>
                <p class="text-xs text-purple-500 mt-1">Registered customers</p>
            </div>
            <div class="w-10 h-10 md:w-14 md:h-14 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg ml-3 md:ml-0">
                <i class="fas fa-users text-white text-lg md:text-xl"></i>
            </div>
        </div>
    </div>
</div>

<!-- Additional Stats -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-6 mb-6 md:mb-8">
    <!-- Today's Orders -->
    <div class="bg-gradient-to-br from-indigo-50 to-indigo-100 rounded-xl shadow-lg p-4 md:p-6 border border-indigo-200 hover:shadow-xl transition-all duration-300">
        <div class="flex items-center justify-between">
            <div class="flex-1">
                <p class="text-xs md:text-sm font-semibold text-indigo-600 uppercase tracking-wide">Today's Orders</p>
                <p class="text-xl md:text-2xl font-bold text-gray-900 mt-2"><?php echo number_format($stats['today_orders']); ?></p>
                <div class="flex items-center mt-1">
                    <i class="fas fa-arrow-up text-green-500 text-xs mr-1"></i>
                    <span class="text-xs text-green-500">+12% from yesterday</span>
                </div>
            </div>
            <div class="w-8 h-8 md:w-12 md:h-12 bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-lg flex items-center justify-center shadow-md ml-3 md:ml-0">
                <i class="fas fa-calendar-day text-white text-sm md:text-base"></i>
            </div>
        </div>
    </div>

    <!-- Low Stock Alert -->
    <div class="bg-gradient-to-br from-red-50 to-red-100 rounded-xl shadow-lg p-4 md:p-6 border border-red-200 hover:shadow-xl transition-all duration-300">
        <div class="flex items-center justify-between">
            <div class="flex-1">
                <p class="text-xs md:text-sm font-semibold text-red-600 uppercase tracking-wide">Low Stock Items</p>
                <p class="text-xl md:text-2xl font-bold text-gray-900 mt-2"><?php echo number_format($stats['low_stock']); ?></p>
                <div class="flex items-center mt-1">
                    <span class="text-xs text-red-500">Requires attention</span>
                </div>
            </div>
            <div class="w-8 h-8 md:w-12 md:h-12 bg-gradient-to-br from-red-500 to-red-600 rounded-lg flex items-center justify-center shadow-md ml-3 md:ml-0">
                <i class="fas fa-exclamation-triangle text-white text-sm md:text-base"></i>
            </div>
        </div>
    </div>

    <!-- Total Categories -->
    <div class="bg-gradient-to-br from-teal-50 to-teal-100 rounded-xl shadow-lg p-4 md:p-6 border border-teal-200 hover:shadow-xl transition-all duration-300">
        <div class="flex items-center justify-between">
            <div class="flex-1">
                <p class="text-xs md:text-sm font-semibold text-teal-600 uppercase tracking-wide">Categories</p>
                <p class="text-xl md:text-2xl font-bold text-gray-900 mt-2"><?php echo number_format($stats['total_categories']); ?></p>
                <div class="flex items-center mt-1">
                    <span class="text-xs text-teal-500">Product categories</span>
                </div>
            </div>
            <div class="w-8 h-8 md:w-12 md:h-12 bg-gradient-to-br from-teal-500 to-teal-600 rounded-lg flex items-center justify-center shadow-md ml-3 md:ml-0">
                <i class="fas fa-tags text-white text-sm md:text-base"></i>
            </div>
        </div>
    </div>
</div>

<!-- Recent Orders -->
<div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-200">
    <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-4 md:px-6 py-4 md:py-5 border-b border-gray-200">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-2 sm:space-y-0">
            <div class="flex items-center space-x-3">
                <div class="w-8 h-8 md:w-10 md:h-10 bg-blue-500 rounded-lg flex items-center justify-center">
                    <i class="fas fa-shopping-bag text-white text-sm md:text-base"></i>
                </div>
                <div>
                    <h3 class="text-base md:text-lg font-bold text-gray-900">Recent Orders</h3>
                    <p class="text-xs md:text-sm text-gray-500">Latest customer orders</p>
                </div>
            </div>
            <a href="orders.php" class="inline-flex items-center px-3 md:px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors duration-200 shadow-md hover:shadow-lg">
                <i class="fas fa-arrow-right mr-2"></i>
                <span class="hidden sm:inline">View All Orders</span>
                <span class="sm:hidden">View All</span>
            </a>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full min-w-full md:min-w-0">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-3 md:px-6 py-3 md:py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Order ID</th>
                    <th class="px-3 md:px-6 py-3 md:py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Customer</th>
                    <th class="px-3 md:px-6 py-3 md:py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Total</th>
                    <th class="px-3 md:px-6 py-3 md:py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider hidden sm:table-cell">Status</th>
                    <th class="px-3 md:px-6 py-3 md:py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider hidden md:table-cell">Date</th>
                    <th class="px-3 md:px-6 py-3 md:py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($recent_orders)): ?>
                    <tr>
                        <td colspan="6" class="px-4 md:px-6 py-8 md:py-12 text-center">
                            <div class="flex flex-col items-center">
                                <i class="fas fa-shopping-cart text-gray-300 text-3xl md:text-4xl mb-3"></i>
                                <p class="text-gray-500 text-sm md:text-lg">No orders found</p>
                                <p class="text-gray-400 text-xs md:text-sm">Recent orders will appear here</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recent_orders as $order): ?>
                        <tr class="hover:bg-gray-50 transition-colors duration-150 table-row">
                            <td class="px-3 md:px-6 py-3 md:py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-6 h-6 md:w-8 md:h-8 bg-blue-100 rounded-full flex items-center justify-center mr-2 md:mr-3">
                                        <i class="fas fa-hashtag text-blue-600 text-xs"></i>
                                    </div>
                                    <span class="text-xs md:text-sm font-semibold text-gray-900">#<?php echo $order['id']; ?></span>
                                </div>
                            </td>
                            <td class="px-3 md:px-6 py-3 md:py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-6 h-6 md:w-8 md:h-8 bg-gray-200 rounded-full flex items-center justify-center mr-2 md:mr-3">
                                        <i class="fas fa-user text-gray-600 text-xs"></i>
                                    </div>
                                    <span class="text-xs md:text-sm text-gray-900"><?php echo htmlspecialchars($order['user_name']); ?></span>
                                </div>
                            </td>
                            <td class="px-3 md:px-6 py-3 md:py-4 whitespace-nowrap text-xs md:text-sm font-semibold text-gray-900">
                                ৳<?php echo number_format($order['total'], 2); ?>
                            </td>
                            <td class="px-3 md:px-6 py-3 md:py-4 whitespace-nowrap hidden sm:table-cell">
                                <?php echo getOrderStatusBadge($order['status']); ?>
                            </td>
                            <td class="px-3 md:px-6 py-3 md:py-4 whitespace-nowrap text-xs text-gray-500 hidden md:table-cell">
                                <div class="flex flex-col">
                                    <span><?php echo date('M j, Y', strtotime($order['created_at'])); ?></span>
                                    <span class="text-xs text-gray-400"><?php echo date('g:i A', strtotime($order['created_at'])); ?></span>
                                </div>
                            </td>
                            <td class="px-3 md:px-6 py-3 md:py-4 whitespace-nowrap text-xs md:text-sm font-medium">
                                <a href="orders.php?action=view&id=<?php echo $order['id']; ?>" class="inline-flex items-center px-2 md:px-3 py-1 bg-blue-50 hover:bg-blue-100 text-blue-700 hover:text-blue-800 rounded-md transition-colors duration-150">
                                    <i class="fas fa-eye mr-1"></i>
                                    <span class="hidden sm:inline">View</span>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Quick Actions -->
<div class="mt-6 md:mt-8 bg-gradient-to-br from-white to-gray-50 rounded-xl shadow-lg p-4 md:p-8 border border-gray-200">
    <div class="mb-4 md:mb-6">
        <div class="flex items-center space-x-3 mb-2">
            <div class="w-8 h-8 md:w-10 md:h-10 bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg flex items-center justify-center shadow-md">
                <i class="fas fa-bolt text-white text-sm md:text-base"></i>
            </div>
            <div>
                <h3 class="text-base md:text-xl font-bold text-gray-900">Quick Actions</h3>
                <p class="text-xs md:text-sm text-gray-500">Common administrative tasks</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 md:gap-6">
        <a href="site_settings.php" class="group bg-gradient-to-br from-emerald-50 to-emerald-100 p-4 md:p-6 rounded-xl border border-emerald-200 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 hover:border-emerald-300">
            <div class="flex items-center space-x-3 md:space-x-4">
                <div class="w-10 h-10 md:w-14 md:h-14 bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl flex items-center justify-center shadow-lg group-hover:shadow-xl transition-shadow duration-300">
                    <i class="fas fa-cog text-white text-lg md:text-xl"></i>
                </div>
                <div class="flex-1">
                    <p class="font-bold text-emerald-900 text-base md:text-lg">Site Settings</p>
                    <p class="text-xs md:text-sm text-emerald-700 mt-1">Edit site-wide header/footer information</p>
                </div>
            </div>
        </a>
        <a href="products.php?action=add" class="group bg-gradient-to-br from-blue-50 to-blue-100 p-4 md:p-6 rounded-xl border border-blue-200 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 hover:border-blue-300">
            <div class="flex items-center space-x-3 md:space-x-4">
                <div class="w-10 h-10 md:w-14 md:h-14 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center shadow-lg group-hover:shadow-xl transition-shadow duration-300">
                    <i class="fas fa-plus text-white text-lg md:text-xl"></i>
                </div>
                <div class="flex-1">
                    <p class="font-bold text-blue-900 text-base md:text-lg">Add New Product</p>
                    <p class="text-xs md:text-sm text-blue-700 mt-1">Create a new product listing</p>
                </div>
            </div>
        </a>

        <a href="categories.php?action=add" class="group bg-gradient-to-br from-green-50 to-green-100 p-4 md:p-6 rounded-xl border border-green-200 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 hover:border-green-300">
            <div class="flex items-center space-x-3 md:space-x-4">
                <div class="w-10 h-10 md:w-14 md:h-14 bg-gradient-to-br from-green-500 to-green-600 rounded-xl flex items-center justify-center shadow-lg group-hover:shadow-xl transition-shadow duration-300">
                    <i class="fas fa-tag text-white text-lg md:text-xl"></i>
                </div>
                <div class="flex-1">
                    <p class="font-bold text-green-900 text-base md:text-lg">Add New Category</p>
                    <p class="text-xs md:text-sm text-green-700 mt-1">Create a new product category</p>
                </div>
            </div>
        </a>

        <a href="promo_codes.php?action=add" class="group bg-gradient-to-br from-purple-50 to-purple-100 p-4 md:p-6 rounded-xl border border-purple-200 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 hover:border-purple-300">
            <div class="flex items-center space-x-3 md:space-x-4">
                <div class="w-10 h-10 md:w-14 md:h-14 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg group-hover:shadow-xl transition-shadow duration-300">
                    <i class="fas fa-percent text-white text-lg md:text-xl"></i>
                </div>
                <div class="flex-1">
                    <p class="font-bold text-purple-900 text-base md:text-lg">Create Promo Code</p>
                    <p class="text-xs md:text-sm text-purple-700 mt-1">Add discount codes for customers</p>
                </div>
            </div>
        </a>
    </div>

    <!-- Additional Quick Links -->
    <div class="mt-6 md:mt-8 pt-4 md:pt-6 border-t border-gray-200">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-2 md:gap-4">
            <a href="orders.php" class="flex items-center justify-center p-2 md:p-3 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors duration-200 group">
                <i class="fas fa-list text-gray-600 group-hover:text-gray-800 mr-1 md:mr-2 text-sm md:text-base"></i>
                <span class="text-xs md:text-sm font-medium text-gray-700 group-hover:text-gray-900">All Orders</span>
            </a>
            <a href="users.php" class="flex items-center justify-center p-2 md:p-3 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors duration-200 group">
                <i class="fas fa-users text-gray-600 group-hover:text-gray-800 mr-1 md:mr-2 text-sm md:text-base"></i>
                <span class="text-xs md:text-sm font-medium text-gray-700 group-hover:text-gray-900">Manage Users</span>
            </a>
            <a href="banners.php" class="flex items-center justify-center p-2 md:p-3 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors duration-200 group">
                <i class="fas fa-image text-gray-600 group-hover:text-gray-800 mr-1 md:mr-2 text-sm md:text-base"></i>
                <span class="text-xs md:text-sm font-medium text-gray-700 group-hover:text-gray-900">Banners</span>
            </a>
            <a href="products.php" class="flex items-center justify-center p-2 md:p-3 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors duration-200 group">
                <i class="fas fa-boxes text-gray-600 group-hover:text-gray-800 mr-1 md:mr-2 text-sm md:text-base"></i>
                <span class="text-xs md:text-sm font-medium text-gray-700 group-hover:text-gray-900">All Products</span>
            </a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>