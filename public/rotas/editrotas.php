
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

    

<link rel="stylesheet" href="../../style/style.css">
</head>

<body class="page-public_rotas_editrotas_php">

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

                        <span class="form-help inline-style-1">
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
