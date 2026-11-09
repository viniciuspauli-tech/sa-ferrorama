
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

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Roboto', sans-serif;
            background: #f1f3f9;
            color: #0d1b2a;
            min-height: 100vh;
        }

        a {
            text-decoration: none;
        }

        /* NAVBAR */

        .navbar {
            width: 100%;
            background: #0d1b2a;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }

        .navbar-content {
            max-width: 1290px;
            margin: auto;
            min-height: 70px;
            padding: 12px 0;

            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            color: white;
        }

        .logo-icon {
            width: 43px;
            height: 43px;
            border-radius: 12px;
            background: white;

            display: flex;
            align-items: center;
            justify-content: center;
        }

        .logo-icon svg {
            width: 25px;
            height: 25px;
            stroke: #3324e8;
            fill: none;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .logo-text strong {
            display: block;
            font-size: 18px;
            font-weight: 800;
        }

        .logo-text span {
            display: block;
            margin-top: 3px;
            font-size: 11px;
            color: #c4d0e2;
        }

        .usuario {
            display: flex;
            align-items: center;
            gap: 11px;
            color: white;
            font-size: 12px;
            font-weight: 600;
        }

        .usuario-icon {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #1c304b;

            display: flex;
            align-items: center;
            justify-content: center;
        }

        .usuario-icon svg {
            width: 17px;
            height: 17px;
            stroke: white;
            fill: none;
            stroke-width: 1.8;
        }

        /* HERO */

        .hero {
            background: linear-gradient(
                110deg,
                #0d1b2a 0%,
                #14213e 55%,
                #292c69 100%
            );

            color: white;
            padding: 34px 0;
        }

        .hero-content {
            max-width: 1290px;
            margin: auto;

            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 25px;
        }

        .hero-label {
            color: #a9a0ff;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 1.2px;
            margin-bottom: 15px;
        }

        .hero h1 {
            font-size: 31px;
            font-weight: 800;
            margin-bottom: 10px;
        }

        .hero h1 span {
            color: #a99aff;
        }

        .hero p {
            font-size: 14px;
            color: #d0d8e8;
            line-height: 1.6;
        }

        .hero-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        /* BOTÕES */

        .botao {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            gap: 6px;

            padding: 11px 16px;
            border-radius: 9px;

            font-family: inherit;
            font-size: 12px;
            font-weight: 700;

            border: 1px solid transparent;
            cursor: pointer;
            transition: .2s;
        }

        .botao:hover {
            transform: translateY(-2px);
        }

        .botao-voltar {
            background: rgba(255,255,255,0.08);
            border-color: rgba(255,255,255,0.25);
            color: white;
        }

        .botao-voltar:hover,
        .botao-monitoramento:hover {
            background: rgba(255,255,255,0.16);
        }

        .botao-monitoramento {
            background: rgba(255,255,255,0.08);
            border-color: rgba(255,255,255,0.25);
            color: white;
        }

        .botao-primario {
            background: #3924e8;
            color: white;
        }

        .botao-primario:hover {
            background: #2816ca;
        }

        .botao-editar {
            background: #eeedff;
            color: #3324d9;
            border: 1px solid #dcd8ff;
            padding: 8px 13px;
        }

        /* CONTEÚDO */

        main {
            max-width: 1290px;
            margin: 30px auto 0;
        }

        .secao-titulo {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
        }

        .secao-titulo h2 {
            font-size: 18px;
            font-weight: 800;
            margin-bottom: 5px;
        }

        .secao-titulo p {
            font-size: 12px;
            color: #71809b;
        }

        .contador {
            background: #eeedff;
            color: #3924e8;
            border-radius: 7px;
            padding: 7px 12px;
            font-size: 11px;
            font-weight: 700;
        }

        /* CARDS */

        .card {
            background: white;
            border: 1px solid #dfe5f0;
            border-radius: 13px;
            overflow: hidden;
            margin-bottom: 20px;

            box-shadow: 0 5px 18px rgba(16,32,58,.035);
        }

        .card-header {
            padding: 19px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e8ecf3;
        }

        .card-header h3 {
            font-size: 15px;
            font-weight: 800;
            margin-bottom: 5px;
        }

        .card-header p {
            font-size: 12px;
            color: #7886a1;
        }

        .card-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: #eeedff;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #3924e8;
        }

        .card-icon svg {
            width: 19px;
            height: 19px;
            stroke: currentColor;
            fill: none;
            stroke-width: 1.8;
        }

        /* FILTROS */

        .filtros {
            padding: 20px;
            display: flex;
            align-items: end;
            gap: 12px;
        }

        .campo {
            flex: 1;
        }

        .campo label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .campo input {
            width: 100%;
            height: 40px;
            padding: 0 13px;

            border: 1px solid #d7dfec;
            border-radius: 8px;

            font-family: inherit;
            font-size: 13px;
            color: #0d1b2a;
            outline: none;
        }

        .campo input:focus {
            border-color: #6554ed;
            box-shadow: 0 0 0 3px rgba(101,84,237,.10);
        }

        .campo input::placeholder {
            color: #9aa7bd;
        }

        /* TABELA */

        .tabela-wrap {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 750px;
        }

        thead {
            background: #f6f7fb;
        }

        th {
            padding: 14px 16px;
            text-align: left;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            color: #64728b;
            white-space: nowrap;
        }

        td {
            padding: 15px 16px;
            border-top: 1px solid #e8ecf3;
            font-size: 12px;
            color: #34435b;
        }

        tbody tr:hover {
            background: #fafbff;
        }

        .id {
            color: #8490a6;
            font-weight: 600;
        }

        .nome-sensor {
            font-weight: 700;
            color: #0d1b2a;
        }

        .acoes {
            display: flex;
            align-items: center;
            gap: 7px;
        }

        .vazio {
            padding: 55px 20px;
            text-align: center;
        }

        .vazio-icon {
            width: 48px;
            height: 48px;
            margin: 0 auto 13px;
            border-radius: 13px;
            background: #eeedff;
            color: #6554ed;

            display: flex;
            align-items: center;
            justify-content: center;
        }

        .vazio-icon svg {
            width: 23px;
            height: 23px;
            stroke: currentColor;
            fill: none;
            stroke-width: 1.8;
        }

        .vazio strong {
            display: block;
            font-size: 13px;
            margin-bottom: 7px;
        }

        .vazio p {
            font-size: 12px;
            color: #8390a8;
        }

        /* FOOTER */

        footer {
            text-align: center;
            padding: 28px 15px;
            color: #8491a9;
            font-size: 11px;
        }

        /* RESPONSIVIDADE */

        @media (max-width: 1350px) {
            .navbar-content,
            .hero-content,
            main {
                width: 94%;
            }
        }

        @media (max-width: 800px) {

            .hero-content {
                flex-direction: column;
                align-items: flex-start;
            }

            .hero-actions {
                width: 100%;
            }

            .hero h1 {
                font-size: 26px;
            }

            .filtros {
                flex-wrap: wrap;
            }

            .campo {
                flex-basis: 100%;
            }

            .filtros .botao {
                flex: 1;
            }

        }

        @media (max-width: 500px) {

            .navbar-content {
                min-height: 65px;
            }

            .usuario {
                font-size: 10px;
            }

            .hero {
                padding: 28px 0;
            }

            .hero-actions .botao {
                flex: 1;
                font-size: 10px;
                padding: 10px;
            }

            .secao-titulo {
                align-items: flex-start;
                gap: 10px;
            }

        }

    </style>

</head>

<body>

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

            <a href="listarsensor.php" class="botao botao-voltar" style="background:white;color:#172a42;border-color:#d7dfec;">
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
