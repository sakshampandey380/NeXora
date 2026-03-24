<?php
session_start();
require_once __DIR__ . '/../config/db.php';

/* OPTIONAL ADMIN CHECK */
// if (!isset($_SESSION['admin_id'])) {
//     header("Location: login.php");
//     exit;
// }

/* SEARCH LOGIC */
$search = '';
$where = '';

if (!empty($_GET['search'])) {
    $search = trim($_GET['search']);
    $where = "WHERE p.name LIKE '%$search%' OR p.id='$search'";
}

$query = "
SELECT p.*, c.name AS category_name 
FROM products p 
LEFT JOIN categories c ON p.category_id = c.id
$where
ORDER BY p.id DESC
";

$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>View Products | Admin</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',sans-serif;}

body{
    background:#0f172a;
    color:#fff;
    padding:40px;
}

/* HEADER */
h1{
    text-align:center;
    margin-bottom:20px;
    font-size:36px;
    color:#38bdf8;
    text-shadow:0 0 20px rgba(56,189,248,.6);
}

/* SEARCH */
.search-box{
    max-width:420px;
    margin:0 auto 35px;
}

.search-box input{
    width:100%;
    padding:14px;
    border-radius:14px;
    border:none;
    outline:none;
    font-size:15px;
}

/* GRID */
.grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(280px,1fr));
    gap:28px;
}

/* CARD */
.card{
    background:#020617;
    border-radius:18px;
    overflow:hidden;
    box-shadow:0 20px 50px rgba(0,0,0,.4);
    transition:.35s ease;
}

.card:hover{
    transform:translateY(-10px) scale(1.02);
    box-shadow:0 30px 80px rgba(56,189,248,.25);
}

/* IMAGE */
.card img{
    width:100%;
    height:190px;
    object-fit:contain;     
    background:#020617;      
    padding:10px;            
}
/* PRICE */
.price{
    position:absolute;
    top:12px;
    right:12px;
    background:#000;
    padding:6px 14px;
    border-radius:20px;
    font-size:13px;
}

/* BODY */
.body{
    padding:16px;
}

.body h3{font-size:18px;margin-bottom:4px;}

.cat{
    font-size:13px;
    color:#94a3b8;
    margin-bottom:8px;
}

.desc{
    font-size:14px;
    color:#cbd5f5;
    line-height:1.4em;
    max-height:3em;
    overflow:hidden;
}

/* FOOTER */
.footer{
    margin-top:14px;
    display:flex;
    justify-content:space-between;
    align-items:center;
}

.offer{color:#22c55e;font-size:18px;font-weight:700;}
.id{font-size:12px;color:#64748b}

/* ACTIONS */
.actions{
    display:flex;
    gap:10px;
    margin-top:12px;
}

.actions a{
    flex:1;
    text-align:center;
    padding:8px;
    font-size:14px;
    border-radius:10px;
    text-decoration:none;
    color:#fff;
}

/* TOP ACTION BAR */
.top-actions{
    display:flex;
    justify-content:space-between;
    align-items:center;
    max-width:1000px;
    margin:0 auto 30px;
    gap:20px;
}

.top-actions a{
    padding:12px 26px;
    border-radius:30px;
    font-size:14px;
    font-weight:600;
    text-decoration:none;
    color:#fff;
    transition:.35s ease;
    box-shadow:0 0 25px rgba(56,189,248,.35);
}

.back-btn{
    background:linear-gradient(135deg,#38bdf8,#2563eb);
}

.back-btn:hover{
    transform:translateY(-3px) scale(1.05);
    box-shadow:0 0 45px rgba(56,189,248,.8);
}

.profile-btn{
    background:linear-gradient(135deg,#22c55e,#16a34a);
}

.profile-btn:hover{
    transform:translateY(-3px) scale(1.05);
    box-shadow:0 0 45px rgba(34,197,94,.8);
}

.dashboard-btn{

position:absolute;
top:20px;
right:30px;

padding:12px 24px;

border-radius:30px;

background:linear-gradient(135deg,#38bdf8,#2563eb);

color:#fff;

text-decoration:none;

font-weight:600;

box-shadow:0 0 25px rgba(56,189,248,.5);

transition:all .35s ease;

}

.dashboard-btn:hover{

transform:translateY(-4px) scale(1.05);

box-shadow:0 0 45px rgba(56,189,248,.9);

}

.edit{background:#2563eb}
.delete{background:#dc2626}
</style>
</head>

<body>

<h1>Admin • View Products</h1>

<div class="top-actions">
    <a href="add-product.php" class="back-btn">⬅ Back to Add Product</a>
    <a href="../auth/admin/profile.php" class="profile-btn">👤 Admin Profile</a>
    <a href="dashboard.php" class="dashboard-btn">🏠 Dashboard</a>
</div>

<form class="search-box">
    <input type="text" name="search" placeholder="Search by ID or Name" value="<?= htmlspecialchars($search) ?>">
</form>

<div class="grid">
<?php while($p = $result->fetch_assoc()): ?>
<div class="card">

    <span class="price">₹<?= number_format($p['price'],2) ?></span>

    <img src="../uploads/products/<?= htmlspecialchars($p['image']) ?>">

    <div class="body">
        <h3><?= htmlspecialchars($p['name']) ?></h3>
        <div class="cat">Category: <?= htmlspecialchars($p['category_name'] ?? 'None') ?></div>

        <p class="desc"><?= htmlspecialchars($p['description']) ?></p>

        <div class="footer">
            <span class="offer">₹<?= number_format($p['offer_price'],2) ?></span>
            <span class="id">ID: <?= $p['id'] ?></span>
        </div>

        <div class="actions">
            <a class="edit" href="edit-product.php?id=<?= $p['id'] ?>">✏ Edit</a>
            <a class="delete" href="delete-product.php?id=<?= $p['id'] ?>" 
               onclick="return confirm('Delete this product?')">🗑 Delete</a>
        </div>
    </div>

</div>
<?php endwhile; ?>
</div>

</body>
</html>