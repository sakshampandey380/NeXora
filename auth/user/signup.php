<?php
require_once __DIR__ . '/../../config/db.php';

/* ===============================
   INITIAL VARIABLES
================================ */
$name = '';
$email = '';
$phone = '';
$category = '';
$password = '';
$confirm = '';
$profile_image = '';
$success_message = '';
$errors = [];

$categories = [
    ['id' => 1, 'name' => 'Fruits'],
    ['id' => 2, 'name' => 'Vegetables'],
    ['id' => 3, 'name' => 'Cloths'],
    ['id' => 4, 'name' => 'Sports'],
    ['id' => 5, 'name' => 'Toys']
];

/* ===============================
   FORM SUBMISSION
================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    /* VALIDATION */
    if ($name === '') $errors[] = 'Name is required.';
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
    if ($phone === '') $errors[] = 'Phone is required.';
    if ($password === '') $errors[] = 'Password is required.';
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';

    /* IMAGE UPLOAD */
    if (!empty($_FILES['profile_image']['name'])) {

        $upload_dir = __DIR__ . '/../../uploads/profile/';

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $ext = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];

        if (!in_array($ext, $allowed)) {
            $errors[] = 'Only JPG, PNG, WEBP images allowed.';
        } else {
            $profile_image = time() . '_' . uniqid() . '.' . $ext;
            move_uploaded_file(
                $_FILES['profile_image']['tmp_name'],
                $upload_dir . $profile_image
            );
        }
    } else {
        $errors[] = 'Profile image is required.';
    }

    /* DATABASE */
    if (empty($errors)) {

        $stmt = $conn->prepare("SELECT id FROM users WHERE email=? OR phone=?");
        $stmt->bind_param("ss", $email, $phone);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $errors[] = 'Email or phone already registered.';
        } else {

            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare(
                "INSERT INTO users (name, email, phone, fav_category, password, profile_image)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param(
                "ssssss",
                $name,
                $email,
                $phone,
                $category,
                $hash,
                $profile_image
            );

            if ($stmt->execute()) {
                $success_message = 'Account created successfully! Redirecting to login...';
                header("refresh:2;url=login.php");
            } else {
                $errors[] = 'Error creating account.';
            }
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Sign Up</title>
    <link rel="stylesheet" href="../../assets/css/user_signup_page.css">
</head>

<body>

    <section>
        <div class="leaves">
            <div class="set">
                <div><img src="../../assets/images/user_signup_img's/leaf_01.png"></div>
                <div><img src="../../assets/images/user_signup_img's/leaf_02.png"></div>
                <div><img src="../../assets/images/user_signup_img's/leaf_03.png"></div>
                <div><img src="../../assets/images/user_signup_img's/leaf_04.png"></div>
                <div><img src="../../assets/images/user_signup_img's/leaf_01.png"></div>
                <div><img src="../../assets/images/user_signup_img's/leaf_02.png"></div>
                <div><img src="../../assets/images/user_signup_img's/leaf_03.png"></div>
                <div><img src="../../assets/images/user_signup_img's/leaf_04.png"></div>
            </div>
        </div>

        <img src="../../assets/images/user_signup_img's/bg.jpg" class="bg">
        <img src="../../assets/images/user_signup_img's/girl.png" class="girl">
        <img src="../../assets/images/user_signup_img's/trees.png" class="trees">

        <div class="login">
            <h2>Create Account</h2>

            <?php if ($success_message): ?>
                <div class="success">
                    <?= htmlspecialchars($success_message) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="errors">
                    <?php foreach ($errors as $e): ?>
                        <p>❌ <?= htmlspecialchars($e) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">

                <div class="inputBox">
                    <input type="text" name="name" placeholder="Full Name" required value="<?= htmlspecialchars($name) ?>">
                </div>

                <div class="inputBox">
                    <input type="email" name="email" placeholder="Email" required value="<?= htmlspecialchars($email) ?>">
                </div>

                <div class="inputBox">
                    <input type="text" name="phone" placeholder="Phone" required value="<?= htmlspecialchars($phone) ?>">
                </div>

                <div class="inputBox">
                    <select name="category" required>
                        <option value="">-- Favorite Category --</option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= ($category == $c['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- PROFILE IMAGE UPLOAD -->
                <div class="inputBox">
                    <input type="file" name="profile_image" accept="image/*" required>
                </div>

                <div class="inputBox">
                    <input type="password" name="password" placeholder="Password" required>
                </div>

                <div class="inputBox">
                    <input type="password" name="confirm_password" placeholder="Confirm Password" required>
                </div>

                <div class="inputBox">
                    <button type="submit" id="btn">Sign Up</button>
                </div>

            </form>

            <div class="group">
                <a href="login.php">Already have an account? Log in</a>
            </div>
        </div>
    </section>

    <script src="../../assets/js/user_signup_page.js"></script>
</body>

</html>