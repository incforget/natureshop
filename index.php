<?php 
$page_title = 'Home';
include 'includes/header.php';
$banners = getBanners();
// Ensure page-level data is defined here (header no longer fetches products/categories)
$categories = getCategories();
$products = getProducts(8); // fetch a small set for best sellers on homepage
?>

    <!-- Hero Slider -->
    <section class="relative bg-gradient-to-br from-green-500 via-emerald-600 to-teal-700 overflow-hidden min-h-screen flex items-center">
        <div class="absolute inset-0 bg-black bg-opacity-30"></div>
        <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width="60" height="60" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg"%3E%3Cg fill="none" fill-rule="evenodd"%3E%3Cg fill="%23ffffff" fill-opacity="0.05"%3E%3Ccircle cx="30" cy="30" r="2"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')] opacity-20"></div>
        <div class="relative container mx-auto px-4 py-20">
            <div class="max-w-5xl mx-auto text-center text-white">
                <h1 class="text-6xl md:text-7xl font-extrabold mb-6 leading-tight">
                    Discover the Power of <span class="text-yellow-300 drop-shadow-lg">Nature</span>
                </h1>
                <p class="text-xl md:text-2xl mb-4 text-green-100 font-light">
                    Premium natural products sourced sustainably from Bangladesh
                </p>
                <p class="text-lg md:text-xl mb-8 text-green-200">
                    Embrace wellness with our curated collection of organic herbs, essential oils, and natural remedies
                </p>
                <div class="flex flex-col sm:flex-row gap-6 justify-center items-center">
                    <a href="/products" class="bg-white text-green-700 px-10 py-4 rounded-full font-bold text-lg hover:bg-green-50 transition-all transform hover:scale-105 shadow-2xl hover:shadow-green-500/25 flex items-center">
                        <i class="fas fa-shopping-cart mr-3"></i>
                        Shop Now
                    </a>
                    <a href="#categories" class="border-2 border-white text-white px-10 py-4 rounded-full font-bold text-lg hover:bg-white hover:text-green-700 transition-all hover:scale-105 flex items-center">
                        <i class="fas fa-compass mr-3"></i>
                        Explore Categories
                    </a>
                </div>
            </div>
        </div>
        <!-- Decorative elements -->
        <div class="absolute top-20 left-20 w-24 h-24 bg-white bg-opacity-10 rounded-full animate-pulse"></div>
        <div class="absolute bottom-20 right-20 w-36 h-36 bg-white bg-opacity-10 rounded-full animate-pulse delay-1000"></div>
        <div class="absolute top-1/2 right-32 w-20 h-20 bg-white bg-opacity-10 rounded-full animate-pulse delay-500"></div>
        <div class="absolute top-32 right-10 w-16 h-16 bg-yellow-300 bg-opacity-20 rounded-full animate-bounce"></div>
    </section>

    <!-- Product Categories -->
    <section id="categories" class="py-16 bg-white">
        <div class="container mx-auto px-4 relative">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-gray-800 mb-4">Shop by Category</h2>
                <p class="text-gray-600 text-xl max-w-2xl mx-auto">Browse our wide range of natural product categories</p>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <?php foreach($categories as $cat): ?>
                <a href="/products/category/<?php echo $cat['slug']; ?>" class="cursor-pointer group">
                    <div class="bg-white rounded-xl p-6 text-center shadow-lg group-hover:shadow-2xl group-hover:-translate-y-1 transform transition">
                        <div class="<?php echo $cat['icon_type'] === 'svg' ? 'bg-white' : 'bg-emerald-50'; ?> w-16 h-16 rounded-xl flex items-center justify-center mx-auto mb-4">
                            <?php echo getCategoryIcon($cat, 'large'); ?>
                        </div>
                        <h3 class="font-semibold"><?php echo $cat['name']; ?></h3>
                        <p class="text-gray-500 text-sm mt-1"><?php echo $cat['count']; ?> products</p>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Banners -->
    <?php if (!empty($banners)): ?>
    <section class="py-12 bg-gradient-to-r from-gray-100 to-gray-200">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <?php foreach($banners as $banner): ?>
                <a href="<?php echo $banner['link']; ?>" class="group block bg-white rounded-2xl shadow-xl overflow-hidden hover:shadow-2xl transition-all duration-500 transform hover:-translate-y-3 border border-gray-200">
                    <div class="relative">
                        <?php if($banner['image']): ?>
                        <img src="assets/images/<?php echo $banner['image']; ?>" alt="<?php echo $banner['title']; ?>" class="w-full h-64 object-cover group-hover:scale-110 transition-transform duration-500" loading="lazy">
                        <?php else: ?>
                        <div class="w-full h-64 bg-gradient-to-br from-green-500 to-emerald-600 flex items-center justify-center">
                            <div class="text-center text-white">
                                <i class="fas fa-image text-5xl mb-4"></i>
                                <p class="text-lg font-semibold">No Image</p>
                            </div>
                        </div>
                        <?php endif; ?>
                        <div class="absolute inset-0 bg-gradient-to-t from-black via-transparent to-transparent opacity-0 group-hover:opacity-80 transition-all duration-500 flex items-end justify-center pb-8">
                            <div class="text-white text-center transform translate-y-4 group-hover:translate-y-0 transition-transform duration-500">
                                <h3 class="text-2xl font-bold mb-3"><?php echo $banner['title']; ?></h3>
                                <?php if($banner['description']): ?>
                                <p class="text-base"><?php echo $banner['description']; ?></p>
                                <?php endif; ?>
                                <div class="mt-4">
                                    <span class="inline-block bg-white text-green-600 px-4 py-2 rounded-full font-semibold hover:bg-green-600 hover:text-white transition-colors">Learn More</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Best Sellers -->
    <section class="py-20 bg-white">
        <div class="container mx-auto px-4">
            <div class="text-center mb-16">
                <h2 class="text-5xl font-bold text-gray-800 mb-4">Our Best Sellers</h2>
                <p class="text-gray-600 text-xl">Most loved products by our customers</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8" id="best-sellers">
                <?php foreach($products as $product): ?>
                <a href="/product/<?php echo $product['id']; ?>" class="group block bg-white rounded-3xl shadow-xl hover:shadow-2xl transition-all duration-500 transform hover:-translate-y-4 border border-gray-200 hover:border-green-400 hover:ring-4 hover:ring-green-200/50 overflow-hidden relative">
                    <div class="absolute inset-0 bg-gradient-to-br from-green-50/0 via-emerald-50/0 to-teal-50/0 group-hover:from-green-50/20 group-hover:via-emerald-50/10 group-hover:to-teal-50/20 transition-all duration-500 rounded-3xl"></div>
                    <div class="relative z-10">
                        <div class="relative overflow-hidden aspect-[4/3]">
                            <img src="/assets/images/<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700" loading="lazy">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/20 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                            <?php if($product['badge']): ?>
                            <div class="absolute top-3 left-3 bg-gradient-to-r from-green-500 to-emerald-600 text-white px-4 py-2 rounded-full text-sm font-bold shadow-xl transform group-hover:scale-105 transition-transform duration-300">
                                <?php echo $product['badge']; ?>
                            </div>
                            <?php endif; ?>
                            <?php echo renderFavouriteButton($product, 'absolute top-3 right-3'); ?>
                            <?php if($product['original_price'] && $product['original_price'] > $product['price']): ?>
                            <div class="absolute bottom-3 left-3 bg-red-500 text-white px-3 py-1 rounded-full text-xs font-bold shadow-lg">
                                -<?php echo round((1 - $product['price'] / $product['original_price']) * 100); ?>%
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="p-4">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-sm text-gray-600 bg-gradient-to-r from-gray-100 to-gray-200 px-4 py-2 rounded-full font-semibold shadow-sm"><?php echo ucfirst($product['category']); ?></span>
                                <div class="flex items-center bg-yellow-50 px-2 py-1 rounded-full">
                                    <div class="flex text-yellow-400 mr-1">
                                        <?php for($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star text-xs <?php echo $i <= $product['rating'] ? 'text-yellow-400' : 'text-gray-300'; ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <span class="text-xs text-gray-700 font-medium">(<?php echo $product['reviews']; ?>)</span>
                                </div>
                            </div>
                            <h3 class="font-bold text-lg mb-2 text-gray-800 group-hover:text-green-700 transition-colors duration-300 leading-tight line-clamp-2">
                                <?php echo $product['name']; ?>
                            </h3>
                            <p class="text-gray-600 text-sm mb-4 line-clamp-2 leading-relaxed"><?php echo substr($product['description'], 0, 70); ?>...</p>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <div class="flex flex-col">
                                        <span class="text-green-600 font-bold text-2xl">৳<?php echo number_format($product['price'], 0); ?></span>
                                        <?php if($product['original_price']): ?>
                                        <span class="text-gray-500 line-through text-sm">৳<?php echo number_format($product['original_price'], 0); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if(isset($product['size']) && $product['size']): ?>
                                    <span class="text-xs text-blue-600 bg-blue-50 px-2 py-1 rounded-full font-medium">Size: <?php echo $product['size']; ?></span>
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
            <div class="text-center mt-12">
                <a href="products.php" class="bg-green-600 text-white px-8 py-4 rounded-full font-semibold text-lg hover:bg-green-700 transition-all transform hover:scale-105 shadow-lg inline-block">
                    View All Products
                </a>
            </div>
        </div>
    </section>

<?php include 'includes/footer.php'; ?>

    <!-- Vue App -->
    <div id="app"></div>
    <script src="/js/app.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            updateCartCount();
            initFavourites();

            // Smooth scrolling for anchor links
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
        });
    </script>
</body>
</html>