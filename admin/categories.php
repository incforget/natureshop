<?php
$page_title = 'Category Management';
include_once '../includes/config.php';
include_once 'includes/functions.php';
requireAdminLogin();
include 'includes/header.php';

// Handle actions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        if ($action === 'add' || $action === 'edit') {
            $name = sanitizeInput($_POST['name']);
            $slug = sanitizeInput($_POST['slug']);
            $description = sanitizeInput($_POST['description']);
            $icon_type = sanitizeInput($_POST['icon_type']);
            $icon_class = sanitizeInput($_POST['icon_class']);
            $sticker_url = sanitizeInput($_POST['sticker_url']);
            $svg_code = isset($_POST['svg_code']) ? trim($_POST['svg_code']) : '';

            if ($action === 'add') {
                $sql = "INSERT INTO categories (name, slug, description, icon_type, icon_class, sticker_url, svg_code) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssssss", $name, $slug, $description, $icon_type, $icon_class, $sticker_url, $svg_code);

                if ($stmt->execute()) {
                    $message = 'Category added successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Error adding category: ' . $conn->error;
                    $message_type = 'error';
                }
            } else {
                $id = (int)$_POST['id'];
                $sql = "UPDATE categories SET name=?, slug=?, description=?, icon_type=?, icon_class=?, sticker_url=?, svg_code=? WHERE id=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssssssi", $name, $slug, $description, $icon_type, $icon_class, $sticker_url, $svg_code, $id);

                if ($stmt->execute()) {
                    $message = 'Category updated successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Error updating category: ' . $conn->error;
                    $message_type = 'error';
                }
            }
        } elseif ($action === 'delete') {
            $id = (int)$_POST['id'];
            // Check if category has products - get category slug first
            $stmt = $conn->prepare("SELECT slug FROM categories WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $category_data = $stmt->get_result()->fetch_assoc();

            if ($category_data) {
                $stmt = $conn->prepare("SELECT COUNT(*) as count FROM products WHERE (category = ? OR category_id = ?) AND is_active = 1");
                $stmt->bind_param("si", $category_data['slug'], $id);
                $stmt->execute();
                $result = $stmt->get_result();
                $count = $result->fetch_assoc()['count'];

                if ($count > 0) {
                    $message = 'Cannot delete category. It has ' . $count . ' products associated with it.';
                    $message_type = 'error';
                } else {
                    $sql = "DELETE FROM categories WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $id);

                    if ($stmt->execute()) {
                        $message = 'Category deleted successfully!';
                        $message_type = 'success';
                    } else {
                        $message = 'Error deleting category: ' . $conn->error;
                        $message_type = 'error';
                    }
                }
            }
        }
    }
}

// Get categories
$categories = $conn->query("SELECT * FROM categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);

// Get category for editing
$edit_category = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $edit_category = $stmt->get_result()->fetch_assoc();
}
?>

<?php if ($message): ?>
    <div class="mb-6 p-4 rounded-lg shadow-sm <?php echo $message_type === 'success' ? 'bg-green-50 border border-green-200 text-green-700' : 'bg-red-50 border border-red-200 text-red-700'; ?>">
        <i class="fas <?php echo $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?> mr-2"></i>
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<!-- Page Header -->
<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Category Management</h1>
            <p class="text-gray-600">Organize your products into categories for better navigation</p>
        </div>
        <div class="flex flex-col sm:flex-row gap-3">
            <div class="flex items-center text-sm text-gray-500 bg-gray-50 px-3 py-2 rounded-lg">
                <i class="fas fa-tags mr-2 text-green-600"></i>
                <span><?php echo count($categories); ?> Categories</span>
            </div>
            <a href="?action=add" class="inline-flex items-center px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-all duration-200 font-medium shadow-sm hover:shadow-md">
                <i class="fas fa-plus mr-2"></i>Add Category
            </a>
        </div>
    </div>
</div>

<?php if (isset($_GET['action']) && ($_GET['action'] === 'add' || $_GET['action'] === 'edit')): ?>
    <!-- Add/Edit Category Form -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-xl font-semibold text-gray-900">
                    <?php echo $_GET['action'] === 'add' ? 'Add New Category' : 'Edit Category'; ?>
                </h2>
                <p class="text-sm text-gray-600 mt-1">
                    <?php echo $_GET['action'] === 'add' ? 'Create a new category to organize your products' : 'Update category information and settings'; ?>
                </p>
            </div>
            <a href="categories.php" class="text-gray-400 hover:text-gray-600 transition-colors p-2 hover:bg-gray-100 rounded-lg">
                <i class="fas fa-times text-xl"></i>
            </a>
        </div>

        <form method="POST" id="category-form" class="space-y-6" novalidate>
            <input type="hidden" name="action" value="<?php echo $_GET['action']; ?>">
            <?php if ($edit_category): ?>
                <input type="hidden" name="id" value="<?php echo $edit_category['id']; ?>">
            <?php endif; ?>

            <!-- Basic Information -->
            <div class="bg-gray-50 rounded-lg p-4 form-section">
                <h3 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-info-circle text-blue-600 mr-2" aria-hidden="true"></i>
                    Basic Information
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="category-name" class="block text-sm font-medium text-gray-700 mb-2">
                            Category Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="category-name" name="name" required
                               value="<?php echo $edit_category ? htmlspecialchars(html_entity_decode($edit_category['name'])) : ''; ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors"
                               placeholder="Enter category name">
                        <p class="text-xs text-gray-500 mt-1">This will be displayed to customers</p>
                    </div>

                    <div>
                        <label for="category-slug" class="block text-sm font-medium text-gray-700 mb-2">
                            Slug <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="category-slug" name="slug" required
                               value="<?php echo $edit_category ? htmlspecialchars(html_entity_decode($edit_category['slug'])) : ''; ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors"
                               placeholder="url-friendly-name">
                        <p class="text-xs text-gray-500 mt-1">Used in URLs and should be unique</p>
                    </div>

                    <div class="md:col-span-2">
                        <label for="category-description" class="block text-sm font-medium text-gray-700 mb-2">
                            Description
                        </label>
                        <textarea id="category-description" name="description" rows="3"
                                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors resize-vertical"
                                  placeholder="Describe this category..."><?php echo $edit_category ? htmlspecialchars(html_entity_decode($edit_category['description'])) : ''; ?></textarea>
                        <p class="text-xs text-gray-500 mt-1">Optional description for SEO and customer understanding</p>
                    </div>
                </div>
            </div>

            <!-- Icon Settings -->
            <div class="bg-gray-50 rounded-lg p-4 form-section">
                <h3 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-icons text-purple-600 mr-2" aria-hidden="true"></i>
                    Icon Settings
                </h3>
                <div class="space-y-6">
                    <div>
                        <label for="icon-type" class="block text-sm font-medium text-gray-700 mb-3">
                            Icon Type
                        </label>
                        <select id="icon-type" name="icon_type"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors">
                            <option value="fontawesome" <?php echo $edit_category && $edit_category['icon_type'] === 'fontawesome' ? 'selected' : ''; ?>>Font Awesome Icon</option>
                            <option value="bootstrap" <?php echo $edit_category && $edit_category['icon_type'] === 'bootstrap' ? 'selected' : ''; ?>>Bootstrap Icon</option>
                            <option value="sticker" <?php echo $edit_category && $edit_category['icon_type'] === 'sticker' ? 'selected' : ''; ?>>Sticker Image</option>
                            <option value="svg" <?php echo $edit_category && $edit_category['icon_type'] === 'svg' ? 'selected' : ''; ?>>Custom SVG</option>
                        </select>
                    </div>

                    <!-- Font Awesome/Bootstrap Icon -->
                    <div id="icon-class-section" class="space-y-3">
                        <label for="icon-class" class="block text-sm font-medium text-gray-700">
                            Icon Class <span class="text-xs text-gray-500">(for Font Awesome or Bootstrap)</span>
                        </label>
                        <input type="text" id="icon-class" name="icon_class"
                               value="<?php echo $edit_category ? htmlspecialchars(html_entity_decode($edit_category['icon_class'])) : ''; ?>"
                               placeholder="fas fa-leaf or bi bi-tree"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors">
                        <div class="flex items-center space-x-2 text-sm text-gray-600">
                            <i class="fas fa-lightbulb text-yellow-500"></i>
                            <span>Examples: <code class="bg-gray-100 px-2 py-1 rounded text-xs">fas fa-utensils</code>, <code class="bg-gray-100 px-2 py-1 rounded text-xs">bi bi-phone</code></span>
                        </div>
                    </div>

                    <!-- Sticker URL -->
                    <div id="sticker-section" class="space-y-3" style="display: none;">
                        <label for="sticker-url" class="block text-sm font-medium text-gray-700">
                            Sticker Image URL
                        </label>
                        <input type="url" id="sticker-url" name="sticker_url"
                               value="<?php echo $edit_category ? htmlspecialchars(html_entity_decode($edit_category['sticker_url'])) : ''; ?>"
                               placeholder="https://example.com/sticker.png"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors">
                        <p class="text-xs text-gray-500">Enter a URL to an image file (PNG, JPG, GIF, WebP)</p>
                    </div>

                    <!-- SVG Code -->
                    <div id="svg-section" class="space-y-3" style="display: none;">
                        <label for="svg-code" class="block text-sm font-medium text-gray-700">
                            SVG Code
                        </label>
                        <textarea id="svg-code" name="svg_code" rows="4"
                                  placeholder="<svg width='24' height='24' viewBox='0 0 24 24' fill='none' xmlns='http://www.w3.org/2000/svg'>...</svg>"
                                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors font-mono text-sm resize-vertical"><?php echo $edit_category && isset($edit_category['svg_code']) ? htmlspecialchars(html_entity_decode($edit_category['svg_code'])) : ''; ?></textarea>
                        <p class="text-xs text-gray-500">Paste your SVG code here. The icon will be automatically sized.</p>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex flex-col sm:flex-row gap-4 pt-6 border-t border-gray-200">
                <button type="submit" id="submit-btn" class="flex-1 sm:flex-none px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-all duration-200 font-medium shadow-sm hover:shadow-md disabled:opacity-50 disabled:cursor-not-allowed">
                    <i class="fas fa-save mr-2"></i><?php echo $_GET['action'] === 'add' ? 'Create Category' : 'Update Category'; ?>
                </button>
                <a href="categories.php" class="flex-1 sm:flex-none px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-all duration-200 font-medium text-center shadow-sm hover:shadow-md">
                    <i class="fas fa-times mr-2"></i>Cancel
                </a>
            </div>
        </form>
    </div>
<?php endif; ?>

<!-- Search and Filters -->
<div class="bg-white rounded-lg shadow-md p-6 mb-6" role="search" aria-label="Category search and filters">
    <div class="flex flex-col lg:flex-row gap-4 items-start lg:items-center justify-between">
        <div class="flex flex-col sm:flex-row gap-4 flex-1 w-full">
            <div class="flex-1">
                <label for="search-input" class="sr-only">Search categories</label>
                <input type="text" id="search-input" value="" placeholder="Search categories by name or description..." class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm" aria-describedby="search-help">
                <div id="search-help" class="sr-only">Search through category names and descriptions</div>
            </div>
            <button type="button" id="search-btn" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed" aria-label="Perform search">
                <i class="fas fa-search mr-2" aria-hidden="true"></i>Search
            </button>
            <button type="button" id="clear-filters-btn" class="px-4 py-3 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors text-sm font-medium" aria-label="Clear search filters">
                <i class="fas fa-times mr-2" aria-hidden="true"></i>Clear
            </button>
        </div>
    </div>
</div>

<!-- Categories Display -->
<div id="categories-container" role="main" aria-label="Categories list">
<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200">
        <h2 class="text-lg font-semibold text-gray-900">Categories (<span id="total-count"><?php echo count($categories); ?></span>)</h2>
    </div>

    <!-- Desktop Grid View -->
    <div class="hidden md:block">
        <div class="p-6">
            <?php if (empty($categories)): ?>
                <div class="text-center py-12">
                    <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-tags text-3xl text-gray-400"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No categories found</h3>
                    <p class="text-gray-500 mb-6">Get started by creating your first category</p>
                    <a href="?action=add" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-plus mr-2"></i>Add Category
                    </a>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    <?php foreach ($categories as $category): ?>
                        <div class="bg-gray-50 rounded-lg p-6 hover:bg-gray-100 transition-all duration-200 hover:shadow-md border border-gray-200 hover:border-gray-300">
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex items-center flex-1 min-w-0">
                                    <div class="w-12 h-12 bg-white rounded-lg flex items-center justify-center mr-3 shadow-sm border border-gray-200">
                                        <?php
                                        if ($category['icon_type'] === 'sticker' && $category['sticker_url']) {
                                            echo '<img src="' . htmlspecialchars($category['sticker_url']) . '" alt="' . htmlspecialchars(html_entity_decode($category['name'])) . '" class="w-8 h-8 object-contain rounded">';
                                        } elseif ($category['icon_type'] === 'svg' && isset($category['svg_code']) && $category['svg_code']) {
                                            $svg = $category['svg_code'];
                                            // Ensure SVG has proper sizing
                                            if (preg_match('/<svg/i', $svg) && !preg_match('/width=/i', $svg)) {
                                                $svg = preg_replace('/<svg/i', '<svg width="32" height="32"', $svg);
                                            }
                                            // Replace currentColor with theme color
                                            $svg = str_replace('currentColor', '#059669', $svg);
                                            echo $svg;
                                        } else {
                                            echo '<i class="' . htmlspecialchars($category['icon_class']) . ' text-green-600 text-xl"></i>';
                                        }
                                        ?>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h3 class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars(html_entity_decode($category['name'])); ?></h3>
                                        <p class="text-sm text-gray-500 truncate"><?php echo htmlspecialchars(html_entity_decode($category['slug'])); ?></p>
                                    </div>
                                </div>
                                <div class="flex space-x-1 ml-2">
                                    <a href="?action=edit&id=<?php echo $category['id']; ?>"
                                       class="p-2 text-blue-600 hover:text-blue-800 hover:bg-blue-50 rounded-lg transition-colors"
                                       title="Edit category">
                                        <i class="fas fa-edit text-sm"></i>
                                    </a>
                                    <form method="POST" class="inline" onsubmit="return confirmDelete('Are you sure you want to delete this category? This action cannot be undone.')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $category['id']; ?>">
                                        <button type="submit" class="p-2 text-red-600 hover:text-red-800 hover:bg-red-50 rounded-lg transition-colors" title="Delete category">
                                            <i class="fas fa-trash text-sm"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <?php if ($category['description']): ?>
                                <p class="text-gray-600 text-sm mb-4 line-clamp-2"><?php echo htmlspecialchars(html_entity_decode($category['description'])); ?></p>
                            <?php endif; ?>

                            <div class="flex items-center justify-between text-sm border-t border-gray-200 pt-4">
                                <div class="flex items-center space-x-4">
                                    <span class="text-gray-500 flex items-center">
                                        <i class="fas fa-box mr-1 text-xs"></i>
                                        <?php
                                        // Get product count
                                        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM products WHERE (category = ? OR category_id = ?) AND is_active = 1");
                                        $stmt->bind_param("si", $category['slug'], $category['id']);
                                        $stmt->execute();
                                        $count = $stmt->get_result()->fetch_assoc()['count'];
                                        echo $count . ' product' . ($count !== 1 ? 's' : '');
                                        ?>
                                    </span>
                                </div>
                                <span class="text-gray-400 text-xs">
                                    <?php echo date('M j, Y', strtotime($category['created_at'])); ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Mobile List View -->
    <div class="md:hidden">
        <?php if (empty($categories)): ?>
            <div class="px-6 py-12 text-center">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-tags text-2xl text-gray-400"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No categories found</h3>
                <p class="text-gray-500 mb-6">Get started by creating your first category</p>
                <a href="?action=add" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-plus mr-2"></i>Add Category
                </a>
            </div>
        <?php else: ?>
            <div class="divide-y divide-gray-200">
                <?php foreach ($categories as $category): ?>
                    <div class="p-4 hover:bg-gray-50 transition-colors">
                        <div class="flex items-start space-x-4">
                            <div class="w-12 h-12 bg-white rounded-lg flex items-center justify-center flex-shrink-0 shadow-sm border border-gray-200">
                                <?php
                                if ($category['icon_type'] === 'sticker' && $category['sticker_url']) {
                                    echo '<img src="' . htmlspecialchars($category['sticker_url']) . '" alt="' . htmlspecialchars(html_entity_decode($category['name'])) . '" class="w-8 h-8 object-contain rounded">';
                                } elseif ($category['icon_type'] === 'svg' && isset($category['svg_code']) && $category['svg_code']) {
                                    $svg = $category['svg_code'];
                                    if (preg_match('/<svg/i', $svg) && !preg_match('/width=/i', $svg)) {
                                        $svg = preg_replace('/<svg/i', '<svg width="24" height="24"', $svg);
                                    }
                                    $svg = str_replace('currentColor', '#059669', $svg);
                                    echo $svg;
                                } else {
                                    echo '<i class="' . htmlspecialchars($category['icon_class']) . ' text-green-600 text-lg"></i>';
                                }
                                ?>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between mb-2">
                                    <div class="flex-1 min-w-0">
                                        <h4 class="text-base font-semibold text-gray-900 truncate"><?php echo htmlspecialchars(html_entity_decode($category['name'])); ?></h4>
                                        <p class="text-sm text-gray-500 truncate"><?php echo htmlspecialchars(html_entity_decode($category['slug'])); ?></p>
                                    </div>
                                    <div class="flex space-x-2 ml-2">
                                        <a href="?action=edit&id=<?php echo $category['id']; ?>"
                                           class="p-2 text-blue-600 hover:text-blue-800 hover:bg-blue-50 rounded-lg transition-colors">
                                            <i class="fas fa-edit text-sm"></i>
                                        </a>
                                        <form method="POST" class="inline" onsubmit="return confirmDelete('Are you sure you want to delete this category? This action cannot be undone.')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $category['id']; ?>">
                                            <button type="submit" class="p-2 text-red-600 hover:text-red-800 hover:bg-red-50 rounded-lg transition-colors">
                                                <i class="fas fa-trash text-sm"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>

                                <?php if ($category['description']): ?>
                                    <p class="text-gray-600 text-sm mb-3 line-clamp-2"><?php echo htmlspecialchars(html_entity_decode($category['description'])); ?></p>
                                <?php endif; ?>

                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-500 flex items-center">
                                        <i class="fas fa-box mr-1 text-xs"></i>
                                        <?php
                                        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM products WHERE (category = ? OR category_id = ?) AND is_active = 1");
                                        $stmt->bind_param("si", $category['slug'], $category['id']);
                                        $stmt->execute();
                                        $count = $stmt->get_result()->fetch_assoc()['count'];
                                        echo $count . ' product' . ($count !== 1 ? 's' : '');
                                        ?>
                                    </span>
                                    <span class="text-gray-400 text-xs">
                                        <?php echo date('M j, Y', strtotime($category['created_at'])); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Icon type switching functionality
    const iconTypeSelect = document.getElementById('icon-type');
    const iconClassSection = document.getElementById('icon-class-section');
    const stickerSection = document.getElementById('sticker-section');
    const svgSection = document.getElementById('svg-section');

    if (iconTypeSelect) {
        function toggleIconSections() {
            const selectedType = iconTypeSelect.value;

            // Hide all sections first
            iconClassSection.style.display = 'none';
            stickerSection.style.display = 'none';
            svgSection.style.display = 'none';

            // Show relevant section
            if (selectedType === 'fontawesome' || selectedType === 'bootstrap') {
                iconClassSection.style.display = 'block';
            } else if (selectedType === 'sticker') {
                stickerSection.style.display = 'block';
            } else if (selectedType === 'svg') {
                svgSection.style.display = 'block';
            }
        }

        iconTypeSelect.addEventListener('change', toggleIconSections);
        toggleIconSections(); // Initialize on page load
    }

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

    // Search functionality
    const searchInput = document.getElementById('search-input');
    const searchBtn = document.getElementById('search-btn');
    const clearFiltersBtn = document.getElementById('clear-filters-btn');
    const categoriesContainer = document.getElementById('categories-container');
    const totalCount = document.getElementById('total-count');

    let searchTimeout;

    function performSearch() {
        const searchTerm = searchInput.value.trim().toLowerCase();

        // Show loading state
        searchBtn.disabled = true;
        searchBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Searching...';

        // Get all category cards
        const desktopCards = categoriesContainer.querySelectorAll('.hidden.md\\:block .bg-gray-50');
        const mobileCards = categoriesContainer.querySelectorAll('.md\\:hidden .divide-y > div');

        let visibleCount = 0;

        // Filter desktop cards
        desktopCards.forEach(card => {
            const title = card.querySelector('h3')?.textContent.toLowerCase() || '';
            const description = card.querySelector('p.text-gray-600')?.textContent.toLowerCase() || '';
            const slug = card.querySelector('p.text-gray-500')?.textContent.toLowerCase() || '';

            const matches = !searchTerm ||
                          title.includes(searchTerm) ||
                          description.includes(searchTerm) ||
                          slug.includes(searchTerm);

            card.style.display = matches ? 'block' : 'none';
            if (matches) visibleCount++;
        });

        // Filter mobile cards
        mobileCards.forEach(card => {
            const title = card.querySelector('h4')?.textContent.toLowerCase() || '';
            const description = card.querySelector('p.text-gray-600')?.textContent.toLowerCase() || '';
            const slug = card.querySelector('p.text-gray-500')?.textContent.toLowerCase() || '';

            const matches = !searchTerm ||
                          title.includes(searchTerm) ||
                          description.includes(searchTerm) ||
                          slug.includes(searchTerm);

            card.style.display = matches ? 'block' : 'none';
            if (matches) visibleCount++;
        });

        // Update count
        totalCount.textContent = visibleCount;

        // Reset loading state
        searchBtn.disabled = false;
        searchBtn.innerHTML = '<i class="fas fa-search mr-2"></i>Search';
    }

    // Search with debouncing
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(performSearch, 300);
    });

    // Search button click
    searchBtn.addEventListener('click', performSearch);

    // Clear filters
    clearFiltersBtn.addEventListener('click', function() {
        searchInput.value = '';
        performSearch();
    });

    // Enter key support
    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            performSearch();
        }
    });

    // Form validation and submission
    const form = document.getElementById('category-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('input[required], select[required]');
            let isValid = true;

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.classList.add('border-red-500', 'focus:ring-red-500', 'focus:border-red-500');
                    isValid = false;
                } else {
                    field.classList.remove('border-red-500', 'focus:ring-red-500', 'focus:border-red-500');
                }
            });

            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields.');
                return false;
            }

            // Show loading state
            const submitBtn = document.getElementById('submit-btn');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Saving...';
            }
        });
    }

    // Enhanced delete confirmation
    window.confirmDelete = function(message) {
        return confirm(message);
    };

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

    // Add loading animation for page transitions
    const links = document.querySelectorAll('a[href]');
    links.forEach(link => {
        link.addEventListener('click', function(e) {
            if (this.hostname === window.location.hostname) {
                const href = this.getAttribute('href');
                if (href && !href.startsWith('#') && !href.startsWith('javascript:')) {
                    e.preventDefault();
                    document.body.style.opacity = '0.7';
                    setTimeout(() => {
                        window.location.href = href;
                    }, 150);
                }
            }
        });
    });
});
</script>