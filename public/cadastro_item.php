<?php

require_once "../infra/connect.php";

$mensagemErro = "";
$mensagemSucesso = "";

// Busca a lista de trens para popular o campo <select>
$sqlTrens = "SELECT id, identificador, modelo FROM trens ORDER BY identificador ASC";
$resTrens = $conn->query($sqlTrens);
$trens = $resTrens ? $resTrens->fetch_all(MYSQLI_ASSOC) : [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nome = trim($_POST["nome"] ?? "");
    $localizacao = trim($_POST["localizacao"] ?? "");
    $tipoDado = trim($_POST["tipo_dado"] ?? "");
    $tremId = !empty($_POST["trem_id"]) ? (int)$_POST["trem_id"] : null;

    if (empty($nome) || empty($tipoDado)) {
        $mensagemErro = "Por favor, preencha pelo menos o Nome e o Tipo de dado do sensor.";
    } else {
        $sql = "INSERT INTO sensores (nome, localizacao, tipo_dado, trem_id) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("sssi", $nome, $localizacao, $tipoDado, $tremId);

            if ($stmt->execute()) {
                $mensagemSucesso = "Sensor cadastrado com sucesso!";
            } else {
                $mensagemErro = "Erro ao cadastrar sensor: " . $conn->error;
            }
            $stmt->close();
        } else {
            $mensagemErro = "Erro na preparação da consulta: " . $conn->error;
        }
    }
}

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Sensor - Ferrorama</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container py-4" style="max-width: 600px;">

    <!-- CABEÇALHO -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Cadastrar Sensor</h1>

        <a href="sistema.php" class="btn btn-primary">
            Voltar ao Sistema
        </a>
    </div>

    <!-- MENSAGENS DE ALERTA -->
    <?php if (!empty($mensagemErro)): ?>
        <div class="alert alert-danger" role="alert">
            <?= htmlspecialchars($mensagemErro) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($mensagemSucesso)): ?>
        <div class="alert alert-success" role="alert">
            <?= htmlspecialchars($mensagemSucesso) ?>
        </div>
    <?php endif; ?>

    <!-- FORMULÁRIO -->
    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="cadastro_item.php">

                <div class="mb-3">
                    <label for="nome" class="form-label">Nome do Sensor</label>
                    <input 
                        type="text" 
                        class="form-control" 
                        id="nome" 
                        name="nome" 
                        placeholder="Ex: Sensor de Temperatura Norte"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label for="localizacao" class="form-label">Localização</label>
                    <input 
                        type="text" 
                        class="form-control" 
                        id="localizacao" 
                        name="localizacao" 
                        placeholder="Ex: Vagão 01 / Vagão Motor"
                    >
                </div>

                <div class="mb-3">
                    <label for="tipo_dado" class="form-label">Tipo de Dado</label>
                    <input 
                        type="text" 
                        class="form-control" 
                        id="tipo_dado" 
                        name="tipo_dado" 
                        placeholder="Ex: Temperatura, Pressão, Velocidade"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label for="trem_id" class="form-label">Trem Associado</label>
                    <select class="form-select" id="trem_id" name="trem_id">
                        <option value="">Selecione um trem (opcional)</option>
                        <?php foreach ($trens as $trem): ?>
                            <option value="<?= $trem['id'] ?>">
                                <?= htmlspecialchars($trem['identificador']) ?> - <?= htmlspecialchars($trem['modelo']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="sistema.php" class="btn btn-outline-secondary">
                        Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        Cadastrar
                    </button>
                </div>

            </form>
        </div>
    </div>

</div>

</body>
</html>