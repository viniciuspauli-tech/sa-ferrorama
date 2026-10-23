
<?php

require_once __DIR__ . "/../../infra/auth.php";
require_once __DIR__ . "/../../infra/connect.php";

// Permissões
$ehAdministrador =
    ($_SESSION["usuario_perfil"] ?? "") === "administrador";

// Filtros
$busca = trim($_GET["busca"] ?? "");
$statusFiltro = $_GET["status"] ?? "";

$statusValidos = ["ativo", "inativo", "manutencao", "falha"];

if (
    $statusFiltro !== "" &&
    !in_array($statusFiltro, $statusValidos, true)
) {
    $statusFiltro = "";
}

// Consulta dos trens
$sql = "
    SELECT
        id,
        identificador,
        modelo,
        status,
        velocidade_atual,
        localizacao_atual,
        consumo_energia,
        atualizado_em
    FROM trens
    WHERE 1=1
";

$tipos = "";
$params = [];

if ($busca !== "") {
    $sql .= "
        AND (
            identificador LIKE ?
            OR modelo LIKE ?
            OR localizacao_atual LIKE ?
        )
    ";

    $like = "%" . $busca . "%";

    $tipos .= "sss";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($statusFiltro !== "") {
    $sql .= " AND status = ?";

    $tipos .= "s";
    $params[] = $statusFiltro;
}

$sql .= " ORDER BY identificador ASC";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    error_log($conn->error);
    http_response_code(500);
    exit("Não foi possível consultar os trens.");
}

if (!empty($params)) {
    $stmt->bind_param($tipos, ...$params);
}

if (!$stmt->execute()) {
    error_log($stmt->error);
    http_response_code(500);
    exit("Não foi possível carregar os trens.");
}

$resultado = $stmt->get_result();
$trens = $resultado->fetch_all(MYSQLI_ASSOC);

$stmt->close();

// Proteção de textos exibidos na página
function escapar($valor): string
{
    return htmlspecialchars(
        (string) $valor,
        ENT_QUOTES,
        "UTF-8"
    );
}

// Classes visuais dos status
function corStatusTrem($status): string
{
    switch ($status) {
        case "ativo":
            return "ativo";

        case "inativo":
            return "inativo";

        case "manutencao":
            return "manutencao";

        case "falha":
            return "falha";

        default:
            return "inativo";
    }
}

// Nome amigável do status
function nomeStatusTrem($status): string
{
    $nomes = [
        "ativo" => "Ativo",
        "inativo" => "Inativo",
        "manutencao" => "Manutenção",
        "falha" => "Falha"
    ];

    return $nomes[$status] ?? "Desconhecido";
}

// Contagem dos status dos trens encontrados
$totalTrens = count($trens);
$totalAtivos = 0;
$totalManutencao = 0;
$totalFalhas = 0;

foreach ($trens as $trem) {
    if ($trem["status"] === "ativo") {
        $totalAtivos++;
    }

    if ($trem["status"] === "manutencao") {
        $totalManutencao++;
    }

    if ($trem["status"] === "falha") {
        $totalFalhas++;
    }
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Trens - Ferrorama</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

    <style>
        :root {
            --navy: #0d1b2a;
            --navy-light: #172a42;
            --primary: #3324d9;
            --primary-light: #eeedff;
            --background: #f1f3f8;
            --surface: #ffffff;
            --text: #111c30;
            --muted: #728098;
            --border: #e0e6f0;
            --green: #16875a;
            --red: #d9364b;
            --orange: #b87500;
            --shadow: 0 4px 16px rgba(16, 32, 58, 0.045);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: "Roboto", sans-serif;
            background: var(--background);
            color: var(--text);
            font-size: 14px;
            -webkit-font-smoothing: antialiased;
        }

        a {
            text-decoration: none;
        }

        button,
        input,
        select {
            font: inherit;
        }

        /* Barra superior */

        .navbar {
            background: var(--navy);
            color: white;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .navbar-inner {
            width: min(1400px, calc(100% - 64px));
            min-height: 76px;
            margin: auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            color: white;
        }

        .brand-icon {
            width: 42px;
            height: 42px;
            border-radius: 13px;
            background: white;
            color: var(--primary);
            display: grid;
            place-items: center;
            font-size: 22px;
            font-weight: 800;
        }

        .brand-name {
            font-size: 18px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .brand-subtitle {
            margin-top: 3px;
            font-size: 11px;
            color: #b9c8e5;
        }

        .user-area {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #e1e8f6;
            font-size: 12px;
        }

        .user-icon {
            width: 36px;
            height: 36px;
            display: grid;
            place-items: center;
            border-radius: 50%;
            background: #1a2e47;
            font-size: 17px;
        }

        /* Cabeçalho da página */

        .hero {
            background:
                radial-gradient(
                    circle at 85% 15%,
                    rgba(92, 75, 230, 0.22),
                    transparent 35%
                ),
                linear-gradient(115deg, #0d1b2a, #172443);
            color: white;
            padding: 34px 0 38px;
        }

        .hero-inner {
            width: min(1400px, calc(100% - 64px));
            margin: auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 24px;
        }

        .eyebrow {
            display: flex;
            align-items: center;
            gap: 9px;
            margin-bottom: 13px;
            color: #bcb5ff;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }

        .eyebrow::before {
            content: "";
            width: 20px;
            height: 2px;
            background: #8f83ff;
            border-radius: 5px;
        }

        .hero h1 {
            margin: 0;
            font-size: clamp(27px, 3vw, 36px);
            font-weight: 800;
            letter-spacing: -1.1px;
        }

        .hero h1 span {
            color: #a99dff;
        }

        .hero p {
            margin: 10px 0 0;
            color: #c5d0e2;
            font-size: 14px;
            line-height: 1.7;
        }

        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
        }

        /* Botões */

        .btn {
            min-height: 40px;
            padding: 0 16px;
            border-radius: 9px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            border: 1px solid transparent;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            transition: background 0.2s, transform 0.2s,
                border-color 0.2s;
            white-space: nowrap;
        }

        .btn:hover {
            transform: translateY(-1px);
        }

        .btn-primary {
            color: white;
            background: var(--primary);
            border-color: var(--primary);
        }

        .btn-primary:hover {
            background: #281ab7;
            border-color: #281ab7;
        }

        .btn-dark {
            color: white;
            background: var(--navy);
            border-color: var(--navy);
        }

        .btn-dark:hover {
            background: var(--navy-light);
        }

        .btn-hero {
            color: white;
            border-color: rgba(255, 255, 255, 0.25);
            background: rgba(255, 255, 255, 0.07);
        }

        .btn-hero:hover {
            background: rgba(255, 255, 255, 0.14);
        }

        .btn-outline {
            color: #56647b;
            background: white;
            border-color: #d5dce8;
        }

        .btn-outline:hover {
            background: #f7f8fc;
            border-color: #aab5c8;
        }

        .btn-edit {
            min-height: 32px;
            padding: 0 11px;
            color: var(--primary);
            border: 1px solid #d8d4ff;
            background: #f6f5ff;
            border-radius: 7px;
            font-size: 12px;
        }

        .btn-edit:hover {
            background: #eae8ff;
        }

        .btn-delete {
            min-height: 32px;
            padding: 0 11px;
            color: var(--red);
            border: 1px solid #ffd8de;
            background: #fff6f7;
            border-radius: 7px;
            font-size: 12px;
        }

        .btn-delete:hover {
            background: #ffe8eb;
        }

        /* Conteúdo */

        .main {
            width: min(1400px, calc(100% - 64px));
            margin: 30px auto 56px;
        }

        .section-heading {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 15px;
            margin-bottom: 18px;
        }

        .section-heading h2 {
            margin: 0;
            font-size: 19px;
            font-weight: 800;
            letter-spacing: -0.4px;
        }

        .section-heading p {
            margin: 6px 0 0;
            color: var(--muted);
            font-size: 13px;
        }

        .results-count {
            padding: 7px 12px;
            border-radius: 8px;
            background: var(--primary-light);
            color: var(--primary);
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
        }

        /* Cartões de resumo */

        .stats {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 26px;
        }

        .stat-card {
            padding: 20px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 14px;
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .stat-icon {
            width: 46px;
            height: 46px;
            flex-shrink: 0;
            display: grid;
            place-items: center;
            border-radius: 12px;
            font-size: 20px;
            font-weight: 700;
        }

        .stat-icon.purple {
            color: var(--primary);
            background: var(--primary-light);
        }

        .stat-icon.green {
            color: var(--green);
            background: #e5f7ee;
        }

        .stat-icon.orange {
            color: var(--orange);
            background: #fff3d8;
        }

        .stat-icon.red {
            color: var(--red);
            background: #fff0f2;
        }

        .stat-label {
            color: var(--muted);
            font-size: 12px;
            font-weight: 500;
        }

        .stat-value {
            margin-top: 5px;
            font-size: 25px;
            font-weight: 800;
            letter-spacing: -0.7px;
            color: var(--text);
        }

        /* Mensagens */

        .alert {
            padding: 14px 17px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 10px;
            line-height: 1.5;
        }

        .alert-success {
            background: #eaf8f0;
            color: #126b45;
            border-color: #c8ebd7;
        }

        .alert-danger {
            background: #fff0f1;
            color: #a92939;
            border-color: #ffd5da;
        }

        .alert-warning {
            background: #fff7e5;
            color: #895b00;
            border-color: #f6e4b6;
        }

        /* Filtros */

        .filter-card {
            padding: 21px;
            margin-bottom: 20px;
            background: white;
            border: 1px solid var(--border);
            border-radius: 14px;
            box-shadow: var(--shadow);
        }

        .filter-title {
            margin: 0 0 15px;
            font-size: 14px;
            font-weight: 700;
        }

        .filters {
            display: grid;
            grid-template-columns: minmax(220px, 2fr) minmax(180px, 1.1fr) auto auto;
            gap: 12px;
            align-items: end;
        }

        .field {
            min-width: 0;
        }

        .field label {
            display: block;
            margin-bottom: 8px;
            color: #46536a;
            font-size: 12px;
            font-weight: 600;
        }

        .control {
            width: 100%;
            height: 43px;
            padding: 0 13px;
            color: var(--text);
            background: #fff;
            border: 1px solid #dce2ec;
            border-radius: 9px;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .control::placeholder {
            color: #9aa5b7;
        }

        .control:focus {
            border-color: #8275ff;
            box-shadow: 0 0 0 3px rgba(83, 65, 220, 0.1);
        }

        .filter-button {
            min-width: 100px;
            height: 43px;
        }

        /* Tabela */

        .table-card {
            overflow: hidden;
            background: white;
            border: 1px solid var(--border);
            border-radius: 14px;
            box-shadow: var(--shadow);
        }

        .table-header {
            padding: 20px 22px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            border-bottom: 1px solid var(--border);
        }

        .table-header h3 {
            margin: 0;
            font-size: 15px;
            font-weight: 800;
        }

        .table-header p {
            margin: 5px 0 0;
            color: var(--muted);
            font-size: 12px;
        }

        .table-wrap {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            white-space: nowrap;
        }

        thead {
            background: #f7f8fc;
        }

        th {
            padding: 15px 16px;
            color: #69758b;
            border-bottom: 1px solid var(--border);
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.45px;
        }

        td {
            padding: 16px;
            color: #354258;
            border-bottom: 1px solid #edf0f6;
            font-size: 12px;
        }

        tbody tr {
            transition: background 0.15s;
        }

        tbody tr:hover {
            background: #fafaff;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        .id-cell {
            color: #8490a4;
            font-weight: 600;
        }

        .train-id {
            color: #192742;
            font-size: 13px;
            font-weight: 800;
        }

        .model-name {
            color: #4a5870;
        }

        .location {
            max-width: 220px;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .numeric {
            font-variant-numeric: tabular-nums;
        }

        .status {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 6px 9px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
        }

        .status::before {
            content: "";
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: currentColor;
        }

        .status.ativo {
            color: #137849;
            background: #e5f7ed;
        }

        .status.inativo {
            color: #627086;
            background: #edf0f5;
        }

        .status.manutencao {
            color: #986200;
            background: #fff3d6;
        }

        .status.falha {
            color: #c32e43;
            background: #ffeaed;
        }

        .actions {
            display: flex;
            align-items: center;
            gap: 7px;
        }

        .empty-state {
            padding: 54px 20px;
            text-align: center;
            white-space: normal;
        }

        .empty-icon {
            width: 54px;
            height: 54px;
            margin: 0 auto 14px;
            display: grid;
            place-items: center;
            border-radius: 16px;
            color: var(--primary);
            background: var(--primary-light);
            font-size: 25px;
        }

        .empty-state strong {
            display: block;
            margin-bottom: 7px;
            color: var(--text);
            font-size: 15px;
        }

        .empty-state p {
            margin: 0;
            color: var(--muted);
            font-size: 13px;
        }

        .footer {
            padding: 22px 0 0;
            text-align: center;
            color: #8b96a9;
            font-size: 11px;
        }

        /* Responsividade */

        @media (max-width: 1050px) {
            .stats {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .filters {
                grid-template-columns: 1fr 1fr;
            }

            .filter-button {
                width: 100%;
            }
        }

        @media (max-width: 700px) {
            .navbar-inner,
            .hero-inner,
            .main {
                width: calc(100% - 32px);
            }

            .navbar-inner {
                min-height: 68px;
            }

            .hero {
                padding: 28px 0;
            }

            .hero-inner {
                align-items: flex-start;
                flex-direction: column;
            }

            .hero-actions {
                width: 100%;
            }

            .hero-actions .btn {
                flex: 1;
            }

            .main {
                margin-top: 22px;
            }

            .stats {
                gap: 10px;
            }

            .stat-card {
                padding: 14px;
                gap: 10px;
            }

            .stat-icon {
                width: 38px;
                height: 38px;
                font-size: 17px;
            }

            .stat-value {
                font-size: 22px;
            }

            .filters {
                grid-template-columns: 1fr;
            }

            .filter-card {
                padding: 16px;
            }

            .section-heading {
                align-items: flex-start;
            }

            .table-header {
                align-items: flex-start;
                flex-direction: column;
                padding: 17px;
            }

            th,
            td {
                padding: 14px;
            }
        }

        @media (max-width: 400px) {
            .stats {
                grid-template-columns: 1fr;
            }

            .user-area {
                font-size: 0;
            }

            .user-icon {
                font-size: 17px;
            }
        }
    </style>
</head>

<body>

<!-- Barra superior -->
<header class="navbar">
    <div class="navbar-inner">

        <a href="../sistema.php" class="brand">
            <div class="brand-icon">🚆</div>

            <div>
                <div class="brand-name">Ferrorama</div>
                <div class="brand-subtitle">Sistema ferroviário</div>
            </div>
        </a>

        <div class="user-area">
            <div class="user-icon">♙</div>

            <span>
                Área do usuário
                <?= $ehAdministrador ? "Administrador" : "Operador" ?>
            </span>
        </div>

    </div>
</header>

<!-- Cabeçalho -->
<section class="hero">
    <div class="hero-inner">

        <div>
            <div class="eyebrow">Gestão ferroviária</div>

            <h1>
                Gerenciamento de <span>trens.</span>
            </h1>

            <p>
                Consulte os veículos ferroviários, acompanhe seus status
                e gerencie as informações da frota.
            </p>
        </div>

        <div class="hero-actions">

            <a href="../sistema.php" class="btn btn-hero">
                ← Voltar ao sistema
            </a>

            <?php if ($ehAdministrador): ?>

                <a href="../rotas/rotas.php" class="btn btn-hero">
                    ↗ Rotas
                </a>

                <a href="cadastrotrem.php" class="btn btn-primary">
                    + Cadastrar trem
                </a>

            <?php endif; ?>

        </div>

    </div>
</section>

<main class="main">

    <!-- Mensagens -->
    <?php if (
        isset($_GET["sucesso"]) &&
        $_GET["sucesso"] === "excluido"
    ): ?>

        <div class="alert alert-success">
            ✓ Trem excluído com sucesso!
        </div>

    <?php elseif (
        isset($_GET["sucesso"]) &&
        $_GET["sucesso"] === "cadastrado"
    ): ?>

        <div class="alert alert-success">
            ✓ Trem cadastrado com sucesso!
        </div>

    <?php elseif (
        isset($_GET["erro"]) &&
        $_GET["erro"] === "dependencias"
    ): ?>

        <div class="alert alert-danger">
            Não foi possível excluir o trem porque existem registros
            relacionados a ele.
        </div>

    <?php elseif (
        isset($_GET["erro"]) &&
        $_GET["erro"] === "nao_encontrado"
    ): ?>

        <div class="alert alert-warning">
            O trem não foi encontrado ou já foi excluído.
        </div>

    <?php elseif (
        isset($_GET["erro"]) &&
        $_GET["erro"] === "id_invalido"
    ): ?>

        <div class="alert alert-warning">
            Identificador do trem inválido.
        </div>

    <?php endif; ?>

    <!-- Título da seção -->
    <div class="section-heading">
        <div>
            <h2>Visão geral da frota</h2>
            <p>Resumo dos trens que correspondem à sua consulta.</p>
        </div>

        <div class="results-count">
            <?= $totalTrens ?>
            <?= $totalTrens === 1 ? "trem encontrado" : "trens encontrados" ?>
        </div>
    </div>

    <!-- Indicadores -->
    <section class="stats">

        <div class="stat-card">
            <div class="stat-icon purple">🚆</div>

            <div>
                <div class="stat-label">Total de trens</div>
                <div class="stat-value"><?= $totalTrens ?></div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon green">✓</div>

            <div>
                <div class="stat-label">Em operação</div>
                <div class="stat-value"><?= $totalAtivos ?></div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon orange">⚙</div>

            <div>
                <div class="stat-label">Em manutenção</div>
                <div class="stat-value"><?= $totalManutencao ?></div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon red">!</div>

            <div>
                <div class="stat-label">Com falha</div>
                <div class="stat-value"><?= $totalFalhas ?></div>
            </div>
        </div>

    </section>

    <!-- Filtros -->
    <section class="filter-card">

        <h3 class="filter-title">⌕ Buscar e filtrar trens</h3>

        <form
            method="GET"
            action="listagemtrem.php"
            class="filters"
        >

            <div class="field">
                <label for="busca">Pesquisar</label>

                <input
                    type="text"
                    id="busca"
                    name="busca"
                    class="control"
                    placeholder="Identificador, modelo ou localização..."
                    maxlength="100"
                    value="<?= escapar($busca) ?>"
                >
            </div>

            <div class="field">
                <label for="status">Status do trem</label>

                <select
                    id="status"
                    name="status"
                    class="control"
                >
                    <option value="">Todos os status</option>

                    <option
                        value="ativo"
                        <?= $statusFiltro === "ativo" ? "selected" : "" ?>
                    >
                        Ativo
                    </option>

                    <option
                        value="inativo"
                        <?= $statusFiltro === "inativo" ? "selected" : "" ?>
                    >
                        Inativo
                    </option>

                    <option
                        value="manutencao"
                        <?= $statusFiltro === "manutencao" ? "selected" : "" ?>
                    >
                        Manutenção
                    </option>

                    <option
                        value="falha"
                        <?= $statusFiltro === "falha" ? "selected" : "" ?>
                    >
                        Falha
                    </option>
                </select>
            </div>

            <button type="submit" class="btn btn-dark filter-button">
                ⌕ Buscar
            </button>

            <a
                href="listagemtrem.php"
                class="btn btn-outline filter-button"
            >
                Limpar filtros
            </a>

        </form>

    </section>

    <!-- Tabela -->
    <section class="table-card">

        <div class="table-header">
            <div>
                <h3>Trens cadastrados</h3>

                <p>
                    Informações operacionais e dados atualizados da frota.
                </p>
            </div>

            <div class="results-count">
                <?= $totalTrens ?> registros
            </div>
        </div>

        <div class="table-wrap">

            <table>

                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Identificador</th>
                        <th>Modelo</th>
                        <th>Status</th>
                        <th>Velocidade</th>
                        <th>Localização</th>
                        <th>Consumo</th>
                        <th>Atualizado em</th>

                        <?php if ($ehAdministrador): ?>
                            <th>Ações</th>
                        <?php endif; ?>
                    </tr>
                </thead>

                <tbody>

                <?php if (empty($trens)): ?>

                    <tr>
                        <td
                            colspan="<?= $ehAdministrador ? 9 : 8 ?>"
                            class="empty-state"
                        >
                            <div class="empty-icon">⌕</div>

                            <strong>Nenhum trem encontrado</strong>

                            <p>
                                Tente alterar os filtros ou limpar a pesquisa
                                para visualizar os outros trens.
                            </p>
                        </td>
                    </tr>

                <?php else: ?>

                    <?php foreach ($trens as $trem): ?>

                        <tr>

                            <td class="id-cell">
                                #<?= (int) $trem["id"] ?>
                            </td>

                            <td>
                                <span class="train-id">
                                    <?= escapar($trem["identificador"]) ?>
                                </span>
                            </td>

                            <td class="model-name">
                                <?= escapar($trem["modelo"]) ?>
                            </td>

                            <td>
                                <span class="status <?= escapar(
                                    corStatusTrem($trem["status"])
                                ) ?>">
                                    <?= escapar(
                                        nomeStatusTrem($trem["status"])
                                    ) ?>
                                </span>
                            </td>

                            <td class="numeric">
                                <?= number_format(
                                    (float) $trem["velocidade_atual"],
                                    2,
                                    ",",
                                    "."
                                ) ?> km/h
                            </td>

                            <td
                                class="location"
                                title="<?= escapar(
                                    $trem["localizacao_atual"] ?? "Não informado"
                                ) ?>"
                            >
                                <?= escapar(
                                    $trem["localizacao_atual"] ?? "Não informado"
                                ) ?>
                            </td>

                            <td class="numeric">
                                <?= number_format(
                                    (float) $trem["consumo_energia"],
                                    2,
                                    ",",
                                    "."
                                ) ?> kWh
                            </td>

                            <td class="numeric">
                                <?php if (!empty($trem["atualizado_em"])): ?>

                                    <?= escapar(
                                        date(
                                            "d/m/Y H:i",
                                            strtotime($trem["atualizado_em"])
                                        )
                                    ) ?>

                                <?php else: ?>

                                    —

                                <?php endif; ?>
                            </td>

                            <?php if ($ehAdministrador): ?>

                                <td>
                                    <div class="actions">

                                        <a
                                            href="editartrem.php?id=<?= (int) $trem["id"] ?>"
                                            class="btn btn-edit"
                                        >
                                            Editar
                                        </a>

                                        <form
                                            method="POST"
                                            action="../trem_excluir.php"
                                            class="delete-form"
                                            style="margin: 0;"
                                        >

                                            <input
                                                type="hidden"
                                                name="id"
                                                value="<?= (int) $trem["id"] ?>"
                                            >

                                            <button
                                                type="submit"
                                                class="btn btn-delete"
                                            >
                                                Excluir
                                            </button>

                                        </form>

                                    </div>
                                </td>

                            <?php endif; ?>

                        </tr>

                    <?php endforeach; ?>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>

    <footer class="footer">
        Ferrorama · Sistema de gerenciamento ferroviário
    </footer>

</main>

<script>
    document.querySelectorAll(".delete-form").forEach(form => {
        form.addEventListener("submit", event => {
            event.preventDefault();

            const confirmado = confirm(
                "Tem certeza que deseja excluir este trem? " +
                "Esta ação não pode ser desfeita."
            );

            if (confirmado) {
                form.submit();
            }
        });
    });
</script>

</body>
</html>
