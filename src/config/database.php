<?php
$servername = "127.0.0.1";
$username = "root";
$password = "";
$dbname = "synergyspace";


try{
    #Database connection
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);

    #Enable to handle PDO error exceptions
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    # Return database results as associative arrays
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e){
    echo "Connection failed: " . $e->getMessage();
}
$conn = null;
?>