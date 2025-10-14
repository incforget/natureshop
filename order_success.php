<?php
$page_title = "Order Success";
include 'includes/header.php';

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$order = getOrderById($order_id);
$user = getUserById($order['user_id']);
$order_items = getOrderItems($order_id);

// Format items for localStorage (similar to cart format)
$formatted_items = [];
foreach ($order_items as $item) {
    $formatted_items[] = [
        'id' => $item['product_id'],
        'name' => $item['product_name'],
        'price' => $item['price'],
        'image' => $item['product_image'],
        'quantity' => $item['quantity']
    ];
}

// Get all orders for the user to sync across devices
$user_id = $order['user_id'];
$all_orders = getRecentOrders($user_id, 100); // Get up to 100 recent orders
$all_formatted_orders = [];
foreach ($all_orders as $ord) {
    $ord_items = getOrderItems($ord['id']);
    $formatted_ord_items = [];
    foreach ($ord_items as $item) {
        $formatted_ord_items[] = [
            'id' => $item['product_id'],
            'name' => $item['product_name'],
            'price' => $item['price'],
            'image' => $item['product_image'],
            'quantity' => $item['quantity']
        ];
    }
    $all_formatted_orders[] = [
        'id' => $ord['id'],
        'date' => $ord['created_at'],
        'items' => $formatted_ord_items,
        'total' => $ord['total']
    ];
}
?>

    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .fade-in {
            animation: fadeIn 0.8s ease-out;
        }
    </style>

    <div class="min-h-screen bg-gradient-to-br from-green-50 via-white to-blue-50 flex items-center justify-center px-4 py-16 fade-in">
        <div class="max-w-2xl mx-auto bg-white rounded-2xl shadow-2xl p-8 text-center">
            <!-- Success Icon -->
            <div class="mb-6">
                <div class="inline-flex items-center justify-center w-20 h-20 bg-green-100 rounded-full animate-bounce">
                    <i class="fas fa-check-circle text-5xl text-green-600"></i>
                </div>
            </div>
            <!-- Success Message -->
            <h1 class="text-3xl font-bold text-gray-800 mb-4">Order Placed Successfully!</h1>
            <p class="text-gray-600 mb-6">Thank you for your purchase! Your order has been confirmed and will be processed shortly. You'll receive an email confirmation soon.</p>
            <p class="text-lg text-gray-700 mb-8">Order ID: <span class="font-semibold text-green-600">#<?php echo $order_id; ?></span></p>
            <!-- Order Summary -->
            <div class="bg-gray-50 rounded-lg p-6 mb-8 text-left">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Order Summary</h2>
                <ul class="space-y-2 mb-4">
                    <?php foreach ($order_items as $item): ?>
                    <li class="flex justify-between items-center">
                        <span><?php echo htmlspecialchars($item['product_name']); ?> <span class="text-gray-500">x<?php echo $item['quantity']; ?></span></span>
                        <span class="font-medium">৳<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <div class="border-t pt-4 flex justify-between items-center">
                    <span class="text-lg font-semibold">Total:</span>
                    <span class="text-lg font-bold text-green-600">৳<?php echo number_format($order['total'], 2); ?></span>
                </div>
            </div>
            <!-- Actions -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="order_details.php?id=<?php echo $order_id; ?>" class="bg-green-600 text-white px-8 py-3 rounded-lg hover:bg-green-700 transition-colors font-semibold shadow-md">View Order Details</a>
                <a href="index.php" class="bg-gray-600 text-white px-8 py-3 rounded-lg hover:bg-gray-700 transition-colors font-semibold shadow-md">Continue Shopping</a>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script>
        // Store all user orders in history to sync across devices
        <?php if ($order && $all_formatted_orders): ?>
        (function() {
            function safeSetLocal(key, value) {
                try {
                    localStorage.setItem(key, JSON.stringify(value));
                } catch (e) {
                    console.error('Failed to write to localStorage', key, e);
                }
            }
            const allOrders = <?php echo json_encode($all_formatted_orders); ?>;
            safeSetLocal('orderHistory', allOrders);
            console.log('Order history synced:', allOrders);
        })();
        <?php endif; ?>

        // Store user info to sync across devices
        <?php if ($user): ?>
        (function() {
            function safeSetLocal(key, value) {
                try {
                    localStorage.setItem(key, JSON.stringify(value));
                } catch (e) {
                    console.error('Failed to write to localStorage', key, e);
                }
            }
            const userInfo = {
                name: '<?php echo addslashes($user['name']); ?>',
                email: '<?php echo addslashes($user['email']); ?>',
                phone: '<?php echo addslashes($user['phone'] ?? ''); ?>',
                address: '<?php echo addslashes($user['address'] ?? ''); ?>'
            };
            safeSetLocal('userInfo', userInfo);
            console.log('User info synced:', userInfo);
        })();
        <?php endif; ?>

    // Clear cart after successful order
    try { localStorage.removeItem('cart'); } catch (e) { /* ignore */ }
    </script>
    <script src="js/app.js"></script>
</body>
</html>