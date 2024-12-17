<?php
session_start();
include 'db.php';

if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$query   = '';
$results = [];

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['q'])) {
    $query = trim($_GET['q']);
    if(!empty($query)) {
        $q = "%$query%";
        $stmt = mysqli_prepare($conn,
            "SELECT e.id, u.username as sender_name, e.subject, e.timestamp 
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
        while($row = mysqli_fetch_assoc($res)) {
            $results[] = $row;
        }
        mysqli_stmt_close($stmt);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Search Emails</title>
<style>
body {
    font-family: Arial, sans-serif;
    background:#f2f2f2;
}
.navbar {
    background: #333;
    color:#fff;
    padding:10px;
    display:flex;
    justify-content: space-around;
}
.navbar a {
    color:#fff;
    text-decoration:none;
    margin:0 10px;
}
.container {
    width:80%;
    margin:20px auto;
    background:#fff;
    padding:20px;
    border-radius:5px;
}
input[type=text] {
    width:70%;
    padding:10px;
    border:1px solid #ccc;
    border-radius:3px;
}
input[type=submit] {
    padding:10px 20px;
    background:#007bff;
    color:#fff;
    border:none;
    border-radius:3px;
    cursor:pointer;
}
.email-list {
    width:100%;
    border-collapse:collapse;
    margin-top:20px;
}
.email-list th, .email-list td {
    border-bottom:1px solid #ccc;
    padding:10px;
    text-align:left;
}
.email-list tr:hover {
    background:#f9f9f9;
}
a {
    text-decoration:none;
    color:#007bff;
}
</style>
</head>
<body>
<div class="navbar">
    <a href="inbox.php?folder=inbox">Inbox</a>
    <a href="inbox.php?folder=sent">Sent</a>
    <a href="inbox.php?folder=starred">Starred</a>
    <a href="compose.php">Compose</a>
    <a href="search.php">Search</a>
    <a href="logout.php">Logout</a>
</div>

<div class="container">
    <h2>Search Emails</h2>
    <form method="get">
        <input type="text" name="q" placeholder="Search by keyword..." value="<?php echo htmlspecialchars($query); ?>"/>
        <input type="submit" value="Search" />
    </form>
    <?php if(!empty($results)): ?>
    <table class="email-list">
        <tr>
            <th>Sender</th>
            <th>Subject</th>
            <th>Timestamp</th>
        </tr>
        <?php foreach($results as $row): ?>
        <tr>
            <td><?php echo htmlspecialchars($row['sender_name']); ?></td>
            <td><a href="view_email.php?id=<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['subject']); ?></a></td>
            <td><?php echo $row['timestamp']; ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php elseif(isset($_GET['q'])): ?>
    <p>No results found.</p>
    <?php endif; ?>
</div>
</body>
</html>
