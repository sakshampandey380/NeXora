<?php
session_start();
require_once __DIR__ . '/config/db.php';

echo "<h2>Debug Information:</h2>";

// Check database connection
echo "<p><strong>Database Connection:</strong> ";
if ($conn) {
    echo "✅ Connected</p>";
} else {
    echo "❌ Failed - " . mysqli_connect_error() . "</p>";
    exit();
}

// Check if session is working
echo "<p><strong>Session Status:</strong> ";
if (isset($_SESSION['user_id'])) {
    echo "✅ User logged in (ID: " . $_SESSION['user_id'] . ")</p>";
    echo "<p><a href='products/index.php'>Go to Products</a></p>";
} else {
    echo "❌ No user session</p>";
}

// Check users in database
echo "<p><strong>Users in Database:</strong></p>";
$result = $conn->query("SELECT id, name, email, phone FROM users");
if ($result && $result->num_rows > 0) {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['email']) . "</td>";
        echo "<td>" . htmlspecialchars($row['phone']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "❌ No users found in database";
}

echo "<p><br><a href='auth/user/login.php'>Go to Login</a></p>";
?>
