<?php
$host = "localhost";
$user = "root";
$senha = "";
$dbname = "ferrorama";
$porta = 3306; 

   $conn = mysqli_connect('localhost', 'root', '', 'ferrorama', 3306);

if (!$conn) {
    die("Não foi possível conectar ao banco de dados.");
}

mysqli_set_charset($conn, "utf8mb4");
?>
