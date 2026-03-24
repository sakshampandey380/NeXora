<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if(!isset($_SESSION['user_id'])){
header("Location: ../auth/user/login.php");
exit;
}

$user_id = $_SESSION['user_id'];

$orders = $conn->query("
SELECT 
orders.id,
orders.status,
orders.created_at,
order_items.product_name,
order_items.product_price,
order_items.product_image

FROM orders

JOIN order_items 
ON orders.id = order_items.order_id

WHERE orders.user_id = $user_id

ORDER BY orders.id DESC
");
?>

<!DOCTYPE html>

<html>
<head>
<title>My Orders</title>

<style>

*{
margin:0;
padding:0;
box-sizing:border-box;
font-family:Segoe UI;
}

body{

padding:40px;

/* ANIMATED BACKGROUND */

background:linear-gradient(270deg,#020617,#0f172a,#1e293b,#020617);
background-size:600% 600%;

animation:bgMove 15s ease infinite;

color:white;

}

@keyframes bgMove{

0%{background-position:0% 50%;}
50%{background-position:100% 50%;}
100%{background-position:0% 50%;}

}

h2{
margin-bottom:30px;
font-size:32px;
text-align:center;
color:#38bdf8;
text-shadow:0 0 20px rgba(56,189,248,.8);
}

/* GRID */

.grid{

display:grid;
grid-template-columns:repeat(4,1fr);
gap:25px;

}

/* CARD */

.card{

background:rgba(255,255,255,.08);

backdrop-filter:blur(12px);

padding:18px;

border-radius:16px;

box-shadow:0 15px 35px rgba(0,0,0,.5);

transition:.4s;

border:1px solid rgba(255,255,255,.1);

}

.card:hover{

transform:translateY(-8px) scale(1.03);

box-shadow:0 25px 50px rgba(0,0,0,.6);

}

/* IMAGE */

img{

width:100%;
height:150px;

object-fit:cover;

border-radius:10px;

margin-bottom:10px;

}

/* PRODUCT */

h4{

margin:8px 0;

font-size:18px;

}

p{

color:#94a3b8;

}

/* STATUS */

.status{

margin-top:10px;

font-weight:bold;

font-size:14px;

}

.delivery{
color:#22c55e;
}

.stock{
color:#ef4444;
}

/* PROGRESS */

.progress{

width:100%;
height:8px;

background:#1e293b;

border-radius:20px;

margin:12px 0;

overflow:hidden;

}

.bar{

height:100%;

width:20%;

background:linear-gradient(90deg,#22c55e,#16a34a);

animation:progressGlow 2s infinite alternate;

transition:.6s;

}

/* TOP NAVIGATION */

.top-nav{

display:flex;

justify-content:center;

gap:20px;

margin-bottom:30px;

}

/* BUTTON STYLE */

.nav-btn{

padding:12px 26px;

border-radius:30px;

text-decoration:none;

font-weight:bold;

color:white;

transition:.4s;

box-shadow:0 10px 25px rgba(0,0,0,.3);

position:relative;

overflow:hidden;

}

/* BUTTON COLORS */

.profile-btn{

background:linear-gradient(135deg,#3b82f6,#06b6d4);

}

.product-btn{

background:linear-gradient(135deg,#22c55e,#16a34a);

}

.cart-btn{

background:linear-gradient(135deg,#f97316,#fb923c);

}

/* HOVER ANIMATION */

.nav-btn:hover{

transform:translateY(-5px) scale(1.05);

box-shadow:0 15px 35px rgba(0,0,0,.5);

}

/* GLOW EFFECT */

.nav-btn::before{

content:"";

position:absolute;

top:0;

left:-100%;

width:100%;

height:100%;

background:linear-gradient(120deg,transparent,rgba(255,255,255,.5),transparent);

transition:.6s;

}

.nav-btn:hover::before{

left:100%;

}

@keyframes progressGlow{

from{
box-shadow:0 0 6px #22c55e;
}

to{
box-shadow:0 0 18px #22c55e;
}

}

/* RESPONSIVE */

@media(max-width:1100px){

.grid{
grid-template-columns:repeat(2,1fr);
}

}

@media(max-width:600px){

.grid{
grid-template-columns:1fr;
}

}

</style>

</head>

<body>

<div class="top-nav">

<a href="../profile/profile.php" class="nav-btn profile-btn">👤 Profile</a>

<a href="../products/product_page.php" class="nav-btn product-btn">🛍 Products</a>

<a href="../cart/view.php" class="nav-btn cart-btn">🛒 Cart</a>

</div>

<script>

let lastStatus = {};

function playSound(){
document.getElementById("orderSound").play();
}

function updateProgress(id,status){

let bar = document.getElementById("progress-"+id);

if(!bar) return;

if(status === "Pending"){
bar.style.width = "20%";
}

else if(status === "Out Of Delivery"){
bar.style.width = "70%";
}

else if(status === "Delivered"){
bar.style.width = "100%";
}

else if(status === "Out Of Stock"){
bar.style.width = "100%";
bar.style.background = "#dc2626";
}

}

function checkOrders(){

fetch("get_order_status.php")
.then(res => res.json())
.then(data => {

for(let id in data){

let status = data[id];
let statusBox = document.getElementById("order-status-"+id);

if(!statusBox) continue;

if(lastStatus[id] && lastStatus[id] !== status){

playSound();

if(status === "Out Of Delivery"){
alert("🚚 Your order is arriving soon!");
}

if(status === "Out Of Stock"){
alert("⚠ Product is out of stock.");
}

if(status === "Delivered"){
alert("✅ Your order has been delivered.");
}

}

lastStatus[id] = status;

updateProgress(id,status);

if(status === "Out Of Delivery"){
statusBox.innerHTML = "🚚 Your order is arriving soon!";
}

else if(status === "Out Of Stock"){
statusBox.innerHTML = "⚠ Product currently out of stock.";
}

else if(status === "Delivered"){
statusBox.innerHTML = "✅ Order Delivered";
}

else{
statusBox.innerHTML = "⏳ Order Processing";
}

}

});

}

setInterval(checkOrders,4000);

checkOrders();

</script>

<h2>My Orders</h2>

<div class="grid">

<?php while($row = $orders->fetch_assoc()){ ?>

<div class="card">

<img src="../uploads/products/<?php echo $row['product_image']; ?>">

<h4><?php echo $row['product_name']; ?></h4>

<p>₹<?php echo $row['product_price']; ?></p>

<div class="progress">
<div class="bar" id="progress-<?php echo $row['id']; ?>"></div>
</div>

<div class="status" id="order-status-<?php echo $row['id']; ?>">

<?php
if($row['status'] == "Out Of Delivery"){
echo "<span class='delivery'>🚚 Your order is arriving soon!</span>";
}

elseif($row['status'] == "Out Of Stock"){
echo "<span class='stock'>⚠ Product currently out of stock. Sorry!</span>";
}

else{
echo "⏳ Order Processing";
}
?>

</div>

</div>

<?php } ?>

</div>

<audio id="orderSound">
<source src="../assets/sounds/notification.mp3" type="audio/mpeg">
</audio>

</body>
</html>
