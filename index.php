<?php
$host = 'localhost';
$user = 'yor_forger';
$pass = '1234';
$dbname = 'crunch';

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$output = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sql = $_POST['sql_query'] ?? '';
    if ($sql) {
        $result = $conn->query($sql);
        if ($result === TRUE) {
            $output = "<p>Query executed successfully.</p>";
        } elseif ($result) {
            $output .= "<pre>Results:<br>";
            while ($row = $result->fetch_assoc()) {
                $output .= print_r($row, true) . "<br>";
            }
            $output .= "</pre>";
        } else {
            $output = "<p>Error: " . $conn->error . "</p>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SQL Query UI</title>
    <link rel="stylesheet" href="/css/index.css">
</head>
<body>
<div class="background-container"></div>
<div class="main">
    <div class="sql-container">
        <form method="post">
            <textarea name="sql_query" rows="6" cols="60" placeholder="Write your SQL query here..."></textarea><br>
            <button type="submit">Run Query
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M12 10V3M13.4141 10.5857L18.3639 5.63599M14 12H21M13.4143 13.4141L18.364 18.3639M12 14V21M10.5859 13.4143L5.63611 18.364M10 12H3M10.5857 10.5859L5.63599 5.63611M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12ZM14 12C14 13.1046 13.1046 14 12 14C10.8954 14 10 13.1046 10 12C10 10.8954 10.8954 10 12 10C13.1046 10 14 10.8954 14 12Z" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path> </g></svg>
            </button>
        </form>
        <?php if (!empty($output)): ?>
            <div class="output">
                <?= $output ?>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>