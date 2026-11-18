
<?php
require_once __DIR__ . "/../infra/auth.php";

$nomeUsuario = htmlspecialchars(
    $_SESSION["usuario_nome"] ?? "Usuário",
    ENT_QUOTES,
    "UTF-8"
);

$ehAdministrador =
    ($_SESSION["usuario_perfil"] ?? "") === "administrador";
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Ferrorama | Painel</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="../style/style.css?v=3">
</head>

<body class="page-public_sistema_php">

    <header class="topbar">

        <a class="brand" href="sistema.php">
            FERRO<span>RAMA</span>
        </a>

        <div class="top-actions">

            <span>Olá, <?= $nomeUsuario ?></span>

            <a href="home.php">Meu perfil</a>

            <?php if ($ehAdministrador): ?>
                <a href="usuarios/pagina_adm.php">
                    Administração
                </a>
            <?php endif; ?>

            <a class="logout" href="logout.php">
                Sair
            </a>

        </div>

    </header>

    <section class="hero">

        <div class="hero-inner">

            <div class="eyebrow">
                PAINEL OPERACIONAL
            </div>

            <h1>Bem-vindo ao Ferrorama</h1>

            <p>
                Acompanhe informações ferroviárias, gerencie sensores
                e consulte dados importantes para apoiar uma operação
                mais segura e eficiente.
            </p>

        </div>

    </section>

    <main class="main">

        <h2 class="section-title">
            Condições meteorológicas
        </h2>

        <section class="weather">

            <div class="weather-main">

                <small id="weather-location">
                    Consultando localização...
                </small>

                <div class="temperature" id="temperature">
                    --°C
                </div>

                <p id="condition">
                    Carregando condições atuais
                </p>

            </div>

            <div class="weather-details">

                <div class="metric">
                    <span>SENSAÇÃO TÉRMICA</span>
                    <strong id="feels">--</strong>
                </div>

                <div class="metric">
                    <span>UMIDADE</span>
                    <strong id="humidity">--</strong>
                </div>

                <div class="metric">
                    <span>VENTO</span>
                    <strong id="wind">--</strong>
                </div>

                <div class="metric">
                    <span>PRECIPITAÇÃO</span>
                    <strong id="rain">--</strong>
                </div>

            </div>

            <div class="weather-footer" id="weather-updated">
                Os dados são consultados pela API Open-Meteo.
            </div>

        </section>

        <h2 class="section-title">
            Funcionalidades do sistema
        </h2>

        <section class="grid">

            <article class="tile">

                <div class="symbol">◈</div>

                <h3>Sensores</h3>

                <p>
                    <?= $ehAdministrador
                        ? "Cadastre, edite e consulte os sensores utilizados no monitoramento ferroviário."
                        : "Consulte os sensores instalados nos trilhos e o que cada um monitora." ?>
                </p>

                <a href="<?= $ehAdministrador ? "cadastro_item.php" : "listarsensor.php" ?>">
                    <?= $ehAdministrador ? "Acessar sensores →" : "Visualizar sensores →" ?>
                </a>

            </article>

            <article class="tile">

                <div class="symbol">▰</div>

                <h3>Trens</h3>

                <p>
                    <?= $ehAdministrador
                        ? "Gerencie os trens cadastrados e consulte suas informações."
                        : "Consulte os trens cadastrados e suas informações." ?>
                </p>

                <a href="trem/listagemtrem.php">
                    <?= $ehAdministrador ? "Acessar trens →" : "Visualizar trens →" ?>
                </a>

            </article>

            <article class="tile">

                <div class="symbol">⌁</div>

                <h3>Rotas</h3>

                <p>
                    <?= $ehAdministrador
                        ? "Organize e acompanhe os trajetos ferroviários."
                        : "Consulte os trajetos ferroviários cadastrados." ?>
                </p>

                <a href="rotas/rotas.php">
                    <?= $ehAdministrador ? "Acessar rotas →" : "Visualizar rotas →" ?>
                </a>

            </article>

            <article class="tile">

                <div class="symbol">◎</div>

                <h3>Meu perfil</h3>

                <p>
                    Consulte as informações da sua conta.
                </p>

                <a href="home.php">
                    Visualizar perfil →
                </a>

            </article>

            <?php if ($ehAdministrador): ?>

                <article class="tile">

                    <div class="symbol">♙</div>

                    <h3>Usuários</h3>

                    <p>
                        Gerencie as contas e permissões de acesso.
                    </p>

                    <a href="usuarios/pagina_adm.php">
                        Administrar usuários →
                    </a>

                </article>

            <?php endif; ?>

        </section>

    </main>

    <footer>
        Ferrorama · Sistema de monitoramento ferroviário
    </footer>

    <script>
        async function carregarClima() {

            const mensagem = document.getElementById("weather-updated");

            try {

                const resposta = await fetch("api/clima.php", {
                    cache: "no-store"
                });

                const dados = await resposta.json();

                if (!resposta.ok) {
                    throw new Error(
                        dados.erro || "Erro ao consultar o clima."
                    );
                }

                document.getElementById("weather-location")
                    .textContent = dados.localizacao;

                document.getElementById("temperature")
                    .textContent = Number(dados.temperatura).toFixed(1) + "°C";

                document.getElementById("condition")
                    .textContent = dados.condicao;

                document.getElementById("feels")
                    .textContent = Number(dados.sensacao).toFixed(1) + "°C";

                document.getElementById("humidity")
                    .textContent = dados.umidade + "%";

                document.getElementById("wind")
                    .textContent = dados.vento + " km/h";

                document.getElementById("rain")
                    .textContent = dados.precipitacao + " mm";

                const hora = dados.atualizado_em
                    ? new Date(dados.atualizado_em).toLocaleString("pt-BR")
                    : new Date().toLocaleString("pt-BR");

                mensagem.textContent =
                    "Última atualização: " + hora +
                    " | Fonte: Open-Meteo";

            } catch (erro) {

                mensagem.textContent = erro.message;

                document.getElementById("condition")
                    .textContent = "Clima indisponível";

            }
        }

        carregarClima();
    </script>

</body>
</html>
