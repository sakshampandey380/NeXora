<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $identity = trim($_POST['identity'] ?? ''); // email OR phone OR username
    $password = $_POST['password'] ?? '';

    if (!$identity || !$password) {
        $error = "All fields are required.";
    } else {

        // login using email OR phone OR username
        $stmt = $conn->prepare(
            "SELECT id, name, password 
             FROM admins 
             WHERE email = ? OR phone = ? OR name = ?"
        );
        $stmt->bind_param("sss", $identity, $identity, $identity);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $admin = $result->fetch_assoc();

            if (password_verify($password, $admin['password'])) {

                // login success
                $_SESSION['admin_id']   = $admin['id'];
                $_SESSION['admin_name'] = $admin['name'];

                $success = "Login successful! Redirecting...";

                // redirect after short delay
                header("refresh:1.5;url= ../../admin/add-product.php");
            } else {
                $error = "Invalid credentials.";
            }
        } else {
            $error = "Admin not found.";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Login</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
/* ===== RESET ===== */
*{
    box-sizing:border-box;
    font-family:'Segoe UI',sans-serif;
}

/* ===== BACKGROUND ===== */
body{
    margin:0;
    min-height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
    background:linear-gradient(135deg,#1d2671,#c33764);
}

/* ===== CARD ===== */
.login-box{
    width:380px;
    background:#fff;
    padding:32px;
    border-radius:18px;
    box-shadow:0 30px 70px rgba(0,0,0,.35);
    animation:slideUp .7s ease;
}

@keyframes slideUp{
    from{opacity:0;transform:translateY(30px);}
    to{opacity:1;transform:translateY(0);}
}

/* ===== TITLE ===== */
.login-box h2{
    text-align:center;
    margin-bottom:6px;
    color:#333;
}

.login-box p{
    text-align:center;
    color:#777;
    margin-bottom:25px;
    font-size:14px;
}

/* ===== INPUT ===== */
.input-group{
    margin-bottom:18px;
}

.input-group label{
    display:block;
    margin-bottom:6px;
    font-size:13px;
    font-weight:600;
    color:#444;
}

.input-group input{
    width:100%;
    padding:13px;
    border-radius:10px;
    border:1px solid #ccc;
    font-size:14px;
    transition:.3s;
}

.input-group input:focus{
    outline:none;
    border-color:#c33764;
    box-shadow:0 0 0 3px rgba(195,55,100,.2);
}

/* ===== BUTTON ===== */
.login-btn{
    width:100%;
    padding:14px;
    border:none;
    border-radius:12px;
    background:#c33764;
    color:#fff;
    font-size:16px;
    font-weight:700;
    cursor:pointer;
    transition:.3s;
}

.login-btn:hover{
    background:#a82c55;
    transform:translateY(-2px);
}

/* ===== MESSAGES ===== */
.error{
    background:#ffe3e3;
    color:#b10000;
    padding:10px;
    border-radius:8px;
    margin-bottom:15px;
    text-align:center;
    font-size:14px;
    animation:shake .3s;
}

@keyframes shake{
    0%{transform:translateX(0)}
    25%{transform:translateX(-5px)}
    50%{transform:translateX(5px)}
    75%{transform:translateX(-5px)}
    100%{transform:translateX(0)}
}

.success{
    background:#e6ffef;
    color:#0b7a32;
    padding:10px;
    border-radius:8px;
    margin-bottom:15px;
    text-align:center;
    font-size:14px;
    animation:fade .5s;
}

@keyframes fade{
    from{opacity:0}
    to{opacity:1}
}

/* ===== FOOTER ===== */
.footer{
    margin-top:18px;
    text-align:center;
    font-size:13px;
}

.footer a{
    color:#c33764;
    font-weight:600;
    text-decoration:none;
}

.forgot-btn{
    display:block;
    margin-top:18px;
    text-align:center;
    padding:12px;
    border-radius:30px;
    font-size:14px;
    font-weight:600;
    text-decoration:none;
    color:#38bdf8;
    background:rgba(56,189,248,.1);
    border:1px solid rgba(56,189,248,.4);
    transition:.35s ease;
}

.forgot-btn:hover{
    background:#38bdf8;
    color:#020617;
    box-shadow:0 0 30px rgba(56,189,248,.9);
    transform:scale(1.05);
}
</style>
</head>

<body>

<div class="login-box">

    <h2>Admin Login</h2>
    <p>Login using email, phone or username</p>

    <?php if($error): ?>
        <div class="error"><?= $error ?></div>
    <?php endif; ?>

    <?php if($success): ?>
        <div class="success"><?= $success ?></div>
    <?php endif; ?>

    <form method="POST">

        <div class="input-group">
            <label>Email / Phone / Username</label>
            <input type="text" name="identity" required>
        </div>

        <div class="input-group">
            <label>Password</label>
            <input type="password" name="password" required>
        </div>

        <button class="login-btn" type="submit">Login</button>
        <a href="forgot-password.php" class="forgot-btn">
    🔑 Forgot Password?
        </a>
    </form>

    <div class="footer">
        New admin? <a href="signup.php">Create account</a>
    </div>

</div>

</body>
</html>