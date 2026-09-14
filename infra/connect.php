<?php
$host = "localhost";
$user = "root";
$senha = "root";
$dbname = "ferrorama";
$porta = 3349; 

$conn = mysqli_connect($host, $user, $senha, $dbname, $porta);

if (!$conn) {
    die("Falha na conexão com o banco de dados: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");
?>
