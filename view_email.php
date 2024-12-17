<?php
session_start();
include 'db.php';

if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$email_id = intval($_GET['id']);

// Get current user's details
$user_stmt = mysqli_prepare($conn, "SELECT username, email FROM gmail_users WHERE id = ?");
mysqli_stmt_bind_param($user_stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($user_stmt);
$user_result = mysqli_stmt_get_result($user_stmt);
$user = mysqli_fetch_assoc($user_result);

// Handle starring the email
if(isset($_POST['star'])) {
    $stmt = mysqli_prepare($conn, "UPDATE gmail_emails SET starred = 1 - starred WHERE id=? AND recipient_id=?");
    mysqli_stmt_bind_param($stmt, "ii", $email_id, $user_id);
    mysqli_stmt_execute($stmt);
}

// Fetch the email details
$stmt = mysqli_prepare($conn,
    "SELECT e.*, u.username as sender_name, u.email as sender_email, 
     (SELECT username FROM gmail_users WHERE id=e.recipient_id) as recipient_name,
     (SELECT email FROM gmail_users WHERE id=e.recipient_id) as recipient_email
     FROM gmail_emails e
     JOIN gmail_users u ON e.sender_id = u.id
     WHERE e.id=? AND (e.recipient_id=? OR e.sender_id=?)");
mysqli_stmt_bind_param($stmt, "iii", $email_id, $user_id, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$email = mysqli_fetch_assoc($result);

if(!$email) {
    die("Email not found or you do not have permission to view it.");
}

// Handle the reply submission
$message = '';
if (isset($_POST['reply']) && !empty($_POST['reply_body'])) {
    $reply_body = trim($_POST['reply_body']);
    // The current email's sender becomes the recipient of the reply
    $recipient_id = $email['sender_id'];
    $reply_subject = "Re: " . $email['subject'];
    $sender_id = $_SESSION['user_id'];

    $stmt = mysqli_prepare($conn, "INSERT INTO gmail_emails (sender_id, recipient_id, subject, body) VALUES (?, ?, ?, ?)");
    if (!$stmt) {
        die("Prepare failed: " . mysqli_error($conn));
    }
    mysqli_stmt_bind_param($stmt, "iiss", $sender_id, $recipient_id, $reply_subject, $reply_body);
    if (mysqli_stmt_execute($stmt)) {
        $message = "Reply sent successfully.";
    } else {
        $message = "Failed to send reply.";
    }
    mysqli_stmt_close($stmt);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Email - Gmail Clone</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Google Sans', 'Roboto', Arial, sans-serif;
            background-color: #f5f5f5;
            color: #202124;
            line-height: 1.6;
        }
        .app-container {
            display: flex;
            height: 100vh;
            overflow: hidden;
        }
        .sidebar {
            width: 250px;
            background-color: #ffffff;
            border-right: 1px solid #dadce0;
            padding: 20px;
            display: flex;
            flex-direction: column;
        }
        .sidebar-compose {
            background-color: #c2e7ff;
            border-radius: 50px;
            padding: 10px 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            cursor: pointer;
            transition: background-color 0.3s;
            color: #001d35;
            font-weight: 500;
        }
        .sidebar-compose .material-icons {
            margin-right: 10px;
            color: #001d35;
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
        }
        .sidebar-menu li.active,
        .sidebar-menu li:hover {
            background-color: #e8f0fe;
            color: #1a73e8;
        }
        .sidebar-menu li .material-icons {
            margin-right: 15px;
            color: #5f6368;
        }
        .main-content {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            background-color: #ffffff;
            overflow-y: auto;
        }
        .email-view-container {
            max-width: 800px;
            width: 100%;
            margin: 0 auto;
            padding: 20px;
        }
        .email-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 1px solid #dadce0;
            padding-bottom: 15px;
        }
        .email-subject {
            font-size: 1.5em;
            font-weight: 500;
        }
        .email-meta {
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }
        .email-sender {
            display: flex;
            align-items: center;
            margin-right: 20px;
        }
        .email-sender-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #1a73e8;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            font-weight: bold;
        }
        .email-sender-details {
            display: flex;
            flex-direction: column;
        }
        .email-sender-name {
            font-weight: 500;
        }
        .email-sender-email {
            color: #5f6368;
            font-size: 0.9em;
        }
        .email-date {
            color: #5f6368;
        }
        .email-body {
            background-color: #f5f5f5;
            padding: 20px;
            border-radius: 4px;
            white-space: pre-wrap;
            word-wrap: break-word;
            margin-bottom: 20px;
        }
        .email-actions {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        .btn {
            background-color: #1a73e8;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            margin-right: 10px;
        }
        .btn:hover {
            background-color: #185abc;
        }
        .btn .material-icons {
            margin-right: 5px;
        }
        .star-btn {
            background: none;
            color: #5f6368;
            border: none;
            cursor: pointer;
        }
        .star-btn.starred {
            color: #f7cb4d;
        }
        .reply-section {
            border-top: 1px solid #dadce0;
            padding-top: 20px;
        }
        .reply-form textarea {
            width: 100%;
            min-height: 150px;
            border: 1px solid #dadce0;
            border-radius: 4px;
            padding: 10px;
            margin-bottom: 10px;
            resize: vertical;
        }
        .message {
            color: #1a73e8;
            text-align: center;
            margin-bottom: 10px;
        }

        .reply-section {
            border-top: 1px solid #dadce0;
            padding-top: 20px;
        }
        .reply-btn {
            background-color: #1a73e8;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        .reply-btn:hover {
            background-color: #185abc;
        }
        .reply-btn .material-icons {
            margin-right: 5px;
        }
        .reply-form {
            display: none;
        }
        .reply-form.active {
            display: block;
        }
        .reply-form textarea {
            width: 100%;
            min-height: 150px;
            border: 1px solid #dadce0;
            border-radius: 4px;
            padding: 10px;
            margin-bottom: 10px;
            resize: vertical;
        }
        .message {
            color: #1a73e8;
            text-align: center;
            margin-bottom: 10px;
        }
    </style>
</head>
<script>
        function toggleReplyForm() {
            document.querySelector('.reply-form').classList.toggle('active');
        }
    </script>

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
            <div class="email-view-container">
                <div class="email-header">
                    <div class="email-subject">
                        <?php echo htmlspecialchars($email['subject']); ?>
                    </div>
                    <div class="email-actions">
                        <form method="post" style="margin-right: 10px;">
                            <button type="submit" name="star" class="btn star-btn <?php echo $email['starred'] ? 'starred' : ''; ?>">
                                <span class="material-icons"><?php echo $email['starred'] ? 'star' : 'star_border'; ?></span>
                            </button>
                        </form>
                        <a href="inbox.php" class="btn">
                            <span class="material-icons">arrow_back</span>
                            Back
                        </a>
                    </div>
                </div>

                <div class="email-meta">
                    <div class="email-sender">
                        <div class="email-sender-avatar">
                            <?php echo strtoupper(substr($email['sender_name'], 0, 1)); ?>
                        </div>
                        <div class="email-sender-details">
                            <div class="email-sender-name">
                                <?php echo htmlspecialchars($email['sender_name']); ?>
                            </div>
                            <div class="email-sender-email">
                                <?php echo htmlspecialchars($email['sender_email']); ?>
                            </div>
                        </div>
                    </div>
                    <div class="email-date">
                        <?php echo date('M d, Y h:i A', strtotime($email['timestamp'])); ?>
                    </div>
                </div>

                <div class="email-body">
                    <?php echo htmlspecialchars($email['body']); ?>
                </div>

                <?php if (!empty($message)): ?>
                    <div class="message">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <div class="reply-section">
        <div class="reply-btn" onclick="toggleReplyForm()">
            <span class="material-icons">reply</span>
            Reply
        </div>
        <form method="post" class="reply-form">
            <textarea name="reply_body" placeholder="Write your reply here..."></textarea>
            <button type="submit" name="reply" class="btn">
                <span class="material-icons">send</span>
                Send Reply
            </button>
        </form>
    </div>
            </div>
        </div>
    </div>
</body>
</html> 