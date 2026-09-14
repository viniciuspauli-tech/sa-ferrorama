<?php

$host = "localhost";
$user = "root";
$senha = "ferrorama123";
$dbname = "ferrorama";
$porta = 3349;

$conn = mysqli_connect(
    $host,
    $user,
    $senha,
    $dbname,
    $porta
);

if (!$conn) {
    die("ERRO: " . mysqli_connect_error());
}

echo "CONEXÃO COM MYSQL FUNCIONANDO!";

echo "<br><br>";

echo "Bancos disponíveis:<br>";

$resultado = mysqli_query($conn, "SHOW DATABASES");

while ($banco = mysqli_fetch_assoc($resultado)) {
    echo $banco['Database'] . "<br>";
}

?>