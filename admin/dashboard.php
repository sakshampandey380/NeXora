<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../auth/admin/login.php");
    exit;
}

$chartQuery = $conn->query("
SELECT DATE(created_at) as day, COUNT(*) as total
FROM orders
GROUP BY DATE(created_at)
ORDER BY DATE(created_at)
");

$days = [];
$counts = [];

while($row = $chartQuery->fetch_assoc()){
$days[] = $row['day'];
$counts[] = $row['total'];
}

/* TOTAL ORDERS */
$totalOrders = $conn->query("SELECT COUNT(*) as total FROM orders")->fetch_assoc()['total'];

/* TOTAL REVENUE */
$totalRevenue = $conn->query("SELECT SUM(total_price) as revenue FROM orders WHERE status='Delivered'")->fetch_assoc()['revenue'];

/* TOTAL PRODUCTS */
$totalProducts = $conn->query("SELECT COUNT(*) as total FROM products")->fetch_assoc()['total'];

/* COUNT NEW ORDERS */
$noti = $conn->query("SELECT COUNT(*) AS total FROM orders WHERE admin_seen = 0");
$notiCount = $noti->fetch_assoc()['total'];

/* TOTAL PRODUCTS */
$total_products = $conn->query("SELECT COUNT(*) as total FROM products")->fetch_assoc()['total'];

/* TOTAL USERS */
$total_users = $conn->query("SELECT COUNT(*) as total FROM users")->fetch_assoc()['total'];

/* TOTAL ORDERS */
$total_orders = $conn->query("SELECT COUNT(*) as total FROM orders")->fetch_assoc()['total'];

/* TOTAL REVENUE */
$total_revenue = $conn->query("SELECT SUM(total_price) as revenue FROM orders")->fetch_assoc()['revenue'];

/* NEW ORDER NOTIFICATIONS */
$new_orders = $conn->query("SELECT COUNT(*) as total FROM orders WHERE admin_seen = 0")->fetch_assoc()['total'];
?>

<!DOCTYPE html>

<html>
<head>
<title>Admin Dashboard</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>

body{
font-family:Segoe UI;
background:linear-gradient(135deg,#020617,#0f172a);
color:white;
padding:40px;
}

/* TITLE */

.dashboard-title{
text-align:center;
font-size:52px;
font-weight:800;
margin-bottom:60px;
color:#38bdf8;
text-shadow:0 0 15px #38bdf8,0 0 35px #0ea5e9;
animation:glowMove 3s infinite alternate;
}

@keyframes glowMove{
from{ text-shadow:0 0 15px #38bdf8,0 0 30px #38bdf8; }
to{ text-shadow:0 0 25px #0ea5e9,0 0 50px #38bdf8; }
}

/* BELL */

.notification{
position:absolute;
top:20px;
right:60px;
}

.bell{
font-size:28px;
text-decoration:none;
position:relative;
animation:ring 1.5s infinite;
}

.badge{
position:absolute;
top:-8px;
right:-10px;
background:#dc2626;
color:white;
font-size:12px;
padding:4px 8px;
border-radius:50%;
}

@keyframes ring{
0%{ transform:rotate(0deg); }
25%{ transform:rotate(15deg); }
50%{ transform:rotate(-15deg); }
75%{ transform:rotate(10deg); }
100%{ transform:rotate(0deg); }
}

/* TOP CARDS */

.analytics-cards{
display:flex;
justify-content:center;
gap:40px;
margin-bottom:70px;
}

.card{
width:200px;
padding:25px;
border-radius:20px;
text-align:center;
color:#fff;
font-weight:600;
box-shadow:0 15px 30px rgba(0,0,0,.3);
transition:.4s;
}

.card:hover{
transform:translateY(-10px) scale(1.05);
}

.card p{
font-size:32px;
margin-top:10px;
}

/* GRADIENT COLORS */

.orders-card{
background:linear-gradient(135deg,#3b82f6,#06b6d4);
}

.revenue-card{
background:linear-gradient(135deg,#10b981,#22c55e);
}

.products-card{
background:linear-gradient(135deg,#f97316,#fb923c);
}

/* CHART */

.chart-container{
width:750px;
margin:50px auto;
padding:25px;
background:#ffe5d4;
border-radius:20px;
box-shadow:0 15px 35px rgba(0,0,0,.3);
color:#111;
}

.chart-container h2{
text-align:center;
margin-bottom:20px;
}

/* NEW ORDERS BADGE */

.new-order-box{
text-align:center;
margin-top:20px;
}

.noti{
background:#dc2626;
padding:6px 12px;
border-radius:20px;
font-size:14px;
margin-left:10px;
animation:pulse 1.5s infinite;
}

@keyframes pulse{
0%{transform:scale(1)}
50%{transform:scale(1.2)}
100%{transform:scale(1)}
}

/* BOTTOM CARDS */

.bottom-cards{
display:flex;
justify-content:center;
gap:30px;
margin-top:70px;
}

.bottom-card{
width:240px;
padding:25px;
border-radius:18px;
background:linear-gradient(135deg,#6366f1,#8b5cf6);
color:white;
text-align:center;
font-weight:600;
box-shadow:0 10px 30px rgba(0,0,0,.3);
}

.bottom-card p{
font-size:30px;
margin-top:10px;
}

/* BUTTONS */

.actions{
margin-top:60px;
display:flex;
justify-content:center;
gap:20px;
}

.btn{
padding:14px 30px;
border-radius:30px;
text-decoration:none;
font-weight:bold;
color:white;
background:linear-gradient(135deg,#38bdf8,#2563eb);
transition:.3s;
}

.btn:hover{
transform:scale(1.1);
box-shadow:0 0 40px rgba(56,189,248,.8);
}

</style>

</head>

<body>

<div class="notification">
<a href="../auth/admin/view_order.php" class="bell">🔔
<?php if($notiCount > 0){ ?>
<span class="badge"><?php echo $notiCount; ?></span>
<?php } ?>
</a>
</div>

<h1 class="dashboard-title">Admin Dashboard</h1>

<div class="analytics-cards">

<div class="card orders-card">
<h3>Total Orders</h3>
<p><?php echo $totalOrders; ?></p>
</div>

<div class="card revenue-card">
<h3>Total Revenue</h3>
<p>₹<?php echo $totalRevenue ?: 0; ?></p>
</div>

<div class="card products-card">
<h3>Total Products</h3>
<p><?php echo $totalProducts; ?></p>
</div>

</div>

<div class="chart-container">
<h2>Daily Orders</h2>
<canvas id="ordersChart"></canvas>
</div>

<div class="new-order-box">
New Orders
<span class="noti"><?php echo $new_orders; ?></span>
</div>

<div class="bottom-cards">

<div class="bottom-card">
<h3>Total Products</h3>
<p><?php echo $total_products; ?></p>
</div>

<div class="bottom-card">
<h3>Total Users</h3>
<p><?php echo $total_users; ?></p>
</div>

<div class="bottom-card">
<h3>Total Orders</h3>
<p><?php echo $total_orders; ?></p>
</div>

<div class="bottom-card">
<h3>Total Revenue</h3>
<p>₹<?php echo $total_revenue ?: 0; ?></p>
</div>

</div>

<div class="actions">

<a href="add-product.php" class="btn">➕ Add Product</a>

<a href="../auth/admin/view_order.php" class="btn">📦 View Orders</a>

<a href="view-product.php" class="btn">👁 View Products</a>

<a href="../auth/admin/profile.php" class="btn">👤 Profile</a>

</div>

<script>

const ctx = document.getElementById('ordersChart');

new Chart(ctx,{
type:'line',
data:{
labels: <?php echo json_encode($days); ?>,
datasets:[{
label:'Daily Orders',
data: <?php echo json_encode($counts); ?>,
borderColor:'#2563eb',
backgroundColor:'#93c5fd',
borderWidth:2,
fill:false
}]
}
});

</script>

</body>
</html>
