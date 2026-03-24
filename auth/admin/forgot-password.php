<?php
session_start();

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/otp_helpers.php';

ensureOtpVerificationSchema($conn);

$message = '';
$messageType = 'error';
$otpAlert = '';
$identity = trim($_POST['identity'] ?? ($_SESSION['admin_reset_identity'] ?? ''));
$adminResetId = (int) ($_SESSION['admin_reset_id'] ?? 0);
$adminResetName = trim((string) ($_SESSION['admin_reset_name'] ?? ''));

function findAdminForReset(mysqli $conn, string $identity): ?array
{
    $stmt = $conn->prepare(
        'SELECT id, name
         FROM admins
         WHERE email = ? OR phone = ? OR name = ?
         LIMIT 1'
    );
    $stmt->bind_param('sss', $identity, $identity, $identity);
    $stmt->execute();
    $result = $stmt->get_result();
    $admin = $result->fetch_assoc() ?: null;
    $stmt->close();

    return $admin;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['generate_otp'])) {
        if ($identity === '') {
            $message = 'Enter admin email, phone, or username first.';
        } else {
            $admin = findAdminForReset($conn, $identity);

            if (!$admin) {
                $message = 'Admin account not found.';
            } else {
                $_SESSION['admin_reset_id'] = (int) $admin['id'];
                $_SESSION['admin_reset_name'] = trim((string) $admin['name']);
                $_SESSION['admin_reset_identity'] = $identity;

                $adminResetId = (int) $admin['id'];
                $adminResetName = trim((string) $admin['name']);
                $resetKey = 'admin-' . $adminResetId;
                $activeOtp = createVisibleOtp($conn, $resetKey, 'admin');
                $otpAlert = (string) $activeOtp['otp'];
                $message = 'OTP generated successfully. Enter it to update the password.';
                $messageType = 'success';
            }
        }
    } elseif (isset($_POST['update_password'])) {
        $otp = trim($_POST['otp'] ?? '');
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $resetKey = 'admin-' . $adminResetId;

        if ($adminResetId <= 0) {
            $message = 'Generate an OTP first.';
        } elseif ($otp === '' || $newPassword === '' || $confirmPassword === '') {
            $message = 'OTP, new password, and confirm password are required.';
        } elseif (!preg_match('/^\d{4}$/', $otp)) {
            $message = 'Enter the 4 digit OTP shown in the alert.';
        } elseif (strlen($newPassword) < 6) {
            $message = 'Password must be at least 6 characters.';
        } elseif ($newPassword !== $confirmPassword) {
            $message = 'Both password fields must match.';
        } elseif (!verifyVisibleOtp($conn, $resetKey, 'admin', $otp)) {
            $message = 'OTP is invalid or expired. Generate a new OTP.';
        } else {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $updateStmt = $conn->prepare('UPDATE admins SET password = ? WHERE id = ?');
            $updateStmt->bind_param('si', $hashedPassword, $adminResetId);
            $updateStmt->execute();
            $updateStmt->close();

            clearOtpForPhoneRole($conn, $resetKey, 'admin');
            unset(
                $_SESSION['admin_reset_id'],
                $_SESSION['admin_reset_name'],
                $_SESSION['admin_reset_identity']
            );
            $message = 'Password updated successfully. Redirecting to login...';
            $messageType = 'success';
            header('Refresh:2; url=login.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin OTP Password Reset | ShopSphere</title>
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
    align-items:center;
    justify-content:center;
    background:linear-gradient(135deg,#020617,#0f172a);
    padding:24px;
    overflow:hidden;
}

body::before{
    content:'';
    position:absolute;
    inset:0;
    background:
        radial-gradient(circle at 20% 20%, rgba(37,99,235,.35), transparent 36%),
        radial-gradient(circle at 78% 80%, rgba(34,197,94,.22), transparent 30%);
}

.card{
    position:relative;
    z-index:1;
    width:min(490px,100%);
    max-height:90vh;
    overflow-y:auto;
    background:#020617;
    padding:34px;
    border-radius:24px;
    box-shadow:0 40px 90px rgba(0,0,0,.62);
    color:#fff;
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
    background:#0f172a;
    color:#e2e8f0;
    font-size:14px;
}

button:disabled{
    opacity:.6;
    cursor:not-allowed;
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
    background:#0f172a;
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
    margin-top:8px;
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
    background:linear-gradient(135deg,#2563eb,#38bdf8);
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
</style>
</head>
<body>

<div class="card">
    <span class="eyebrow">Admin Security</span>
    <h2>OTP Password Reset</h2>
    <p class="subtitle">Enter your admin email, phone, or username, generate a 4 digit OTP, see it in a page alert, and then update the password.</p>

    <?php if ($adminResetId > 0): ?>
        <div class="meta">
            <div><strong>Name:</strong> <?= htmlspecialchars($adminResetName !== '' ? $adminResetName : 'Admin') ?></div>
            <div><strong>OTP Access:</strong> Browser alert on this page</div>
        </div>
    <?php endif; ?>

    <?php if ($message !== ''): ?>
        <div class="message <?= htmlspecialchars($messageType) ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="post">
        <div class="input-box">
            <label for="identity">Admin Email / Phone / Username</label>
            <input type="text" id="identity" name="identity" value="<?= htmlspecialchars($identity) ?>" placeholder="Enter admin identity">
        </div>

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
            <a class="back-link" href="<?= isset($_SESSION['admin_id']) ? 'profile.php' : 'login.php' ?>">Back</a>
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
