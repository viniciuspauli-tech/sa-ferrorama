<?php
session_start();

// ---- Controle de acesso: só admin edita rotas ----
if (!isset($_SESSION['usuario_id']) || ($_SESSION['cargo'] ?? '') !== 'adm') {
    header('Location: login.php');
    exit;
}

require_once 'conexao.php'; // deve definir $pdo (PDO com ERRMODE_EXCEPTION)

// ---- Token CSRF ----
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

$erros = [];
$sucesso = false;

// ---- ID da rota ----
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT)
   ?: filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    http_response_code(400);
    exit('Rota inválida.');
}

$statusPermitidos = ['ativa', 'inativa', 'manutencao'];

// ---- Salvar (POST) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        http_response_code(403);
        exit('Requisição inválida.');
    }

    $origem      = trim($_POST['origem'] ?? '');
    $destino     = trim($_POST['destino'] ?? '');
    $distancia   = filter_var($_POST['distancia_km'] ?? '', FILTER_VALIDATE_FLOAT);
    $status      = $_POST['status'] ?? '';

    if ($origem === '' || mb_strlen($origem) > 100)   $erros[] = 'Informe a origem (até 100 caracteres).';
    if ($destino === '' || mb_strlen($destino) > 100) $erros[] = 'Informe o destino (até 100 caracteres).';
    if (mb_strtolower($origem) === mb_strtolower($destino) && $origem !== '') $erros[] = 'Origem e destino não podem ser iguais.';
    if ($distancia === false || $distancia <= 0)      $erros[] = 'Informe uma distância válida (maior que zero).';
    if (!in_array($status, $statusPermitidos, true))  $erros[] = 'Status inválido.';

    if (!$erros) {
        try {
            $stmt = $pdo->prepare(
                'UPDATE rotas
                    SET origem = :origem, destino = :destino,
                        distancia_km = :distancia, status = :status
                  WHERE id = :id'
            );
            $stmt->execute([
                ':origem'    => $origem,
                ':destino'   => $destino,
                ':distancia' => $distancia,
                ':status'    => $status,
                ':id'        => $id,
            ]);
            $sucesso = true;
        } catch (PDOException $e) {
            error_log('Erro ao editar rota: ' . $e->getMessage());
            $erros[] = 'Não foi possível salvar. Tente novamente.';
        }
    }
}

// ---- Carregar rota (valores do formulário) ----
try {
    $stmt = $pdo->prepare('SELECT id, origem, destino, distancia_km, status FROM rotas WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $rota = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Erro ao buscar rota: ' . $e->getMessage());
    http_response_code(500);
    exit('Erro interno.');
}

if (!$rota) {
    http_response_code(404);
    exit('Rota não encontrada.');
}

// Se houve erro de validação, mantém o que o usuário digitou
if ($erros && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $rota['origem']       = $origem;
    $rota['destino']      = $destino;
    $rota['distancia_km'] = $_POST['distancia_km'] ?? '';
    $rota['status']       = $status;
}

function e(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Editar Rota - Ferrorama</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5" style="max-width: 600px;">
    <h1 class="h3 mb-4">Editar Rota #<?= (int)$rota['id'] ?></h1>

    <?php if ($sucesso): ?>
        <div class="alert alert-success">Rota atualizada com sucesso!</div>
    <?php endif; ?>

    <?php if ($erros): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($erros as $erro): ?>
                    <li><?= e($erro) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" class="card card-body shadow-sm">
        <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
        <input type="hidden" name="id" value="<?= (int)$rota['id'] ?>">

        <div class="mb-3">
            <label for="origem" class="form-label">Origem</label>
            <input type="text" id="origem" name="origem" class="form-control" maxlength="100"
                   value="<?= e($rota['origem']) ?>" required>
        </div>

        <div class="mb-3">
            <label for="destino" class="form-label">Destino</label>
            <input type="text" id="destino" name="destino" class="form-control" maxlength="100"
                   value="<?= e($rota['destino']) ?>" required>
        </div>

        <div class="mb-3">
            <label for="distancia_km" class="form-label">Distância (km)</label>
            <input type="number" id="distancia_km" name="distancia_km" class="form-control"
                   step="0.01" min="0.01" value="<?= e((string)$rota['distancia_km']) ?>" required>
        </div>

        <div class="mb-4">
            <label for="status" class="form-label">Status</label>
            <select id="status" name="status" class="form-select" required>
                <?php foreach ($statusPermitidos as $s): ?>
                    <option value="<?= e($s) ?>" <?= $rota['status'] === $s ? 'selected' : '' ?>>
                        <?= e(ucfirst($s === 'manutencao' ? 'manutenção' : $s)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">Salvar alterações</button>
            <a href="listar_rotas.php" class="btn btn-outline-secondary">Voltar</a>
        </div>
    </form>
</div>
</body>
</html>