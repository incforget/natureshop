<!-- Footer -->
    <footer class="bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 text-white py-8 sm:py-12 lg:py-16">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 sm:gap-8 lg:gap-10">
                <div class="lg:col-span-1">
                    <?php
                    $site_name = get_setting('site_name', 'NatureBD');
                    $site_tagline = get_setting('site_tagline', 'Back to Nature');
                    $contact_phone = get_setting('contact_phone', '09639812525');
                    $contact_address = get_setting('contact_address', 'Level-5, Noor Tower, 110 Bir Uttam CR Dutta Rd, Dhaka 1205');
                    $contact_email = get_setting('contact_email', 'naturebd@gmail.com');
                    $social = get_setting('social_links', []);
                    ?>
                    <h3 class="text-xl sm:text-2xl font-bold mb-4 text-green-400"><?php echo htmlspecialchars($site_name); ?></h3>
                    <p class="text-sm sm:text-base text-gray-300 mb-4"><?php echo htmlspecialchars($site_tagline); ?></p>
                    <div class="space-y-2">
                        <p class="flex items-center text-sm sm:text-base"><i class="fas fa-phone mr-3 text-green-400"></i><?php echo htmlspecialchars($contact_phone); ?></p>
                        <p class="flex items-start text-sm sm:text-base"><i class="fas fa-map-marker-alt mr-3 mt-1 text-green-400"></i><?php echo htmlspecialchars($contact_address); ?></p>
                        <p class="flex items-center text-sm sm:text-base"><i class="fas fa-envelope mr-3 text-green-400"></i><?php echo htmlspecialchars($contact_email); ?></p>
                    </div>
                </div>
                <div>
                    <h4 class="text-lg sm:text-xl font-semibold mb-4 text-white">Useful Links</h4>
                    <ul class="space-y-2">
                        <li><a href="#" class="text-sm sm:text-base text-gray-300 hover:text-green-400 transition-colors duration-200">About Us</a></li>
                        <li><a href="#" class="text-sm sm:text-base text-gray-300 hover:text-green-400 transition-colors duration-200">Privacy Policy</a></li>
                        <li><a href="#" class="text-sm sm:text-base text-gray-300 hover:text-green-400 transition-colors duration-200">Terms and Conditions</a></li>
                        <li><a href="#" class="text-sm sm:text-base text-gray-300 hover:text-green-400 transition-colors duration-200">Return and Refund</a></li>
                        <li><a href="#" class="text-sm sm:text-base text-gray-300 hover:text-green-400 transition-colors duration-200">Cookie Policy</a></li>
                        <li><a href="#" class="text-sm sm:text-base text-gray-300 hover:text-green-400 transition-colors duration-200">Sitemap</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-lg sm:text-xl font-semibold mb-4 text-white">Help Center</h4>
                    <ul class="space-y-2">
                        <li><a href="#" class="text-sm sm:text-base text-gray-300 hover:text-green-400 transition-colors duration-200">Order Tracking</a></li>
                        <li><a href="#" class="text-sm sm:text-base text-gray-300 hover:text-green-400 transition-colors duration-200">Contact Us</a></li>
                        <li><a href="#" class="text-sm sm:text-base text-gray-300 hover:text-green-400 transition-colors duration-200">How to Order</a></li>
                        <li><a href="#" class="text-sm sm:text-base text-gray-300 hover:text-green-400 transition-colors duration-200">Product Returns</a></li>
                        <li><a href="#" class="text-sm sm:text-base text-gray-300 hover:text-green-400 transition-colors duration-200">FAQ</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-lg sm:text-xl font-semibold mb-4 text-white">Follow Us</h4>
                    <div class="flex space-x-4">
                        <a href="<?php echo htmlspecialchars($social['facebook'] ?? '#'); ?>" target="_blank" rel="noopener noreferrer" class="text-gray-300 hover:text-green-400 transition-colors duration-200" aria-label="Facebook"><i class="fab fa-facebook text-2xl sm:text-3xl"></i></a>
                        <a href="<?php echo htmlspecialchars($social['whatsapp'] ?? '#'); ?>" target="_blank" rel="noopener noreferrer" class="text-gray-300 hover:text-green-400 transition-colors duration-200" aria-label="WhatsApp"><i class="fab fa-whatsapp text-2xl sm:text-3xl"></i></a>
                        <a href="<?php echo htmlspecialchars($social['instagram'] ?? '#'); ?>" target="_blank" rel="noopener noreferrer" class="text-gray-300 hover:text-green-400 transition-colors duration-200" aria-label="Instagram"><i class="fab fa-instagram text-2xl sm:text-3xl"></i></a>
                        <a href="<?php echo htmlspecialchars($social['twitter'] ?? '#'); ?>" target="_blank" rel="noopener noreferrer" class="text-gray-300 hover:text-green-400 transition-colors duration-200" aria-label="Twitter"><i class="fab fa-twitter text-2xl sm:text-3xl"></i></a>
                    </div>
                </div>
            </div>
            <div class="border-t border-gray-600 mt-8 sm:mt-10 lg:mt-12 pt-6 sm:pt-8 text-center">
                <p class="text-sm sm:text-base text-gray-400"><?php echo get_setting('copyright_text', '&copy; 2025 NatureBD. All Rights Reserved.'); ?></p>
            </div>
        </div>
    </footer>

    <!-- Floating Cart Button -->
    <button id="floating-cart-btn" onclick="event.stopPropagation(); openCartModal()" class="fixed bottom-4 right-4 sm:bottom-6 sm:right-6 lg:bottom-8 lg:right-8 bg-transparent text-green-500 p-2 sm:p-2 rounded-2xl shadow-none hover:shadow-none transition-all duration-300 transform hover:scale-110 hover:-translate-y-1 z-50 group focus:outline-none focus:ring-4 focus:ring-green-300" aria-label="Open cart">
        <div class="relative">
            <i class="fas fa-shopping-bag text-7xl sm:text-7xl lg:text-6xl text-green-600"></i>
            <span id="cart-count" class="absolute -top-2 -right-2 sm:-top-3 sm:-right-3 lg:-top-4 lg:-right-4 bg-red-500 text-white text-xs sm:text-sm font-bold rounded-full h-5 w-5 sm:h-6 sm:w-6 lg:h-7 lg:w-7 flex items-center justify-center animate-bounce"></span>
        </div>
        <div class="absolute bottom-full right-0 mb-2 px-3 py-2 bg-gray-800 text-white text-xs sm:text-sm rounded-lg opacity-0 group-hover:opacity-100 transition-opacity duration-300 whitespace-nowrap shadow-lg">
            View Cart
            <div class="absolute top-full right-3 w-0 h-0 border-l-4 border-r-4 border-t-4 border-transparent border-t-gray-800"></div>
        </div>
    </button>

    <!-- Cart Modal -->
    <div id="cart-modal" class="fixed inset-0 bg-black bg-opacity-60 backdrop-blur-sm hidden z-50 modal-backdrop">
        <div class="flex items-center justify-center min-h-screen p-4 sm:p-6">
            <div class="bg-white rounded-3xl shadow-2xl w-full max-w-sm sm:max-w-md lg:max-w-2xl max-h-[90vh] sm:max-h-[95vh] overflow-hidden transform transition-all duration-300 scale-95 modal-enter" onclick="event.stopPropagation()">
                <!-- Modal Header -->
                <div class="bg-gradient-to-r from-green-500 to-emerald-600 text-white p-4 sm:p-6 lg:p-8">
                    <div class="flex justify-between items-center">
                        <h2 class="text-xl sm:text-2xl lg:text-3xl font-bold flex items-center">
                            <i class="fas fa-shopping-cart mr-3"></i>
                            Your Cart
                        </h2>
                        <button onclick="closeCartModal()" class="text-white hover:text-gray-200 transition-colors p-2 rounded-full hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-white/50" aria-label="Close cart">
                            <i class="fas fa-times text-xl sm:text-2xl"></i>
                        </button>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="p-4 sm:p-6 lg:p-8 max-h-64 sm:max-h-80 lg:max-h-96 overflow-y-auto custom-scrollbar">
                    <div id="cart-items" class="space-y-4">
                        <!-- Cart items will be populated here -->
                    </div>

                    <!-- Empty cart message -->
                    <div id="empty-cart" class="text-center py-12 sm:py-16 lg:py-20 hidden">
                        <i class="fas fa-shopping-cart text-5xl sm:text-6xl lg:text-7xl text-gray-300 mb-6"></i>
                        <h3 class="text-xl sm:text-2xl font-semibold text-gray-600 mb-3">Your cart is empty</h3>
                        <p class="text-gray-500 text-base sm:text-lg">Add some products to get started!</p>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div id="cart-footer" class="border-t border-gray-200 p-4 sm:p-6 lg:p-8 hidden">
                    <div class="flex justify-between items-center mb-4 sm:mb-6">
                        <span class="text-lg sm:text-xl font-semibold text-gray-700">Total:</span>
                        <span id="cart-total" class="text-2xl sm:text-3xl font-bold text-green-600">৳0</span>
                    </div>
                    <div class="flex flex-col sm:flex-row gap-3 sm:gap-4">
                        <button onclick="closeCartModal()" class="flex-1 bg-gray-200 text-gray-800 py-3 sm:py-4 px-6 rounded-xl font-semibold hover:bg-gray-300 transition-colors text-base sm:text-lg focus:outline-none focus:ring-2 focus:ring-gray-400">
                            Continue Shopping
                        </button>
                        <a href="/checkout" class="flex-1 bg-gradient-to-r from-green-500 to-emerald-600 text-white py-3 sm:py-4 px-6 rounded-xl font-semibold hover:from-green-600 hover:to-emerald-700 transition-all text-center text-base sm:text-lg focus:outline-none focus:ring-2 focus:ring-green-400">
                            Proceed to Checkout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>