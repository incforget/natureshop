# Naturo BD Clone

A PHP-based e-commerce website clone of Naturo BD, built with HTML, CSS (Tailwind), MySQL, and Vue JS.

## Features

- Product catalog with categories
- Best sellers section
- Responsive design with Tailwind CSS
- Vue JS for interactive cart functionality
- MySQL database for products and categories

## Setup Instructions

1. **Database Setup:**
   - Create a MySQL database named `naturo_clone`
   - Import the `database.sql` file to create tables and sample data

2. **Configuration:**
   - Update `includes/config.php` with your database credentials

3. **Web Server:**
   - Place the project in your web server's root directory (e.g., htdocs for XAMPP)
   - Ensure PHP and MySQL are running

4. **Access:**
   - Open `index.php` in your browser

## Technologies Used

- PHP 7+
- MySQL
- HTML5
- Tailwind CSS (CDN)
- Vue JS 3 (CDN)
- Font Awesome

## File Structure

```
naturo_clone/
├── index.php          # Main homepage
├── database.sql       # Database schema and sample data
├── includes/
│   ├── config.php     # Database configuration
│   └── functions.php  # Helper functions
├── js/
│   └── app.js         # Vue JS application
├── assets/            # Static assets
└── images/            # Product images
```