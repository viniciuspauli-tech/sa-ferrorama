
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

    
<link rel="stylesheet" href="../../style/style.css">
</head>

<body class="page-public_trem_cadastrotrem_php">

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
