<?php
$page_title = 'Promo Code Management';
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
            $code = strtoupper(sanitizeInput($_POST['code']));
            $description = sanitizeInput($_POST['description']);
            $discount_type = sanitizeInput($_POST['discount_type']);
            $discount_value = (float)$_POST['discount_value'];
            $min_order_amount = (float)$_POST['min_order_amount'];
            $max_discount = !empty($_POST['max_discount']) ? (float)$_POST['max_discount'] : null;
            $usage_limit = !empty($_POST['usage_limit']) ? (int)$_POST['usage_limit'] : null;
            $expiry_date = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
            $is_active = isset($_POST['is_active']) ? 1 : 0;

            if ($action === 'add') {
                $sql = "INSERT INTO promo_codes (code, description, discount_type, discount_value, min_order_amount, max_discount, usage_limit, expiry_date, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssddiissi", $code, $description, $discount_type, $discount_value, $min_order_amount, $max_discount, $usage_limit, $expiry_date, $is_active);

                if ($stmt->execute()) {
                    $message = 'Promo code added successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Error adding promo code: ' . $conn->error;
                    $message_type = 'error';
                }
            } else {
                $id = (int)$_POST['id'];
                $sql = "UPDATE promo_codes SET code=?, description=?, discount_type=?, discount_value=?, min_order_amount=?, max_discount=?, usage_limit=?, expiry_date=?, is_active=? WHERE id=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssddiissii", $code, $description, $discount_type, $discount_value, $min_order_amount, $max_discount, $usage_limit, $expiry_date, $is_active, $id);

                if ($stmt->execute()) {
                    $message = 'Promo code updated successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Error updating promo code: ' . $conn->error;
                    $message_type = 'error';
                }
            }
        } elseif ($action === 'delete') {
            $id = (int)$_POST['id'];
            $sql = "DELETE FROM promo_codes WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);

            if ($stmt->execute()) {
                $message = 'Promo code deleted successfully!';
                $message_type = 'success';
            } else {
                $message = 'Error deleting promo code: ' . $conn->error;
                $message_type = 'error';
            }
        }
    }
}

// Get promo codes
$promo_codes = $conn->query("SELECT * FROM promo_codes ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);

// Get promo code for editing
$edit_promo = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM promo_codes WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $edit_promo = $stmt->get_result()->fetch_assoc();
}
?>

<?php if ($message): ?>
    <div class="mb-6 p-4 rounded-lg <?php echo $message_type === 'success' ? 'bg-green-50 border border-green-200 text-green-700' : 'bg-red-50 border border-red-200 text-red-700'; ?>">
        <i class="fas <?php echo $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?> mr-2"></i>
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<div class="flex justify-between items-center mb-6">
    <h2 class="text-2xl font-bold text-gray-900">Promo Codes</h2>
    <a href="?action=add" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
        <i class="fas fa-plus mr-2"></i>Add New Promo Code
    </a>
</div>

<?php if (isset($_GET['action']) && ($_GET['action'] === 'add' || $_GET['action'] === 'edit')): ?>
    <!-- Add/Edit Promo Code Form -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
            <?php echo $_GET['action'] === 'add' ? 'Add New Promo Code' : 'Edit Promo Code'; ?>
        </h3>

        <form method="POST">
            <input type="hidden" name="action" value="<?php echo $_GET['action']; ?>">
            <?php if ($edit_promo): ?>
                <input type="hidden" name="id" value="<?php echo $edit_promo['id']; ?>">
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Promo Code *</label>
                    <input type="text" name="code" required value="<?php echo $edit_promo ? htmlspecialchars($edit_promo['code']) : ''; ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="SUMMER2024">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Discount Type *</label>
                    <select name="discount_type" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="percentage" <?php echo $edit_promo && $edit_promo['discount_type'] === 'percentage' ? 'selected' : ''; ?>>Percentage</option>
                        <option value="fixed" <?php echo $edit_promo && $edit_promo['discount_type'] === 'fixed' ? 'selected' : ''; ?>>Fixed Amount</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Discount Value *</label>
                    <input type="number" name="discount_value" step="0.01" required value="<?php echo $edit_promo ? $edit_promo['discount_value'] : ''; ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <p class="text-xs text-gray-500 mt-1">Enter percentage (e.g., 10) or fixed amount (e.g., 50)</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Minimum Order Amount</label>
                    <input type="number" name="min_order_amount" step="0.01" value="<?php echo $edit_promo ? $edit_promo['min_order_amount'] : '0'; ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Maximum Discount</label>
                    <input type="number" name="max_discount" step="0.01" value="<?php echo $edit_promo && $edit_promo['max_discount'] ? $edit_promo['max_discount'] : ''; ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <p class="text-xs text-gray-500 mt-1">For percentage discounts only</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Usage Limit</label>
                    <input type="number" name="usage_limit" value="<?php echo $edit_promo && $edit_promo['usage_limit'] ? $edit_promo['usage_limit'] : ''; ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <p class="text-xs text-gray-500 mt-1">Leave empty for unlimited</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Expiry Date</label>
                    <input type="date" name="expiry_date" value="<?php echo $edit_promo && $edit_promo['expiry_date'] ? $edit_promo['expiry_date'] : ''; ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <textarea name="description" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"><?php echo $edit_promo ? htmlspecialchars($edit_promo['description']) : ''; ?></textarea>
                </div>

                <div class="flex items-center">
                    <input type="checkbox" name="is_active" id="is_active" <?php echo !$edit_promo || $edit_promo['is_active'] ? 'checked' : ''; ?> class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                    <label for="is_active" class="ml-2 block text-sm text-gray-900">Active</label>
                </div>
            </div>

            <div class="mt-6 flex gap-4">
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-save mr-2"></i><?php echo $_GET['action'] === 'add' ? 'Add Promo Code' : 'Update Promo Code'; ?>
                </button>
                <a href="promo_codes.php" class="px-6 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                    <i class="fas fa-times mr-2"></i>Cancel
                </a>
            </div>
        </form>
    </div>
<?php endif; ?>

<!-- Promo Codes Table -->
<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Discount</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usage</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Expiry</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($promo_codes)): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">No promo codes found</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($promo_codes as $promo): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($promo['code']); ?></div>
                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars(substr($promo['description'], 0, 30)) . (strlen($promo['description']) > 30 ? '...' : ''); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php
                                if ($promo['discount_type'] === 'percentage') {
                                    echo $promo['discount_value'] . '%';
                                    if ($promo['max_discount']) {
                                        echo ' (max ৳' . number_format($promo['max_discount'], 2) . ')';
                                    }
                                } else {
                                    echo '৳' . number_format($promo['discount_value'], 2);
                                }
                                ?>
                                <div class="text-xs text-gray-500">Min order: ৳<?php echo number_format($promo['min_order_amount'], 2); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo $promo['used_count']; ?>
                                <?php if ($promo['usage_limit']): ?>
                                    / <?php echo $promo['usage_limit']; ?>
                                <?php else: ?>
                                    (unlimited)
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php
                                if ($promo['expiry_date']) {
                                    $expiry = strtotime($promo['expiry_date']);
                                    $is_expired = $expiry < time();
                                    echo '<span class="' . ($is_expired ? 'text-red-600' : 'text-gray-900') . '">' . date('M j, Y', $expiry) . '</span>';
                                } else {
                                    echo 'No expiry';
                                }
                                ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php
                                $is_expired = $promo['expiry_date'] && strtotime($promo['expiry_date']) < time();
                                $is_limit_reached = $promo['usage_limit'] && $promo['used_count'] >= $promo['usage_limit'];
                                $status = $promo['is_active'] && !$is_expired && !$is_limit_reached ? 'active' : 'inactive';
                                ?>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $status === 'active' ? 'text-green-800 bg-green-100' : 'text-red-800 bg-red-100'; ?>">
                                    <?php echo ucfirst($status); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex space-x-2">
                                    <a href="?action=edit&id=<?php echo $promo['id']; ?>" class="text-blue-600 hover:text-blue-900">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <form method="POST" class="inline" onsubmit="return confirmDelete('Are you sure you want to delete this promo code?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $promo['id']; ?>">
                                        <button type="submit" class="text-red-600 hover:text-red-900">
                                            <i class="fas fa-trash"></i> Delete
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

<?php include 'includes/footer.php'; ?>