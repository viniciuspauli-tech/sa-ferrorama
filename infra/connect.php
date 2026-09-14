<?php
$host = "localhost";
$user = "root";
$senha = "";
$dbname = "ferrorama";
$porta = 3306; 

$conn = mysqli_connect($host, $user, $senha, $dbname, $porta);

if (!$conn) {
    die("Falha na conexão com o banco de dados: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");
?>
