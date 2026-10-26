<?php
require_once __DIR__ . "/../infra/adm.php";
require_once __DIR__ . "/../infra/connect.php";

// Exclusão só via POST (nunca por link/GET)
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: sensor.php");
    exit;
}

$id = filter_var($_POST["id"] ?? null, FILTER_VALIDATE_INT);
if (!$id) {
    header("Location: sensor.php?erro=id_invalido");
    exit;
}

try {
    $stmt = $conn->prepare("DELETE FROM sensores WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $linhas = $stmt->affected_rows;
    $stmt->close();

    if ($linhas === 0) {
        header("Location: sensor.php?erro=nao_encontrado");
    } else {
        header("Location: sensor.php?sucesso=excluido");
    }
    exit;
} catch (mysqli_sql_exception $e) {
    error_log($e->getMessage());

    // 1451 = sensor referenciado por outra tabela (chave estrangeira)
    if ($e->getCode() === 1451) {
        header("Location: sensor.php?erro=em_uso");
    } else {
        header("Location: sensor.php?erro=exclusao");
    }
    exit;
}