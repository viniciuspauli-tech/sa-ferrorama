
<?php
require_once __DIR__ . "/../infra/adm.php";
require_once __DIR__ . "/../infra/connect.php";

$mensagemErro = "";
$mensagemSucesso = "";

$nome = "";
$localizacao = "";
$tipoDado = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nome = trim($_POST["nome"] ?? "");
    $localizacao = trim($_POST["localizacao"] ?? "");
    $tipoDado = trim($_POST["tipo_dado"] ?? "");

    if ($nome === "" || $localizacao === "" || $tipoDado === "") {
        $mensagemErro = "Preencha todos os campos.";
    } elseif (
        strlen($nome) > 100 ||
        strlen($localizacao) > 150 ||
        strlen($tipoDado) > 100
    ) {
        $mensagemErro = "Um dos campos ultrapassou o tamanho permitido.";
    } else {
        $sql = "INSERT INTO sensores (nome, localizacao, tipo_dado)
                VALUES (?, ?, ?)";

        $stmt = $conn->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("sss", $nome, $localizacao, $tipoDado);

            try {
                $stmt->execute();
                $stmt->close();

                header("Location: sensor.php?sucesso=cadastrado");
                exit;
            } catch (mysqli_sql_exception $e) {
                error_log($e->getMessage());

                if ($e->getCode() === 1062) {
                    $mensagemErro =
                        "Já existe um sensor com esse nome nessa localização.";
                } else {
                    $mensagemErro = "Erro ao cadastrar o sensor.";
                }

                $stmt->close();
            }
        } else {
            error_log($conn->error);
            $mensagemErro = "Não foi possível preparar o cadastro.";
        }
    }
}

function escapar($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, "UTF-8");
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

    </div>

    <?php if ($mensagemErro !== ""): ?>
        <div class="alert alert-danger">
            <?= escapar($mensagemErro) ?>
        </div>
    <?php endif; ?>

    <?php if ($mensagemSucesso !== ""): ?>
        <div class="alert alert-success">
            <?= escapar($mensagemSucesso) ?>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">

            <form method="POST" action="cadastro_item.php">

                <div class="mb-3">
                    <label for="nome" class="form-label">Nome do sensor</label>

                    <input
                        type="text"
                        id="nome"
                        name="nome"
                        class="form-control"
                        maxlength="100"
                        value="<?= escapar($nome) ?>"
                        placeholder="Ex.: Sensor de temperatura"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label for="localizacao" class="form-label">
                        Localização no trilho
                    </label>

                    <input
                        type="text"
                        id="localizacao"
                        name="localizacao"
                        class="form-control"
                        maxlength="150"
                        value="<?= escapar($localizacao) ?>"
                        placeholder="Ex.: KM 12 - Trecho Norte"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label for="tipo_dado" class="form-label">
                        Tipo de dado
                    </label>

                    <input
                        type="text"
                        id="tipo_dado"
                        name="tipo_dado"
                        class="form-control"
                        maxlength="100"
                        value="<?= escapar($tipoDado) ?>"
                        placeholder="Ex.: Temperatura, presença ou velocidade"
                        required
                    >
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="sistema.php" class="btn btn-outline-secondary">
                        Voltar
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
