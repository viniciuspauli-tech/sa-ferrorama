
<?php
require_once __DIR__ . "/../infra/adm.php";
require_once __DIR__ . "/../infra/connect.php";

$mensagemErro = "";
$mensagemSucesso = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nome = trim($_POST["nome"] ?? "");
    $localizacao = trim($_POST["localizacao"] ?? "");
    $tipoDado = trim($_POST["tipo_dado"] ?? "");

    if ($nome === "" || $localizacao === "" || $tipoDado === "") {
        $mensagemErro = "Preencha todos os campos.";
    } else {
        $sql = "INSERT INTO sensores (nome, localizacao, tipo_dado)
                VALUES (?, ?, ?)";

        $stmt = $conn->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("sss", $nome, $localizacao, $tipoDado);

            if ($stmt->execute()) {
                $mensagemSucesso = "Sensor cadastrado com sucesso!";
            } else {
                $mensagemErro = "Erro ao cadastrar o sensor.";
            }

            $stmt->close();
        } else {
            $mensagemErro = "Não foi possível preparar o cadastro.";
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

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
</head>

<body class="bg-light">

<div class="container py-4" style="max-width: 650px;">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Cadastrar Sensor</h1>
        <a href="sistema.php" class="btn btn-dark">Voltar</a>
    </div>

    <?php if ($mensagemErro !== ""): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($mensagemErro) ?>
        </div>
    <?php endif; ?>

    <?php if ($mensagemSucesso !== ""): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($mensagemSucesso) ?>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">

            <form method="POST" action="cadastro_item.php">

                <div class="mb-3">
                    <label for="nome" class="form-label">
                        Nome do sensor
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        id="nome"
                        name="nome"
                        maxlength="100"
                        placeholder="Ex.: Sensor de Temperatura"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label for="localizacao" class="form-label">
                        Localização no trilho
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        id="localizacao"
                        name="localizacao"
                        maxlength="150"
                        placeholder="Ex.: KM 12"
                        required
                    >

                    <div class="form-text">
                        Informe o quilômetro ou trecho onde o sensor está instalado.
                    </div>
                </div>

                <div class="mb-3">
                    <label for="tipo_dado" class="form-label">
                        Tipo de dado
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        id="tipo_dado"
                        name="tipo_dado"
                        maxlength="100"
                        placeholder="Ex.: Temperatura, Velocidade ou Presença"
                        required
                    >
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="sistema.php" class="btn btn-outline-secondary">
                        Cancelar
                    </a>

                    <button type="submit" class="btn btn-primary">
                        Cadastrar sensor
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>

</body>
</html>
