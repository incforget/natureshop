<?php
$page_title = "Order History";
include 'includes/header.php';

// Handle GET parameters
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'created_at';
$order = isset($_GET['order']) && strtoupper($_GET['order']) === 'ASC' ? 'ASC' : 'DESC';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$min_total = isset($_GET['min_total']) ? floatval($_GET['min_total']) : '';
$max_total = isset($_GET['max_total']) ? floatval($_GET['max_total']) : '';

$orders_per_page = 10;
$offset = ($page - 1) * $orders_per_page;

$user_orders = [];
$total_orders = 0;

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    
    // Build filters array
    $filters = [];
    if (!empty($status_filter)) {
        $filters['status'] = $status_filter;
    }
    if (!empty($date_from)) {
        $filters['date_from'] = $date_from;
    }
    if (!empty($date_to)) {
        $filters['date_to'] = $date_to;
    }
    if (!empty($min_total)) {
        $filters['min_total'] = $min_total;
    }
    if (!empty($max_total)) {
        $filters['max_total'] = $max_total;
    }
    
    // Get total count for pagination
    $total_orders = getOrdersCount($user_id, $filters);
    
    // Get orders with filters
    $orders = getOrdersWithFilters($user_id, $filters, $sort, $order, $orders_per_page, $offset);
    
    foreach ($orders as $ord) {
        $ord_items = getOrderItems($ord['id']);
        $formatted_ord_items = [];
        foreach ($ord_items as $item) {
            $formatted_ord_items[] = [
                'id' => $item['product_id'],
                'name' => $item['product_name'],
                'price' => $item['price'],
                'image' => $item['product_image'],
                'quantity' => $item['quantity']
            ];
        }
        $user_orders[] = [
            'id' => $ord['id'],
            'date' => $ord['created_at'],
            'items' => $formatted_ord_items,
            'total' => $ord['total'],
            'status' => $ord['status']
        ];
    }
}

// Calculate pagination
$total_pages = ceil($total_orders / $orders_per_page);
$start_page = max(1, $page - 2);
$end_page = min($total_pages, $page + 2);

// Get unique statuses for filter dropdown
$available_statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'];
?>

    <!-- Order History Page -->
    <div class="min-h-screen bg-gradient-to-br from-gray-50 via-white to-green-50/30">
        <div class="container mx-auto px-4 py-8">
            <div class="max-w-7xl mx-auto">
                <!-- Page Header -->
                <div class="text-center mb-8 animate-fade-in">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-green-500 to-emerald-600 rounded-3xl shadow-xl mb-4 animate-bounce-subtle">
                        <i class="fas fa-history text-white text-2xl"></i>
                    </div>
                    <h1 class="text-3xl md:text-4xl font-bold text-gray-800 mb-3 gradient-text">Order History</h1>
                    <p class="text-base md:text-lg text-gray-600">Track and manage your previous orders with advanced filtering</p>
                </div>

                <?php if (!isset($_SESSION['user_id'])): ?>
                    <div class="bg-gradient-to-r from-yellow-50 to-orange-50 border-2 border-yellow-200 rounded-3xl p-6 text-center shadow-lg">
                        <div class="inline-flex items-center justify-center w-12 h-12 bg-yellow-100 rounded-2xl mb-4">
                            <i class="fas fa-exclamation-triangle text-yellow-600 text-xl"></i>
                        </div>
                        <h2 class="text-xl font-bold text-gray-800 mb-3">Access Restricted</h2>
                        <p class="text-gray-600 mb-4 text-base">Please place an order first to access your order history and account management.</p>
                        <a href="products.php" class="inline-flex items-center bg-gradient-to-r from-green-500 to-emerald-600 text-white px-6 py-3 rounded-2xl font-semibold text-base hover:from-green-600 hover:to-emerald-700 transition-all transform hover:scale-105 shadow-xl hover:shadow-2xl">
                            <i class="fas fa-shopping-cart mr-3"></i>
                            Browse Products
                        </a>
                    </div>
                <?php else: ?>
                    <!-- Filters and Controls Card -->
                    <div class="bg-white/80 backdrop-blur-sm rounded-3xl shadow-xl hover:shadow-2xl transition-all duration-500 border border-gray-100 hover:border-green-200 mb-8 overflow-hidden card-enhanced">
                        <div class="bg-gradient-to-r from-blue-500 to-indigo-600 p-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="inline-flex items-center justify-center w-10 h-10 bg-white/20 rounded-2xl mr-3">
                                        <i class="fas fa-filter text-white text-lg"></i>
                                    </div>
                                    <h2 class="text-lg md:text-xl font-bold text-white">Filters & Sorting</h2>
                                </div>
                                <button id="toggle-filters" class="inline-flex items-center bg-white/20 text-white px-3 py-2 rounded-2xl hover:bg-white/30 transition-all">
                                    <i class="fas fa-chevron-down mr-2"></i>
                                    <span id="toggle-text">Show Filters</span>
                                </button>
                            </div>
                        </div>
                        <div id="filters-content" class="p-6 hidden">
                            <form method="GET" class="space-y-3" id="filters-form">
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                    <!-- Status Filter -->
                                    <div class="space-y-2">
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                                            <i class="fas fa-circle-notch mr-2 text-blue-600"></i>Status
                                        </label>
                                        <select name="status" class="w-full border-2 border-gray-200 rounded-2xl px-4 py-3 focus:outline-none focus:ring-4 focus:ring-blue-100 focus:border-blue-500 transition-all duration-300 text-gray-800">
                                            <option value="">All Statuses</option>
                                            <?php foreach ($available_statuses as $status): ?>
                                                <option value="<?php echo $status; ?>" <?php echo $status_filter === $status ? 'selected' : ''; ?>>
                                                    <?php echo ucfirst($status); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <!-- Date From -->
                                    <div class="space-y-2">
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                                            <i class="fas fa-calendar-alt mr-2 text-green-600"></i>Date From
                                        </label>
                                        <input type="date" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>"
                                               class="w-full border-2 border-gray-200 rounded-2xl px-4 py-3 focus:outline-none focus:ring-4 focus:ring-green-100 focus:border-green-500 transition-all duration-300 text-gray-800">
                                    </div>

                                    <!-- Date To -->
                                    <div class="space-y-2">
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                                            <i class="fas fa-calendar-alt mr-2 text-green-600"></i>Date To
                                        </label>
                                        <input type="date" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>"
                                               class="w-full border-2 border-gray-200 rounded-2xl px-4 py-3 focus:outline-none focus:ring-4 focus:ring-green-100 focus:border-green-500 transition-all duration-300 text-gray-800">
                                    </div>

                                    <!-- Sort By -->
                                    <div class="space-y-2">
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                                            <i class="fas fa-sort mr-2 text-purple-600"></i>Sort By
                                        </label>
                                        <select name="sort" class="w-full border-2 border-gray-200 rounded-2xl px-4 py-3 focus:outline-none focus:ring-4 focus:ring-purple-100 focus:border-purple-500 transition-all duration-300 text-gray-800">
                                            <option value="created_at" <?php echo $sort === 'created_at' ? 'selected' : ''; ?>>Date</option>
                                            <option value="total" <?php echo $sort === 'total' ? 'selected' : ''; ?>>Total Amount</option>
                                            <option value="status" <?php echo $sort === 'status' ? 'selected' : ''; ?>>Status</option>
                                            <option value="id" <?php echo $sort === 'id' ? 'selected' : ''; ?>>Order ID</option>
                                        </select>
                                    </div>

                                    <!-- Min Total -->
                                    <div class="space-y-2">
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                                            <i class="fas fa-dollar-sign mr-2 text-emerald-600"></i>Min Total (৳)
                                        </label>
                                        <input type="number" name="min_total" value="<?php echo htmlspecialchars($min_total); ?>" step="0.01" min="0"
                                               class="w-full border-2 border-gray-200 rounded-2xl px-4 py-3 focus:outline-none focus:ring-4 focus:ring-emerald-100 focus:border-emerald-500 transition-all duration-300 text-gray-800"
                                               placeholder="0.00">
                                    </div>

                                    <!-- Max Total -->
                                    <div class="space-y-2">
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                                            <i class="fas fa-dollar-sign mr-2 text-emerald-600"></i>Max Total (৳)
                                        </label>
                                        <input type="number" name="max_total" value="<?php echo htmlspecialchars($max_total); ?>" step="0.01" min="0"
                                               class="w-full border-2 border-gray-200 rounded-2xl px-4 py-3 focus:outline-none focus:ring-4 focus:ring-emerald-100 focus:border-emerald-500 transition-all duration-300 text-gray-800"
                                               placeholder="0.00">
                                    </div>

                                    <!-- Order Direction -->
                                    <div class="space-y-2">
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                                            <i class="fas fa-arrow-up mr-2 text-indigo-600"></i>Order
                                        </label>
                                        <select name="order" class="w-full border-2 border-gray-200 rounded-2xl px-4 py-3 focus:outline-none focus:ring-4 focus:ring-indigo-100 focus:border-indigo-500 transition-all duration-300 text-gray-800">
                                            <option value="DESC" <?php echo $order === 'DESC' ? 'selected' : ''; ?>>Descending</option>
                                            <option value="ASC" <?php echo $order === 'ASC' ? 'selected' : ''; ?>>Ascending</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="flex flex-col sm:flex-row gap-3 pt-3">
                                    <button type="submit" class="bg-gradient-to-r from-blue-500 to-indigo-600 text-white px-6 py-2 rounded-2xl font-semibold hover:from-blue-600 hover:to-indigo-700 transition-all transform hover:scale-105 shadow-lg hover:shadow-xl flex items-center justify-center">
                                        <i class="fas fa-search mr-2"></i>
                                        Apply Filters
                                    </button>
                                    <a href="order_history.php" class="bg-gray-500 text-white px-6 py-2 rounded-2xl font-semibold hover:bg-gray-600 transition-all transform hover:scale-105 shadow-lg hover:shadow-xl flex items-center justify-center">
                                        <i class="fas fa-times mr-2"></i>
                                        Clear Filters
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Orders Summary -->
                    <div class="mb-6">
                        <div class="bg-white/80 backdrop-blur-sm rounded-3xl shadow-xl hover:shadow-2xl transition-all duration-500 border border-gray-100 hover:border-green-200 p-4 card-enhanced">
                            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3">
                                <div class="flex items-center">
                                    <div class="inline-flex items-center justify-center w-10 h-10 bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl mr-3">
                                        <i class="fas fa-chart-line text-white text-lg"></i>
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-bold text-gray-800">Order Summary</h3>
                                        <p class="text-gray-600">Total orders found: <span class="font-semibold text-green-600"><?php echo $total_orders; ?></span></p>
                                    </div>
                                </div>
                                <div class="text-sm text-gray-500">
                                    Page <?php echo $page; ?> of <?php echo $total_pages; ?> • Showing <?php echo count($user_orders); ?> orders
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Orders List -->
                    <div id="order-history" class="space-y-4">
                        <?php if (!empty($user_orders)): ?>
                            <?php foreach ($user_orders as $order): ?>
                                <div class="bg-white/80 backdrop-blur-sm rounded-3xl shadow-xl hover:shadow-2xl transition-all duration-500 border border-gray-100 hover:border-green-200 overflow-hidden card-enhanced">
                                    <div class="p-6">
                                        <div class="flex flex-col lg:flex-row lg:justify-between lg:items-center mb-4">
                                            <div class="mb-3 lg:mb-0">
                                                <div class="flex items-center mb-2">
                                                    <i class="fas fa-receipt text-green-600 mr-3 text-base"></i>
                                                    <h3 class="text-lg font-semibold text-gray-900">Order #<?php echo $order['id']; ?></h3>
                                                </div>
                                                <p class="text-gray-500 flex items-center">
                                                    <i class="fas fa-calendar-alt mr-2"></i>
                                                    <?php echo date('F d, Y \a\t g:i A', strtotime($order['date'])); ?>
                                                </p>
                                            </div>
                                            <div class="flex flex-col items-start lg:items-end">
                                                <p class="text-2xl font-bold text-green-600 mb-2">৳<?php echo number_format($order['total'], 2); ?></p>
                                                <?php if ($order['status']): ?>
                                                    <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium
                                                        <?php
                                                        switch(strtolower($order['status'])) {
                                                            case 'pending':
                                                                echo 'bg-yellow-100 text-yellow-800';
                                                                break;
                                                            case 'processing':
                                                                echo 'bg-blue-100 text-blue-800 animate-pulse';
                                                                break;
                                                            case 'shipped':
                                                                echo 'bg-purple-100 text-purple-800 animate-bounce';
                                                                break;
                                                            case 'delivered':
                                                                echo 'bg-green-100 text-green-800';
                                                                break;
                                                            case 'cancelled':
                                                                echo 'bg-red-100 text-red-800';
                                                                break;
                                                            case 'refunded':
                                                                echo 'bg-indigo-100 text-indigo-800';
                                                                break;
                                                            default:
                                                                echo 'bg-gray-100 text-gray-800';
                                                        }
                                                        ?>">
                                                        <i class="fas <?php
                                                            switch(strtolower($order['status'])) {
                                                                case 'pending':
                                                                    echo 'fa-clock';
                                                                    break;
                                                                case 'processing':
                                                                    echo 'fa-cog fa-spin';
                                                                    break;
                                                                case 'shipped':
                                                                    echo 'fa-truck';
                                                                    break;
                                                                case 'delivered':
                                                                    echo 'fa-check-circle';
                                                                    break;
                                                                case 'cancelled':
                                                                    echo 'fa-times-circle';
                                                                    break;
                                                                case 'refunded':
                                                                    echo 'fa-undo';
                                                                    break;
                                                                default:
                                                                    echo 'fa-circle';
                                                            }
                                                        ?> mr-2 text-xs"></i>
                                                        <?php echo ucfirst($order['status']); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <div class="border-t border-gray-100 pt-4">
                                            <div class="flex items-center justify-between mb-3">
                                                <h4 class="font-semibold text-gray-900 flex items-center">
                                                    <i class="fas fa-box mr-2 text-gray-500"></i>
                                                    Order Items (<?php echo count($order['items']); ?>)
                                                </h4>
                                            </div>
                                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                                <?php foreach ($order['items'] as $item): ?>
                                                    <div class="flex justify-between items-center py-3 px-4 bg-gradient-to-r from-gray-50 to-white rounded-2xl hover:from-gray-100 hover:to-gray-50 transition-all duration-300 border border-gray-100 hover:border-green-200 transform hover:scale-105">
                                                        <div class="flex items-center">
                                                            <div class="w-10 h-10 bg-gradient-to-br from-green-400 to-emerald-500 rounded-2xl flex items-center justify-center mr-3 shadow-lg">
                                                                <i class="fas fa-leaf text-white text-sm"></i>
                                                            </div>
                                                            <div class="min-w-0 flex-1">
                                                                <span class="font-medium text-gray-900 block truncate"><?php echo htmlspecialchars($item['name']); ?></span>
                                                                <span class="text-gray-500 text-sm">× <?php echo $item['quantity']; ?> • ৳<?php echo number_format($item['price'], 2); ?></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>

                                        <div class="mt-6 pt-4 border-t border-gray-100">
                                            <div class="flex justify-end">
                                                <a href="order_details.php?id=<?php echo $order['id']; ?>" class="inline-flex items-center bg-gradient-to-r from-green-500 to-emerald-600 text-white px-6 py-2 rounded-2xl hover:from-green-600 hover:to-emerald-700 transition-all transform hover:scale-105 shadow-lg hover:shadow-xl font-medium">
                                                    <i class="fas fa-eye mr-2"></i>
                                                    View Details
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div id="no-orders" class="text-center py-16">
                                <div class="max-w-md mx-auto">
                                    <div class="bg-white rounded-full w-20 h-20 mx-auto mb-4 flex items-center justify-center shadow-lg">
                                        <i class="fas fa-shopping-bag text-3xl text-gray-400"></i>
                                    </div>
                                    <h2 class="text-xl font-semibold text-gray-700 mb-2">No orders found</h2>
                                    <p class="text-gray-500 mb-6 leading-relaxed">
                                        <?php if (!empty($status_filter) || !empty($date_from) || !empty($date_to) || !empty($min_total) || !empty($max_total)): ?>
                                            No orders match your current filters. Try adjusting your search criteria.
                                        <?php else: ?>
                                            Your order history will appear here once you make your first purchase. Start exploring our natural products!
                                        <?php endif; ?>
                                    </p>
                                    <div class="flex flex-col sm:flex-row gap-3 justify-center">
                                        <?php if (!empty($status_filter) || !empty($date_from) || !empty($date_to) || !empty($min_total) || !empty($max_total)): ?>
                                            <a href="order_history.php" class="inline-flex items-center bg-gradient-to-r from-blue-500 to-indigo-600 text-white px-6 py-2 rounded-2xl hover:from-blue-600 hover:to-indigo-700 transition-all transform hover:scale-105 shadow-xl">
                                                <i class="fas fa-times mr-2"></i>
                                                Clear Filters
                                            </a>
                                        <?php endif; ?>
                                        <a href="products.php" class="inline-flex items-center bg-gradient-to-r from-green-500 to-emerald-600 text-white px-6 py-2 rounded-2xl hover:from-green-600 hover:to-emerald-700 transition-all transform hover:scale-105 shadow-xl">
                                            <i class="fas fa-arrow-right mr-2"></i>
                                            Browse Products
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="mt-8">
                            <div class="bg-white/80 backdrop-blur-sm rounded-3xl shadow-xl hover:shadow-2xl transition-all duration-500 border border-gray-100 hover:border-green-200 p-4 card-enhanced">
                                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3">
                                    <div class="text-sm text-gray-600">
                                        Showing page <?php echo $page; ?> of <?php echo $total_pages; ?>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <?php if ($page > 1): ?>
                                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>" class="inline-flex items-center bg-gray-100 text-gray-700 px-3 py-2 rounded-2xl hover:bg-gray-200 transition-all">
                                                <i class="fas fa-angle-double-left mr-1"></i>
                                                First
                                            </a>
                                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" class="inline-flex items-center bg-gray-100 text-gray-700 px-3 py-2 rounded-2xl hover:bg-gray-200 transition-all">
                                                <i class="fas fa-angle-left mr-1"></i>
                                                Previous
                                            </a>
                                        <?php endif; ?>

                                        <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"
                                               class="inline-flex items-center px-3 py-2 rounded-2xl transition-all <?php echo $i === $page ? 'bg-gradient-to-r from-green-500 to-emerald-600 text-white shadow-lg' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        <?php endfor; ?>

                                        <?php if ($page < $total_pages): ?>
                                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" class="inline-flex items-center bg-gray-100 text-gray-700 px-3 py-2 rounded-2xl hover:bg-gray-200 transition-all">
                                                Next
                                                <i class="fas fa-angle-right ml-1"></i>
                                            </a>
                                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $total_pages])); ?>" class="inline-flex items-center bg-gray-100 text-gray-700 px-3 py-2 rounded-2xl hover:bg-gray-200 transition-all">
                                                Last
                                                <i class="fas fa-angle-double-right ml-1"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Enhanced filter toggle with smooth animation
            const toggleFilters = document.getElementById('toggle-filters');
            const filtersContent = document.getElementById('filters-content');
            const toggleText = document.getElementById('toggle-text');
            const toggleIcon = toggleFilters.querySelector('i');

            toggleFilters.addEventListener('click', function() {
                const isHidden = filtersContent.classList.contains('hidden');
                
                if (isHidden) {
                    filtersContent.classList.remove('hidden');
                    filtersContent.classList.add('filter-content');
                    setTimeout(() => filtersContent.classList.add('animate-fade-in'), 10);
                    toggleText.textContent = 'Hide Filters';
                    toggleIcon.classList.remove('fa-chevron-down');
                    toggleIcon.classList.add('fa-chevron-up');
                } else {
                    filtersContent.classList.add('hidden');
                    filtersContent.classList.remove('filter-content');
                    toggleText.textContent = 'Show Filters';
                    toggleIcon.classList.remove('fa-chevron-up');
                    toggleIcon.classList.add('fa-chevron-down');
                }
            });

            // Auto-submit form on select changes with debouncing
            const filterSelects = document.querySelectorAll('#filters-form select');
            let filterTimeout;
            
            filterSelects.forEach(select => {
                select.addEventListener('change', function() {
                    clearTimeout(filterTimeout);
                    filterTimeout = setTimeout(() => {
                        showLoadingOverlay();
                        document.getElementById('filters-form').submit();
                    }, 500);
                });
            });

            // Enhanced pagination with loading states
            const paginationLinks = document.querySelectorAll('a[href*="page="]');
            paginationLinks.forEach(link => {
                link.classList.add('pagination-link');
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    showLoadingOverlay();
                    window.location.href = this.href;
                });
            });

            // Update cart count
            updateCartCount();

            // Add hover effects to order cards
            const orderCards = document.querySelectorAll('.card-enhanced');
            orderCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-8px) scale(1.02)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0) scale(1)';
                });
            });
        });

        function showLoadingOverlay() {
            const loadingOverlay = document.createElement('div');
            loadingOverlay.className = 'loading-overlay';
            loadingOverlay.innerHTML = `
                <div class="loading-content">
                    <div class="spinner"></div>
                    <p class="mt-4 text-gray-600 font-medium">Loading orders...</p>
                </div>
            `;
            document.body.appendChild(loadingOverlay);
        }

        function updateCartCount() {
            const cart = (typeof getCart === 'function') ? getCart() : JSON.parse(localStorage.getItem('cart') || '[]');
            const count = cart.reduce((total, item) => total + item.quantity, 0);
            
            const cartCountElements = document.querySelectorAll('.cart-count');
            cartCountElements.forEach(element => {
                element.textContent = count;
                element.style.display = count > 0 ? 'inline-flex' : 'none';
            });
        }

        // Add smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
    <script src="js/app.js"></script>
</body>
</html>