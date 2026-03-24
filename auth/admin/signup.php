<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$name || !$email || !$phone || !$password) {
        $error = "All fields are required.";
    } else {

        // check existing admin
        $check = $conn->prepare("SELECT id FROM admins WHERE email = ? OR phone = ?");
        $check->bind_param("ss", $email, $phone);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = "Admin already exists.";
        } else {

            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $insert = $conn->prepare(
                "INSERT INTO admins (name, email, phone, password) VALUES (?, ?, ?, ?)"
            );
            $insert->bind_param("ssss", $name, $email, $phone, $hashed);

            if ($insert->execute()) {
                header("Location: login.php");
                exit;
            } else {
                $error = "Signup failed. Try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Admin Signup</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
        /* ===== RESET ===== */
        * {
            box-sizing: border-box;
            font-family: 'Segoe UI', sans-serif;
        }

        /* ===== BACKGROUND ===== */
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(135deg, #667eea, #764ba2);
        }

        /* ===== CARD ===== */
        .signup-box {
            width: 380px;
            background: #fff;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.25);
            animation: fadeUp .6s ease;
        }

        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ===== HEADER ===== */
        .signup-box h2 {
            text-align: center;
            margin-bottom: 10px;
            color: #333;
        }

        .signup-box p {
            text-align: center;
            color: #777;
            margin-bottom: 25px;
            font-size: 14px;
        }

        /* ===== INPUTS ===== */
        .input-group {
            margin-bottom: 16px;
        }

        .input-group label {
            font-size: 13px;
            font-weight: 600;
            color: #444;
            margin-bottom: 6px;
            display: block;
        }

        .input-group input {
            width: 100%;
            padding: 12px;
            border-radius: 8px;
            border: 1px solid #ccc;
            font-size: 14px;
            transition: .3s;
        }

        .input-group input:focus {
            border-color: #667eea;
            outline: none;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, .15);
        }

        /* ===== BUTTON ===== */
        .signup-btn {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 10px;
            background: #667eea;
            color: #fff;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: .3s;
        }

        .signup-btn:hover {
            background: #5567d6;
            transform: translateY(-1px);
        }

        /* ===== ERROR ===== */
        .error {
            background: #ffe3e3;
            color: #b10000;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 15px;
            text-align: center;
            font-size: 14px;
        }

        /* ===== FOOTER ===== */
        .footer-text {
            margin-top: 18px;
            text-align: center;
            font-size: 13px;
        }

        .footer-text a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>

<body>

    <div class="signup-box">

        <h2>Admin Signup</h2>
        <p>Create your admin account</p>

        <?php if ($error): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST">

            <div class="input-group">
                <label>Username</label>
                <input type="text" name="name" required>
            </div>

            <div class="input-group">
                <label>Email</label>
                <input type="email" name="email" required>
            </div>

            <div class="input-group">
                <label>Phone</label>
                <input type="text" name="phone" required>
            </div>

            <div class="input-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>

            <button class="signup-btn" type="submit">Sign Up</button>
        </form>

        <div class="footer-text">
            Already an admin? <a href="login.php">Login</a>
        </div>

    </div>

</body>

</html>