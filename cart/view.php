<?php
session_start();
$_SESSION['cart_page_active'] = true;
require_once __DIR__ . '/../config/db.php';

/* LOGIN CHECK */
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/user/login.php");
    exit;
}

/* INIT CART */
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

/* AJAX HANDLERS */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* UPDATE QUANTITY */
    if (isset($_POST['update_qty'])) {
        $pid = (int)$_POST['product_id'];
        $qty = max(1, (int)$_POST['qty']);
        $_SESSION['cart'][$pid] = $qty;
        echo json_encode(['ok'=>true]);
        exit;
    }

    /* REMOVE ITEM */
    if (isset($_POST['remove_item'])) {
        $pid = (int)$_POST['product_id'];
        unset($_SESSION['cart'][$pid]);
        echo json_encode(['ok'=>true]);
        exit;
    }
}

/* FETCH PRODUCTS */
$cart = $_SESSION['cart'];
$products = [];

if (!empty($cart)) {
    $ids = implode(',', array_keys($cart));
    $products = $conn->query("SELECT * FROM products WHERE id IN ($ids)");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Cart | ShopSphere</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',sans-serif;}

body{
    background:linear-gradient(135deg,#020617,#0f172a,#020617);
    color:#fff;
    padding:30px 40px 50px;
}

/* HEADER */
header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:40px;
    padding-bottom:20px;
    border-bottom:1px solid rgba(255,255,255,.1);
}

.logo{
    font-size:40px;
    font-weight:900;
    text-shadow:0 0 15px #38bdf8,0 0 40px #38bdf8;
}

.back-btn{
    text-decoration:none;
    padding:10px 18px;
    border-radius:25px;
    background:rgba(56,189,248,.15);
    border:1px solid #38bdf8;
    color:#38bdf8;
    font-weight:600;
    transition:.3s;
}
.back-btn:hover{
    background:#38bdf8;
    color:#020617;
    box-shadow:0 0 30px #38bdf8;
}

h2{text-align:center;margin-bottom:35px;font-size:32px;}

/* GRID */
.cart-grid{
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:26px;
}

/* CARD */
.cart-card{
    background:#fff;
    color:#000;
    border-radius:18px;
    overflow:hidden;
    position:relative;
    transition:.4s;
    box-shadow:0 15px 40px rgba(0,0,0,.3);
}
.cart-card:hover{
    transform:translateY(-12px);
    box-shadow:0 30px 70px rgba(0,0,0,.45);
}

/* IMAGE */
.cart-img{
    height:220px;
    overflow:hidden;
}
.cart-img img{
    width:100%;
    height:100%;
    object-fit:cover;
    transition:.6s;
}
.cart-card:hover img{transform:scale(1.35);}

/* BODY */
.cart-body{
    padding:18px;
    display:flex;
    flex-direction:column;
    gap:10px;
}

.cart-body h3{font-size:18px;}
.cart-body p{font-size:14px;color:#555;max-height:60px;overflow:auto;}

.price{
    font-size:20px;
    font-weight:800;
    color:#16a34a;
}

/* QUANTITY */
.qty-box{
    display:flex;
    align-items:center;
    gap:12px;
}

.qty-btn{
    width:32px;
    height:32px;
    border-radius:50%;
    border:none;
    background:#020617;
    color:#fff;
    cursor:pointer;
    font-size:18px;
}
.qty-btn:hover{background:#38bdf8;color:#020617;}

.qty{
    font-weight:700;
}

/* ACTIONS */
.actions{
    margin-top:auto;
    display:flex;
    gap:10px;
}

.buy-btn{
    flex:1;
    padding:10px;
    border-radius:25px;
    background:linear-gradient(135deg,#ff8a00,#ff3d00);
    color:#fff;
    text-decoration:none;
    font-weight:700;
    text-align:center;
    transition:.3s;
}
.buy-btn:hover{transform:scale(1.08);box-shadow:0 0 35px rgba(255,61,0,.9);}

.remove-btn{
    background:#dc2626;
    color:#fff;
    border:none;
    border-radius:50%;
    width:42px;
    cursor:pointer;
    font-size:16px;
}
.remove-btn:hover{background:#991b1b;}

/* FOOTER */
footer{
    margin-top:90px;
    padding-top:30px;
    text-align:center;
    border-top:1px solid rgba(255,255,255,.1);
}
footer span{color:#38bdf8;font-weight:700;}

.clear-btn{
    padding:10px 18px;
    border-radius:25px;
    background:linear-gradient(135deg,#ef4444,#dc2626);
    color:#fff;
    text-decoration:none;
    font-weight:700;
    transition:.35s ease;
    box-shadow:0 0 25px rgba(239,68,68,.6);
}

.clear-btn:hover{
    transform:scale(1.08) rotate(-2deg);
    box-shadow:0 0 45px rgba(239,68,68,.95);
}

/* RESPONSIVE */
@media(max-width:1300px){.cart-grid{grid-template-columns:repeat(3,1fr);}}
@media(max-width:900px){.cart-grid{grid-template-columns:repeat(2,1fr);}}
@media(max-width:500px){.cart-grid{grid-template-columns:1fr;}}
</style>
</head>

<body>

<header>
    <a href="../products/product_page.php" class="back-btn">⬅ Back to Products</a>
    <a href="clear.php" class="clear-btn"
   onclick="return confirm('Sab products delete karna hai? 😬')">
   🧹 Clear Cart
</a>
    <div class="logo">ShopSphere</div>
</header>

<h2>🛒 My Cart</h2>

<?php if (empty($cart)): ?>
    <p style="text-align:center;">Your cart is empty 😔</p>
<?php else: ?>

<div class="cart-grid">
<?php while($p = $products->fetch_assoc()):
    $qty = $cart[$p['id']];
?>
<div class="cart-card" id="card<?= $p['id'] ?>">

    <div class="cart-img">
        <img src="../uploads/products/<?= htmlspecialchars($p['image']) ?>">
    </div>

    

    <div class="cart-body">
        <h3><?= htmlspecialchars($p['name']) ?></h3>
        <p><?= htmlspecialchars($p['description']) ?></p>

        <div class="price">
            ₹<?= number_format($p['offer_price'] * $qty, 2) ?>
        </div>

        <div class="qty-box">
            <button class="qty-btn" onclick="changeQty(<?= $p['id'] ?>, <?= $qty-1 ?>)">−</button>
            <span class="qty"><?= $qty ?></span>
            <button class="qty-btn" onclick="changeQty(<?= $p['id'] ?>, <?= $qty+1 ?>)">+</button>
        </div>

        <div class="actions">
            <a href="buy-now.php?pid=<?= $p['id'] ?>" class="buy-btn">⚡ Buy Now</a>
            <button class="remove-btn" onclick="removeItem(<?= $p['id'] ?>)">🗑️</button>
</button>
        </div>
    </div>

</div>
<?php endwhile; ?>
</div>

<?php endif; ?>

<footer>
    <p>Made with ❤️ by <span>Saksham</span> • ShopSphere • Premium • Secure</p>
</footer>

<script>
function changeQty(pid, qty){
    if(qty < 1) return;
    fetch("",{
        method:"POST",
        headers:{"Content-Type":"application/x-www-form-urlencoded"},
        body:"update_qty=1&product_id="+pid+"&qty="+qty
    }).then(()=>location.reload());
}

function removeItem(pid){
    fetch("",{
        method:"POST",
        headers:{"Content-Type":"application/x-www-form-urlencoded"},
        body:"remove_item=1&product_id="+pid
    }).then(()=>{
        document.getElementById("card"+pid).style.opacity=0;
        setTimeout(()=>location.reload(),300);
    });
}
</script>

</body>
</html>