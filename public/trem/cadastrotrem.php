
<?php

require_once __DIR__ . "/../../infra/adm.php";
require_once __DIR__ . "/../../infra/connect.php";

$mensagemErro = "";

$identificador = "";
$modelo = "";
$status = "ativo";
$velocidade = "0.00";
$localizacao = "";
$consumo = "0.00";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $identificador = trim($_POST["identificador"] ?? "");
    $modelo = trim($_POST["modelo"] ?? "");
    $status = $_POST["status"] ?? "ativo";
    $velocidade = $_POST["velocidade_atual"] ?? "0";
    $localizacao = trim($_POST["localizacao_atual"] ?? "");
    $consumo = $_POST["consumo_energia"] ?? "0";

    $statusValidos = [
        "ativo",
        "inativo",
        "manutencao",
        "falha"
    ];

    if ($identificador === "" || $modelo === "") {
        $mensagemErro = "Preencha o identificador e o modelo do trem.";

    } elseif (!in_array($status, $statusValidos, true)) {
        $mensagemErro = "Selecione um status válido.";

    } elseif (
        !is_numeric($velocidade) ||
        (float) $velocidade < 0 ||
        (float) $velocidade > 9999.99
    ) {
        $mensagemErro = "Informe uma velocidade válida.";

    } elseif (
        !is_numeric($consumo) ||
        (float) $consumo < 0 ||
        (float) $consumo > 999999.99
    ) {
        $mensagemErro = "Informe um consumo de energia válido.";

    } elseif (
        strlen($identificador) > 50 ||
        strlen($modelo) > 100 ||
        strlen($localizacao) > 150
    ) {
        $mensagemErro = "Um ou mais campos ultrapassam o tamanho permitido.";

    } else {

        $velocidade = (float) $velocidade;
        $consumo = (float) $consumo;

        $localizacaoBanco = $localizacao === ""
            ? null
            : $localizacao;

        $sql = "
            INSERT INTO trens (
                identificador,
                modelo,
                status,
                velocidade_atual,
                localizacao_atual,
                consumo_energia
            )
            VALUES (?, ?, ?, ?, ?, ?)
        ";

        $stmt = $conn->prepare($sql);

        if ($stmt) {

            $stmt->bind_param(
                "sssdsd",
                $identificador,
                $modelo,
                $status,
                $velocidade,
                $localizacaoBanco,
                $consumo
            );

            try {

                $stmt->execute();
                $stmt->close();

                header("Location: listagemtrem.php?sucesso=cadastrado");
                exit;

            } catch (mysqli_sql_exception $e) {

                if ($e->getCode() === 1062) {
                    $mensagemErro =
                        "Já existe um trem com esse identificador. Escolha outro.";
                } else {
                    error_log($e->getMessage());

                    $mensagemErro =
                        "Não foi possível cadastrar o trem. Verifique os dados e tente novamente.";
                }

                $stmt->close();
            }

        } else {

            error_log($conn->error);

            $mensagemErro =
                "Não foi possível preparar o cadastro do trem.";
        }
    }
}

function escapar($valor): string
{
    return htmlspecialchars(
        (string) $valor,
        ENT_QUOTES,
        "UTF-8"
    );
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Cadastrar Trem - Ferrorama</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

    <style>
        :root {
            --navy: #0d1b2a;
            --navy-light: #172943;
            --blue: #2114d9;
            --blue-light: #f0efff;
            --violet: #9b8cff;
            --page: #f2f4f9;
            --text: #142238;
            --muted: #718096;
            --border: #dfe5ef;
            --white: #ffffff;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Roboto', sans-serif;
            color: var(--text);
            background: var(--page);
        }

        a {
            text-decoration: none;
        }

        /* Barra superior */

        .navbar-ferrorama {
            min-height: 76px;
            padding: 14px 0;
            background: var(--navy);
            border-bottom: 1px solid rgba(255, 255, 255, .08);
        }

        .navbar-content {
            width: min(1180px, calc(100% - 48px));
            margin: 0 auto;
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
            display: grid;
            place-items: center;
            width: 42px;
            height: 42px;
            border-radius: 13px;
            color: var(--blue);
            background: white;
            flex-shrink: 0;
        }

        .brand-icon svg {
            width: 25px;
            height: 25px;
        }

        .brand-name {
            margin: 0;
            font-size: 18px;
            font-weight: 800;
            letter-spacing: -.4px;
        }

        .brand-subtitle {
            margin-top: 3px;
            color: #b9c9e5;
            font-size: 12px;
        }

        .navbar-label {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #dce5f5;
            font-size: 13px;
        }

        .navbar-label svg {
            width: 19px;
            height: 19px;
            color: #a99aff;
        }

        /* Banner */

        .hero {
            position: relative;
            overflow: hidden;
            color: white;
            background:
                radial-gradient(
                    circle at 85% 15%,
                    rgba(78, 64, 185, .38),
                    transparent 38%
                ),
                linear-gradient(110deg, var(--navy), #172444);
        }

        .hero-content {
            width: min(1180px, calc(100% - 48px));
            min-height: 190px;
            margin: 0 auto;
            padding: 36px 0 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 30px;
        }

        .eyebrow {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 14px;
            color: #b8adff;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }

        .eyebrow::before {
            content: "";
            width: 20px;
            height: 2px;
            background: var(--violet);
        }

        .hero h1 {
            margin: 0 0 10px;
            font-size: clamp(28px, 4vw, 38px);
            font-weight: 800;
            letter-spacing: -1.2px;
        }

        .hero h1 span {
            color: #aa9cff;
        }

        .hero-description {
            max-width: 560px;
            margin: 0;
            color: #c6d1e4;
            font-size: 14px;
            line-height: 1.7;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            padding: 12px 17px;
            color: white;
            background: rgba(255, 255, 255, .08);
            border: 1px solid rgba(255, 255, 255, .2);
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            white-space: nowrap;
            transition: .2s ease;
        }

        .back-button:hover {
            color: white;
            background: rgba(255, 255, 255, .16);
            transform: translateY(-1px);
        }

        .back-button svg {
            width: 17px;
            height: 17px;
        }

        /* Conteúdo */

        .main-content {
            width: min(920px, calc(100% - 40px));
            margin: 0 auto;
            padding: 34px 0 64px;
        }

        .section-heading {
            margin-bottom: 20px;
        }

        .section-heading h2 {
            margin: 0 0 7px;
            font-size: 20px;
            font-weight: 800;
            letter-spacing: -.4px;
        }

        .section-heading p {
            margin: 0;
            color: var(--muted);
            font-size: 13px;
            line-height: 1.6;
        }

        .form-card {
            overflow: hidden;
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 18px;
            box-shadow: 0 8px 28px rgba(20, 34, 56, .045);
        }

        .form-card-header {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 24px 28px;
            border-bottom: 1px solid #edf0f5;
        }

        .form-icon {
            display: grid;
            place-items: center;
            width: 46px;
            height: 46px;
            flex-shrink: 0;
            color: var(--blue);
            background: var(--blue-light);
            border-radius: 14px;
        }

        .form-icon svg {
            width: 24px;
            height: 24px;
        }

        .form-card-header h3 {
            margin: 0 0 5px;
            font-size: 16px;
            font-weight: 800;
        }

        .form-card-header p {
            margin: 0;
            color: var(--muted);
            font-size: 12px;
        }

        .form-card-body {
            padding: 28px;
        }

        .form-label {
            display: block;
            margin-bottom: 9px;
            color: #24334a;
            font-size: 13px;
            font-weight: 700;
        }

        .required-mark {
            color: #6553e8;
        }

        .form-control,
        .form-select {
            min-height: 46px;
            padding: 11px 14px;
            color: var(--text);
            background-color: #fff;
            border: 1px solid #dce3ed;
            border-radius: 9px;
            font-family: inherit;
            font-size: 13px;
            box-shadow: none;
            transition: border-color .2s, box-shadow .2s;
        }

        .form-control::placeholder {
            color: #9aa7ba;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #8b7cf6;
            box-shadow: 0 0 0 4px rgba(91, 73, 225, .10);
        }

        .form-text {
            margin-top: 8px;
            color: var(--muted);
            font-size: 11px;
            line-height: 1.5;
        }

        .field-hint {
            margin-top: 7px;
            color: #8b97a9;
            font-size: 11px;
        }

        .form-divider {
            height: 1px;
            margin: 26px 0;
            background: #edf0f5;
        }

        .form-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            margin-top: 28px;
            padding-top: 22px;
            border-top: 1px solid #edf0f5;
        }

        .form-actions-note {
            display: flex;
            align-items: center;
            gap: 7px;
            color: var(--muted);
            font-size: 11px;
        }

        .form-actions-note svg {
            width: 16px;
            height: 16px;
            color: #6553e8;
        }

        .actions-buttons {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn-cancel,
        .btn-save {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 43px;
            padding: 11px 17px;
            border-radius: 9px;
            font-family: inherit;
            font-size: 13px;
            font-weight: 700;
            transition: .2s ease;
        }

        .btn-cancel {
            color: #526078;
            background: white;
            border: 1px solid #d8dfeb;
        }

        .btn-cancel:hover {
            color: var(--navy);
            background: #f5f7fb;
            border-color: #bbc6d7;
        }

        .btn-save {
            color: white;
            background: var(--blue);
            border: 1px solid var(--blue);
            box-shadow: 0 4px 10px rgba(33, 20, 217, .14);
        }

        .btn-save:hover {
            color: white;
            background: #180db8;
            border-color: #180db8;
            transform: translateY(-1px);
            box-shadow: 0 6px 15px rgba(33, 20, 217, .2);
        }

        .btn-save svg,
        .btn-cancel svg {
            width: 16px;
            height: 16px;
        }

        .alert-custom {
            display: flex;
            align-items: flex-start;
            gap: 11px;
            margin-bottom: 20px;
            padding: 15px 17px;
            border-radius: 11px;
            font-size: 13px;
            line-height: 1.6;
        }

        .alert-custom svg {
            width: 19px;
            height: 19px;
            flex-shrink: 0;
            margin-top: 1px;
        }

        .alert-error {
            color: #a62d3c;
            background: #fff0f1;
            border: 1px solid #ffd5d9;
        }

        .page-footer {
            padding: 0 0 28px;
            color: #8792a4;
            text-align: center;
            font-size: 11px;
        }

        @media (max-width: 600px) {
            .navbar-content,
            .hero-content {
                width: calc(100% - 32px);
            }

            .navbar-ferrorama {
                min-height: 68px;
            }

            .navbar-label {
                font-size: 0;
                gap: 0;
            }

            .navbar-label svg {
                width: 22px;
                height: 22px;
            }

            .hero-content {
                min-height: auto;
                align-items: flex-start;
                flex-direction: column;
                padding: 30px 0;
                gap: 20px;
            }

            .hero h1 {
                font-size: 30px;
            }

            .main-content {
                width: calc(100% - 28px);
                padding-top: 26px;
            }

            .form-card-header {
                padding: 20px;
            }

            .form-card-body {
                padding: 20px;
            }

            .form-actions {
                align-items: stretch;
                flex-direction: column;
            }

            .form-actions-note {
                order: 2;
            }

            .actions-buttons {
                display: grid;
                grid-template-columns: 1fr 1fr;
            }

            .actions-buttons > * {
                width: 100%;
            }
        }
    </style>
</head>

<body>

<header class="navbar-ferrorama">
    <div class="navbar-content">

        <a href="../sistema.php" class="brand">
            <div class="brand-icon">
                <svg viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" stroke-linejoin="round"
                    aria-hidden="true">
                    <rect x="5" y="3" width="14" height="15" rx="4"/>
                    <path d="M8 7h8M8 11h2m4 0h2M8 18l-2 3m10-3 2 3M8 15h8"/>
                </svg>
            </div>

            <div>
                <div class="brand-name">Ferrorama</div>
                <div class="brand-subtitle">Sistema ferroviário</div>
            </div>
        </a>

        <div class="navbar-label">
            <svg viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="1.8"
                stroke-linecap="round" stroke-linejoin="round"
                aria-hidden="true">
                <circle cx="12" cy="8" r="4"/>
                <path d="M5 21v-2a7 7 0 0 1 14 0v2"/>
            </svg>
            Área administrativa
        </div>

    </div>
</header>

<section class="hero">
    <div class="hero-content">

        <div>
            <div class="eyebrow">Gestão ferroviária</div>

            <h1>Cadastrar <span>trem.</span></h1>

            <p class="hero-description">
                Adicione uma nova composição ao Ferrorama e configure
                suas informações operacionais em um só lugar.
            </p>
        </div>

        <a href="listagemtrem.php" class="back-button">
            <svg viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round"
                aria-hidden="true">
                <path d="m15 18-6-6 6-6"/>
                <path d="M9 12h12"/>
            </svg>
            Voltar para trens
        </a>

    </div>
</section>

<main class="main-content">

    <div class="section-heading">
        <h2>Informações do trem</h2>
        <p>
            Preencha os dados abaixo para cadastrar uma nova composição.
            Os campos marcados com * são obrigatórios.
        </p>
    </div>

    <?php if ($mensagemErro !== ""): ?>
        <div class="alert-custom alert-error" role="alert">
            <svg viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round"
                aria-hidden="true">
                <circle cx="12" cy="12" r="10"/>
                <path d="M12 8v4m0 4h.01"/>
            </svg>

            <div><?= escapar($mensagemErro) ?></div>
        </div>
    <?php endif; ?>

    <section class="form-card">

        <div class="form-card-header">
            <div class="form-icon">
                <svg viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" stroke-linejoin="round"
                    aria-hidden="true">
                    <rect x="5" y="3" width="14" height="15" rx="4"/>
                    <path d="M8 7h8M8 11h2m4 0h2M8 15h8M8 18l-2 3m10-3 2 3"/>
                </svg>
            </div>

            <div>
                <h3>Dados cadastrais</h3>
                <p>Identificação, status e informações operacionais</p>
            </div>
        </div>

        <div class="form-card-body">

            <form method="POST" action="cadastrotrem.php">

                <div class="row g-4">

                    <div class="col-md-6">
                        <label for="identificador" class="form-label">
                            Identificador <span class="required-mark">*</span>
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="identificador"
                            name="identificador"
                            maxlength="50"
                            placeholder="Ex.: TR-005"
                            value="<?= escapar($identificador) ?>"
                            autocomplete="off"
                            required
                        >

                        <div class="field-hint">
                            Código único de identificação do trem.
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label for="modelo" class="form-label">
                            Modelo <span class="required-mark">*</span>
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="modelo"
                            name="modelo"
                            maxlength="100"
                            placeholder="Ex.: Expresso 4000"
                            value="<?= escapar($modelo) ?>"
                            required
                        >

                        <div class="field-hint">
                            Nome ou modelo da composição ferroviária.
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label for="status" class="form-label">
                            Status operacional <span class="required-mark">*</span>
                        </label>

                        <select
                            class="form-select"
                            id="status"
                            name="status"
                            required
                        >
                            <option value="ativo"
                                <?= $status === "ativo" ? "selected" : "" ?>>
                                Ativo
                            </option>

                            <option value="inativo"
                                <?= $status === "inativo" ? "selected" : "" ?>>
                                Inativo
                            </option>

                            <option value="manutencao"
                                <?= $status === "manutencao" ? "selected" : "" ?>>
                                Em manutenção
                            </option>

                            <option value="falha"
                                <?= $status === "falha" ? "selected" : "" ?>>
                                Com falha
                            </option>
                        </select>

                        <div class="field-hint">
                            Situação atual da composição.
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label for="velocidade_atual" class="form-label">
                            Velocidade atual (km/h)
                            <span class="required-mark">*</span>
                        </label>

                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            max="9999.99"
                            class="form-control"
                            id="velocidade_atual"
                            name="velocidade_atual"
                            placeholder="0,00"
                            value="<?= escapar($velocidade) ?>"
                            required
                        >

                        <div class="field-hint">
                            Velocidade atual em quilômetros por hora.
                        </div>
                    </div>

                </div>

                <div class="form-divider"></div>

                <div class="row g-4">

                    <div class="col-md-6">
                        <label for="localizacao_atual" class="form-label">
                            Localização atual
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="localizacao_atual"
                            name="localizacao_atual"
                            maxlength="150"
                            placeholder="Ex.: KM 12 - Trecho Norte"
                            value="<?= escapar($localizacao) ?>"
                        >

                        <div class="field-hint">
                            Campo opcional para indicar a posição do trem.
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label for="consumo_energia" class="form-label">
                            Consumo de energia (kWh)
                            <span class="required-mark">*</span>
                        </label>

                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            max="999999.99"
                            class="form-control"
                            id="consumo_energia"
                            name="consumo_energia"
                            placeholder="0,00"
                            value="<?= escapar($consumo) ?>"
                            required
                        >

                        <div class="field-hint">
                            Consumo de energia registrado para o trem.
                        </div>
                    </div>

                </div>

                <div class="form-actions">

                    <div class="form-actions-note">
                        <svg viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="1.8"
                            stroke-linecap="round" stroke-linejoin="round"
                            aria-hidden="true">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="M12 11v5m0-9h.01"/>
                        </svg>
                        Confira os dados antes de salvar.
                    </div>

                    <div class="actions-buttons">

                        <a href="listagemtrem.php" class="btn-cancel">
                            Voltar
                        </a>

                        <button type="submit" class="btn-save">
                            <svg viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round"
                                aria-hidden="true">
                                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2Z"/>
                                <path d="M17 21v-8H7v8M7 3v5h8"/>
                            </svg>
                            Salvar trem
                        </button>

                    </div>

                </div>

            </form>

        </div>
    </section>

</main>

<footer class="page-footer">
    Ferrorama · Sistema de gestão ferroviária
</footer>

</body>
</html>
