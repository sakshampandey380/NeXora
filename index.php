<?php
session_start();

/* ===============================
   LOGIN & SIGNUP STATUS CHECK
================================ */

$redirect = "";

if (!isset($_SESSION['signed_up'])) {
    $redirect = "auth/user/signup.php";
} elseif (!isset($_SESSION['user_id'])) {
    $redirect = "auth/user/login.php";
} else {
    $redirect = "products/product_page.php";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>ShopSphere – Smart Shopping</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<!-- Auto Redirect -->
<meta http-equiv="refresh" content="2;url=<?php echo $redirect; ?>">

<style>
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family: "Segoe UI", sans-serif;
}

body{
    height:100vh;
    background:radial-gradient(circle at top, #1a2980, #26d0ce);
    display:flex;
    justify-content:center;
    align-items:center;
    color:#fff;
    overflow:hidden;
}

/* MAIN CARD */
.loader-card{
    background:rgba(255,255,255,0.12);
    backdrop-filter:blur(18px);
    padding:60px 80px;
    border-radius:20px;
    text-align:center;
    box-shadow:0 30px 60px rgba(0,0,0,0.35);
    animation:pop 0.8s ease;
}

@keyframes pop{
    from{transform:scale(0.85); opacity:0;}
    to{transform:scale(1); opacity:1;}
}

.logo{
    font-size:3rem;
    font-weight:800;
    letter-spacing:1px;
}

.logo span{
    color:#ffe259;
}

.tagline{
    margin-top:12px;
    font-size:1.1rem;
    opacity:0.9;
}

/* LOADING */
.loader{
    margin:35px auto 0;
    width:70px;
    height:70px;
    border-radius:50%;
    border:6px solid rgba(255,255,255,0.2);
    border-top:6px solid #fff;
    animation:spin 1s linear infinite;
}

@keyframes spin{
    100%{transform:rotate(360deg);}
}

.status{
    margin-top:25px;
    font-size:0.95rem;
    opacity:0.85;
    letter-spacing:0.5px;
}
</style>
</head>

<body>

<div class="loader-card">
    <div class="logo">Shop<span>Sphere</span></div>
    <div class="tagline">Smart • Secure • Seamless Shopping</div>

    <div class="loader"></div>

    <div class="status">
        Preparing your experience...
    </div>
</div>

</body>
</html>