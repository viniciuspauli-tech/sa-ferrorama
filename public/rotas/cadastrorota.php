
<?php

require_once "../../infra/adm.php";
require_once "../../infra/connect.php";

$erros = [];

$nome = trim($_POST["nome"] ?? "");
$origem = trim($_POST["origem"] ?? "");
$destino = trim($_POST["destino"] ?? "");
$distancia = trim($_POST["distancia_km"] ?? "");
$tempo = trim($_POST["tempo_estimado"] ?? "");
$status = $_POST["status"] ?? "ativa";
$trem_id = isset($_POST["trem_id"]) ? (int)$_POST["trem_id"] : 0;

// BUSCAR TRENS
$trens = [];

$resTrens = $conn->query("
    SELECT id, identificador, modelo
    FROM trens
    ORDER BY identificador ASC
");

if ($resTrens) {
    $trens = $resTrens->fetch_all(MYSQLI_ASSOC);
}

// PROCESSAR CADASTRO
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if ($nome === "") {
        $erros[] = "Informe o nome da rota.";
    }

    if ($origem === "") {
        $erros[] = "Informe a origem.";
    }

    if ($destino === "") {
        $erros[] = "Informe o destino.";
    }

    if ($distancia === "" || !is_numeric($distancia) || (float)$distancia <= 0) {
        $erros[] = "Informe uma distância válida.";
    }

    if ($tempo === "") {
        $erros[] = "Informe o tempo estimado.";
    }

    if (!in_array($status, ["ativa", "inativa", "manutencao"], true)) {
        $erros[] = "Status inválido.";
    }

    // VALIDAR TREM
    if ($trem_id <= 0) {

        $erros[] = "Selecione um trem.";

    } else {

        $check = $conn->prepare("SELECT id FROM trens WHERE id = ?");
        $check->bind_param("i", $trem_id);
        $check->execute();

        if ($check->get_result()->num_rows === 0) {
            $erros[] = "O trem selecionado não existe.";
        }

        $check->close();
    }

    // INSERIR ROTA
    if (empty($erros)) {

        try {

            $sql = "
                INSERT INTO rotas
                (
                    nome,
                    origem,
                    destino,
                    distancia_km,
                    tempo_estimado,
                    status,
                    trem_id
                )
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ";

            $stmt = $conn->prepare($sql);

            $distanciaFloat = (float)$distancia;

            $stmt->bind_param(
                "sssdssi",
                $nome,
                $origem,
                $destino,
                $distanciaFloat,
                $tempo,
                $status,
                $trem_id
            );

            $stmt->execute();
            $stmt->close();

            header("Location: rotas.php?sucesso=" . urlencode("Rota cadastrada com sucesso!"));
            exit;

        } catch (mysqli_sql_exception $e) {

            error_log($e->getMessage());
            $erros[] = "Não foi possível cadastrar a rota. Verifique os dados.";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Cadastrar Rota | Ferrorama</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    

<link rel="stylesheet" href="../../style/style.css">
</head>

<body class="page-public_rotas_cadastrorota_php">

    <!-- NAVBAR -->

    <header class="navbar-ferrorama">

        <div class="navbar-content">

            <a href="../sistema.php" class="brand">

                <div class="brand-icon">

                    <svg viewBox="0 0 24 24" fill="none"
                         stroke="#3026ff" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round">

                        <rect x="5" y="3" width="14" height="15" rx="4"/>
                        <path d="M8 7h8M8 12h.01M16 12h.01"/>
                        <path d="m8 18-2 3m10-3 2 3M8 15h8"/>

                    </svg>

                </div>

                <div>

                    <div class="brand-name">Ferrorama</div>

                    <div class="brand-subtitle">Sistema ferroviário</div>

                </div>

            </a>

            <div class="admin-area">

                <div class="admin-icon">

                    <svg width="19" height="19" viewBox="0 0 24 24"
                         fill="none" stroke="#e2e8f0"
                         stroke-width="1.7" stroke-linecap="round">

                        <circle cx="12" cy="8" r="4"/>
                        <path d="M5 21v-2a7 7 0 0 1 14 0v2"/>

                    </svg>

                </div>

                Área administrativa

            </div>

        </div>

    </header>


    <!-- BANNER -->

    <section class="hero">

        <div class="hero-content">

            <div class="hero-label">
                Painel de controle
            </div>

            <h1 class="hero-title">
                Cadastrar <span>Rota.</span>
            </h1>

            <p class="hero-description">
                Cadastre novos trajetos ferroviários, defina origem e destino,
                vincule trens e configure as informações operacionais das rotas.
            </p>

        </div>

    </section>


    <!-- CONTEÚDO PRINCIPAL -->

    <main class="main">

        <div class="content-header">

            <div>

                <h2 class="section-title">
                    Cadastro de rotas
                </h2>

                <p class="section-description">
                    Preencha os dados abaixo para adicionar uma nova rota ao sistema.
                </p>

            </div>

            <a href="rotas.php" class="btn-back">
                Voltar
            </a>

        </div>


        <!-- ERROS -->

        <?php if (!empty($erros)): ?>

            <div class="alert alert-danger">

                <strong>Verifique os seguintes campos:</strong>

                <ul class="mb-0 mt-2">

                    <?php foreach ($erros as $erro): ?>

                        <li>
                            <?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?>
                        </li>

                    <?php endforeach; ?>

                </ul>

            </div>

        <?php endif; ?>


        <!-- FORMULÁRIO -->

        <section class="form-card">

            <h3 class="card-title">
                Informações da rota
            </h3>

            <form method="POST">

                <div class="form-group">

                    <label class="form-label">Nome da rota</label>

                    <input
                        type="text"
                        name="nome"
                        class="form-control"
                        placeholder="Ex.: Rota Norte"
                        maxlength="100"
                        value="<?= htmlspecialchars($nome, ENT_QUOTES, 'UTF-8') ?>"
                        required>

                </div>


                <div class="form-group">

                    <label class="form-label">Origem</label>

                    <input
                        type="text"
                        name="origem"
                        class="form-control"
                        placeholder="Informe o local de origem"
                        maxlength="150"
                        value="<?= htmlspecialchars($origem, ENT_QUOTES, 'UTF-8') ?>"
                        required>

                </div>


                <div class="form-group">

                    <label class="form-label">Destino</label>

                    <input
                        type="text"
                        name="destino"
                        class="form-control"
                        placeholder="Informe o local de destino"
                        maxlength="150"
                        value="<?= htmlspecialchars($destino, ENT_QUOTES, 'UTF-8') ?>"
                        required>

                </div>


                <div class="form-row">

                    <div class="form-group">

                        <label class="form-label">Distância (km)</label>

                        <input
                            type="number"
                            name="distancia_km"
                            class="form-control"
                            placeholder="Ex.: 150.50"
                            step="0.01"
                            min="0.01"
                            value="<?= htmlspecialchars($distancia, ENT_QUOTES, 'UTF-8') ?>"
                            required>

                    </div>


                    <div class="form-group">

                        <label class="form-label">Tempo estimado</label>

                        <input
                            type="text"
                            name="tempo_estimado"
                            class="form-control"
                            placeholder="Ex.: 02:30:00"
                            maxlength="20"
                            value="<?= htmlspecialchars($tempo, ENT_QUOTES, 'UTF-8') ?>"
                            required>

                        <div class="form-hint">
                            Formato: horas:minutos:segundos.
                        </div>

                    </div>

                </div>


                <div class="form-group">

                    <label class="form-label">Trem vinculado</label>

                    <select name="trem_id" class="form-select" required>

                        <option value="">Selecione um trem</option>

                        <?php foreach ($trens as $trem): ?>

                            <option
                                value="<?= (int)$trem["id"] ?>"
                                <?= $trem_id === (int)$trem["id"] ? "selected" : "" ?>>

                                <?= htmlspecialchars(
                                    $trem["identificador"] . " - " . $trem["modelo"],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                    <?php if (empty($trens)): ?>

                        <div class="form-hint text-danger">
                            Nenhum trem cadastrado. Cadastre um trem antes de criar rotas.
                        </div>

                    <?php endif; ?>

                </div>


                <div class="form-group">

                    <label class="form-label">Status da rota</label>

                    <select name="status" class="form-select" required>

                        <option value="ativa"
                            <?= $status === "ativa" ? "selected" : "" ?>>
                            Ativa
                        </option>

                        <option value="inativa"
                            <?= $status === "inativa" ? "selected" : "" ?>>
                            Inativa
                        </option>

                        <option value="manutencao"
                            <?= $status === "manutencao" ? "selected" : "" ?>>
                            Manutenção
                        </option>

                    </select>

                </div>


                <button
                    type="submit"
                    class="btn-submit"
                    <?= empty($trens) ? "disabled" : "" ?>>

                    Cadastrar Rota

                </button>

            </form>

        </section>

    </main>

</body>
</html>
