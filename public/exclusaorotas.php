<?php
// public/rotas/excluirrota.php
// Exclusão de rota — SOMENTE administrador, SOMENTE via POST com token CSRF.

require_once __DIR__ . '/../../infra/auth.php'; // exige login
require_once __DIR__ . '/../../infra/adm.php';  // exige perfil adm
require_once __DIR__ . '/../../infra/conexao.php'; // AJUSTE: arquivo que define $pdo (PDO com ERRMODE_EXCEPTION)

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Acesso direto pela URL (GET) não exclui nada
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: rotas.php');
    exit;
}

// Token CSRF
if (empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
    http_response_code(403);
    exit('Requisição inválida.');
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    http_response_code(400);
    exit('Rota inválida.');
}

try {

    $stmt = $pdo->prepare('SELECT id FROM rotas WHERE id = :id');
    $stmt->execute([':id' => $id]);

    if (!$stmt->fetch()) {
        header('Location: rotas.php?msg=nao_encontrada');
        exit;
    }

    $stmt = $pdo->prepare('DELETE FROM rotas WHERE id = :id');
    $stmt->execute([':id' => $id]);

    header('Location: rotas.php?msg=excluida');
    exit;
} catch (PDOException $e) {
    error_log('Erro ao excluir rota: ' . $e->getMessage());
    header('Location: rotas.php?msg=erro');
    exit;
}