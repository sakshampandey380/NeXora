<?php
session_start();
require_once __DIR__ . '/../config/db.php';

/* AUTH CHECK */
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/user/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

/* FETCH USER */
$user = $conn->query("SELECT name,email,phone FROM users WHERE id=$user_id")->fetch_assoc();

/* FETCH SAVED ADDRESS */
$addr = $conn->query("SELECT * FROM user_addresses WHERE user_id=$user_id")->fetch_assoc();

/* SAVE / UPDATE ADDRESS */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $full_name = $_POST['full_name'];
    $address   = $_POST['address'];
    $country   = $_POST['country'];
    $phone     = $user['phone'];
    $email     = $user['email'];

    if ($addr) {
        $stmt = $conn->prepare("
            UPDATE user_addresses 
            SET full_name=?, address_line=?, country_pincode=?
            WHERE user_id=?
        ");
        $stmt->bind_param("sssi", $full_name,$address,$country,$user_id);
    } else {
        $stmt = $conn->prepare("
            INSERT INTO user_addresses 
            (user_id,full_name,address_line,country_pincode,phone,email)
            VALUES (?,?,?,?,?,?)
        ");
        $stmt->bind_param("isssss",
            $user_id,$full_name,$address,$country,$phone,$email
        );
    }
    $stmt->execute();
    $stmt->close();


/* ------------------ SAVE ORDER ------------------ */

$pid = isset($_GET['pid']) ? intval($_GET['pid']) : 0;

if(!$pid){
    die("Product ID missing");
}

/* GET PRODUCT */
$product = $conn->query("
SELECT id,name,offer_price,image
FROM products
WHERE id=$pid
")->fetch_assoc();

if(!$product){
    die("Product not found");
}

$price = $product['offer_price'];
$total = $price;

/* CREATE ORDER */
$conn->query("
INSERT INTO orders (user_id,total_price,status,admin_seen)
VALUES ($user_id,$total,'Pending',0)
");

$order_id = $conn->insert_id;

/* INSERT ORDER ITEM */
$stmt = $conn->prepare("
INSERT INTO order_items
(order_id,product_id,product_name,product_price,product_image,quantity)
VALUES (?,?,?,?,?,?)
");

$qty = 1;

$stmt->bind_param(
"iisssi",
$order_id,
$product['id'],
$product['name'],
$price,
$product['image'],
$qty
);

$stmt->execute();

header("Location: buy-now.php?confirmed=1");
exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Confirm Order | ShopSphere</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<!-- YOUR ORDER ANIMATION FILES -->
<link rel="stylesheet" href="../assets/css/order_ani.css">
<script src="../assets/js/order_script.js" defer></script>

<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',sans-serif;}

body{
    min-height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
    background:linear-gradient(135deg,#0f172a,#020617);
    color:#fff;
    position:relative;
}

/* 🔙 BACK TO CART BUTTON */
.back-cart{
    position:absolute;
    top:25px;
    left:25px;
    padding:12px 20px;
    border-radius:30px;
    text-decoration:none;
    font-weight:700;
    color:#38bdf8;
    border:1px solid rgba(56,189,248,.6);
    background:rgba(56,189,248,.1);
    transition:.35s ease;
}
.back-cart:hover{
    background:#38bdf8;
    color:#020617;
    box-shadow:0 0 35px rgba(56,189,248,.9);
    transform:translateX(-6px);
}

/* CARD */
.order-card{
    width:460px;
    background:#020617;
    padding:35px;
    border-radius:22px;
    box-shadow:0 40px 90px rgba(0,0,0,.7);
    animation:fadeUp .8s ease;
}

@keyframes fadeUp{
    from{opacity:0;transform:translateY(40px)}
    to{opacity:1;transform:translateY(0)}
}

h2{
    text-align:center;
    margin-bottom:25px;
    font-size:28px;
    color:#38bdf8;
    text-shadow:0 0 20px rgba(56,189,248,.7);
}

/* INPUT BLOCK */
.block{margin-bottom:18px;}
.block label{
    font-size:13px;
    color:#94a3b8;
    margin-bottom:6px;
    display:block;
}
.block input{
    width:100%;
    padding:14px;
    border-radius:12px;
    border:none;
    background:#020617;
    color:#fff;
    outline:none;
    box-shadow:inset 0 0 0 1px rgba(255,255,255,.08);
}
.block input:focus{box-shadow:0 0 0 2px #38bdf8;}
.readonly{opacity:.8}

/* BUTTONS */
.actions{
    display:flex;
    gap:14px;
    margin-top:22px;
}

.confirm{
    flex:1;
    padding:14px;
    border:none;
    border-radius:30px;
    background:linear-gradient(135deg,#22c55e,#16a34a);
    color:#020617;
    font-weight:900;
    cursor:pointer;
    transition:.35s;
}
.confirm:hover{
    transform:scale(1.08);
    box-shadow:0 0 45px rgba(34,197,94,.9);
}

.edit{
    padding:14px 18px;
    border-radius:30px;
    background:#2563eb;
    color:#fff;
    text-decoration:none;
    font-weight:700;
}
.edit:hover{
    box-shadow:0 0 30px rgba(37,99,235,.9);
}

/* FINAL ANIMATION */
.order-ani{display:none;}
.show-ani{display:block;}
</style>
</head>

<body>


<a href="view.php" class="back-cart">⬅ Back to Cart</a>

<?php if(isset($_GET['confirmed'])): ?>

<div class="order-ani show-ani">
    <?php include "../assets/indexes/order_index/order_index.html"; ?>
</div>

<?php else: ?>

<div class="order-card">
    <h2>📦 Delivery Address</h2>

    <form method="POST">

        <div class="block">
            <label>Full Name</label>
            <input name="full_name" required
            value="<?= htmlspecialchars($addr['full_name'] ?? $user['name']) ?>">
        </div>

        <div class="block">
            <label>State, Colony, Street</label>
            <input name="address" required
            value="<?= htmlspecialchars($addr['address_line'] ?? '') ?>">
        </div>

        <div class="block">
            <label>Country & Pincode</label>
            <input name="country" required
            value="<?= htmlspecialchars($addr['country_pincode'] ?? '') ?>">
        </div>

        <div class="block">
            <label>Phone</label>
            <input class="readonly" readonly value="<?= $user['phone'] ?>">
        </div>

        <div class="block">
            <label>Email</label>
            <input class="readonly" readonly value="<?= $user['email'] ?>">
        </div>

        <div class="actions">
            <button name="confirm_hover" class="confirm" type="submit">Confirm Order</button>
            <?php if($addr): ?>
                <a href="edit.php" class="edit">✏ Edit</a>

            <?php endif; ?>
        </div>

    </form>
</div>


<?php endif; ?>

</body>
</html>