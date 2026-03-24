<?php
require_once __DIR__ . '/config/db.php';

// Check if categories already exist
$check = $conn->query("SELECT COUNT(*) as count FROM categories");
$row = $check->fetch_assoc();

if ($row['count'] == 0) {
    // Insert categories
    $categories = ['Fruits', 'Vegetables', 'Cloths', 'Sports', 'Toys'];
    
    foreach ($categories as $cat) {
        $stmt = $conn->prepare("INSERT INTO categories (name) VALUES (?)");
        $stmt->bind_param("s", $cat);
        $stmt->execute();
        $stmt->close();
    }
    echo "Categories inserted successfully!";
} else {
    echo "Categories already exist. Count: " . $row['count'];
}

// Display all categories
$result = $conn->query("SELECT * FROM categories");
echo "\n\nAll categories:\n";
while ($row = $result->fetch_assoc()) {
    echo $row['id'] . " - " . $row['name'] . "\n";
}
?>
