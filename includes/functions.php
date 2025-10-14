<?php
function getProducts($limit = 12, $category_slug = null, $search = '', $offset = 0) {
    global $conn;
    $join_type = $category_slug ? 'INNER' : 'LEFT';
    $sql = "SELECT p.*, c.name as category_name FROM products p $join_type JOIN categories c ON p.category_id = c.id WHERE p.is_active = 1";
    $params = [];
    $types = '';

    if ($category_slug && $category_slug !== 'all') {
        $sql .= " AND c.slug = ?";
        $params[] = $category_slug;
        $types .= 's';
    }

    if ($search) {
        $sql .= " AND (p.name LIKE ? OR p.description LIKE ? OR p.category LIKE ? OR c.name LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $types .= 'ssss';
    }

    $sql .= " ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $types .= 'ii';

    $stmt = $conn->prepare($sql);
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $products = $result->fetch_all(MYSQLI_ASSOC);

    // Process features JSON for each product
    foreach ($products as &$product) {
        if ($product['features']) {
            $product['features'] = json_decode($product['features'], true);
        } else {
            $product['features'] = [];
        }
    }

    return $products;
}

/**
 * Site settings helpers
 * - get_setting($key, $default = null)
 * - set_setting($key, $value, $type = 'string')
 * - get_all_settings()
 */
function get_all_settings() {
    static $cache = null;
    global $conn;

    if ($cache !== null) {
        return $cache;
    }

    $sql = "SELECT `key`, `value`, `type` FROM site_settings";
    $result = $conn->query($sql);
    $rows = $result->fetch_all(MYSQLI_ASSOC);
    $settings = [];
    foreach ($rows as $r) {
        $val = $r['value'];
        if ($r['type'] === 'json') {
            $decoded = json_decode($val, true);
            $val = $decoded === null ? [] : $decoded;
        }
        $settings[$r['key']] = $val;
    }

    $cache = $settings;
    return $cache;
}

function get_setting($key, $default = null) {
    $all = get_all_settings();
    return array_key_exists($key, $all) ? $all[$key] : $default;
}

function set_setting($key, $value, $type = 'string') {
    global $conn;

    // Normalize type and value
    $type = in_array($type, ['string','text','json','image']) ? $type : 'string';
    $dbValue = $value;
    if ($type === 'json') {
        $dbValue = json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    // Upsert style: try update, otherwise insert
    $sql = "INSERT INTO site_settings (`key`, `value`, `type`) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), `type` = VALUES(`type`), updated_at = CURRENT_TIMESTAMP";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sss', $key, $dbValue, $type);
    $ok = $stmt->execute();

    // Clear cache on success
    if ($ok) {
        // Clear static cache inside get_all_settings by setting it to null via reflection-like workaround
        // Simpler: reload settings by unsetting function cache using a global flag
        // We'll implement a simple approach: call the query directly to refresh
        // (Next call to get_all_settings will fetch fresh data because static cache is still set; so we manually clear it)
        // Workaround: use run-time reload by resetting static via anonymous function
        $reset = function() { static $cache = null; $cache = null; };
        $reset();
    }

    return $ok;
}

function getProductsCount($category_slug = null, $search = '') {
    global $conn;
    $join_type = $category_slug ? 'INNER' : 'LEFT';
    $sql = "SELECT COUNT(*) as total FROM products p $join_type JOIN categories c ON p.category_id = c.id WHERE p.is_active = 1";
    $params = [];
    $types = '';

    if ($category_slug && $category_slug !== 'all') {
        $sql .= " AND c.slug = ?";
        $params[] = $category_slug;
        $types .= 's';
    }

    if ($search) {
        $sql .= " AND (p.name LIKE ? OR p.description LIKE ? OR p.category LIKE ? OR c.name LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $types .= 'ssss';
    }

    $stmt = $conn->prepare($sql);
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['total'];
}

function getCategories() {
    global $conn;
    // Get categories with icon information
    $sql = "SELECT id, name, slug, description, icon_type, icon_class, sticker_url, svg_code FROM categories ORDER BY name";
    $result = $conn->query($sql);
    $categories = $result->fetch_all(MYSQLI_ASSOC);

    // Fetch product counts for all categories in a single query to avoid N+1 problem
    $countSql = "SELECT c.slug, COUNT(p.id) as count FROM categories c LEFT JOIN products p ON p.category_id = c.id AND p.is_active = 1 GROUP BY c.slug";

    $countResult = $conn->query($countSql);
    $counts = [];
    if ($countResult) {
        while ($r = $countResult->fetch_assoc()) {
            $counts[$r['slug']] = (int)$r['count'];
        }
    }

    // Map counts to categories (default to 0)
    foreach ($categories as &$category) {
        $category['count'] = isset($counts[$category['slug']]) ? $counts[$category['slug']] : 0;
    }

    return $categories;

}

function getCategoryIcon($category, $size = 'medium') {
    $iconHtml = '';

    switch ($category['icon_type']) {
        case 'fontawesome':
            $iconHtml = '<i class="' . htmlspecialchars($category['icon_class']) . ' text-emerald-600 text-3xl"></i>';
            break;
        case 'bootstrap':
            $iconHtml = '<i class="' . htmlspecialchars($category['icon_class']) . ' text-emerald-600 text-3xl"></i>';
            break;
        case 'sticker':
            if ($category['sticker_url']) {
                $iconHtml = '<img src="' . htmlspecialchars($category['sticker_url']) . '" alt="' . htmlspecialchars($category['name']) . '" class="w-6 h-6 object-contain" style="filter: brightness(0) saturate(100%) invert(21%) sepia(91%) saturate(1050%) hue-rotate(108deg) brightness(96%) contrast(104%);">';
            } else {
                // Fallback to Font Awesome if no sticker URL
                $iconHtml = '<i class="fas fa-tag text-emerald-600 text-3xl"></i>';
            }
            break;
        case 'svg':
            $svg = isset($category['svg_code']) && $category['svg_code'] ? $category['svg_code'] : '<svg viewBox="0 0 24 24" fill="#059669" xmlns="http://www.w3.org/2000/svg"><path d="M12 2L13.09 8.26L22 9L17 14L18.18 21L12 17.77L5.82 21L7 14L2 9L10.91 8.26L12 2Z"/><circle cx="12" cy="12" r="3" stroke="#059669" stroke-width="1" fill="none"/></svg>';
            $iconSize = $size === 'large' ? '48' : '48';
            // Always set width and height for proper scaling
            if (preg_match('/<svg/i', $svg)) {
                // Remove existing width and height if present
                $svg = preg_replace('/\s+width="[^"]*"/i', '', $svg);
                $svg = preg_replace('/\s+height="[^"]*"/i', '', $svg);
                // Add the desired width and height
                $svg = preg_replace('/<svg/i', '<svg width="' . $iconSize . '" height="' . $iconSize . '"', $svg);
            }
            // Replace currentColor with emerald color for guaranteed display
            $svg = str_replace('currentColor', '#059669', $svg);
            $iconHtml = $svg;
            break;
        default:
            // Default fallback
            $iconHtml = '<i class="fas fa-box text-emerald-600 text-3xl"></i>';
            break;
    }

    return $iconHtml;
}

function getProductById($id) {
    global $conn;
    $sql = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.id = ? AND p.is_active = 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();

    if ($product && $product['features']) {
        $product['features'] = json_decode($product['features'], true);
    } else if ($product) {
        $product['features'] = [];
    }

    return $product;
}

function getOrCreateUser($email, $name, $phone, $address) {
    global $conn;

    // Check if user exists by phone number
    $sql = "SELECT id FROM users WHERE phone = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        // Update user info if changed
        $sql = "UPDATE users SET name = ?, email = ?, address = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssi", $name, $email, $address, $user['id']);
        $stmt->execute();
        return $user['id'];
    } else {
        // Create new user with default password
        $default_password = password_hash('password123', PASSWORD_DEFAULT); // In real app, send SMS to set password
        $sql = "INSERT INTO users (name, email, phone, address, password) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssss", $name, $email, $phone, $address, $default_password);
        $stmt->execute();
        return $conn->insert_id;
    }
}

function createOrder($user_id, $total, $cart_items, $address, $area = '', $delivery_charge = 0, $promo_code = null, $discount_amount = 0) {
    global $conn;

    // Create order
    $sql = "INSERT INTO orders (user_id, total, address, delivery_area, delivery_charge, promo_code, discount_amount) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("idssdsd", $user_id, $total, $address, $area, $delivery_charge, $promo_code, $discount_amount);
    $stmt->execute();
    $order_id = $conn->insert_id;

    // Update promo code usage if applied
    if ($promo_code) {
        updatePromoCodeUsage($promo_code);
    }

    // Add order items
    foreach ($cart_items as $item) {
        $sql = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiid", $order_id, $item['id'], $item['quantity'], $item['price']);
        $stmt->execute();
    }

    return $order_id;
}

function getOrderById($order_id) {
    global $conn;
    $sql = "SELECT o.*, u.name as user_name, u.email as user_email, u.phone as user_phone, u.address as user_address FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function getOrderItems($order_id) {
    global $conn;
    $sql = "SELECT oi.*, p.name as product_name, p.image as product_image FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getRecentOrders($user_id, $limit = 5) {
    global $conn;
    $sql = "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $user_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getOrdersWithFilters($user_id, $filters = [], $sort = 'created_at', $order = 'DESC', $limit = 10, $offset = 0) {
    global $conn;
    
    $sql = "SELECT * FROM orders WHERE user_id = ?";
    $params = [$user_id];
    $types = 'i';
    
    // Add filters
    if (!empty($filters['status'])) {
        $sql .= " AND status = ?";
        $params[] = $filters['status'];
        $types .= 's';
    }
    
    if (!empty($filters['date_from'])) {
        $sql .= " AND DATE(created_at) >= ?";
        $params[] = $filters['date_from'];
        $types .= 's';
    }
    
    if (!empty($filters['date_to'])) {
        $sql .= " AND DATE(created_at) <= ?";
        $params[] = $filters['date_to'];
        $types .= 's';
    }
    
    if (!empty($filters['min_total'])) {
        $sql .= " AND total >= ?";
        $params[] = $filters['min_total'];
        $types .= 'd';
    }
    
    if (!empty($filters['max_total'])) {
        $sql .= " AND total <= ?";
        $params[] = $filters['max_total'];
        $types .= 'd';
    }
    
    // Add sorting
    $allowed_sort_fields = ['created_at', 'total', 'status', 'id'];
    $allowed_orders = ['ASC', 'DESC'];
    
    if (!in_array($sort, $allowed_sort_fields)) {
        $sort = 'created_at';
    }
    
    if (!in_array(strtoupper($order), $allowed_orders)) {
        $order = 'DESC';
    }
    
    $sql .= " ORDER BY $sort $order";
    
    // Add pagination
    $sql .= " LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $types .= 'ii';
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getOrdersCount($user_id, $filters = []) {
    global $conn;
    
    $sql = "SELECT COUNT(*) as count FROM orders WHERE user_id = ?";
    $params = [$user_id];
    $types = 'i';
    
    // Add filters
    if (!empty($filters['status'])) {
        $sql .= " AND status = ?";
        $params[] = $filters['status'];
        $types .= 's';
    }
    
    if (!empty($filters['date_from'])) {
        $sql .= " AND DATE(created_at) >= ?";
        $params[] = $filters['date_from'];
        $types .= 's';
    }
    
    if (!empty($filters['date_to'])) {
        $sql .= " AND DATE(created_at) <= ?";
        $params[] = $filters['date_to'];
        $types .= 's';
    }
    
    if (!empty($filters['min_total'])) {
        $sql .= " AND total >= ?";
        $params[] = $filters['min_total'];
        $types .= 'd';
    }
    
    if (!empty($filters['max_total'])) {
        $sql .= " AND total <= ?";
        $params[] = $filters['max_total'];
        $types .= 'd';
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['count'];
}

/**
 * Render a standardized favourite button for a product card.
 * Ensures the markup and classes match the index.php product card button.
 * Accepts a product array and optional position classes.
 *
 * Usage: echo renderFavouriteButton($product);
 */
function renderFavouriteButton($product, $posClasses = 'absolute top-3 right-3') {
    // Normalize product data with safe defaults
    $id = isset($product['id']) ? (int)$product['id'] : (int)($product['product_id'] ?? 0);
    $name = isset($product['name']) ? addslashes($product['name']) : '';
    $price = isset($product['price']) ? $product['price'] : 0;
    $image = isset($product['image']) ? addslashes($product['image']) : '';
    $size = isset($product['size']) ? addslashes($product['size']) : '';
    $badge = isset($product['badge']) ? addslashes($product['badge']) : '';
    $category = '';
    if (isset($product['category'])) $category = addslashes($product['category']);
    if (!$category && isset($product['category_name'])) $category = addslashes($product['category_name']);
    $rating = isset($product['rating']) ? (int)$product['rating'] : 0;
    $reviews = isset($product['reviews']) ? (int)$product['reviews'] : 0;
    $description = isset($product['description']) ? addslashes(substr($product['description'], 0, 200)) : '';
    $original_price = isset($product['original_price']) ? $product['original_price'] : 0;

    $classes = trim($posClasses . ' w-6 h-6 bg-transparent bg-opacity-01 rounded-full flex items-center justify-center text-red-500 hover:text-red-600 hover:bg-opacity-100 transition-all duration-300 shadow-lg');

    $onclick = "event.preventDefault(); event.stopPropagation(); toggleFavourite($id, '$name', $price, '$image', '$size', '$badge', '$category', $rating, $reviews, '$description', $original_price);";

    return '<button onclick="' . $onclick . '" id="fav-btn-' . $id . '" class="' . $classes . '"><i class="far fa-heart text-lg"></i></button>';
}

function getUserById($user_id) {
    global $conn;
    $sql = "SELECT * FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function getBanners() {
    global $conn;
    $sql = "SELECT * FROM banners WHERE is_active = 1 ORDER BY position ASC, created_at DESC";
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getPromoCodeByCode($code) {
    global $conn;
    $sql = "SELECT * FROM promo_codes WHERE code = ? AND is_active = 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function validatePromoCode($code, $subtotal) {
    $promo = getPromoCodeByCode($code);

    if (!$promo) {
        return ['valid' => false, 'message' => 'Invalid promo code'];
    }

    // Check expiry date
    if ($promo['expiry_date'] && strtotime($promo['expiry_date']) < time()) {
        return ['valid' => false, 'message' => 'Promo code has expired'];
    }

    // Check minimum order amount
    if ($subtotal < $promo['min_order_amount']) {
        return ['valid' => false, 'message' => 'Minimum order amount is ৳' . number_format($promo['min_order_amount'], 2)];
    }

    // Check usage limit
    if ($promo['usage_limit'] && $promo['used_count'] >= $promo['usage_limit']) {
        return ['valid' => false, 'message' => 'Promo code usage limit exceeded'];
    }

    return ['valid' => true, 'promo' => $promo];
}

function applyPromoCode($code, $subtotal) {
    $validation = validatePromoCode($code, $subtotal);

    if (!$validation['valid']) {
        return $validation;
    }

    $promo = $validation['promo'];
    $discount = 0;

    if ($promo['discount_type'] === 'percentage') {
        $discount = ($subtotal * $promo['discount_value']) / 100;
        // Apply max discount limit if set
        if ($promo['max_discount'] && $discount > $promo['max_discount']) {
            $discount = $promo['max_discount'];
        }
    } else { // fixed amount
        $discount = $promo['discount_value'];
        // Ensure discount doesn't exceed subtotal
        if ($discount > $subtotal) {
            $discount = $subtotal;
        }
    }

    return [
        'valid' => true,
        'discount' => round($discount, 2),
        'promo' => $promo,
        'message' => 'Promo code applied successfully!'
    ];
}

function updatePromoCodeUsage($code) {
    global $conn;
    $sql = "UPDATE promo_codes SET used_count = used_count + 1 WHERE code = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $code);
    $stmt->execute();
}

function getOrderStatusHistory($order_id) {
    global $conn;
    $sql = "SELECT osh.*, a.username as changed_by_name FROM order_status_history osh LEFT JOIN admins a ON osh.changed_by = a.id WHERE osh.order_id = ? ORDER BY osh.created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}
?>