<?php
/**
 * usuarios.php
 * Lista os usuários cadastrados e permite excluir (exclusão lógica) — restrito a admin.
 */

require_once 'conexao.php';
require_once 'auth.php';

exigirPerfil(['admin']);

// Feedback vindo do redirect após ação (padrão PRG: Post/Redirect/Get)
$mensagem = '';
$tipoMensagem = $_GET['status'] ?? '';
if ($tipoMensagem === 'sucesso') {
    $mensagem = 'Usuário excluído com sucesso.';
} elseif ($tipoMensagem === 'erro') {
    $mensagem = 'Não foi possível excluir o usuário. Tente novamente.';
} elseif ($tipoMensagem === 'invalido') {
    $mensagem = 'Requisição inválida.';
}

// Busca apenas usuários ativos (exclusão lógica já aplicada)
$stmt = $pdo->prepare('SELECT id, nome, email, perfil FROM usuarios WHERE ativo = 1 ORDER BY nome ASC');
$stmt->execute();
$usuarios = $stmt->fetchAll();

$csrfToken = gerarTokenCsrf();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ferrorama - Gerenciar Usuários</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Gerenciar Usuários</h1>
        <a href="cadastro_usuario.php" class="btn btn-primary">+ Novo usuário</a>
    </div>

    <?php if ($mensagem): ?>
        <div class="alert <?= $tipoMensagem === 'sucesso' ? 'alert-success' : 'alert-danger' ?>" role="alert">
            <?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Nome</th>
                        <th>E-mail</th>
                        <th>Perfil</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($usuarios)): ?>
                    <tr>
                        <td colspan="4" class="text-center text-muted py-4">Nenhum usuário cadastrado.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($usuarios as $usuario): ?>
                        <tr>
                            <td><?= htmlspecialchars($usuario['nome'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($usuario['email'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><span class="badge bg-secondary"><?= htmlspecialchars($usuario['perfil'], ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td class="text-end">
                                <a href="editar_usuario.php?id=<?= (int)$usuario['id'] ?>" class="btn btn-sm btn-outline-secondary">Editar</a>

                                <?php if ((int)$usuario['id'] !== (int)$_SESSION['usuario_id']): ?>
                                    <!-- Um admin não pode excluir a própria conta por esta tela -->
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-danger"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalExcluir<?= (int)$usuario['id'] ?>">
                                        Excluir
                                    </button>

                                    <!-- Modal de confirmação -->
                                    <div class="modal fade" id="modalExcluir<?= (int)$usuario['id'] ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form action="processar_exclusao.php" method="POST">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Confirmar exclusão</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        Tem certeza que deseja excluir o usuário
                                                        <strong><?= htmlspecialchars($usuario['nome'], ENT_QUOTES, 'UTF-8') ?></strong>?
                                                        <br><small class="text-muted">Esta ação pode ser revertida apenas por um administrador diretamente no banco.</small>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <input type="hidden" name="id" value="<?= (int)$usuario['id'] ?>">
                                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                        <button type="submit" class="btn btn-danger">Excluir</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>