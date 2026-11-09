
<?php

require_once __DIR__ . "/../infra/adm.php";
require_once __DIR__ . "/../infra/connect.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function escapar($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, "UTF-8");
}

$erro = "";
$sucesso = "";

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    header("Location: cadastro_item.php");
    exit;
}

// BUSCAR SENSOR
$stmt = $conn->prepare(
    "SELECT id, nome, localizacao, tipo_dado
     FROM sensores
     WHERE id = ?"
);

$stmt->bind_param("i", $id);
$stmt->execute();

$resultado = $stmt->get_result();
$sensor = $resultado->fetch_assoc();

$stmt->close();

if (!$sensor) {
    header("Location: cadastro_item.php?erro=nao_encontrado");
    exit;
}

// DADOS INICIAIS
$nome = $sensor["nome"];
$localizacao = $sensor["localizacao"];
$tipoDado = $sensor["tipo_dado"];

// ATUALIZAR SENSOR
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nome = trim($_POST["nome"] ?? "");
    $localizacao = trim($_POST["localizacao"] ?? "");
    $tipoDado = trim($_POST["tipo_dado"] ?? "");

    if ($nome === "" || $localizacao === "" || $tipoDado === "") {

        $erro = "Preencha todos os campos.";

    } elseif (
        mb_strlen($nome) > 100 ||
        mb_strlen($localizacao) > 150 ||
        mb_strlen($tipoDado) > 100
    ) {

        $erro = "Um dos campos ultrapassou o tamanho permitido.";

    } else {

        try {

            $stmt = $conn->prepare(
                "UPDATE sensores
                 SET nome = ?, localizacao = ?, tipo_dado = ?
                 WHERE id = ?"
            );

            $stmt->bind_param(
                "sssi",
                $nome,
                $localizacao,
                $tipoDado,
                $id
            );

            $stmt->execute();
            $stmt->close();

            header("Location: cadastro_item.php?sucesso=atualizado");
            exit;

        } catch (mysqli_sql_exception $e) {

            error_log($e->getMessage());

            $erro = "Não foi possível atualizar o sensor. Tente novamente.";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Editar Sensor - Ferrorama</title>

    <link
        href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            font-family: "Roboto", sans-serif;
            color: #132238;
            background: #f2f4f9;
        }

        a {
            text-decoration: none;
        }

        /* CABEÇALHO */

        .topbar {
            background: #0c192b;
            border-bottom: 1px solid rgba(255,255,255,.08);
            color: white;
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
            min-width: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #ffffff;
            border-radius: 13px;
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
            color: white;
        }

        .brand-subtitle {
            margin-top: 3px;
            font-size: 11px;
            color: #b6c6e4;
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
                radial-gradient(circle at 85% 20%, rgba(101,84,232,.25), transparent 38%),
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

        .hero h1 span {
            color: #a99cff;
        }

        .hero p {
            max-width: 620px;
            color: #c7d2e7;
            font-size: 14px;
            line-height: 1.8;
        }

        /* CONTEÚDO */

        .main {
            max-width: 1068px;
            margin: auto;
            padding: 34px 24px 60px;
        }

        .page-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            margin-bottom: 24px;
        }

        .page-heading h2 {
            font-size: 20px;
        }

        .page-heading p {
            margin-top: 6px;
            color: #73819a;
            font-size: 13px;
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
        }

        .button:hover {
            transform: translateY(-1px);
        }

        .button-primary {
            color: white;
            background: #6554e8;
            box-shadow: 0 5px 12px rgba(101,84,232,.15);
        }

        .button-primary:hover {
            background: #5140d4;
        }

        .button-secondary {
            color: #132238;
            background: white;
            border-color: #dfe5ef;
        }

        .button-secondary:hover {
            color: #5140d4;
            border-color: #c9c2ff;
        }

        /* ALERTAS */

        .alert {
            margin-bottom: 20px;
            padding: 14px 16px;
            border-radius: 10px;
            font-size: 13px;
            line-height: 1.6;
        }

        .alert-error {
            color: #b52d3b;
            background: #fff0f1;
            border: 1px solid #f4d3d7;
        }

        /* CARTÃO */

        .content-card {
            overflow: hidden;
            background: white;
            border: 1px solid #dfe5ef;
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
        }

        .card-heading p {
            margin-top: 5px;
            color: #73819a;
            font-size: 12px;
        }

        .card-icon {
            width: 40px;
            height: 40px;
            display: grid;
            place-items: center;
            flex-shrink: 0;
            color: #4b3ac8;
            background: #eeecff;
            border-radius: 12px;
        }

        .card-icon svg {
            width: 21px;
            height: 21px;
        }

        .card-body {
            padding: 25px;
        }

        /* FORMULÁRIO */

        .field {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-size: 13px;
            font-weight: 700;
        }

        input {
            width: 100%;
            min-height: 45px;
            padding: 11px 13px;
            color: #132238;
            background: white;
            border: 1px solid #d8dfeb;
            border-radius: 9px;
            outline: none;
            font-family: inherit;
            font-size: 13px;
            transition: .2s;
        }

        input:focus {
            border-color: #6554e8;
            box-shadow: 0 0 0 3px rgba(101,84,232,.12);
        }

        input::placeholder {
            color: #a1adbf;
        }

        .form-actions {
            margin-top: 25px;
            padding-top: 21px;
            display: flex;
            justify-content: flex-end;
            flex-wrap: wrap;
            gap: 11px;
            border-top: 1px solid #edf0f5;
        }

        /* RODAPÉ */

        .footer {
            margin-top: 30px;
            color: #8a96aa;
            text-align: center;
            font-size: 11px;
        }

        /* RESPONSIVIDADE */

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

            .card-body {
                padding: 20px;
            }

            .form-actions {
                flex-direction: column-reverse;
            }

            .form-actions .button {
                width: 100%;
            }
        }

        @media (max-width: 480px) {

            .top-label {
                font-size: 0;
            }

            .brand-name {
                font-size: 17px;
            }

            .card-heading {
                padding: 17px;
            }
        }

    </style>

</head>

<body>

<!-- CABEÇALHO -->
<header class="topbar">

    <div class="topbar-inner">

        <a href="sistema.php" class="brand">

            <div class="brand-icon">

                <!-- LOGO PADRONIZADA DO FERRORAMA -->

                <svg viewBox="0 0 24 24"
                     fill="none"
                     xmlns="http://www.w3.org/2000/svg"
                     stroke="#3020E8"
                     stroke-width="1.8"
                     stroke-linecap="round"
                     stroke-linejoin="round">

                    <rect x="5" y="3" width="14" height="16" rx="4"/>

                    <path d="M8 7H16M8 11H16M5 15H19"/>

                    <circle cx="9" cy="15" r="1" fill="#3020E8"/>
                    <circle cx="15" cy="15" r="1" fill="#3020E8"/>

                    <path d="M8 19L6 21M16 19L18 21"/>

                </svg>

            </div>

            <div>

                <div class="brand-name">Ferrorama</div>
                <div class="brand-subtitle">Sistema ferroviário</div>

            </div>

        </a>

        <div class="top-label">

            <div class="top-avatar">

                <svg width="19" height="19"
                     viewBox="0 0 24 24"
                     fill="none"
                     stroke="currentColor"
                     stroke-width="1.7"
                     stroke-linecap="round"
                     stroke-linejoin="round">

                    <circle cx="12" cy="8" r="4"/>
                    <path d="M5 21v-2a7 7 0 0 1 14 0v2"/>

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

        <h1>Editar <span>Sensor.</span></h1>

        <p>
            Atualize as informações do sensor cadastrado
            no sistema de monitoramento ferroviário.
        </p>

    </div>

</section>

<!-- CONTEÚDO -->
<main class="main">

    <div class="page-heading">

        <div>

            <h2>Gerenciamento de sensores</h2>

            <p>
                Edite as informações do sensor selecionado.
            </p>

        </div>

        <a href="cadastro_item.php" class="button button-secondary">
            ← Voltar aos sensores
        </a>

    </div>

    <?php if ($erro !== ""): ?>

        <div class="alert alert-error" role="alert">
            <?= escapar($erro) ?>
        </div>

    <?php endif; ?>

    <!-- FORMULÁRIO -->
    <section class="content-card">

        <div class="card-heading">

            <div>

                <h3>Editar sensor #<?= (int)$id ?></h3>

                <p>
                    Altere os campos desejados e salve as modificações.
                </p>

            </div>

            <div class="card-icon">

                <svg viewBox="0 0 24 24"
                     fill="none"
                     stroke="currentColor"
                     stroke-width="1.8"
                     stroke-linecap="round"
                     stroke-linejoin="round">

                    <path d="M12 20h9"/>
                    <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4Z"/>

                </svg>

            </div>

        </div>

        <div class="card-body">

            <form method="POST"
                  action="editar_sensor.php?id=<?= (int)$id ?>">

                <div class="field">

                    <label for="nome">Nome do sensor</label>

                    <input
                        type="text"
                        id="nome"
                        name="nome"
                        maxlength="100"
                        value="<?= escapar($nome) ?>"
                        placeholder="Ex.: Sensor de temperatura"
                        required
                    >

                </div>

                <div class="field">

                    <label for="localizacao">Localização no trilho</label>

                    <input
                        type="text"
                        id="localizacao"
                        name="localizacao"
                        maxlength="150"
                        value="<?= escapar($localizacao) ?>"
                        placeholder="Ex.: KM 12 - Trecho Norte"
                        required
                    >

                </div>

                <div class="field">

                    <label for="tipo_dado">Tipo de dado</label>

                    <input
                        type="text"
                        id="tipo_dado"
                        name="tipo_dado"
                        maxlength="100"
                        value="<?= escapar($tipoDado) ?>"
                        placeholder="Ex.: Temperatura, velocidade ou pressão"
                        required
                    >

                </div>

                <div class="form-actions">

                    <a href="cadastro_item.php"
                       class="button button-secondary">
                        Voltar
                    </a>

                    <button type="submit"
                            class="button button-primary">
                        Salvar alterações →
                    </button>

                </div>

            </form>

        </div>

    </section>

    <div class="footer">

        Ferrorama © <?= date("Y") ?> — Sistema de gerenciamento ferroviário.

    </div>

</main>

</body>
</html>
