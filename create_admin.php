<?php
include 'includes/config.php';

// Create admin table
$sql = "CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE,
    role ENUM('super_admin', 'admin', 'moderator') DEFAULT 'admin',
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);";

if ($conn->query($sql) === TRUE) {
    echo "Admin table created successfully!\n";

    // Insert default admin user
    $password_hash = password_hash('admin123', PASSWORD_DEFAULT);
    $sql = "INSERT IGNORE INTO admins (username, password, email, role) VALUES ('admin', '$password_hash', 'admin@naturebd.com', 'super_admin');";

    if ($conn->query($sql) === TRUE) {
        echo "Default admin user created!\n";
        echo "Username: admin\n";
        echo "Password: admin123\n";
    } else {
        echo "Error creating admin user: " . $conn->error . "\n";
    }
} else {
    echo "Error creating admin table: " . $conn->error . "\n";
}
?>