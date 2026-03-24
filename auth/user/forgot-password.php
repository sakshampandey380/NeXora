<?php
session_start();

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/otp_helpers.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

ensureOtpVerificationSchema($conn);

$userId = (int) $_SESSION['user_id'];
$message = '';
$messageType = 'error';
$otpAlert = '';

$userStmt = $conn->prepare('SELECT name FROM users WHERE id = ?');
$userStmt->bind_param('i', $userId);
$userStmt->execute();
$user = $userStmt->get_result()->fetch_assoc();
$userStmt->close();

if (!$user) {
    header('Location: login.php');
    exit;
}

$resetKey = 'user-' . $userId;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['generate_otp'])) {
        $activeOtp = createVisibleOtp($conn, $resetKey, 'user');
        $otpAlert = (string) $activeOtp['otp'];
        $message = 'OTP generated successfully. Enter it to update your password.';
        $messageType = 'success';
    } elseif (isset($_POST['update_password'])) {
        $otp = trim($_POST['otp'] ?? '');
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if ($otp === '' || $newPassword === '' || $confirmPassword === '') {
            $message = 'OTP, new password, and confirm password are required.';
        } elseif (!preg_match('/^\d{4}$/', $otp)) {
            $message = 'Enter the 4 digit OTP shown in the alert.';
        } elseif (strlen($newPassword) < 6) {
            $message = 'Password must be at least 6 characters.';
        } elseif ($newPassword !== $confirmPassword) {
            $message = 'Both password fields must match.';
        } elseif (!verifyVisibleOtp($conn, $resetKey, 'user', $otp)) {
            $message = 'OTP is invalid or expired. Generate a new OTP.';
        } else {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $updateStmt = $conn->prepare('UPDATE users SET password = ? WHERE id = ?');
            $updateStmt->bind_param('si', $hashedPassword, $userId);
            $updateStmt->execute();
            $updateStmt->close();

            clearOtpForPhoneRole($conn, $resetKey, 'user');
            $message = 'Password updated successfully. Redirecting to your profile...';
            $messageType = 'success';
            header('Refresh:2; url=../../profile/profile.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>User OTP Password Reset | ShopSphere</title>
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
    display:flex;
    justify-content:center;
    align-items:center;
    background:linear-gradient(135deg,#020617,#0f172a,#020617);
    color:#fff;
    padding:24px;
    overflow:hidden;
}

body::before,
body::after{
    content:'';
    position:absolute;
    width:420px;
    height:420px;
    filter:blur(140px);
    opacity:.28;
}

body::before{
    background:#38bdf8;
    top:-120px;
    left:-120px;
}

body::after{
    background:#ec4899;
    bottom:-120px;
    right:-120px;
}

.card{
    position:relative;
    z-index:1;
    width:min(480px,100%);
    max-height:90vh;
    overflow-y:auto;
    background:rgba(2,6,23,.95);
    border-radius:24px;
    padding:34px;
    box-shadow:0 40px 90px rgba(0,0,0,.6);
}

.eyebrow{
    display:inline-flex;
    padding:8px 14px;
    border-radius:999px;
    background:rgba(56,189,248,.14);
    color:#93c5fd;
    font-size:12px;
    letter-spacing:.08em;
    text-transform:uppercase;
    margin-bottom:14px;
}

h2{
    font-size:30px;
    margin-bottom:10px;
}

.subtitle{
    color:#cbd5e1;
    line-height:1.6;
    margin-bottom:22px;
}

.meta{
    display:grid;
    gap:10px;
    margin-bottom:20px;
}

.meta div{
    padding:12px 14px;
    border-radius:14px;
    background:#020617;
    color:#e2e8f0;
    font-size:14px;
}

button:disabled{
    opacity:.6;
    cursor:not-allowed;
}

form{
    display:grid;
    gap:14px;
}

.input-box{
    display:grid;
    gap:8px;
}

.input-box label{
    font-size:13px;
    color:#cbd5e1;
}

.input-box input{
    width:100%;
    padding:14px 16px;
    border-radius:16px;
    border:1px solid rgba(255,255,255,.08);
    outline:none;
    background:#020617;
    color:#fff;
    font-size:15px;
}

.input-box input:focus{
    border-color:#38bdf8;
    box-shadow:0 0 0 3px rgba(56,189,248,.12);
}

.actions{
    display:grid;
    gap:12px;
    margin-top:6px;
}

button,
.back-link{
    width:100%;
    padding:14px 16px;
    border:none;
    border-radius:999px;
    cursor:pointer;
    text-decoration:none;
    text-align:center;
    font-size:15px;
    font-weight:700;
    transition:.3s ease;
}

.primary{
    background:linear-gradient(135deg,#38bdf8,#2563eb);
    color:#fff;
}

.secondary{
    background:rgba(255,255,255,.06);
    color:#fff;
    border:1px solid rgba(255,255,255,.1);
}

.back-link{
    background:transparent;
    color:#93c5fd;
    border:1px solid rgba(56,189,248,.35);
}

button:hover,
.back-link:hover{
    transform:translateY(-2px);
}

.message{
    margin-bottom:18px;
    padding:13px 14px;
    border-radius:14px;
    font-size:14px;
    line-height:1.5;
}

.message.error{
    background:rgba(239,68,68,.14);
    color:#fecaca;
    border:1px solid rgba(239,68,68,.28);
}

.message.success{
    background:rgba(34,197,94,.14);
    color:#bbf7d0;
    border:1px solid rgba(34,197,94,.28);
}
</style>
</head>
<body>

<div class="card">
    <span class="eyebrow">User Security</span>
    <h2>OTP Password Reset</h2>
    <p class="subtitle">Generate a 4 digit OTP, see it in a page alert, and then update your password.</p>

    <div class="meta">
        <div><strong>Name:</strong> <?= htmlspecialchars($user['name']) ?></div>
        <div><strong>OTP Access:</strong> Browser alert on this page</div>
    </div>

    <?php if ($message !== ''): ?>
        <div class="message <?= htmlspecialchars($messageType) ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="post">
        <div class="actions">
            <button class="secondary" type="submit" name="generate_otp">Generate OTP</button>
        </div>

        <div class="input-box">
            <label for="otp">Enter OTP</label>
            <input type="text" id="otp" name="otp" maxlength="4" inputmode="numeric" placeholder="Enter the OTP you received">
        </div>

        <div class="input-box">
            <label for="new_password">New Password</label>
            <input type="password" id="new_password" name="new_password" placeholder="Enter new password">
        </div>

        <div class="input-box">
            <label for="confirm_password">Confirm Password</label>
            <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm new password">
        </div>

        <div class="actions">
            <button class="primary" type="submit" name="update_password">Verify OTP and Update Password</button>
            <a class="back-link" href="../../profile/profile.php">Back to Profile</a>
        </div>
    </form>
</div>

<?php if ($otpAlert !== ''): ?>
<script>
alert(<?= json_encode('Your OTP is: ' . $otpAlert) ?>);
</script>
<?php endif; ?>

</body>
</html>
