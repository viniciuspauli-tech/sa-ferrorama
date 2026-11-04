
<?php

require_once "../../infra/adm.php";
require_once "../../infra/connect.php";

$erros = [];

// ID DA ROTA
$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT)
    ?: filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);

if (!$id) {
    header("Location: rotas.php?erro=" . urlencode("Rota inválida."));
    exit;
}

// FUNÇÃO DE SEGURANÇA
function e($valor) {
    return htmlspecialchars(
        (string)($valor ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}

// BUSCAR TRENS
$trens = [];

$resTrens = $conn->query("
    SELECT id, modelo
    FROM trens
    ORDER BY modelo
");

if ($resTrens) {
    $trens = $resTrens->fetch_all(MYSQLI_ASSOC);
}

// CARREGAR DADOS DA ROTA
if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    $stmt = $conn->prepare("
        SELECT nome, origem, destino, distancia_km,
               tempo_estimado, status, trem_id
        FROM rotas
        WHERE id = ?
    ");

    $stmt->bind_param("i", $id);
    $stmt->execute();

    $rota = $stmt->get_result()->fetch_assoc();

    $stmt->close();

    if (!$rota) {
        header("Location: rotas.php?erro=" . urlencode("Rota não encontrada."));
        exit;
    }

    $nome = $rota["nome"];
    $origem = $rota["origem"];
    $destino = $rota["destino"];
    $distancia = $rota["distancia_km"];
    $tempo = $rota["tempo_estimado"];
    $status = $rota["status"];
    $trem_id = (int)$rota["trem_id"];

} else {

    // RECEBER DADOS DO FORMULÁRIO
    $nome = trim($_POST["nome"] ?? "");
    $origem = trim($_POST["origem"] ?? "");
    $destino = trim($_POST["destino"] ?? "");
    $distancia = trim($_POST["distancia_km"] ?? "");
    $tempo = trim($_POST["tempo_estimado"] ?? "");
    $status = $_POST["status"] ?? "ativa";
    $trem_id = filter_input(INPUT_POST, "trem_id", FILTER_VALIDATE_INT);

    // VALIDAÇÕES

    if ($nome === "") {
        $erros[] = "Informe o nome da rota.";
    }

    if ($origem === "") {
        $erros[] = "Informe a origem.";
    }

    if ($destino === "") {
        $erros[] = "Informe o destino.";
    }

    if ($distancia === "" || !is_numeric($distancia) || $distancia <= 0) {
        $erros[] = "Informe uma distância válida.";
    }

    if ($tempo === "") {
        $erros[] = "Informe o tempo estimado.";
    }

    $statusValidos = ["ativa", "inativa", "manutencao"];

    if (!in_array($status, $statusValidos, true)) {
        $erros[] = "Status inválido.";
    }

    // VALIDAR TREM

    if (!$trem_id) {

        $erros[] = "Selecione um trem.";

    } else {

        $check = $conn->prepare("
            SELECT id
            FROM trens
            WHERE id = ?
        ");

        $check->bind_param("i", $trem_id);
        $check->execute();

        if ($check->get_result()->num_rows === 0) {
            $erros[] = "O trem selecionado não existe.";
        }

        $check->close();
    }

    // ATUALIZAR ROTA

    if (empty($erros)) {

        $sql = "
            UPDATE rotas
            SET nome = ?,
                origem = ?,
                destino = ?,
                distancia_km = ?,
                tempo_estimado = ?,
                status = ?,
                trem_id = ?
            WHERE id = ?
        ";

        try {

            $stmt = $conn->prepare($sql);

            $distancia = (float)$distancia;

            $stmt->bind_param(
                "sssdssii",
                $nome,
                $origem,
                $destino,
                $distancia,
                $tempo,
                $status,
                $trem_id,
                $id
            );

            $stmt->execute();
            $stmt->close();

            header(
                "Location: rotas.php?sucesso="
                . urlencode("Rota atualizada com sucesso!")
            );

            exit;

        } catch (mysqli_sql_exception $e) {

            error_log($e->getMessage());

            $erros[] = "Não foi possível atualizar a rota. Verifique os dados.";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Editar Rota | Ferrorama</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

    <style>

        :root {
            --primary: #6554e8;
            --primary-hover: #5140d4;
            --navy: #0c1929;
            --navy-light: #14263b;
            --background: #f3f5f9;
            --white: #ffffff;
            --text: #142033;
            --muted: #778397;
            --border: #e1e6ef;
            --danger: #c62828;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            font-family: 'Roboto', sans-serif;
            background: var(--background);
            color: var(--text);
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        /* CABEÇALHO */

        .topbar {
            min-height: 76px;
            padding: 0 7%;
            background: var(--navy);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            border-bottom: 1px solid rgba(255,255,255,.08);
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            color: white;
        }

        .brand-icon {
            width: 43px;
            height: 43px;
            border-radius: 13px;
            background: white;
            color: #0d009c;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .brand-icon svg {
            width: 26px;
            height: 26px;
        }

        .brand-name {
            font-size: 21px;
            font-weight: 800;
            letter-spacing: -.5px;
        }

        .brand-subtitle {
            display: block;
            color: #aebbd0;
            font-size: 12px;
            margin-top: 4px;
        }

        .topbar-label {
            color: #cbd5e1;
            font-size: 13px;
        }

        /* CONTEÚDO */

        .main {
            max-width: 1000px;
            margin: 0 auto;
            padding: 42px 24px 60px;
        }

        .page-heading {
            margin-bottom: 28px;
        }

        .eyebrow {
            display: flex;
            align-items: center;
            gap: 9px;
            margin-bottom: 12px;
            color: var(--primary);
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }

        .eyebrow::before {
            content: "";
            width: 20px;
            height: 2px;
            background: var(--primary);
            border-radius: 5px;
        }

        .page-heading h1 {
            margin-bottom: 9px;
            font-size: 30px;
            font-weight: 800;
            letter-spacing: -.8px;
            color: var(--navy);
        }

        .page-heading p {
            color: var(--muted);
            font-size: 14px;
            line-height: 1.7;
        }

        /* BOTÃO VOLTAR */

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            margin-bottom: 22px;
            padding: 10px 15px;
            background: white;
            border: 1px solid var(--border);
            border-radius: 9px;
            color: var(--text);
            font-size: 13px;
            font-weight: 700;
            transition: .2s;
        }

        .back-button:hover {
            color: var(--primary);
            border-color: #c9c2ff;
            background: #fafaff;
        }

        /* FORMULÁRIO */

        .form-card {
            background: white;
            border: 1px solid var(--border);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(18,32,56,.035);
        }

        .form-header {
            padding: 23px 28px;
            border-bottom: 1px solid var(--border);
            background: #ffffff;
        }

        .form-header h2 {
            font-size: 17px;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .form-header p {
            color: var(--muted);
            font-size: 13px;
            line-height: 1.5;
        }

        .form-body {
            padding: 28px;
        }

        .form-group {
            margin-bottom: 21px;
        }

        .form-group label {
            display: block;
            margin-bottom: 9px;
            color: #344258;
            font-size: 13px;
            font-weight: 700;
        }

        .form-control {
            width: 100%;
            min-height: 46px;
            padding: 12px 14px;
            background: #f9fafc;
            border: 1px solid var(--border);
            border-radius: 9px;
            outline: none;
            color: var(--text);
            font-family: inherit;
            font-size: 13px;
            transition: .2s;
        }

        .form-control:focus {
            background: white;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(101,84,232,.10);
        }

        .form-control::placeholder {
            color: #a1aabd;
        }

        select.form-control {
            cursor: pointer;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        .form-help {
            display: block;
            margin-top: 7px;
            color: var(--muted);
            font-size: 11px;
            line-height: 1.5;
        }

        /* ALERTAS */

        .alert {
            margin-bottom: 22px;
            padding: 15px 18px;
            border-radius: 10px;
            font-size: 13px;
            line-height: 1.6;
        }

        .alert-danger {
            color: #a51d2d;
            background: #fff0f1;
            border: 1px solid #f4cbd0;
        }

        .alert-danger ul {
            padding-left: 20px;
        }

        /* AÇÕES */

        .form-actions {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 10px;
            padding-top: 22px;
            border-top: 1px solid var(--border);
        }

        .button {
            min-height: 45px;
            padding: 0 21px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            border: 1px solid transparent;
            border-radius: 9px;
            font-family: inherit;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            transition: .2s ease;
        }

        .button-primary {
            color: white;
            background: var(--primary);
            box-shadow: 0 5px 12px rgba(101,84,232,.15);
        }

        .button-primary:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
        }

        .button-secondary {
            color: var(--text);
            background: white;
            border-color: var(--border);
        }

        .button-secondary:hover {
            color: var(--primary);
            border-color: #c9c2ff;
        }

        /* RODAPÉ */

        .footer {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px 24px 28px;
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            gap: 15px;
            color: var(--muted);
            font-size: 12px;
        }

        .footer strong {
            color: #526078;
        }

        /* RESPONSIVIDADE */

        @media (max-width: 650px) {

            .topbar {
                padding: 0 20px;
            }

            .brand-name {
                font-size: 19px;
            }

            .brand-subtitle {
                font-size: 10px;
            }

            .topbar-label {
                display: none;
            }

            .main {
                padding: 30px 17px 40px;
            }

            .page-heading h1 {
                font-size: 25px;
            }

            .form-header {
                padding: 21px;
            }

            .form-body {
                padding: 21px;
            }

            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }

            .form-actions {
                flex-direction: column;
            }

            .button {
                width: 100%;
            }

            .footer {
                padding: 20px;
                flex-direction: column;
                line-height: 1.6;
            }
        }

    </style>

</head>

<body>

<!-- CABEÇALHO -->

<header class="topbar">

    <a href="../sistema.php" class="brand">

        <span class="brand-icon">

            <svg viewBox="0 0 24 24"
                 fill="none"
                 stroke="currentColor"
                 stroke-width="1.8"
                 stroke-linecap="round"
                 stroke-linejoin="round"
                 aria-hidden="true">

                <rect x="5" y="3" width="14" height="15" rx="4"></rect>
                <path d="M8 7h8M8 12h8M8 18l-2 3M16 18l2 3M5 15h14"></path>
                <circle cx="9" cy="15" r="1"></circle>
                <circle cx="15" cy="15" r="1"></circle>

            </svg>

        </span>

        <span>
            <span class="brand-name">Ferrorama</span>
            <span class="brand-subtitle">Sistema de Monitoramento Ferroviário</span>
        </span>

    </a>

    <span class="topbar-label">
        Área administrativa
    </span>

</header>

<!-- CONTEÚDO PRINCIPAL -->

<main class="main">

    <a href="rotas.php" class="back-button">
        ← Voltar para rotas
    </a>

    <div class="page-heading">

        <div class="eyebrow">
            GERENCIAMENTO FERROVIÁRIO
        </div>

        <h1>Editar Rota</h1>

        <p>
            Atualize as informações da rota, altere seu trajeto
            e selecione o trem responsável.
        </p>

    </div>

    <!-- ERROS -->

    <?php if (!empty($erros)): ?>

        <div class="alert alert-danger">

            <strong>Não foi possível salvar as alterações:</strong>

            <ul>
                <?php foreach ($erros as $erro): ?>
                    <li><?= e($erro) ?></li>
                <?php endforeach; ?>
            </ul>

        </div>

    <?php endif; ?>

    <!-- FORMULÁRIO -->

    <section class="form-card">

        <div class="form-header">

            <h2>Informações da rota</h2>

            <p>
                Altere os campos necessários e salve as modificações.
            </p>

        </div>

        <div class="form-body">

            <form method="POST">

                <input
                    type="hidden"
                    name="id"
                    value="<?= (int)$id ?>"
                >

                <!-- NOME -->

                <div class="form-group">

                    <label for="nome">
                        Nome da rota
                    </label>

                    <input
                        type="text"
                        id="nome"
                        name="nome"
                        class="form-control"
                        maxlength="100"
                        placeholder="Ex.: Rota Norte"
                        value="<?= e($nome) ?>"
                        required
                    >

                </div>

                <!-- ORIGEM E DESTINO -->

                <div class="form-row">

                    <div class="form-group">

                        <label for="origem">
                            Origem
                        </label>

                        <input
                            type="text"
                            id="origem"
                            name="origem"
                            class="form-control"
                            maxlength="150"
                            placeholder="Local de origem"
                            value="<?= e($origem) ?>"
                            required
                        >

                    </div>

                    <div class="form-group">

                        <label for="destino">
                            Destino
                        </label>

                        <input
                            type="text"
                            id="destino"
                            name="destino"
                            class="form-control"
                            maxlength="150"
                            placeholder="Local de destino"
                            value="<?= e($destino) ?>"
                            required
                        >

                    </div>

                </div>

                <!-- DISTÂNCIA E TEMPO -->

                <div class="form-row">

                    <div class="form-group">

                        <label for="distancia_km">
                            Distância (km)
                        </label>

                        <input
                            type="number"
                            id="distancia_km"
                            name="distancia_km"
                            class="form-control"
                            step="0.01"
                            min="0.01"
                            placeholder="Ex.: 150.50"
                            value="<?= e($distancia) ?>"
                            required
                        >

                    </div>

                    <div class="form-group">

                        <label for="tempo_estimado">
                            Tempo estimado
                        </label>

                        <input
                            type="text"
                            id="tempo_estimado"
                            name="tempo_estimado"
                            class="form-control"
                            placeholder="Ex.: 02:30:00"
                            maxlength="20"
                            value="<?= e($tempo) ?>"
                            required
                        >

                        <span class="form-help">
                            Informe o tempo no formato horas:minutos:segundos.
                        </span>

                    </div>

                </div>

                <!-- TREM -->

                <div class="form-group">

                    <label for="trem_id">
                        Trem vinculado
                    </label>

                    <select
                        id="trem_id"
                        name="trem_id"
                        class="form-control"
                        required
                    >

                        <option value="">
                            Selecione um trem
                        </option>

                        <?php foreach ($trens as $trem): ?>

                            <option
                                value="<?= (int)$trem["id"] ?>"
                                <?= (int)$trem_id === (int)$trem["id"] ? "selected" : "" ?>
                            >
                                <?= e($trem["modelo"]) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                    <?php if (empty($trens)): ?>

                        <span class="form-help" style="color: #dc414b;">
                            Nenhum trem cadastrado. Cadastre um trem antes de editar rotas.
                        </span>

                    <?php else: ?>

                        <span class="form-help">
                            Selecione o trem responsável por realizar esta rota.
                        </span>

                    <?php endif; ?>

                </div>

                <!-- STATUS -->

                <div class="form-group">

                    <label for="status">
                        Status da rota
                    </label>

                    <select
                        id="status"
                        name="status"
                        class="form-control"
                        required
                    >

                        <option
                            value="ativa"
                            <?= $status === "ativa" ? "selected" : "" ?>
                        >
                            Ativa
                        </option>

                        <option
                            value="inativa"
                            <?= $status === "inativa" ? "selected" : "" ?>
                        >
                            Inativa
                        </option>

                        <option
                            value="manutencao"
                            <?= $status === "manutencao" ? "selected" : "" ?>
                        >
                            Em manutenção
                        </option>

                    </select>

                </div>

                <!-- BOTÕES -->

                <div class="form-actions">

                    <button
                        type="submit"
                        class="button button-primary"
                    >
                        ✓ Salvar alterações
                    </button>

                    <a
                        href="rotas.php"
                        class="button button-secondary"
                    >
                        Cancelar
                    </a>

                </div>

            </form>

        </div>

    </section>

</main>

<!-- RODAPÉ -->

<footer class="footer">

    <span>
        <strong>Ferrorama</strong> · Sistema de gerenciamento ferroviário
    </span>

    <span>
        Área administrativa
    </span>

</footer>

</body>
</html>
