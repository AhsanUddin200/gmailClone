    <?php
    session_start();
    include 'db.php';

    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }

    $user_id = $_SESSION['user_id'];

    // Handle deletion of selected emails
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete']) && !empty($_POST['email_ids'])) {
        $email_ids = $_POST['email_ids'];
        $in_clause = implode(',', array_map('intval', $email_ids));
        $delete_sql = "DELETE FROM gmail_emails WHERE recipient_id = $user_id AND id IN ($in_clause)";
        mysqli_query($conn, $delete_sql);
    }

    // Check if a search query is provided
    $search_query = isset($_GET['q']) ? trim($_GET['q']) : '';
    $folder = isset($_GET['folder']) ? $_GET['folder'] : 'inbox';

    if (!empty($search_query)) {
        // Perform search
        $q = "%$search_query%";
        $stmt = mysqli_prepare($conn,
            "SELECT e.id, u.username as sender_name, e.subject, e.timestamp, e.body
            FROM gmail_emails e
            JOIN gmail_users u ON e.sender_id = u.id
            WHERE e.recipient_id = ? AND (e.subject LIKE ? OR e.body LIKE ?)
            ORDER BY e.timestamp DESC"
        );
        if (!$stmt) {
            die("Prepare failed: " . mysqli_error($conn));
        }
        mysqli_stmt_bind_param($stmt, "iss", $user_id, $q, $q);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $emails = mysqli_fetch_all($res, MYSQLI_ASSOC);
        mysqli_stmt_close($stmt);
    } else {
        // Display emails based on folder
        if ($folder === 'sent') {
            $sql = "SELECT e.id, u.username as sender_name, e.subject, e.timestamp, e.body 
                    FROM gmail_emails e
                    JOIN gmail_users u ON e.recipient_id = u.id
                    WHERE e.sender_id = $user_id
                    ORDER BY e.timestamp DESC";
        } elseif ($folder === 'starred') {
            $sql = "SELECT e.id, u.username as sender_name, e.subject, e.timestamp, e.body 
                    FROM gmail_emails e
                    JOIN gmail_users u ON e.sender_id = u.id
                    WHERE e.recipient_id = $user_id AND e.starred = 1
                    ORDER BY e.timestamp DESC";
        } else {
            $sql = "SELECT e.id, u.username as sender_name, e.subject, e.timestamp, e.body 
                    FROM gmail_emails e
                    JOIN gmail_users u ON e.sender_id = u.id
                    WHERE e.recipient_id = $user_id
                    ORDER BY e.timestamp DESC";
        }

        $result = mysqli_query($conn, $sql);
        $emails = mysqli_fetch_all($result, MYSQLI_ASSOC);
    }

    // Get username of current user
    $user_stmt = mysqli_prepare($conn, "SELECT username FROM gmail_users WHERE id = ?");
    mysqli_stmt_bind_param($user_stmt, "i", $user_id);
    mysqli_stmt_execute($user_stmt);
    $user_result = mysqli_stmt_get_result($user_stmt);
    $user = mysqli_fetch_assoc($user_result);
    $username = $user['username'];
    $colors = ['red', 'blue', 'yellow', 'green', 'purple', 'orange', 'pink', 'teal', 'cyan'];
$selectedColor = $colors[array_rand($colors)];
    ?>



    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Gmail Clone</title>
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
            .circle-container {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            color: #fff;
            font-size: 20px;
            font-weight: bold;
            text-align: center;
            background-color: <?php echo htmlspecialchars($selectedColor); ?>;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
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
                transition: width 0.3s ease;
            }
            .sidebar.hidden {
                width: 0;
                padding: 20px 0;
                overflow: hidden;
            }
            .sidebar-compose {
                background-color: #ffffff;
                border: 1px solid #dadce0;
                border-radius: 50px;
                padding: 10px 20px;
                margin-bottom: 20px;
                display: flex;
                align-items: center;
                cursor: pointer;
                transition: background-color 0.3s;
                white-space: nowrap;
            }
            .sidebar.hidden .sidebar-compose {
                display: none;
            }
            .sidebar-compose:hover {
                background-color: #f5f5f5;
            }
            .sidebar-compose .material-icons {
                margin-right: 10px;
                color: #5f6368;
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
                white-space: nowrap;
            }
            .sidebar.hidden .sidebar-menu li {
                padding: 10px;
                justify-content: center;
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
            .sidebar.hidden .sidebar-menu li .material-icons {
                margin-right: 0;
            }

            .main-content {
                flex-grow: 1;
                display: flex;
                flex-direction: column;
                background-color: #ffffff;
            }
            .main-header {
                display: flex;
                align-items: center;
                padding: 10px 20px;
                border-bottom: 1px solid #dadce0;
            }
            .hamburger {
                cursor: pointer;
                margin-right: 20px;
            }
            .search-container {
                flex-grow: 1;
                margin: 0 20px;
            }
            .search-input {
                width: 100%;
                padding: 10px 15px;
                border: 1px solid #dadce0;
                border-radius: 50px;
                outline: none;
            }
            .user-profile {
                display: flex;
                align-items: center;
                gap: 10px;
            }
            .email-list {
                flex-grow: 1;
                overflow-y: auto;
            }
            .header-actions {
                display: flex;
                align-items: center;
                padding: 10px 20px;
            }
            .delete-button {
                background: #d32f2f;
                color: #fff;
                border: none;
                border-radius: 25px;
                padding: 8px 15px;
                cursor: pointer;
                margin-right: 10px;
            }
            .delete-button:hover {
                background: #b71c1c;
            }
            .email-item {
                display: flex;
                align-items: center;
                padding: 15px 20px;
                border-bottom: 1px solid #f1f3f4;
                cursor: pointer;
                transition: background-color 0.3s;
            }
            .email-item:hover {
                background-color: #f5f5f5;
            }
            .email-checkbox {
                margin-right: 15px;
                cursor: pointer;
            }
            .email-star {
                margin-right: 15px;
                color: #5f6368;
                cursor: pointer;
            }
            .email-star.starred {
                color: #f7cb4d;
            }
            .email-sender {
                flex-basis: 200px;
                font-weight: bold;
                margin-right: 15px;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            .email-subject {
                flex-grow: 1;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            .email-preview {
                color: #5f6368;
                margin-left:10px;
                font-weight: normal;
            }
            .email-date {
                color: #5f6368;
                margin-left: 15px;
            }
            .no-emails {
                text-align: center;
                padding: 50px;
                color: #5f6368;
            }
        </style>
    </head>
    <body>
        <div class="app-container">
            <div class="sidebar" id="sidebar">
                <div style="margin-bottom:20px;">
                        <img src="data:image/png;base64,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" alt="Gmail Logo" style="width:90px; height:auto; margin-left:20px;">
                </div>
                <div class="sidebar-compose" onclick="window.location.href='compose.php'">
                    <span class="material-icons">add</span>
                    Compose
                </div>
                <ul class="sidebar-menu">
                    <li <?php echo ($folder == 'inbox' ? 'class="active"' : ''); ?> onclick="window.location.href='inbox.php?folder=inbox'">
                        <span class="material-icons">inbox</span>
                        <span class="menu-text">Inbox</span>
                    </li>
                    <li <?php echo ($folder == 'starred' ? 'class="active"' : ''); ?> onclick="window.location.href='inbox.php?folder=starred'">
                        <span class="material-icons">star</span>
                        <span class="menu-text">Starred</span>
                    </li>
                    <li <?php echo ($folder == 'sent' ? 'class="active"' : ''); ?> onclick="window.location.href='inbox.php?folder=sent'">
                        <span class="material-icons">send</span>
                        <span class="menu-text">Sent</span>
                    </li>
                    <li onclick="window.location.href='logout.php'">
                        <span class="material-icons">logout</span>
                        <span class="menu-text">Logout</span>
                    </li>
                </ul>
            </div>
            <div class="main-content">
                <div class="main-header">
                    <span class="material-icons hamburger" id="hamburger">menu</span>
                    <div class="search-container">
                        <form method="get">
                            <input type="text" name="q" class="search-input" 
                                placeholder="Search mail" 
                                value="<?php echo htmlspecialchars($search_query); ?>">
                        </form>
                    </div>
                    <div class="circle-container">
        <?php echo htmlspecialchars($username); ?>
    </div>
                </div>
                <div class="email-list">
                    <?php if (!empty($emails)): ?>
                        <form method="post">
                            <div class="header-actions">
                                <button type="submit" name="delete" class="delete-button">Delete Selected</button>
                            </div>
                            <?php foreach ($emails as $row): ?>
                                <div class="email-item">
                                    <input type="checkbox" class="email-checkbox" name="email_ids[]" value="<?php echo $row['id']; ?>">
                                    <span class="material-icons email-star <?php echo (isset($row['starred']) && $row['starred'] ? 'starred' : ''); ?>">
                                        star_border
                                    </span>
                                    <div class="email-sender"><?php echo htmlspecialchars($row['sender_name']); ?></div>
                                    <div class="email-subject" onclick="window.location.href='view_email.php?id=<?php echo $row['id']; ?>'">
                                        <?php echo htmlspecialchars($row['subject']); ?>
                                        <span class="email-preview"><?php echo htmlspecialchars(substr(strip_tags($row['body']), 0, 100)); ?>...</span>
                                    </div>
                                    <div class="email-date"><?php echo date('M d', strtotime($row['timestamp'])); ?></div>
                                </div>
                            <?php endforeach; ?>
                        </form>
                    <?php else: ?>
                        <div class="no-emails">
                            <?php 
                            if (!empty($search_query)) {
                                echo "No results found for \"" . htmlspecialchars($search_query) . "\"";
                            } else {
                                echo "No emails in this folder";
                            }
                            ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <script>
    document.getElementById('hamburger').addEventListener('click', function() {
        var sidebar = document.getElementById('sidebar');
        sidebar.classList.toggle('hidden');
    });
    </script>
    </body>
    </html>
