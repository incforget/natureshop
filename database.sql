-- Database schema for Naturo BD clone e-commerce site

CREATE DATABASE IF NOT EXISTS naturo_clone;
USE naturo_clone;

-- Categories table with icon support
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    icon_type ENUM('fontawesome', 'bootstrap', 'sticker', 'svg') DEFAULT 'fontawesome',
    icon_class VARCHAR(100),
    sticker_url VARCHAR(255),
    svg_code TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Products table with enhanced features
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    original_price DECIMAL(10,2),
    image VARCHAR(255),
    category VARCHAR(100),
    category_id INT,
    stock INT DEFAULT 0,
    rating DECIMAL(3,2) DEFAULT 0,
    reviews INT DEFAULT 0,
    badge VARCHAR(50),
    features JSON,
    size VARCHAR(100),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

-- Users table (for future login)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    phone VARCHAR(20) UNIQUE NOT NULL,
    address TEXT,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Banners table for homepage banners
CREATE TABLE banners (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    image VARCHAR(255),
    link VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    position INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Promo codes table for discount management
CREATE TABLE promo_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    discount_type ENUM('percentage', 'fixed') NOT NULL,
    discount_value DECIMAL(10,2) NOT NULL,
    min_order_amount DECIMAL(10,2) DEFAULT 0,
    max_discount DECIMAL(10,2) DEFAULT NULL,
    usage_limit INT DEFAULT NULL,
    used_count INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    expiry_date DATE DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Orders table (simplified)
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    total DECIMAL(10,2) NOT NULL,
    address TEXT,
    delivery_area VARCHAR(50),
    delivery_charge DECIMAL(10,2) DEFAULT 0,
    -- Promo and discount info
    promo_code VARCHAR(50) DEFAULT NULL,
    discount_amount DECIMAL(10,2) DEFAULT 0,
    -- Enhanced order management
    tracking_number VARCHAR(100) DEFAULT NULL,
    estimated_delivery_date DATE DEFAULT NULL,
    cancellation_reason VARCHAR(255) DEFAULT NULL,
    status VARCHAR(50) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Order items
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT,
    product_id INT,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Additional indexes to improve query performance
-- Indexes for product lookups and filters
CREATE INDEX idx_products_category_id ON products(category_id);
CREATE INDEX idx_products_is_active ON products(is_active);

-- Indexes for orders and order lookups
CREATE INDEX idx_orders_user_id ON orders(user_id);
CREATE INDEX idx_orders_created_at ON orders(created_at);

-- Indexes for order items
CREATE INDEX idx_order_items_order_id ON order_items(order_id);
CREATE INDEX idx_order_items_product_id ON order_items(product_id);

-- Insert categories with robust icon system
INSERT INTO categories (name, slug, description, icon_type, icon_class, sticker_url) VALUES
('Honey', 'honey', 'Pure natural honey varieties', 'fontawesome', 'fas fa-jar', NULL),
('Spices', 'spices', 'Premium quality spices and seasonings', 'fontawesome', 'fas fa-pepper-hot', NULL),
('Oils', 'oils', 'Natural and essential oils', 'fontawesome', 'fas fa-oil-can', NULL),
('Herbs', 'herbs', 'Fresh and dried herbs', 'fontawesome', 'fas fa-leaf', NULL),
('Tea & Coffee', 'tea', 'Premium tea and coffee products', 'fontawesome', 'fas fa-mug-hot', NULL),
('Grains & Seeds', 'grains', 'Healthy grains and seeds', 'fontawesome', 'fas fa-seedling', NULL),
('Skin Care', 'skincare', 'Natural skin care products', 'fontawesome', 'fas fa-spa', NULL),
('Health Supplements', 'health', 'Ayurvedic and health supplements', 'fontawesome', 'fas fa-heart', NULL);

-- Insert sample products with enhanced data
INSERT INTO products (name, slug, description, price, original_price, image, category, stock, rating, reviews, badge, features, size) VALUES
('Pure Organic Honey', 'pure-organic-honey', '100% pure organic honey collected from the finest apiaries in Bangladesh. Rich in nutrients and natural enzymes.', 450, 550, 'honey1.jpg', 'honey', 50, 4.8, 124, 'Best Seller', '["100% Organic", "No additives", "Rich in antioxidants", "Natural sweetness"]', '500g'),
('Turmeric Powder Premium', 'turmeric-powder-premium', 'Premium quality turmeric powder with high curcumin content. Perfect for cooking and medicinal purposes.', 180, 220, 'turmeric.jpg', 'spices', 100, 4.6, 89, 'Organic', '["High curcumin", "Pure ground", "No preservatives", "Anti-inflammatory"]', '100g'),
('Black Cumin Seed Oil', 'black-cumin-seed-oil', 'Cold-pressed black cumin seed oil, known for its numerous health benefits and therapeutic properties.', 680, 850, 'cumin-oil.jpg', 'oils', 30, 4.9, 67, 'Premium', '["Cold pressed", "Pure extract", "Rich in antioxidants", "Immune booster"]', '250ml'),
('Fresh Ginger', 'fresh-ginger', 'Fresh, locally grown ginger root with intense flavor and numerous health benefits.', 120, 150, 'ginger.jpg', 'herbs', 75, 4.5, 45, 'Fresh', '["Freshly harvested", "Intense flavor", "Anti-inflammatory", "Digestive aid"]', '250g'),
('Cinnamon Sticks', 'cinnamon-sticks', 'High-quality cinnamon sticks with aromatic flavor and numerous health benefits.', 250, 300, 'cinnamon.jpg', 'spices', 60, 4.7, 92, 'Popular', '["Aromatic", "Pure bark", "Blood sugar support", "Antioxidant rich"]', '50g'),
('Aloe Vera Gel', 'aloe-vera-gel', 'Pure aloe vera gel for skin care and health. 100% natural with no added chemicals.', 320, 400, 'aloe-vera.jpg', 'herbs', 40, 4.8, 156, 'Natural', '["100% Natural", "Skin care", "Soothing", "No chemicals"]', '100ml'),
('Coriander Seeds', 'coriander-seeds', 'Premium quality coriander seeds with rich aroma and essential oils.', 95, 120, 'coriander.jpg', 'spices', 85, 4.4, 38, 'Value', '["Aromatic", "Fresh seeds", "Digestive aid", "Rich in oils"]', '100g'),
('Neem Oil', 'neem-oil', 'Pure neem oil extracted from neem seeds. Excellent for skin care and as natural pesticide.', 420, 520, 'neem-oil.jpg', 'oils', 25, 4.6, 71, 'Traditional', '["Pure extract", "Skin care", "Natural pesticide", "Antibacterial"]', '100ml'),
('Sundarban Honey', 'sundarban-honey', 'Wild honey collected from the Sundarban mangrove forests. Unique flavor with medicinal properties.', 550, 650, 'sundarban-honey.jpg', 'honey', 35, 4.9, 203, 'Exclusive', '["Wild collected", "Medicinal", "Unique flavor", "Rare"]', '500g'),
('Mustard Oil', 'mustard-oil', 'Pure mustard oil extracted from high-quality mustard seeds. Essential for Bengali cuisine.', 280, 350, 'mustard-oil.jpg', 'oils', 90, 4.5, 112, 'Traditional', '["Cold pressed", "Traditional", "Rich flavor", "High smoke point"]', '500ml'),
('Holy Basil (Tulsi)', 'holy-basil-tulsi', 'Sacred holy basil leaves known for their spiritual and medicinal significance in Ayurveda.', 85, 110, 'tulsi.jpg', 'herbs', 120, 4.7, 88, 'Ayurvedic', '["Sacred herb", "Medicinal", "Aromatic", "Stress relief"]', '50g'),
('Red Chili Powder', 'red-chili-powder', 'Hot and spicy red chili powder made from premium quality dried red chilies.', 140, 180, 'chili.jpg', 'spices', 70, 4.3, 156, 'Hot', '["Extra hot", "Pure ground", "Vibrant color", "Rich flavor"]', '100g'),
('Coconut Oil', 'coconut-oil', 'Virgin coconut oil extracted from fresh coconut meat. Perfect for cooking and skin care.', 380, 450, 'coconut-oil.jpg', 'oils', 55, 4.8, 194, 'Versatile', '["Virgin", "Cold pressed", "Multi-purpose", "Organic"]', '500ml'),
('Mint Leaves', 'mint-leaves', 'Fresh mint leaves with cooling properties. Ideal for beverages and culinary uses.', 45, 60, 'mint.jpg', 'herbs', 200, 4.4, 67, 'Refreshing', '["Fresh", "Aromatic", "Cooling", "Digestive aid"]', '50g'),
('Cardamom Pods', 'cardamom-pods', 'Premium green cardamom pods with intense aroma and flavor. The queen of spices.', 420, 500, 'cardamom.jpg', 'spices', 45, 4.9, 143, 'Premium', '["Premium quality", "Intense aroma", "Exotic flavor", "Aromatic"]', '50g'),
('Garlic Paste', 'garlic-paste', 'Fresh garlic paste made from premium quality garlic cloves. Convenient cooking ingredient.', 95, 120, 'garlic-paste.jpg', 'herbs', 80, 4.2, 89, 'Convenient', '["Ready to use", "Fresh", "No preservatives", "Convenient"]', '200g'),
('Sesame Oil', 'sesame-oil', 'Pure sesame oil with nutty flavor. Essential for Asian cuisine and traditional medicines.', 320, 400, 'sesame-oil.jpg', 'oils', 65, 4.6, 102, 'Nutritious', '["Cold pressed", "Nutty flavor", "Rich in nutrients", "Traditional"]', '500ml'),
('Curry Leaves', 'curry-leaves', 'Fresh curry leaves with distinctive aroma. Essential for South Indian cuisine.', 55, 75, 'curry-leaves.jpg', 'herbs', 150, 4.3, 76, 'Aromatic', '["Fresh", "Aromatic", "Culinary essential", "Flavorful"]', '50g'),
('Cumin Seeds', 'cumin-seeds', 'High-quality cumin seeds with warm, earthy flavor. Staple spice in Bangladeshi cuisine.', 125, 160, 'cumin.jpg', 'spices', 95, 4.5, 134, 'Essential', '["Earthy flavor", "Aromatic", "Essential spice", "Premium"]', '100g'),
('Lemon Grass', 'lemon-grass', 'Fresh lemon grass with citrusy aroma. Perfect for teas and Asian dishes.', 75, 95, 'lemongrass.jpg', 'herbs', 110, 4.4, 58, 'Citrusy', '["Citrus aroma", "Fresh", "Medicinal", "Aromatic"]', '100g'),
('Almond Oil', 'almond-oil', 'Sweet almond oil for skin care and hair care. Rich in vitamins and minerals.', 750, 900, 'almond-oil.jpg', 'oils', 40, 4.8, 167, 'Beauty', '["Cold pressed", "Skin care", "Hair care", "Rich in vitamins"]', '100ml'),
('Fenugreek Seeds', 'fenugreek-seeds', 'Bitter fenugreek seeds with medicinal properties. Used in cooking and traditional medicine.', 110, 140, 'fenugreek.jpg', 'spices', 85, 4.1, 92, 'Medicinal', '["Medicinal", "Bitter taste", "Traditional", "Health benefits"]', '100g'),
('Spinach Powder', 'spinach-powder', 'Dried spinach powder rich in iron and vitamins. Perfect for smoothies and cooking.', 165, 200, 'spinach.jpg', 'herbs', 70, 4.6, 81, 'Healthy', '["Nutrient rich", "Iron source", "Easy to use", "Healthy"]', '100g'),
('Olive Oil Extra Virgin', 'olive-oil-extra-virgin', 'Premium extra virgin olive oil imported from Mediterranean regions. Perfect for healthy cooking.', 880, 1100, 'olive-oil.jpg', 'oils', 30, 4.9, 224, 'Imported', '["Extra virgin", "Imported", "Heart healthy", "Premium quality"]', '500ml'),
('Green Tea Premium', 'green-tea-premium', 'Premium green tea leaves from Sylhet gardens. Rich in antioxidants and delicate flavor.', 280, 350, 'green-tea.jpg', 'tea', 85, 4.7, 145, 'Premium', '["Antioxidant rich", "Premium quality", "Delicate flavor", "Organic"]', '100g'),
('Black Tea Classic', 'black-tea-classic', 'Traditional black tea with robust flavor. Perfect morning beverage.', 180, 220, 'black-tea.jpg', 'tea', 120, 4.5, 189, 'Popular', '["Robust flavor", "Traditional", "Energy boost", "Aromatic"]', '100g'),
('Lemon Ginger Tea', 'lemon-ginger-tea', 'Refreshing blend of lemon and ginger. Soothing and invigorating.', 220, 280, 'lemon-ginger-tea.jpg', 'tea', 95, 4.6, 112, 'Refreshing', '["Refreshing", "Soothing", "Natural ingredients", "Digestive aid"]', '100g'),
('Mint Tea', 'mint-tea', 'Cooling mint tea perfect for relaxation and digestion.', 195, 250, 'mint-tea.jpg', 'tea', 110, 4.4, 87, 'Soothing', '["Cooling", "Relaxing", "Digestive aid", "Natural"]', '100g'),
('Coffee Beans Premium', 'coffee-beans-premium', 'Premium coffee beans with rich aroma and bold flavor.', 450, 550, 'coffee.jpg', 'tea', 60, 4.8, 203, 'Premium', '["Bold flavor", "Rich aroma", "Premium beans", "Freshly roasted"]', '250g'),
('Chamomile Tea', 'chamomile-tea', 'Calming chamomile tea perfect for bedtime relaxation.', 240, 300, 'chamomile-tea.jpg', 'tea', 75, 4.6, 134, 'Relaxing', '["Calming", "Sleep aid", "Natural", "Gentle"]', '100g'),
('Basmati Rice Premium', 'basmati-rice-premium', 'Premium basmati rice with long grains and aromatic fragrance.', 320, 400, 'basmati-rice.jpg', 'grains', 150, 4.7, 178, 'Premium', '["Long grain", "Aromatic", "Premium quality", "Fluffy texture"]', '1kg'),
('Flax Seeds', 'flax-seeds', 'Nutrient-rich flax seeds high in omega-3 fatty acids.', 165, 200, 'flax-seeds.jpg', 'grains', 95, 4.5, 92, 'Healthy', '["Omega-3 rich", "High fiber", "Nutrient dense", "Heart healthy"]', '250g'),
('Chia Seeds', 'chia-seeds', 'Superfood chia seeds packed with protein, fiber, and antioxidants.', 280, 350, 'chia-seeds.jpg', 'grains', 70, 4.8, 156, 'Superfood', '["Superfood", "High protein", "Antioxidant rich", "Versatile"]', '250g'),
('Quinoa Organic', 'quinoa-organic', 'Organic quinoa grains complete protein source for healthy meals.', 420, 520, 'quinoa.jpg', 'grains', 55, 4.6, 124, 'Organic', '["Complete protein", "Organic", "Gluten free", "Nutritious"]', '500g'),
('Sunflower Seeds', 'sunflower-seeds', 'Crunchy sunflower seeds rich in vitamin E and healthy fats.', 125, 160, 'sunflower-seeds.jpg', 'grains', 130, 4.3, 78, 'Healthy', '["Vitamin E rich", "Healthy fats", "Crunchy", "Snack ready"]', '250g'),
('Rose Water', 'rose-water', 'Pure rose water for skin toning and refreshing.', 180, 240, 'rose-water.jpg', 'skincare', 85, 4.7, 145, 'Natural', '["Natural toner", "Refreshing", "Hydrating", "Pure extract"]', '200ml'),
('Face Pack Multani Mitti', 'face-pack-multani-mitti', 'Traditional multani mitti face pack for deep cleansing.', 95, 130, 'multani-mitti.jpg', 'skincare', 110, 4.4, 98, 'Traditional', '["Deep cleansing", "Traditional", "Natural clay", "Purifying"]', '100g'),
('Sandalwood Powder', 'sandalwood-powder', 'Pure sandalwood powder for skin brightening and soothing.', 280, 350, 'sandalwood.jpg', 'skincare', 65, 4.8, 167, 'Premium', '["Skin brightening", "Soothing", "Premium quality", "Natural"]', '50g'),
('Ubtan Face Pack', 'ubtan-face-pack', 'Traditional ubtan blend for glowing and healthy skin.', 150, 200, 'ubtan.jpg', 'skincare', 90, 4.5, 123, 'Traditional', '["Glowing skin", "Traditional blend", "Natural herbs", "Nourishing"]', '100g'),
('Ashwagandha Powder', 'ashwagandha-powder', 'Premium ashwagandha powder for stress relief and vitality.', 320, 400, 'ashwagandha.jpg', 'health', 75, 4.7, 189, 'Ayurvedic', '["Stress relief", "Energy boost", "Traditional herb", "Pure powder"]', '100g'),
('Triphala Tablets', 'triphala-tablets', 'Traditional triphala tablets for digestive health and detoxification.', 245, 300, 'triphala.jpg', 'health', 95, 4.5, 134, 'Traditional', '["Digestive health", "Detox", "Traditional formula", "Natural"]', '60 tablets'),
('Amla Powder', 'amla-powder', 'Rich vitamin C amla powder for immunity and hair health.', 185, 240, 'amla.jpg', 'health', 105, 4.6, 156, 'Immunity', '["Vitamin C rich", "Immunity booster", "Hair health", "Natural"]', '100g'),
('Shilajit Pure', 'shilajit-pure', 'Pure Himalayan shilajit for strength and vitality.', 680, 850, 'shilajit.jpg', 'health', 35, 4.8, 201, 'Premium', '["Strength boost", "Vitality", "Himalayan source", "Pure resin"]', '50g'),
('Turmeric Capsules', 'turmeric-capsules', 'Standardized turmeric capsules with curcumin for anti-inflammatory benefits.', 380, 480, 'turmeric-capsules.jpg', 'health', 80, 4.4, 145, 'Effective', '["Anti-inflammatory", "Standardized", "Easy to take", "Effective"]', '60 capsules'),
('Wheatgrass Powder', 'wheatgrass-powder', 'Nutrient-dense wheatgrass powder for detox and nutrition.', 290, 360, 'wheatgrass.jpg', 'health', 70, 4.5, 112, 'Superfood', '["Nutrient dense", "Detox", "Green superfood", "Alkalizing"]', '100g'),
('Ginger Garlic Paste', 'ginger-garlic-paste', 'Fresh ginger garlic paste for cooking and health benefits.', 85, 110, 'ginger-garlic-paste.jpg', 'health', 125, 4.3, 89, 'Convenient', '["Ready to use", "Immunity boost", "Fresh ingredients", "Convenient"]', '200g');


-- Insert sample banner data
INSERT INTO banners (title, description, image, link, position) VALUES
('Welcome to NatureBD', 'Discover the finest natural products from Bangladesh', 'banner1.jpg', 'products.php', 1),
('Special Offer', 'Get 20% off on all honey products this week', 'banner2.jpg', 'products.php?category=honey', 2);

-- Insert sample promo codes
INSERT INTO promo_codes (code, description, discount_type, discount_value, min_order_amount, max_discount, usage_limit, expiry_date) VALUES
('WELCOME10', 'Welcome discount for new customers', 'percentage', 10.00, 500.00, 200.00, 100, '2025-12-31'),
('SAVE50', 'Save ৳50 on orders over ৳1000', 'fixed', 50.00, 1000.00, NULL, 50, '2025-12-31'),
('NATURAL20', '20% off on natural products', 'percentage', 20.00, 300.00, 300.00, 200, '2025-12-31'),
('FIRSTORDER', 'First order discount', 'percentage', 15.00, 200.00, 150.00, NULL, '2025-12-31'),
('FLASH100', 'Flash sale - ৳100 off', 'fixed', 100.00, 800.00, NULL, 25, '2025-10-31');




-- delivery, promo and address columns consolidated into `orders` CREATE TABLE above

-- Admin table for admin panel access
CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE,
    role ENUM('super_admin', 'admin', 'moderator') DEFAULT 'admin',
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default admin user (password: admin123)
INSERT INTO admins (username, password, email, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@naturebd.com', 'super_admin');

-- Update products with correct category_id based on category string
UPDATE products SET category_id = 1 WHERE category = 'honey';
UPDATE products SET category_id = 2 WHERE category = 'spices';
UPDATE products SET category_id = 3 WHERE category = 'oils';
UPDATE products SET category_id = 4 WHERE category = 'herbs';
UPDATE products SET category_id = 5 WHERE category = 'tea';
UPDATE products SET category_id = 6 WHERE category = 'grains';
UPDATE products SET category_id = 7 WHERE category = 'skincare';
UPDATE products SET category_id = 8 WHERE category = 'health';

-- Enhanced order management and category SVG support consolidated into CREATE TABLE definitions above

-- Create order status history table for tracking all status changes
CREATE TABLE order_status_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    old_status VARCHAR(50),
    new_status VARCHAR(50) NOT NULL,
    changed_by INT, -- admin user id who made the change
    change_reason TEXT, -- optional reason for status change
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (changed_by) REFERENCES admins(id)
);

-- Site settings table for configurable header/footer/site-wide values
CREATE TABLE site_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    `key` VARCHAR(100) NOT NULL UNIQUE,
    `value` TEXT,
    `type` ENUM('string','text','json','image') DEFAULT 'string',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default site settings (can be edited via admin panel)
INSERT INTO site_settings (`key`, `value`, `type`) VALUES
('site_name', 'Natural Shop', 'string'),
('site_tagline', 'Back to Nature', 'string'),
('contact_phone', '09639812525', 'string'),
('contact_email', 'NaturalShop@gmail.com', 'string'),
('contact_address', 'House 14, Road 20, Nikunja 2, Khilkhet, Dhaka 1229', 'text'),
('social_links', '{"facebook":"https://facebook.com/","whatsapp":"https://wa.me/","instagram":"https://instagram.com/","twitter":"https://twitter.com/"}', 'json'),
('logo', 'assets/images/logo.png', 'image'),
('copyright_text', '&copy; 2025 Natural Shop. All Rights Reserved.', 'text');
