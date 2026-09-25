<?php
$host = "localhost";
$user = "root";
$senha = "ferrorama123";
$dbname = "ferrorama";
$porta = 3306; 

$conn = mysqli_connect($host, $user, $senha, $dbname, $porta);

if (!$conn) {
    die("Não foi possível conectar ao banco de dados.");
}

mysqli_set_charset($conn, "utf8mb4");
?>
