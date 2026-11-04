
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

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
          rel="stylesheet">

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Inter, Arial, sans-serif;
            background: #f3f5fa;
            color: #17243a;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        .topbar {
            background: #0b1728;
            color: white;
            padding: 20px max(24px, calc((100% - 1160px) / 2));
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
        }

        .brand {
            font-size: 21px;
            font-weight: 800;
        }

        .brand span {
            color: #a99fff;
        }

        .top-actions {
            display: flex;
            align-items: center;
            gap: 16px;
            font-size: 13px;
            flex-wrap: wrap;
        }

        .logout {
            border: 1px solid #53627a;
            padding: 9px 13px;
            border-radius: 9px;
        }

        .hero {
            background:
                radial-gradient(circle at 82% 20%, #5144a855, transparent 35%),
                linear-gradient(115deg, #0b1728, #14243b);
            color: white;
            padding: 48px 24px 58px;
        }

        .hero-inner,
        .main {
            max-width: 1160px;
            margin: auto;
        }

        .eyebrow {
            color: #bcb5ff;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 1.6px;
            margin-bottom: 14px;
        }

        .hero h1 {
            font-size: clamp(28px, 4vw, 40px);
            margin-bottom: 12px;
        }

        .hero p {
            max-width: 650px;
            color: #c7d2e2;
            line-height: 1.7;
            font-size: 14px;
        }

        .main {
            padding: 30px 24px 60px;
        }

        .section-title {
            font-size: 18px;
            margin-bottom: 16px;
        }

        /* CLIMA */

        .weather {
            display: grid;
            grid-template-columns: 1.2fr 2fr;
            gap: 20px;
            background: white;
            border: 1px solid #e4e9f1;
            border-radius: 18px;
            padding: 24px;
            margin-bottom: 28px;
        }

        .weather-main {
            background: linear-gradient(135deg, #171b48, #343078);
            border-radius: 14px;
            padding: 22px;
            color: white;
        }

        .weather-main small {
            color: #c6c3ff;
        }

        .temperature {
            font-size: 48px;
            font-weight: 800;
            margin: 12px 0 3px;
        }

        .weather-main p {
            color: #d6d7f0;
            font-size: 13px;
        }

        .weather-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            align-content: center;
        }

        .metric {
            padding: 16px;
            border: 1px solid #e4e9f1;
            border-radius: 12px;
        }

        .metric span {
            display: block;
            color: #718096;
            font-size: 11px;
            margin-bottom: 8px;
        }

        .metric strong {
            font-size: 15px;
        }

        .weather-footer {
            grid-column: 1 / -1;
            color: #718096;
            font-size: 11px;
        }

        /* FUNCIONALIDADES */

        .grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 30px;
        }

        .tile {
            background: white;
            border: 1px solid #e4e9f1;
            border-radius: 15px;
            padding: 21px;
            transition: .2s;
        }

        .tile:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px #17243a0d;
        }

        .symbol {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            background: #efedff;
            color: #6554e8;
            display: grid;
            place-items: center;
            font-size: 20px;
            margin-bottom: 15px;
        }

        .tile h3 {
            font-size: 15px;
            margin-bottom: 7px;
        }

        .tile p {
            font-size: 12px;
            color: #718096;
            line-height: 1.6;
            margin-bottom: 15px;
        }

        .tile a {
            font-size: 12px;
            font-weight: 700;
            color: #6554e8;
        }

        /* CONSULTA CEP */

        .cep-card {
            background: white;
            border: 1px solid #e4e9f1;
            border-radius: 16px;
            padding: 24px;
        }

        .cep-card > p {
            font-size: 13px;
            color: #718096;
            margin: 6px 0 18px;
        }

        .cep-form {
            display: grid;
            grid-template-columns: 220px auto;
            gap: 10px;
            max-width: 500px;
        }

        .cep-form input,
        .address-grid input {
            width: 100%;
            padding: 12px;
            border: 1px solid #d9e0eb;
            border-radius: 9px;
            font: inherit;
            font-size: 13px;
        }

        .cep-form button {
            border: 0;
            border-radius: 9px;
            background: #6554e8;
            color: white;
            font-weight: 700;
            padding: 0 18px;
            cursor: pointer;
        }

        .address-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 10px;
            margin-top: 14px;
        }

        .address-grid input {
            background: #f8f9fc;
        }

        .message {
            font-size: 12px;
            margin-top: 12px;
            min-height: 18px;
        }

        .error {
            color: #b42332;
        }

        .success {
            color: #167447;
        }

        footer {
            text-align: center;
            color: #8490a3;
            font-size: 11px;
            padding: 22px;
        }

        @media(max-width: 760px) {
            .weather {
                grid-template-columns: 1fr;
            }

            .grid {
                grid-template-columns: 1fr 1fr;
            }

            .address-grid {
                grid-template-columns: 1fr 1fr;
            }

            .cep-form {
                grid-template-columns: 1fr;
            }
        }

        @media(max-width: 480px) {
            .grid {
                grid-template-columns: 1fr;
            }

            .topbar {
                flex-direction: column;
                align-items: flex-start;
            }

            .address-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>






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

        <div class="eyebrow">PAINEL OPERACIONAL</div>

        <h1>Bem-vindo ao Ferrorama</h1>

        <p>
            Acompanhe informações ferroviárias, gerencie sensores
            e consulte dados importantes para apoiar uma operação
            mais segura e eficiente.
        </p>

    </div>

</section>

<main class="main">

    <!-- CLIMA -->

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

    <!-- FUNCIONALIDADES -->

    <h2 class="section-title">
        Funcionalidades do sistema
    </h2>

    <section class="grid">

   <?php if ($ehAdministrador): ?>

<article class="tile">
    <div class="symbol">◈</div>

    <h3>Sensores</h3>

    <p>
        Cadastre, edite e consulte os sensores utilizados
        no monitoramento ferroviário.
    </p>

    <a href="cadastro_item.php">
        Acessar sensores →
    </a>
</article>

<?php endif; ?>

        <article class="tile">
            <div class="symbol">▰</div>
            <h3>Trens</h3>
            <p>
                Gerencie os trens cadastrados e consulte
                suas informações.
            </p>
            <a href="trens.php">
                Acessar trens →
            </a>
        </article>

        <article class="tile">
            <div class="symbol">⌁</div>
            <h3>Rotas</h3>
            <p>
                Organize e acompanhe os trajetos ferroviários.
            </p>
            <a href="rotas/rotas.php">
                Acessar rotas →
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

    <!-- CEP -->

    <section class="cep-card">

        <h2 class="section-title">
            Consulta de endereço por CEP
        </h2>

        <p>
            Informe um CEP brasileiro para consultar
            o endereço automaticamente pelo ViaCEP.
        </p>

        <form class="cep-form" id="cep-form">

            <input
                id="cep"
                name="cep"
                inputmode="numeric"
                maxlength="9"
                placeholder="Ex.: 89201-000"
                required
            >

            <button type="submit">
                Consultar CEP
            </button>

        </form>

        <div class="message" id="cep-message"></div>

        <div class="address-grid">

            <input id="logradouro" placeholder="Logradouro" readonly>

            <input id="bairro" placeholder="Bairro" readonly>

            <input id="cidade" placeholder="Cidade" readonly>

            <input id="uf" placeholder="UF" readonly>

        </div>

    </section>

</main>

<footer>
    Ferrorama · Sistema de monitoramento ferroviário
</footer>

<script>

// ===================================
// CONSULTA DE CLIMA
// ===================================

async function carregarClima() {

    const mensagem =
        document.getElementById("weather-updated");

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
            .textContent =
            Number(dados.temperatura).toFixed(1) + "°C";

        document.getElementById("condition")
            .textContent = dados.condicao;

        document.getElementById("feels")
            .textContent =
            Number(dados.sensacao).toFixed(1) + "°C";

        document.getElementById("humidity")
            .textContent = dados.umidade + "%";

        document.getElementById("wind")
            .textContent = dados.vento + " km/h";

        document.getElementById("rain")
            .textContent = dados.precipitacao + " mm";

        const hora = dados.atualizado_em
            ? new Date(dados.atualizado_em)
                .toLocaleString("pt-BR")
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


// ===================================
// MÁSCARA DO CEP
// ===================================

document.getElementById("cep")
    .addEventListener("input", function() {

        let valor = this.value.replace(/\D/g, "").slice(0, 8);

        if (valor.length > 5) {
            valor =
                valor.substring(0, 5) +
                "-" +
                valor.substring(5);
        }

        this.value = valor;

    });


// ===================================
// CONSULTA DE CEP
// ===================================

document.getElementById("cep-form")
    .addEventListener("submit", async function(event) {

        event.preventDefault();

        const cep = document.getElementById("cep")
            .value.replace(/\D/g, "");

        const mensagem =
            document.getElementById("cep-message");

        mensagem.className = "message";
        mensagem.textContent = "Consultando ViaCEP...";

        const campos = [
            "logradouro",
            "bairro",
            "cidade",
            "uf"
        ];

        campos.forEach(function(campo) {
            document.getElementById(campo).value = "";
        });

        try {

            const resposta = await fetch(
                "api/cep.php?cep=" + encodeURIComponent(cep)
            );

            const dados = await resposta.json();

            if (!resposta.ok) {
                throw new Error(
                    dados.erro || "Erro na consulta."
                );
            }

            document.getElementById("logradouro").value =
                dados.logradouro;

            document.getElementById("bairro").value =
                dados.bairro;

            document.getElementById("cidade").value =
                dados.cidade;

            document.getElementById("uf").value =
                dados.uf;

            mensagem.className = "message success";

            mensagem.textContent =
                "Endereço encontrado com sucesso!";

        } catch (erro) {

            mensagem.className = "message error";

            mensagem.textContent = erro.message;

        }

    });

</script>

</body>
</html>
