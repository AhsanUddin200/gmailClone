<?php
session_start();
include 'db.php';

// Ensure user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$message = '';

// Get current user's details
$user_stmt = mysqli_prepare($conn, "SELECT username, email FROM gmail_users WHERE id = ?");
mysqli_stmt_bind_param($user_stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($user_stmt);
$user_result = mysqli_stmt_get_result($user_stmt);
$user = mysqli_fetch_assoc($user_result);

// Handle file upload
function uploadAttachment($file) {
    $upload_dir = 'uploads/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $filename = uniqid() . '_' . basename($file['name']);
    $target_path = $upload_dir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        return $filename;
    }
    return null;
}

// If form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recipient_email = trim($_POST['recipient']);
    $subject = trim($_POST['subject']);
    $body = trim($_POST['body']);
    $sender_id = $_SESSION['user_id'];

    // Handle attachment
    $attachment_filename = null;
    if (!empty($_FILES['attachment']['name'])) {
        $attachment_filename = uploadAttachment($_FILES['attachment']);
    }

    // 1. Get recipient id
    $stmt = mysqli_prepare($conn, "SELECT id FROM gmail_users WHERE email=?");
    if (!$stmt) {
        die("Error preparing SELECT statement: " . mysqli_error($conn));
    }
    mysqli_stmt_bind_param($stmt, "s", $recipient_email);
    if (!mysqli_stmt_execute($stmt)) {
        die("Error executing SELECT statement: " . mysqli_error($conn));
    }
    mysqli_stmt_bind_result($stmt, $recipient_id);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    if($recipient_id) {
        // 2. Insert email with optional attachment
        $stmt = mysqli_prepare($conn, "INSERT INTO gmail_emails (sender_id, recipient_id, subject, body, attachment) VALUES (?, ?, ?, ?, ?)");
        if (!$stmt) {
            die("Error preparing INSERT statement: " . mysqli_error($conn));
        }
        mysqli_stmt_bind_param($stmt, "iisss", $sender_id, $recipient_id, $subject, $body, $attachment_filename);
        if (mysqli_stmt_execute($stmt)) {
            $message = "Email sent successfully.";
        } else {
            $message = "Failed to send email: " . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
    } else {
        $message = "Recipient not found.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compose Email - Gmail Clone</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        :root {
            --primary-color: #1a73e8;
            --sidebar-bg: #f6f8fc;
            --text-color: #202124;
            --border-color: #dadce0;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Roboto', Arial, sans-serif;
            background-color: #f5f5f5;
            color: var(--text-color);
            line-height: 1.6;
        }
        .app-container {
            display: flex;
            height: 100vh;
            overflow: hidden;
        }
        .sidebar {
            width: 280px;
            background-color: var(--sidebar-bg);
            border-right: 1px solid var(--border-color);
            padding: 15px;
            display: flex;
            flex-direction: column;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        }
        .sidebar-compose {
            background-color: white;
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 12px 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .sidebar-compose:hover {
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
            border-color: var(--primary-color);
        }
        .sidebar-compose .material-icons {
            margin-right: 10px;
            color: var(--primary-color);
            font-size: 20px;
        }
        .sidebar-menu {
            list-style-type: none;
        }
        .sidebar-menu li {
            padding: 10px 15px;
            border-radius: 0 25px 25px 0;
            margin: 5px 0;
            cursor: pointer;
            display: flex;
            align-items: center;
            transition: all 0.2s;
        }
        .sidebar-menu li.active,
        .sidebar-menu li:hover {
            background-color: rgba(26, 115, 232, 0.1);
            color: var(--primary-color);
        }
        .sidebar-menu li .material-icons {
            margin-right: 15px;
            color: #5f6368;
            font-size: 20px;
        }
        .main-content {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            background-color: white;
            overflow-y: auto;
        }
        .compose-container {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Top bar with hamburger and back arrow */
        .top-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 15px;
        }
        .top-bar .material-icons {
            cursor: pointer;
            color: var(--text-color);
        }
        .top-bar-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .compose-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 15px;
        }
        .compose-header h2 {
            color: var(--text-color);
            font-weight: 500;
            margin: 0;
        }
        .compose-form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .compose-form input, 
        .compose-form textarea {
            border: 1px solid var(--border-color);
            border-radius: 4px;
            padding: 10px 12px;
            font-size: 14px;
            transition: border-color 0.2s;
        }
        .compose-form input:focus, 
        .compose-form textarea:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 2px rgba(26, 115, 232, 0.1);
        }
        .compose-form textarea {
            resize: vertical;
            min-height: 200px;
        }
        .attachment-section {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
        }
        .attachment-input {
            display: none;
        }
        .attachment-label {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            color: var(--primary-color);
            font-weight: 500;
        }
        .attachment-label:hover {
            text-decoration: underline;
        }
        .compose-actions {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-top: 20px;
        }
        .send-btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: background-color 0.2s;
        }
        .send-btn:hover {
            background-color: #185abc;
        }
        .message {
            text-align: center;
            padding: 10px;
            border-radius: 4px;
            margin-top: 15px;
            font-weight: 500;
        }
        .message.success {
            background-color: rgba(26, 115, 232, 0.1);
            color: var(--primary-color);
        }
        .message.error {
            background-color: rgba(234, 67, 53, 0.1);
            color: #d93025;
        }
        @media (max-width: 768px) {
            .app-container {
                flex-direction: column;
            }
            .sidebar {
                width: 100%;
                height: auto;
                border-right: none;
                border-bottom: 1px solid var(--border-color);
                flex-direction: row;
                align-items: center;
                padding: 10px 15px;
            }
            .sidebar-menu {
                display: flex;
                flex-grow: 1;
                justify-content: space-around;
            }
            .sidebar-menu li {
                margin: 0;
                padding: 5px 10px;
            }
        }
    </style>
</head>
<body>
    <div class="app-container">
        <div class="sidebar">
            <div class="sidebar-compose" onclick="window.location.href='compose.php'">
                <span class="material-icons">add</span>
                Compose
            </div>
            <ul class="sidebar-menu">
                <li onclick="window.location.href='inbox.php?folder=inbox'">
                    <span class="material-icons">inbox</span>
                    Inbox
                </li>
                <li onclick="window.location.href='inbox.php?folder=starred'">
                    <span class="material-icons">star</span>
                    Starred
                </li>
                <li onclick="window.location.href='inbox.php?folder=sent'">
                    <span class="material-icons">send</span>
                    Sent
                </li>
                <li onclick="window.location.href='logout.php'">
                    <span class="material-icons">logout</span>
                    Logout
                </li>
            </ul>
        </div>
        <div class="main-content">
            <div class="compose-container">
                <!-- Top Bar with hamburger and back arrow -->
                <div class="top-bar">
                  
                    <!-- Back arrow icon that redirects to inbox -->
                    <span class="material-icons" onclick="window.location.href='inbox.php'">arrow_back</span>
                </div>

                <div class="compose-header">
                    <h2>New Message</h2>
                </div>
                <form method="post" class="compose-form" enctype="multipart/form-data">
                    <input type="email" name="recipient" placeholder="To" 
                           required value="<?php echo isset($recipient_email) ? htmlspecialchars($recipient_email) : ''; ?>"/>
                    
                    <input type="text" name="subject" placeholder="Subject" 
                           value="<?php echo isset($subject) ? htmlspecialchars($subject) : ''; ?>"/>
                    
                    <textarea name="body" placeholder="Message"><?php 
                        echo isset($body) ? htmlspecialchars($body) : ''; 
                    ?></textarea>
                    
                    <div class="attachment-section">
                        <input type="file" name="attachment" id="attachment" class="attachment-input" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                        <label for="attachment" class="attachment-label">
                            <span class="material-icons">attach_file</span>
                            Attach file
                        </label>
                    </div>
                    
                    <div class="compose-actions">
                        <button type="submit" class="send-btn">
                            <span class="material-icons">send</span>
                            Send
                        </button>
                    </div>
                </form>

                <?php if(!empty($message)): ?>
                    <div class="message <?php echo $message === 'Email sent successfully.' ? 'success' : 'error'; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script>
        document.querySelector('#attachment').addEventListener('change', function(e) {
            const fileName = e.target.files[0] ? e.target.files[0].name : 'No file chosen';
            const label = document.querySelector('.attachment-label');
            label.innerHTML = `<span class="material-icons">attach_file</span>${fileName}`;
        });
    </script>
</body>
</html>
