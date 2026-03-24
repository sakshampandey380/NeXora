<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if(!isset($_SESSION['user_id'])){
header("Location: ../auth/user/login.php");
exit;
}

$user_id = $_SESSION['user_id'];

/* FETCH USER ADDRESS */

$addressQuery = $conn->query("
SELECT * FROM user_addresses 
WHERE user_id = $user_id
");

$address = $addressQuery->fetch_assoc();

/* FETCH USER DATA */
$stmt = $conn->prepare("
    SELECT u.name, u.email, u.phone, u.profile_image, c.name AS category
    FROM users u
    LEFT JOIN categories c ON u.fav_category = c.id
    WHERE u.id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>User Profile | ShopSphere</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
/* ===== RESET ===== */
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Segoe UI',sans-serif;
}

/* ===== BACKGROUND ===== */
body{
    min-height:100vh;
    display:flex;
    justify-content:center;
    align-items:flex-start;
    padding-top:60px;
    background:linear-gradient(135deg,#0f172a,#020617);
    color:#fff;
    overflow-y:auto;
}

/* FLOATING GLOW */
body::before{
    content:'';
    position:absolute;
    width:450px;
    height:450px;
    background:#38bdf8;
    filter:blur(160px);
    opacity:.25;
    top:-120px;
    left:-120px;
}
body::after{
    content:'';
    position:absolute;
    width:450px;
    height:450px;
    background:#22c55e;
    filter:blur(160px);
    opacity:.25;
    bottom:-120px;
    right:-120px;
}

/* ===== CARD ===== */
.profile-card{
    position:relative;
    z-index:2;
    width:420px;
    background:rgba(2,6,23,.95);
    border-radius:22px;
    padding:35px;
    text-align:center;
    box-shadow:0 40px 90px rgba(0,0,0,.6);
    animation:fadeUp .8s ease;
    max-height:none;
}

@keyframes fadeUp{
    from{opacity:0;transform:translateY(40px)}
    to{opacity:1;transform:translateY(0)}
}

/* ===== PROFILE IMAGE ===== */
.profile-img{
    width:130px;
    height:130px;
    margin:0 auto 15px;
    border-radius:50%;
    overflow:hidden;
    border:4px solid #38bdf8;
    box-shadow:0 0 35px rgba(56,189,248,.8);
    transition:.45s ease;
}

.profile-img img{
    width:100%;
    height:100%;
    object-fit:cover;
    transition:.45s ease;
}

.profile-img:hover img{
    transform:scale(1.25);
}

/* ===== NAME ===== */
.profile-card h2{
    margin-top:10px;
    font-size:26px;
    color:#fff;
}

.tag{
    margin-top:4px;
    font-size:13px;
    color:#38bdf8;
    letter-spacing:.5px;
}

/* ===== INFO ===== */
.info{
    margin-top:25px;
    text-align:left;
}

.info div{
    margin-bottom:14px;
    padding:12px 14px;
    background:#020617;
    border-radius:12px;
    font-size:14px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    box-shadow:inset 0 0 0 1px rgba(255,255,255,.05);
}

.info span{
    color:#94a3b8;
}

/* ===== BUTTONS ===== */
.actions{

margin-top:30px;

display:grid;

grid-template-columns:repeat(2,1fr);

gap:12px;

width:100%;

}

.actions a{

text-align:center;

padding:12px;

border-radius:10px;

font-weight:600;

text-decoration:none;

color:white;

transition:.3s;

font-size:14px;

}

.btn{

padding:10px 18px;

border-radius:25px;

background:linear-gradient(135deg,#38bdf8,#2563eb);

color:white;

text-decoration:none;

font-weight:bold;

transition:.3s;

}

.btn:hover{

transform:scale(1.05);

box-shadow:0 0 20px rgba(56,189,248,.7);

}

/* UPDATE */
.update{
    background:linear-gradient(135deg,#38bdf8,#2563eb);
    color:#fff;
    box-shadow:0 0 25px rgba(56,189,248,.6);
}
.update:hover{
    transform:scale(1.05);
    box-shadow:0 0 45px rgba(56,189,248,.9);
}

/* CART */
.cart{
    background:linear-gradient(135deg,#ff8a00,#ff3d00);
    color:#fff;
    box-shadow:0 0 25px rgba(255,138,0,.6);
}
.cart:hover{
    transform:scale(1.05);
    box-shadow:0 0 45px rgba(255,138,0,.9);
}

/* MY ORDERS */
.my-orders{
    background:linear-gradient(135deg,#ff8a00,#ff3d00);
    color:#fff;
    box-shadow:0 0 25px rgba(17, 145, 184, 0.6);
}
.my-orders:hover{
    transform:scale(1.05);
    box-shadow:0 0 45px rgba(205, 16, 54, 0.9);
}

/* FORGOT PASSWORD */
.forgot{
    background:linear-gradient(135deg,#a855f7,#ec4899);
    color:#fff;
    box-shadow:0 0 25px rgba(236,72,153,.6);
}
.forgot:hover{
    transform:scale(1.08);
    box-shadow:0 0 50px rgba(236,72,153,.9);
}

/* LOGOUT */
.logout{
    background:transparent;
    color:#f87171;
    border:1px solid rgba(248,113,113,.5);
}
.logout:hover{
    background:#f87171;
    color:#020617;
    box-shadow:0 0 35px rgba(248,113,113,.8);
}

/* BACK TO PRODUCTS BUTTON */
.back-products{
    position:absolute;
    top:25px;
    left:25px;
    padding:12px 22px;
    border-radius:30px;
    text-decoration:none;
    font-weight:700;
    font-size:14px;
    color:#38bdf8;
    border:2px solid #38bdf8;
    background:rgba(56,189,248,.08);
    backdrop-filter:blur(8px);
    transition:.35s ease;
    box-shadow:0 0 25px rgba(56,189,248,.35);
    z-index:5;
}

.back-products:hover{
    background:#38bdf8;
    color:#020617;
    transform:translateX(-6px) scale(1.05);
    box-shadow:0 0 45px rgba(56,189,248,.9);
}

/* ===== BEAUTIFUL ADDRESS CARD ===== */

.address-box{

margin-top:28px;

padding:24px;

border-radius:18px;

background:rgba(255,255,255,.06);

backdrop-filter:blur(12px);

border:1px solid rgba(255,255,255,.08);

box-shadow:0 15px 40px rgba(0,0,0,.6);

text-align:center;

position:relative;

overflow:hidden;

animation:addressFade .6s ease;

}

/* subtle glow border */

.address-box::before{

content:"";

position:absolute;

top:-50%;

left:-50%;

width:200%;

height:200%;

background:conic-gradient(
transparent,
rgba(56,189,248,.4),
transparent,
rgba(34,197,94,.4),
transparent
);

animation:spin 8s linear infinite;

opacity:.35;

}

.address-box::after{

content:"";

position:absolute;

inset:2px;

border-radius:16px;

background:rgba(2,6,23,.95);

z-index:1;

}

.address-box *{
position:relative;
z-index:2;
}

/* title */

.address-box h3{

font-size:18px;

margin-bottom:12px;

color:#38bdf8;

letter-spacing:.4px;

text-shadow:0 0 12px rgba(56,189,248,.6);

}

/* address text */

.address-box p{

margin:6px 0;

font-size:14px;

color:#e2e8f0;

line-height:1.4;

}

/* edit button */

.edit-btn{

display:inline-block;

margin-top:15px;

padding:10px 22px;

border-radius:25px;

background:linear-gradient(135deg,#38bdf8,#2563eb);

color:white;

text-decoration:none;

font-weight:600;

transition:.35s;

box-shadow:0 0 20px rgba(56,189,248,.5);

}

.edit-btn:hover{

transform:translateY(-3px) scale(1.05);

box-shadow:0 0 35px rgba(56,189,248,.9);

}

/* animations */

@keyframes spin{

0%{ transform:rotate(0deg); }

100%{ transform:rotate(360deg); }

}

@keyframes addressFade{

from{
opacity:0;
transform:translateY(20px);
}

to{
opacity:1;
transform:translateY(0);
}

}
</style>
</head>

<body>
<a href="../products/product_page.php" class="back-products">
    ⬅ Back to Products
</a>
<div class="profile-card">

    <div class="profile-img">
        <img src="../uploads/profile/<?= htmlspecialchars($user['profile_image'] ?? 'default.png') ?>">
    </div>

    <h2><?= htmlspecialchars($user['name']) ?></h2>
    <div class="tag">ShopSphere User</div>

    <div class="info">
        <div><span>Email</span><?= htmlspecialchars($user['email']) ?></div>
        <div><span>Phone</span><?= htmlspecialchars($user['phone']) ?></div>
        <div><span>Favorite Category</span><?= htmlspecialchars($user['category'] ?? 'Not selected') ?></div>
    </div>

    <div class="address-box">

        <h3>📍 Delivery Address</h3>

        <?php if(!empty($address)){ ?>

        <p><b>Address:</b> <?= htmlspecialchars($address['address_line']) ?></p>

        <p><b>Country / Pincode:</b> <?= htmlspecialchars($address['country_pincode']) ?></p>

        <a href="edit-address.php" class="edit-btn">✏ Edit Address</a>

        <?php } else { ?>

        <p>No address saved yet.</p>

        <a href="edit-address.php" class="edit-btn">➕ Add Address</a>

        <?php } ?>

    </div>

    <div class="actions">
        <a href="update-profile.php" class="update">✏️ Update Profile</a>
        <a href="../cart/view.php" class="cart">🛒 View Cart</a>
        <a href="../auth/user/forgot-password.php" class="forgot">🔐 Reset Password</a>
        <a href="../auth/user/logout.php"
           class="logout"
           onclick="return confirm('Are you sure you want to logout?')">
           🚪 Logout
        </a>
        <a href="my-orders.php" class="my-orders">📦 My Orders</a>
    </div>

</div>

</body>
</html>
