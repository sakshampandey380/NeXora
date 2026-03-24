<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

// Check database connection
if (!$conn) {
    die("Database connection error: " . mysqli_connect_error());
}

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: ../../products/product_page.php");
    exit();
}

$email = '';
$phone = '';
$error_message = '';
$debug_mode = false; // Set to true for debugging

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if (empty($email) || empty($phone) || empty($password)) {
        $error_message = 'Email, Phone, and Password are required.';
    } else {
        // Prepare statement to check user credentials
        $stmt = $conn->prepare("SELECT id, name, email, phone, password FROM users WHERE email = ? AND phone = ?");
        
        if (!$stmt) {
            $error_message = 'Database error: ' . $conn->error;
        } else {
            $stmt->bind_param("ss", $email, $phone);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                
                // Verify password
                if (password_verify($password, $user['password'])) {
                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_phone'] = $user['phone'];
                    
                    // Close statement before redirect
                    $stmt->close();
                    
                    // Redirect to products page using absolute path
                    header("Location: ../../products/product_page.php", true, 302);
                    exit();
                } else {
                    $error_message = 'Invalid email, phone, or password.';
                    if ($debug_mode) {
                        $error_message .= ' [Password verification failed]';
                    }
                }
            } else {
                $error_message = 'No account found with this email and phone combination.';
                if ($debug_mode) {
                    $error_message .= ' [Query: ' . count($result) . ' rows found]';
                }
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/user_login_page.css">
</head>
<body>
    <div class="container">
        <!-- Background Elements -->
        <div class="tree tree-1">
            <div class="trunk"></div>
            <div class="leaves"></div>
        </div>
        <div class="tree tree-2">
            <div class="trunk"></div>
            <div class="leaves"></div>
        </div>
        <div class="tree tree-3">
            <div class="trunk"></div>
            <div class="leaves"></div>
        </div>
        <div class="tree tree-4">
            <div class="trunk"></div>
            <div class="leaves"></div>
        </div>

        <div class="path"></div>

        <div class="chair chair-1">
            <div class="chair-back"></div>
            <div class="chair-base"></div>
        </div>
        <div class="chair chair-2">
            <div class="chair-back"></div>
            <div class="chair-base"></div>
        </div>

        <div class="falling-leaves" id="leaves-container"></div>

        <!-- Login Form -->
        <div class="login-form">
            <h2>User Login</h2>
            
            <?php if (!empty($error_message)): ?>
                <div style="background: #f8d7da; color: #721c24; padding: 12px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #721c24;">
                    <p style="margin: 0;">❌ <?php echo htmlspecialchars($error_message); ?></p>
                </div>
            <?php endif; ?>

            <form id="loginForm" method="post" action="">
                <div class="input-group">
                    <input type="email" name="email" placeholder="Email" required value="<?php echo htmlspecialchars($email); ?>">
                    <i class="fas fa-envelope"></i>
                </div>
                
                <div class="input-group">
                    <input type="text" name="phone" placeholder="Phone Number" required value="<?php echo htmlspecialchars($phone); ?>">
                    <i class="fas fa-phone"></i>
                </div>

                <div class="input-group">
                    <input type="password" name="password" placeholder="Password" required>
                    <i class="fas fa-lock"></i>
                </div>

                <button type="submit" class="login-btn">log In</button>

                <div class="form-footer">
                    <button type="button" class="signup-btn" onclick="window.location.href='signup.php'">Sign Up</button>
                    <button type="button" class="forgot-btn" onclick="window.location.href='forgot-password.php'">Forgot Password?</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../../assets/js/user_login_page.js"></script>
</body>
</html>
