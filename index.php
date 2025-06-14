<?php
error_reporting(E_ALL & ~E_DEPRECATED & ~E_WARNING);

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

$feedback = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'insert') {


    // continues...
}

    $selectedTable = $_POST['table_name'] ?? '';
    $searchQuery = trim($_POST['search_query'] ?? '');

    if ($selectedTable !== '') {
        // Get columns
        $columnsResult = $conn->query("SHOW COLUMNS FROM `$selectedTable`");
        $columns = [];
        while ($col = $columnsResult->fetch_assoc()) {
            $columns[] = $col['Field'];
        }

        if ($action === 'insert') {
            $insertValues = [];
            foreach ($columns as $col) {
                $value = $conn->real_escape_string($_POST["new_$col"] ?? '');
                $insertValues[] = "'$value'";
            }
            $insertCols = implode(',', array_map(fn($c) => "`$c`", $columns));
            $insertVals = implode(',', $insertValues);
            $conn->query("INSERT INTO `$selectedTable` ($insertCols) VALUES ($insertVals)");
            $feedback = "✅ New row inserted.";
        }

        if ($action === 'update' && isset($_POST['row_data'])) {
            foreach ($_POST['row_data'] as $rowIndex => $rowValues) {
                $setParts = [];
                foreach ($columns as $col) {
                    $val = $conn->real_escape_string($rowValues[$col]);
                    $setParts[] = "`$col` = '$val'";
                }
                // Assuming the first column is the PRIMARY KEY
                $pkCol = $columns[0];
                $pkVal = $conn->real_escape_string($rowValues[$pkCol]);
                $conn->query("UPDATE `$selectedTable` SET " . implode(',', $setParts) . " WHERE `$pkCol` = '$pkVal'");
            }
            $feedback = "✅ Rows updated.";
        }

        if ($action === 'delete' && isset($_POST['delete_keys'])) {
            $pkCol = $columns[0];
            foreach ($_POST['delete_keys'] as $pkVal) {
                $safeVal = $conn->real_escape_string($pkVal);
                $conn->query("DELETE FROM `$selectedTable` WHERE `$pkCol` = '$safeVal'");
            }
            $feedback = "🗑️ Rows deleted.";
        }

        // Fetch data again
        $likeClause = "";
        if ($searchQuery !== '') {
            $likeParts = array_map(function ($col) use ($conn, $searchQuery) {
                $safeQuery = $conn->real_escape_string($searchQuery);
                return "`$col` LIKE '%$safeQuery%'";
            }, $columns);
            $likeClause = "WHERE " . implode(" OR ", $likeParts);
        }

        $query = "SELECT * FROM `$selectedTable` $likeClause";
        $result = $conn->query($query);

        // Render table
        if ($result && $result->num_rows > 0) {
            $tableData = "<form method='post'><input type='hidden' name='table_name' value='$selectedTable'>";
            $tableData .= "<thead><tr>";
            $tableData .= "<th>Select</th>";
            foreach ($columns as $col) {
                $tableData .= "<th>" . htmlspecialchars($col) . "</th>";
            }
            $tableData .= "</tr></thead><tbody>";

            while ($row = $result->fetch_assoc()) {
                $tableData .= "<tr>";
                $pk = $row[$columns[0]];
                $tableData .= "<td><input type='checkbox' name='delete_keys[]' value='" . htmlspecialchars($pk) . "'></td>";
                foreach ($columns as $col) {
                    $value = htmlspecialchars($row[$col]);
                    $tableData .= "<td><input name='row_data[$pk][$col]' value='$value'></td>";
                }
                $tableData .= "</tr>";
            }

            // Insert Row
            $tableData .= "<tr><td>➕</td>";
            foreach ($columns as $col) {
                $tableData .= "<td><input name='new_$col' placeholder='$col'></td>";
            }
            $tableData .= "</tr>";

            $tableData .= "</tbody><tfoot><tr><td colspan='" . (count($columns) + 1) . "'>";
            $tableData .= '
            <div class="button-container">
                <button class="Button-UI" type="submit" name="action" value="update">
                    <img class="Button-Image" src="/images/update.png" />
                    <span>Update</span>
                </button>
                <button class="Button-UI" type="submit" name="action" value="insert">
                    <img class="Button-Image" src="/images/add.png" />
                    <span>Insert</span>
                </button>
                <button class="Button-UI" type="submit" name="action" value="delete">
                    <img class="Button-Image-Del" src="/images/delete.png" />
                    <span>Delete</span>
                </button>
            </div>';
            $tableData .= "</td></tr></tfoot></form>";
        } else {
            $tableData = "<thead><tr><th>No results found.</th></tr></thead>";
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
<header class="hero-header">
    <div class="wrapper">
        <img src="/images/crunchy-banner.png"/>
        <h2>サクサクの瞬間</h2>
    </div>
</header>
<div class="main">
    <?php if (!empty($feedback)): ?>
        <div class="popup-message"><?= $feedback ?></div>
    <?php endif; ?>
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