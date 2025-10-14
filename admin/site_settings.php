<?php
// Admin Site Settings Page
if (session_status() === PHP_SESSION_NONE) session_start();
// Use __DIR__ so includes work regardless of current working directory
include_once __DIR__ . '/../includes/config.php';
// Load public/site helpers (get_setting, set_setting)
include_once __DIR__ . '/../includes/functions.php';
// Load admin helpers (requireAdminLogin, auth helpers)
include_once __DIR__ . '/includes/functions.php';

requireAdminLogin();

$page_title = 'Site Settings';
include 'includes/header.php';

$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid CSRF token. Please refresh and try again.';
    } else {
        // Sanitize inputs
        $site_name = trim($_POST['site_name'] ?? '');
        $site_tagline = trim($_POST['site_tagline'] ?? '');
        $contact_phone = trim($_POST['contact_phone'] ?? '');
        $contact_email = trim($_POST['contact_email'] ?? '');
        $contact_address = trim($_POST['contact_address'] ?? '');
        $copyright_text = trim($_POST['copyright_text'] ?? '');

        // Social links as JSON
        $social = [
            'facebook' => trim($_POST['social_facebook'] ?? ''),
            'whatsapp' => trim($_POST['social_whatsapp'] ?? ''),
            'instagram' => trim($_POST['social_instagram'] ?? ''),
            'twitter' => trim($_POST['social_twitter'] ?? ''),
        ];

        $ok = true;
        // Update settings
        $ok = $ok && set_setting('site_name', $site_name, 'string');
        $ok = $ok && set_setting('site_tagline', $site_tagline, 'string');
        $ok = $ok && set_setting('contact_phone', $contact_phone, 'string');
        $ok = $ok && set_setting('contact_email', $contact_email, 'string');
        $ok = $ok && set_setting('contact_address', $contact_address, 'text');
        $ok = $ok && set_setting('social_links', $social, 'json');
        $ok = $ok && set_setting('copyright_text', $copyright_text, 'text');

        // Handle logo upload if provided
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] !== UPLOAD_ERR_NO_FILE) {
            $file = $_FILES['logo'];
            if ($file['error'] === UPLOAD_ERR_OK) {
                $allowed = ['image/png','image/jpeg','image/webp','image/svg+xml'];
                if (!in_array($file['type'], $allowed)) {
                    $error = 'Logo must be PNG, JPG/JPEG, WEBP or SVG';
                } else {
                    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $target_dir = __DIR__ . '/../assets/images/';
                    if (!is_dir($target_dir)) mkdir($target_dir, 0755, true);
                    $target_name = 'logo_' . time() . '.' . $ext;
                    $target_path = $target_dir . $target_name;
                    if (move_uploaded_file($file['tmp_name'], $target_path)) {
                        // Store relative path from site root
                        $rel_path = 'assets/images/' . $target_name;
                        $ok = $ok && set_setting('logo', $rel_path, 'image');
                    } else {
                        $error = 'Failed to move uploaded file.';
                    }
                }
            } else {
                $error = 'File upload error.';
            }
        }

        if ($error === '') {
            if ($ok) {
                $success = 'Settings updated successfully.';
            } else {
                $error = 'Failed to save settings. Check database connection.';
            }
        }
    }
}

// Load current settings for form defaults
$site_name = get_setting('site_name', 'NatureBD');
$site_tagline = get_setting('site_tagline', 'Back to Nature');
$contact_phone = get_setting('contact_phone', '');
$contact_email = get_setting('contact_email', '');
$contact_address = get_setting('contact_address', '');
$social = get_setting('social_links', []);
$logo = get_setting('logo', 'assets/images/logo.png');
$copyright_text = get_setting('copyright_text', '');
$csrf = generateCSRFToken();
?>

<div class="max-w-4xl mx-auto p-4">
    <div class="bg-white rounded-xl shadow p-6">
        <h2 class="text-xl font-bold mb-4">Site Settings</h2>

        <?php if ($success): ?>
            <div class="p-3 bg-green-50 border border-green-200 text-green-700 rounded mb-4"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="p-3 bg-red-50 border border-red-200 text-red-700 rounded mb-4"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">

            <div>
                <label class="block text-sm font-medium text-gray-700">Site Name</label>
                <input type="text" name="site_name" value="<?php echo htmlspecialchars($site_name); ?>" class="mt-1 w-full border rounded px-3 py-2" required>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Tagline</label>
                <input type="text" name="site_tagline" value="<?php echo htmlspecialchars($site_tagline); ?>" class="mt-1 w-full border rounded px-3 py-2">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Contact Phone</label>
                    <input type="text" name="contact_phone" value="<?php echo htmlspecialchars($contact_phone); ?>" class="mt-1 w-full border rounded px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Contact Email</label>
                    <input type="email" name="contact_email" value="<?php echo htmlspecialchars($contact_email); ?>" class="mt-1 w-full border rounded px-3 py-2">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Address</label>
                <textarea name="contact_address" class="mt-1 w-full border rounded px-3 py-2" rows="3"><?php echo htmlspecialchars($contact_address); ?></textarea>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Facebook URL</label>
                    <input type="url" name="social_facebook" value="<?php echo htmlspecialchars($social['facebook'] ?? ''); ?>" class="mt-1 w-full border rounded px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">WhatsApp URL</label>
                    <input type="url" name="social_whatsapp" value="<?php echo htmlspecialchars($social['whatsapp'] ?? ''); ?>" class="mt-1 w-full border rounded px-3 py-2">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Instagram URL</label>
                    <input type="url" name="social_instagram" value="<?php echo htmlspecialchars($social['instagram'] ?? ''); ?>" class="mt-1 w-full border rounded px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Twitter URL</label>
                    <input type="url" name="social_twitter" value="<?php echo htmlspecialchars($social['twitter'] ?? ''); ?>" class="mt-1 w-full border rounded px-3 py-2">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Logo (leave empty to keep current)</label>
                <div class="flex items-center space-x-4 mt-2">
                    <img src="<?php echo htmlspecialchars($logo); ?>" alt="logo" class="w-24 h-auto border rounded" />
                    <input type="file" name="logo" accept="image/*">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Copyright Text</label>
                <input type="text" name="copyright_text" value="<?php echo htmlspecialchars($copyright_text); ?>" class="mt-1 w-full border rounded px-3 py-2">
            </div>

            <div class="pt-4">
                <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">Save Settings</button>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php';
