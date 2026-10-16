<?php
session_start();

// ---- Controle de acesso: só admin edita sensores ----
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

// ---- ID do sensor ----
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT)
   ?: filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    http_response_code(400);
    exit('Sensor inválido.');
}

$tiposPermitidos  = ['temperatura', 'velocidade', 'presenca', 'vibracao', 'posicao'];
$statusPermitidos = ['ativo', 'inativo', 'manutencao'];

// ---- Salvar (POST) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        http_response_code(403);
        exit('Requisição inválida.');
    }

    $nome        = trim($_POST['nome'] ?? '');
    $tipo        = $_POST['tipo'] ?? '';
    $localizacao = trim($_POST['localizacao'] ?? '');
    $status      = $_POST['status'] ?? '';

    if ($nome === '' || mb_strlen($nome) > 100)               $erros[] = 'Informe o nome (até 100 caracteres).';
    if (!in_array($tipo, $tiposPermitidos, true))             $erros[] = 'Tipo de sensor inválido.';
    if ($localizacao === '' || mb_strlen($localizacao) > 150) $erros[] = 'Informe a localização (até 150 caracteres).';
    if (!in_array($status, $statusPermitidos, true))          $erros[] = 'Status inválido.';

    if (!$erros) {
        try {
            $stmt = $pdo->prepare(
                'UPDATE sensores
                    SET nome = :nome, tipo = :tipo,
                        localizacao = :localizacao, status = :status
                  WHERE id = :id'
            );
            $stmt->execute([
                ':nome'        => $nome,
                ':tipo'        => $tipo,
                ':localizacao' => $localizacao,
                ':status'      => $status,
                ':id'          => $id,
            ]);
            $sucesso = true;
        } catch (PDOException $e) {
            error_log('Erro ao editar sensor: ' . $e->getMessage());
            $erros[] = 'Não foi possível salvar. Tente novamente.';
        }
    }
}

// ---- Carregar sensor ----
try {
    $stmt = $pdo->prepare('SELECT id, nome, tipo, localizacao, status FROM sensores WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $sensor = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Erro ao buscar sensor: ' . $e->getMessage());
    http_response_code(500);
    exit('Erro interno.');
}

if (!$sensor) {
    http_response_code(404);
    exit('Sensor não encontrado.');
}

// Se houve erro de validação, mantém o que o usuário digitou
if ($erros && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $sensor['nome']        = $nome;
    $sensor['tipo']        = $tipo;
    $sensor['localizacao'] = $localizacao;
    $sensor['status']      = $status;
}

function e(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }

$rotulos = [
    'temperatura' => 'Temperatura', 'velocidade' => 'Velocidade', 'presenca' => 'Presença',
    'vibracao' => 'Vibração', 'posicao' => 'Posição',
    'ativo' => 'Ativo', 'inativo' => 'Inativo', 'manutencao' => 'Manutenção',
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Editar Sensor - Ferrorama</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5" style="max-width: 600px;">
    <h1 class="h3 mb-4">Editar Sensor #<?= (int)$sensor['id'] ?></h1>

    <?php if ($sucesso): ?>
        <div class="alert alert-success">Sensor atualizado com sucesso!</div>
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
        <input type="hidden" name="id" value="<?= (int)$sensor['id'] ?>">

        <div class="mb-3">
            <label for="nome" class="form-label">Nome</label>
            <input type="text" id="nome" name="nome" class="form-control" maxlength="100"
                   value="<?= e($sensor['nome']) ?>" required>
        </div>

        <div class="mb-3">
            <label for="tipo" class="form-label">Tipo</label>
            <select id="tipo" name="tipo" class="form-select" required>
                <?php foreach ($tiposPermitidos as $t): ?>
                    <option value="<?= e($t) ?>" <?= $sensor['tipo'] === $t ? 'selected' : '' ?>>
                        <?= e($rotulos[$t]) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label for="localizacao" class="form-label">Localização</label>
            <input type="text" id="localizacao" name="localizacao" class="form-control" maxlength="150"
                   value="<?= e($sensor['localizacao']) ?>" required>
        </div>

        <div class="mb-4">
            <label for="status" class="form-label">Status</label>
            <select id="status" name="status" class="form-select" required>
                <?php foreach ($statusPermitidos as $s): ?>
                    <option value="<?= e($s) ?>" <?= $sensor['status'] === $s ? 'selected' : '' ?>>
                        <?= e($rotulos[$s]) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">Salvar alterações</button>
            <a href="listar_sensores.php" class="btn btn-outline-secondary">Voltar</a>
        </div>
    </form>
</div>
</body>
</html>