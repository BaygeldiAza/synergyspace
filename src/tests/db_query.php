<?php

require_once __DIR__ . "/../config/database.php";

$stmt = $pdo->query("SHOW TABLES");

$tables = $stmt->fetchAll(PDO::FETCH_NUM);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Database Query Test</title>
</head>
<body>

    <h1>SynergySpace Database Tables</h1>

    <ul>
        <?php foreach ($tables as $table): ?>
            <li>
                <?= $table[0] ?>
            </li>
        <?php endforeach; ?>
    </ul>

</body>
</html>
