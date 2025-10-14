<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
    // Ensure config and helper functions are available for title and meta data
    include_once __DIR__ . '/config.php';
    include_once __DIR__ . '/functions.php';

    $default_site = get_setting('site_name', 'NatureBD');
    $default_tagline = get_setting('site_tagline', 'Back to Nature');
    ?>
    <title><?php
        if (isset($page_title) && $page_title !== '') {
            // Append site name if not already present in page_title
            if (stripos($page_title, $default_site) === false) {
                echo htmlspecialchars($page_title . ' - ' . $default_site);
            } else {
                echo htmlspecialchars($page_title);
            }
        } else {
            echo htmlspecialchars($default_site . ' - ' . $default_tagline);
        }
    ?></title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Vue JS CDN -->
    <script src="https://cdn.jsdelivr.net/npm/vue@3/dist/vue.global.js"></script>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Bootstrap Icons for additional icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Custom Styles -->
    <link rel="stylesheet" href="/css/style.css">
</head>
<body class="bg-gray-50">
    <?php // config and helpers already included in <head> ?>

    <!-- Header -->
    <header class="bg-white shadow-lg sticky top-0 z-50 border-b border-gray-100">
        <div class="container mx-auto px-4">
            <div class="flex items-center justify-between py-4">
                <!-- Logo -->
                <div class="flex items-center space-x-3">
                    <?php
                    $site_name = get_setting('site_name', 'NatureBD');
                    $site_tagline = get_setting('site_tagline', 'Premium Naturals');
                    $site_logo = get_setting('logo', '/assets/images/logo.png');
                    $icon_id = 'site-logo-icon';
                    $img_id = 'site-logo-img';
                    ?>
                    <!-- Icon container: visible when no logo or when logo fails to load -->
                    <div id="<?php echo $icon_id; ?>" class="flex items-center justify-center w-12 h-12 rounded-xl bg-gradient-to-br from-green-500 to-emerald-600 shadow-md" <?php echo $site_logo ? 'style="display:none;"' : ''; ?>>
                        <i class="fas fa-leaf text-white text-xl"></i>
                    </div>
                    <div>
                        <a href="/" class="flex items-center space-x-3">
                            <?php if ($site_logo): ?>
                                <img id="<?php echo $img_id; ?>" src="<?php echo htmlspecialchars($site_logo); ?>" alt="<?php echo htmlspecialchars($site_name); ?>" class="w-8 h-8 object-contain mr-2" onload="document.getElementById('<?php echo $icon_id; ?>').style.display='none';" onerror="this.style.display='none'; document.getElementById('<?php echo $icon_id; ?>').style.display='flex';">
                            <?php endif; ?>
                            <div>
                                <span class="text-2xl font-extrabold text-gray-800 tracking-tight"><?php echo htmlspecialchars($site_name); ?></span>
                                <p class="text-xs text-gray-500"><?php echo htmlspecialchars($site_tagline); ?></p>
                            </div>
                        </a>
                    </div>
                </div>

                <!-- Navigation -->
                <nav class="hidden md:flex items-center space-x-8">
                    <a href="/" class="flex items-center text-green-600 font-semibold text-lg hover:text-green-700 hover:bg-green-50 px-3 py-2 rounded-lg transition-all duration-200">
                        <i class="fas fa-home mr-2"></i>Home
                    </a>
                    <a href="/products" class="flex items-center text-gray-700 hover:text-green-600 hover:bg-green-50 px-3 py-2 rounded-lg text-lg transition-all duration-200">
                        <svg class="mr-2" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
<path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
<polyline points="3.27,6.96 12,12.01 20.73,6.96"></polyline>
<line x1="12" y1="22.08" x2="12" y2="12"></line>
</svg>Products
                    </a>
                    <a href="/favourites" class="flex items-center text-gray-700 hover:text-green-600 hover:bg-green-50 px-3 py-2 rounded-lg text-lg transition-all duration-200">
                        <i class="fas fa-heart mr-2"></i>Favourites
                    </a>
                    <a href="/order-history" class="flex items-center text-gray-700 hover:text-green-600 hover:bg-green-50 px-3 py-2 rounded-lg text-lg transition-all duration-200">
                        <i class="fas fa-list mr-2"></i>Orders
                    </a>
                    <a href="/profile" class="flex items-center text-gray-700 hover:text-green-600 hover:bg-green-50 px-3 py-2 rounded-lg text-lg transition-all duration-200">
                        <i class="fas fa-user mr-2"></i>Profile
                    </a>
                </nav>

                <!-- Search and Cart -->
                <div class="flex items-center space-x-4">
                    <div class="hidden md:block relative">
                        <form method="GET" action="/products" class="flex">
                            <input type="text" name="search" placeholder="Search natural products..." class="border border-gray-300 rounded-l-full px-4 py-2 w-72 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded-r-full hover:bg-green-700 transition-colors">
                                <i class="fas fa-search"></i>
                            </button>
                        </form>
                    </div>
                    <a href="/favourites" class="md:hidden text-gray-700 hover:text-green-600 relative p-3 rounded-full hover:bg-green-50 transition-all duration-200 group">
                        <i class="fas fa-heart text-xl"></i>
                    </a>
                    <a href="/products" class="md:hidden text-gray-700 hover:text-green-600 p-3 rounded-full hover:bg-green-50 transition-all duration-200">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
<path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
<polyline points="3.27,6.96 12,12.01 20.73,6.96"></polyline>
<line x1="12" y1="22.08" x2="12" y2="12"></line>
</svg>
                    </a>
                    <a href="/profile" class="md:hidden text-gray-700 hover:text-green-600 p-3 rounded-full hover:bg-green-50 transition-all duration-200">
                        <i class="fas fa-user text-xl"></i>
                    </a>
                    <!-- Mobile Menu Button -->
                    <button id="mobile-menu-btn" class="md:hidden text-gray-700 p-4 rounded-full hover:bg-green-50 hover:text-green-600 transition-all duration-200">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                </div>
            </div>

            <!-- Mobile Search -->
            <div class="md:hidden pb-4">
                <form method="GET" action="/products" class="flex">
                    <input type="text" name="search" placeholder="Search products..." class="border border-gray-300 rounded-l-full px-4 py-2 w-full focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                    <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded-r-full hover:bg-green-700">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>

            <!-- Mobile Menu -->
            <div id="mobile-menu" class="md:hidden bg-white border-t border-gray-100 hidden">
                <div class="py-4 space-y-3">
                    <a href="/" class="flex items-center text-green-600 font-semibold py-3 px-4 hover:bg-green-50 rounded-lg transition-all duration-200">
                        <i class="fas fa-home mr-3"></i>Home
                    </a>
                    <a href="/products" class="flex items-center text-gray-700 hover:text-green-600 py-3 px-4 hover:bg-green-50 rounded-lg transition-all duration-200">
                        <svg class="mr-3" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
<path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
<polyline points="3.27,6.96 12,12.01 20.73,6.96"></polyline>
<line x1="12" y1="22.08" x2="12" y2="12"></line>
</svg>Products
                    </a>
                    <a href="/favourites" class="flex items-center text-gray-700 hover:text-green-600 py-3 px-4 hover:bg-green-50 rounded-lg transition-all duration-200">
                        <i class="fas fa-heart mr-3"></i>Favourites
                    </a>
                    <a href="/order-history" class="flex items-center text-gray-700 hover:text-green-600 py-3 px-4 hover:bg-green-50 rounded-lg transition-all duration-200">
                        <i class="fas fa-list mr-3"></i>Orders
                    </a>
                    <a href="/profile" class="flex items-center text-gray-700 hover:text-green-600 py-3 px-4 hover:bg-green-50 rounded-lg transition-all duration-200">
                        <i class="fas fa-user mr-3"></i>Profile
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Toast Notifications Container -->
    <div id="toast-container" class="fixed top-20 right-4 z-50 space-y-2"></div>