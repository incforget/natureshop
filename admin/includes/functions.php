<?php
// Admin authentication and utility functions

function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']) && isset($_SESSION['admin_username']);
}

function requireAdminLogin() {
    if (!isAdminLoggedIn()) {
        header('Location: index.php');
        exit;
    }
}

function loginAdmin($username, $password) {
    global $conn;

    $sql = "SELECT id, username, password, email, role, is_active FROM admins WHERE username = ? AND is_active = 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $admin = $result->fetch_assoc();

        if (password_verify($password, $admin['password'])) {
            // Update last login
            $sql = "UPDATE admins SET last_login = NOW() WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $admin['id']);
            $stmt->execute();

            // Set session
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_email'] = $admin['email'];
            $_SESSION['admin_role'] = $admin['role'];

            return ['success' => true, 'message' => 'Login successful'];
        }
    }

    return ['success' => false, 'message' => 'Invalid username or password'];
}

function logoutAdmin() {
    session_destroy();
    header('Location: index.php');
    exit;
}

function getAdminStats() {
    global $conn;

    $stats = [];

    // Total products
    $result = $conn->query("SELECT COUNT(*) as count FROM products WHERE is_active = 1");
    $stats['total_products'] = $result->fetch_assoc()['count'];

    // Total categories
    $result = $conn->query("SELECT COUNT(*) as count FROM categories");
    $stats['total_categories'] = $result->fetch_assoc()['count'];

    // Total orders
    $result = $conn->query("SELECT COUNT(*) as count FROM orders");
    $stats['total_orders'] = $result->fetch_assoc()['count'];

    // Total users
    $result = $conn->query("SELECT COUNT(*) as count FROM users");
    $stats['total_users'] = $result->fetch_assoc()['count'];

    // Recent orders
    $result = $conn->query("SELECT COUNT(*) as count FROM orders WHERE DATE(created_at) = CURDATE()");
    $stats['today_orders'] = $result->fetch_assoc()['count'];

    // Total revenue
    $result = $conn->query("SELECT SUM(total) as revenue FROM orders WHERE status != 'cancelled'");
    $stats['total_revenue'] = $result->fetch_assoc()['revenue'] ?? 0;

    // Low stock products
    $result = $conn->query("SELECT COUNT(*) as count FROM products WHERE stock < 10 AND is_active = 1");
    $stats['low_stock'] = $result->fetch_assoc()['count'];

    return $stats;
}

function getAllRecentOrders($limit = 10) {
    global $conn;
    $sql = "SELECT o.*, u.name as user_name FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC LIMIT ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getOrderStatusBadge($status) {
    $badges = [
        'pending' => '<span class="px-2 py-1 text-xs font-semibold text-yellow-800 bg-yellow-100 rounded-full">Pending</span>',
        'confirmed' => '<span class="px-2 py-1 text-xs font-semibold text-blue-800 bg-blue-100 rounded-full">Confirmed</span>',
        'processing' => '<span class="px-2 py-1 text-xs font-semibold text-purple-800 bg-purple-100 rounded-full">Processing</span>',
        'shipped' => '<span class="px-2 py-1 text-xs font-semibold text-indigo-800 bg-indigo-100 rounded-full">Shipped</span>',
        'delivered' => '<span class="px-2 py-1 text-xs font-semibold text-green-800 bg-green-100 rounded-full">Delivered</span>',
        'cancelled' => '<span class="px-2 py-1 text-xs font-semibold text-red-800 bg-red-100 rounded-full">Cancelled</span>'
    ];

    return $badges[$status] ?? '<span class="px-2 py-1 text-xs font-semibold text-gray-800 bg-gray-100 rounded-full">Unknown</span>';
}

function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function logOrderStatusChange($order_id, $old_status, $new_status, $admin_id = null, $change_reason = null) {
    global $conn;
    $sql = "INSERT INTO order_status_history (order_id, old_status, new_status, changed_by, change_reason) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issis", $order_id, $old_status, $new_status, $admin_id, $change_reason);
    $stmt->execute();
}
?>