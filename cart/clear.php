<?php
session_start();

/* CLEAR CART */
$_SESSION['cart'] = [];

/* REDIRECT AFTER ANIMATION */
header("refresh:1.6;url=view.php");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Clearing Cart...</title>
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
    background:radial-gradient(circle at top,#0f172a,#020617);
    overflow:hidden;
    color:#fff;
}

/* SHAKE + FADE */
.wrapper{
    text-align:center;
    animation:shake .6s ease-in-out infinite alternate;
}

@keyframes shake{
    from{transform:translateX(-6px)}
    to{transform:translateX(6px)}
}

/* GLOW CIRCLE */
.circle{
    width:140px;
    height:140px;
    border-radius:50%;
    background:rgba(239,68,68,.15);
    box-shadow:0 0 80px rgba(239,68,68,.9);
    display:flex;
    justify-content:center;
    align-items:center;
    margin:0 auto 25px;
    animation:pulse 1s infinite;
}

@keyframes pulse{
    0%{transform:scale(1)}
    50%{transform:scale(1.12)}
    100%{transform:scale(1)}
}

.circle span{
    font-size:48px;
}

/* TEXT */
h2{
    font-size:28px;
    color:#f87171;
    margin-bottom:10px;
}

p{
    font-size:15px;
    opacity:.85;
}
</style>
</head>

<body>

<div class="wrapper">
    <div class="circle">
        <span>🗑</span>
    </div>
    <h2>Cart Cleared!</h2>
    <p>Sab products uda diye gaye 😌</p>
</div>

</body>
</html>