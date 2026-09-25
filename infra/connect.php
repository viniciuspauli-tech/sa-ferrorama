<?php
$host = "localhost";
$user = "root";
$senha = "root";
$dbname = "ferrorama";
$porta = 3306; 

$conn = mysqli_connect($host, $user, $senha, $dbname, $porta);

if (!$conn) {
    die("Não foi possível conectar ao banco de dados.");
}

mysqli_set_charset($conn, "utf8mb4");
?>
