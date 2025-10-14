const { createApp } = Vue;

createApp({
    data() {
        return {
            cartCount: 0
        }
    },
    methods: {
        addToCart() {
            this.cartCount++;
        }
    }
}).mount('#app');

// --- Robust cart storage helpers (safe parse, debounced writes, cross-tab sync) ---
const CART_KEY = 'cart';
let _cartWriteTimer = null;
const CART_WRITE_DELAY = 120; // ms debounce to reduce sync pressure

// BroadcastChannel for cross-tab sync when available
let _bc = null;
try {
    _bc = new BroadcastChannel('shop_cart_channel');
    _bc.onmessage = (ev) => {
        try {
            if (!ev.data) return;
            if (ev.data.type === 'cart:update') {
                // Update UI in response to external changes
                updateCartCount();
                const modal = document.getElementById('cart-modal');
                if (modal && !modal.classList.contains('hidden')) {
                    renderCartItems();
                }
            }
        } catch (e) { /* ignore cross-tab handler errors */ }
    };
} catch (e) {
    _bc = null;
}

function getCart() {
    try {
        const raw = localStorage.getItem(CART_KEY);
        if (!raw) return [];
        const parsed = JSON.parse(raw);
        if (!Array.isArray(parsed)) return [];
        return parsed;
    } catch (err) {
        // Corrupted data -> remove and return empty
        try { localStorage.removeItem(CART_KEY); } catch (e) {}
        return [];
    }
}

function setCart(cart) {
    // Debounced write to avoid blocking main thread on repeated updates
    if (_cartWriteTimer) clearTimeout(_cartWriteTimer);
    _cartWriteTimer = setTimeout(() => {
        try {
            localStorage.setItem(CART_KEY, JSON.stringify(cart));
            // broadcast update to other tabs
            if (_bc) {
                try { _bc.postMessage({ type: 'cart:update' }); } catch (e) {}
            }
        } catch (e) {
            // localStorage write may fail (quota), log for now
            console.error('Failed to write cart to localStorage', e);
        }
    }, CART_WRITE_DELAY);
}

// Fallback: storage event for cross-tab updates if BroadcastChannel is not available
window.addEventListener('storage', (e) => {
    if (e.key === CART_KEY) {
        updateCartCount();
        const modal = document.getElementById('cart-modal');
        if (modal && !modal.classList.contains('hidden')) {
            renderCartItems();
        }
    }
});

// Generic safe accessors for other localStorage keys (non-debounced)
function getLocal(key, defaultValue = null) {
    try {
        const raw = localStorage.getItem(key);
        if (raw === null) return defaultValue;
        return JSON.parse(raw);
    } catch (e) {
        try { localStorage.removeItem(key); } catch (er) {}
        return defaultValue;
    }
}

function setLocal(key, value) {
    try {
        localStorage.setItem(key, JSON.stringify(value));
    } catch (e) {
        console.error('Failed to write to localStorage key', key, e);
    }
}

// Cart functions
function addToCart(productId, name, price, image, size = '', quantity = 1, event = null) {
    let cart = getCart();
    const existingItem = cart.find(item => item.id == productId);

    if (existingItem) {
        existingItem.quantity += parseInt(quantity);
    } else {
        cart.push({
            id: productId,
            name: name,
            price: price,
            image: image,
            size: size,
            quantity: parseInt(quantity)
        });
        // Store product data for favourites
        try {
            setLocal('product_' + productId, {
                id: productId,
                name: name,
                price: price,
                image: image,
                size: size
            });
        } catch (e) { /* ignore */ }
    }

    setCart(cart);

    // Trigger fly-to-cart animation
    if (event) {
        animateToCart(event, image);
    }

    // Update UI immediately using the in-memory cart (don't wait for debounced storage)
    updateCartCount(cart);
    showToast('Product added to cart!', 'success');
}

function updateCartCount(cart = null) {
    const theCart = cart === null ? getCart() : cart;
    const totalItems = theCart.reduce((sum, item) => sum + item.quantity, 0);
    const cartCountSpan = document.getElementById('cart-count');

    if (cartCountSpan) {
            const wasHidden = cartCountSpan.classList.contains('hidden');
            if (totalItems > 0) {
                cartCountSpan.textContent = totalItems;
                cartCountSpan.classList.remove('hidden');
                if (wasHidden) {
                    // Add bounce animation when count first appears
                    cartCountSpan.style.animation = 'none';
                    cartCountSpan.offsetHeight; // Trigger reflow
                    cartCountSpan.style.animation = 'bounce 0.5s ease';
                } else {
                    // small pulse when updating while visible
                    cartCountSpan.style.animation = 'none';
                    cartCountSpan.offsetHeight;
                    cartCountSpan.style.animation = 'bounce 0.35s ease';
                }
        } else {
            cartCountSpan.classList.add('hidden');
        }
    }
}

// Fly-to-cart animation function
function animateToCart(event, imageSrc) {
    // Get the cart button position
    const cartBtn = document.getElementById('floating-cart-btn');
    if (!cartBtn) return;

    const cartRect = cartBtn.getBoundingClientRect();
    const cartCenterX = cartRect.left + cartRect.width / 2;
    const cartCenterY = cartRect.top + cartRect.height / 2;

    // Get the starting position - from the Add to Cart button
    let startX, startY;
    if (event && event.target) {
        const targetRect = event.target.getBoundingClientRect();
        startX = targetRect.left + targetRect.width / 2;
        startY = targetRect.top + targetRect.height / 2;
    } else {
        // Fallback to center of screen
        startX = window.innerWidth / 2;
        startY = window.innerHeight / 2;
    }

    // Create flying element container for better mobile support
    const container = document.createElement('div');
    container.className = 'fly-to-cart-container';
    document.body.appendChild(container);

    // Create flying element
    const flyingElement = document.createElement('div');
    flyingElement.className = 'fly-to-cart';
    flyingElement.style.left = startX + 'px';
    flyingElement.style.top = startY + 'px';

    // Create the visual content (product image or cart icon)
    const content = document.createElement('div');
    content.className = 'w-16 h-16 rounded-full bg-gradient-to-r from-green-400 to-emerald-500 flex items-center justify-center shadow-xl border-3 border-white';

    if (imageSrc && imageSrc !== '') {
        // Use product image
        const img = document.createElement('img');
        img.src = '/assets/images/' + imageSrc;
        img.className = 'w-full h-full object-cover rounded-full';
        img.onerror = function() {
            // Fallback to cart icon if image fails to load
            content.innerHTML = '<i class="fas fa-shopping-cart text-white text-xl"></i>';
        };
        content.appendChild(img);
    } else {
        // Use cart icon
        content.innerHTML = '<i class="fas fa-shopping-cart text-white text-xl"></i>';
    }

    flyingElement.appendChild(content);
    container.appendChild(flyingElement);

    // Calculate the transform values (relative to the container)
    const deltaX = cartCenterX - startX;
    const deltaY = cartCenterY - startY;

    // Set CSS custom properties for animation
    flyingElement.style.setProperty('--translate-x', deltaX + 'px');
    flyingElement.style.setProperty('--translate-y', deltaY + 'px');

    // Add pulse animation to cart button AFTER the fly animation completes
    const animationDuration = window.innerWidth <= 768 ? 1400 : 650;
    setTimeout(() => {
        cartBtn.classList.add('cart-pulse');
        setTimeout(() => {
            cartBtn.classList.remove('cart-pulse');
        }, 600);
    }, animationDuration); // Wait for the fly animation to complete

    // Remove flying element and container after animation
    setTimeout(() => {
        if (container.parentNode) {
            container.parentNode.removeChild(container);
        }
    }, animationDuration + 200);
}

// Favourites functions
function toggleFavourite(productId, name, price, image, size = '', badge = '', category = '', rating = 0, reviews = 0, description = '', original_price = 0) {
    let favourites = getLocal('favourites', []);
    const btns = document.querySelectorAll('[id="fav-btn-' + productId + '"]');

    if (favourites.includes(productId)) {
        // Remove from favourites
    favourites = favourites.filter(id => id !== productId);
    setLocal('favourites', favourites);
        
        // Update icons to empty heart
        btns.forEach(btn => {
            const icon = btn.querySelector('i');
            if (icon) {
                // set to empty heart with consistent size
                icon.className = 'far fa-heart text-lg';
            }

            // If we're on favourites page and this button is inside a product card anchor, remove the card
            try {
                const card = btn.closest('a');
                if (card && document.getElementById('favourites-list') && card.parentElement) {
                    // Add fade-out classes to animate then remove after transition
                    // Use existing CSS classes: .fade-leave-active and .fade-leave-to
                    card.classList.add('fade-leave-active');
                    // Ensure reflow so the transition starts
                    // (reading offsetHeight forces layout)
                    void card.offsetHeight;
                    card.classList.add('fade-leave-to');

                    // Remove after transitionend or fallback timeout
                    const removeCard = () => {
                        try {
                            if (card.parentElement) card.parentElement.removeChild(card);
                        } catch (e) {}

                        // If no more cards, show the no-favourites message
                        const favouritesDiv = document.getElementById('favourites-list');
                        if (favouritesDiv && favouritesDiv.children.length === 0) {
                            const noFav = document.getElementById('no-favourites');
                            if (noFav) noFav.classList.remove('hidden');
                        }
                    };

                    let handled = false;
                    const onEnd = function(e) {
                        if (e.target === card && e.propertyName === 'opacity') {
                            handled = true;
                            card.removeEventListener('transitionend', onEnd);
                            removeCard();
                        }
                    };

                    card.addEventListener('transitionend', onEnd);
                    // Fallback in case transitionend doesn't fire
                    setTimeout(() => { if (!handled) removeCard(); }, 400);
                }
            } catch (e) {
                // ignore any DOM errors
            }
        });
        
        showToast('Product removed from favourites!', 'info');
    } else {
        // Add to favourites
    favourites.push(productId);
    setLocal('favourites', favourites);
        
        // Store complete product data for favourites page
        setLocal('product_' + productId, {
            id: productId,
            name: name,
            price: price,
            image: image,
            size: size,
            badge: badge,
            category: category,
            rating: rating,
            reviews: reviews,
            description: description,
            original_price: original_price
        });
        
        // Update icons to filled heart
        btns.forEach(btn => {
            const icon = btn.querySelector('i');
            if (icon) {
                // set to filled heart with consistent size
                icon.className = 'fas fa-heart text-lg';
            }
        });
        
        showToast('Product added to favourites!', 'success');
    }
}

function initFavourites() {
    let favourites = getLocal('favourites', []);
    favourites.forEach(id => {
        const btns = document.querySelectorAll('[id="fav-btn-' + id + '"]');
        btns.forEach(btn => {
            const icon = btn.querySelector('i');
            if (icon) {
                icon.className = 'fas fa-heart text-lg';
            }
        });
    });
}

// For checkout, after placing order, store in order history
function storeOrderHistory(orderId, cartItems, total) {
    let orderHistory = getLocal('orderHistory', []);
    const order = {
        id: orderId,
        date: new Date().toISOString(),
        items: cartItems,
        total: total
    };
    orderHistory.push(order);
    setLocal('orderHistory', orderHistory);
}

// Toast notification functions
function showToast(message, type = 'success', duration = 3000) {
    const toastContainer = document.getElementById('toast-container');
    if (!toastContainer) return;

    const toast = document.createElement('div');
    toast.className = `max-w-md w-full bg-white shadow-lg rounded-lg pointer-events-auto ring-1 ring-black ring-opacity-5 transform transition-all duration-300 ease-in-out translate-x-full opacity-0`;

    let iconClass = 'fas fa-check-circle';
    let bgColor = 'bg-green-50';
    let borderColor = 'border-green-200';
    let textColor = 'text-green-800';
    let iconColor = 'text-green-400';

    if (type === 'error') {
        iconClass = 'fas fa-exclamation-circle';
        bgColor = 'bg-red-50';
        borderColor = 'border-red-200';
        textColor = 'text-red-800';
        iconColor = 'text-red-400';
    } else if (type === 'warning') {
        iconClass = 'fas fa-exclamation-triangle';
        bgColor = 'bg-yellow-50';
        borderColor = 'border-yellow-200';
        textColor = 'text-yellow-800';
        iconColor = 'text-yellow-400';
    } else if (type === 'info') {
        iconClass = 'fas fa-info-circle';
        bgColor = 'bg-blue-50';
        borderColor = 'border-blue-200';
        textColor = 'text-blue-800';
        iconColor = 'text-blue-400';
    }

    toast.innerHTML = `
        <div class="p-4">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <i class="${iconClass} ${iconColor} text-lg"></i>
                </div>
                <div class="ml-3 flex-1 pt-0.5">
                    <p class="text-sm font-medium ${textColor} leading-relaxed break-words">
                        ${message}
                    </p>
                </div>
                <div class="ml-4 flex-shrink-0 flex">
                    <button onclick="dismissToast(this.parentElement.parentElement.parentElement.parentElement)" class="inline-flex rounded-md ${bgColor} p-1.5 ${textColor} hover:${bgColor} focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-${type === 'success' ? 'green' : type === 'error' ? 'red' : type === 'warning' ? 'yellow' : 'blue'}-500 transition-colors">
                        <span class="sr-only">Dismiss</span>
                        <i class="fas fa-times text-sm"></i>
                    </button>
                </div>
            </div>
        </div>
    `;

    toastContainer.appendChild(toast);

    // Trigger animation
    setTimeout(() => {
        toast.classList.remove('translate-x-full', 'opacity-0');
        toast.classList.add('translate-x-0', 'opacity-100');
    }, 10);

    // Auto dismiss
    if (duration > 0) {
        setTimeout(() => {
            dismissToast(toast);
        }, duration);
    }
}

function dismissToast(toast) {
    toast.classList.remove('translate-x-0', 'opacity-100');
    toast.classList.add('translate-x-full', 'opacity-0');
    setTimeout(() => {
        if (toast.parentElement) {
            toast.parentElement.removeChild(toast);
        }
    }, 300);
}

// Mobile menu toggle
function initMobileMenu() {
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const mobileMenu = document.getElementById('mobile-menu');
    
    if (mobileMenuBtn && mobileMenu) {
        mobileMenuBtn.addEventListener('click', function() {
            mobileMenu.classList.toggle('hidden');
        });
    }
    
    // Close mobile menu when clicking outside
    document.addEventListener('click', function(event) {
        if (mobileMenuBtn && mobileMenu && !mobileMenuBtn.contains(event.target) && !mobileMenu.contains(event.target)) {
            mobileMenu.classList.add('hidden');
        }
    });
}

// Initialize mobile menu when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    initMobileMenu();
    updateCartCount(); // Initialize cart count on page load
});

// Cart Modal Functions
function openCartModal() {
    const modal = document.getElementById('cart-modal');
    if (modal) {
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden'; // Prevent background scrolling
        renderCartItems();
    }
}

function closeCartModal() {
    const modal = document.getElementById('cart-modal');
    if (modal) {
        modal.classList.add('hidden');
        document.body.style.overflow = ''; // Restore scrolling
    }
}

function renderCartItems(cart = null) {
    const cartItemsContainer = document.getElementById('cart-items');
    const emptyCartDiv = document.getElementById('empty-cart');
    const cartFooter = document.getElementById('cart-footer');
    const cartTotalSpan = document.getElementById('cart-total');

    if (!cartItemsContainer || !emptyCartDiv || !cartFooter || !cartTotalSpan) return;

    const theCart = cart === null ? getCart() : cart;

    if (theCart.length === 0) {
        cartItemsContainer.innerHTML = '';
        emptyCartDiv.classList.remove('hidden');
        cartFooter.classList.add('hidden');
        return;
    }

    emptyCartDiv.classList.add('hidden');
    cartFooter.classList.remove('hidden');

    let total = 0;
    cartItemsContainer.innerHTML = theCart.map(item => {
        const itemTotal = item.price * item.quantity;
        total += itemTotal;

        return `
            <div class="cart-item p-4 border border-gray-200 rounded-lg">
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
                                    <button class="quantity-btn" onclick="updateCartQuantity(${item.id}, ${item.quantity - 1})">
                                        <i class="fas fa-minus text-sm"></i>
                                    </button>
                                    <input type="number" value="${item.quantity}" min="1" class="quantity-input" onchange="updateCartQuantity(${item.id}, this.value)">
                                    <button class="quantity-btn" onclick="updateCartQuantity(${item.id}, ${item.quantity + 1})">
                                        <i class="fas fa-plus text-sm"></i>
                                    </button>
                                </div>
                                <button class="remove-btn p-2" onclick="removeFromCart(${item.id})">
                                    <i class="fas fa-trash text-lg"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Desktop Layout -->
                <div class="hidden md:flex items-center space-x-4">
                    <img src="/assets/images/${item.image}" alt="${item.name}" class="cart-item-image flex-shrink-0" onerror="this.src='/assets/images/placeholder.jpg'">
                    <div class="flex-1 min-w-0">
                        <h4 class="font-semibold text-gray-900 truncate">${item.name}</h4>
                        ${item.size ? `<p class="text-sm text-gray-600 mt-1">Size: ${item.size}</p>` : ''}
                    </div>
                    <div class="quantity-controls">
                        <button class="quantity-btn" onclick="updateCartQuantity(${item.id}, ${item.quantity - 1})">
                            <i class="fas fa-minus text-sm"></i>
                        </button>
                        <input type="number" value="${item.quantity}" min="1" class="quantity-input" onchange="updateCartQuantity(${item.id}, this.value)">
                        <button class="quantity-btn" onclick="updateCartQuantity(${item.id}, ${item.quantity + 1})">
                            <i class="fas fa-plus text-sm"></i>
                        </button>
                    </div>
                    <div class="flex flex-col items-end space-y-1">
                        <button class="remove-btn p-2" onclick="removeFromCart(${item.id})">
                            <i class="fas fa-trash text-lg"></i>
                        </button>
                        <p class="text-lg font-bold text-green-600">৳${item.price}</p>
                    </div>
                </div>
            </div>
        `;
    }).join('');

    cartTotalSpan.textContent = `৳${total}`;
}

function updateCartQuantity(productId, newQuantity) {
    newQuantity = parseInt(newQuantity);
    if (newQuantity < 1) {
        removeFromCart(productId);
        return;
    }

    let cart = getCart();
    const itemIndex = cart.findIndex(item => item.id == productId);

    if (itemIndex !== -1) {
        cart[itemIndex].quantity = newQuantity;
        setCart(cart);
        // Update UI immediately with the in-memory cart
        updateCartCount(cart);
        renderCartItems(cart);
        showToast('Cart updated!', 'success');
    }
}

function removeFromCart(productId) {
    let cart = getCart();
    cart = cart.filter(item => item.id != productId);
    setCart(cart);
    // Update UI immediately with the in-memory cart
    updateCartCount(cart);
    renderCartItems(cart);
    showToast('Item removed from cart!', 'info');
}

// Close modal when clicking outside
document.addEventListener('click', function(event) {
    const modal = document.getElementById('cart-modal');
    const modalContent = modal ? modal.querySelector('.bg-white') : null;
    const floatingCartBtn = document.getElementById('floating-cart-btn');

    // Don't close if clicking on the floating cart button
    if (floatingCartBtn && floatingCartBtn.contains(event.target)) {
        return;
    }

    if (modal && !modal.classList.contains('hidden') && modalContent && !modalContent.contains(event.target)) {
        closeCartModal();
    }
});

// Close modal on Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeCartModal();
    }
});