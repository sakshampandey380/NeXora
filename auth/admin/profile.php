<?php
session_start();
require_once __DIR__ . '/../../config/db.php';


$noti = $conn->query("
SELECT COUNT(*) as total 
FROM orders 
WHERE admin_seen = 0
");

$notiCount = $noti->fetch_assoc()['total'];

/* ADMIN AUTH CHECK */
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

$admin_id = $_SESSION['admin_id'];
$msg = '';

/* FETCH ADMIN DATA */
$stmt = $conn->prepare(
    "SELECT name, email, phone, profile_image 
     FROM admins 
     WHERE id=?"
);
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();
$stmt->close();

/* IMAGE UPDATE */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['profile_image']['name'])) {

    $imgName = time().'_'.$_FILES['profile_image']['name'];
    $tmp = $_FILES['profile_image']['tmp_name'];
    $dir = __DIR__ . '/../../uploads/admin_profiles/';

    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    if (move_uploaded_file($tmp, $dir.$imgName)) {

        if ($admin['profile_image'] !== 'default.png') {
            @unlink($dir.$admin['profile_image']);
        }

        $stmt = $conn->prepare(
            "UPDATE admins SET profile_image=? WHERE id=?"
        );
        $stmt->bind_param("si", $imgName, $admin_id);
        $stmt->execute();
        $stmt->close();

        header("Location: profile.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Profile</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Segoe UI',sans-serif;
}

body{
    min-height:100vh;
    background:linear-gradient(135deg,#020617,#0f172a,#020617);
    background-size:400% 400%;
    animation:bgMove 10s infinite;
    display:flex;
    justify-content:center;
    align-items:center;
    color:#fff;
}

@keyframes bgMove{
    0%{background-position:0% 50%}
    50%{background-position:100% 50%}
    100%{background-position:0% 50%}
}

/* CARD */
.card{
    width:420px;
    background:rgba(2,6,23,.85);
    backdrop-filter:blur(12px);
    border-radius:20px;
    padding:30px;
    box-shadow:0 40px 100px rgba(56,189,248,.35);
    animation:fadeUp .8s ease;
}

@keyframes fadeUp{
    from{opacity:0;transform:translateY(40px)}
    to{opacity:1}
}

/* PROFILE IMAGE */
.avatar{
    width:140px;
    height:140px;
    margin:0 auto 20px;
    position:relative;
}

.avatar img{
    width:100%;
    height:100%;
    border-radius:50%;
    object-fit:cover;
    border:4px solid #38bdf8;
    transition:.4s;
}

.avatar:hover img{
    transform:scale(1.08);
    box-shadow:0 0 30px #38bdf8;
}

.avatar label{
    position:absolute;
    bottom:0;
    right:0;
    background:#22c55e;
    width:36px;
    height:36px;
    border-radius:50%;
    display:flex;
    justify-content:center;
    align-items:center;
    cursor:pointer;
    font-size:18px;
}

.avatar input{display:none}

/* INFO */
h2{text-align:center;margin-bottom:8px}
.info{text-align:center;color:#cbd5f5;margin-bottom:18px}

/* DATA */
.data{
    background:#020617;
    padding:14px;
    border-radius:14px;
    margin-bottom:12px;
    font-size:14px;
}

/* BUTTONS */
.btns{
    display:flex;
    gap:12px;
    margin-top:20px;
}

.btns a{
    flex:1;
    padding:12px;
    border-radius:30px;
    text-align:center;
    text-decoration:none;
    color:#fff;
    font-weight:600;
    transition:.35s;
}

.add{
    background:linear-gradient(135deg,#38bdf8,#2563eb);
}

.view-order{
    background:linear-gradient(135deg,#f59e0b,#d97706);
    box-shadow:0 0 20px rgba(245,158,11,.6);
}

.btns a:hover{
    transform:translateY(-3px) scale(1.05);
    box-shadow:0 0 30px rgba(255,255,255,.4);
}

.security-link{
    display:block;
    margin-top:14px;
    padding:12px 16px;
    border-radius:30px;
    text-align:center;
    text-decoration:none;
    color:#fff;
    font-weight:600;
    background:linear-gradient(135deg,#8b5cf6,#ec4899);
    box-shadow:0 0 24px rgba(236,72,153,.35);
    transition:.35s;
}

.security-link:hover{
    transform:translateY(-3px) scale(1.02);
    box-shadow:0 0 34px rgba(236,72,153,.55);
}

/* TOP BAR */
.top-bar{
    position:fixed;
    top:20px;
    right:30px;
    z-index:1000;
}

/* LOGOUT BUTTON */
.logout-btn{
    padding:14px 36px;
    border-radius:30px;
    background:linear-gradient(135deg,#ef4444,#b91c1c);
    color:#fff;
    font-size:15px;
    font-weight:600;
    text-decoration:none;
    box-shadow:0 0 25px rgba(239,68,68,.45);
    transition:.35s;
}

.logout-btn:hover{
    transform:translateY(-4px) scale(1.06);
    box-shadow:0 0 45px rgba(239,68,68,.85);
}

.dashboard-btn{

position:absolute;
top:20px;
left:20px;

padding:12px 24px;

border-radius:30px;

background:linear-gradient(135deg,#38bdf8,#2563eb);

color:white;

text-decoration:none;

font-weight:600;

box-shadow:0 0 25px rgba(56,189,248,.6);

transition:.3s;

}

.dashboard-btn:hover{

transform:scale(1.05);

box-shadow:0 0 45px rgba(56,189,248,.9);

}

</style>
</head>

<body>
<a href="../../admin/dashboard.php" class="dashboard-btn">🏠 Dashboard</a>
<!-- TOP BAR -->
<div class="top-bar">
    <a href="logout.php" class="logout-btn"
       onclick="return confirm('Are you sure you want to logout?')">
       🚪 Logout
    </a>
</div>

<div class="card">

<form method="post" enctype="multipart/form-data">
    <div class="avatar">
        <img src="../../uploads/admin_profiles/<?= htmlspecialchars($admin['profile_image']) ?>">
        <label>
            ✎
            <input type="file" name="profile_image" onchange="this.form.submit()">
        </label>
    </div>
</form>

<h2><?= htmlspecialchars($admin['name']) ?></h2>
<p class="info">Administrator</p>

<div class="data">📧 <?= htmlspecialchars($admin['email']) ?></div>
<div class="data">📞 <?= htmlspecialchars($admin['phone']) ?></div>

<div class="btns">
    <a href="../../admin/add-product.php" class="add">➕ Add Product</a>
    <a href="../admin/view_order.php" class="view-products-btn">

📦 View Orders

<?php if($notiCount > 0){ ?>

<span class="noti-badge">
<?php echo $notiCount; ?>
</span>

<?php } ?>

</a>
</div>

<a href="forgot-password.php" class="security-link">Reset Password with OTP</a>

</div>

</body>
</html>
