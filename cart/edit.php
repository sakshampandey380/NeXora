<?php
session_start();
require_once __DIR__ . '/../config/db.php';

/* AUTH CHECK */
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/user/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

/* FETCH USER + ADDRESS */
$user = $conn->query("SELECT name,email,phone FROM users WHERE id=$user_id")->fetch_assoc();
$addr = $conn->query("SELECT * FROM user_addresses WHERE user_id=$user_id")->fetch_assoc();

/* UPDATE ADDRESS */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $full_name = $_POST['full_name'];
    $address   = $_POST['address'];
    $country   = $_POST['country'];
    $phone     = $_POST['phone'];
    $email     = $_POST['email'];

    $stmt = $conn->prepare("
        UPDATE user_addresses 
        SET full_name=?, address_line=?, country_pincode=?, phone=?, email=?
        WHERE user_id=?
    ");
    $stmt->bind_param("sssssi",
        $full_name,$address,$country,$phone,$email,$user_id
    );
    $stmt->execute();
    $stmt->close();

    header("Location: buy-now.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Address | ShopSphere</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',sans-serif;}

body{
    min-height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
    background:linear-gradient(135deg,#052e16,#022c22);
    color:#fff;
    position:relative;
}

/* GLOW */
body::before{
    content:'';
    position:absolute;
    width:420px;height:420px;
    background:#22c55e;
    filter:blur(180px);
    opacity:.25;
    top:-120px;left:-120px;
}

/* CARD */
.edit-card{
    width:470px;
    background:#022c22;
    padding:38px;
    border-radius:24px;
    box-shadow:0 45px 100px rgba(0,0,0,.7);
    animation:slideUp .8s ease;
}

@keyframes slideUp{
    from{opacity:0;transform:translateY(50px)}
    to{opacity:1;transform:translateY(0)}
}

h2{
    text-align:center;
    margin-bottom:26px;
    font-size:28px;
    color:#22c55e;
    text-shadow:0 0 25px rgba(34,197,94,.8);
}

/* INPUTS */
.block{margin-bottom:18px;}
.block label{
    font-size:13px;
    color:#a7f3d0;
    margin-bottom:6px;
    display:block;
}
.block input{
    width:100%;
    padding:14px;
    border-radius:14px;
    border:none;
    background:#022c22;
    color:#fff;
    outline:none;
    box-shadow:inset 0 0 0 1px rgba(255,255,255,.08);
    transition:.3s;
}
.block input:focus{
    box-shadow:0 0 0 2px #22c55e;
}

/* BUTTONS */
.actions{
    display:flex;
    gap:14px;
    margin-top:26px;
}

.save{
    flex:1;
    padding:15px;
    border:none;
    border-radius:30px;
    background:linear-gradient(135deg,#22c55e,#16a34a);
    color:#022c22;
    font-weight:900;
    cursor:pointer;
    transition:.35s;
}
.save:hover{
    transform:scale(1.1);
    box-shadow:0 0 50px rgba(34,197,94,.9);
}

.back{
    padding:15px 20px;
    border-radius:30px;
    background:transparent;
    color:#22c55e;
    border:1px solid rgba(34,197,94,.6);
    text-decoration:none;
    font-weight:800;
    transition:.35s;
}
.back:hover{
    background:#22c55e;
    color:#022c22;
    box-shadow:0 0 35px rgba(34,197,94,.8);
}
</style>
</head>

<body>

<div class="edit-card">
    <h2>✏️ Edit Delivery Address</h2>

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
            <label>Phone Number</label>
            <input name="phone" required
            value="<?= htmlspecialchars($addr['phone'] ?? $user['phone']) ?>">
        </div>

        <div class="block">
            <label>Email</label>
            <input name="email" required
            value="<?= htmlspecialchars($addr['email'] ?? $user['email']) ?>">
        </div>

        <div class="actions">
            <button class="save">💾 Save Address</button>
            <a href="buy-now.php" class="back">⬅ Back to Buy-More</a>
        </div>

    </form>
</div>

</body>
</html>