
<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . "/../infra/auth.php";
require_once __DIR__ . "/../infra/connect.php";

$ehAdministrador =
    ($_SESSION["usuario_perfil"] ?? "") === "administrador";

$nomeUsuario = $_SESSION["usuario_nome"] ?? "Usuário";

function escapar($valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, "UTF-8");
}

$busca = trim($_GET["busca"] ?? "");

$sql = "
    SELECT id, nome, localizacao, tipo_dado
    FROM sensores
    WHERE 1 = 1
";

$params = [];
$tipos = "";

if ($busca !== "") {

    $sql .= "
        AND (
            nome LIKE ?
            OR localizacao LIKE ?
            OR tipo_dado LIKE ?
        )
    ";

    $like = "%" . $busca . "%";

    $params = [$like, $like, $like];
    $tipos = "sss";
}

$sql .= " ORDER BY localizacao ASC, nome ASC";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    error_log($conn->error);
    http_response_code(500);
    exit("Não foi possível consultar os sensores.");
}

if (!empty($params)) {
    $stmt->bind_param($tipos, ...$params);
}

if (!$stmt->execute()) {
    error_log($stmt->error);
    http_response_code(500);
    exit("Não foi possível carregar os sensores.");
}

$resultado = $stmt->get_result();
$sensores = $resultado->fetch_all(MYSQLI_ASSOC);

$stmt->close();

$totalSensores = count($sensores);

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Gerenciamento de Sensores | Ferrorama</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    

<link rel="stylesheet" href="../style/style.css">
</head>

<body class="page-public_listarsensor_php">

<!-- NAVBAR -->

<header class="navbar">

    <div class="navbar-content">

        <a href="sistema.php" class="logo">

            <div class="logo-icon">

                <svg viewBox="0 0 24 24">
                    <rect x="5" y="2.5" width="14" height="15" rx="4"/>
                    <path d="M8 6.5h8"/>
                    <path d="M8 11h2"/>
                    <path d="M14 11h2"/>
                    <path d="M8 17.5l-2 4"/>
                    <path d="M16 17.5l2 4"/>
                    <path d="M9 17.5l-1 2"/>
                    <path d="M15 17.5l1 2"/>
                    <path d="M5 14h14"/>
                </svg>

            </div>

            <div class="logo-text">
                <strong>Ferrorama</strong>
                <span>Sistema ferroviário</span>
            </div>

        </a>

        <div class="usuario">

            <div class="usuario-icon">
                <svg viewBox="0 0 24 24">
                    <circle cx="12" cy="8" r="3"/>
                    <path d="M5 21v-2a7 7 0 0 1 14 0v2"/>
                </svg>
            </div>

            Área do usuário <?= escapar($nomeUsuario) ?>

        </div>

    </div>

</header>


<!-- HERO -->

<section class="hero">

    <div class="hero-content">

        <div>

            <div class="hero-label">
                ━ &nbsp; GESTÃO FERROVIÁRIA
            </div>

            <h1>
                Gerenciamento de <span>sensores.</span>
            </h1>

            <p>
                Consulte os sensores instalados, acompanhe suas informações
                e gerencie os dispositivos da ferrovia.
            </p>

        </div>

        <div class="hero-actions">

            <a href="sistema.php" class="botao botao-voltar">
                ← Voltar ao sistema
            </a>

            <a href="sensor.php" class="botao botao-monitoramento">
                ↗ Monitoramento
            </a>

            <?php if ($ehAdministrador): ?>

                <a href="cadastro_item.php" class="botao botao-primario">
                    + Cadastrar sensor
                </a>

            <?php endif; ?>

        </div>

    </div>

</section>


<!-- CONTEÚDO PRINCIPAL -->

<main>

    <div class="secao-titulo">

        <div>
            <h2>Visão geral dos sensores</h2>
            <p>Consulte e gerencie os sensores cadastrados no sistema.</p>
        </div>

        <span class="contador">
            <?= $totalSensores ?>
            <?= $totalSensores === 1 ? "sensor encontrado" : "sensores encontrados" ?>
        </span>

    </div>


    <!-- FILTROS -->

    <section class="card">

        <div class="card-header">

            <div>
                <h3>⌕ Buscar e filtrar sensores</h3>
                <p>Pesquise por nome, localização ou tipo de dado.</p>
            </div>

        </div>

        <form method="GET" action="listarsensor.php" class="filtros">

            <div class="campo">

                <label for="busca">Pesquisar sensor</label>

                <input
                    type="text"
                    id="busca"
                    name="busca"
                    maxlength="100"
                    placeholder="Nome, localização ou tipo de dado..."
                    value="<?= escapar($busca) ?>"
                >

            </div>

            <button type="submit" class="botao botao-primario">
                ⌕ Buscar
            </button>

            <a href="listarsensor.php" class="botao botao-voltar inline-style-1">
                Limpar filtros
            </a>

        </form>

    </section>


    <!-- SENSORES CADASTRADOS -->

    <section class="card">

        <div class="card-header">

            <div>
                <h3>Sensores cadastrados</h3>
                <p>Informações dos dispositivos registrados no Ferrorama.</p>
            </div>

            <div class="card-icon">

                <svg viewBox="0 0 24 24">
                    <rect x="4" y="3" width="16" height="18" rx="2"/>
                    <path d="M8 8h8"/>
                    <path d="M8 12h8"/>
                    <path d="M8 16h5"/>
                </svg>

            </div>

        </div>

        <div class="tabela-wrap">

            <table>

                <thead>

                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Localização</th>
                        <th>Tipo de dado</th>

                        <?php if ($ehAdministrador): ?>
                            <th>Ações</th>
                        <?php endif; ?>

                    </tr>

                </thead>

                <tbody>

                    <?php if (empty($sensores)): ?>

                        <tr>

                            <td colspan="<?= $ehAdministrador ? 5 : 4 ?>">

                                <div class="vazio">

                                    <div class="vazio-icon">

                                        <svg viewBox="0 0 24 24">
                                            <rect x="4" y="3" width="16" height="18" rx="2"/>
                                            <path d="M8 8h8"/>
                                            <path d="M8 12h8"/>
                                            <path d="M8 16h5"/>
                                        </svg>

                                    </div>

                                    <strong>Nenhum sensor encontrado.</strong>

                                    <p>
                                        Os sensores cadastrados aparecerão nesta lista.
                                    </p>

                                </div>

                            </td>

                        </tr>

                    <?php else: ?>

                        <?php foreach ($sensores as $sensor): ?>

                            <tr>

                                <td class="id">
                                    #<?= (int) $sensor["id"] ?>
                                </td>

                                <td class="nome-sensor">
                                    <?= escapar($sensor["nome"]) ?>
                                </td>

                                <td>
                                    <?= escapar($sensor["localizacao"]) ?>
                                </td>

                                <td>
                                    <?= escapar($sensor["tipo_dado"]) ?>
                                </td>

                                <?php if ($ehAdministrador): ?>

                                    <td>

                                        <div class="acoes">

                                            <a
                                                href="editar_sensor.php?id=<?= (int) $sensor["id"] ?>"
                                                class="botao botao-editar"
                                            >
                                                Editar
                                            </a>

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


</main>


<!-- FOOTER -->

<footer>
    Ferrorama © <?= date("Y") ?> · Sistema de gerenciamento ferroviário.
</footer>


</body>
</html>
