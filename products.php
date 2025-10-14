<?php
include_once 'includes/config.php';
include_once 'includes/functions.php';

$page_title = "All Products";

$category_slug = isset($_GET['category']) ? $_GET['category'] : null;
$search = isset($_GET['search']) ? $_GET['search'] : '';
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

$products = getProducts(12, $category_slug, $search, $offset);
$categories = getCategories();
$total_products = getProductsCount($category_slug, $search);
$has_more = ($offset + count($products)) < $total_products;

// Handle AJAX requests
if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
    $title = $category_slug ? ucfirst(str_replace('-', ' ', $category_slug)) : 'All Products';
    if($search) {
        $title .= ' - Search: "' . htmlspecialchars($search) . '"';
    }
    $count = count($products);
    $total = getProductsCount($category_slug, $search);
    $has_more = ($offset + $count) < $total;
    
    ob_start();
    ?>
    
        <?php foreach($products as $product): ?>
        <a href="product.php?id=<?php echo $product['id']; ?>" class="group block bg-white/90 backdrop-blur-sm rounded-3xl shadow-lg hover:shadow-2xl transition-all duration-500 transform hover:-translate-y-2 border border-gray-100 hover:border-green-300 hover:ring-2 hover:ring-green-200/50 overflow-hidden relative">
            <div class="absolute inset-0 bg-gradient-to-br from-green-50/0 via-emerald-50/0 to-teal-50/0 group-hover:from-green-50/30 group-hover:via-emerald-50/20 group-hover:to-teal-50/30 transition-all duration-500 rounded-3xl"></div>
            <div class="relative z-10">
                <div class="relative overflow-hidden rounded-t-3xl aspect-[4/3]">
                    <img src="assets/images/<?php echo $product['image']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700" loading="lazy">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/10 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                    <?php if($product['badge']): ?>
                    <div class="absolute top-4 left-4 bg-gradient-to-r from-green-500 to-emerald-600 text-white px-4 py-2 rounded-full text-sm font-bold shadow-xl transform group-hover:scale-105 transition-transform duration-300">
                        <?php echo $product['badge']; ?>
                    </div>
                    <?php endif; ?>
                    <?php echo renderFavouriteButton($product, 'absolute top-4 right-4'); ?>
                    <?php if($product['original_price'] && $product['original_price'] > $product['price']): ?>
                    <div class="absolute bottom-4 left-4 bg-red-500 text-white px-3 py-1 rounded-full text-xs font-bold shadow-lg">
                        -<?php echo round((1 - $product['price'] / $product['original_price']) * 100); ?>%
                    </div>
                    <?php endif; ?>
                </div>
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-sm text-gray-600 bg-gradient-to-r from-gray-100 to-gray-200 px-3 py-1 rounded-full font-semibold shadow-sm"><?php echo ucfirst($product['category_name'] ?: str_replace('-', ' ', $product['category'])); ?></span>
                        <div class="flex items-center bg-yellow-50 px-3 py-1 rounded-full shadow-sm">
                            <div class="flex text-yellow-400 mr-1">
                                <?php for($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star text-xs <?php echo $i <= $product['rating'] ? 'text-yellow-400' : 'text-gray-300'; ?>"></i>
                                <?php endfor; ?>
                            </div>
                            <span class="text-xs text-gray-700 font-medium">(<?php echo $product['reviews']; ?>)</span>
                        </div>
                    </div>
                    <h3 class="font-bold text-lg mb-3 text-gray-800 group-hover:text-green-700 transition-colors duration-300 leading-tight line-clamp-2">
                        <?php echo htmlspecialchars($product['name']); ?>
                    </h3>
                    <p class="text-gray-600 text-sm mb-4 line-clamp-2 leading-relaxed"><?php echo htmlspecialchars(substr($product['description'], 0, 80)); ?>...</p>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <div class="flex flex-col">
                                <span class="text-green-600 font-bold text-2xl">৳<?php echo number_format($product['price'], 0); ?></span>
                                <?php if($product['original_price']): ?>
                                <span class="text-gray-500 line-through text-sm">৳<?php echo number_format($product['original_price'], 0); ?></span>
                                <?php endif; ?>
                            </div>
                            <?php if(isset($product['size']) && $product['size']): ?>
                            <span class="text-xs text-blue-600 bg-blue-50 px-3 py-1 rounded-full font-medium shadow-sm">Size: <?php echo $product['size']; ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="flex items-center text-green-600 font-semibold text-sm group-hover:text-green-700 transition-colors duration-300">
                            <span>View Details</span>
                            <i class="fas fa-arrow-right ml-2 transform group-hover:translate-x-1 transition-transform duration-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </a>
        <?php endforeach; ?>
    
    <?php
    $html = ob_get_clean();
    
    header('Content-Type: application/json');
    echo json_encode([
        'title' => $title,
        'count' => $total,
        'html' => $html,
        'has_more' => $has_more
    ]);
    exit;
}

include 'includes/header.php';
?>

    <div class="min-h-screen bg-gradient-to-br from-green-50 via-white to-emerald-50">
        <div class="container mx-auto px-4 py-8">
            <div class="flex flex-col md:flex-row gap-8">
                <!-- Sidebar Filters -->
                <div class="w-full md:w-1/4">
                    <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-lg border border-green-100 p-6 md:sticky md:top-24">
                        <h3 class="text-xl font-bold mb-6 text-gray-800 text-center bg-gradient-to-r from-green-600 to-emerald-600 bg-clip-text text-transparent sticky top-0 bg-white/80 backdrop-blur-sm -mx-6 -mt-6 px-6 pt-6 pb-4 rounded-t-2xl border-b border-green-100">Categories</h3>
                        <div class="overflow-y-auto md:max-h-[calc(100vh-12rem)] pb-4" style="scrollbar-width: none; -ms-overflow-style: none;">
                            <ul class="sidebar-categories flex flex-row md:flex-col space-x-3 md:space-x-0 md:space-y-2">
                            <li>
                                <a href="products.php<?php echo $search ? '?search='.urlencode($search) : ''; ?>" class="flex flex-col md:flex-row md:items-center items-center p-4 rounded-xl <?php echo !$category_slug ? 'bg-gradient-to-r from-green-100 to-emerald-100 text-green-700 font-semibold shadow-md' : 'hover:bg-gray-50 text-gray-700 hover:shadow-sm'; ?> transition-all duration-300 group">
                                    <i class="fas fa-th-large mb-2 md:mb-0 md:mr-3 text-green-500 group-hover:scale-110 transition-transform"></i>
                                    <span class="text-center md:text-left md:ml-3 whitespace-nowrap font-medium">All Products</span>
                                </a>
                            </li>
                            <?php foreach($categories as $cat):
                                if($cat['slug'] === 'all') continue;
                            ?>
                            <li>
                                <a href="products.php?category=<?php echo $cat['slug']; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>" class="flex flex-col md:flex-row md:items-center items-center p-4 rounded-xl <?php echo $category_slug == $cat['slug'] ? 'bg-gradient-to-r from-green-100 to-emerald-100 text-green-700 font-semibold shadow-md' : 'hover:bg-gray-50 text-gray-700 hover:shadow-sm'; ?> transition-all duration-300 group">
                                    <?php echo getCategoryIcon($cat); ?>
                                    <span class="text-center md:text-left md:ml-3 whitespace-nowrap mt-2 md:mt-0 font-medium"><?php echo $cat['name']; ?> <span class="text-xs text-gray-500">(<?php echo $cat['count']; ?>)</span></span>
                                </a>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        </div>
                    </div>
                </div>

                <!-- Products Grid -->
                <div class="w-full md:w-3/4">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
                        <h1 id="page-title" class="text-3xl md:text-4xl font-bold text-gray-800 bg-gradient-to-r from-green-600 to-emerald-600 bg-clip-text text-transparent">
                            <?php echo $category_slug ? ucfirst(str_replace('-', ' ', $category_slug)) : 'All Products'; ?>
                            <?php if($search): ?>
                            <span class="text-lg text-gray-600 font-normal block sm:inline">- Search: "<?php echo htmlspecialchars($search); ?>"</span>
                            <?php endif; ?>
                        </h1>
                        <div id="product-count" class="text-gray-600 bg-white/60 backdrop-blur-sm px-4 py-2 rounded-full shadow-sm border border-gray-200">
                            <i class="fas fa-box mr-2 text-green-500"></i><?php echo $total_products; ?> products found
                        </div>
                    </div>
                    <div id="products-container" class="relative">
                        <div id="loading-spinner" class="hidden absolute inset-0 flex items-center justify-center bg-white/80 backdrop-blur-sm rounded-3xl z-10">
                            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-green-500"></div>
                        </div>
                        <div id="products-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 md:gap-8">
                            <?php foreach($products as $product): ?>
                            <a href="product.php?id=<?php echo $product['id']; ?>" class="group block bg-white/90 backdrop-blur-sm rounded-3xl shadow-lg hover:shadow-2xl transition-all duration-500 transform hover:-translate-y-2 border border-gray-100 hover:border-green-300 hover:ring-2 hover:ring-green-200/50 overflow-hidden relative">
                                <div class="absolute inset-0 bg-gradient-to-br from-green-50/0 via-emerald-50/0 to-teal-50/0 group-hover:from-green-50/30 group-hover:via-emerald-50/20 group-hover:to-teal-50/30 transition-all duration-500 rounded-3xl"></div>
                                <div class="relative z-10">
                                    <div class="relative overflow-hidden rounded-t-3xl aspect-[4/3]">
                                        <img src="assets/images/<?php echo $product['image']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700" loading="lazy">
                                        <div class="absolute inset-0 bg-gradient-to-t from-black/10 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                                        <?php if($product['badge']): ?>
                                        <div class="absolute top-4 left-4 bg-gradient-to-r from-green-500 to-emerald-600 text-white px-4 py-2 rounded-full text-sm font-bold shadow-xl transform group-hover:scale-105 transition-transform duration-300">
                                            <?php echo $product['badge']; ?>
                                        </div>
                                        <?php endif; ?>
                                        <?php echo renderFavouriteButton($product, 'absolute top-4 right-4'); ?>
                                        <?php if($product['original_price'] && $product['original_price'] > $product['price']): ?>
                                        <div class="absolute bottom-4 left-4 bg-red-500 text-white px-3 py-1 rounded-full text-xs font-bold shadow-lg">
                                            -<?php echo round((1 - $product['price'] / $product['original_price']) * 100); ?>%
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="p-6">
                                        <div class="flex items-center justify-between mb-4">
                                            <span class="text-sm text-gray-600 bg-gradient-to-r from-gray-100 to-gray-200 px-3 py-1 rounded-full font-semibold shadow-sm"><?php echo ucfirst($product['category_name'] ?: str_replace('-', ' ', $product['category'])); ?></span>
                                            <div class="flex items-center bg-yellow-50 px-3 py-1 rounded-full shadow-sm">
                                                <div class="flex text-yellow-400 mr-1">
                                                    <?php for($i = 1; $i <= 5; $i++): ?>
                                                        <i class="fas fa-star text-xs <?php echo $i <= $product['rating'] ? 'text-yellow-400' : 'text-gray-300'; ?>"></i>
                                                    <?php endfor; ?>
                                                </div>
                                                <span class="text-xs text-gray-700 font-medium">(<?php echo $product['reviews']; ?>)</span>
                                            </div>
                                        </div>
                                        <h3 class="font-bold text-lg mb-3 text-gray-800 group-hover:text-green-700 transition-colors duration-300 leading-tight line-clamp-2">
                                            <?php echo htmlspecialchars($product['name']); ?>
                                        </h3>
                                        <p class="text-gray-600 text-sm mb-4 line-clamp-2 leading-relaxed"><?php echo htmlspecialchars(substr($product['description'], 0, 80)); ?>...</p>
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center space-x-3">
                                                <div class="flex flex-col">
                                                    <span class="text-green-600 font-bold text-2xl">৳<?php echo number_format($product['price'], 0); ?></span>
                                                    <?php if($product['original_price']): ?>
                                                    <span class="text-gray-500 line-through text-sm">৳<?php echo number_format($product['original_price'], 0); ?></span>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if(isset($product['size']) && $product['size']): ?>
                                                <span class="text-xs text-blue-600 bg-blue-50 px-3 py-1 rounded-full font-medium shadow-sm">Size: <?php echo $product['size']; ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="flex items-center text-green-600 font-semibold text-sm group-hover:text-green-700 transition-colors duration-300">
                                                <span>View Details</span>
                                                <i class="fas fa-arrow-right ml-2 transform group-hover:translate-x-1 transition-transform duration-300"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                            <?php endforeach; ?>
                        </div>
                        <?php if($has_more): ?>
                        <div class="text-center mt-8">
                            <button id="see-more-btn" class="bg-gradient-to-r from-green-500 to-emerald-600 text-white px-8 py-3 rounded-full font-semibold shadow-lg hover:shadow-xl transition-all duration-300 transform hover:scale-105">
                                <i class="fas fa-plus mr-2"></i>See More Products
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="js/app.js"></script>
    <script>
        // Load cart count on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateCartCount();
            initFavourites();

            let currentOffset = 12; // since initial is 12

            // Helper to get current URLSearchParams (reads from window.location each time)
            function getUrlParams() {
                return new URLSearchParams(window.location.search);
            }

            // Centralized load-more logic so it can be attached to dynamic buttons
            function loadMore() {
                // Show loading
                const loadingSpinner = document.getElementById('loading-spinner');
                loadingSpinner.classList.remove('hidden');

                const urlParams = getUrlParams();
                const category = urlParams.get('category') || '';
                const search = urlParams.get('search') || '';

                // Fetch more products
                fetch(`products.php?offset=${currentOffset}${category ? `&category=${category}` : ''}${search ? `&search=${encodeURIComponent(search)}` : ''}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    // Append products
                    document.getElementById('products-grid').insertAdjacentHTML('beforeend', data.html);

                    // Update offset (we request 12 per page)
                    currentOffset += 12;

                    // Ensure See More button exists/updated according to the response
                    ensureSeeMoreButton(data.has_more);

                    // Re-initialize favourites for new products
                    initFavourites();
                })
                .catch(error => {
                    console.error('Error loading more products:', error);
                    alert('Error loading more products. Please try again.');
                })
                .finally(() => {
                    // Hide loading
                    loadingSpinner.classList.add('hidden');
                });
            }

            // Ensure the See More button is present when hasMore is true, removed/hidden otherwise
            function ensureSeeMoreButton(hasMore) {
                const container = document.getElementById('products-container');
                if (!container) return;

                let wrapper = container.querySelector('.see-more-wrapper');
                let btn = document.getElementById('see-more-btn');

                if (hasMore) {
                    if (!btn) {
                        // Create wrapper and button to match original markup
                        wrapper = document.createElement('div');
                        wrapper.className = 'see-more-wrapper text-center mt-8';

                        btn = document.createElement('button');
                        btn.id = 'see-more-btn';
                        btn.className = 'bg-gradient-to-r from-green-500 to-emerald-600 text-white px-8 py-3 rounded-full font-semibold shadow-lg hover:shadow-xl transition-all duration-300 transform hover:scale-105';
                        btn.innerHTML = '<i class="fas fa-plus mr-2"></i>See More Products';

                        wrapper.appendChild(btn);
                        container.appendChild(wrapper);

                        // Attach handler
                        btn.addEventListener('click', loadMore);
                    } else {
                        // Make sure it's visible
                        btn.style.display = 'inline-block';
                        if (wrapper) wrapper.style.display = '';
                    }
                } else {
                    // Remove/hide if present
                    if (btn) {
                        // prefer to remove entirely to keep DOM consistent with server markup
                        if (wrapper && wrapper.parentNode) {
                            wrapper.parentNode.removeChild(wrapper);
                        } else if (btn.parentNode) {
                            btn.parentNode.removeChild(btn);
                        }
                    }
                }
            }

            // Attach to existing see-more button if present
            const existingSeeMore = document.getElementById('see-more-btn');
            if (existingSeeMore) {
                // If the button exists in the initial markup, ensure it calls the centralized loader
                existingSeeMore.addEventListener('click', function(e) { e.preventDefault(); loadMore(); });
            }

            // Category filtering
            const categoryLinks = document.querySelectorAll('.sidebar-categories a');

            categoryLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const href = this.getAttribute('href');
                    const linkParams = new URLSearchParams(href.split('?')[1] || '');
                    const category = linkParams.get('category') || null;

                    // Show loading
                    const loadingSpinner = document.getElementById('loading-spinner');
                    loadingSpinner.classList.remove('hidden');

                    // Read current search param from the live URL (so it's always up-to-date)
                    const currentSearch = getUrlParams().get('search') || '';

                    // Build fetch URL
                    const fetchUrl = `products.php${category ? `?category=${category}` : ''}${currentSearch ? `${category ? '&' : '?'}search=${encodeURIComponent(currentSearch)}` : ''}`;

                    // Fetch filtered products
                    fetch(fetchUrl, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        // Update title
                        document.getElementById('page-title').innerHTML = data.title;

                        // Update count
                        document.getElementById('product-count').innerHTML = `<i class="fas fa-box mr-2 text-green-500"></i>${data.count} products found`;

                        // Update products grid
                        document.getElementById('products-grid').innerHTML = data.html;

                        // Reset offset
                        currentOffset = 12;

                        // Ensure See More button presence matches the response
                        ensureSeeMoreButton(data.has_more);

                        // Update URL
                        const newUrl = `${window.location.pathname}${category ? `?category=${category}` : ''}${currentSearch ? `${category ? '&' : '?'}search=${encodeURIComponent(currentSearch)}` : ''}`;
                        history.pushState(null, '', newUrl);

                        // Update active category classes
                        categoryLinks.forEach(l => l.classList.remove('bg-gradient-to-r', 'from-green-100', 'to-emerald-100', 'text-green-700', 'font-semibold', 'shadow-md'));
                        categoryLinks.forEach(l => l.classList.add('hover:bg-gray-50', 'text-gray-700', 'hover:shadow-sm'));
                        this.classList.remove('hover:bg-gray-50', 'text-gray-700', 'hover:shadow-sm');
                        this.classList.add('bg-gradient-to-r', 'from-green-100', 'to-emerald-100', 'text-green-700', 'font-semibold', 'shadow-md');

                        // Re-initialize favourites for new products
                        initFavourites();
                    })
                    .catch(error => {
                        console.error('Error fetching products:', error);
                        alert('Error loading products. Please try again.');
                    })
                    .finally(() => {
                        // Hide loading
                        loadingSpinner.classList.add('hidden');
                    });
                });
            });

            // Search handling (desktop and mobile forms)
            function handleSearchSubmit(searchValue) {
                // Show loading
                const loadingSpinner = document.getElementById('loading-spinner');
                loadingSpinner.classList.remove('hidden');

                // Build fetch URL keeping current category if present in URL
                const currentParams = getUrlParams();
                const category = currentParams.get('category') || '';
                const fetchUrl = `products.php${category ? `?category=${category}` : ''}${searchValue ? `${category ? '&' : '?'}search=${encodeURIComponent(searchValue)}` : ''}`;

                fetch(fetchUrl, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    // Update title and count
                    document.getElementById('page-title').innerHTML = data.title;
                    document.getElementById('product-count').innerHTML = `<i class="fas fa-box mr-2 text-green-500"></i>${data.count} products found`;

                    // Update grid
                    document.getElementById('products-grid').innerHTML = data.html;

                    // Reset offset
                    currentOffset = 12;

                    // Ensure See More button presence matches response
                    ensureSeeMoreButton(data.has_more);

                    // Update URL
                    const newUrl = `${window.location.pathname}${category ? `?category=${category}` : ''}${searchValue ? `${category ? '&' : '?'}search=${encodeURIComponent(searchValue)}` : ''}`;
                    history.pushState(null, '', newUrl);

                    // Re-init favourites
                    initFavourites();
                })
                .catch(err => {
                    console.error('Search error:', err);
                    alert('Error performing search. Please try again.');
                })
                .finally(() => {
                    loadingSpinner.classList.add('hidden');
                });
            }

            // Desktop header search form
            const headerSearchForm = document.querySelector('header form[method="GET"][action="products.php"]');
            if (headerSearchForm) {
                headerSearchForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const input = this.querySelector('input[name="search"]');
                    const val = input ? input.value.trim() : '';
                    handleSearchSubmit(val);
                });
            }

            // Mobile search form (in header markup under md:hidden)
            const mobileSearchForm = document.querySelector('form[method="GET"][action="products.php"].md\:hidden');
            // Fallback: select any mobile form by checking nearest under header
            if (!mobileSearchForm) {
                const mobileFormFallback = document.querySelector('#mobile-menu') ? document.querySelector('#mobile-menu').previousElementSibling && document.querySelector('#mobile-menu').previousElementSibling.querySelector('form[action="products.php"]') : null;
                if (mobileFormFallback) {
                    mobileFormFallback.addEventListener('submit', function(e) {
                        e.preventDefault();
                        const input = this.querySelector('input[name="search"]');
                        const val = input ? input.value.trim() : '';
                        handleSearchSubmit(val);
                    });
                }
            } else {
                mobileSearchForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const input = this.querySelector('input[name="search"]');
                    const val = input ? input.value.trim() : '';
                    handleSearchSubmit(val);
                });
            }
        });
    </script>
</body>
</html>