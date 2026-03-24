<?php
session_start();
require_once __DIR__ . '/../config/db.php';

/* CHECK LOGIN */
if(!isset($_SESSION['user_id'])){
    header("Location: ../auth/user/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

/* GET PRODUCT ID */
if(!isset($_GET['pid'])){
    die("Product ID missing");
}

$product_id = intval($_GET['pid']);

/* CHECK PRODUCT EXISTS */
$product = $conn->query("SELECT id FROM products WHERE id=$product_id");

if($product->num_rows == 0){
    die("Product not found");
}

/* CHECK IF PRODUCT ALREADY IN CART */
$check = $conn->query("
SELECT id,quantity 
FROM cart 
WHERE user_id=$user_id AND product_id=$product_id
");

if($check->num_rows > 0){

    /* UPDATE QUANTITY */
    $row = $check->fetch_assoc();
    $qty = $row['quantity'] + 1;

    $conn->query("
    UPDATE cart 
    SET quantity=$qty 
    WHERE id=".$row['id']
    );

}else{

    /* INSERT NEW ITEM */
    $stmt = $conn->prepare("
    INSERT INTO cart (user_id,product_id,quantity)
    VALUES (?,?,?)
    ");

    $qty = 1;

    $stmt->bind_param("iii",$user_id,$product_id,$qty);
    $stmt->execute();
}

/* REDIRECT TO CART PAGE */
header("Location: view.php");
exit;

?>