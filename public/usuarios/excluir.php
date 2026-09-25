<?php

require_once "../../infra/admin.php";
require_once "../../infra/connect.php";

$id = filter_input(
    INPUT_GET,
    "id",
    FILTER_VALIDATE_INT
);

if (!$id) {

    die("ID de usuário inválido.");

}

/*
 * Impede o administrador de excluir
 * a própria conta por acidente.
 */

if ($id == $_SESSION["usuario_id"]) {

    die("Você não pode excluir sua própria conta.");
}

$sql = "DELETE FROM usuarios WHERE id = ?";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {

    die("Não foi possível preparar a exclusão.");

}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id
);

if (mysqli_stmt_execute($stmt)) {

    header("Location: index.php");
    exit;

} else {

    die("Não foi possível excluir o usuário.");

}

mysqli_stmt_close($stmt);