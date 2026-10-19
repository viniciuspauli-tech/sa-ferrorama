<?php

require_once "../../infra/adm.php";
require_once "../../infra/connect.php";

header("Content-Type: application/json; charset=utf-8");

function responder(bool $sucesso, string $mensagem, int $status = 200): void
{
    http_response_code($status);
    echo json_encode([
        "sucesso"  => $sucesso,
        "mensagem" => $mensagem
    ]);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    responder(false, "Método não permitido.", 405);
}

$id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);

if (!$id) {
    responder(false, "ID de usuário inválido.", 400);
}

if ($id == $_SESSION["usuario_id"]) {
    responder(false, "Você não pode excluir a própria conta.", 403);
}

$sql  = "DELETE FROM usuarios WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    responder(false, "Não foi possível preparar a exclusão.", 500);
}

mysqli_stmt_bind_param($stmt, "i", $id);

if (mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);
    responder(true, "Usuário excluído com sucesso.");
}

mysqli_stmt_close($stmt);
responder(false, "Não foi possível excluir o usuário.", 500);