
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

// Proteção de textos exibidos
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

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Trens - Ferrorama</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

    
<link rel="stylesheet" href="../../style/style.css">
</head>

<body class="page-public_trem_listagemtrem_php">

<!-- Barra superior -->
<header class="navbar">
    <div class="navbar-inner">

        <a href="../sistema.php" class="brand">

            <div class="brand-icon">
                <svg class="train-svg" viewBox="0 0 24 24"
                     xmlns="http://www.w3.org/2000/svg">
                    <rect x="5" y="3" width="14" height="15" rx="4"/>
                    <path d="M8 7h8M8 12h8M8 18l-2 3M16 18l2 3M5 15h14"/>
                    <circle cx="9" cy="15" r="0.5"/>
                    <circle cx="15" cy="15" r="0.5"/>
                </svg>
            </div>

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
        isset($_GET["sucesso"]) &&
        $_GET["sucesso"] === "editado"
    ): ?>

        <div class="alert alert-success">
            ✓ Trem atualizado com sucesso!
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

            <div class="stat-icon purple">
                <svg class="train-svg" viewBox="0 0 24 24"
                     xmlns="http://www.w3.org/2000/svg">
                    <rect x="5" y="3" width="14" height="15" rx="4"/>
                    <path d="M8 7h8M8 12h8M8 18l-2 3M16 18l2 3M5 15h14"/>
                    <circle cx="9" cy="15" r="0.5"/>
                    <circle cx="15" cy="15" r="0.5"/>
                </svg>
            </div>

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
                                            class="delete-form inline-style-1"
                                           
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
