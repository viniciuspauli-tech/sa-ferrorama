<?php
/**
 * processar_exclusao.php
 * Recebe o POST do modal de confirmação e realiza a exclusão lógica do usuário.
 * Restrito a admin. Protegido contra CSRF e SQL Injection (prepared statements).
 */

require_once 'conexao.php';
require_once 'auth.php';

exigirPerfil(['admin']);

// Só aceita POST — evita exclusão via link direto (GET)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: usuarios.php?status=invalido');
    exit;
}

// Validação do token CSRF
if (!validarTokenCsrf($_POST['csrf_token'] ?? null)) {
    header('Location: usuarios.php?status=invalido');
    exit;
}

// Validação/sanitização do ID recebido
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if ($id === false || $id === null || $id <= 0) {
    header('Location: usuarios.php?status=invalido');
    exit;
}

// Proteção extra: impede que o admin exclua a própria conta por esta tela
if ($id === (int)$_SESSION['usuario_id']) {
    header('Location: usuarios.php?status=invalido');
    exit;
}

try {
    // Exclusão lógica (soft delete): preserva integridade referencial e histórico.
    // Troque por DELETE FROM usuarios WHERE id = :id se o requisito exigir exclusão física.
    $stmt = $pdo->prepare('UPDATE usuarios SET ativo = 0 WHERE id = :id');
    $stmt->execute(['id' => $id]);

    if ($stmt->rowCount() > 0) {
        header('Location: usuarios.php?status=sucesso');
    } else {
        header('Location: usuarios.php?status=erro');
    }
    exit;

} catch (PDOException $e) {
    error_log('Erro ao excluir usuário: ' . $e->getMessage());
    header('Location: usuarios.php?status=erro');
    exit;
}