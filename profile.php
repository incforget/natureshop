<?php
$page_title = "Profile";
include 'includes/header.php';

$user = null;
$password_set = false;
$message = '';

if (isset($_SESSION['user_id'])) {
    $sql = "SELECT * FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $password_set = $user && !password_verify('password123', $user['password']);
    $recent_orders = getRecentOrders($_SESSION['user_id']);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_profile'])) {
        $name = $_POST['name'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $address = $_POST['address'];

        if (isset($_SESSION['user_id'])) {
            $sql = "UPDATE users SET name = ?, email = ?, phone = ?, address = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssi", $name, $email, $phone, $address, $_SESSION['user_id']);
            if ($stmt->execute()) {
                $message = "Profile updated successfully!";
            } else {
                $message = "Error updating profile.";
            }
        }
    } elseif (isset($_POST['update_password'])) {
        $old_password = $_POST['old_password'] ?? '';
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if ($new_password !== $confirm_password) {
            $message = "New passwords do not match.";
        } elseif (strlen($new_password) < 6) {
            $message = "Password must be at least 6 characters long.";
        } else {
            // Check old password if password is set
            $password_valid = true;
            if ($password_set) {
                if (!password_verify($old_password, $user['password'])) {
                    $message = "Current password is incorrect.";
                    $password_valid = false;
                }
            }

            if ($password_valid) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $sql = "UPDATE users SET password = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("si", $hashed_password, $_SESSION['user_id']);
                if ($stmt->execute()) {
                    $message = "Password updated successfully!";
                    // Refresh user data
                    $sql = "SELECT * FROM users WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $_SESSION['user_id']);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $user = $result->fetch_assoc();
                    $password_set = !password_verify('password123', $user['password']);
                } else {
                    $message = "Error updating password.";
                }
            }
        }
    }
}

if (isset($_SESSION['user_id'])) {
    $sql = "SELECT * FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $recent_orders = getRecentOrders($_SESSION['user_id']);
}
?>


    <!-- Profile Page -->
    <div class="min-h-screen bg-gradient-to-br from-gray-50 via-white to-green-50/30">
        <div class="container mx-auto px-4 py-4">
            <div class="max-w-4xl mx-auto">
                <!-- Page Header -->
                <div class="text-center mb-12 animate-fade-in">
                    <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-green-500 to-emerald-600 rounded-3xl shadow-xl mb-6 animate-bounce-subtle">
                        <i class="fas fa-user text-white text-3xl"></i>
                    </div>
                    <h1 class="text-3xl md:text-4xl font-bold text-gray-800 mb-2 pb-2 gradient-text">My Profile</h1>
                    <p class="text-base md:text-lg text-gray-600">Manage your account information and preferences</p>
                </div>

        <?php if (!isset($_SESSION['user_id'])): ?>
            <div class="bg-gradient-to-r from-yellow-50 to-orange-50 border-2 border-yellow-200 rounded-3xl p-8 text-center shadow-lg">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-yellow-100 rounded-2xl mb-2">
                    <i class="fas fa-exclamation-triangle text-yellow-600 text-2xl"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-800 mb-4">Access Restricted</h2>
                <p class="text-gray-600 mb-6 text-lg">Please place an order first to access your profile and account management.</p>
                <a href="products.php" class="inline-flex items-center bg-gradient-to-r from-green-500 to-emerald-600 text-white px-8 py-4 rounded-2xl font-semibold text-lg hover:from-green-600 hover:to-emerald-700 transition-all transform hover:scale-105 shadow-xl hover:shadow-2xl">
                    <i class="fas fa-shopping-cart mr-3"></i>
                    Browse Products
                </a>
            </div>
        <?php else: ?>
            <?php if ($message): ?>
                <div class="bg-gradient-to-r from-green-50 to-emerald-50 border-2 border-green-200 rounded-3xl p-6 mb-8 shadow-lg">
                    <div class="flex items-center">
                        <div class="inline-flex items-center justify-center w-10 h-10 bg-green-100 rounded-2xl mr-4">
                            <i class="fas fa-check-circle text-green-600 text-xl"></i>
                        </div>
                        <p class="text-green-800 font-semibold text-lg"><?php echo $message; ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Personal Information Card -->
            <div class="bg-white/80 backdrop-blur-sm rounded-3xl shadow-xl hover:shadow-2xl transition-all duration-500 border border-gray-100 hover:border-green-200 mb-8 overflow-hidden card-enhanced">
                <div class="bg-gradient-to-r from-green-500 to-emerald-600 p-2">
                    <div class="flex items-center">
                        <div class="inline-flex items-center justify-center w-12 h-12 bg-white/20 rounded-2xl mr-4">
                            <i class="fas fa-id-card text-white text-xl"></i>
                        </div>
                        <h2 class="text-lg md:text-xl font-bold text-white">Personal Information</h2>
                    </div>
                </div>
                <div class="p-8">
                    <form method="POST" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    <i class="fas fa-user mr-2 text-green-600"></i>Full Name
                                </label>
                                <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required
                                       class="w-full border-2 border-green-500 rounded-2xl px-4 py-3 focus:outline-none focus:ring-4 focus:ring-green-100 focus:border-green-500 transition-all duration-300 text-gray-800 placeholder-gray-400">
                            </div>
                            <div class="space-y-2">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    <i class="fas fa-envelope mr-2 text-green-600"></i>Email Address <span class="text-gray-500 font-normal">(Optional)</span>
                                </label>
                                <input type="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>"
                                       class="w-full border-2 border-green-500 rounded-2xl px-4 py-3 focus:outline-none focus:ring-4 focus:ring-green-100 focus:border-green-500 transition-all duration-300 text-gray-800 placeholder-gray-400">
                            </div>
                            <div class="space-y-2">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    <i class="fas fa-phone mr-2 text-green-600"></i>Phone Number
                                </label>
                                <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" required
                                       class="w-full border-2 border-green-500 rounded-2xl px-4 py-3 focus:outline-none focus:ring-4 focus:ring-green-100 focus:border-green-500 transition-all duration-300 text-gray-800 placeholder-gray-400">
                            </div>
                            <div class="space-y-2 md:col-span-2">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    <i class="fas fa-map-marker-alt mr-2 text-green-600"></i>Delivery Address
                                </label>
                                <textarea name="address" rows="4" class="w-full border-2 border-green-500 rounded-2xl px-4 py-3 focus:outline-none focus:ring-4 focus:ring-green-100 focus:border-green-500 transition-all duration-300 text-gray-800 placeholder-gray-400 resize-none"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                            </div>
                        </div>
                        <div class="flex justify-end pt-4">
                            <button type="submit" name="update_profile"
                                    class="bg-gradient-to-r from-green-500 to-emerald-600 text-white px-8 py-3 rounded-2xl font-semibold text-lg hover:from-green-600 hover:to-emerald-700 transition-all transform hover:scale-105 shadow-lg hover:shadow-xl flex items-center">
                                <i class="fas fa-save mr-2"></i>
                                Update Profile
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Password Settings Card -->
            <div class="bg-white/80 backdrop-blur-sm rounded-3xl shadow-xl hover:shadow-2xl transition-all duration-500 border border-gray-100 hover:border-green-200 mb-8 overflow-hidden card-enhanced">
                <div class="bg-gradient-to-r from-blue-500 to-indigo-600 p-2">
                    <div class="flex items-center">
                        <div class="inline-flex items-center justify-center w-12 h-12 bg-white/20 rounded-2xl mr-4">
                            <i class="fas fa-shield-alt text-white text-xl"></i>
                        </div>
                        <h2 class="text-lg md:text-xl font-bold text-white">Password Settings</h2>
                    </div>
                </div>
                <div class="p-8">
                    <?php if (!$password_set): ?>
                        <div class="bg-gradient-to-r from-yellow-50 to-orange-50 border-2 border-yellow-200 rounded-2xl p-6 mb-6">
                            <div class="flex items-start">
                                <div class="inline-flex items-center justify-center w-10 h-10 bg-yellow-100 rounded-2xl mr-4 flex-shrink-0">
                                    <i class="fas fa-exclamation-triangle text-yellow-600 text-lg"></i>
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-yellow-800 mb-2">Security Alert</h3>
                                    <p class="text-yellow-700">For your safety and security, please add a strong password to your account.</p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="space-y-6">
                        <?php if ($password_set): ?>
                            <div class="space-y-2">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    <i class="fas fa-lock mr-2 text-blue-600"></i>Current Password
                                </label>
                                <input type="password" name="old_password" required
                                       class="w-full border-2 border-green-500 rounded-2xl px-4 py-3 focus:outline-none focus:ring-4 focus:ring-blue-100 focus:border-blue-500 transition-all duration-300 text-gray-800 placeholder-gray-400"
                                       placeholder="Enter your current password">
                            </div>
                        <?php endif; ?>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    <i class="fas fa-key mr-2 text-green-600"></i>New Password
                                </label>
                                <input type="password" name="new_password" required
                                       class="w-full border-2 border-green-500 rounded-2xl px-4 py-3 focus:outline-none focus:ring-4 focus:ring-green-100 focus:border-green-500 transition-all duration-300 text-gray-800 placeholder-gray-400"
                                       placeholder="Enter new password (min 6 characters)">
                            </div>
                            <div class="space-y-2">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    <i class="fas fa-check-circle mr-2 text-green-600"></i>Confirm New Password
                                </label>
                                <input type="password" name="confirm_password" required
                                       class="w-full border-2 border-green-500 rounded-2xl px-4 py-3 focus:outline-none focus:ring-4 focus:ring-green-100 focus:border-green-500 transition-all duration-300 text-gray-800 placeholder-gray-400"
                                       placeholder="Confirm your new password">
                            </div>
                        </div>

                        <div class="bg-gray-50 rounded-2xl p-4">
                            <h4 class="text-sm font-semibold text-gray-700 mb-2">Password Requirements:</h4>
                            <ul class="text-sm text-gray-600 space-y-1">
                                <li class="flex items-center"><i class="fas fa-check text-green-500 mr-2"></i>At least 6 characters long</li>
                                <li class="flex items-center"><i class="fas fa-info-circle text-blue-500 mr-2"></i>Use a mix of letters, numbers, and symbols</li>
                            </ul>
                        </div>

                        <div class="flex justify-end pt-4">
                            <button type="submit" name="update_password"
                                    class="bg-gradient-to-r from-blue-500 to-indigo-600 text-white px-8 py-3 rounded-2xl font-semibold text-lg hover:from-blue-600 hover:to-indigo-700 transition-all transform hover:scale-105 shadow-lg hover:shadow-xl flex items-center">
                                <i class="fas fa-save mr-2"></i>
                                <?php echo $password_set ? 'Update Password' : 'Set Password'; ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Recent Orders Section -->
            <div class="bg-white/80 backdrop-blur-sm rounded-3xl shadow-xl hover:shadow-2xl transition-all duration-500 border border-gray-100 hover:border-green-200 overflow-hidden card-enhanced">
                <div class="bg-gradient-to-r from-green-500 to-emerald-600 p-2">
                    <div class="flex items-center">
                        <div class="inline-flex items-center justify-center w-12 h-12 bg-white/20 rounded-2xl mr-4">
                            <i class="fas fa-shopping-bag text-white text-xl"></i>
                        </div>
                        <h2 class="text-lg md:text-xl font-bold text-white">Recent Orders</h2>
                    </div>
                </div>
                <div class="p-8">
                    <div id="recent-orders" class="space-y-6">
                        <?php if (!empty($recent_orders)): ?>
                            <?php foreach ($recent_orders as $order): ?>
                                <div class="bg-gradient-to-r from-gray-50 to-white rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 border border-gray-100 hover:border-green-200 transform hover:-translate-y-1">
                                    <div class="flex flex-col lg:flex-row lg:justify-between lg:items-center gap-4">
                                        <div class="flex items-center space-x-4">
                                            <div class="inline-flex items-center justify-center w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl shadow-lg">
                                                <i class="fas fa-receipt text-white text-lg"></i>
                                            </div>
                                            <div>
                                                <h3 class="text-lg font-bold text-gray-800">Order #<?php echo $order['id']; ?></h3>
                                                <p class="text-gray-600 flex items-center">
                                                    <i class="fas fa-calendar-alt mr-2 text-green-600"></i>
                                                    <?php echo date('F d, Y', strtotime($order['created_at'])); ?>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                                            <div class="text-center sm:text-right">
                                                <p class="text-xl font-bold text-green-600 mb-1">৳<?php echo number_format($order['total'], 2); ?></p>
                                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold
                                                    <?php
                                                    switch(strtolower($order['status'])) {
                                                        case 'pending':
                                                            echo 'bg-yellow-100 text-yellow-800';
                                                            break;
                                                        case 'processing':
                                                            echo 'bg-blue-100 text-blue-800';
                                                            break;
                                                        case 'shipped':
                                                            echo 'bg-purple-100 text-purple-800';
                                                            break;
                                                        case 'delivered':
                                                            echo 'bg-green-100 text-green-800';
                                                            break;
                                                        case 'cancelled':
                                                            echo 'bg-red-100 text-red-800';
                                                            break;
                                                        default:
                                                            echo 'bg-gray-100 text-gray-800';
                                                    }
                                                    ?>">
                                                    <i class="fas fa-circle mr-2 text-xs"></i>
                                                    <?php echo ucfirst($order['status']); ?>
                                                </span>
                                            </div>
                                            <a href="/order-details/<?php echo $order['id']; ?>"
                                               class="inline-flex items-center bg-gradient-to-r from-green-500 to-emerald-600 text-white px-2 py-3 rounded-2xl font-semibold hover:from-green-600 hover:to-emerald-700 transition-all transform hover:scale-105 shadow-lg hover:shadow-xl">
                                                <i class="fas fa-eye mr-2"></i>
                                                View Details
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-12">
                                <div class="inline-flex items-center justify-center w-20 h-20 bg-gray-100 rounded-3xl mb-6">
                                    <i class="fas fa-shopping-cart text-gray-400 text-3xl"></i>
                                </div>
                                <h3 class="text-lg font-semibold text-gray-600 mb-2">No Recent Orders</h3>
                                <p class="text-gray-500 mb-6">You haven't placed any orders yet. Start shopping to see your order history here!</p>
                                <a href="products.php" class="inline-flex items-center bg-gradient-to-r from-green-500 to-emerald-600 text-white px-8 py-4 rounded-2xl font-semibold text-lg hover:from-green-600 hover:to-emerald-700 transition-all transform hover:scale-105 shadow-xl hover:shadow-2xl">
                                    <i class="fas fa-shopping-bag mr-3"></i>
                                    Start Shopping
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="/js/app.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            updateCartCount();
        });
    </script>
</body>
</html>