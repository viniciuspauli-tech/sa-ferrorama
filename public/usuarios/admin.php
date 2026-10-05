
<?php
// adm.php — painel do administrador (Ferrorama)
// Baseado no gabarito: icrcode-senai/gabarito-login-sessao
session_start();

// 1) Controle de acesso: só entra quem está logado E é adm
if (!isset($_SESSION['usuario']) || ($_SESSION['tipo'] ?? '') !== 'adm') {
    header('Location: home.php');
    exit();
}

// 2) Conexão (mesmo connect.php do gabarito, que define $conn = mysqli)
require_once 'connect.php';

// 3) Consulta usando prepared statement (evita SQL Injection)
$stmt = $conn->prepare("SELECT id, email, cargo FROM usuarios ORDER BY email");
$stmt->execute();
$usuarios = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$totalAdm = count(array_filter($usuarios, fn($u) => $u['cargo'] === 'adm'));

// Função curta para escapar saída (evita XSS)
function e(string $v): string {
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel do Administrador – Ferrorama</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-dark">
    <div class="container">
        <span class="navbar-brand mb-0 h1">Ferrorama · Administração</span>
        <div class="d-flex align-items-center gap-3">
            <span class="text-white-50 small"><?= e($_SESSION['usuario']) ?></span>
            <a href="logout.php" class="btn btn-outline-light btn-sm">Sair</a>
        </div>
    </div>
</nav>

<main class="container py-4">
    <h1 class="h3 mb-1">Bem-vindo, <?= e($_SESSION['usuario']) ?></h1>
    <p class="text-muted">Gerencie os usuários do sistema de monitoramento de trens.</p>

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="border rounded bg-white p-3">
                <div class="text-muted small">Usuários cadastrados</div>
                <div class="fs-3 fw-semibold"><?= count($usuarios) ?></div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="border rounded bg-white p-3">
                <div class="text-muted small">Administradores</div>
                <div class="fs-3 fw-semibold"><?= $totalAdm ?></div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-2">
        <h2 class="h5 mb-0">Usuários</h2>
        <a href="cadastrar_usuario.php" class="btn btn-primary btn-sm">Cadastrar usuário</a>
    </div>

    <div class="table-responsive bg-white border rounded">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Email</th>
                    <th>Cargo</th>
                    <th class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
            <?php if (count($usuarios) > 0): ?>
                <?php foreach ($usuarios as $u): ?>
                    <tr>
                        <td><?= e($u['email']) ?></td>
                        <td>
                            <span class="badge <?= $u['cargo'] === 'adm' ? 'bg-danger' : 'bg-secondary' ?>">
                                <?= e($u['cargo']) ?>
                            </span>
                        </td>
                        <td class="text-end">
                            <?php if ($u['email'] !== $_SESSION['usuario']): ?>
                                <a href="excluir_usuario.php?id=<?= (int)$u['id'] ?>"
                                   class="btn btn-outline-danger btn-sm"
                                   onclick="return confirm('Excluir este usuário?')">Excluir</a>
                            <?php else: ?>
                                <span class="text-muted small">Você</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="3" class="text-center text-muted py-4">Nenhum usuário encontrado.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

</body>
</html>