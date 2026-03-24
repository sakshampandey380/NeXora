<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/user/login.php');
    exit;
}

$search = '';
$results = null;
$suggestions = null;

if (isset($_GET['q'])) {
    $search = trim($_GET['q']);

    if ($search !== '') {
        $like = '%' . $search . '%';

        $resultStmt = $conn->prepare(
            'SELECT * FROM products
             WHERE name LIKE ? OR description LIKE ?
             ORDER BY name'
        );
        $resultStmt->bind_param('ss', $like, $like);
        $resultStmt->execute();
        $results = $resultStmt->get_result();

        $suggestStmt = $conn->prepare(
            'SELECT name FROM products
             WHERE name LIKE ?
             ORDER BY name
             LIMIT 6'
        );
        $suggestStmt->bind_param('s', $like);
        $suggestStmt->execute();
        $suggestions = $suggestStmt->get_result();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Search Products | ShopSphere</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',sans-serif;}

body{
    min-height:100vh;
    background:linear-gradient(135deg,#020617,#0f172a,#020617);
    color:#fff;
    padding:30px 40px 50px;
}

header{
    display:flex;
    align-items:center;
    gap:20px;
    margin-bottom:40px;
}

.back-btn{
    padding:10px 18px;
    border-radius:25px;
    text-decoration:none;
    background:rgba(56,189,248,.15);
    border:1px solid #38bdf8;
    color:#38bdf8;
    font-weight:700;
    transition:.3s;
}

.back-btn:hover{
    background:#38bdf8;
    color:#020617;
    box-shadow:0 0 30px #38bdf8;
}

.logo{
    font-size:36px;
    font-weight:900;
    text-shadow:0 0 15px #38bdf8,0 0 40px #38bdf8;
}

.search-box{
    margin:0 auto 35px;
    max-width:520px;
    position:relative;
}

.search-box input{
    width:100%;
    padding:16px 55px 16px 20px;
    border-radius:40px;
    border:none;
    outline:none;
    font-size:16px;
    background:#020617;
    color:#fff;
    box-shadow:0 0 30px rgba(56,189,248,.45);
    transition:.35s;
}

.search-box input:focus{
    transform:scale(1.05);
    box-shadow:0 0 45px rgba(56,189,248,.9);
}

.search-box button{
    position:absolute;
    right:8px;
    top:50%;
    transform:translateY(-50%);
    border:none;
    background:linear-gradient(135deg,#38bdf8,#2563eb);
    width:42px;
    height:42px;
    border-radius:50%;
    cursor:pointer;
    color:#fff;
    font-size:18px;
    transition:.3s;
}

.search-box button:hover{
    transform:translateY(-50%) scale(1.15);
    box-shadow:0 0 30px rgba(56,189,248,.9);
}

.suggestions{
    max-width:520px;
    margin:0 auto 25px;
}

.suggestions span{
    display:inline-block;
    padding:8px 14px;
    background:#020617;
    border-radius:20px;
    margin:6px;
    cursor:pointer;
    font-size:13px;
    border:1px solid rgba(255,255,255,.1);
    transition:.3s;
}

.suggestions span:hover{
    background:#38bdf8;
    color:#020617;
    box-shadow:0 0 25px #38bdf8;
}

.grid{
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:25px;
}

.card{
    background:#fff;
    color:#000;
    border-radius:18px;
    overflow:hidden;
    box-shadow:0 15px 40px rgba(0,0,0,.35);
    transition:.4s;
}

.card:hover{
    transform:translateY(-12px);
    box-shadow:0 30px 70px rgba(0,0,0,.55);
}

.card img{
    width:100%;
    height:200px;
    object-fit:cover;
    transition:.5s;
}

.card:hover img{
    transform:scale(1.2);
}

.body{
    padding:16px;
}

.body h3{
    font-size:18px;
    margin-bottom:6px;
}

.body p{
    font-size:14px;
    color:#555;
    height:40px;
    overflow:hidden;
}

.price{
    margin-top:10px;
    font-size:18px;
    font-weight:800;
    color:#16a34a;
}

.empty{
    text-align:center;
    margin-top:80px;
    font-size:20px;
    color:#f87171;
}

@media(max-width:1200px){.grid{grid-template-columns:repeat(3,1fr);}}
@media(max-width:850px){.grid{grid-template-columns:repeat(2,1fr);}}
@media(max-width:500px){.grid{grid-template-columns:1fr;}}
</style>
</head>
<body>

<header>
    <a href="product_page.php" class="back-btn">Back to Products</a>
    <div class="logo">ShopSphere Search</div>
</header>

<form class="search-box" method="get">
    <input type="text" name="q" placeholder="Search your product..." value="<?= htmlspecialchars($search) ?>" autofocus>
    <button type="submit">Go</button>
</form>

<?php if ($suggestions && $suggestions->num_rows > 0): ?>
    <div class="suggestions">
        <?php while ($suggestion = $suggestions->fetch_assoc()): ?>
            <span onclick="location.href='?q=<?= urlencode($suggestion['name']) ?>'">
                <?= htmlspecialchars($suggestion['name']) ?>
            </span>
        <?php endwhile; ?>
    </div>
<?php endif; ?>

<?php if ($search !== '' && $results && $results->num_rows > 0): ?>
    <div class="grid">
        <?php while ($product = $results->fetch_assoc()): ?>
            <div class="card">
                <img src="../uploads/products/<?= rawurlencode($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                <div class="body">
                    <h3><?= htmlspecialchars($product['name']) ?></h3>
                    <p><?= htmlspecialchars($product['description']) ?></p>
                    <div class="price">Rs <?= number_format((float) $product['offer_price'], 2) ?></div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
<?php elseif ($search !== ''): ?>
    <div class="empty">Not present right now.</div>
<?php endif; ?>

</body>
</html>
