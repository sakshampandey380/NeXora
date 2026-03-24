<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

$conn->query("
UPDATE orders 
SET admin_seen = 1 
WHERE admin_seen = 0
");

if (!isset($_SESSION['admin_id'])) {
header("Location: login.php");
exit;
}

/* CHANGE STATUS */
if(isset($_GET['deliver'])){
$id = intval($_GET['deliver']);

$conn->query("
UPDATE orders
SET status='Delivered'
WHERE id=$id
");

$conn->query("UPDATE orders SET admin_seen = 1 WHERE admin_seen = 0");

$conn->query("
UPDATE orders 
SET status='Out Of Delivery', admin_seen=1 
WHERE id=$id
");
}

/* OUT OF STOCK */
if(isset($_GET['stock'])){
$id = intval($_GET['stock']);

$conn->query("
UPDATE orders 
SET status='Out Of Stock', admin_seen=1 
WHERE id=$id
");
}

/* FETCH ORDERS */
$orders = $conn->query("
SELECT 
orders.id,
orders.total_price,
orders.status,
orders.created_at,
users.name,
users.email,
users.phone,
order_items.product_name,
order_items.product_price,
order_items.product_image,
ua.address_line,
ua.country_pincode

FROM orders

JOIN users 
ON orders.user_id = users.id

JOIN order_items
ON orders.id = order_items.order_id

LEFT JOIN user_addresses ua
ON ua.user_id = users.id

ORDER BY orders.id DESC
");
?>

<!DOCTYPE html>
<html>
<head>
<title>Admin Orders</title>

<style>

body{
margin:0;
font-family:Segoe UI;
min-height:100vh;
background:linear-gradient(45deg,#ff0000,#00ffcc,#6600ff,#ffcc00);
background-size:400% 400%;
animation:rgbBG 15s ease infinite;
}

@keyframes rgbBG{
0%{background-position:0% 50%;}
50%{background-position:100% 50%;}
100%{background-position:0% 50%;}
}

/* BACK BUTTON */

.back{
position:absolute;
top:20px;
left:20px;
background:#111;
color:#fff;
padding:12px 20px;
border-radius:30px;
text-decoration:none;
font-weight:600;
}

/* GRID */

.container{
padding:80px 40px;
display:grid;
grid-template-columns:repeat(6,1fr);
gap:20px;
}

/* CARD */

.card{

background:#fff;
border-radius:18px;
padding:15px;
box-shadow:0 20px 40px rgba(0,0,0,.3);
transition:.3s;
}

.card:hover{
transform:translateY(-8px) scale(1.03);
}

/* IMAGE */

.card img{
width:100%;
height:120px;
object-fit:cover;
border-radius:10px;
}

/* TITLE */

.title{
font-size:15px;
font-weight:600;
margin:8px 0;
}

/* INFO */

.info{
font-size:13px;
margin:3px 0;
color:#333;
}

/* BUTTONS */

.btns{
margin-top:10px;
display:flex;
gap:6px;
}

.btn{
flex:1;
padding:8px;
border:none;
border-radius:8px;
cursor:pointer;
font-size:12px;
font-weight:600;
}

.deliver{
background:#16a34a;
color:#fff;
}

.stock{
background:#dc2626;
color:#fff;
}

.status{
font-size:12px;
margin-top:6px;
font-weight:bold;
}

.top-btn{

padding:10px 20px;
background:#000;
color:#fff;
text-decoration:none;
border-radius:25px;
font-weight:600;
transition:.3s;

}

.top-btn:hover{

background:#2563eb;
transform:scale(1.05);

}

</style>
</head>

<body>

<div style="position:absolute; top:20px; right:20px; display:flex; gap:10px;">

<a href="../../admin/dashboard.php" class="top-btn">Dashboard</a>

<a href="../../auth/admin/profile.php" class="top-btn">Profile</a>

<a href="../../admin/view-product.php" class="top-btn">Products</a>

</div>

<a class="back" href="../../admin/view-product.php">⬅ Back To Products</a>

<div class="container">

<?php while($row = $orders->fetch_assoc()){ ?>

<div class="card">

<img src="../../uploads/products/<?php echo $row['product_image']; ?>">

<div class="title">
<?php echo $row['product_name']; ?>
</div>

<div class="info">
Price: ₹<?php echo $row['product_price']; ?>
</div>

<div class="info">
User: <?php echo $row['name']; ?>
</div>

<div class="info">
Phone: <?php echo $row['phone']; ?>
</div>

<div class="info">
Email: <?php echo $row['email']; ?>
</div>

<div class="info">
Address: <?php echo $row['address_line']; ?>
</div>

<div class="info">
Pincode: <?php echo $row['country_pincode']; ?>
</div>

<div class="info">
Order Total: ₹<?php echo $row['total_price']; ?>
</div>

<div class="status">
Status: <?php echo $row['status']; ?>
</div>

<div class="btns">

<a href="?deliver=<?php echo $row['id']; ?>">
<button class="btn deliver">
Out For Delivery
</button>
</a>

<a href="?stock=<?php echo $row['id']; ?>">
<button class="btn stock">
Out Of Stock
</button>
</a>

<a href="?delivered=<?php echo $row['id']; ?>" class="delivered-btn">Delivered</a>

</div>

</div>

<?php } ?>

</div>

</body>
</html>