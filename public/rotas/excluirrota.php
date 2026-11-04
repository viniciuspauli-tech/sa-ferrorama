<?php
// public/rotas/excluirrota.php
// Exclusão de rota — SOMENTE administrador, SOMENTE via POST com token CSRF.

require_once __DIR__ . "/../../infra/adm.php";     // exige login + perfil administrador
require_once __DIR__ . "/../../infra/connect.php"; // define $conn (mysqli)

// Acesso direto pela URL (GET) não exclui nada
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: rotas.php");
    exit;
}

// Token CSRF
if (empty($_SESSION["csrf"]) || !hash_equals($_SESSION["csrf"], $_POST["csrf"] ?? "")) {
    http_response_code(403);
    exit("Requisição inválida.");
}

$id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);

if (!$id) {
    http_response_code(400);
    exit("Rota inválida.");
}

try {
    $stmt = $conn->prepare("DELETE FROM rotas WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    $excluiu = $stmt->affected_rows > 0;
    $stmt->close();

    header("Location: rotas.php?msg=" . ($excluiu ? "excluida" : "nao_encontrada"));
    exit;
} catch (mysqli_sql_exception $e) {
    error_log("Erro ao excluir rota: " . $e->getMessage());
    header("Location: rotas.php?msg=erro");
    exit;
}