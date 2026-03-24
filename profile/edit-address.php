<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if(!isset($_SESSION['user_id'])){
header("Location: ../auth/user/login.php");
exit;
}

$user_id = $_SESSION['user_id'];

$address = $conn->query("
SELECT * FROM user_addresses 
WHERE user_id=$user_id
")->fetch_assoc();

if($_SERVER['REQUEST_METHOD']=="POST"){

$name = $_POST['name'];
$address_line = $_POST['address'];
$country = $_POST['country'];

if($address){

$stmt = $conn->prepare("
UPDATE user_addresses 
SET full_name=?, address_line=?, country_pincode=?
WHERE user_id=?
");

$stmt->bind_param("sssi",$name,$address_line,$country,$user_id);

}else{

$stmt = $conn->prepare("
INSERT INTO user_addresses
(user_id,full_name,address_line,country_pincode)
VALUES(?,?,?,?)
");

$stmt->bind_param("isss",$user_id,$name,$address_line,$country);

}

$stmt->execute();

header("Location: profile.php");
exit;

}
?>

<!DOCTYPE html>

<html>
<head>
<meta charset="UTF-8">
<title>Edit Address</title>

<style>

/* RESET */

*{
margin:0;
padding:0;
box-sizing:border-box;
font-family:'Segoe UI',sans-serif;
}

/* ANIMATED DARK BACKGROUND */

body{

min-height:100vh;

display:flex;

align-items:center;

justify-content:center;

background:linear-gradient(-45deg,#020617,#0f172a,#1e293b,#020617);

background-size:400% 400%;

animation:bgMove 14s ease infinite;

color:white;

}

@keyframes bgMove{

0%{background-position:0% 50%;}

50%{background-position:100% 50%;}

100%{background-position:0% 50%;}

}

/* FORM CARD */

.form-card{

width:420px;

padding:35px;

border-radius:22px;

background:rgba(2,6,23,.85);

backdrop-filter:blur(12px);

border:1px solid rgba(255,255,255,.08);

box-shadow:0 40px 80px rgba(0,0,0,.7);

animation:fadeUp .8s ease;

}

@keyframes fadeUp{

from{opacity:0; transform:translateY(40px);}

to{opacity:1; transform:translateY(0);}

}

/* TITLE */

.form-card h2{

text-align:center;

margin-bottom:25px;

font-size:26px;

color:#38bdf8;

text-shadow:0 0 20px rgba(56,189,248,.8);

}

/* INPUT BLOCK */

.form-group{

margin-bottom:16px;

}

input,textarea{

width:100%;

padding:14px;

border-radius:12px;

border:none;

outline:none;

background:#020617;

color:white;

font-size:14px;

box-shadow:inset 0 0 0 1px rgba(255,255,255,.08);

transition:.3s;

}

textarea{

resize:none;

height:90px;

}

input:focus, textarea:focus{

box-shadow:0 0 0 2px #38bdf8;

}

/* BUTTON */

button{

width:100%;

margin-top:10px;

padding:14px;

border:none;

border-radius:30px;

background:linear-gradient(135deg,#38bdf8,#2563eb);

color:white;

font-size:15px;

font-weight:700;

cursor:pointer;

transition:.35s;

box-shadow:0 0 25px rgba(56,189,248,.6);

}

button:hover{

transform:scale(1.06);

box-shadow:0 0 45px rgba(56,189,248,.9);

}

</style>

</head>

<body>

<div class="form-card">

<h2>📍 Edit Delivery Address</h2>

<form method="POST">

<div class="form-group">
<input type="text" name="name"
value="<?php echo $address['full_name'] ?? ''; ?>"
placeholder="Full Name" required>
</div>

<div class="form-group">
<textarea name="address" required
placeholder="Enter your full address"><?php echo $address['address_line'] ?? ''; ?></textarea>
</div>

<div class="form-group">
<input type="text" name="country"
value="<?php echo $address['country_pincode'] ?? ''; ?>"
placeholder="Country & Pincode"
maxlength="20"
required>
</div>

<button type="submit">💾 Save Address</button>

</form>

</div>

</body>
</html>
