<?php
$host = 'localhost';
$user = 'yor_forger';
$pass = '1234';
$dbname = 'crunch';

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$tables = [];
$tableData = '';
$tableOptions = '';
$selectedTable = '';
$searchQuery = '';

// Get list of all tables
$result = $conn->query("SHOW TABLES");
while ($row = $result->fetch_row()) {
    $tables[] = $row[0];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedTable = $_POST['table_name'] ?? '';
    $searchQuery = trim($_POST['search_query'] ?? '');

    if ($selectedTable !== '') {
        $likeClause = "";
        if ($searchQuery !== '') {
            // Get column names to build flexible WHERE clause
            $columnsResult = $conn->query("SHOW COLUMNS FROM `$selectedTable`");
            $columns = [];
            while ($col = $columnsResult->fetch_assoc()) {
                $columns[] = $col['Field'];
            }
            $likeParts = array_map(function ($col) use ($conn, $searchQuery) {
                $safeQuery = $conn->real_escape_string($searchQuery);
                return "`$col` LIKE '%$safeQuery%'";
            }, $columns);
            $likeClause = "WHERE " . implode(" OR ", $likeParts);
        }

        $query = "SELECT * FROM `$selectedTable` $likeClause";
        $result = $conn->query($query);

        if ($result && $result->num_rows > 0) {
            $tableData .= "<thead><tr>";
            while ($field = $result->fetch_field()) {
                $tableData .= "<th>" . htmlspecialchars($field->name) . "</th>";
            }
            $tableData .= "</tr></thead><tbody>";

            while ($row = $result->fetch_assoc()) {
                $tableData .= "<tr>";
                foreach ($row as $value) {
                    $tableData .= "<td>" . htmlspecialchars($value) . "</td>";
                }
                $tableData .= "</tr>";
            }

            $tableData .= "</tbody>";
        } else {
            $tableData = "<thead><tr><th>No results found.</th></tr></thead>";
        }
    }
}

//Dropdown options
foreach ($tables as $tbl) {
    $selected = ($tbl === $selectedTable) ? 'selected' : '';
    $tableOptions .= "<option value=\"$tbl\" $selected>$tbl</option>";
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
<header class="hero-header">
    <div class="wrapper">
        <img src="/images/crunchy-banner.png"/>
        <h2>サクサクの瞬間</h2>
    </div>
</header>
<div class="main">
    <div class="search-bar">
        <form method="post">
                <label for="table_name"></label>
                    <select name="table_name" id="table_name" required>
                        <?= $tableOptions ?>
                    </select><br><br>
                        <textarea name="search_query" rows="4" cols="60" placeholder="Search something (e.g., Naruto)..."><?= htmlspecialchars($searchQuery) ?></textarea><br>
            <button type="submit">
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 10V3M13.4141 10.5857L18.3639 5.63599M14 12H21M13.4143 13.4141L18.364 18.3639M12 14V21M10.5859 13.4143L5.63611 18.364M10 12H3M10.5857 10.5859L5.63599 5.63611M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12ZM14 12C14 13.1046 13.1046 14 12 14C10.8954 14 10 13.1046 10 12C10 10.8954 10.8954 10 12 10C13.1046 10 14 10.8954 14 12Z" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                <span>Search</span>
            </button>
        </form>
    </div>
    <div class="table-wrapper">
        <table class="items-table">
            <?= $tableData ?>
        </table>
    </div>
    <div class="button-container">
        <button class="Button-UI">
            <svg class="my-icon" fill="#ffffff" width="64px" height="64px" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" id="update-alt" class="icon glyph"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"><path d="M12,3A9,9,0,0,0,6,5.32V3A1,1,0,0,0,4,3V8a1,1,0,0,0,.92,1H10a1,1,0,0,0,0-2H7.11A7,7,0,0,1,19,12a1,1,0,0,0,2,0A9,9,0,0,0,12,3Z"></path><path d="M19.08,15H14a1,1,0,0,0,0,2h2.89A7,7,0,0,1,5,12a1,1,0,0,0-2,0,9,9,0,0,0,15,6.68V21a1,1,0,0,0,2,0V16A1,1,0,0,0,19.08,15Z"></path></g></svg>
            <span>Update</span>
        </button>
        <button class="Button-UI">
            <svg class="my-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" fill="#ffffff" stroke="#ffffff"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <title></title> <g id="Complete"> <g data-name="add" id="add-2"> <g> <line fill="none" stroke="#ffffff" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" x1="12" x2="12" y1="19" y2="5"></line> <line fill="none" stroke="#ffffff" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" x1="5" x2="19" y1="12" y2="12"></line> </g> </g> </g> </g></svg>
            <span>Insert</span>
        </button>
        <button class="Button-UI">
            <svg class="my-icon" fill="#ffffff" viewBox="0 0 14 14" role="img" focusable="false" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"><path d="m 11.324219,3.07539 -1.21875,1.21875 0.621093,0.6211 c 0.220313,0.22031 0.220313,0.57656 0,0.79453 L 10.31875,6.11758 C 10.595313,6.7293 10.75,7.40899 10.75,8.12383 c 0,2.69297 -2.1820313,4.875 -4.875,4.875 C 3.1820313,12.99883 1,10.81914 1,8.12617 c 0,-2.69296 2.1820312,-4.875 4.875,-4.875 0.7148437,0 1.3945312,0.15469 2.00625,0.43125 L 8.2890625,3.27461 C 8.509375,3.0543 8.865625,3.0543 9.0835937,3.27461 L 9.7046875,3.8957 10.923438,2.67695 11.324219,3.07539 Z m 1.394531,-0.66797 -0.5625,0 c -0.154688,0 -0.28125,0.12657 -0.28125,0.28125 0,0.15469 0.126562,0.28125 0.28125,0.28125 l 0.5625,0 C 12.873438,2.96992 13,2.84336 13,2.68867 13,2.53399 12.873438,2.40742 12.71875,2.40742 Z M 11.3125,1.00117 c -0.154688,0 -0.28125,0.12657 -0.28125,0.28125 l 0,0.5625 c 0,0.15469 0.126562,0.28125 0.28125,0.28125 0.154688,0 0.28125,-0.12656 0.28125,-0.28125 l 0,-0.5625 c 0,-0.15468 -0.126562,-0.28125 -0.28125,-0.28125 z m 0.794531,1.28907 0.398438,-0.39844 c 0.110156,-0.11016 0.110156,-0.28828 0,-0.39844 -0.110157,-0.11016 -0.288281,-0.11016 -0.398438,0 L 11.708594,1.8918 c -0.110157,0.11015 -0.110157,0.28828 0,0.39844 0.1125,0.11015 0.290625,0.11015 0.398437,0 z m -1.589062,0 c 0.110156,0.11015 0.288281,0.11015 0.398437,0 0.110156,-0.11016 0.110156,-0.28829 0,-0.39844 L 10.517969,1.49336 c -0.110156,-0.11016 -0.288281,-0.11016 -0.398438,0 -0.110156,0.11016 -0.110156,0.28828 0,0.39844 l 0.398438,0.39844 z m 1.589062,0.79687 c -0.110156,-0.11016 -0.288281,-0.11016 -0.398437,0 -0.110157,0.11016 -0.110157,0.28828 0,0.39844 l 0.398437,0.39844 c 0.110157,0.11015 0.288281,0.11015 0.398438,0 0.110156,-0.11016 0.110156,-0.28829 0,-0.39844 L 12.107031,3.08711 Z M 3.625,7.37617 c 0,-0.82734 0.6726562,-1.5 1.5,-1.5 0.20625,0 0.375,-0.16875 0.375,-0.375 0,-0.20625 -0.16875,-0.375 -0.375,-0.375 -1.2398438,0 -2.25,1.01016 -2.25,2.25 0,0.20625 0.16875,0.375 0.375,0.375 0.20625,0 0.375,-0.16875 0.375,-0.375 z"></path></g></svg>
            <span>Delete</span>
        </button>
    </div>
</div>
   <footer class="footer-container">
        <div class="footer-1">
            <div class="footer-info">
                <p>Bussiness Name: 알렉스 <br>Representative: 나레<br> Contact Number:
                    Address: 60, Seoul, Korea<br>Email: allegsumaga@daum.net<br>Phone: 00000
                    ©2025 .알렉스 | Operated under FEDRA Civilian Oversight — Region: EU Zone 04. Seville, Spain
                </p>
                <ul class="footer-terms">
                    <li>
                        <a>Terms and Conditions</a>
                    </li>
                    <li>
                        <a>Privacy Policy</a>
                    </li>
                    <li>
                        <a>Cookie Setttings</a>
                    </li>
                </ul>
            </div>
        </div>
    </footer>
</body>
</html>