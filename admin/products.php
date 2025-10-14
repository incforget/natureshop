<?php
$page_title = 'Product Management';
include_once '../includes/config.php';
include_once 'includes/functions.php';
requireAdminLogin();

// Check if this is an AJAX request for product data
$isAjaxRequest = isset($_GET['ajax']) && $_GET['ajax'] === 'products';

if ($isAjaxRequest) {
    // Handle AJAX request for product data
    handleAjaxProductsRequest();
    exit;
}

// Function to handle AJAX requests for product data
function handleAjaxProductsRequest() {
    global $conn;
    
    // Set content type to JSON
    header('Content-Type: application/json');

    // Get parameters
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $per_page = 10;
    $offset = ($page - 1) * $per_page;

    $search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
    $category_filter = isset($_GET['category']) ? sanitizeInput($_GET['category']) : '';

    // Build query
    $sql = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id OR p.category = c.slug WHERE 1=1";
    $params = [];
    $types = '';

    if ($search) {
        $sql .= " AND (p.name LIKE ? OR p.description LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $types .= 'ss';
    }

    if ($category_filter && $category_filter !== 'all') {
        $sql .= " AND (c.slug = ? OR p.category = ?)";
        $params[] = $category_filter;
        $params[] = $category_filter;
        $types .= 'ss';
    }

    $sql .= " ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
    $params[] = $per_page;
    $params[] = $offset;
    $types .= 'ii';

    $stmt = $conn->prepare($sql);
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Get total count
    $sql_count = "SELECT COUNT(*) as total FROM products p LEFT JOIN categories c ON p.category_id = c.id OR p.category = c.slug WHERE 1=1";
    $params_count = [];
    $types_count = '';

    if ($search) {
        $sql_count .= " AND (p.name LIKE ? OR p.description LIKE ?)";
        $params_count[] = "%$search%";
        $params_count[] = "%$search%";
        $types_count .= 'ss';
    }

    if ($category_filter && $category_filter !== 'all') {
        $sql_count .= " AND (c.slug = ? OR p.category = ?)";
        $params_count[] = $category_filter;
        $params_count[] = $category_filter;
        $types_count .= 'ss';
    }

    $stmt_count = $conn->prepare($sql_count);
    if ($params_count) {
        $stmt_count->bind_param($types_count, ...$params_count);
    }
    $stmt_count->execute();
    $total_products = $stmt_count->get_result()->fetch_assoc()['total'];
    $total_pages = ceil($total_products / $per_page);

    // Generate HTML for desktop table
    $desktop_html = '';
    if (empty($products)) {
        $desktop_html = '<tr><td colspan="8" class="px-6 py-4 text-center text-gray-500">No products found</td></tr>';
    } else {
        foreach ($products as $product) {
            $desktop_html .= '<tr class="hover:bg-gray-50">';
            $desktop_html .= '<td class="px-6 py-4 whitespace-nowrap">';
            $desktop_html .= '<div class="h-12 w-12 rounded-lg bg-gray-200 flex items-center justify-center overflow-hidden">';
            if ($product['image']) {
                $desktop_html .= '<img src="../assets/images/' . htmlspecialchars($product['image']) . '" alt="' . htmlspecialchars($product['name']) . '" class="h-full w-full object-cover">';
            } else {
                $desktop_html .= '<i class="fas fa-box text-gray-500 text-lg"></i>';
            }
            $desktop_html .= '</div></td>';

            $desktop_html .= '<td class="px-6 py-4 whitespace-nowrap">';
            $desktop_html .= '<div class="flex items-center">';
            $desktop_html .= '<div class="ml-4">';
            $desktop_html .= '<div class="text-sm font-medium text-gray-900">' . htmlspecialchars($product['name']) . '</div>';
            $desktop_html .= '<div class="text-sm text-gray-500">' . htmlspecialchars(substr($product['description'], 0, 50)) . (strlen($product['description']) > 50 ? '...' : '') . '</div>';
            $desktop_html .= '</div></div></td>';

            $desktop_html .= '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . htmlspecialchars($product['category_name'] ?? 'N/A') . '</td>';

            $desktop_html .= '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">';
            $desktop_html .= '৳' . number_format($product['price'], 2);
            if ($product['original_price']) {
                $desktop_html .= '<span class="text-gray-500 line-through ml-1">৳' . number_format($product['original_price'], 2) . '</span>';
            }
            $desktop_html .= '</td>';

            $desktop_html .= '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . $product['stock'] . '</td>';
            $desktop_html .= '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . htmlspecialchars($product['size'] ?? 'N/A') . '</td>';

            $desktop_html .= '<td class="px-6 py-4 whitespace-nowrap">';
            $desktop_html .= '<span class="px-2 py-1 text-xs font-semibold rounded-full ' . ($product['is_active'] ? 'text-green-800 bg-green-100' : 'text-red-800 bg-red-100') . '">';
            $desktop_html .= $product['is_active'] ? 'Active' : 'Inactive';
            $desktop_html .= '</span></td>';

            $desktop_html .= '<td class="px-6 py-4 whitespace-nowrap text-sm font-medium">';
            $desktop_html .= '<div class="flex space-x-2">';
            $desktop_html .= '<a href="?action=edit&id=' . $product['id'] . '" class="text-blue-600 hover:text-blue-900 transition-colors" title="Edit"><i class="fas fa-edit"></i></a>';
            $desktop_html .= '<form method="POST" class="inline" onsubmit="return confirmDelete(\'Are you sure you want to delete this product?\')">';
            $desktop_html .= '<input type="hidden" name="action" value="delete">';
            $desktop_html .= '<input type="hidden" name="id" value="' . $product['id'] . '">';
            $desktop_html .= '<button type="submit" class="text-red-600 hover:text-red-900 transition-colors" title="Delete"><i class="fas fa-trash"></i></button>';
            $desktop_html .= '</form></div></td>';

            $desktop_html .= '</tr>';
        }
    }

    // Generate HTML for mobile cards
    $mobile_html = '';
    if (empty($products)) {
        $mobile_html = '<div class="px-6 py-8 text-center text-gray-500"><i class="fas fa-box text-4xl text-gray-300 mb-4"></i><p>No products found</p></div>';
    } else {
        $mobile_html = '<div class="divide-y divide-gray-200">';
        foreach ($products as $product) {
            $mobile_html .= '<div class="p-4 hover:bg-gray-50">';
            $mobile_html .= '<div class="flex items-start space-x-4">';
            $mobile_html .= '<div class="h-16 w-16 rounded-lg bg-gray-200 flex items-center justify-center overflow-hidden flex-shrink-0">';
            if ($product['image']) {
                $mobile_html .= '<img src="../assets/images/' . htmlspecialchars($product['image']) . '" alt="' . htmlspecialchars($product['name']) . '" class="h-full w-full object-cover">';
            } else {
                $mobile_html .= '<i class="fas fa-box text-gray-500 text-xl"></i>';
            }
            $mobile_html .= '</div>';

            $mobile_html .= '<div class="flex-1 min-w-0">';
            $mobile_html .= '<div class="flex items-start justify-between">';
            $mobile_html .= '<div class="flex-1">';
            $mobile_html .= '<h4 class="text-sm font-medium text-gray-900 truncate">' . htmlspecialchars($product['name']) . '</h4>';
            $mobile_html .= '<p class="text-sm text-gray-500 mt-1">' . htmlspecialchars(substr($product['description'], 0, 60)) . (strlen($product['description']) > 60 ? '...' : '') . '</p>';
            $mobile_html .= '<div class="flex items-center space-x-4 mt-2">';
            $mobile_html .= '<span class="text-sm font-medium text-gray-900">৳' . number_format($product['price'], 2) . '</span>';
            if ($product['original_price']) {
                $mobile_html .= '<span class="text-sm text-gray-500 line-through">৳' . number_format($product['original_price'], 2) . '</span>';
            }
            $mobile_html .= '<span class="text-xs text-gray-500">Stock: ' . $product['stock'] . '</span></div>';
            $mobile_html .= '<div class="flex items-center space-x-2 mt-1">';
            $mobile_html .= '<span class="text-xs text-gray-500">' . htmlspecialchars($product['category_name'] ?? 'N/A') . '</span>';
            if ($product['size']) {
                $mobile_html .= '<span class="text-xs text-gray-500">• ' . htmlspecialchars($product['size']) . '</span>';
            }
            $mobile_html .= '</div></div>';

            $mobile_html .= '<div class="flex flex-col items-end space-y-2">';
            $mobile_html .= '<span class="px-2 py-1 text-xs font-semibold rounded-full ' . ($product['is_active'] ? 'text-green-800 bg-green-100' : 'text-red-800 bg-red-100') . '">';
            $mobile_html .= $product['is_active'] ? 'Active' : 'Inactive';
            $mobile_html .= '</span>';
            $mobile_html .= '<div class="flex space-x-3">';
            $mobile_html .= '<a href="?action=edit&id=' . $product['id'] . '" class="text-blue-600 hover:text-blue-900 transition-colors"><i class="fas fa-edit text-sm"></i></a>';
            $mobile_html .= '<form method="POST" class="inline" onsubmit="return confirmDelete(\'Are you sure you want to delete this product?\')">';
            $mobile_html .= '<input type="hidden" name="action" value="delete">';
            $mobile_html .= '<input type="hidden" name="id" value="' . $product['id'] . '">';
            $mobile_html .= '<button type="submit" class="text-red-600 hover:text-red-900 transition-colors"><i class="fas fa-trash text-sm"></i></button>';
            $mobile_html .= '</form></div></div></div></div></div>';
        }
        $mobile_html .= '</div>';
    }

    // Generate pagination HTML
    $pagination_html = '';
    if ($total_pages > 1) {
        $pagination_html = '<div class="px-6 py-4 border-t border-gray-200">';
        $pagination_html .= '<div class="flex flex-col sm:flex-row items-center justify-between space-y-4 sm:space-y-0">';
        $pagination_html .= '<div class="text-sm text-gray-700">Showing ' . ($offset + 1) . ' to ' . min($offset + $per_page, $total_products) . ' of ' . $total_products . ' products</div>';
        $pagination_html .= '<div class="flex items-center space-x-2">';

        if ($page > 1) {
            $pagination_html .= '<button type="button" class="px-3 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50 transition-colors pagination-btn" data-page="' . ($page - 1) . '">';
            $pagination_html .= '<i class="fas fa-chevron-left mr-1"></i>Previous</button>';
        }

        $pagination_html .= '<div class="flex space-x-1">';

        $start_page = max(1, $page - 2);
        $end_page = min($total_pages, $page + 2);

        if ($start_page > 1) {
            $pagination_html .= '<button type="button" class="px-3 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50 transition-colors pagination-btn" data-page="1">1</button>';
            if ($start_page > 2) {
                $pagination_html .= '<span class="px-2 py-2 text-gray-500">...</span>';
            }
        }

        for ($i = $start_page; $i <= $end_page; $i++) {
            $pagination_html .= '<button type="button" class="px-3 py-2 border rounded-lg text-sm transition-colors pagination-btn ' . ($i === $page ? 'bg-blue-600 text-white border-blue-600' : 'border-gray-300 hover:bg-gray-50') . '" data-page="' . $i . '">' . $i . '</button>';
        }

        if ($end_page < $total_pages) {
            if ($end_page < $total_pages - 1) {
                $pagination_html .= '<span class="px-2 py-2 text-gray-500">...</span>';
            }
            $pagination_html .= '<button type="button" class="px-3 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50 transition-colors pagination-btn" data-page="' . $total_pages . '">' . $total_pages . '</button>';
        }

        $pagination_html .= '</div>';

        if ($page < $total_pages) {
            $pagination_html .= '<button type="button" class="px-3 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50 transition-colors pagination-btn" data-page="' . ($page + 1) . '">';
            $pagination_html .= 'Next<i class="fas fa-chevron-right ml-1"></i></button>';
        }

        $pagination_html .= '</div></div></div>';
    }

    // Return JSON response
    echo json_encode([
        'success' => true,
        'total_products' => $total_products,
        'current_page' => $page,
        'total_pages' => $total_pages,
        'desktop_html' => $desktop_html,
        'mobile_html' => $mobile_html,
        'pagination_html' => $pagination_html
    ]);
}
include 'includes/header.php';

// Handle actions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        if ($action === 'add' || $action === 'edit') {
            // Handle add/edit product
            $name = sanitizeInput($_POST['name']);
            $slug = sanitizeInput($_POST['slug']);
            $description = sanitizeInput($_POST['description']);
            $price = (float)$_POST['price'];
            $original_price = !empty($_POST['original_price']) ? (float)$_POST['original_price'] : null;
            $category_id = (int)$_POST['category_id'];
            $stock = (int)$_POST['stock'];
            $rating = (float)$_POST['rating'];
            $badge = sanitizeInput($_POST['badge']);
            $size = sanitizeInput($_POST['size']);
            
            // Handle features
            $features_text = trim($_POST['features_text'] ?? '');
            $features_array = array_filter(array_map('trim', explode("\n", $features_text)));
            $features = json_encode(array_values($features_array));
            
            // Handle image upload
            $image = '';
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../assets/images/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                
                if (in_array($file_extension, $allowed_extensions)) {
                    $new_filename = $slug . '.' . $file_extension;
                    $upload_path = $upload_dir . $new_filename;
                    
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                        $image = $new_filename;
                    }
                }
            } elseif ($action === 'edit' && isset($_POST['current_image'])) {
                // Keep existing image if no new image uploaded
                $image = sanitizeInput($_POST['current_image']);
            }
            
            $is_active = isset($_POST['is_active']) ? 1 : 0;

            if ($action === 'add') {
                $sql = "INSERT INTO products (name, slug, description, price, original_price, category_id, image, stock, rating, badge, features, size, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssdissdsisss", $name, $slug, $description, $price, $original_price, $category_id, $image, $stock, $rating, $badge, $features, $size, $is_active);

                if ($stmt->execute()) {
                    $message = 'Product added successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Error adding product: ' . $conn->error;
                    $message_type = 'error';
                }
            } else {
                $id = (int)$_POST['id'];
                $sql = "UPDATE products SET name=?, slug=?, description=?, price=?, original_price=?, category_id=?, image=?, stock=?, rating=?, badge=?, features=?, size=?, is_active=? WHERE id=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssdissdsissis", $name, $slug, $description, $price, $original_price, $category_id, $image, $stock, $rating, $badge, $features, $size, $is_active, $id);

                if ($stmt->execute()) {
                    $message = 'Product updated successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Error updating product: ' . $conn->error;
                    $message_type = 'error';
                }
            }
        } elseif ($action === 'delete') {
            $id = (int)$_POST['id'];
            $sql = "DELETE FROM products WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);

            if ($stmt->execute()) {
                $message = 'Product deleted successfully!';
                $message_type = 'success';
            } else {
                $message = 'Error deleting product: ' . $conn->error;
                $message_type = 'error';
            }
        }
    }
}

// Get products with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? sanitizeInput($_GET['category']) : '';

$sql = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id OR p.category = c.slug WHERE 1=1";
$params = [];
$types = '';

if ($search) {
    $sql .= " AND (p.name LIKE ? OR p.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= 'ss';
}

if ($category_filter && $category_filter !== 'all') {
    $sql .= " AND (c.slug = ? OR p.category = ?)";
    $params[] = $category_filter;
    $params[] = $category_filter;
    $types .= 'ss';
}

$sql .= " ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;
$types .= 'ii';

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get total count for pagination
$sql_count = "SELECT COUNT(*) as total FROM products p LEFT JOIN categories c ON p.category_id = c.id OR p.category = c.slug WHERE 1=1";
$params_count = [];
$types_count = '';

if ($search) {
    $sql_count .= " AND (p.name LIKE ? OR p.description LIKE ?)";
    $params_count[] = "%$search%";
    $params_count[] = "%$search%";
    $types_count .= 'ss';
}

if ($category_filter && $category_filter !== 'all') {
    $sql_count .= " AND (c.slug = ? OR p.category = ?)";
    $params_count[] = $category_filter;
    $params_count[] = $category_filter;
    $types_count .= 'ss';
}

$stmt_count = $conn->prepare($sql_count);
if ($params_count) {
    $stmt_count->bind_param($types_count, ...$params_count);
}
$stmt_count->execute();
$total_products = $stmt_count->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_products / $per_page);

// Get categories for filter and form
$categories = $conn->query("SELECT id, name, slug FROM categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);

// Get product for editing
$edit_product = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $edit_product = $stmt->get_result()->fetch_assoc();
}
?>

<?php if ($message): ?>
    <div class="mb-6 p-4 rounded-lg shadow-sm <?php echo $message_type === 'success' ? 'bg-green-50 border border-green-200 text-green-700' : 'bg-red-50 border border-red-200 text-red-700'; ?>">
        <i class="fas <?php echo $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?> mr-2"></i>
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<!-- Filters and Search -->
<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <div class="flex flex-col lg:flex-row gap-4 items-start lg:items-center justify-between">
        <div class="flex flex-col sm:flex-row gap-4 flex-1 w-full">
            <div class="flex flex-col sm:flex-row gap-2 flex-1" id="search-filters">
                <input type="text" id="search-input" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search products by name or description..." class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                <select id="category-filter" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm min-w-0 sm:min-w-[200px]">
                    <option value="all">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['slug']; ?>" <?php echo $category_filter === $cat['slug'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="button" id="search-btn" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed">
                    <i class="fas fa-search mr-2"></i>Search
                </button>
                <button type="button" id="clear-filters-btn" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors text-sm font-medium">
                    <i class="fas fa-times mr-2"></i>Clear
                </button>
            </div>
        </div>
        <a href="?action=add" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm font-medium whitespace-nowrap">
            <i class="fas fa-plus mr-2"></i>Add Product
        </a>
    </div>
</div>

<?php if (isset($_GET['action']) && ($_GET['action'] === 'add' || $_GET['action'] === 'edit')): ?>
    <!-- Add/Edit Product Form -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-semibold text-gray-900">
                <?php echo $_GET['action'] === 'add' ? 'Add New Product' : 'Edit Product'; ?>
            </h3>
            <a href="products.php" class="text-gray-400 hover:text-gray-600 transition-colors">
                <i class="fas fa-times text-xl"></i>
            </a>
        </div>

        <form method="POST" enctype="multipart/form-data" class="space-y-6">
            <input type="hidden" name="action" value="<?php echo $_GET['action']; ?>">
            <?php if ($edit_product): ?>
                <input type="hidden" name="id" value="<?php echo $edit_product['id']; ?>">
                <input type="hidden" name="current_image" value="<?php echo htmlspecialchars($edit_product['image'] ?? ''); ?>">
            <?php endif; ?>

            <!-- Basic Information -->
            <div class="bg-gray-50 rounded-lg p-4">
                <h4 class="text-lg font-medium text-gray-900 mb-4">Basic Information</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Product Name *</label>
                        <input type="text" name="name" required value="<?php echo $edit_product ? htmlspecialchars($edit_product['name']) : ''; ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Enter product name">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Slug *</label>
                        <input type="text" name="slug" required value="<?php echo $edit_product ? htmlspecialchars($edit_product['slug']) : ''; ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="url-friendly-name">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                        <textarea name="description" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Describe your product..."><?php echo $edit_product ? htmlspecialchars($edit_product['description']) : ''; ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Pricing & Inventory -->
            <div class="bg-gray-50 rounded-lg p-4">
                <h4 class="text-lg font-medium text-gray-900 mb-4">Pricing & Inventory</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Price (৳) *</label>
                        <input type="number" name="price" step="0.01" required value="<?php echo $edit_product ? $edit_product['price'] : ''; ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="0.00">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Original Price (৳)</label>
                        <input type="number" name="original_price" step="0.01" value="<?php echo $edit_product && $edit_product['original_price'] ? $edit_product['original_price'] : ''; ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="0.00">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Stock</label>
                        <input type="number" name="stock" value="<?php echo $edit_product ? $edit_product['stock'] : '0'; ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="0">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Rating</label>
                        <input type="number" name="rating" step="0.1" min="0" max="5" value="<?php echo $edit_product ? $edit_product['rating'] : '0'; ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="0.0">
                    </div>
                </div>
            </div>

            <!-- Categories & Attributes -->
            <div class="bg-gray-50 rounded-lg p-4">
                <h4 class="text-lg font-medium text-gray-900 mb-4">Categories & Attributes</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Category *</label>
                        <select name="category_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo $edit_product && $edit_product['category_id'] == $cat['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Badge</label>
                        <input type="text" name="badge" value="<?php echo $edit_product ? htmlspecialchars($edit_product['badge']) : ''; ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="e.g., New, Sale, Hot">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Size</label>
                        <input type="text" name="size" value="<?php echo $edit_product ? htmlspecialchars($edit_product['size']) : ''; ?>" placeholder="e.g., 500g, 250ml, 1kg" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </div>
            </div>

            <!-- Features -->
            <div class="bg-gray-50 rounded-lg p-4">
                <h4 class="text-lg font-medium text-gray-900 mb-4">Features</h4>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Features (one per line)</label>
                    <textarea name="features_text" rows="4" placeholder="Enter each feature on a new line" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"><?php
                        if ($edit_product && $edit_product['features']) {
                            $features_array = json_decode($edit_product['features'], true);
                            if (is_array($features_array)) {
                                echo htmlspecialchars(implode("\n", $features_array));
                            }
                        }
                    ?></textarea>
                    <p class="text-sm text-gray-500 mt-1">Enter each feature on a separate line. These will be displayed as bullet points on the product page.</p>
                </div>
            </div>

            <!-- Image Upload -->
            <div class="bg-gray-50 rounded-lg p-4">
                <h4 class="text-lg font-medium text-gray-900 mb-4">Product Image</h4>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Upload Image</label>
                    <input type="file" name="image" accept="image/*" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent file:mr-4 file:py-2 file:px-4 file:rounded-l-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    <?php if ($edit_product && $edit_product['image']): ?>
                        <div class="mt-4">
                            <p class="text-sm text-gray-600 mb-2">Current image:</p>
                            <div class="flex items-center space-x-4">
                                <img src="../assets/images/<?php echo htmlspecialchars($edit_product['image']); ?>" alt="Current product image" class="h-20 w-20 object-cover rounded-lg border border-gray-200">
                                <div>
                                    <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($edit_product['image']); ?></p>
                                    <p class="text-xs text-gray-500">Image will be replaced if you upload a new one</p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Status -->
            <div class="bg-gray-50 rounded-lg p-4">
                <div class="flex items-center">
                    <input type="checkbox" name="is_active" id="is_active" <?php echo !$edit_product || $edit_product['is_active'] ? 'checked' : ''; ?> class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                    <label for="is_active" class="ml-2 block text-sm text-gray-900 font-medium">Active (visible to customers)</label>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row gap-4 pt-6 border-t border-gray-200">
                <button type="submit" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium">
                    <i class="fas fa-save mr-2"></i><?php echo $_GET['action'] === 'add' ? 'Add Product' : 'Update Product'; ?>
                </button>
                <a href="products.php" class="px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors font-medium text-center">
                    <i class="fas fa-times mr-2"></i>Cancel
                </a>
            </div>
        </form>
    </div>
<?php endif; ?>

<!-- Products Table -->
<div id="products-container">
<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900">Products (<span id="total-count"><?php echo $total_products; ?></span>)</h3>
    </div>

    <!-- Desktop Table View -->
    <div class="hidden md:block">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Image</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Size</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="8" class="px-6 py-4 text-center text-gray-500">No products found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($products as $product): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="h-12 w-12 rounded-lg bg-gray-200 flex items-center justify-center overflow-hidden">
                                        <?php if ($product['image']): ?>
                                            <img src="../assets/images/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="h-full w-full object-cover">
                                        <?php else: ?>
                                            <i class="fas fa-box text-gray-500 text-lg"></i>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($product['name']); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars(substr($product['description'], 0, 50)) . (strlen($product['description']) > 50 ? '...' : ''); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars($product['category_name'] ?? 'N/A'); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    ৳<?php echo number_format($product['price'], 2); ?>
                                    <?php if ($product['original_price']): ?>
                                        <span class="text-gray-500 line-through ml-1">৳<?php echo number_format($product['original_price'], 2); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo $product['stock']; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars($product['size'] ?? 'N/A'); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $product['is_active'] ? 'text-green-800 bg-green-100' : 'text-red-800 bg-red-100'; ?>">
                                        <?php echo $product['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <a href="?action=edit&id=<?php echo $product['id']; ?>" class="text-blue-600 hover:text-blue-900 transition-colors" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form method="POST" class="inline" onsubmit="return confirmDelete('Are you sure you want to delete this product?')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                                            <button type="submit" class="text-red-600 hover:text-red-900 transition-colors" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Mobile Card View -->
    <div class="md:hidden">
        <?php if (empty($products)): ?>
            <div class="px-6 py-8 text-center text-gray-500">
                <i class="fas fa-box text-4xl text-gray-300 mb-4"></i>
                <p>No products found</p>
            </div>
        <?php else: ?>
            <div class="divide-y divide-gray-200">
                <?php foreach ($products as $product): ?>
                    <div class="p-4 hover:bg-gray-50">
                        <div class="flex items-start space-x-4">
                            <div class="h-16 w-16 rounded-lg bg-gray-200 flex items-center justify-center overflow-hidden flex-shrink-0">
                                <?php if ($product['image']): ?>
                                    <img src="../assets/images/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="h-full w-full object-cover">
                                <?php else: ?>
                                    <i class="fas fa-box text-gray-500 text-xl"></i>
                                <?php endif; ?>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <h4 class="text-sm font-medium text-gray-900 truncate"><?php echo htmlspecialchars($product['name']); ?></h4>
                                        <p class="text-sm text-gray-500 mt-1"><?php echo htmlspecialchars(substr($product['description'], 0, 60)) . (strlen($product['description']) > 60 ? '...' : ''); ?></p>
                                        <div class="flex items-center space-x-4 mt-2">
                                            <span class="text-sm font-medium text-gray-900">৳<?php echo number_format($product['price'], 2); ?></span>
                                            <?php if ($product['original_price']): ?>
                                                <span class="text-sm text-gray-500 line-through">৳<?php echo number_format($product['original_price'], 2); ?></span>
                                            <?php endif; ?>
                                            <span class="text-xs text-gray-500">Stock: <?php echo $product['stock']; ?></span>
                                        </div>
                                        <div class="flex items-center space-x-2 mt-1">
                                            <span class="text-xs text-gray-500"><?php echo htmlspecialchars($product['category_name'] ?? 'N/A'); ?></span>
                                            <?php if ($product['size']): ?>
                                                <span class="text-xs text-gray-500">• <?php echo htmlspecialchars($product['size']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="flex flex-col items-end space-y-2">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $product['is_active'] ? 'text-green-800 bg-green-100' : 'text-red-800 bg-red-100'; ?>">
                                            <?php echo $product['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                        <div class="flex space-x-3">
                                            <a href="?action=edit&id=<?php echo $product['id']; ?>" class="text-blue-600 hover:text-blue-900 transition-colors">
                                                <i class="fas fa-edit text-sm"></i>
                                            </a>
                                            <form method="POST" class="inline" onsubmit="return confirmDelete('Are you sure you want to delete this product?')">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                                                <button type="submit" class="text-red-600 hover:text-red-900 transition-colors">
                                                    <i class="fas fa-trash text-sm"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <div class="px-6 py-4 border-t border-gray-200">
            <div class="flex flex-col sm:flex-row items-center justify-between space-y-4 sm:space-y-0">
                <div class="text-sm text-gray-700">
                    Showing <?php echo ($offset + 1); ?> to <?php echo min($offset + $per_page, $total_products); ?> of <?php echo $total_products; ?> products
                </div>
                <div class="flex items-center space-x-2">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $category_filter && $category_filter !== 'all' ? '&category=' . urlencode($category_filter) : ''; ?>" class="px-3 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50 transition-colors">
                            <i class="fas fa-chevron-left mr-1"></i>Previous
                        </a>
                    <?php endif; ?>

                    <div class="flex space-x-1">
                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        if ($start_page > 1): ?>
                            <a href="?page=1<?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $category_filter && $category_filter !== 'all' ? '&category=' . urlencode($category_filter) : ''; ?>" class="px-3 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50 transition-colors">1</a>
                            <?php if ($start_page > 2): ?>
                                <span class="px-2 py-2 text-gray-500">...</span>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                            <a href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $category_filter && $category_filter !== 'all' ? '&category=' . urlencode($category_filter) : ''; ?>" class="px-3 py-2 border rounded-lg text-sm transition-colors <?php echo $i === $page ? 'bg-blue-600 text-white border-blue-600' : 'border-gray-300 hover:bg-gray-50'; ?>"><?php echo $i; ?></a>
                        <?php endfor; ?>

                        <?php if ($end_page < $total_pages): ?>
                            <?php if ($end_page < $total_pages - 1): ?>
                                <span class="px-2 py-2 text-gray-500">...</span>
                            <?php endif; ?>
                            <a href="?page=<?php echo $total_pages; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $category_filter && $category_filter !== 'all' ? '&category=' . urlencode($category_filter) : ''; ?>" class="px-3 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50 transition-colors"><?php echo $total_pages; ?></a>
                        <?php endif; ?>
                    </div>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $category_filter && $category_filter !== 'all' ? '&category=' . urlencode($category_filter) : ''; ?>" class="px-3 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50 transition-colors">
                            Next<i class="fas fa-chevron-right ml-1"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

    </div>
</div>
</div>

<script>
// AJAX Search and Filter functionality
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('search-input');
    const categoryFilter = document.getElementById('category-filter');
    const searchBtn = document.getElementById('search-btn');
    const clearFiltersBtn = document.getElementById('clear-filters-btn');
    const productsContainer = document.getElementById('products-container');
    const totalCount = document.getElementById('total-count');

    let currentPage = <?php echo $page; ?>;
    let searchTimeout;
    let isLoading = false;

    // Function to perform AJAX search
    function performSearch(page = 1) {
        if (isLoading) return;

        const searchTerm = searchInput.value.trim();
        const categoryValue = categoryFilter.value;

        // Show loading state
        isLoading = true;
        searchBtn.disabled = true;
        searchBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Searching...';

        // Update URL without reloading
        const urlParams = new URLSearchParams(window.location.search);
        if (searchTerm) {
            urlParams.set('search', searchTerm);
        } else {
            urlParams.delete('search');
        }
        if (categoryValue && categoryValue !== 'all') {
            urlParams.set('category', categoryValue);
        } else {
            urlParams.delete('category');
        }
        urlParams.set('page', page);

        // Update browser history
        const newUrl = window.location.pathname + (urlParams.toString() ? '?' + urlParams.toString() : '');
        history.pushState({page: page, search: searchTerm, category: categoryValue}, '', newUrl);

        // Make AJAX request
        fetch('products.php?ajax=products&' + urlParams.toString())
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update total count
                    totalCount.textContent = data.total_products;

                    // Update desktop table
                    const desktopTable = productsContainer.querySelector('.hidden.md\\:block tbody');
                    if (desktopTable) {
                        desktopTable.innerHTML = data.desktop_html;
                    }

                    // Update mobile cards
                    const mobileContainer = productsContainer.querySelector('.md\\:hidden');
                    if (mobileContainer) {
                        mobileContainer.innerHTML = data.mobile_html;
                    }

                    // Update pagination
                    let paginationContainer = productsContainer.querySelector('.border-t.border-gray-200');
                    if (paginationContainer) {
                        paginationContainer.remove();
                    }

                    if (data.pagination_html) {
                        productsContainer.querySelector('.bg-white.rounded-lg.shadow-md.overflow-hidden').insertAdjacentHTML('beforeend', data.pagination_html);
                    }

                    currentPage = data.current_page;

                    // Re-attach pagination event listeners
                    attachPaginationListeners();
                } else {
                    console.error('Search failed:', data.error);
                }
            })
            .catch(error => {
                console.error('AJAX Error:', error);
                alert('An error occurred while searching. Please try again.');
            })
            .finally(() => {
                isLoading = false;
                searchBtn.disabled = false;
                searchBtn.innerHTML = '<i class="fas fa-search mr-2"></i>Search';
            });
    }

    // Function to attach pagination listeners
    function attachPaginationListeners() {
        const paginationBtns = document.querySelectorAll('.pagination-btn');
        paginationBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const page = parseInt(this.dataset.page);
                if (page && page !== currentPage) {
                    performSearch(page);
                }
            });
        });
    }

    // Search input with debouncing
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            performSearch(1); // Reset to first page when searching
        }, 500);
    });

    // Category filter change
    categoryFilter.addEventListener('change', function() {
        performSearch(1); // Reset to first page when filtering
    });

    // Search button click
    searchBtn.addEventListener('click', function() {
        performSearch(1);
    });

    // Clear filters button
    clearFiltersBtn.addEventListener('click', function() {
        searchInput.value = '';
        categoryFilter.value = 'all';
        performSearch(1);
    });

    // Enter key support
    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            performSearch(1);
        }
    });

    // Handle browser back/forward buttons
    window.addEventListener('popstate', function(e) {
        if (e.state) {
            searchInput.value = e.state.search || '';
            categoryFilter.value = e.state.category || 'all';
            performSearch(e.state.page || 1);
        }
    });

    // Initial pagination listeners
    attachPaginationListeners();

    // Auto-generate slug from name
    const nameInput = document.querySelector('input[name="name"]');
    const slugInput = document.querySelector('input[name="slug"]');

    if (nameInput && slugInput) {
        nameInput.addEventListener('input', function() {
            if (!slugInput.dataset.userModified) {
                slugInput.value = this.value
                    .toLowerCase()
                    .replace(/[^a-z0-9\s-]/g, '')
                    .replace(/\s+/g, '-')
                    .replace(/-+/g, '-')
                    .trim();
            }
        });

        slugInput.addEventListener('input', function() {
            this.dataset.userModified = true;
        });
    }

    // Image preview
    const imageInput = document.querySelector('input[name="image"]');
    if (imageInput) {
        imageInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Basic validation
                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Please select a valid image file (JPEG, PNG, GIF, or WebP).');
                    this.value = '';
                    return;
                }

                // Check file size (5MB limit)
                if (file.size > 5 * 1024 * 1024) {
                    alert('Image file size must be less than 5MB.');
                    this.value = '';
                    return;
                }
            }
        });
    }

    // Form validation
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('input[required], select[required]');
            let isValid = true;

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.classList.add('border-red-500');
                    isValid = false;
                } else {
                    field.classList.remove('border-red-500');
                }
            });

            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields.');
                return false;
            }

            // Show loading state
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Saving...';
            }
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>