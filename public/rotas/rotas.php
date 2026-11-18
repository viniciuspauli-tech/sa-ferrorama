<?php

require_once __DIR__ . "/../../infra/auth.php"; // exige login (todos os perfis)

// Só o administrador cadastra, edita e exclui; o usuário comum apenas consulta
$ehAdministrador =
    ($_SESSION["usuario_perfil"] ?? "") === "administrador";
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

    if (!$ehAdministrador) {
        http_response_code(403);
        exit("Acesso negado. Apenas administradores podem excluir rotas.");
    }

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

    

<link rel="stylesheet" href="../../style/style.css?v=3">
</head>

<body class="page-public_rotas_rotas_php">

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

            <?= $ehAdministrador ? "Área administrativa" : "Área do usuário" ?>

        </div>

    </div>

</header>

<!-- DESTAQUE -->
<section class="hero">

    <div class="hero-inner">

        <div class="eyebrow"><?= $ehAdministrador ? "Painel de controle" : "Consulta" ?></div>

        <h1>
            <?= $ehAdministrador ? "Gerenciar" : "Consultar" ?> <span class="inline-style-1">Rotas.</span>
        </h1>

        <p>
            <?= $ehAdministrador
                ? "Gerencie os trajetos ferroviários, consulte as rotas cadastradas e acompanhe as informações de origem, destino e distância."
                : "Consulte as rotas cadastradas e acompanhe as informações de origem, destino e distância." ?>
        </p>

    </div>

</section>

<!-- CONTEÚDO PRINCIPAL -->
<main class="main">

    <!-- TÍTULO E NAVEGAÇÃO -->
    <div class="page-heading">

        <div>

            <h2><?= $ehAdministrador ? "Gerenciamento de rotas" : "Rotas ferroviárias" ?></h2>

            <p>
                <?= $ehAdministrador
                    ? "Consulte, pesquise e gerencie os trajetos ferroviários."
                    : "Consulte e pesquise os trajetos ferroviários." ?>
            </p>

        </div>

        <div class="heading-actions">

            <a href="../sistema.php" class="button button-secondary">
                ← Voltar ao sistema
            </a>

            <a href="../trem/listagemtrem.php" class="button button-secondary">
                Trens
            </a>

            <?php if ($ehAdministrador): ?>

                <a href="cadastrorota.php" class="button button-primary">
                    + Cadastrar rota
                </a>

            <?php endif; ?>

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
                    <?= $ehAdministrador
                        ? "Consulte as informações e gerencie suas rotas."
                        : "Consulte as informações das rotas." ?>
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
                        <?php if ($ehAdministrador): ?>
                            <th>Ações</th>
                        <?php endif; ?>
                    </tr>

                </thead>

                <tbody>

                    <?php if (empty($rotas)): ?>

                        <tr>

                            <td colspan="<?= $ehAdministrador ? 8 : 7 ?>">

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

                                <?php if ($ehAdministrador): ?>

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

                                <?php endif; ?>

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