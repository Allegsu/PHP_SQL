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
<div class="main">
    <div class="wrapper">
        <form method="post">
            <textarea name="sql_query" rows="6" cols="60" placeholder="Write your SQL query here..."></textarea><br>
            <button type="submit">Run</button>
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