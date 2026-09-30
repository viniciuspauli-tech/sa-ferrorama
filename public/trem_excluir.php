<?php

require_once "../../infra/admin.php";
require_once __DIR__ . '/../infra/conect.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: trens.php");
    exit;
}

$id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);

if (!$id) {
    header("Location: trens.php");
    exit;
}

$stmt = mysqli_prepare($conn, "DELETE FROM trens WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);

try {
    mysqli_stmt_execute($stmt);
} catch (mysqli_sql_exception $e) {
    // Trem com sensores que já têm dados registrados não pode ser excluído
    die("Não é possível excluir trens com sensores que possuem dados registrados.");
}

mysqli_stmt_close($stmt);

header("Location: trens.php");
exit;
