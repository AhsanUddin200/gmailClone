<?php
session_start();
include 'db.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    if (!empty($username) && !empty($email) && !empty($password)) {
        $check = mysqli_prepare($conn, "SELECT id FROM gmail_users WHERE email=?");
        mysqli_stmt_bind_param($check, "s", $email);
        mysqli_stmt_execute($check);
        mysqli_stmt_store_result($check);

        if (mysqli_stmt_num_rows($check) > 0) {
            // Email already registered
            $message = "Email is already registered. <a href='login.php' style='color:#1a73e8;text-decoration:none;'>Sign in instead</a>";
        } else {
            // Insert user
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = mysqli_prepare($conn, "INSERT INTO gmail_users (username, email, password) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "sss", $username, $email, $hash);
            if (mysqli_stmt_execute($stmt)) {
                // On successful signup, redirect to login page
                header("Location: login.php");
                exit;
            } else {
                $message = "Signup failed. Please try again.";
            }
        }
    } else {
        $message = "All fields are required.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Create Your Account</title>
<style>
body {
    font-family: Arial, sans-serif;
    background: #f2f2f2;
    color: #202124;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
}

.container {
    background: #fff;
    width: 360px;
    padding: 40px 30px;
    border-radius: 8px;
    box-shadow:0 1px 3px rgba(0,0,0,0.1);
}

.container h1 {
    font-size: 24px;
    font-weight: 400;
    margin-bottom: 10px;
}

.container p {
    color: #5f6368;
    font-size: 14px;
    margin-bottom: 20px;
}

.input-row {
    display: flex;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 16px;
}

.input-row input[type=text],
.container input[type=text],
.container input[type=email],
.container input[type=password] {
    width: 100%;
    padding: 12px;
    border: 1px solid #dadce0;
    border-radius: 4px;
    font-size: 14px;
    outline: none;
}

.container input[type=text]:focus,
.container input[type=email]:focus,
.container input[type=password]:focus {
    border-color: #1a73e8;
}

.container .btn {
    width: 100%;
    padding: 10px;
    background:#1a73e8;
    color:#fff;
    border:none;
    border-radius:4px;
    font-size:14px;
    cursor:pointer;
    margin-top: 20px;
}
.container .btn:hover {
    background:#1669c1;
}

.message {
    margin-top: 10px;
    color: red;
    text-align: left;
    font-size: 14px;
    display: flex; /* Added for justify-content */
    justify-content: left; /* Corrected property */
}

.footer-link {
    text-align: center;
    margin-top: 10px;
    font-size:14px;
}
.footer-link a {
    color: #1a73e8;
    text-decoration:none;
}

.footer-link a:hover {
    text-decoration:underline;
}
</style>
</head>
<body>
<div class="container">
    <img src="https://upload.wikimedia.org/wikipedia/commons/2/2f/Google_2015_logo.svg" alt="Google Logo" style="width:75px; display:block; margin:0 auto 20px;">
    <h1>Create your Google Account</h1>
    <p>Continue to Gmail</p>
    <form method="post">
        <div class="input-row">
            <input type="text" name="username" placeholder="Username" required />
        </div>
        <input type="email" name="email" placeholder="Email" required />
        <input type="password" name="password" placeholder="Password" required />
        
        <input type="submit" class="btn" value="Sign Up" />
    </form>
    <div class="message"><?php echo $message; ?></div>
    <div class="footer-link">
        Already have an account?
        
         <a href="login.php"> Sign in instead</a>
    </div>
</div>
</body>
</html>
