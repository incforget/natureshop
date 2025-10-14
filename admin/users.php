<?php
$page_title = 'User Management';
include_once '../includes/config.php';
include_once 'includes/functions.php';
requireAdminLogin();
include_once '../includes/functions.php';

// Get users with pagination
// Pagination and input handling
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';

// CSV export support
$export_csv = isset($_GET['export']) && $_GET['export'] === 'csv';

$sql = "SELECT u.*, COUNT(o.id) as order_count, IFNULL(SUM(o.total),0) as total_spent FROM users u LEFT JOIN orders o ON u.id = o.user_id WHERE 1=1";
$params = [];
$types = '';

if ($search) {
    $sql .= " AND (u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= 'sss';
}

$sql .= " GROUP BY u.id";

// Sorting: allow only specific columns and directions
$sortParam = isset($_GET['sort']) ? sanitizeInput($_GET['sort']) : '';
$orderParam = isset($_GET['order']) ? strtolower(sanitizeInput($_GET['order'])) : 'desc';
$allowed_sorts = [
    'name' => 'u.name',
    'email' => 'u.email',
    'phone' => 'u.phone',
    'orders' => 'order_count',
    'total' => 'total_spent',
    'created' => 'u.created_at',
];

if ($sortParam && isset($allowed_sorts[$sortParam])) {
    $dir = $orderParam === 'asc' ? 'ASC' : 'DESC';
    $orderBy = $allowed_sorts[$sortParam] . ' ' . $dir;
} else {
    $orderBy = 'u.created_at DESC';
    // normalize empty params
    $sortParam = '';
    $orderParam = '';
}

$sql .= " ORDER BY " . $orderBy;

// If exporting, remove limit; otherwise paginate
if (!$export_csv) {
    $sql .= " LIMIT ? OFFSET ?";
    $params[] = $per_page;
    $params[] = $offset;
    $types .= 'ii';
}

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get total count for pagination
$sql_count = "SELECT COUNT(*) as total FROM users WHERE 1=1";
$params_count = [];
$types_count = '';

if ($search) {
    $sql_count .= " AND (name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $params_count[] = "%$search%";
    $params_count[] = "%$search%";
    $params_count[] = "%$search%";
    $types_count .= 'sss';
}

$stmt_count = $conn->prepare($sql_count);
if ($params_count) {
    $stmt_count->bind_param($types_count, ...$params_count);
}
$stmt_count->execute();
$total_users = (int)$stmt_count->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_users / $per_page);

// Get user details for viewing
$view_user = null;
$user_orders = [];
if (isset($_GET['action']) && $_GET['action'] === 'view' && isset($_GET['id'])) {
    $user_id = (int)$_GET['id'];
    $view_user = getUserById($user_id);
    if ($view_user) {
        $stmt_orders = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
        $stmt_orders->bind_param("i", $user_id);
        $stmt_orders->execute();
        $user_orders = $stmt_orders->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

// Handle CSV export: stream CSV and exit
if ($export_csv) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=users_export_' . date('Ymd_His') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Name', 'Email', 'Phone', 'Orders', 'Total Spent', 'Joined']);
    foreach ($users as $u) {
        fputcsv($output, [
            $u['id'],
            $u['name'],
            $u['email'],
            $u['phone'],
            $u['order_count'],
            number_format($u['total_spent'], 2),
            $u['created_at']
        ]);
    }
    fclose($output);
    exit;
}

// Include header after export handling so headers() can be sent without prior output
include 'includes/header.php';
?>

<!-- Search & Actions -->
<div class="bg-white rounded-lg shadow-md p-4 md:p-6 mb-6">
    <form method="GET" class="flex flex-col md:flex-row md:items-center gap-3">
        <label for="search" class="sr-only">Search users</label>
        <div class="flex items-center gap-2 flex-1">
            <input id="search" name="search" type="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search name, email or phone" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" aria-label="Search users">
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors" aria-label="Search">
                <i class="fas fa-search"></i>
            </button>
        </div>

        <div class="flex items-center gap-2">
            <?php
            // Build base query params to preserve search, sort, and order
            $baseParams = [];
            if ($search) $baseParams['search'] = $search;
            if ($sortParam) $baseParams['sort'] = $sortParam;
            if ($orderParam) $baseParams['order'] = $orderParam;
            $baseQuery = $baseParams ? '&' . http_build_query($baseParams) : '';
            ?>
            <a href="?export=csv<?php echo $baseQuery; ?>" class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md text-sm bg-white hover:bg-gray-50" aria-label="Export CSV">
                <i class="fas fa-file-csv mr-2"></i>Export CSV
            </a>
            <a href="users.php" class="inline-flex items-center px-3 py-2 rounded-md text-sm bg-gray-100 hover:bg-gray-200" aria-label="Reset filters">Reset</a>
        </div>
    </form>
</div>

<?php if ($view_user): ?>
    <!-- User Details Modal -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-semibold text-gray-900">User Details</h3>
            <a href="users.php" class="text-gray-600 hover:text-gray-800">
                <i class="fas fa-times"></i>
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <h4 class="font-medium text-gray-900 mb-2">Personal Information</h4>
                <p class="text-sm text-gray-600"><strong>Name:</strong> <?php echo htmlspecialchars($view_user['name']); ?></p>
                <p class="text-sm text-gray-600"><strong>Phone:</strong> <?php echo htmlspecialchars($view_user['phone']); ?></p>
                <p class="text-sm text-gray-600"><strong>Email:</strong> <?php echo htmlspecialchars($view_user['email'] ?? 'N/A'); ?></p>
                <p class="text-sm text-gray-600"><strong>Joined:</strong> <?php echo date('M j, Y', strtotime($view_user['created_at'])); ?></p>
            </div>

            <div>
                <h4 class="font-medium text-gray-900 mb-2">Address</h4>
                <p class="text-sm text-gray-600"><?php echo nl2br(htmlspecialchars($view_user['address'] ?? 'N/A')); ?></p>
            </div>
        </div>

        <div class="mb-6">
            <h4 class="font-medium text-gray-900 mb-4">Recent Orders</h4>
            <?php if (empty($user_orders)): ?>
                <p class="text-sm text-gray-500">No orders found</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left">Order ID</th>
                                <th class="px-4 py-2 text-left">Total</th>
                                <th class="px-4 py-2 text-left">Status</th>
                                <th class="px-4 py-2 text-left">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($user_orders as $order): ?>
                                    <tr class="border-t">
                                        <td class="px-4 py-2">
                                            <a href="orders.php?action=view&id=<?php echo (int)$order['id']; ?>" class="text-blue-600 hover:text-blue-900 inline-flex items-center gap-2" title="View order #<?php echo (int)$order['id']; ?>">
                                                <span class="font-mono text-sm">#<?php echo (int)$order['id']; ?></span>
                                                <span class="sr-only">View order <?php echo (int)$order['id']; ?></span>
                                                <i class="fas fa-external-link-alt" aria-hidden="true"></i>
                                            </a>
                                        </td>
                                        <td class="px-4 py-2">৳<?php echo number_format($order['total'], 2); ?></td>
                                        <td class="px-4 py-2"><?php echo getOrderStatusBadge($order['status']); ?></td>
                                        <td class="px-4 py-2"><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                                    </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<!-- Users List -->
<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <div class="px-4 py-3 md:px-6 md:py-4 border-b border-gray-200 flex items-center justify-between">
        <h3 class="text-lg font-semibold text-gray-900">Users <span class="text-sm text-gray-500">(<?php echo $total_users; ?>)</span></h3>
        <div class="text-sm text-gray-600 hidden md:block">Showing <?php echo ($offset + 1); ?>–<?php echo min($offset + $per_page, $total_users); ?></div>
    </div>

    <!-- Desktop table -->
    <div class="hidden md:block overflow-x-auto">
        <table class="w-full table-auto">
            <thead class="bg-gray-50">
                <tr>
                    <?php
                    // helper to create sort links and indicators
                    function sort_link($label, $key, $currentSort, $currentOrder, $baseParams) {
                        $nextOrder = ($currentSort === $key && strtolower($currentOrder) === 'asc') ? 'desc' : 'asc';
                        $params = $baseParams;
                        $params['sort'] = $key;
                        $params['order'] = $nextOrder;
                        $query = '?' . http_build_query($params);
                        $indicator = '';
                        if ($currentSort === $key) {
                            $indicator = strtolower($currentOrder) === 'asc' ? ' ▲' : ' ▼';
                        }
                        return '<a href="' . $query . '" class="inline-flex items-center gap-1">' . htmlspecialchars($label) . '<span class="text-xs text-gray-400">' . $indicator . '</span></a>';
                    }

                    // reuse baseParams from above
                    $headerBase = [];
                    if ($search) $headerBase['search'] = $search;
                    if ($page) $headerBase['page'] = $page;
                    ?>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo sort_link('User', 'name', $sortParam, $orderParam, $headerBase); ?></th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo sort_link('Contact', 'email', $sortParam, $orderParam, $headerBase); ?></th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo sort_link('Orders', 'orders', $sortParam, $orderParam, $headerBase); ?></th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo sort_link('Total Spent', 'total', $sortParam, $orderParam, $headerBase); ?></th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo sort_link('Joined', 'created', $sortParam, $orderParam, $headerBase); ?></th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">No users found</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $user): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center" aria-hidden="true">
                                            <span class="text-sm font-medium text-gray-600"><?php echo strtoupper(substr($user['name'], 0, 1)); ?></span>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($user['name']); ?></div>
                                        <div class="text-xs text-gray-500">ID: <?php echo $user['id']; ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?php echo htmlspecialchars($user['phone']); ?></div>
                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($user['email'] ?? 'No email'); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo $user['order_count']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">৳<?php echo number_format($user['total_spent'] ?? 0, 2); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <a href="?action=view&id=<?php echo $user['id']; ?>" class="text-blue-600 hover:text-blue-900 inline-flex items-center gap-2" aria-label="View user <?php echo htmlspecialchars($user['name']); ?>">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Mobile cards -->
    <div class="md:hidden p-4 space-y-3">
        <?php if (empty($users)): ?>
            <div class="text-center text-gray-500">No users found</div>
        <?php else: ?>
            <?php foreach ($users as $user): ?>
                <div class="border rounded-lg p-3 bg-white shadow-sm">
                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center text-sm font-medium text-gray-700"><?php echo strtoupper(substr($user['name'], 0, 1)); ?></div>
                            <div>
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($user['name']); ?></div>
                                <div class="text-xs text-gray-500"><?php echo htmlspecialchars($user['email'] ?? 'No email'); ?></div>
                            </div>
                        </div>
                        <div class="text-right text-xs text-gray-500">ID: <?php echo $user['id']; ?></div>
                    </div>
                    <div class="mt-2 flex items-center justify-between text-sm text-gray-700">
                        <div>Orders: <strong class="text-gray-900"><?php echo $user['order_count']; ?></strong></div>
                        <div>৳<?php echo number_format($user['total_spent'] ?? 0, 2); ?></div>
                    </div>
                    <div class="mt-3 flex items-center justify-between">
                        <div class="text-xs text-gray-500">Joined <?php echo date('M j, Y', strtotime($user['created_at'])); ?></div>
                        <a href="?action=view&id=<?php echo $user['id']; ?>" class="inline-flex items-center px-3 py-1 rounded bg-blue-600 text-white text-sm">View</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <div class="px-4 py-3 md:px-6 md:py-4 border-t border-gray-200">
            <nav class="flex items-center justify-between" aria-label="Pagination">
                <div class="text-sm text-gray-700 hidden sm:block">Showing <?php echo ($offset + 1); ?> to <?php echo min($offset + $per_page, $total_users); ?> of <?php echo $total_users; ?> users</div>
                <div class="flex items-center space-x-1">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?><?php echo $baseQuery; ?>" class="px-2 py-1 border border-gray-300 rounded text-sm hover:bg-gray-50" aria-label="Previous page">&laquo;</a>
                    <?php endif; ?>

                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <a href="?page=<?php echo $i; ?><?php echo $baseQuery; ?>" class="px-2 py-1 border text-sm rounded <?php echo $i === $page ? 'bg-blue-600 text-white border-blue-600' : 'border-gray-300 hover:bg-gray-50'; ?>" aria-current="<?php echo $i === $page ? 'page' : 'false'; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?><?php echo $baseQuery; ?>" class="px-2 py-1 border border-gray-300 rounded text-sm hover:bg-gray-50" aria-label="Next page">&raquo;</a>
                    <?php endif; ?>
                </div>
            </nav>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>