
<?php

require_once __DIR__ . "/../infra/adm.php";
require_once __DIR__ . "/../infra/connect.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: trens.php");
    exit;
}

$id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    header("Location: trens.php");
    exit;
}

$stmt = mysqli_prepare(
    $conn,
    "DELETE FROM trens WHERE id = ?"
);

mysqli_stmt_bind_param($stmt, "i", $id);

try {

    mysqli_stmt_execute($stmt);

    if (mysqli_stmt_affected_rows($stmt) === 0) {
        mysqli_stmt_close($stmt);
        header("Location: trens.php?erro=nao_encontrado");
        exit;
    }

    mysqli_stmt_close($stmt);

    header("Location: trens.php?sucesso=excluido");
    exit;

} catch (mysqli_sql_exception $e) {

    mysqli_stmt_close($stmt);

    error_log($e->getMessage());

    header("Location: trens.php?erro=dependencias");
    exit;
}