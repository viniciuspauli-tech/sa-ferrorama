<?php
require_once __DIR__ . "/../infra/adm.php";
require_once __DIR__ . "/../infra/connect.php";

function escapar($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, "UTF-8");
}

// Se vier um id válido (GET ao abrir, POST ao salvar), a página funciona como edição
$id = filter_var($_GET["id"] ?? $_POST["id"] ?? null, FILTER_VALIDATE_INT);
$editando = $id !== false && $id !== null;

$mensagemErro = "";
$nome = "";
$localizacao = "";
$tipoDado = "";

// Modo edição: carrega os dados atuais do sensor
if ($editando) {
    $stmt = $conn->prepare("SELECT nome, localizacao, tipo_dado FROM sensores WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $sensor = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$sensor) {
        header("Location: sensor.php?erro=nao_encontrado");
        exit;
    }

    $nome = $sensor["nome"];
    $localizacao = $sensor["localizacao"];
    $tipoDado = $sensor["tipo_dado"];
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nome = trim($_POST["nome"] ?? "");
    $localizacao = trim($_POST["localizacao"] ?? "");
    $tipoDado = trim($_POST["tipo_dado"] ?? "");

    if ($nome === "" || $localizacao === "" || $tipoDado === "") {
        $mensagemErro = "Preencha todos os campos.";
    } elseif (
        mb_strlen($nome) > 100 ||
        mb_strlen($localizacao) > 150 ||
        mb_strlen($tipoDado) > 100
    ) {
        $mensagemErro = "Um dos campos ultrapassou o tamanho permitido.";
    } else {
        try {
            if ($editando) {
                $stmt = $conn->prepare(
                    "UPDATE sensores SET nome = ?, localizacao = ?, tipo_dado = ? WHERE id = ?"
                );
                $stmt->bind_param("sssi", $nome, $localizacao, $tipoDado, $id);
                $sucesso = "editado";
            } else {
                $stmt = $conn->prepare(
                    "INSERT INTO sensores (nome, localizacao, tipo_dado) VALUES (?, ?, ?)"
                );
                $stmt->bind_param("sss", $nome, $localizacao, $tipoDado);
                $sucesso = "cadastrado";
            }

            $stmt->execute();
            $stmt->close();

            header("Location: sensor.php?sucesso=" . $sucesso);
            exit;
        } catch (mysqli_sql_exception $e) {
            error_log($e->getMessage());

            if ($e->getCode() === 1062) {
                $mensagemErro = "Já existe um sensor com esse nome nessa localização.";
            } else {
                $mensagemErro = $editando
                    ? "Erro ao atualizar o sensor. Tente novamente."
                    : "Erro ao cadastrar o sensor. Tente novamente.";
            }
        }
    }
}

$titulo = $editando ? "Editar Sensor" : "Cadastrar Sensor";
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= escapar($titulo) ?> - Ferrorama</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4" style="max-width: 650px;">

    <h1 class="h3 mb-4"><?= escapar($titulo) ?></h1>

    <?php if ($mensagemErro !== ""): ?>
        <div class="alert alert-danger"><?= escapar($mensagemErro) ?></div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="">
                <?php if ($editando): ?>
                    <input type="hidden" name="id" value="<?= (int)$id ?>">
                <?php endif; ?>

                <div class="mb-3">
                    <label for="nome" class="form-label">Nome do sensor</label>
                    <input type="text" id="nome" name="nome" class="form-control" maxlength="100"
                           value="<?= escapar($nome) ?>" placeholder="Ex.: Sensor de temperatura" required>
                </div>

                <div class="mb-3">
                    <label for="localizacao" class="form-label">Localização no trilho</label>
                    <input type="text" id="localizacao" name="localizacao" class="form-control" maxlength="150"
                           value="<?= escapar($localizacao) ?>" placeholder="Ex.: KM 12 - Trecho Norte" required>
                </div>

                <div class="mb-3">
                    <label for="tipo_dado" class="form-label">Tipo de dado</label>
                    <input type="text" id="tipo_dado" name="tipo_dado" class="form-control" maxlength="100"
                           value="<?= escapar($tipoDado) ?>" placeholder="Ex.: Temperatura, presença ou velocidade" required>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="sensor.php" class="btn btn-outline-secondary">Voltar</a>
                    <button type="submit" class="btn btn-primary">
                        <?= $editando ? "Salvar alterações" : "Cadastrar sensor" ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>