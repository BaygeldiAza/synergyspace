
<?php

$host = "127.0.0.1";
$dbname = "synergyspace";
$dbUsername = "root";
$dbPassword = "";

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $dbUsername,
        $dbPassword
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->setAttribute(
        PDO::ATTR_DEFAULT_FETCH_MODE,
        PDO::FETCH_ASSOC
    );

} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

?>
