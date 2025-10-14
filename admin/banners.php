<?php
$page_title = 'Banner Management';
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
            $title = sanitizeInput($_POST['title']);
            $description = sanitizeInput($_POST['description']);
            $image = sanitizeInput($_POST['image']);
            $link = sanitizeInput($_POST['link']);
            $position = (int)$_POST['position'];
            $is_active = isset($_POST['is_active']) ? 1 : 0;

            if ($action === 'add') {
                $sql = "INSERT INTO banners (title, description, image, link, position, is_active) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssii", $title, $description, $image, $link, $position, $is_active);

                if ($stmt->execute()) {
                    $message = 'Banner added successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Error adding banner: ' . $conn->error;
                    $message_type = 'error';
                }
            } else {
                $id = (int)$_POST['id'];
                $sql = "UPDATE banners SET title=?, description=?, image=?, link=?, position=?, is_active=? WHERE id=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssiii", $title, $description, $image, $link, $position, $is_active, $id);

                if ($stmt->execute()) {
                    $message = 'Banner updated successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Error updating banner: ' . $conn->error;
                    $message_type = 'error';
                }
            }
        } elseif ($action === 'delete') {
            $id = (int)$_POST['id'];
            $sql = "DELETE FROM banners WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);

            if ($stmt->execute()) {
                $message = 'Banner deleted successfully!';
                $message_type = 'success';
            } else {
                $message = 'Error deleting banner: ' . $conn->error;
                $message_type = 'error';
            }
        }
    }
}

// Get banners
$banners = $conn->query("SELECT * FROM banners ORDER BY position ASC, created_at DESC")->fetch_all(MYSQLI_ASSOC);

// Get banner for editing
$edit_banner = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM banners WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $edit_banner = $stmt->get_result()->fetch_assoc();
}
?>

<?php if ($message): ?>
    <div class="mb-6 p-4 rounded-lg <?php echo $message_type === 'success' ? 'bg-green-50 border border-green-200 text-green-700' : 'bg-red-50 border border-red-200 text-red-700'; ?>">
        <i class="fas <?php echo $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?> mr-2"></i>
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<div class="flex justify-between items-center mb-6">
    <h2 class="text-2xl font-bold text-gray-900">Banners</h2>
    <a href="?action=add" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
        <i class="fas fa-plus mr-2"></i>Add New Banner
    </a>
</div>

<?php if (isset($_GET['action']) && ($_GET['action'] === 'add' || $_GET['action'] === 'edit')): ?>
    <!-- Add/Edit Banner Form -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
            <?php echo $_GET['action'] === 'add' ? 'Add New Banner' : 'Edit Banner'; ?>
        </h3>

        <form method="POST">
            <input type="hidden" name="action" value="<?php echo $_GET['action']; ?>">
            <?php if ($edit_banner): ?>
                <input type="hidden" name="id" value="<?php echo $edit_banner['id']; ?>">
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Title *</label>
                    <input type="text" name="title" required value="<?php echo $edit_banner ? htmlspecialchars($edit_banner['title']) : ''; ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Position</label>
                    <input type="number" name="position" value="<?php echo $edit_banner ? $edit_banner['position'] : '0'; ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <p class="text-xs text-gray-500 mt-1">Lower numbers appear first</p>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <textarea name="description" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"><?php echo $edit_banner ? htmlspecialchars($edit_banner['description']) : ''; ?></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Image URL</label>
                    <input type="url" name="image" value="<?php echo $edit_banner ? htmlspecialchars($edit_banner['image']) : ''; ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="https://example.com/banner.jpg">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Link URL</label>
                    <input type="url" name="link" value="<?php echo $edit_banner ? htmlspecialchars($edit_banner['link']) : ''; ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="https://example.com/page">
                </div>

                <div class="flex items-center">
                    <input type="checkbox" name="is_active" id="is_active" <?php echo !$edit_banner || $edit_banner['is_active'] ? 'checked' : ''; ?> class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                    <label for="is_active" class="ml-2 block text-sm text-gray-900">Active</label>
                </div>
            </div>

            <div class="mt-6 flex gap-4">
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-save mr-2"></i><?php echo $_GET['action'] === 'add' ? 'Add Banner' : 'Update Banner'; ?>
                </button>
                <a href="banners.php" class="px-6 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                    <i class="fas fa-times mr-2"></i>Cancel
                </a>
            </div>
        </form>
    </div>
<?php endif; ?>

<!-- Banners Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php if (empty($banners)): ?>
        <div class="col-span-full bg-white rounded-lg shadow-md p-8 text-center">
            <i class="fas fa-image text-4xl text-gray-400 mb-4"></i>
            <p class="text-gray-500">No banners found</p>
        </div>
    <?php else: ?>
        <?php foreach ($banners as $banner): ?>
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <?php if ($banner['image']): ?>
                    <div class="h-32 bg-gray-200 flex items-center justify-center">
                        <img src="<?php echo htmlspecialchars($banner['image']); ?>" alt="<?php echo htmlspecialchars($banner['title']); ?>" class="max-h-full max-w-full object-contain">
                    </div>
                <?php else: ?>
                    <div class="h-32 bg-gray-200 flex items-center justify-center">
                        <i class="fas fa-image text-4xl text-gray-400"></i>
                    </div>
                <?php endif; ?>

                <div class="p-4">
                    <div class="flex items-start justify-between mb-2">
                        <h3 class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($banner['title']); ?></h3>
                        <div class="flex space-x-2">
                            <a href="?action=edit&id=<?php echo $banner['id']; ?>" class="text-blue-600 hover:text-blue-800 p-1">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form method="POST" class="inline" onsubmit="return confirmDelete('Are you sure you want to delete this banner?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $banner['id']; ?>">
                                <button type="submit" class="text-red-600 hover:text-red-800 p-1">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>

                    <?php if ($banner['description']): ?>
                        <p class="text-gray-600 text-sm mb-3"><?php echo htmlspecialchars($banner['description']); ?></p>
                    <?php endif; ?>

                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-500">Position: <?php echo $banner['position']; ?></span>
                        <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $banner['is_active'] ? 'text-green-800 bg-green-100' : 'text-red-800 bg-red-100'; ?>">
                            <?php echo $banner['is_active'] ? 'Active' : 'Inactive'; ?>
                        </span>
                    </div>

                    <?php if ($banner['link']): ?>
                        <div class="mt-3">
                            <a href="<?php echo htmlspecialchars($banner['link']); ?>" target="_blank" class="text-blue-600 hover:text-blue-800 text-sm">
                                <i class="fas fa-external-link-alt mr-1"></i>View Link
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>