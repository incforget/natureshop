<?php
$page_title = "Favourites";
include 'includes/header.php';
?>

    <section class="py-20 bg-white">
        <div class="container mx-auto px-4">
            <div class="text-center mb-16">
                <h2 class="text-5xl font-bold text-gray-800 mb-4">My Favourites</h2>
                <p class="text-gray-600 text-xl">Your saved natural products</p>
            </div>

            <div id="favourites-list" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <!-- Favourites will be loaded here -->
            </div>

            <div id="no-favourites" class="text-center py-16 hidden">
                <div class="max-w-md mx-auto">
                    <i class="fas fa-heart text-8xl text-red-200 mb-6"></i>
                    <h2 class="text-3xl font-bold text-gray-700 mb-4">No favourites yet</h2>
                    <p class="text-gray-500 text-lg mb-8">Start exploring our natural products and save your favorites for later.</p>
                    <a href="products.php" class="bg-gradient-to-r from-green-500 to-emerald-600 text-white px-8 py-4 rounded-full font-semibold text-lg hover:from-green-600 hover:to-emerald-700 transition-all transform hover:scale-105 shadow-xl inline-block">Browse Products</a>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>

    <script>
        // Local safe accessor to avoid depending on js/app.js load order
        function safeGetLocal(key, defaultValue) {
            try {
                const raw = localStorage.getItem(key);
                if (raw === null) return typeof defaultValue === 'undefined' ? null : defaultValue;
                return JSON.parse(raw);
            } catch (e) {
                try { localStorage.removeItem(key); } catch (er) {}
                return typeof defaultValue === 'undefined' ? null : defaultValue;
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            loadFavourites();
            // updateCartCount is provided by js/app.js; if not yet loaded, it's safe to call later
            if (typeof updateCartCount === 'function') updateCartCount();
            if (typeof initFavourites === 'function') initFavourites();
        });

        function loadFavourites() {
            const favourites = safeGetLocal('favourites', []);
            const favouritesDiv = document.getElementById('favourites-list');
            const noFavouritesDiv = document.getElementById('no-favourites');

            favouritesDiv.innerHTML = '';

            if (!Array.isArray(favourites) || favourites.length === 0) {
                noFavouritesDiv.classList.remove('hidden');
                return;
            }

            noFavouritesDiv.classList.add('hidden');

            favourites.forEach(productId => {
                const productData = safeGetLocal('product_' + productId, {});
                if (productData && productData.id) {
                    const productDiv = document.createElement('a');
                    productDiv.href = `product.php?id=${productData.id}`;
                    productDiv.className = 'group block bg-white rounded-3xl shadow-xl hover:shadow-2xl transition-all duration-500 transform hover:-translate-y-4 border border-gray-200 hover:border-green-400 hover:ring-4 hover:ring-green-200/50 overflow-hidden relative';
                    productDiv.innerHTML = `
                        <div class="absolute inset-0 bg-gradient-to-br from-green-50/0 via-emerald-50/0 to-teal-50/0 group-hover:from-green-50/20 group-hover:via-emerald-50/10 group-hover:to-teal-50/20 transition-all duration-500 rounded-3xl"></div>
                        <div class="relative z-10">
                            <div class="relative overflow-hidden aspect-[4/3]">
                                <img src="assets/images/${productData.image}" alt="${productData.name}" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700" loading="lazy">
                                <div class="absolute inset-0 bg-gradient-to-t from-black/20 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                                ${productData.badge ? `<div class="absolute top-3 left-3 bg-gradient-to-r from-green-500 to-emerald-600 text-white px-4 py-2 rounded-full text-sm font-bold shadow-xl transform group-hover:scale-105 transition-transform duration-300">${productData.badge}</div>` : ''}
                                <button onclick="event.preventDefault(); event.stopPropagation(); toggleFavourite(${productData.id}, '${productData.name.replace(/'/g, "\\'")}', ${productData.price}, '${productData.image}', '${productData.size || ''}', '${productData.badge || ''}', '${productData.category || ''}', ${productData.rating || 0}, ${productData.reviews || 0}, '${(productData.description || '').substring(0, 200).replace(/'/g, "\\'")}', ${productData.original_price || 0});" id="fav-btn-${productData.id}" class="absolute top-3 right-3 w-6 h-6 bg-transparent bg-opacity-01 rounded-full flex items-center justify-center text-red-500 hover:text-red-600 hover:bg-opacity-100 transition-all duration-300 shadow-lg">
                                    <i class="far fa-heart text-lg"></i>
                                </button>
                                ${productData.original_price && productData.original_price > productData.price ? `<div class="absolute bottom-3 left-3 bg-red-500 text-white px-3 py-1 rounded-full text-xs font-bold shadow-lg">-${Math.round((1 - productData.price / productData.original_price) * 100)}%</div>` : ''}
                            </div>
                            <div class="p-4">
                                <div class="flex items-center justify-between mb-3">
                                    <span class="text-sm text-gray-600 bg-gradient-to-r from-gray-100 to-gray-200 px-4 py-2 rounded-full font-semibold shadow-sm">${productData.category ? productData.category.charAt(0).toUpperCase() + productData.category.slice(1) : ''}</span>
                                    <div class="flex items-center bg-yellow-50 px-2 py-1 rounded-full">
                                        <div class="flex text-yellow-400 mr-1">
                                            ${Array.from({length:5}, (_,i) => `<i class="fas fa-star text-xs ${i < (productData.rating || 0) ? 'text-yellow-400' : 'text-gray-300'}"></i>`).join('')}
                                        </div>
                                        <span class="text-xs text-gray-700 font-medium">(${productData.reviews || 0})</span>
                                    </div>
                                </div>
                                <h3 class="font-bold text-lg mb-2 text-gray-800 group-hover:text-green-700 transition-colors duration-300 leading-tight line-clamp-2">${productData.name}</h3>
                                <p class="text-gray-600 text-sm mb-4 line-clamp-2 leading-relaxed">${productData.description ? productData.description.substring(0,70) + '...' : ''}</p>
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-3">
                                        <div class="flex flex-col">
                                            <span class="text-green-600 font-bold text-2xl">৳${productData.price.toLocaleString()}</span>
                                            ${productData.original_price ? `<span class="text-gray-500 line-through text-sm">৳${productData.original_price.toLocaleString()}</span>` : ''}
                                        </div>
                                        ${productData.size ? `<span class="text-xs text-blue-600 bg-blue-50 px-2 py-1 rounded-full font-medium">Size: ${productData.size}</span>` : ''}
                                    </div>
                                    <div class="flex items-center text-green-600 font-semibold text-sm group-hover:text-green-700 transition-colors duration-300">
                                        <span>View</span>
                                        <i class="fas fa-arrow-right ml-1 transform group-hover:translate-x-1 transition-transform duration-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    favouritesDiv.appendChild(productDiv);
                }
            });
        }
    </script>
    <script src="/js/app.js"></script>
</body>
</html>