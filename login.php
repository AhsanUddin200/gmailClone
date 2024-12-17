<?php
session_start();
include 'db.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = mysqli_prepare($conn, "SELECT id, password FROM gmail_users WHERE email=?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $id, $hash);
    mysqli_stmt_fetch($stmt);

    if ($id && password_verify($password, $hash)) {
        $_SESSION['user_id'] = $id;
        header("Location: inbox.php");
        exit;
    } else {
        $message = "Invalid email or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gmail Clone - Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #1a73e8;
            --text-color: #202124;
            --border-color: #dadce0;
            --bg-color: #f5f5f5;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Roboto', Arial, sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .login-container {
            width: 400px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
            padding: 40px;
        }

        .login-header {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 30px;
        }

        .login-header img {
            width: 75px;
            margin-bottom: 20px;
        }

        .login-header h2 {
            font-size: 24px;
            font-weight: 500;
        }

        .login-form input {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 16px;
            margin-bottom: 20px;
            outline: none;
            transition: border-color 0.2s;
        }

        .login-form input:focus {
            border-color: var(--primary-color);
        }

        .login-form button {
            width: 100%;
            background-color: var(--primary-color);
            color: #fff;
            border: none;
            border-radius: 4px;
            padding: 12px 16px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .login-form button:hover {
            background-color: #185abc;
        }

        .message {
            color: #d93025;
            text-align: center;
            margin-top: 10px;
        }

        .signup-link {
            text-align: center;
            margin-top: 20px;
        }

        .signup-link a {
            color: var(--primary-color);
            text-decoration: none;
        }

        .signup-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <img src="https://www.google.com/images/branding/googlelogo/1x/googlelogo_color_92x30dp.png" alt="Google Logo">
            <h2>Gmail Clone</h2>
        </div>
        <form method="post" class="login-form">
            <input type="email" name="email" placeholder="Email" required />
            <input type="password" name="password" placeholder="Password" required />
            <button type="submit">Sign In</button>
        </form>
        <?php if(!empty($message)): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <div class="signup-link">
            <a href="signup.php">Don't have an account? Sign up</a>
        </div>
    </div>
</body>
</html>