<?php
session_start();
require_once __DIR__ . '/../config/db.php';

$id = $_GET['id'] ?? 0;

$product = $conn->query("SELECT * FROM products WHERE id=$id")->fetch_assoc();
$categories = $conn->query("SELECT * FROM categories");

$updated = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = $_POST['name'];
    $price = $_POST['price'];
    $offer = $_POST['offer_price'];
    $desc = $_POST['description'];
    $category_id = $_POST['category'];

    if (!empty($_POST['new_category'])) {
        $new = trim($_POST['new_category']);
        $conn->query("INSERT IGNORE INTO categories (name) VALUES ('$new')");
        $res = $conn->query("SELECT id FROM categories WHERE name='$new'");
        $category_id = $res->fetch_assoc()['id'];
    }

    $imgName = $product['image'];

    if (!empty($_FILES['image']['name'])) {
        $imgName = time().'_'.$_FILES['image']['name'];
        move_uploaded_file(
            $_FILES['image']['tmp_name'],
            __DIR__.'/../uploads/products/'.$imgName
        );
    }

    $stmt = $conn->prepare(
        "UPDATE products 
         SET name=?, price=?, offer_price=?, description=?, image=?, category_id=? 
         WHERE id=?"
    );
    $stmt->bind_param("sddssii", $name, $price, $offer, $desc, $imgName, $category_id, $id);

    if ($stmt->execute()) {
        $updated = true;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Edit Product</title>

<style>
body{
    background:#0f172a;
    display:flex;
    justify-content:center;
    align-items:center;
    min-height:100vh;
    font-family:Segoe UI;
}

.card{
    width:460px;
    background:#fff;
    padding:30px;
    border-radius:18px;
}

input,textarea,select{
    width:100%;
    padding:12px;
    margin-bottom:14px;
    border-radius:10px;
    border:1px solid #ccc;
}

button{
    width:100%;
    padding:14px;
    background:#2563eb;
    color:#fff;
    border:none;
    border-radius:14px;
    font-size:16px;
    cursor:pointer;
}

/* 🔔 SUCCESS TOAST */
.toast{
    position:fixed;
    top:30px;
    right:30px;
    background:#22c55e;
    color:#fff;
    padding:16px 22px;
    border-radius:12px;
    font-size:15px;
    box-shadow:0 10px 40px rgba(34,197,94,.6);
    opacity:0;
    transform:translateY(-20px);
    animation:slideIn .5s forwards;
}

@keyframes slideIn{
    to{opacity:1;transform:translateY(0)}
}
</style>
</head>

<body>

<?php if($updated): ?>
<div class="toast">✅ Product updated successfully</div>

<script>
    setTimeout(() => {
        window.location.href = "view-product.php?id=<?= $id ?>";
    }, 1000);
</script>
<?php endif; ?>

<div class="card">
<h2>Edit Product</h2>

<form method="post" enctype="multipart/form-data">

<input name="name" value="<?= htmlspecialchars($product['name']) ?>" required>
<input type="number" step="0.01" name="price" value="<?= $product['price'] ?>" required>
<input type="number" step="0.01" name="offer_price" value="<?= $product['offer_price'] ?>">
<textarea name="description"><?= htmlspecialchars($product['description']) ?></textarea>

<select name="category">
<?php while($c = $categories->fetch_assoc()): ?>
<option value="<?= $c['id'] ?>" <?= $c['id']==$product['category_id']?'selected':'' ?>>
<?= htmlspecialchars($c['name']) ?>
</option>
<?php endwhile; ?>
</select>

<input name="new_category" placeholder="Add new category (optional)">
<input type="file" name="image">

<button>Update Product</button>
</form>
</div>

</body>
</html>