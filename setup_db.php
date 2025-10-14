<?php
include 'includes/config.php';

$sql = file_get_contents('database.sql');

// Split the SQL file into individual statements
$statements = array_filter(array_map('trim', explode(';', $sql)));

$success_count = 0;
$error_count = 0;

foreach ($statements as $statement) {
    if (!empty($statement)) {
        if ($conn->query($statement) === TRUE) {
            $success_count++;
        } else {
            echo "Error executing: " . $statement . "\n";
            echo "Error: " . $conn->error . "\n\n";
            $error_count++;
        }
    }
}

echo "Database setup completed!\n";
echo "Successful statements: $success_count\n";
echo "Errors: $error_count\n";
?>