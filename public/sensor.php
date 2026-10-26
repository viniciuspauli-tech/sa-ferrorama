<?php
require_once __DIR__ . "/../infra/adm.php";
require_once __DIR__ . "/../infra/connect.php";

/* Exclusão: o formulário da tabela envia POST para esta mesma página */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["excluir_id"])) {
    $idExcluir = filter_var($_POST["excluir_id"], FILTER_VALIDATE_INT);

    if (!$idExcluir) {
        header("Location: sensor.php?erro=id_invalido");
        exit;
    }

    try {
        $stmt = $conn->prepare("DELETE FROM sensores WHERE id = ?");
        $stmt->bind_param("i", $idExcluir);
        $stmt->execute();
        $linhas = $stmt->affected_rows;
        $stmt->close();

        header("Location: sensor.php?" . ($linhas === 0 ? "erro=nao_encontrado" : "sucesso=excluido"));
        exit;
    } catch (mysqli_sql_exception $e) {
        error_log($e->getMessage());
        // 1451 = sensor referenciado por outra tabela (chave estrangeira)
        header("Location: sensor.php?erro=" . ($e->getCode() === 1451 ? "em_uso" : "exclusao"));
        exit;
    }
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function escapar($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, "UTF-8");
}

/* Mensagens vindas do cadastro, edição e exclusão */
$mensagens = [
    "sucesso" => [
        "cadastrado" => "Sensor cadastrado com sucesso.",
        "editado"    => "Sensor atualizado com sucesso.",
        "excluido"   => "Sensor excluído com sucesso.",
    ],
    "erro" => [
        "nao_encontrado" => "Sensor não encontrado.",
        "id_invalido"    => "ID inválido.",
        "em_uso"         => "Não é possível excluir: o sensor está vinculado a outros registros.",
        "exclusao"       => "Erro ao excluir o sensor.",
    ],
];
$msgSucesso = $mensagens["sucesso"][$_GET["sucesso"] ?? ""] ?? "";
$msgErro    = $mensagens["erro"][$_GET["erro"] ?? ""] ?? "";

/* Filtros */
$busca = trim($_GET["busca"] ?? "");
$tipo  = trim($_GET["tipo"] ?? "");

$where  = [];
$params = [];
$types  = "";

if ($busca !== "") {
    $where[] = "(nome LIKE ? OR localizacao LIKE ? OR tipo_dado LIKE ?)";
    $like = "%" . $busca . "%";
    array_push($params, $like, $like, $like);
    $types .= "sss";
}
if ($tipo !== "") {
    $where[] = "tipo_dado = ?";
    $params[] = $tipo;
    $types .= "s";
}

$sql = "SELECT id, nome, localizacao, tipo_dado FROM sensores";
if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY id DESC";

$sensores = [];
$tiposDisponiveis = [];

try {
    $stmt = $conn->prepare($sql);
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $sensores = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $res = $conn->query("SELECT DISTINCT tipo_dado FROM sensores ORDER BY tipo_dado");
    while ($linha = $res->fetch_assoc()) {
        $tiposDisponiveis[] = $linha["tipo_dado"];
    }
} catch (mysqli_sql_exception $e) {
    error_log($e->getMessage());
    $msgErro = "Não foi possível carregar os sensores.";
}

/* Resumo (calculado sobre o resultado da consulta) */
$totalSensores = count($sensores);
$totalTipos    = count(array_unique(array_column($sensores, "tipo_dado")));
$totalLocais   = count(array_unique(array_column($sensores, "localizacao")));

$nomeUsuario = $_SESSION["nome"] ?? "Administrador";
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sensores - Ferrorama</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background:#f8fafc; font-family: "Inter", system-ui, sans-serif; color:#0f172a; }
        .topbar { background:#0f172a; border-bottom:1px solid #1e293b; }
        .hero { background:linear-gradient(120deg,#0f172a 40%,#1e1b4b); color:#fff; padding:2.2rem 0; }
        .hero .eyebrow { font-size:.7rem; letter-spacing:.12em; font-weight:700; color:#818cf8; text-transform:uppercase; }
        .hero h1 span { color:#a78bfa; }
        .hero p { color:#cbd5e1; font-size:.85rem; margin:0; }
        .btn-hero { background:transparent; border:1px solid #475569; color:#fff; font-size:.75rem; font-weight:600; }
        .btn-hero:hover { background:#1e293b; color:#fff; }
        .btn-main { background:#4f46e5; border:0; color:#fff; font-size:.75rem; font-weight:600; }
        .btn-main:hover { background:#4338ca; color:#fff; }
        .kpi, .painel { background:#fff; border:1px solid #e2e8f0; border-radius:14px; }
        .kpi { padding:1.1rem 1.2rem; display:flex; gap:.9rem; align-items:center; }
        .kpi .icone { width:38px; height:38px; border-radius:10px; display:grid; place-items:center; font-size:1.1rem; }
        .kpi small { color:#64748b; font-size:.7rem; display:block; }
        .kpi strong { font-size:1.4rem; }
        .badge-soft { background:#eef2ff; color:#4338ca; font-size:.7rem; font-weight:600; padding:.35rem .6rem; border-radius:8px; }
        .tabela th { font-size:.68rem; color:#475569; text-transform:uppercase; letter-spacing:.04em; background:#f8fafc; }
        .tabela td { font-size:.78rem; vertical-align:middle; }
        .tag-tipo { background:#ecfdf5; color:#047857; font-size:.7rem; font-weight:600; padding:.25rem .6rem; border-radius:999px; }
        .btn-acao { font-size:.7rem; font-weight:600; padding:.2rem .6rem; }
    </style>
</head>
<body>

<div class="topbar py-3">
    <div class="container d-flex justify-content-between align-items-center">
        <div class="text-white">
            <div class="fw-bold">Ferrorama</div>
            <div style="font-size:.7rem;color:#93c5fd;">Sistema ferroviário</div>
        </div>
        <div class="text-white-50" style="font-size:.72rem;">Área do usuário <?= escapar($nomeUsuario) ?></div>
    </div>
</div>

<section class="hero">
    <div class="container d-flex flex-wrap justify-content-between align-items-end gap-3">
        <div>
            <div class="eyebrow">— Gestão ferroviária</div>
            <h1 class="fw-bold mt-1">Gerenciamento de <span>sensores.</span></h1>
            <p>Consulte os sensores instalados no trilho, acompanhe suas localizações e gerencie as informações.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="sistema.php" class="btn btn-hero">← Voltar ao sistema</a>
       
        </div>
    </div>
</section>

<main class="container py-4">

    <?php if ($msgSucesso !== ""): ?>
        <div class="alert alert-success"><?= escapar($msgSucesso) ?></div>
    <?php endif; ?>
    <?php if ($msgErro !== ""): ?>
        <div class="alert alert-danger"><?= escapar($msgErro) ?></div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-end mb-3">
        <div>
            <h2 class="h6 fw-bold mb-0">Visão geral dos sensores</h2>
            <small class="text-secondary">Resumo dos sensores que correspondem à sua consulta.</small>
        </div>
        <span class="badge-soft"><?= $totalSensores ?> sensor<?= $totalSensores === 1 ? "" : "es" ?> encontrado<?= $totalSensores === 1 ? "" : "s" ?></span>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="kpi">
                <div class="icone" style="background:#eef2ff;">📡</div>
                <div><small>Total de sensores</small><strong><?= $totalSensores ?></strong></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="kpi">
                <div class="icone" style="background:#ecfdf5;color:#047857;">⚙</div>
                <div><small>Tipos de dado</small><strong><?= $totalTipos ?></strong></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="kpi">
                <div class="icone" style="background:#fffbeb;color:#b45309;">📍</div>
                <div><small>Localizações</small><strong><?= $totalLocais ?></strong></div>
            </div>
        </div>
    </div>

    <form method="GET" class="painel p-3 mb-4">
        <div class="fw-bold mb-2" style="font-size:.8rem;">Buscar e filtrar sensores</div>
        <div class="row g-3 align-items-end">
            <div class="col-md-6">
                <label class="form-label small fw-semibold mb-1" for="busca">Pesquisar</label>
                <input type="text" id="busca" name="busca" class="form-control"
                       value="<?= escapar($busca) ?>" placeholder="Nome, localização ou tipo de dado...">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1" for="tipo">Tipo de dado</label>
                <select id="tipo" name="tipo" class="form-select">
                    <option value="">Todos os tipos</option>
                    <?php foreach ($tiposDisponiveis as $t): ?>
                        <option value="<?= escapar($t) ?>" <?= $t === $tipo ? "selected" : "" ?>><?= escapar($t) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-dark">Buscar</button>
                
            </div>
        </div>
    </form>

    <div class="painel">
        <div class="d-flex justify-content-between align-items-center p-3">
            <div>
                <div class="fw-bold" style="font-size:.85rem;">Sensores cadastrados</div>
                <small class="text-secondary">Informações dos sensores instalados ao longo do trilho.</small>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge-soft"><?= $totalSensores ?> registro<?= $totalSensores === 1 ? "" : "s" ?></span>
                <a href="cadastro_item.php" class="btn btn-sm btn-success">+ Cadastrar sensor</a>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table tabela mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">ID</th>
                        <th>Nome</th>
                        <th>Localização</th>
                        <th>Tipo de dado</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!$sensores): ?>
                    <tr><td colspan="5" class="text-center text-secondary py-4">Nenhum sensor encontrado.</td></tr>
                <?php endif; ?>

                <?php foreach ($sensores as $s): ?>
                    <tr>
                        <td class="ps-3 text-secondary">#<?= (int)$s["id"] ?></td>
                        <td class="fw-bold"><?= escapar($s["nome"]) ?></td>
                        <td><?= escapar($s["localizacao"]) ?></td>
                        <td><span class="tag-tipo"><?= escapar($s["tipo_dado"]) ?></span></td>
                        <td>
                            <a href="cadastro_item.php?id=<?= (int)$s["id"] ?>" class="btn btn-acao btn-primary">Editar</a>
                            <form method="POST" action="sensor.php" class="d-inline"
                                  onsubmit="return confirm('Excluir este sensor? Essa ação não pode ser desfeita.');">
                                <input type="hidden" name="excluir_id" value="<?= (int)$s["id"] ?>">
                                <button type="submit" class="btn btn-acao btn-danger">Excluir</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <p class="text-center text-secondary mt-4" style="font-size:.7rem;">Ferrorama · Sistema de gerenciamento ferroviário</p>
</main>

</body>
</html>