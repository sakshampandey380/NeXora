<?php
session_start();
require_once __DIR__ . '/../config/db.php';


$noti = $conn->query("
SELECT COUNT(*) as total 
FROM orders 
WHERE admin_seen = 0
");

$notiCount = $noti->fetch_assoc()['total'];

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../auth/admin/login.php");
    exit;
}

$msg = "";

/* FETCH EXISTING CATEGORIES FROM DB */
$categories = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = $_POST['name'];
    $price = $_POST['price'];
    $offer = $_POST['offer_price'];
    $desc = $_POST['description'];

    /** CATEGORY HANDLING **/
    $category_id = null;

    // 1️⃣ If existing category is selected
    if (!empty($_POST['category'])) {
        $category_id = $_POST['category'];
    }

    // 2️⃣ If admin enters a new category
    if (!empty($_POST['new_category'])) {
        $newCat = trim($_POST['new_category']);

        // Insert new category (ignore duplicates)
        $stmt = $conn->prepare("INSERT IGNORE INTO categories (name) VALUES (?)");
        $stmt->bind_param("s", $newCat);
        $stmt->execute();
        $stmt->close();

        // Fetch its ID
        $res = $conn->query("SELECT id FROM categories WHERE name='$newCat'");
        $category_id = $res->fetch_assoc()['id'];
    }

    /** IMAGE UPLOAD **/
    $imgName = time() . '_' . $_FILES['image']['name'];
    $imgTmp  = $_FILES['image']['tmp_name'];
    $uploadDir = __DIR__ . '/../uploads/products/';

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $path = $uploadDir . $imgName;

    if (move_uploaded_file($imgTmp, $path)) {

        $stmt = $conn->prepare(
            "INSERT INTO products (name, price, offer_price, description, image, category_id) 
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("sddssi", $name, $price, $offer, $desc, $imgName, $category_id);

        if ($stmt->execute()) {
            $msg = "Product added successfully!";
        } else {
            $msg = "Failed to add product.";
        }
        $stmt->close();

    } else {
        $msg = "Image upload failed.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Add Product</title>

<style>
body{
    margin:0;
    min-height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
    background:linear-gradient(135deg,#141e30,#243b55);
    font-family:Segoe UI;
}

.card{
    width:450px;
    background:#fff;
    padding:30px;
    border-radius:18px;
    box-shadow:0 30px 80px rgba(0,0,0,.4);
    animation:fadeUp .7s;
}

@keyframes fadeUp{
    from{opacity:0;transform:translateY(30px)}
    to{opacity:1}
}

h2{text-align:center;margin-bottom:20px}

input, textarea, select{
    width:100%;
    padding:12px;
    margin-bottom:14px;
    border-radius:10px;
    border:1px solid #ccc;
    font-size:14px;
}

textarea{resize:none;height:90px}

button{
    width:100%;
    padding:14px;
    border:none;
    border-radius:12px;
    background:#243b55;
    color:#fff;
    font-size:16px;
    cursor:pointer;
    transition:.3s;
}
button:hover{background:#141e30}

.success{
    background:#e6ffef;
    color:#0b7a32;
    padding:10px;
    text-align:center;
    border-radius:8px;
    margin-bottom:15px;
}

.view-products-wrap{
    margin-top:25px;
    text-align:center;
}

.view-products-btn{
    display:inline-block;
    padding:14px 34px;
    border-radius:30px;
    font-size:16px;
    font-weight:600;
    text-decoration:none;
    color:#fff;
    background:linear-gradient(135deg,#38bdf8,#2563eb);
    box-shadow:0 0 25px rgba(56,189,248,.5);
    transition:all .35s ease;
}

.view-products-btn:hover{
    transform:translateY(-4px) scale(1.05);
    box-shadow:0 0 45px rgba(56,189,248,.9);
}

.noti-badge{
    background:#dc2626;
    color:#fff;
    font-size:12px;
    padding:4px 8px;
    border-radius:50%;
    margin-left:6px;
    animation:pulse 1.5s infinite;
}

@keyframes pulse{
    0%{transform:scale(1)}
    50%{transform:scale(1.2)}
    100%{transform:scale(1)}
}
</style>

</head>

<body>

<div class="card">
<h2>Add New Product</h2>

<?php if($msg): ?>
<div class="success"><?= $msg ?></div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">

<input type="text" name="name" placeholder="Product Name" required>
<input type="number" step="0.01" name="price" placeholder="Price" required>
<input type="number" step="0.01" name="offer_price" placeholder="Offer Price">
<textarea name="description" placeholder="Description"></textarea>

<!-- EXISTING CATEGORY DROPDOWN -->
<select name="category">
    <option value="">Select Category (Optional)</option>
    <?php while($c = $categories->fetch_assoc()): ?>
    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
    <?php endwhile; ?>
</select>

<!-- NEW CATEGORY INPUT -->
<input type="text" name="new_category" placeholder="Or add new category (optional)">

<input type="file" name="image" required>

<button>Add Product</button>

<div class="view-products-wrap">
    <a href="view-product.php" class="view-products-btn">👁️ View Products</a>
    <a href="../auth/admin/view_order.php" class="view-products-btn">

📦 View Orders

<?php if($notiCount > 0){ ?>

<span class="noti-badge">
<?php echo $notiCount; ?>
</span>

<?php } ?>

</a>
</div>
<a href="../auth/admin/profile.php" class="view-products-btn">👤 Admin Profile</a>
<a href="../admin/dashboard.php" class="view-products-btn">🏠 Dashboard</a>
</form>
</div>

</body>
</html>