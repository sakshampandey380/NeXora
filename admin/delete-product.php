<?php
session_start();
require_once __DIR__ . '/../config/db.php';

/* OPTIONAL ADMIN CHECK */
// if (!isset($_SESSION['admin_id'])) {
//     header("Location: login.php");
//     exit;
// }

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: view-product.php");
    exit;
}

$id = (int) $_GET['id'];

/* FETCH PRODUCT IMAGE FIRST */
$stmt = $conn->prepare("SELECT image FROM products WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: view-product.php");
    exit;
}

$product = $result->fetch_assoc();
$imagePath = __DIR__ . '/../uploads/products/' . $product['image'];
$stmt->close();

/* DELETE IMAGE FILE */
if (!empty($product['image']) && file_exists($imagePath)) {
    unlink($imagePath);
}

/* DELETE PRODUCT FROM DB */
$stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->close();

/* REDIRECT BACK */
header("Location: view-product.php?deleted=1");
exit;