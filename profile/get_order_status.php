<?php
session_start();
require_once __DIR__ . '/../config/db.php';

$user_id = $_SESSION['user_id'];

$result = $conn->query("
SELECT id,status 
FROM orders
WHERE user_id=$user_id
");

$data = [];

while($row = $result->fetch_assoc()){
    $data[$row['id']] = $row['status'];
}

echo json_encode($data);