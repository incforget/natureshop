<?php
include_once 'includes/config.php';
include_once 'includes/functions.php';

// We'll set page title to product name for better SEO
$page_title = isset($product['name']) ? $product['name'] : 'Product Details';

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$product = getProductById($product_id);

if (!$product) {
    header('Location: products.php');
    exit;
}

include 'includes/header.php';
?>
<script>var currentProduct = <?php echo json_encode($product); ?>;</script>

    <div class="container mx-auto px-4 py-6" role="main">
        <!-- Breadcrumb -->
        <nav class="flex mb-6" aria-label="Breadcrumb navigation">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li class="inline-flex items-center">
                    <a href="index.php" class="text-gray-700 hover:text-green-600 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 rounded-md px-2 py-1 transition-colors" aria-label="Go to home page">Home</a>
                </li>
                <li>
                    <div class="flex items-center">
                        <i class="fas fa-chevron-right text-gray-400 mx-2" aria-hidden="true"></i>
                        <a href="products.php" class="text-gray-700 hover:text-green-600 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 rounded-md px-2 py-1 transition-colors" aria-label="Go to products page">Products</a>
                    </div>
                </li>
                <li aria-current="page">
                    <div class="flex items-center">
                        <i class="fas fa-chevron-right text-gray-400 mx-2" aria-hidden="true"></i>
                        <span class="text-gray-500" aria-label="Current page"><?php echo htmlspecialchars($product['name']); ?></span>
                    </div>
                </li>
            </ol>
        </nav>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Product Image -->
            <div class="space-y-4">
                <div class="relative group">
                    <div class="relative overflow-hidden rounded-2xl shadow-xl bg-white p-2 transform transition-all duration-300 hover:shadow-2xl hover:scale-[1.01] max-w-lg mx-auto">
                        <img src="assets/images/<?php echo $product['image']; ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>" 
                             class="w-full aspect-square rounded-xl object-cover cursor-zoom-in transition-transform duration-500 group-hover:scale-105"
                             id="main-product-image"
                             loading="lazy">
                        <!-- Zoom overlay -->
                        <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-10 transition-all duration-300 rounded-xl flex items-center justify-center">
                            <div class="opacity-0 group-hover:opacity-100 transition-opacity duration-300 transform translate-y-2 group-hover:translate-y-0">
                                <i class="fas fa-search-plus text-white text-xl drop-shadow-lg"></i>
                            </div>
                        </div>
                        <?php if($product['badge']): ?>
                        <div class="absolute top-6 left-6 bg-gradient-to-r from-green-500 to-emerald-600 text-white px-3 py-1 rounded-full text-sm font-bold shadow-lg transform group-hover:scale-105 transition-transform duration-300">
                            <?php echo $product['badge']; ?>
                        </div>
                        <?php endif; ?>
                        <?php echo renderFavouriteButton($product, 'absolute top-6 right-6'); ?>
                    </div>
                </div>
                
                <!-- Image Gallery Thumbnails (if multiple images exist) -->
                <div class="flex space-x-3 overflow-x-auto pb-2 justify-center">
                    <div class="flex-shrink-0 w-28 h-28 rounded-lg overflow-hidden border-2 border-green-200 cursor-pointer hover:border-green-400 transition-colors duration-300">
                        <img src="assets/images/<?php echo $product['image']; ?>" alt="Product view 1" class="w-full h-full object-cover">
                    </div>
                    <!-- Add more thumbnails if product has multiple images -->
                </div>
            </div>

            <!-- Product Details -->
            <div class="space-y-6">
                <!-- Header Section -->
                <div class="space-y-3">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <span class="inline-flex items-center px-3 py-1 bg-gradient-to-r from-green-50 to-emerald-50 text-green-700 border border-green-200 rounded-full text-sm font-semibold shadow-sm">
                            <i class="fas fa-tag mr-2"></i><?php echo ucfirst($product['category']); ?>
                        </span>
                        <div class="flex items-center bg-yellow-50 px-3 py-1 rounded-full border border-yellow-200">
                            <div class="flex text-yellow-400 mr-2">
                                <?php for($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star text-sm <?php echo $i <= $product['rating'] ? 'text-yellow-400' : 'text-gray-300'; ?>"></i>
                                <?php endfor; ?>
                            </div>
                            <span class="text-sm text-gray-700 font-medium"><?php echo $product['rating']; ?> (<?php echo $product['reviews']; ?> reviews)</span>
                        </div>
                    </div>
                    
                    <div class="space-y-2">
                        <h1 class="text-3xl lg:text-4xl font-bold text-gray-900 leading-tight tracking-tight"><?php echo $product['name']; ?></h1>
                        <p class="text-base text-gray-600 leading-relaxed max-w-2xl"><?php echo $product['description']; ?></p>
                    </div>
                </div>

                <!-- Price Section -->
                <div class="bg-gradient-to-r from-gray-50 to-green-50 p-4 rounded-xl border border-gray-100">
                    <div class="flex flex-wrap items-center gap-3 mb-3">
                        <span class="text-4xl font-bold text-green-600 tracking-tight">৳<?php echo number_format($product['price'], 0); ?></span>
                        <?php if($product['original_price']): ?>
                        <div class="flex flex-col">
                            <span class="text-xl text-gray-500 line-through">৳<?php echo number_format($product['original_price'], 0); ?></span>
                            <span class="bg-red-100 text-red-700 px-2 py-1 rounded-full text-sm font-bold border border-red-200">
                                Save ৳<?php echo number_format($product['original_price'] - $product['price'], 0); ?> (<?php echo round((1 - $product['price'] / $product['original_price']) * 100); ?>%)
                            </span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Stock and Size Info -->
                    <div class="flex flex-wrap items-center gap-4 text-sm">
                        <div class="flex items-center <?php echo $product['stock'] > 0 ? 'text-emerald-600' : 'text-red-600'; ?>">
                            <i class="fas <?php echo $product['stock'] > 0 ? 'fa-check-circle' : 'fa-times-circle'; ?> mr-2"></i>
                            <span class="font-semibold"><?php echo $product['stock'] > 0 ? 'In Stock' : 'Out of Stock'; ?></span>
                        </div>
                        <span class="text-gray-600 font-medium"><?php echo $product['stock']; ?> available</span>
                        <?php if(!empty($product['size'])): ?>
                        <div class="flex items-center text-blue-600">
                            <i class="fas fa-weight-hanging mr-2"></i>
                            <span class="font-semibold">Size: <?php echo $product['size']; ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Features -->
                <?php if(!empty($product['features'])): ?>
                <div class="bg-white p-4 rounded-xl shadow-lg border border-gray-100">
                    <h3 class="text-lg font-bold text-gray-900 mb-3 flex items-center">
                        <i class="fas fa-check-circle text-green-500 mr-2"></i>
                        Key Features
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                        <?php foreach($product['features'] as $feature): ?>
                        <div class="flex items-center p-2 bg-green-50 rounded-lg border border-green-100 hover:bg-green-100 transition-colors duration-200">
                            <i class="fas fa-check text-green-500 mr-2 flex-shrink-0"></i>
                            <span class="text-gray-700 font-medium text-sm"><?php echo $feature; ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Quantity Selector -->
                <div class="flex items-center space-x-3">
                    <label for="quantity" class="text-gray-700 font-medium text-sm">Quantity:</label>
                    <div class="flex items-center border-2 border-gray-200 rounded-lg overflow-hidden" role="group" aria-label="Quantity selector">
                        <button onclick="changeQuantity(-1)" 
                                class="px-3 py-2 bg-gray-50 hover:bg-gray-100 text-gray-600 hover:text-gray-800 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-green-500 focus:bg-green-50" 
                                aria-label="Decrease quantity">
                            <i class="fas fa-minus text-xs" aria-hidden="true"></i>
                        </button>
                        <input type="number" id="quantity" value="1" min="1" max="<?php echo $product['stock']; ?>" 
                               class="w-12 text-center py-2 border-0 focus:outline-none focus:ring-0 text-gray-800 font-medium text-sm" 
                               readonly 
                               aria-label="Current quantity">
                        <button onclick="changeQuantity(1)" 
                                class="px-3 py-2 bg-gray-50 hover:bg-gray-100 text-gray-600 hover:text-gray-800 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-green-500 focus:bg-green-50" 
                                aria-label="Increase quantity">
                            <i class="fas fa-plus text-xs" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="space-y-3">
                    <button onclick="buyNow(<?php echo $product['id']; ?>, '<?php echo addslashes($product['name']); ?>', <?php echo $product['price']; ?>, '<?php echo $product['image']; ?>', '<?php echo addslashes($product['size'] ?? ''); ?>', document.getElementById('quantity').value)" 
                            class="w-full bg-gradient-to-r from-blue-500 to-indigo-600 text-white px-6 py-3 rounded-xl hover:from-blue-600 hover:to-indigo-700 transition-all duration-300 flex items-center justify-center font-bold text-base shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 hover:scale-105 focus:outline-none focus:ring-4 focus:ring-blue-300 group"
                            aria-label="Buy <?php echo htmlspecialchars($product['name']); ?> now">
                        <i class="fas fa-bolt mr-2 group-hover:animate-pulse" aria-hidden="true"></i>
                        Buy Now
                    </button>
                    
                    <button onclick="addToCart(<?php echo $product['id']; ?>, '<?php echo addslashes($product['name']); ?>', <?php echo $product['price']; ?>, '<?php echo $product['image']; ?>', '<?php echo addslashes($product['size'] ?? ''); ?>', document.getElementById('quantity').value, event)" 
                            class="w-full bg-gradient-to-r from-green-500 to-emerald-600 text-white px-6 py-3 rounded-xl hover:from-green-600 hover:to-emerald-700 transition-all duration-300 flex items-center justify-center font-bold text-base shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 hover:scale-105 focus:outline-none focus:ring-4 focus:ring-green-300 group <?php echo $product['stock'] <= 0 ? 'opacity-50 cursor-not-allowed' : ''; ?>" 
                            <?php echo $product['stock'] <= 0 ? 'disabled' : ''; ?>
                            aria-label="<?php echo $product['stock'] > 0 ? 'Add ' . htmlspecialchars($product['name']) . ' to cart' : 'Product out of stock'; ?>">
                        <i class="fas fa-cart-plus mr-2 group-hover:animate-bounce" aria-hidden="true"></i>
                        <?php echo $product['stock'] > 0 ? 'Add to Cart' : 'Out of Stock'; ?>
                    </button>
                </div>
            </div>
        </div>

        <!-- Related Products Section -->
        <div class="mt-12">
            <div class="text-center mb-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-2 tracking-tight">You Might Also Like</h2>
                <p class="text-sm text-gray-600 max-w-2xl mx-auto">Discover more premium natural products from our collection</p>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6">
                <?php
                // Get related products from same category
                $relatedProducts = getProducts(4, $product['category']);
                $relatedProducts = array_filter($relatedProducts, function($p) use ($product) {
                    return $p['id'] != $product['id'];
                });
                foreach(array_slice($relatedProducts, 0, 4) as $related):
                ?>
                <a href="product.php?id=<?php echo $related['id']; ?>" class="group block bg-white rounded-3xl shadow-xl hover:shadow-2xl transition-all duration-500 transform hover:-translate-y-4 border border-gray-200 hover:border-green-400 hover:ring-4 hover:ring-green-200/50 overflow-hidden relative">
                    <div class="absolute inset-0 bg-gradient-to-br from-green-50/0 via-emerald-50/0 to-teal-50/0 group-hover:from-green-50/20 group-hover:via-emerald-50/10 group-hover:to-teal-50/20 transition-all duration-500 rounded-3xl"></div>
                    <div class="relative z-10">
                        <div class="relative overflow-hidden aspect-[4/3]">
                            <img src="assets/images/<?php echo $related['image']; ?>" alt="<?php echo htmlspecialchars($related['name']); ?>" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700" loading="lazy">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/20 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                            <?php if($related['badge']): ?>
                            <div class="absolute top-3 left-3 bg-gradient-to-r from-green-500 to-emerald-600 text-white px-4 py-2 rounded-full text-sm font-bold shadow-xl transform group-hover:scale-105 transition-transform duration-300">
                                <?php echo $related['badge']; ?>
                            </div>
                            <?php endif; ?>
                            <?php echo renderFavouriteButton($related, 'absolute top-3 right-3'); ?>
                            <?php if($related['original_price'] && $related['original_price'] > $related['price']): ?>
                            <div class="absolute bottom-3 left-3 bg-red-500 text-white px-3 py-1 rounded-full text-xs font-bold shadow-lg">
                                -<?php echo round((1 - $related['price'] / $related['original_price']) * 100); ?>%
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="p-4">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-sm text-gray-600 bg-gradient-to-r from-gray-100 to-gray-200 px-4 py-2 rounded-full font-semibold shadow-sm"><?php echo ucfirst($related['category']); ?></span>
                                <div class="flex items-center bg-yellow-50 px-2 py-1 rounded-full">
                                    <div class="flex text-yellow-400 mr-1">
                                        <?php for($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star text-xs <?php echo $i <= $related['rating'] ? 'text-yellow-400' : 'text-gray-300'; ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <span class="text-xs text-gray-700 font-medium">(<?php echo $related['reviews']; ?>)</span>
                                </div>
                            </div>
                            <h3 class="font-bold text-lg mb-2 text-gray-800 group-hover:text-green-700 transition-colors duration-300 leading-tight line-clamp-2">
                                <?php echo $related['name']; ?>
                            </h3>
                            <p class="text-gray-600 text-sm mb-4 line-clamp-2 leading-relaxed"><?php echo substr($related['description'], 0, 70); ?>...</p>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <div class="flex flex-col">
                                        <span class="text-green-600 font-bold text-2xl">৳<?php echo number_format($related['price'], 0); ?></span>
                                        <?php if($related['original_price']): ?>
                                        <span class="text-gray-500 line-through text-sm">৳<?php echo number_format($related['original_price'], 0); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if(isset($related['size']) && $related['size']): ?>
                                    <span class="text-xs text-blue-600 bg-blue-50 px-2 py-1 rounded-full font-medium">Size: <?php echo $related['size']; ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="flex items-center text-green-600 font-semibold text-sm group-hover:text-green-700 transition-colors duration-300">
                                    <span>View</span>
                                    <i class="fas fa-arrow-right ml-1 transform group-hover:translate-x-1 transition-transform duration-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Recently Viewed Products Section -->
        <div class="mt-12" id="recently-viewed-section" style="display: none;">
            <div class="text-center mb-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-2 tracking-tight">Recently Viewed</h2>
                <p class="text-sm text-gray-600 max-w-2xl mx-auto">Products you've recently looked at</p>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6" id="recently-viewed-grid">
                <!-- Recently viewed products will be populated here by JavaScript -->
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="js/app.js"></script>
    <script>
        // Recently viewed functions
        function getRecentlyViewed() {
            if (typeof getLocal === 'function') return getLocal('recentlyViewed', []);
            try {
                const stored = localStorage.getItem('recentlyViewed');
                return stored ? JSON.parse(stored) : [];
            } catch (e) {
                return [];
            }
        }

        function saveRecentlyViewed(products) {
            if (typeof setLocal === 'function') return setLocal('recentlyViewed', products);
            try {
                localStorage.setItem('recentlyViewed', JSON.stringify(products));
            } catch (e) {
                // ignore
            }
        }

        function createProductCard(product) {
            const card = document.createElement('a');
            card.href = `product.php?id=${product.id}`;
            card.className = 'group block bg-white rounded-3xl shadow-xl hover:shadow-2xl transition-all duration-500 transform hover:-translate-y-4 border border-gray-200 hover:border-green-400 hover:ring-4 hover:ring-green-200/50 overflow-hidden relative';
            card.innerHTML = `
                <div class="absolute inset-0 bg-gradient-to-br from-green-50/0 via-emerald-50/0 to-teal-50/0 group-hover:from-green-50/20 group-hover:via-emerald-50/10 group-hover:to-teal-50/20 transition-all duration-500 rounded-3xl"></div>
                <div class="relative z-10">
                    <div class="relative overflow-hidden aspect-[4/3]">
                        <img src="assets/images/${product.image}" alt="${product.name.replace(/"/g, '&quot;')}" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700" loading="lazy">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/20 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                        ${product.badge ? `<div class="absolute top-3 left-3 bg-gradient-to-r from-green-500 to-emerald-600 text-white px-4 py-2 rounded-full text-sm font-bold shadow-xl transform group-hover:scale-105 transition-transform duration-300">${product.badge}</div>` : ''}
                        <button onclick="event.preventDefault(); event.stopPropagation(); toggleFavourite(${product.id}, '${product.name.replace(/'/g, "\\'").replace(/"/g, '&quot;')}', ${product.price}, '${product.image}', '${product.size || ''}', '${product.badge || ''}', '${product.category || ''}', ${product.rating || 0}, ${product.reviews || 0}, '${(product.description || '').substring(0, 200).replace(/'/g, "\\'").replace(/"/g, '&quot;')}', ${product.original_price || 0});" id="fav-btn-${product.id}" class="absolute top-3 right-3 w-6 h-6 bg-transparent bg-opacity-01 rounded-full flex items-center justify-center text-red-500 hover:text-red-600 hover:bg-opacity-100 transition-all duration-300 shadow-lg">
                            <i class="far fa-heart text-lg"></i>
                        </button>
                        ${product.original_price && product.original_price > product.price ? `<div class="absolute bottom-3 left-3 bg-red-500 text-white px-3 py-1 rounded-full text-xs font-bold shadow-lg">-${Math.round((1 - product.price / product.original_price) * 100)}%</div>` : ''}
                    </div>
                    <div class="p-4">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-sm text-gray-600 bg-gradient-to-r from-gray-100 to-gray-200 px-4 py-2 rounded-full font-semibold shadow-sm">${product.category.charAt(0).toUpperCase() + product.category.slice(1)}</span>
                            <div class="flex items-center bg-yellow-50 px-2 py-1 rounded-full">
                                <div class="flex text-yellow-400 mr-1">
                                    ${Array.from({length: 5}, (_, i) => `<i class="fas fa-star text-xs ${i < product.rating ? 'text-yellow-400' : 'text-gray-300'}"></i>`).join('')}
                                </div>
                                <span class="text-xs text-gray-700 font-medium">(${product.reviews})</span>
                            </div>
                        </div>
                        <h3 class="font-bold text-lg mb-2 text-gray-800 group-hover:text-green-700 transition-colors duration-300 leading-tight line-clamp-2">
                            ${product.name}
                        </h3>
                        <p class="text-gray-600 text-sm mb-4 line-clamp-2 leading-relaxed">${product.description ? product.description.substring(0, 70) + '...' : ''}</p>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div class="flex flex-col">
                                    <span class="text-green-600 font-bold text-2xl">৳${product.price.toLocaleString()}</span>
                                    ${product.original_price ? `<span class="text-gray-500 line-through text-sm">৳${product.original_price.toLocaleString()}</span>` : ''}
                                </div>
                                ${product.size ? `<span class="text-xs text-blue-600 bg-blue-50 px-2 py-1 rounded-full font-medium">Size: ${product.size}</span>` : ''}
                            </div>
                            <div class="flex items-center text-green-600 font-semibold text-sm group-hover:text-green-700 transition-colors duration-300">
                                <span>View</span>
                                <i class="fas fa-arrow-right ml-1 transform group-hover:translate-x-1 transition-transform duration-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            return card;
        }

        document.addEventListener('DOMContentLoaded', function() {
            updateCartCount();
            initFavourites();

            // Quantity selector functionality
            window.changeQuantity = function(delta) {
                const quantityInput = document.getElementById('quantity');
                const currentValue = parseInt(quantityInput.value);
                const maxStock = parseInt(quantityInput.max);
                const newValue = Math.max(1, Math.min(maxStock, currentValue + delta));
                quantityInput.value = newValue;
                
                // Add bounce animation
                quantityInput.classList.add('animate-pulse');
                setTimeout(() => quantityInput.classList.remove('animate-pulse'), 200);
            };

            // Image zoom functionality
            const mainImage = document.getElementById('main-product-image');
            if (mainImage) {
                mainImage.addEventListener('click', function() {
                    // Create modal for zoomed image
                    const modal = document.createElement('div');
                    modal.className = 'fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 p-4';
                    modal.innerHTML = `
                        <div class="relative max-w-4xl max-h-full">
                            <img src="${this.src}" alt="${this.alt}" class="max-w-full max-h-full object-contain rounded-2xl">
                            <button onclick="this.parentElement.parentElement.remove()" class="absolute -top-4 -right-4 w-12 h-12 bg-white rounded-full flex items-center justify-center text-gray-800 hover:bg-gray-100 transition-colors shadow-xl">
                                <i class="fas fa-times text-xl"></i>
                            </button>
                        </div>
                    `;
                    document.body.appendChild(modal);
                    
                    // Close modal on background click
                    modal.addEventListener('click', function(e) {
                        if (e.target === modal) {
                            modal.remove();
                        }
                    });
                });
            }

            // Buy now functionality
            window.buyNow = function(id, name, price, image, size, quantity) {
                // Add to cart first
                addToCart(id, name, price, image, size, quantity, null);
                // Redirect to checkout (you might need to implement this)
                setTimeout(() => {
                    window.location.href = 'checkout.php'; // Assuming you have a checkout page
                }, 500);
            };

            // Enhanced button animations
            const buttons = document.querySelectorAll('button');
            buttons.forEach(button => {
                button.addEventListener('mousedown', function() {
                    this.style.transform = 'scale(0.98)';
                });
                
                button.addEventListener('mouseup', function() {
                    this.style.transform = '';
                });
                
                button.addEventListener('mouseleave', function() {
                    this.style.transform = '';
                });
            });

            // Smooth scroll for anchor links
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

            // Recently viewed logic
            if (typeof currentProduct !== 'undefined' && currentProduct && currentProduct.id) {
                let recent = getRecentlyViewed();
                // Remove if already exists
                recent = recent.filter(p => p.id != currentProduct.id);
                // Add to beginning
                recent.unshift({
                    id: currentProduct.id,
                    name: currentProduct.name,
                    price: currentProduct.price,
                    image: currentProduct.image,
                    size: currentProduct.size || '',
                    category: currentProduct.category,
                    rating: currentProduct.rating,
                    reviews: currentProduct.reviews,
                    badge: currentProduct.badge || '',
                    original_price: currentProduct.original_price || null,
                    description: currentProduct.description || ''
                });
                // Limit to 5
                recent = recent.slice(0, 5);
                saveRecentlyViewed(recent);
            }

            // Populate recently viewed
            const recentProducts = getRecentlyViewed().filter(p => p.id != (currentProduct ? currentProduct.id : 0));
            if (recentProducts.length > 0) {
                const grid = document.getElementById('recently-viewed-grid');
                recentProducts.forEach(product => {
                    const card = createProductCard(product);
                    grid.appendChild(card);
                });
                document.getElementById('recently-viewed-section').style.display = 'block';
                initFavourites(); // Update favourite buttons for newly added cards
            }
        });
    </script>
</body>
</html>