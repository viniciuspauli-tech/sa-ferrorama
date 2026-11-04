
<?php

require_once __DIR__ . "/../../infra/adm.php";
require_once __DIR__ . "/../../infra/connect.php";

// TOKEN CSRF
if (empty($_SESSION["csrf"])) {
    $_SESSION["csrf"] = bin2hex(random_bytes(32));
}

// MENSAGENS
$mensagens = [
    "excluida"       => ["success", "Rota excluída com sucesso."],
    "nao_encontrada" => ["warning", "Rota não encontrada ou já excluída."],
    "erro"           => ["danger", "Não foi possível excluir a rota."],
];

$msg = $mensagens[$_GET["msg"] ?? ""] ?? null;

// EXCLUSÃO
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["excluir_id"])) {

    $idExcluir = filter_var($_POST["excluir_id"], FILTER_VALIDATE_INT);
    $tokenOk = hash_equals($_SESSION["csrf"], $_POST["csrf"] ?? "");

    if (!$tokenOk || !$idExcluir) {
        header("Location: rotas.php?msg=erro");
        exit;
    }

    try {
        $stmtExcluir = $conn->prepare("DELETE FROM rotas WHERE id = ?");
        $stmtExcluir->bind_param("i", $idExcluir);
        $stmtExcluir->execute();

        $excluiu = $stmtExcluir->affected_rows > 0;
        $stmtExcluir->close();

        header("Location: rotas.php?msg=" . ($excluiu ? "excluida" : "nao_encontrada"));

    } catch (mysqli_sql_exception $e) {
        error_log("Erro ao excluir rota: " . $e->getMessage());
        header("Location: rotas.php?msg=erro");
    }

    exit;
}

// BUSCA E FILTROS
$busca = trim($_GET["busca"] ?? "");
$statusFiltro = $_GET["status"] ?? "";

$sql = "
    SELECT
        id,
        nome,
        origem,
        destino,
        distancia_km,
        status
    FROM rotas
    WHERE 1=1
";

$tipos = "";
$params = [];

if ($busca !== "") {

    $sql .= "
        AND (
            nome LIKE ?
            OR origem LIKE ?
            OR destino LIKE ?
        )
    ";

    $like = "%$busca%";

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

$sql .= " ORDER BY id ASC";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Erro ao preparar consulta: " . $conn->error);
}

if (!empty($params)) {
    $stmt->bind_param($tipos, ...$params);
}

$stmt->execute();

$resultado = $stmt->get_result();
$rotas = $resultado->fetch_all(MYSQLI_ASSOC);

$stmt->close();

// CORES DOS STATUS
function corStatusRota($status)
{
    switch ($status) {

        case "ativa":
        case "ativo":
            return "ativa";

        case "inativa":
        case "inativo":
            return "inativa";

        case "manutencao":
            return "manutencao";

        case "bloqueada":
        case "bloqueado":
            return "bloqueada";

        default:
            return "inativa";
    }
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Rotas - Ferrorama</title>

    <link
        href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

    <style>

        :root {
            --navy: #0c192b;
            --purple: #6554e8;
            --purple-dark: #5140d4;
            --purple-light: #eeecff;
            --background: #f2f4f9;
            --white: #ffffff;
            --text: #132238;
            --muted: #73819a;
            --border: #dfe5ef;
            --success: #18794e;
            --success-bg: #eaf8f0;
            --danger: #b52d3b;
            --danger-bg: #fff0f1;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            min-height: 100vh;
            font-family: "Roboto", sans-serif;
            color: var(--text);
            background: var(--background);
        }

        a {
            text-decoration: none;
        }

        /* CABEÇALHO */

        .topbar {
            background: var(--navy);
            color: white;
            border-bottom: 1px solid rgba(255,255,255,.08);
        }

        .topbar-inner {
            max-width: 1500px;
            min-height: 76px;
            margin: auto;
            padding: 0 28px;
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
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            border-radius: 13px;
            background: white;
            color: #2415d5;
        }

        .brand-icon svg {
            width: 26px;
            height: 26px;
            display: block;
        }

        .brand-name {
            font-size: 19px;
            font-weight: 800;
            letter-spacing: -.5px;
        }

        .brand-subtitle {
            margin-top: 3px;
            color: #b6c6e4;
            font-size: 11px;
        }

        .top-label {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #edf2ff;
            font-size: 13px;
        }

        .top-avatar {
            width: 38px;
            height: 38px;
            display: grid;
            place-items: center;
            border-radius: 50%;
            background: #1c2d45;
        }

        /* DESTAQUE */

        .hero {
            padding: 45px 24px 48px;
            color: white;
            background:
                radial-gradient(
                    circle at 85% 20%,
                    rgba(101,84,232,.25),
                    transparent 38%
                ),
                linear-gradient(115deg, #0c192b, #17243e);
        }

        .hero-inner {
            max-width: 1068px;
            margin: auto;
        }

        .eyebrow {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
            color: #c5c0ff;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 1.4px;
            text-transform: uppercase;
        }

        .eyebrow::before {
            content: "";
            width: 20px;
            height: 2px;
            background: #9286ff;
        }

        .hero h1 {
            margin-bottom: 12px;
            font-size: clamp(28px, 4vw, 36px);
            letter-spacing: -1px;
        }

        .hero p {
            max-width: 620px;
            color: #c7d2e7;
            font-size: 14px;
            line-height: 1.8;
        }

        /* CONTEÚDO */

        .main {
            max-width: 1116px;
            margin: auto;
            padding: 34px 24px 60px;
        }

        .page-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 18px;
            margin-bottom: 24px;
        }

        .page-heading h2 {
            font-size: 20px;
            font-weight: 700;
        }

        .page-heading p {
            margin-top: 6px;
            color: var(--muted);
            font-size: 13px;
        }

        .heading-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 9px;
        }

        /* BOTÕES */

        .button {
            min-height: 42px;
            padding: 0 17px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: 1px solid transparent;
            border-radius: 9px;
            font-family: inherit;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            transition: .2s ease;
            white-space: nowrap;
        }

        .button:hover {
            transform: translateY(-1px);
        }

        .button-primary {
            color: white;
            background: var(--purple);
            box-shadow: 0 5px 12px rgba(101,84,232,.15);
        }

        .button-primary:hover {
            color: white;
            background: var(--purple-dark);
        }

        .button-secondary {
            color: var(--text);
            background: white;
            border-color: var(--border);
        }

        .button-secondary:hover {
            color: var(--purple);
            border-color: #c9c2ff;
        }

        .button-danger {
            color: var(--danger);
            background: var(--danger-bg);
            border-color: #f4d3d7;
        }

        .button-danger:hover {
            color: white;
            background: var(--danger);
        }

        .button-small {
            min-height: 34px;
            padding: 0 12px;
            font-size: 12px;
        }

        /* FILTROS */

        .filter-card {
            margin-bottom: 22px;
            padding: 20px;
            background: white;
            border: 1px solid var(--border);
            border-radius: 14px;
            box-shadow: 0 5px 20px rgba(23,40,70,.035);
        }

        .filter-title {
            margin-bottom: 15px;
            font-size: 14px;
            font-weight: 700;
        }

        .filter-form {
            display: grid;
            grid-template-columns: 2fr 1.2fr .8fr .8fr;
            gap: 10px;
        }

        .filter-form input,
        .filter-form select {
            width: 100%;
            min-width: 0;
            height: 43px;
            padding: 0 13px;
            color: var(--text);
            background: white;
            border: 1px solid #d8dfeb;
            border-radius: 9px;
            outline: none;
            font-family: inherit;
            font-size: 13px;
        }

        .filter-form input:focus,
        .filter-form select:focus {
            border-color: var(--purple);
            box-shadow: 0 0 0 3px rgba(101,84,232,.12);
        }

        /* ALERTAS */

        .alert {
            margin-bottom: 20px;
            padding: 14px 16px;
            border-radius: 10px;
            font-size: 13px;
            line-height: 1.6;
        }

        .alert-success {
            color: var(--success);
            background: var(--success-bg);
            border: 1px solid #c9ead6;
        }

        .alert-warning {
            color: #946200;
            background: #fff8e5;
            border: 1px solid #f2dfaa;
        }

        .alert-danger {
            color: var(--danger);
            background: var(--danger-bg);
            border: 1px solid #f4d3d7;
        }

        /* CARTÃO DA TABELA */

        .content-card {
            overflow: hidden;
            background: white;
            border: 1px solid var(--border);
            border-radius: 16px;
            box-shadow: 0 5px 20px rgba(23,40,70,.035);
        }

        .card-heading {
            padding: 21px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            border-bottom: 1px solid #edf0f5;
        }

        .card-heading h3 {
            font-size: 16px;
            font-weight: 700;
        }

        .card-heading p {
            margin-top: 5px;
            color: var(--muted);
            font-size: 12px;
        }

        .card-icon {
            width: 40px;
            height: 40px;
            display: grid;
            place-items: center;
            flex-shrink: 0;
            color: #4b3ac8;
            background: var(--purple-light);
            border-radius: 12px;
        }

        .card-icon svg {
            width: 21px;
            height: 21px;
        }

        /* TABELA */

        .table-container {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        thead {
            background: #f8f9fc;
        }

        th {
            padding: 15px 16px;
            color: #46546b;
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
            border-bottom: 1px solid var(--border);
        }

        td {
            padding: 15px 16px;
            color: #35445c;
            font-size: 13px;
            border-bottom: 1px solid #edf0f5;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        tbody tr:hover {
            background: #fafaff;
        }

        .route-id {
            color: #5140d4;
            font-weight: 700;
        }

        .route-name {
            color: var(--text);
            font-weight: 700;
        }

        /* STATUS */

        .status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            white-space: nowrap;
        }

        .status::before {
            content: "";
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: currentColor;
        }

        .status-ativa {
            color: #18794e;
            background: #eaf8f0;
        }

        .status-inativa {
            color: #667085;
            background: #f0f2f5;
        }

        .status-manutencao {
            color: #946200;
            background: #fff5dc;
        }

        .status-bloqueada {
            color: #b52d3b;
            background: #fff0f1;
        }

        /* AÇÕES */

        .actions {
            display: flex;
            align-items: center;
            gap: 7px;
        }

        .actions form {
            margin: 0;
        }

        /* ESTADO VAZIO */

        .empty-state {
            padding: 42px 20px;
            color: var(--muted);
            text-align: center;
        }

        .empty-icon {
            width: 48px;
            height: 48px;
            margin: 0 auto 12px;
            display: grid;
            place-items: center;
            color: #6554e8;
            background: var(--purple-light);
            border-radius: 14px;
        }

        .empty-icon svg {
            width: 24px;
            height: 24px;
        }

        .empty-state strong {
            display: block;
            margin-bottom: 5px;
            color: var(--text);
            font-size: 14px;
        }

        .empty-state span {
            font-size: 12px;
        }

        /* RODAPÉ */

        .footer {
            margin-top: 30px;
            color: #8a96aa;
            text-align: center;
            font-size: 11px;
        }

        /* RESPONSIVIDADE */

        @media (max-width: 850px) {

            .filter-form {
                grid-template-columns: 1fr 1fr;
            }

        }

        @media (max-width: 700px) {

            .topbar-inner {
                padding: 0 18px;
            }

            .hero {
                padding: 35px 20px;
            }

            .main {
                padding: 25px 16px 40px;
            }

            .page-heading {
                align-items: flex-start;
                flex-direction: column;
            }

            .heading-actions {
                width: 100%;
            }

            .heading-actions .button {
                flex: 1;
            }

            .filter-form {
                grid-template-columns: 1fr;
            }

            .filter-form .button {
                width: 100%;
            }

        }

        @media (max-width: 480px) {

            .top-label {
                font-size: 0;
            }

            .top-avatar {
                width: 36px;
                height: 36px;
            }

            .brand-name {
                font-size: 17px;
            }

            .card-heading {
                padding: 17px;
            }

            .actions {
                flex-direction: column;
                align-items: stretch;
            }

            .actions .button {
                width: 100%;
            }

            th, td {
                padding: 13px 12px;
            }

        }

    </style>

</head>

<body>

<!-- CABEÇALHO -->
<header class="topbar">

    <div class="topbar-inner">

        <a href="../sistema.php" class="brand">

            <span class="brand-icon">

                <svg viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="1.8"
                     stroke-linecap="round" stroke-linejoin="round"
                     aria-hidden="true">

                    <rect x="5" y="3" width="14" height="15" rx="4"></rect>
                    <path d="M8 7h8M8 12h8M8 18l-2 3M16 18l2 3M5 15h14"></path>
                    <circle cx="9" cy="15" r="1"></circle>
                    <circle cx="15" cy="15" r="1"></circle>

                </svg>

            </span>

            <div>
                <div class="brand-name">Ferrorama</div>
                <div class="brand-subtitle">Sistema ferroviário</div>
            </div>

        </a>

        <div class="top-label">

            <div class="top-avatar">

                <svg width="19" height="19" viewBox="0 0 24 24"
                     fill="none" stroke="currentColor"
                     stroke-width="1.7" stroke-linecap="round"
                     stroke-linejoin="round">

                    <circle cx="12" cy="8" r="4"></circle>
                    <path d="M5 21v-2a7 7 0 0 1 14 0v2"></path>

                </svg>

            </div>

            Área administrativa

        </div>

    </div>

</header>

<!-- DESTAQUE -->
<section class="hero">

    <div class="hero-inner">

        <div class="eyebrow">Painel de controle</div>

        <h1>
            Gerenciar <span style="color:#a99cff;">Rotas.</span>
        </h1>

        <p>
            Gerencie os trajetos ferroviários, consulte as rotas cadastradas
            e acompanhe as informações de origem, destino e distância.
        </p>

    </div>

</section>

<!-- CONTEÚDO PRINCIPAL -->
<main class="main">

    <!-- TÍTULO E NAVEGAÇÃO -->
    <div class="page-heading">

        <div>

            <h2>Gerenciamento de rotas</h2>

            <p>
                Consulte, pesquise e gerencie os trajetos ferroviários.
            </p>

        </div>

        <div class="heading-actions">

            <a href="../sistema.php" class="button button-secondary">
                ← Voltar ao sistema
            </a>

            <a href="../trem/listagemtrem.php" class="button button-secondary">
                Trens
            </a>

            <a href="cadastrorota.php" class="button button-primary">
                + Cadastrar rota
            </a>

        </div>

    </div>

    <!-- FILTROS -->
    <section class="filter-card">

        <div class="filter-title">
            Buscar e filtrar rotas
        </div>

        <form method="GET" class="filter-form">

            <input
                type="text"
                name="busca"
                placeholder="Buscar por nome, origem ou destino..."
                value="<?= htmlspecialchars($busca) ?>"
            >

            <select name="status">

                <option value="">Todos os status</option>

                <option value="ativa" <?= $statusFiltro == "ativa" ? "selected" : "" ?>>
                    Ativa
                </option>

                <option value="inativa" <?= $statusFiltro == "inativa" ? "selected" : "" ?>>
                    Inativa
                </option>

                <option value="manutencao" <?= $statusFiltro == "manutencao" ? "selected" : "" ?>>
                    Manutenção
                </option>

                <option value="bloqueada" <?= $statusFiltro == "bloqueada" ? "selected" : "" ?>>
                    Bloqueada
                </option>

            </select>

            <button type="submit" class="button button-primary">
                Buscar
            </button>

            <a href="rotas.php" class="button button-secondary">
                Limpar filtros
            </a>

        </form>

    </section>

    <!-- MENSAGENS -->
    <?php if ($msg): ?>

        <div class="alert alert-<?= htmlspecialchars($msg[0]) ?>">
            <?= htmlspecialchars($msg[1]) ?>
        </div>

    <?php elseif (!empty($_GET["sucesso"])): ?>

        <div class="alert alert-success">
            <?= htmlspecialchars($_GET["sucesso"]) ?>
        </div>

    <?php endif; ?>

    <!-- LISTAGEM -->
    <section class="content-card">

        <div class="card-heading">

            <div>

                <h3>Rotas cadastradas</h3>

                <p>
                    Consulte as informações e gerencie suas rotas.
                </p>

            </div>

            <div class="card-icon">

                <svg viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="1.8"
                     stroke-linecap="round" stroke-linejoin="round">

                    <circle cx="6" cy="18" r="2"></circle>
                    <circle cx="18" cy="6" r="2"></circle>
                    <path d="M8 17c4-1 3-8 8-9"></path>
                    <path d="M13 8h3V5"></path>

                </svg>

            </div>

        </div>

        <div class="table-container">

            <table>

                <thead>

                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Origem</th>
                        <th>Destino</th>
                        <th>Distância</th>
                        <th>Tempo</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>

                </thead>

                <tbody>

                    <?php if (empty($rotas)): ?>

                        <tr>

                            <td colspan="8">

                                <div class="empty-state">

                                    <div class="empty-icon">

                                        <svg viewBox="0 0 24 24" fill="none"
                                             stroke="currentColor"
                                             stroke-width="1.8"
                                             stroke-linecap="round"
                                             stroke-linejoin="round">

                                            <circle cx="6" cy="18" r="2"></circle>
                                            <circle cx="18" cy="6" r="2"></circle>
                                            <path d="M8 17c4-1 3-8 8-9"></path>

                                        </svg>

                                    </div>

                                    <strong>Nenhuma rota encontrada.</strong>

                                    <span>
                                        As rotas cadastradas aparecerão nesta lista.
                                    </span>

                                </div>

                            </td>

                        </tr>

                    <?php else: ?>

                        <?php foreach ($rotas as $rota): ?>

                            <tr>

                                <td class="route-id">
                                    #<?= (int)$rota["id"] ?>
                                </td>

                                <td class="route-name">
                                    <?= htmlspecialchars($rota["nome"]) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($rota["origem"]) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($rota["destino"]) ?>
                                </td>

                                <td>
                                    <?= number_format(
                                        (float)($rota["distancia_km"] ?? 0),
                                        2,
                                        ",",
                                        "."
                                    ) ?> km
                                </td>

                                <td>
                                    <?= isset($rota["tempo_estimado_min"])
                                        ? (int)$rota["tempo_estimado_min"] . " min"
                                        : "—" ?>
                                </td>

                                <td>

                                    <span class="status status-<?= corStatusRota($rota["status"]) ?>">

                                        <?= ucfirst(htmlspecialchars($rota["status"])) ?>

                                    </span>

                                </td>

                                <td>

                                    <div class="actions">

                                        <a
                                            href="editrotas.php?id=<?= (int)$rota["id"] ?>"
                                            class="button button-primary button-small"
                                        >
                                            Editar
                                        </a>

                                        <form
                                            method="POST"
                                            action="rotas.php"
                                            onsubmit="return confirm('Excluir a rota &quot;<?= htmlspecialchars($rota["nome"], ENT_QUOTES) ?>&quot;? Esta ação não pode ser desfeita.');"
                                        >

                                            <input
                                                type="hidden"
                                                name="csrf"
                                                value="<?= htmlspecialchars($_SESSION["csrf"]) ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="excluir_id"
                                                value="<?= (int)$rota["id"] ?>"
                                            >

                                            <button
                                                type="submit"
                                                class="button button-danger button-small"
                                            >
                                                Excluir
                                            </button>

                                        </form>

                                    </div>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>

    <!-- RODAPÉ -->
    <div class="footer">
        Ferrorama © <?= date("Y") ?> — Sistema de gerenciamento ferroviário.
    </div>

</main>

</body>
</html>
