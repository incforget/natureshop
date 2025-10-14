<?php
$page_title = 'Order Management';
include_once '../includes/config.php';
include_once 'includes/functions.php';
requireAdminLogin();

// Handle CSV export before any HTML output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'bulk_update' && isset($_POST['bulk_action']) && $_POST['bulk_action'] === 'export_csv') {
    $selected_orders = $_POST['selected_orders'] ?? [];

    if (!empty($selected_orders)) {
        // Handle CSV export
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="orders_export_' . date('Y-m-d') . '.csv"');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['Order ID', 'Customer Name', 'Phone', 'Total', 'Status', 'Date']);

        foreach ($selected_orders as $order_id) {
            $sql = "SELECT o.id, u.name, u.phone, o.total, o.status, o.created_at FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
            $order = $stmt->get_result()->fetch_assoc();

            if ($order) {
                fputcsv($output, [
                    $order['id'],
                    $order['name'],
                    $order['phone'],
                    $order['total'],
                    $order['status'],
                    date('Y-m-d H:i:s', strtotime($order['created_at']))
                ]);
            }
        }
        fclose($output);
        exit;
    }
}

include 'includes/header.php';
include_once '../includes/functions.php';

// Handle status update
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_status') {
        $order_id = (int)$_POST['order_id'];
        $status = sanitizeInput($_POST['status']);
        $tracking_number = sanitizeInput($_POST['tracking_number'] ?? '');
        $estimated_delivery_date = !empty($_POST['estimated_delivery_date']) ? $_POST['estimated_delivery_date'] : null;
        $cancellation_reason = sanitizeInput($_POST['cancellation_reason'] ?? '');

        // Get current order status for logging
        $current_order_sql = "SELECT status FROM orders WHERE id = ?";
        $current_stmt = $conn->prepare($current_order_sql);
        $current_stmt->bind_param("i", $order_id);
        $current_stmt->execute();
        $current_order = $current_stmt->get_result()->fetch_assoc();
        $old_status = $current_order['status'];

        $sql = "UPDATE orders SET status = ?, tracking_number = ?, estimated_delivery_date = ?, cancellation_reason = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssi", $status, $tracking_number, $estimated_delivery_date, $cancellation_reason, $order_id);

        if ($stmt->execute()) {
            // Log status change. Also log when a cancellation reason is added so cancelled events appear in history.
            $admin_id = $_SESSION['admin_id'] ?? null;
            $change_reason = $status === 'cancelled' && !empty($cancellation_reason) ? $cancellation_reason : null;

            // Record history when status actually changes OR when cancellation reason is provided for cancelled status
            if ($old_status !== $status || ($status === 'cancelled' && $change_reason)) {
                logOrderStatusChange($order_id, $old_status, $status, $admin_id, $change_reason);
            }

            $message = 'Order updated successfully!';
            $message_type = 'success';
        } else {
            $message = 'Error updating order: ' . $conn->error;
            $message_type = 'error';
        }
    } elseif ($_POST['action'] === 'bulk_update') {
        $selected_orders = $_POST['selected_orders'] ?? [];
        $bulk_action = sanitizeInput($_POST['bulk_action']);

        if (empty($selected_orders)) {
            $message = 'No orders selected!';
            $message_type = 'error';
        } elseif ($bulk_action === 'status_update') {
            $new_status = sanitizeInput($_POST['new_status']);
            $placeholders = str_repeat('?,', count($selected_orders) - 1) . '?';
            $types = str_repeat('i', count($selected_orders));

            $sql = "UPDATE orders SET status = ? WHERE id IN ($placeholders)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s$types", $new_status, ...$selected_orders);

            if ($stmt->execute()) {
                $message = 'Status updated for ' . count($selected_orders) . ' order(s) successfully!';
                $message_type = 'success';
            } else {
                $message = 'Error updating orders: ' . $conn->error;
                $message_type = 'error';
            }
        } elseif ($bulk_action === 'delete') {
            // Only allow deletion of cancelled orders
            $placeholders = str_repeat('?,', count($selected_orders) - 1) . '?';
            $types = str_repeat('i', count($selected_orders));

            $sql = "DELETE FROM orders WHERE id IN ($placeholders) AND status = 'cancelled'";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$selected_orders);

            if ($stmt->execute()) {
                $deleted_count = $stmt->affected_rows;
                $message = "$deleted_count cancelled order(s) deleted successfully!";
                $message_type = 'success';
            } else {
                $message = 'Error deleting orders: ' . $conn->error;
                $message_type = 'error';
            }
        }
    }
}

// Get orders with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

$status_filter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$sort_by = isset($_GET['sort']) ? sanitizeInput($_GET['sort']) : 'created_at';
$sort_order = isset($_GET['order']) && $_GET['order'] === 'asc' ? 'ASC' : 'DESC';

// Validate sort column
$allowed_sort_columns = ['id', 'user_name', 'total', 'status', 'created_at'];
if (!in_array($sort_by, $allowed_sort_columns)) {
    $sort_by = 'created_at';
}

$sql = "SELECT o.*, u.name as user_name, u.phone as user_phone FROM orders o JOIN users u ON o.user_id = u.id WHERE 1=1";
$params = [];
$types = '';

if ($status_filter && $status_filter !== 'all') {
    $sql .= " AND o.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if ($search) {
    $sql .= " AND (o.id = ? OR u.name LIKE ? OR u.phone LIKE ?)";
    $params[] = $search;
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= 'sss';
}

$sql .= " ORDER BY ";

switch ($sort_by) {
    case 'user_name':
        $sql .= "u.name $sort_order";
        break;
    case 'id':
        $sql .= "o.id $sort_order";
        break;
    case 'total':
        $sql .= "o.total $sort_order";
        break;
    case 'status':
        $sql .= "o.status $sort_order";
        break;
    case 'created_at':
    default:
        $sql .= "o.created_at $sort_order";
        break;
}

$sql .= " LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;
$types .= 'ii';

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get total count for pagination
$sql_count = "SELECT COUNT(*) as total FROM orders o JOIN users u ON o.user_id = u.id WHERE 1=1";
$params_count = [];
$types_count = '';

if ($status_filter && $status_filter !== 'all') {
    $sql_count .= " AND o.status = ?";
    $params_count[] = $status_filter;
    $types_count .= 's';
}

if ($search) {
    $sql_count .= " AND (o.id = ? OR u.name LIKE ? OR u.phone LIKE ?)";
    $params_count[] = $search;
    $params_count[] = "%$search%";
    $params_count[] = "%$search%";
    $types_count .= 'sss';
}

$stmt_count = $conn->prepare($sql_count);
if ($params_count) {
    $stmt_count->bind_param($types_count, ...$params_count);
}
$stmt_count->execute();
$total_orders = $stmt_count->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_orders / $per_page);

// Get order details for viewing
$view_order = null;
$order_items = [];
$order_history = [];
if (isset($_GET['action']) && $_GET['action'] === 'view' && isset($_GET['id'])) {
    $order_id = (int)$_GET['id'];
    $view_order = getOrderById($order_id);
    if ($view_order) {
        $order_items = getOrderItems($order_id);
        $order_history = getOrderStatusHistory($order_id);
    } else {
        // Debug: Order not found
        error_log("Order not found: ID $order_id");
    }
}
?>

<?php
function getSortLink($column, $current_sort, $current_order, $search, $status_filter, $page) {
    $new_order = ($current_sort === $column && $current_order === 'DESC') ? 'asc' : 'desc';
    $params = array_filter([
        'sort' => $column,
        'order' => $new_order,
        'search' => $search,
        'status' => $status_filter !== 'all' ? $status_filter : null,
        'page' => $page > 1 ? $page : null
    ]);

    return '?' . http_build_query($params);
}

function getSortIcon($column, $current_sort, $current_order) {
    if ($current_sort !== $column) {
        return '<i class="fas fa-sort text-gray-400 ml-1"></i>';
    }
    return '<i class="fas fa-sort-' . ($current_order === 'DESC' ? 'down' : 'up') . ' text-blue-600 ml-1"></i>';
}
?>

<?php if ($message): ?>
    <div class="mb-6 p-4 rounded-lg border-l-4 <?php echo $message_type === 'success' ? 'bg-green-50 border-green-400 text-green-700' : 'bg-red-50 border-red-400 text-red-700'; ?>">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <i class="fas <?php echo $message_type === 'success' ? 'fa-check-circle text-green-400' : 'fa-exclamation-circle text-red-400'; ?> text-lg"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium"><?php echo $message; ?></p>
            </div>
            <div class="ml-auto pl-3">
                <button type="button" class="inline-flex rounded-md p-1.5 <?php echo $message_type === 'success' ? 'text-green-400 hover:bg-green-100' : 'text-red-400 hover:bg-red-100'; ?> focus:outline-none focus:ring-2 focus:ring-offset-2 <?php echo $message_type === 'success' ? 'focus:ring-green-500' : 'focus:ring-red-500'; ?>" onclick="this.parentElement.parentElement.style.display='none'">
                    <i class="fas fa-times text-sm"></i>
                </button>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if ($view_order): ?>
    <!-- Order Details Section -->
    <div class="bg-white rounded-xl shadow-lg p-6 mb-8 border border-gray-100 print-order-details">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold text-gray-900 flex items-center">
                <i class="fas fa-shopping-cart mr-3 text-blue-600"></i>
                Order #<?php echo $view_order['id']; ?> Details
            </h2>
            <div class="flex gap-3">
                <button onclick="printOrder()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors font-medium print-only">
                    <i class="fas fa-print mr-2"></i>Print Order
                </button>
                <a href="orders.php" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors font-medium no-print">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Orders
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <div class="bg-gray-50 p-4 rounded-lg">
                <h4 class="font-semibold text-gray-900 mb-3 flex items-center">
                    <i class="fas fa-user mr-2 text-blue-600"></i>Customer Information
                </h4>
                <div class="space-y-2">
                    <p class="text-sm text-gray-600"><strong>Name:</strong> <?php echo htmlspecialchars($view_order['user_name']); ?></p>
                    <p class="text-sm text-gray-600"><strong>Phone:</strong> <?php echo htmlspecialchars($view_order['user_phone']); ?></p>
                    <p class="text-sm text-gray-600"><strong>Email:</strong> <?php echo htmlspecialchars($view_order['user_email'] ?? 'N/A'); ?></p>
                </div>
            </div>

            <div class="bg-gray-50 p-4 rounded-lg">
                <h4 class="font-semibold text-gray-900 mb-3 flex items-center">
                    <i class="fas fa-info-circle mr-2 text-blue-600"></i>Order Information
                </h4>
                <div class="space-y-2">
                    <p class="text-sm text-gray-600"><strong>Order Date:</strong> <?php echo date('M j, Y H:i', strtotime($view_order['created_at'])); ?></p>
                    <p class="text-sm text-gray-600"><strong>Status:</strong> <?php echo getOrderStatusBadge($view_order['status']); ?></p>
                    <p class="text-sm text-gray-600"><strong>Total:</strong> <span class="font-semibold text-green-600">৳<?php echo number_format($view_order['total'], 2); ?></span></p>
                    <?php if (!empty($view_order['tracking_number'])): ?>
                        <p class="text-sm text-gray-600"><strong>Tracking Number:</strong> <?php echo htmlspecialchars($view_order['tracking_number']); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($view_order['estimated_delivery_date'])): ?>
                        <p class="text-sm text-gray-600"><strong>Est. Delivery:</strong> <?php echo date('M j, Y', strtotime($view_order['estimated_delivery_date'])); ?></p>
                    <?php endif; ?>
                    <?php if ($view_order['status'] === 'cancelled' && !empty($view_order['cancellation_reason'])): ?>
                        <p class="text-sm text-red-600"><strong>Cancellation Reason:</strong> <?php echo htmlspecialchars($view_order['cancellation_reason']); ?></p>
                    <?php endif; ?>
                    <?php if ($view_order['promo_code']): ?>
                        <p class="text-sm text-gray-600"><strong>Promo Code:</strong> <?php echo htmlspecialchars($view_order['promo_code']); ?> (<span class="text-green-600">৳<?php echo number_format($view_order['discount_amount'], 2); ?> off</span>)</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="bg-gray-50 p-4 rounded-lg mb-6">
            <h4 class="font-semibold text-gray-900 mb-3 flex items-center">
                <i class="fas fa-map-marker-alt mr-2 text-blue-600"></i>Delivery Address
            </h4>
            <p class="text-sm text-gray-600"><?php echo nl2br(htmlspecialchars($view_order['address'])); ?></p>
            <?php if ($view_order['delivery_area']): ?>
                <p class="text-sm text-gray-600"><strong>Area:</strong> <?php echo htmlspecialchars($view_order['delivery_area']); ?></p>
            <?php endif; ?>
            <?php if ($view_order['delivery_charge'] > 0): ?>
                <p class="text-sm text-gray-600"><strong>Delivery Charge:</strong> <span class="font-semibold">৳<?php echo number_format($view_order['delivery_charge'], 2); ?></span></p>
            <?php endif; ?>
        </div>

        <div class="mb-6">
            <h4 class="font-semibold text-gray-900 mb-4 flex items-center">
                <i class="fas fa-shopping-cart mr-2 text-blue-600"></i>Order Items
            </h4>
            <div class="overflow-x-auto bg-white rounded-lg shadow">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-gray-900">Product</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-900">Quantity</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-900">Price</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-900">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($order_items as $item): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3"><?php echo htmlspecialchars($item['product_name']); ?></td>
                                <td class="px-4 py-3 text-center"><?php echo $item['quantity']; ?></td>
                                <td class="px-4 py-3">৳<?php echo number_format($item['price'], 2); ?></td>
                                <td class="px-4 py-3 font-semibold">৳<?php echo number_format($item['quantity'] * $item['price'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Order Status History Timeline -->
        <div class="mb-6 no-print">
            <h4 class="font-semibold text-gray-900 mb-4 flex items-center">
                <i class="fas fa-history mr-2 text-blue-600"></i>Order Status History
            </h4>
            <div class="space-y-4 bg-gray-50 p-4 rounded-lg">
                <?php if (empty($order_history)): ?>
                    <p class="text-sm text-gray-500 italic">No status changes recorded yet.</p>
                <?php else: ?>
                    <?php foreach ($order_history as $history): ?>
                        <div class="flex items-start space-x-3">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-clock text-blue-600"></i>
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm">
                                    <p class="text-gray-900">
                                        Status changed from <span class="font-medium text-red-600"><?php echo ucfirst($history['old_status'] ?? 'New'); ?></span> to <span class="font-medium text-green-600"><?php echo ucfirst($history['new_status']); ?></span>
                                        <?php if (!empty($history['change_reason'])): ?>
                                            <span class="text-gray-500">— <?php echo htmlspecialchars($history['change_reason']); ?></span>
                                        <?php endif; ?>
                                    </p>
                                    <p class="text-gray-500 text-xs mt-1">
                                        <?php echo date('M j, Y H:i', strtotime($history['created_at'])); ?>
                                        <?php if (!empty($history['changed_by_name'])): ?>
                                            by <span class="font-medium"><?php echo htmlspecialchars($history['changed_by_name']); ?></span>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Update Status Form -->
        <div class="border-t pt-6 bg-gray-50 -m-6 p-6 rounded-b-xl no-print">
            <h4 class="font-semibold text-gray-900 mb-4 flex items-center">
                <i class="fas fa-edit mr-2 text-blue-600"></i>Update Order Status
            </h4>
            <form method="POST" class="space-y-4" id="update-status-form">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="order_id" value="<?php echo $view_order['id']; ?>">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select name="status" id="status" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors">
                            <option value="pending" <?php echo $view_order['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="confirmed" <?php echo $view_order['status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="processing" <?php echo $view_order['status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                            <option value="shipped" <?php echo $view_order['status'] === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                            <option value="delivered" <?php echo $view_order['status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                            <option value="cancelled" <?php echo $view_order['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>

                    <div id="cancellation-reason-container" class="<?php echo $view_order['status'] === 'cancelled' ? '' : 'hidden'; ?>">
                        <label for="cancellation_reason" class="block text-sm font-medium text-gray-700 mb-2">Cancellation Reason</label>
                        <select name="cancellation_reason" id="cancellation_reason" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors">
                            <option value="">Select reason</option>
                            <option value="Customer request" <?php echo ($view_order['cancellation_reason'] ?? '') === 'Customer request' ? 'selected' : ''; ?>>Customer request</option>
                            <option value="Payment failed" <?php echo ($view_order['cancellation_reason'] ?? '') === 'Payment failed' ? 'selected' : ''; ?>>Payment failed</option>
                            <option value="Out of stock" <?php echo ($view_order['cancellation_reason'] ?? '') === 'Out of stock' ? 'selected' : ''; ?>>Out of stock</option>
                            <option value="Delivery issue" <?php echo ($view_order['cancellation_reason'] ?? '') === 'Delivery issue' ? 'selected' : ''; ?>>Delivery issue</option>
                            <option value="Duplicate order" <?php echo ($view_order['cancellation_reason'] ?? '') === 'Duplicate order' ? 'selected' : ''; ?>>Duplicate order</option>
                            <option value="Fraudulent order" <?php echo ($view_order['cancellation_reason'] ?? '') === 'Fraudulent order' ? 'selected' : ''; ?>>Fraudulent order</option>
                            <option value="Other" <?php echo ($view_order['cancellation_reason'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>

                    <div>
                        <label for="tracking_number" class="block text-sm font-medium text-gray-700 mb-2">Tracking Number</label>
                        <input type="text" name="tracking_number" id="tracking_number" value="<?php echo htmlspecialchars($view_order['tracking_number'] ?? ''); ?>" placeholder="Enter tracking number" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors">
                    </div>

                    <div>
                        <label for="estimated_delivery_date" class="block text-sm font-medium text-gray-700 mb-2">Est. Delivery Date</label>
                        <input type="date" name="estimated_delivery_date" id="estimated_delivery_date" value="<?php echo $view_order['estimated_delivery_date'] ?? ''; ?>" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors">
                    </div>
                </div>

                <div class="flex gap-4 pt-4">
                    <button type="submit" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors font-medium">
                        <i class="fas fa-save mr-2"></i>Update Order
                    </button>
                    <a href="orders.php" class="px-6 py-3 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors font-medium">
                        <i class="fas fa-times mr-2"></i>Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php if (!$view_order): ?>
<!-- Filters and Search -->
<div class="bg-white rounded-xl shadow-lg p-6 mb-8 border border-gray-100">
    <div class="flex flex-col lg:flex-row gap-6 items-start lg:items-center justify-between">
        <div class="flex flex-col sm:flex-row gap-4 flex-1 w-full lg:w-auto">
            <form method="GET" class="flex flex-col sm:flex-row gap-3 flex-1">
                <div class="relative flex-1">
                    <label for="search-input" class="sr-only">Search orders</label>
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-search text-gray-400" aria-hidden="true"></i>
                    </div>
                    <input type="text" id="search-input" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by order ID, customer name, or phone..." class="pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent w-full transition-colors" aria-describedby="search-help">
                </div>
                <div class="relative">
                    <label for="status-filter" class="sr-only">Filter by status</label>
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-filter text-gray-400" aria-hidden="true"></i>
                    </div>
                    <select name="status" id="status-filter" class="pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent appearance-none bg-white transition-colors" aria-describedby="filter-help">
                        <option value="all">All Status</option>
                        <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="confirmed" <?php echo $status_filter === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                        <option value="processing" <?php echo $status_filter === 'processing' ? 'selected' : ''; ?>>Processing</option>
                        <option value="shipped" <?php echo $status_filter === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                        <option value="delivered" <?php echo $status_filter === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                        <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                        <i class="fas fa-chevron-down text-gray-400" aria-hidden="true"></i>
                    </div>
                </div>
                <button type="submit" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors font-medium" aria-label="Search orders">
                    <i class="fas fa-search mr-2" aria-hidden="true"></i>Search
                </button>
            </form>
        </div>
        <div class="flex items-center gap-3 text-sm text-gray-600">
            <i class="fas fa-info-circle text-blue-500" aria-hidden="true"></i>
            <span id="search-help">Filter and search orders to manage efficiently</span>
            <span id="filter-help" class="sr-only">Select status to filter orders</span>
        </div>
    </div>
</div>

<!-- Orders Table -->
<div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-100">
    <div class="px-6 py-5 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-indigo-50">
        <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
            <div>
                <h3 class="text-xl font-bold text-gray-900 flex items-center">
                    <i class="fas fa-shopping-cart mr-3 text-blue-600"></i>
                    Orders Management
                </h3>
                <p class="text-sm text-gray-600 mt-1"><?php echo $total_orders; ?> orders found</p>
            </div>

            <!-- Bulk Actions Controls -->
            <div id="bulk-actions-form" class="flex flex-col sm:flex-row items-start sm:items-center gap-3 w-full lg:w-auto">
                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-2 w-full sm:w-auto">
                    <label for="bulk-action-select" class="text-sm font-medium text-gray-700 sm:whitespace-nowrap">Bulk Actions:</label>
                    <div class="flex gap-2 w-full sm:w-auto">
                        <select id="bulk-action-select" class="flex-1 sm:flex-none px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent disabled:opacity-50" disabled>
                            <option value="">Select Action</option>
                            <option value="status_update">Update Status</option>
                            <option value="export_csv">Export to CSV</option>
                            <option value="delete">Delete Selected</option>
                        </select>
                        <select id="bulk-status-select" class="flex-1 sm:flex-none px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent hidden">
                            <option value="pending">Pending</option>
                            <option value="confirmed">Confirmed</option>
                            <option value="processing">Processing</option>
                            <option value="shipped">Shipped</option>
                            <option value="delivered">Delivered</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                        <button type="button" id="bulk-submit-btn" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-colors font-medium whitespace-nowrap" disabled>
                            <i class="fas fa-check mr-1"></i>Apply
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <form method="POST" id="bulk-actions-form-table">
    <input type="hidden" name="action" value="bulk_update">
    <div class="overflow-x-auto">
        <table class="w-full" role="table" aria-label="Orders list">
            <thead class="bg-gray-50">
                <tr role="row">
                    <th class="px-4 lg:px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider" role="columnheader" aria-sort="none">
                        <input type="checkbox" id="select-all" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" aria-label="Select all orders">
                    </th>
                    <th class="px-4 lg:px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider" role="columnheader" aria-sort="<?php echo $sort_by === 'id' ? ($sort_order === 'DESC' ? 'descending' : 'ascending') : 'none'; ?>">
                        <a href="<?php echo getSortLink('id', $sort_by, $sort_order, $search, $status_filter, $page); ?>" class="flex items-center hover:text-gray-800 transition-colors group" aria-label="Sort by Order ID">
                            Order ID <?php echo getSortIcon('id', $sort_by, $sort_order); ?>
                        </a>
                    </th>
                    <th class="px-4 lg:px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider" role="columnheader" aria-sort="<?php echo $sort_by === 'user_name' ? ($sort_order === 'DESC' ? 'descending' : 'ascending') : 'none'; ?>">
                        <a href="<?php echo getSortLink('user_name', $sort_by, $sort_order, $search, $status_filter, $page); ?>" class="flex items-center hover:text-gray-800 transition-colors group" aria-label="Sort by Customer">
                            Customer <?php echo getSortIcon('user_name', $sort_by, $sort_order); ?>
                        </a>
                    </th>
                    <th class="px-4 lg:px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider" role="columnheader" aria-sort="<?php echo $sort_by === 'total' ? ($sort_order === 'DESC' ? 'descending' : 'ascending') : 'none'; ?>">
                        <a href="<?php echo getSortLink('total', $sort_by, $sort_order, $search, $status_filter, $page); ?>" class="flex items-center hover:text-gray-800 transition-colors group" aria-label="Sort by Total">
                            Total <?php echo getSortIcon('total', $sort_by, $sort_order); ?>
                        </a>
                    </th>
                    <th class="px-4 lg:px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider" role="columnheader" aria-sort="<?php echo $sort_by === 'status' ? ($sort_order === 'DESC' ? 'descending' : 'ascending') : 'none'; ?>">
                        <a href="<?php echo getSortLink('status', $sort_by, $sort_order, $search, $status_filter, $page); ?>" class="flex items-center hover:text-gray-800 transition-colors group" aria-label="Sort by Status">
                            Status <?php echo getSortIcon('status', $sort_by, $sort_order); ?>
                        </a>
                    </th>
                    <th class="hidden md:table-cell px-4 lg:px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider" role="columnheader" aria-sort="<?php echo $sort_by === 'created_at' ? ($sort_order === 'DESC' ? 'descending' : 'ascending') : 'none'; ?>">
                        <a href="<?php echo getSortLink('created_at', $sort_by, $sort_order, $search, $status_filter, $page); ?>" class="flex items-center hover:text-gray-800 transition-colors group" aria-label="Sort by Date">
                            Date <?php echo getSortIcon('created_at', $sort_by, $sort_order); ?>
                        </a>
                    </th>
                    <th class="px-4 lg:px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider" role="columnheader">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center">
                                <i class="fas fa-shopping-cart text-gray-300 text-4xl mb-4"></i>
                                <p class="text-gray-500 text-lg">No orders found</p>
                                <p class="text-gray-400 text-sm">Try adjusting your search or filter criteria</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($orders as $order): ?>
                        <tr class="hover:bg-blue-50 transition-colors">
                            <td class="px-4 lg:px-6 py-4 whitespace-nowrap">
                                <input type="checkbox" name="selected_orders[]" value="<?php echo $order['id']; ?>" class="order-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            </td>
                            <td class="px-4 lg:px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                                #<?php echo $order['id']; ?>
                            </td>
                            <td class="px-4 lg:px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-8 w-8 lg:h-10 lg:w-10">
                                        <div class="h-8 w-8 lg:h-10 lg:w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                            <i class="fas fa-user text-blue-600 text-xs lg:text-sm"></i>
                                        </div>
                                    </div>
                                    <div class="ml-2 lg:ml-3">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($order['user_name']); ?></div>
                                        <div class="text-xs lg:text-sm text-gray-500"><?php echo htmlspecialchars($order['user_phone']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 lg:px-6 py-4 whitespace-nowrap text-sm font-semibold text-green-600">
                                ৳<?php echo number_format($order['total'], 2); ?>
                            </td>
                            <td class="px-4 lg:px-6 py-4 whitespace-nowrap">
                                <?php echo getOrderStatusBadge($order['status']); ?>
                            </td>
                            <td class="hidden md:table-cell px-4 lg:px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo date('M j, Y', strtotime($order['created_at'])); ?>
                            </td>
                            <td class="px-4 lg:px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <a href="?action=view&id=<?php echo $order['id']; ?>" class="inline-flex items-center px-2 lg:px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 hover:bg-blue-200 transition-colors">
                                    <i class="fas fa-eye mr-1"></i> View
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <div class="px-4 lg:px-6 py-5 border-t border-gray-200 bg-gray-50">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="text-sm text-gray-700 text-center sm:text-left">
                    <span class="font-medium"><?php echo ($offset + 1); ?> to <?php echo min($offset + $per_page, $total_orders); ?></span> of <span class="font-medium"><?php echo $total_orders; ?></span> orders
                </div>
                <div class="flex items-center gap-2 flex-wrap justify-center">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $status_filter && $status_filter !== 'all' ? '&status=' . urlencode($status_filter) : ''; ?><?php echo '&sort=' . urlencode($sort_by) . '&order=' . urlencode(strtolower($sort_order)); ?>" class="px-3 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50 hover:border-gray-400 transition-colors flex items-center">
                            <i class="fas fa-chevron-left mr-1"></i> Previous
                        </a>
                    <?php endif; ?>

                    <div class="flex gap-1">
                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        if ($start_page > 1): ?>
                            <a href="?page=1<?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $status_filter && $status_filter !== 'all' ? '&status=' . urlencode($status_filter) : ''; ?><?php echo '&sort=' . urlencode($sort_by) . '&order=' . urlencode(strtolower($sort_order)); ?>" class="px-3 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50 transition-colors">1</a>
                            <?php if ($start_page > 2): ?>
                                <span class="px-2 py-2 text-gray-500">...</span>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                            <a href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $status_filter && $status_filter !== 'all' ? '&status=' . urlencode($status_filter) : ''; ?><?php echo '&sort=' . urlencode($sort_by) . '&order=' . urlencode(strtolower($sort_order)); ?>" class="px-3 py-2 border rounded-lg text-sm transition-colors <?php echo $i === $page ? 'bg-blue-600 text-white border-blue-600' : 'border-gray-300 hover:bg-gray-50'; ?>"><?php echo $i; ?></a>
                        <?php endfor; ?>

                        <?php if ($end_page < $total_pages): ?>
                            <?php if ($end_page < $total_pages - 1): ?>
                                <span class="px-2 py-2 text-gray-500">...</span>
                            <?php endif; ?>
                            <a href="?page=<?php echo $total_pages; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $status_filter && $status_filter !== 'all' ? '&status=' . urlencode($status_filter) : ''; ?><?php echo '&sort=' . urlencode($sort_by) . '&order=' . urlencode(strtolower($sort_order)); ?>" class="px-3 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50 transition-colors"><?php echo $total_pages; ?></a>
                        <?php endif; ?>
                    </div>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $status_filter && $status_filter !== 'all' ? '&status=' . urlencode($status_filter) : ''; ?><?php echo '&sort=' . urlencode($sort_by) . '&order=' . urlencode(strtolower($sort_order)); ?>" class="px-3 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50 hover:border-gray-400 transition-colors flex items-center">
                            Next <i class="fas fa-chevron-right ml-1"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
    </form>
</div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAllCheckbox = document.getElementById('select-all');
    const orderCheckboxes = document.querySelectorAll('.order-checkbox');
    const bulkActionSelect = document.getElementById('bulk-action-select');
    const bulkStatusSelect = document.getElementById('bulk-status-select');
    const bulkSubmitBtn = document.getElementById('bulk-submit-btn');

    // Handle select all checkbox
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            orderCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateBulkActions();
        });
    }

    // Handle individual checkboxes
    orderCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            if (selectAllCheckbox) {
                const checkedBoxes = document.querySelectorAll('.order-checkbox:checked');
                selectAllCheckbox.checked = checkedBoxes.length === orderCheckboxes.length;
                selectAllCheckbox.indeterminate = checkedBoxes.length > 0 && checkedBoxes.length < orderCheckboxes.length;
            }
            updateBulkActions();
        });
    });

    // Handle bulk action selection
    if (bulkActionSelect) {
        bulkActionSelect.addEventListener('change', function() {
            const selectedAction = this.value;
            if (bulkStatusSelect) {
                bulkStatusSelect.classList.toggle('hidden', selectedAction !== 'status_update');
            }
            updateBulkActions();
        });
    }

    // Handle bulk submit button
    if (bulkSubmitBtn) {
        bulkSubmitBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const form = document.getElementById('bulk-actions-form-table');
            if (form) {
                const bulkAction = bulkActionSelect.value;
                const newStatus = bulkStatusSelect ? bulkStatusSelect.value : '';

                // Add hidden inputs to the form
                const bulkActionInput = document.createElement('input');
                bulkActionInput.type = 'hidden';
                bulkActionInput.name = 'bulk_action';
                bulkActionInput.value = bulkAction;
                form.appendChild(bulkActionInput);

                if (bulkAction === 'status_update') {
                    const newStatusInput = document.createElement('input');
                    newStatusInput.type = 'hidden';
                    newStatusInput.name = 'new_status';
                    newStatusInput.value = newStatus;
                    form.appendChild(newStatusInput);
                }

                form.submit();
            }
        });
    }

    function updateBulkActions() {
        if (!bulkActionSelect || !bulkSubmitBtn) return;

        const checkedBoxes = document.querySelectorAll('.order-checkbox:checked');
        const hasSelection = checkedBoxes.length > 0;
        const selectedAction = bulkActionSelect.value;

        bulkActionSelect.disabled = !hasSelection;
        bulkSubmitBtn.disabled = !hasSelection || !selectedAction;

        if (selectedAction === 'delete') {
            bulkSubmitBtn.classList.add('bg-red-600', 'hover:bg-red-700');
            bulkSubmitBtn.classList.remove('bg-blue-600', 'hover:bg-blue-700');
        } else {
            bulkSubmitBtn.classList.add('bg-blue-600', 'hover:bg-blue-700');
            bulkSubmitBtn.classList.remove('bg-red-600', 'hover:bg-red-700');
        }
    }

    // Handle status change to show/hide cancellation reason
    const statusSelect = document.getElementById('status');
    const cancellationReasonContainer = document.getElementById('cancellation-reason-container');

    if (statusSelect && cancellationReasonContainer) {
        statusSelect.addEventListener('change', function() {
            if (this.value === 'cancelled') {
                cancellationReasonContainer.classList.remove('hidden');
            } else {
                cancellationReasonContainer.classList.add('hidden');
            }
        });
    }

    // Initialize bulk actions if elements exist
    if (bulkActionSelect && bulkSubmitBtn) {
        updateBulkActions();
    }
});

// Print order function
function printOrder() {
    // Get order number from the page
    const orderTitle = document.querySelector('.text-2xl.font-bold.text-gray-900');
    const orderNumber = orderTitle ? orderTitle.textContent.trim() : 'Order';

    // Store original title
    const originalTitle = document.title;

    // Set custom title for print
    document.title = orderNumber + ' - Order Details';

    // Hide non-print elements
    document.querySelectorAll('.no-print').forEach(el => el.style.display = 'none');
    document.querySelectorAll('.print-order-details').forEach(el => el.style.display = 'block');

    // Trigger print
    window.print();

    // Restore elements and title after printing (with a delay to ensure print dialog is shown)
    setTimeout(() => {
        document.querySelectorAll('.no-print').forEach(el => el.style.display = '');
        document.querySelectorAll('.print-order-details').forEach(el => el.style.display = '');
        document.title = originalTitle;
    }, 1000);
}
</script>