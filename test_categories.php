<?php
require_once __DIR__ . '/config/db.php';

echo "Testing database connection...\n";
echo "Connection status: " . ($conn->connect_error ? "Error: " . $conn->connect_error : "Connected") . "\n\n";

$result = $conn->query("SELECT * FROM categories");
if ($result) {
    echo "Categories found: " . $result->num_rows . "\n";
    while ($row = $result->fetch_assoc()) {
        echo "ID: " . $row['id'] . " - Name: " . $row['name'] . "\n";
    }
} else {
    echo "Error fetching categories: " . $conn->error . "\n";
}
?>
