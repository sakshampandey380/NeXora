<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if(!isset($_SESSION['user_id'])){
    header("Location: ../auth/user/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$msg = "";

/* FETCH USER DATA */

$user = $conn->query("
SELECT name,email,phone,profile_image 
FROM users 
WHERE id=$user_id
")->fetch_assoc();


/* UPDATE PROFILE */

if($_SERVER['REQUEST_METHOD'] === 'POST'){

$name  = $_POST['name'];
$email = $_POST['email'];
$phone = $_POST['phone'];

$profile_image = $user['profile_image'];

/* IMAGE UPLOAD */

if(!empty($_FILES['image']['name'])){

$imgName = time().'_'.$_FILES['image']['name'];
$tmp = $_FILES['image']['tmp_name'];

$uploadPath = __DIR__."/../uploads/profile/".$imgName;

if(move_uploaded_file($tmp,$uploadPath)){
$profile_image = $imgName;
}

}

/* UPDATE QUERY */

$stmt = $conn->prepare("
UPDATE users 
SET name=?, email=?, phone=?, profile_image=? 
WHERE id=?
");

$stmt->bind_param("ssssi",$name,$email,$phone,$profile_image,$user_id);

if($stmt->execute()){
$msg = "Profile updated successfully!";
}

$stmt->close();

/* REFRESH USER DATA */

$user = $conn->query("
SELECT name,email,phone,profile_image 
FROM users 
WHERE id=$user_id
")->fetch_assoc();

}

?>

<!DOCTYPE html>
<html>
<head>
<title>Update Profile</title>

<style>

*{
margin:0;
padding:0;
box-sizing:border-box;
font-family:Segoe UI;
}

/* ANIMATED BACKGROUND */

body{

height:100vh;
display:flex;
justify-content:center;
align-items:center;

background:linear-gradient(
270deg,
#ff4d4d,
#4da6ff,
#4dff88,
#ffcc4d
);

background-size:800% 800%;

animation:bgMove 12s ease infinite;

}

@keyframes bgMove{

0%{background-position:0% 50%;}
50%{background-position:100% 50%;}
100%{background-position:0% 50%;}

}

/* BACK BUTTON */

.back-btn{

position:absolute;
top:25px;
left:25px;

padding:12px 22px;

border-radius:30px;

background:rgba(255,255,255,.2);

color:white;

text-decoration:none;

font-weight:600;

transition:.3s;

border:1px solid rgba(255,255,255,.3);

}

.back-btn:hover{

background:white;
color:black;

transform:translateX(-5px);

}

/* CARD */

.card{

width:420px;
padding:35px;

background:rgba(255,255,255,.15);
backdrop-filter:blur(12px);

border-radius:20px;

box-shadow:0 20px 50px rgba(0,0,0,.4);

}

h2{

text-align:center;
margin-bottom:20px;
color:white;

}

/* PROFILE IMAGE */

.profile-img{

width:100px;
height:100px;
border-radius:50%;
object-fit:cover;
display:block;
margin:0 auto 15px auto;
border:3px solid white;

}

/* INPUTS */

.input-group{

margin-bottom:16px;

}

label{

display:block;
margin-bottom:6px;
color:white;
font-size:14px;

}

input{

width:100%;
padding:12px;

border-radius:10px;
border:none;

outline:none;

background:rgba(255,255,255,.2);

color:white;

font-size:14px;

}

/* BUTTON */

button{

width:100%;
padding:14px;

border:none;

border-radius:30px;

background:linear-gradient(
135deg,
#22c55e,
#16a34a
);

color:white;

font-size:16px;

font-weight:bold;

cursor:pointer;

transition:.3s;

}

button:hover{

transform:scale(1.05);
box-shadow:0 0 20px rgba(34,197,94,.8);

}

/* MESSAGE */

.msg{

margin-bottom:15px;

padding:10px;

background:#16a34a;

border-radius:8px;

color:white;

text-align:center;

}

</style>

</head>

<body>

<a class="back-btn" href="profile.php">⬅ Back to Profile</a>

<div class="card">

<h2>Update Profile</h2>

<?php if($msg){ ?>
<div class="msg"><?php echo $msg; ?></div>
<?php } ?>

<?php if($user['profile_image']){ ?>
<img class="profile-img" src="../uploads/profile/<?php echo $user['profile_image']; ?>">
<?php } ?>

<form method="POST" enctype="multipart/form-data">

<div class="input-group">
<label>Name</label>
<input type="text" name="name"
value="<?php echo htmlspecialchars($user['name']); ?>"
required>
</div>

<div class="input-group">
<label>Email</label>
<input type="email" name="email"
value="<?php echo htmlspecialchars($user['email']); ?>"
required>
</div>

<div class="input-group">
<label>Phone</label>
<input type="text" name="phone"
value="<?php echo htmlspecialchars($user['phone']); ?>"
required>
</div>

<div class="input-group">
<label>Change Profile Picture</label>
<input type="file" name="image">
</div>

<button type="submit">Update Profile</button>

</form>

</div>

</body>
</html>