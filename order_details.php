<?php
$page_title = "Order Details";
include 'includes/header.php';
?>

<style>
/* Additional modern styling — scoped to .order-details-content to avoid collisions */
.order-details-content .od-container,
.order-details-content .container {
    max-width: 1200px;
}

.order-details-content .od-shadow-xl,
.order-details-content .shadow-xl {
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

.order-details-content .od-hover-shadow-2xl:hover,
.order-details-content .hover\:shadow-2xl:hover {
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
}

.order-details-content .od-transition-all,
.order-details-content .transition-all {
    transition: all 0.3s ease;
}

.order-details-content .od-transform,
.order-details-content .transform {
    transform: scale(1);
}

.order-details-content .od-hover-scale-105:hover,
.order-details-content .hover\:scale-105:hover {
    transform: scale(1.05);
}

/* Responsive adjustments (scoped) */
@media (max-width: 768px) {
    .order-details-content .container.mx-auto.px-4.py-4 {
        padding: 1rem;
    }

    .order-details-content .grid.grid-cols-1.lg\:grid-cols-2 {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }

    .order-details-content .text-4xl {
        font-size: 2rem;
        line-height: 1.2;
    }

    .order-details-content .text-3xl {
        font-size: 1.875rem;
        line-height: 1.2;
    }

    .order-details-content .p-4 {
        padding: 1rem;
    }

    .order-details-content .flex.flex-col.sm\\:flex-row {
        flex-direction: column;
    }

    .order-details-content .flex.flex-col.sm\\:flex-row > * {
        width: 100%;
        justify-content: center;
    }

    /* Better spacing for mobile */
    .order-details-content .space-y-4 > * + * {
        margin-top: 1rem;
    }

    .order-details-content .space-y-6 > * + * {
        margin-top: 1.5rem;
    }

    /* Fix table on mobile */
    .order-details-content .overflow-x-auto {
        -webkit-overflow-scrolling: touch;
    }

    .order-details-content table {
        font-size: 0.875rem;
    }

    .order-details-content .w-16.h-16 {
        width: 3rem;
        height: 3rem;
    }

    .order-details-content .text-lg {
        font-size: 1rem;
    }

    /* Better button layout on mobile */
    .order-details-content .flex.flex-col.sm\\:flex-row.gap-4 {
        gap: 0.75rem;
    }

    /* Fix timeline cards on mobile */
    .order-details-content .flex.items-start.space-x-4 {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.75rem;
    }

    .order-details-content .flex.items-start.space-x-4 .flex-shrink-0 {
        align-self: flex-start;
    }

    /* Better grid for contact info */
    .order-details-content .grid.grid-cols-1.md\\:grid-cols-2 {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
}

/* Prevent horizontal overflow in order details content */
.order-details-content * {
    box-sizing: border-box;
}

.order-details-content {
    overflow-x: hidden;
}

/* Ensure containers don't overflow in order details */
.order-details-content .container {
    max-width: 1200px;
    margin: 0 auto;
    width: 100%;
    overflow: hidden;
}

/* Fix flex containers in order details */
.order-details-content .flex {
    min-width: 0;
}

/* Better text truncation in order details */
.order-details-content .truncate {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* Ensure images don't overflow in order details */
.order-details-content img {
    max-width: 100%;
    height: auto;
}

/* Custom scrollbar for order details */
.order-details-content ::-webkit-scrollbar {
    width: 8px;
}

.order-details-content ::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

.order-details-content ::-webkit-scrollbar-thumb {
    background: linear-gradient(45deg, #10b981, #059669);
    border-radius: 10px;
}

.order-details-content ::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(45deg, #059669, #047857);
}

/* Loading animation for order details */
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.order-details-content .fa-spinner {
    animation: spin 1s linear infinite;
}

/* Enhanced focus states for order details */
.order-details-content button:focus, .order-details-content a:focus {
    outline: 2px solid #10b981;
    outline-offset: 2px;
}

/* Smooth scrolling */
.order-details-content {
    scroll-behavior: smooth;
}
</style>

<?php
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$order = getOrderById($order_id);
$order_items = getOrderItems($order_id);

// Use the shared getOrderStatusHistory() from includes/functions.php
$order_history = getOrderStatusHistory($order_id);
?>

    <div class="container mx-auto px-4 py-4 bg-gradient-to-br from-green-50 to-blue-50 min-h-screen order-details-content">
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-4xl font-bold text-gray-800 mb-2">Order Details</h1>
                <p class="text-gray-600">Track and manage your order information</p>
            </div>
            <div class="flex flex-col sm:flex-row gap-4">
                <a href="order_history.php" class="bg-white text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-50 flex items-center justify-center shadow-lg border border-gray-200 transform hover:scale-105 transition-all duration-200 text-sm">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Orders
                </a>
            </div>
        </div>

        <?php if ($order): ?>

        <!-- Order Status Timeline -->
        <div class="mt-8 mb-8 bg-white p-4 rounded-2xl shadow-xl border border-gray-100">
            <div class="flex items-center mb-6">
                <div class="bg-gradient-to-r from-indigo-500 to-purple-600 p-3 rounded-xl mr-4">
                    <i class="fas fa-history text-white text-xl"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-800">Order Status History</h2>
            </div>

            <!-- Progress Bar -->
            <div class="mb-8">
                <?php
                $status_order = ['pending', 'confirmed', 'processing', 'shipped', 'delivered'];
                $current_index = array_search($order['status'], $status_order);
                $progress_percentage = $order['status'] === 'cancelled' ? 0 : (($current_index + 1) / count($status_order)) * 100;
                ?>
                <div class="flex flex-wrap justify-between items-center mb-4 gap-2">
                    <?php foreach ($status_order as $index => $status): ?>
                    <div class="flex flex-col items-center min-w-0 flex-1">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center mb-2 <?php echo $index <= $current_index && $order['status'] !== 'cancelled' ? 'bg-green-500 text-white' : 'bg-gray-200 text-gray-400'; ?>">
                            <i class="fas <?php
                                $icons = ['fa-clock', 'fa-check-circle', 'fa-cog', 'fa-truck', 'fa-box-open'];
                                echo $icons[$index];
                            ?> text-sm"></i>
                        </div>
                        <span class="text-xs font-medium text-gray-600 capitalize text-center"><?php echo $status; ?></span>
                    </div>
                    <?php if ($index < count($status_order) - 1): ?>
                    <div class="flex-1 mx-2 mt-5 hidden sm:block">
                        <div class="h-1 bg-gray-200 rounded">
                            <div class="h-1 bg-green-500 rounded transition-all duration-500" style="width: <?php echo $index < $current_index && $order['status'] !== 'cancelled' ? '100%' : '0%'; ?>"></div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                <!-- Mobile progress bar -->
                <div class="sm:hidden mb-4">
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-green-500 h-2 rounded-full transition-all duration-500" style="width: <?php echo $progress_percentage; ?>%"></div>
                    </div>
                    <p class="text-xs text-gray-500 text-center mt-2">Progress: <?php echo round($progress_percentage); ?>%</p>
                </div>
            </div>

            <div class="space-y-6">
                <?php
                // Show status changes first (newest first), then order creation
                if (!empty($order_history)): ?>
                <?php foreach ($order_history as $history): ?>
                <?php
                    // Map status to color classes for card, icon and text
                    $status_styles = [
                        'pending' => [
                            'card_bg' => 'bg-yellow-50',
                            'card_border' => 'border-yellow-200',
                            'icon_bg' => 'bg-yellow-100',
                            'icon_color' => 'text-yellow-600',
                            'text_color' => 'text-yellow-700'
                        ],
                        'confirmed' => [
                            'card_bg' => 'bg-blue-50',
                            'card_border' => 'border-blue-200',
                            'icon_bg' => 'bg-blue-100',
                            'icon_color' => 'text-blue-600',
                            'text_color' => 'text-blue-700'
                        ],
                        'processing' => [
                            'card_bg' => 'bg-purple-50',
                            'card_border' => 'border-purple-200',
                            'icon_bg' => 'bg-purple-100',
                            'icon_color' => 'text-purple-600',
                            'text_color' => 'text-purple-700'
                        ],
                        'shipped' => [
                            'card_bg' => 'bg-indigo-50',
                            'card_border' => 'border-indigo-200',
                            'icon_bg' => 'bg-indigo-100',
                            'icon_color' => 'text-indigo-600',
                            'text_color' => 'text-indigo-700'
                        ],
                        'delivered' => [
                            'card_bg' => 'bg-green-50',
                            'card_border' => 'border-green-200',
                            'icon_bg' => 'bg-green-100',
                            'icon_color' => 'text-green-600',
                            'text_color' => 'text-green-700'
                        ],
                        'cancelled' => [
                            'card_bg' => 'bg-red-50',
                            'card_border' => 'border-red-200',
                            'icon_bg' => 'bg-red-100',
                            'icon_color' => 'text-red-600',
                            'text_color' => 'text-red-700'
                        ]
                    ];

                    $styles = $status_styles[$history['new_status']] ?? [
                        'card_bg' => 'bg-gray-50',
                        'card_border' => 'border-gray-200',
                        'icon_bg' => 'bg-gray-100',
                        'icon_color' => 'text-gray-600',
                        'text_color' => 'text-gray-700'
                    ];

                    $status_icons = [
                        'pending' => 'fa-clock',
                        'confirmed' => 'fa-check-circle',
                        'processing' => 'fa-cog',
                        'shipped' => 'fa-truck',
                        'delivered' => 'fa-box-open',
                        'cancelled' => 'fa-times-circle'
                    ];
                    $icon_class = $status_icons[$history['new_status']] ?? 'fa-info-circle';
                ?>

                <div class="flex flex-col sm:flex-row sm:items-start gap-4 p-4 <?php echo $styles['card_bg']; ?> rounded-xl border <?php echo $styles['card_border']; ?> hover:shadow-md transition-all duration-200">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 <?php echo $styles['icon_bg']; ?> rounded-full flex items-center justify-center">
                            <i class="fas <?php echo $icon_class; ?> <?php echo $styles['icon_color']; ?>"></i>
                        </div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                            <div class="flex-1">
                                <p class="text-base font-medium <?php echo $styles['text_color']; ?>">
                                    Status changed to <span class="font-semibold <?php echo $styles['icon_color']; ?>"><?php echo ucfirst($history['new_status']); ?></span>
                                    <?php if (!empty($history['change_reason'])): ?>
                                        <span class="block mt-1 text-gray-600">• <?php echo htmlspecialchars($history['change_reason']); ?></span>
                                    <?php endif; ?>
                                </p>
                                <p class="text-sm text-gray-500 mt-1">
                                    <?php echo date('F j, Y \a\t g:i A', strtotime($history['created_at'])); ?>
                                </p>
                                <?php if (!empty($history['changed_by_name'])): ?>
                                <p class="text-xs text-gray-400 mt-1">
                                    Changed by: <?php echo htmlspecialchars($history['changed_by_name']); ?>
                                </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>

                <!-- Order creation event (always last) -->
                <div class="flex flex-col sm:flex-row sm:items-start gap-4 p-4 bg-green-50 rounded-xl border border-green-200 hover:shadow-md transition-all duration-200">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-shopping-cart text-green-600"></i>
                        </div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                            <div class="flex-1">
                                <p class="text-base font-medium text-gray-900">
                                    Order placed successfully
                                </p>
                                <p class="text-sm text-gray-500 mt-1">
                                    <?php echo date('F j, Y \a\t g:i A', strtotime($order['created_at'])); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Order Info -->
            <div class="bg-white p-4 rounded-2xl shadow-xl border border-gray-100 hover:shadow-2xl transition-all duration-300">
                <div class="flex items-center mb-4">
                    <div class="bg-gradient-to-r from-blue-500 to-purple-600 p-2 rounded-xl mr-4">
                        <i class="fas fa-receipt text-white text-xl"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-800">Order Information</h2>
                </div>
                <div class="space-y-2">
                    <div class="flex justify-between items-center p-2 bg-gray-50 rounded-lg">
                        <span class="font-medium text-gray-600">Order ID:</span>
                        <span class="font-mono text-gray-800">#<?php echo $order['id']; ?></span>
                    </div>
                    <div class="flex justify-between items-center p-2 bg-gray-50 rounded-lg">
                        <span class="font-medium text-gray-600">Date:</span>
                        <span class="text-gray-800"><?php echo date('F j, Y', strtotime($order['created_at'])); ?></span>
                    </div>
                    <div class="flex justify-between items-center p-2 bg-gray-50 rounded-lg">
                        <span class="font-medium text-gray-600">Status:</span>
                        <?php
                        $status_colors = [
                            'pending' => 'bg-yellow-100 text-yellow-800 border-yellow-300',
                            'confirmed' => 'bg-blue-100 text-blue-800 border-blue-300',
                            'processing' => 'bg-purple-100 text-purple-800 border-purple-300',
                            'shipped' => 'bg-indigo-100 text-indigo-800 border-indigo-300',
                            'delivered' => 'bg-green-100 text-green-800 border-green-300',
                            'cancelled' => 'bg-red-100 text-red-800 border-red-300'
                        ];
                        $status_class = $status_colors[$order['status']] ?? 'bg-gray-100 text-gray-800 border-gray-300';
                        ?>
                        <span class="px-4 py-2 rounded-full text-sm font-semibold border <?php echo $status_class; ?>"><?php echo ucfirst($order['status']); ?></span>
                    </div>

                    <?php if (!empty($order['tracking_number'])): ?>
                    <div class="flex justify-between items-center p-2 bg-blue-50 rounded-lg border border-blue-200">
                        <span class="font-medium text-blue-600">Tracking Number:</span>
                        <span class="font-mono text-blue-800 bg-white px-3 py-1 rounded border"><?php echo htmlspecialchars($order['tracking_number']); ?></span>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($order['estimated_delivery_date'])): ?>
                    <div class="flex justify-between items-center p-2 bg-green-50 rounded-lg border border-green-200">
                        <span class="font-medium text-green-600">Estimated Delivery:</span>
                        <span class="text-green-800 bg-white px-3 py-1 rounded border"><?php echo date('F j, Y', strtotime($order['estimated_delivery_date'])); ?></span>
                    </div>
                    <?php endif; ?>

                    <div class="flex justify-between items-center p-2 bg-gray-50 rounded-lg">
                        <span class="font-medium text-gray-600">Delivery Area:</span>
                        <span class="text-gray-800"><?php echo ucfirst(str_replace('_', ' ', $order['delivery_area'] ?? 'Not specified')); ?></span>
                    </div>
                    <div class="flex justify-between items-center p-2 bg-gray-50 rounded-lg">
                        <span class="font-medium text-gray-600">Delivery Charge:</span>
                        <span class="text-gray-800 font-semibold">৳<?php echo number_format($order['delivery_charge'] ?? 0, 2); ?></span>
                    </div>

                    <?php if (!empty($order['promo_code'])): ?>
                    <div class="flex justify-between items-center p-2 bg-green-50 rounded-lg border border-green-200">
                        <span class="font-medium text-green-600">Promo Code:</span>
                        <span class="text-green-800 bg-white px-3 py-1 rounded border"><?php echo $order['promo_code']; ?></span>
                    </div>
                    <div class="flex justify-between items-center p-2 bg-green-50 rounded-lg border border-green-200">
                        <span class="font-medium text-green-600">Discount:</span>
                        <span class="text-green-600 font-semibold">-৳<?php echo number_format($order['discount_amount'] ?? 0, 2); ?></span>
                    </div>
                    <?php endif; ?>

                    <?php if ($order['status'] === 'cancelled' && !empty($order['cancellation_reason'])): ?>
                    <div class="p-2 bg-red-50 rounded-lg border border-red-200">
                        <span class="font-medium text-red-600">Cancellation Reason:</span>
                        <p class="text-red-800 mt-1"><?php echo htmlspecialchars($order['cancellation_reason']); ?></p>
                    </div>
                    <?php endif; ?>

                    <div class="border-t pt-3 mt-3">
                        <div class="flex justify-between items-center p-2 bg-blue-50 rounded-lg border border-blue-200">
                            <span class="font-medium text-blue-600">Subtotal:</span>
                            <span class="text-blue-800 font-semibold">৳<?php echo number_format($order['total'] - ($order['delivery_charge'] ?? 0) + ($order['discount_amount'] ?? 0), 2); ?></span>
                        </div>
                        <div class="flex justify-between items-center p-2 bg-green-50 rounded-lg border border-green-200 mt-1">
                            <span class="font-medium text-green-600 text-lg">Total:</span>
                            <span class="text-green-800 font-bold text-xl">৳<?php echo number_format($order['total'], 2); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Customer Info -->
            <div class="bg-white p-4 rounded-2xl shadow-xl border border-gray-100 hover:shadow-2xl transition-all duration-300">
                <div class="flex items-center mb-4">
                    <div class="bg-gradient-to-r from-green-500 to-teal-600 p-2 rounded-xl mr-4">
                        <i class="fas fa-user text-white text-xl"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-800">Customer Information</h2>
                </div>
                <div class="space-y-2">
                    <div class="flex items-center p-2 bg-gray-50 rounded-lg">
                        <i class="fas fa-user-circle text-gray-400 mr-3"></i>
                        <div>
                            <p class="text-sm text-gray-500">Name</p>
                            <p class="font-medium text-gray-800"><?php echo $order['user_name']; ?></p>
                        </div>
                    </div>
                    <div class="flex items-center p-2 bg-gray-50 rounded-lg">
                        <i class="fas fa-envelope text-gray-400 mr-3"></i>
                        <div>
                            <p class="text-sm text-gray-500">Email</p>
                            <p class="font-medium text-gray-800"><?php echo $order['user_email']; ?></p>
                        </div>
                    </div>
                    <div class="flex items-center p-2 bg-gray-50 rounded-lg">
                        <i class="fas fa-phone text-gray-400 mr-3"></i>
                        <div>
                            <p class="text-sm text-gray-500">Phone</p>
                            <p class="font-medium text-gray-800"><?php echo $order['user_phone']; ?></p>
                        </div>
                    </div>
                    <div class="p-2 bg-gray-50 rounded-lg">
                        <div class="flex items-start">
                            <i class="fas fa-map-marker-alt text-gray-400 mr-3 mt-1"></i>
                            <div class="flex-1">
                                <p class="text-sm text-gray-500 mb-1">Billing Address</p>
                                <p class="font-medium text-gray-800"><?php echo $order['user_address']; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="p-2 bg-gray-50 rounded-lg">
                        <div class="flex items-start">
                            <i class="fas fa-truck text-gray-400 mr-3 mt-1"></i>
                            <div class="flex-1">
                                <p class="text-sm text-gray-500 mb-1">Shipping Address</p>
                                <p class="font-medium text-gray-800"><?php echo $order['address']; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Order Items -->
        <div class="mt-8 bg-white p-4 rounded-2xl shadow-xl border border-gray-100">
            <div class="flex items-center mb-6">
                <div class="bg-gradient-to-r from-orange-500 to-red-600 p-3 rounded-xl mr-4">
                    <i class="fas fa-shopping-bag text-white text-xl"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-800">Order Items</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-full table-auto border-collapse">
                    <thead>
                        <tr class="border-b-2 border-gray-200">
                            <th class="text-left py-2 px-4 font-semibold text-gray-700 min-w-0">Product</th>
                            <th class="text-center py-2 px-4 font-semibold text-gray-700 w-24">Quantity</th>
                            <th class="text-right py-2 px-4 font-semibold text-gray-700 w-24">Price</th>
                            <th class="text-right py-2 px-4 font-semibold text-gray-700 w-32">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($order_items as $item): ?>
                        <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors duration-200">
                            <td class="py-3 px-4 min-w-0">
                                <div class="flex items-center">
                                    <div class="w-16 h-16 bg-gray-100 rounded-xl overflow-hidden mr-4 flex-shrink-0">
                                        <img src="/assets/images/<?php echo $item['product_image']; ?>" alt="<?php echo $item['product_name']; ?>" class="w-full h-full object-cover">
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="font-medium text-gray-800 text-lg truncate"><?php echo $item['product_name']; ?></p>
                                        <p class="text-sm text-gray-500">Natural & Organic Product</p>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center py-3 px-4">
                                <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full font-semibold"><?php echo $item['quantity']; ?></span>
                            </td>
                            <td class="text-right py-3 px-4 font-semibold text-gray-700">৳<?php echo number_format($item['price'], 2); ?></td>
                            <td class="text-right py-3 px-4 font-bold text-green-600 text-lg">৳<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>



        <!-- Order Status Information -->
        <div class="mt-8 bg-white p-4 rounded-2xl shadow-xl border border-gray-100">
            <div class="flex items-center mb-6">
                <div class="bg-gradient-to-r from-teal-500 to-cyan-600 p-3 rounded-xl mr-4">
                    <i class="fas fa-info-circle text-white text-xl"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-800">Order Status & Next Steps</h2>
            </div>
            <div class="space-y-6">
                <?php
                $status_messages = [
                    'pending' => [
                        'icon' => 'fa-clock',
                        'color' => 'text-yellow-600',
                        'bg' => 'bg-yellow-50',
                        'border' => 'border-yellow-200',
                        'title' => 'Order Pending',
                        'message' => 'Your order is being reviewed. We will confirm it shortly.',
                        'next_steps' => ['Wait for order confirmation', 'Check your email for updates']
                    ],
                    'confirmed' => [
                        'icon' => 'fa-check-circle',
                        'color' => 'text-blue-600',
                        'bg' => 'bg-blue-50',
                        'border' => 'border-blue-200',
                        'title' => 'Order Confirmed',
                        'message' => 'Your order has been confirmed and is being prepared.',
                        'next_steps' => ['We will start processing your order', 'You will receive updates via SMS/email']
                    ],
                    'processing' => [
                        'icon' => 'fa-cog',
                        'color' => 'text-purple-600',
                        'bg' => 'bg-purple-50',
                        'border' => 'border-purple-200',
                        'title' => 'Order Processing',
                        'message' => 'Your order is being prepared and packaged.',
                        'next_steps' => ['Quality check in progress', 'Will be shipped soon']
                    ],
                    'shipped' => [
                        'icon' => 'fa-truck',
                        'color' => 'text-indigo-600',
                        'bg' => 'bg-indigo-50',
                        'border' => 'border-indigo-200',
                        'title' => 'Order Shipped',
                        'message' => 'Your order has been shipped and is on its way to you.',
                        'next_steps' => ['Track your package using the tracking number', 'Delivery expected by estimated date']
                    ],
                    'delivered' => [
                        'icon' => 'fa-box-open',
                        'color' => 'text-green-600',
                        'bg' => 'bg-green-50',
                        'border' => 'border-green-200',
                        'title' => 'Order Delivered',
                        'message' => 'Your order has been successfully delivered.',
                        'next_steps' => ['Enjoy your natural products!', 'Leave a review if you loved them']
                    ],
                    'cancelled' => [
                        'icon' => 'fa-times-circle',
                        'color' => 'text-red-600',
                        'bg' => 'bg-red-50',
                        'border' => 'border-red-200',
                        'title' => 'Order Cancelled',
                        'message' => 'This order has been cancelled.',
                        'next_steps' => ['Contact us if you have questions', 'Place a new order anytime']
                    ]
                ];

                $current_status = $status_messages[$order['status']] ?? $status_messages['pending'];
                ?>

                <div class="border-2 <?php echo $current_status['border']; ?> rounded-2xl p-6 <?php echo $current_status['bg']; ?> hover:shadow-lg transition-all duration-300">
                    <div class="flex items-center mb-4">
                        <i class="fas <?php echo $current_status['icon']; ?> <?php echo $current_status['color']; ?> text-2xl mr-4"></i>
                        <h3 class="text-xl font-bold <?php echo $current_status['color']; ?>"><?php echo $current_status['title']; ?></h3>
                    </div>
                    <p class="text-gray-700 mb-6 text-lg"><?php echo $current_status['message']; ?></p>

                    <?php if (!empty($order['tracking_number']) && $order['status'] === 'shipped'): ?>
                    <div class="bg-white p-4 rounded-xl border mb-6 shadow-sm">
                        <p class="text-sm font-medium text-gray-700 mb-2"><i class="fas fa-truck mr-2"></i>Tracking Information:</p>
                        <p class="font-mono text-lg text-gray-800 bg-gray-50 p-3 rounded border tracking-number"><?php echo htmlspecialchars($order['tracking_number']); ?></p>
                        <p class="text-sm text-gray-500 mt-2">Use this tracking number to track your package with our delivery partner.</p>
                    </div>
                    <?php endif; ?>

                    <div class="bg-white p-4 rounded-xl border shadow-sm">
                        <p class="text-lg font-medium text-gray-700 mb-3"><i class="fas fa-list-check mr-2"></i>Next Steps:</p>
                        <ul class="space-y-2">
                            <?php foreach ($current_status['next_steps'] as $step): ?>
                            <li class="flex items-center text-gray-700">
                                <i class="fas fa-check-circle text-green-500 mr-3 text-sm"></i>
                                <span><?php echo $step; ?></span>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>

                <div class="bg-gradient-to-r from-gray-50 to-gray-100 p-6 rounded-2xl border border-gray-200">
                    <h4 class="font-bold text-gray-800 mb-4 text-lg"><i class="fas fa-question-circle mr-2"></i>Need Help?</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-white p-4 rounded-xl shadow-sm border">
                            <div class="flex items-center mb-2">
                                <i class="fas fa-phone text-green-600 mr-3"></i>
                                <span class="font-medium text-gray-700">Phone Support</span>
                            </div>
                            <p class="text-gray-600">+880 1234-567890</p>
                            <p class="text-sm text-gray-500">Mon-Fri, 9AM-6PM</p>
                        </div>
                        <div class="bg-white p-4 rounded-xl shadow-sm border">
                            <div class="flex items-center mb-2">
                                <i class="fas fa-envelope text-blue-600 mr-3"></i>
                                <span class="font-medium text-gray-700">Email Support</span>
                            </div>
                            <p class="text-gray-600">support@naturebd.com</p>
                            <p class="text-sm text-gray-500">24/7 Response</p>
                        </div>
                        <div class="bg-white p-4 rounded-xl shadow-sm border">
                            <div class="flex items-center mb-2">
                                <i class="fas fa-clock text-purple-600 mr-3"></i>
                                <span class="font-medium text-gray-700">Support Hours</span>
                            </div>
                            <p class="text-gray-600">9 AM - 6 PM</p>
                            <p class="text-sm text-gray-500">Daily</p>
                        </div>
                        <div class="bg-white p-4 rounded-xl shadow-sm border">
                            <div class="flex items-center mb-2">
                                <i class="fas fa-map-marker-alt text-red-600 mr-3"></i>
                                <span class="font-medium text-gray-700">Location</span>
                            </div>
                            <p class="text-gray-600">Dhaka, Bangladesh</p>
                            <p class="text-sm text-gray-500">Head Office</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php else: ?>
        <div class="text-center py-8 bg-white rounded-2xl shadow-xl border border-gray-100">
            <div class="max-w-md mx-auto">
                <div class="w-24 h-24 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-exclamation-triangle text-6xl text-red-400"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-800 mb-4">Order Not Found</h2>
                <p class="text-gray-600 mb-8 text-lg">The order you're looking for doesn't exist or may have been removed.</p>
                <div class="space-y-4">
                    <a href="order_history.php" class="inline-flex items-center bg-gradient-to-r from-green-500 to-green-600 text-white px-8 py-2 rounded-xl hover:from-green-600 hover:to-green-700 shadow-lg transform hover:scale-105 transition-all duration-200">
                        <i class="fas fa-arrow-left mr-3"></i>View Order History
                    </a>
                    <br>
                    <a href="products.php" class="inline-flex items-center text-green-600 hover:text-green-700 font-medium">
                        <i class="fas fa-shopping-bag mr-2"></i>Browse Products
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            updateCartCount();

            const scope = document.querySelector('.order-details-content') || document.body;

            // Add smooth scrolling and animations (only inside order details)
            const cards = scope.querySelectorAll('.bg-white');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    card.style.transition = 'all 0.6s ease-out';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });

            // Add hover effects for table rows (scoped)
            const tableRows = scope.querySelectorAll('tbody tr');
            tableRows.forEach(row => {
                row.addEventListener('mouseenter', function() {
                    this.style.transform = 'scale(1.01)';
                    this.style.transition = 'transform 0.2s ease';
                });
                row.addEventListener('mouseleave', function() {
                    this.style.transform = 'scale(1)';
                });
            });

            // Add click effect for buttons/links inside scope
            const buttons = scope.querySelectorAll('button, a');
            buttons.forEach(button => {
                button.addEventListener('mousedown', function() {
                    this.style.transform = 'scale(0.98)';
                });
                button.addEventListener('mouseup', function() {
                    this.style.transform = 'scale(1)';
                });
                button.addEventListener('mouseleave', function() {
                    this.style.transform = 'scale(1)';
                });
            });

            // Add tooltip for tracking number (only elements explicitly marked and scoped)
            const trackingElements = scope.querySelectorAll('.tracking-number');
            trackingElements.forEach(element => {
                element.title = 'Click to copy tracking number';
                element.style.cursor = 'pointer';
                element.addEventListener('click', function() {
                    const text = this.textContent.trim();
                    if (!text) return;
                    navigator.clipboard.writeText(text).then(() => {
                        // Simple feedback
                        const original = this.textContent;
                        this.textContent = 'Copied!';
                        this.style.color = '#10b981';
                        setTimeout(() => {
                            this.textContent = original;
                            this.style.color = '';
                        }, 1000);
                    }).catch(() => {
                        // optional: silently ignore clipboard errors or provide fallback
                    });
                });
            });
        });

        // Attach print event listener
        document.addEventListener('DOMContentLoaded', function() {
            const printBtn = document.getElementById('printBtn');
            if (printBtn) {
                printBtn.addEventListener('click', printOrder);
            }
        });
    </script>
    <script src="/js/app.js"></script>
</body>
</html>