<?php
session_start();
require 'db.php';

/* ======================
   HANDLE LOGIN
====================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!$username || !$password) {
        exit("Missing credentials");
    }

    $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if (!$admin || !password_verify($password, $admin['password'])) {
        exit("Wrong username or password");
    }

    $_SESSION['admin_logged_in'] = true;
    exit("OK");
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Admin Login</title>

<style>
body {
    background:#0e0e0e;
    font-family: Arial, sans-serif;
}
.box {
    width:300px;
    margin:120px auto;
    background:#1b1b1b;
    padding:20px;
}
h2 { color:#fff; text-align:center; }
input, button {
    width:100%;
    padding:10px;
    margin-top:10px;
}
input {
    background:#111;
    color:#fff;
    border:1px solid #333;
}
button {
    background:#ff2f2f;
    color:#fff;
    border:none;
    cursor:pointer;
}
#msg {
    color:red;
    text-align:center;
    margin-top:10px;
}
</style>
</head>

<body>

<div class="box">
    <h2>Admin Login</h2>
    <form id="loginForm">
        <input name="username" placeholder="Username" required>
        <input name="password" type="password" placeholder="Password" required>
        <button type="submit">Login</button>
        <div id="msg"></div>
    </form>
</div>

<script>
document.getElementById("loginForm").addEventListener("submit", e => {
    e.preventDefault();

    fetch("admin.php", {
        method: "POST",
        body: new FormData(e.target)
    })
    .then(r => r.text())
    .then(res => {
        if (res === "OK") {
            window.location.href = "application.php";
        } else {
            document.getElementById("msg").innerText = res;
        }
    });
});
</script>

</body>
</html>
