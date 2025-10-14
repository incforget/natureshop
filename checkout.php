<?php
include_once 'includes/config.php';
include_once 'includes/functions.php';

$page_title = "Checkout";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Check if cart is empty
    $cart_items = json_decode($_POST['cart_items'] ?? '[]', true);
    if (empty($cart_items)) {
        header("Location: checkout.php?error=" . urlencode("Your cart is empty. Please add some products before placing an order."));
        exit;
    }

    // Process order
    $customer_name = $_POST['name'];
    $customer_email = $_POST['email'];
    $customer_phone = $_POST['phone'];
    $customer_address = $_POST['address'];
    $area = $_POST['area'];
    $cart_items = json_decode($_POST['cart_items'], true);
    $total = $_POST['total'];
    $promo_code = isset($_POST['promo_code']) ? trim($_POST['promo_code']) : null;
    $discount_amount = isset($_POST['discount_amount']) ? floatval($_POST['discount_amount']) : 0;

    // Calculate delivery charge
    $delivery_charge = 0;
    if ($area === 'inside_dhaka') {
        $delivery_charge = 60;
    } elseif ($area === 'outside_dhaka') {
        $delivery_charge = 120;
    }

    // Validate promo code if provided
    if ($promo_code) {
        $subtotal = 0;
        foreach ($cart_items as $item) {
            $subtotal += $item['price'] * $item['quantity'];
        }

        $promo_result = applyPromoCode($promo_code, $subtotal);
        if (!$promo_result['valid']) {
            // Invalid promo code - redirect back with error
            header("Location: checkout.php?error=" . urlencode($promo_result['message']));
            exit;
        }

        // Ensure discount amount matches
        if (abs($promo_result['discount'] - $discount_amount) > 0.01) {
            header("Location: checkout.php?error=" . urlencode("Invalid discount amount"));
            exit;
        }
    }

    // Check if user exists, if not create
    $user_id = getOrCreateUser($customer_email, $customer_name, $customer_phone, $customer_address);

    // Create order with promo code support
    $order_id = createOrder($user_id, $total, $cart_items, $customer_address, $area, $delivery_charge, $promo_code, $discount_amount);

    // Set session for user
    $_SESSION['user_id'] = $user_id;

    // Redirect to order success
    header("Location: /order-success/$order_id");
    exit;
}

include 'includes/header.php';
?>

    <!-- Checkout Progress -->
    <div class="bg-white shadow-sm border-b">
        <div class="container mx-auto px-4 py-4">
            <div class="flex items-center justify-center space-x-4">
                <div class="flex items-center text-green-600">
                    <i class="fas fa-shopping-cart text-lg"></i>
                    <span class="ml-2 font-semibold">Cart</span>
                </div>
                <div class="flex-1 h-1 bg-green-200 rounded">
                    <div class="h-1 bg-green-600 rounded w-1/2"></div>
                </div>
                <div class="flex items-center text-green-600">
                    <i class="fas fa-credit-card text-lg"></i>
                    <span class="ml-2 font-semibold">Checkout</span>
                </div>
                <div class="flex-1 h-1 bg-gray-200 rounded"></div>
                <div class="flex items-center text-gray-400">
                    <i class="fas fa-check-circle text-lg"></i>
                    <span class="ml-2 font-semibold">Complete</span>
                </div>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 py-8 max-w-6xl">
        <h1 class="text-3xl font-bold mb-6">Checkout</h1>

        <?php if (isset($_GET['error'])): ?>
        <div class="bg-red-50 border border-red-200 text-red-800 px-6 py-4 rounded-xl mb-6 shadow-sm">
            <div class="flex items-center">
                <i class="fas fa-exclamation-triangle text-red-500 mr-3"></i>
                <span class="font-medium"><?php echo htmlspecialchars($_GET['error']); ?></span>
            </div>
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Customer Information -->
            <div class="bg-white rounded-xl shadow-lg p-4 border border-gray-100 w-full order-2 lg:order-1">
                <h2 class="text-2xl font-bold mb-6 text-gray-800 flex items-center">
                    <i class="fas fa-user text-green-600 mr-3"></i>
                    Your Information
                </h2>
                <form id="checkout-form" method="POST" class="space-y-4">
                    <div class="relative">
                        <label class="block text-sm font-semibold mb-1 text-gray-700">Full Name *</label>
                        <div class="relative">
                            <i class="fas fa-user absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                            <input type="text" name="name" required placeholder="Enter your full name" class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all">
                        </div>
                    </div>
                    <div class="relative">
                        <label class="block text-sm font-semibold mb-1 text-gray-700">Email (Optional)</label>
                        <div class="relative">
                            <i class="fas fa-envelope absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                            <input type="email" name="email" placeholder="your@email.com" class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all">
                        </div>
                    </div>
                    <div class="relative">
                        <label class="block text-sm font-semibold mb-1 text-gray-700">Phone *</label>
                        <div class="relative">
                            <i class="fas fa-phone absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                            <input type="tel" name="phone" required placeholder="+880 1XX XXX XXXX" class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all">
                        </div>
                    </div>
                    <div class="relative">
                        <label class="block text-sm font-semibold mb-1 text-gray-700">Address *</label>
                        <div class="relative">
                            <i class="fas fa-map-marker-alt absolute left-3 top-3 text-gray-400"></i>
                            <textarea name="address" required rows="3" placeholder="Enter your complete delivery address" class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all resize-none"></textarea>
                        </div>
                    </div>
                    <div class="relative">
                        <label class="block text-sm font-semibold mb-1 text-gray-700">Delivery Area *</label>
                        <div class="relative">
                            <i class="fas fa-truck absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 z-10"></i>
                            <select name="area" id="delivery-area" required class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all appearance-none bg-white">
                                <option value="">Choose your delivery area</option>
                                <option value="inside_dhaka">Inside Dhaka (৳60)</option>
                                <option value="outside_dhaka">Outside Dhaka (৳120)</option>
                            </select>
                            <i class="fas fa-chevron-down absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 pointer-events-none"></i>
                        </div>
                    </div>

                    <!-- Promo Code Section -->
                    <div class="border-t border-gray-200 pt-4">
                        <h3 class="text-lg font-bold mb-4 text-gray-800 flex items-center">
                            <i class="fas fa-tags text-green-600 mr-2"></i>
                            Have a Promo Code?
                        </h3>
                        <div class="flex gap-3">
                            <div class="relative flex-1">
                                <i class="fas fa-percent absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                                <input type="text" id="promo-code" placeholder="Enter discount code" class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all">
                            </div>
                            <button type="button" id="apply-promo" class="bg-gradient-to-r from-blue-600 to-blue-700 text-white px-6 py-3 rounded-lg hover:from-blue-700 hover:to-blue-800 font-semibold transition-all duration-200 shadow-md hover:shadow-lg">
                                <span id="apply-text">Apply</span>
                                <i id="apply-spinner" class="fas fa-spinner fa-spin ml-2 hidden"></i>
                            </button>
                        </div>
                        <div id="promo-message" class="mt-3 text-sm font-medium"></div>
                        <input type="hidden" name="promo_code" id="promo-code-input">
                        <input type="hidden" name="discount_amount" id="discount-amount-input" value="0">
                    </div>

                    <input type="hidden" name="cart_items" id="cart-items-input">
                    <input type="hidden" name="total" id="total-input">
                    <button type="submit" class="w-full bg-gradient-to-r from-green-600 to-emerald-600 text-white py-4 rounded-xl hover:from-green-700 hover:to-emerald-700 font-bold text-lg transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                        <i class="fas fa-credit-card mr-2"></i>
                        Complete Order
                    </button>
                </form>
            </div>

            <!-- Cart Items -->
            <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100 h-auto w-full self-start order-1 lg:order-2">
                <h2 class="text-2xl font-bold mb-6 text-gray-800 flex items-center">
                    <i class="fas fa-shopping-bag text-green-600 mr-3"></i>
                    Your Cart
                </h2>
                <div id="cart-items" class="space-y-4">
                    <!-- Cart items will be loaded here -->
                </div>
                <div id="cart-total" class="mt-8 border-t border-gray-200 pt-6 space-y-3">
                    <div class="flex justify-between text-gray-600">
                        <span>Subtotal:</span>
                        <span class="font-medium">৳<span id="subtotal-amount">0</span></span>
                    </div>
                    <div class="flex justify-between text-gray-600">
                        <span>Delivery Charge:</span>
                        <span class="font-medium">৳<span id="delivery-charge">0</span></span>
                    </div>
                    <div class="flex justify-between text-green-600 font-medium" id="discount-row" style="display: none;">
                        <span>Discount (<span id="applied-code"></span>):</span>
                        <span>-৳<span id="discount-amount">0</span></span>
                    </div>
                    <div class="flex justify-between font-bold text-xl text-gray-800 border-t border-gray-300 pt-3">
                        <span>Total:</span>
                        <span>৳<span id="total-amount">0</span></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Notifications -->
    <div id="toast-container" class="fixed top-4 right-4 z-50 space-y-2"></div>

    <?php include 'includes/footer.php'; ?>

    <script src="/js/app.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            loadCartForCheckout();
            updateCartCount();
            loadUserInfo();
            setupFormSubmit();
            setupPromoCode();

            // Add event listener for delivery area change
            document.getElementById('delivery-area').addEventListener('change', updateTotal);
        });

        function loadCartForCheckout() {
            const cart = (typeof getCart === 'function') ? getCart() : JSON.parse(localStorage.getItem('cart') || '[]');
            const cartItemsDiv = document.getElementById('cart-items');
            const subtotalSpan = document.getElementById('subtotal-amount');
            let subtotal = 0;

            cartItemsDiv.innerHTML = '';

            if (cart.length === 0) {
                cartItemsDiv.innerHTML = `
                    <div class="text-center py-8">
                        <i class="fas fa-shopping-cart text-gray-300 text-4xl mb-4"></i>
                        <p class="text-gray-500 text-lg font-medium">Your cart is empty</p>
                        <p class="text-gray-400 text-sm">Please add some products to continue with your order</p>
                    </div>
                `;
                // Reset subtotal to 0 when cart is empty
                subtotalSpan.textContent = '0';
                updateTotal();
                document.getElementById('cart-items-input').value = JSON.stringify(cart);
                return;
            }

            cart.forEach((item, index) => {
                const itemDiv = document.createElement('div');
                itemDiv.className = 'cart-item p-4 border border-gray-200 rounded-lg mb-4';
                itemDiv.innerHTML = `
                    <!-- Mobile Layout -->
                    <div class="md:hidden">
                        <div class="flex items-start space-x-4">
                            <img src="/assets/images/${item.image}" alt="${item.name}" class="cart-item-image flex-shrink-0" onerror="this.src='/assets/images/placeholder.jpg'">
                            <div class="flex-1 min-w-0">
                                <!-- Name and Price in first row -->
                                <div class="flex items-center justify-between mb-1">
                                    <h4 class="font-semibold text-gray-900 truncate">${item.name}</h4>
                                    <p class="text-lg font-bold text-green-600 ml-2">৳${item.price}</p>
                                </div>
                                <!-- Size in second row -->
                                ${item.size ? `<p class="text-sm text-gray-600 mb-2">Size: ${item.size}</p>` : ''}
                                <!-- Quantity controls in third row -->
                                <div class="flex items-center justify-between">
                                    <div class="quantity-controls">
                                        <button class="quantity-btn" onclick="updateCartQuantity(${index}, ${item.quantity - 1})">
                                            <i class="fas fa-minus text-sm"></i>
                                        </button>
                                        <input type="number" value="${item.quantity}" min="1" class="quantity-input" onchange="updateCartQuantity(${index}, this.value)">
                                        <button class="quantity-btn" onclick="updateCartQuantity(${index}, ${item.quantity + 1})">
                                            <i class="fas fa-plus text-sm"></i>
                                        </button>
                                    </div>
                                    <button class="remove-btn p-2" onclick="removeCartItem(${index})">
                                        <i class="fas fa-trash text-lg"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Desktop Layout -->
                    <div class="hidden md:flex items-center space-x-4 bg-gray-50 p-4 rounded-xl border border-gray-200 hover:shadow-md transition-shadow">
                        <img src="/assets/images/${item.image}" alt="${item.name}" class="w-20 h-20 object-cover rounded-lg shadow-sm flex-shrink-0">
                        <div class="flex-1 min-w-0">
                            <h3 class="font-bold text-gray-800 whitespace-nowrap">${item.name}</h3>
                            ${item.size ? `<p class="text-sm text-gray-500 mb-1">Size: ${item.size}</p>` : ''}
                            <p class="text-gray-600 font-medium">৳${item.price} each</p>
                        </div>
                        <div class="flex items-center space-x-2 flex-shrink-0">
                            <div class="flex items-center bg-white rounded-lg border border-gray-300">
                                <button class="minus-btn px-3 py-1 text-gray-600 hover:text-red-600 hover:bg-red-50 transition-colors" data-index="${index}">
                                    <i class="fas fa-minus text-sm"></i>
                                </button>
                                <span class="quantity px-3 py-1 font-semibold text-gray-800 min-w-[3rem] text-center">${item.quantity}</span>
                                <button class="plus-btn px-3 py-1 text-gray-600 hover:text-green-600 hover:bg-green-50 transition-colors" data-index="${index}">
                                    <i class="fas fa-plus text-sm"></i>
                                </button>
                            </div>
                            <button class="remove-btn p-2 flex-shrink-0" onclick="removeCartItem(${index})">
                                <i class="fas fa-trash text-lg"></i>
                            </button>
                            <div class="text-right min-w-[5rem] flex-shrink-0">
                                <p class="font-bold text-lg text-gray-800">৳${item.price * item.quantity}</p>
                            </div>
                        </div>
                    </div>
                `;
                cartItemsDiv.appendChild(itemDiv);
                subtotal += item.price * item.quantity;
            });

            subtotalSpan.textContent = subtotal;
            updateTotal();
            document.getElementById('cart-items-input').value = JSON.stringify(cart);

            // Add quantity control event listeners
            setupQuantityControls();
        }

        function setupQuantityControls() {
            // Plus buttons
            document.querySelectorAll('.plus-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const index = parseInt(this.getAttribute('data-index'));
                    updateCartQuantity(index, 1);
                });
            });

            // Minus buttons
            document.querySelectorAll('.minus-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const index = parseInt(this.getAttribute('data-index'));
                    updateCartQuantity(index, -1);
                });
            });
        }

        function updateCartQuantity(index, change) {
            const cart = (typeof getCart === 'function') ? getCart() : JSON.parse(localStorage.getItem('cart') || '[]');

            if (cart[index]) {
                if (typeof change === 'string') {
                    change = parseInt(change);
                    if (change < 1) {
                        removeCartItem(index);
                        return;
                    }
                } else {
                    change = cart[index].quantity + change;
                    if (change < 1) {
                        removeCartItem(index);
                        return;
                    }
                }
                cart[index].quantity = change;

                // Save updated cart (use setCart when available)
                if (typeof setCart === 'function') setCart(cart); else localStorage.setItem('cart', JSON.stringify(cart));

                // Reload cart display
                loadCartForCheckout();

                // Update cart count in header
                updateCartCount();

                // Clear promo code if cart becomes empty
                if (cart.length === 0) {
                    clearPromoCode();
                }
            }
        }

        function removeCartItem(index) {
            const cart = (typeof getCart === 'function') ? getCart() : JSON.parse(localStorage.getItem('cart') || '[]');
            if (cart[index]) {
                cart.splice(index, 1);
                if (typeof setCart === 'function') setCart(cart); else localStorage.setItem('cart', JSON.stringify(cart));
                loadCartForCheckout();
                updateCartCount();
                if (cart.length === 0) {
                    clearPromoCode();
                }
                showToast('Item removed from cart!', 'info');
            }
        }

        function updateTotal() {
            const cart = (typeof getCart === 'function') ? getCart() : JSON.parse(localStorage.getItem('cart') || '[]');
            const subtotal = parseFloat(document.getElementById('subtotal-amount').textContent) || 0;
            const areaSelect = document.getElementById('delivery-area');
            const deliveryChargeSpan = document.getElementById('delivery-charge');
            const totalSpan = document.getElementById('total-amount');
            const totalInput = document.getElementById('total-input');
            const discountAmount = parseFloat(document.getElementById('discount-amount-input').value) || 0;

            // If cart is empty, reset all charges to 0
            if (cart.length === 0) {
                deliveryChargeSpan.textContent = '0';
                totalSpan.textContent = '0';
                totalInput.value = '0';
                return;
            }

            let deliveryCharge = 0;
            if (areaSelect.value === 'inside_dhaka') {
                deliveryCharge = 60;
            } else if (areaSelect.value === 'outside_dhaka') {
                deliveryCharge = 120;
            }

            deliveryChargeSpan.textContent = deliveryCharge;
            const total = subtotal + deliveryCharge - discountAmount;
            totalSpan.textContent = Math.max(total, 0); // Ensure total doesn't go negative
            totalInput.value = Math.max(total, 0);
        }

        function setupPromoCode() {
            const applyButton = document.getElementById('apply-promo');
            const promoInput = document.getElementById('promo-code');
            const messageDiv = document.getElementById('promo-message');
            const applyText = document.getElementById('apply-text');
            const applySpinner = document.getElementById('apply-spinner');

            applyButton.addEventListener('click', async function() {
                const code = promoInput.value.trim().toUpperCase();
                if (!code) {
                    showPromoMessage('Please enter a promo code', 'error');
                    return;
                }

                // Show loading state
                applyButton.disabled = true;
                applyText.textContent = 'Applying...';
                applySpinner.classList.remove('hidden');

                const subtotal = parseFloat(document.getElementById('subtotal-amount').textContent) || 0;

                try {
                    const response = await fetch('includes/validate_promo.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ code: code, subtotal: subtotal })
                    });

                    const result = await response.json();

                    if (result.valid) {
                        // Apply discount
                        document.getElementById('promo-code-input').value = code;
                        document.getElementById('discount-amount-input').value = result.discount;
                        document.getElementById('applied-code').textContent = code;
                        document.getElementById('discount-amount').textContent = result.discount;
                        document.getElementById('discount-row').style.display = 'flex';

                        showPromoMessage(result.message, 'success');
                        updateTotal();
                    } else {
                        showPromoMessage(result.message, 'error');
                    }
                } catch (error) {
                    showPromoMessage('Error validating promo code', 'error');
                } finally {
                    // Hide loading state
                    applyButton.disabled = false;
                    applyText.textContent = 'Apply';
                    applySpinner.classList.add('hidden');
                }
            });

            // Clear promo code when input changes
            promoInput.addEventListener('input', function() {
                if (!promoInput.value.trim()) {
                    clearPromoCode();
                }
            });
        }

        function showPromoMessage(message, type) {
            const messageDiv = document.getElementById('promo-message');
            messageDiv.textContent = message;
            messageDiv.className = `mt-3 text-sm font-medium p-3 rounded-lg transition-all ${
                type === 'success' 
                    ? 'text-green-700 bg-green-50 border border-green-200' 
                    : 'text-red-700 bg-red-50 border border-red-200'
            }`;
        }

        function clearPromoCode() {
            document.getElementById('promo-code-input').value = '';
            document.getElementById('discount-amount-input').value = '0';
            document.getElementById('discount-row').style.display = 'none';
            document.getElementById('promo-message').textContent = '';
            updateTotal();
        }

        function loadUserInfo() {
            const userInfo = (typeof getLocal === 'function') ? getLocal('userInfo', {}) : JSON.parse(localStorage.getItem('userInfo') || '{}');
            document.querySelector('input[name="name"]').value = userInfo.name || '';
            document.querySelector('input[name="email"]').value = userInfo.email || '';
            document.querySelector('input[name="phone"]').value = userInfo.phone || '';
            document.querySelector('textarea[name="address"]').value = userInfo.address || '';
        }

        function setupFormSubmit() {
            document.getElementById('checkout-form').addEventListener('submit', function(e) {
                const cart = (typeof getCart === 'function') ? getCart() : JSON.parse(localStorage.getItem('cart') || '[]');

                // Prevent submission if cart is empty
                if (cart.length === 0) {
                    e.preventDefault();
                    showToast('Your cart is empty. Please add some products before placing an order.', 'error');
                    return false;
                }

                // Save user info to localStorage
                const userInfo = {
                    name: document.querySelector('input[name="name"]').value,
                    email: document.querySelector('input[name="email"]').value,
                    phone: document.querySelector('input[name="phone"]').value,
                    address: document.querySelector('textarea[name="address"]').value
                };
                if (typeof setLocal === 'function') setLocal('userInfo', userInfo); else localStorage.setItem('userInfo', JSON.stringify(userInfo));
            });
        }
    </script>
</body>
</html>