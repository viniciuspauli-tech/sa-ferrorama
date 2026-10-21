
<?php

require_once __DIR__ . "/../infra/adm.php";
require_once __DIR__ . "/../infra/connect.php";

// Aceita somente requisições POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: trem/listagemtrem.php");
    exit;
}

// Valida o ID recebido
$id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    header("Location: trem/listagemtrem.php?erro=id_invalido");
    exit;
}

try {

    // Prepara a exclusão com segurança
    $stmt = $conn->prepare(
        "DELETE FROM trens WHERE id = ?"
    );

    if (!$stmt) {
        throw new RuntimeException(
            "Não foi possível preparar a exclusão."
        );
    }

    $stmt->bind_param("i", $id);
    $stmt->execute();

    $excluido = $stmt->affected_rows > 0;

    $stmt->close();

    if (!$excluido) {
        header(
            "Location: trem/listagemtrem.php?erro=nao_encontrado"
        );
        exit;
    }

    header(
        "Location: trem/listagemtrem.php?sucesso=excluido"
    );
    exit;

} catch (mysqli_sql_exception $e) {

    if (isset($stmt) && $stmt instanceof mysqli_stmt) {
        $stmt->close();
    }

    error_log($e->getMessage());

    header(
        "Location: trem/listagemtrem.php?erro=dependencias"
    );
    exit;

} catch (RuntimeException $e) {

    error_log($e->getMessage());

    http_response_code(500);
    exit("Não foi possível excluir o trem.");
}
