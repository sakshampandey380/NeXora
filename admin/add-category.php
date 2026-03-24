<?php
session_start();
require_once __DIR__ . '/../config/db.php';

/* OPTIONAL ADMIN CHECK */
// if (!isset($_SESSION['admin_id'])) {
//     header("Location: login.php");
//     exit;
// }

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name']);

    if ($name !== '') {
        $stmt = $conn->prepare("INSERT INTO categories (name) VALUES (?)");
        $stmt->bind_param("s", $name);

        if ($stmt->execute()) {
            $message = "✅ Category added successfully!";
        } else {
            $message = "❌ Failed to add category.";
        }
        $stmt->close();
    } else {
        $message = "⚠️ Category name cannot be empty.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add Category | Admin</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Segoe UI',sans-serif;
}

body{
    background:#0f172a;
    min-height:100vh;
    display:flex;
    align-items:center;
    justify-content:center;
    color:#fff;
}

/* CARD */
.card{
    width:420px;
    background:#020617;
    padding:40px;
    border-radius:20px;
    box-shadow:0 30px 80px rgba(56,189,248,.3);
    animation:fadeIn .6s ease;
}

@keyframes fadeIn{
    from{opacity:0;transform:translateY(30px)}
    to{opacity:1;transform:translateY(0)}
}

/* TITLE */
h1{
    text-align:center;
    margin-bottom:30px;
    color:#38bdf8;
    text-shadow:0 0 18px rgba(56,189,248,.7);
}

/* INPUT */
input{
    width:100%;
    padding:16px;
    border-radius:14px;
    border:none;
    outline:none;
    font-size:16px;
    margin-bottom:20px;
}

/* BUTTON */
button{
    width:100%;
    padding:16px;
    border:none;
    border-radius:30px;
    font-size:16px;
    cursor:pointer;
    color:#fff;
    background:linear-gradient(135deg,#22c55e,#16a34a);
    transition:.3s;
}

button:hover{
    box-shadow:0 0 30px rgba(34,197,94,.8);
    transform:scale(1.05);
}

/* MESSAGE */
.msg{
    text-align:center;
    margin-bottom:20px;
    font-size:14px;
}

/* BACK LINK */
.back{
    display:block;
    margin-top:25px;
    text-align:center;
    color:#94a3b8;
    text-decoration:none;
    font-size:14px;
}

.back:hover{
    color:#38bdf8;
}
</style>
</head>

<body>

<div class="card">
    <h1>Add Category</h1>

    <?php if($message): ?>
        <div class="msg"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="post">
        <input type="text" name="name" placeholder="Category name (optional)">
        <button type="submit">Add Category</button>
    </form>

    <a class="back" href="add-product.php">⬅ Back to Add Product</a>
</div>

</body>
</html>