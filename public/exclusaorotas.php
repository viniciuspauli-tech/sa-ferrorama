<?php
session_start();
require_once __DIR__ . '/../../infra/auth.php';
require_once __DIR__ . '/../../infra/adm.php';   // garante que só admin passa
require_once __DIR__ . '/../../infra/conexao.php'; // ajuste para o seu arquivo de conexão

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: rotas.php');
    exit;
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    $_SESSION['erro'] = 'ID de rota inválido.';
    header('Location: rotas.php');
    exit;
}

try {
    $stmt = $pdo->prepare('DELETE FROM rotas WHERE id = :id');
    $stmt->execute([':id' => $id]);

    $_SESSION[$stmt->rowCount() ? 'sucesso' : 'erro'] =
        $stmt->rowCount() ? 'Rota excluída com sucesso.' : 'Rota não encontrada.';
} catch (PDOException $e) {
    error_log($e->getMessage());
    $_SESSION['erro'] = 'Não foi possível excluir a rota.';
}

header('Location: rotas.php');
exit;