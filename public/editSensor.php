
<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . "/../infra/auth.php";
require_once __DIR__ . "/../infra/connect.php";

if (($_SESSION["usuario_perfil"] ?? "") !== "administrador") {
    http_response_code(403);
    exit("Acesso permitido apenas para administradores.");
}

if (empty($_SESSION["csrf"])) {
    $_SESSION["csrf"] = bin2hex(random_bytes(32));
}

function escapar($valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, "UTF-8");
}

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);
}

if (!$id || $id < 1) {
    http_response_code(400);
    exit("ID do sensor inválido.");
}

$erros = [];

$stmt = $conn->prepare(
    "SELECT id, nome, localizacao, tipo_dado FROM sensores WHERE id = ?"
);
$stmt->bind_param("i", $id);
$stmt->execute();

$sensor = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$sensor) {
    http_response_code(404);
    exit("Sensor não encontrado.");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!hash_equals($_SESSION["csrf"], $_POST["csrf"] ?? "")) {
        http_response_code(403);
        exit("Requisição inválida. Atualize a página e tente novamente.");
    }

    $nome = trim($_POST["nome"] ?? "");
    $localizacao = trim($_POST["localizacao"] ?? "");
    $tipoDado = trim($_POST["tipo_dado"] ?? "");

    if ($nome === "" || mb_strlen($nome) > 100) {
        $erros[] = "Informe um nome de até 100 caracteres.";
    }

    if ($localizacao === "" || mb_strlen($localizacao) > 150) {
        $erros[] = "Informe uma localização de até 150 caracteres.";
    }

    if ($tipoDado === "" || mb_strlen($tipoDado) > 100) {
        $erros[] = "Informe o tipo de dado, com até 100 caracteres.";
    }

    if (empty($erros)) {
        $stmt = $conn->prepare(
            "UPDATE sensores
             SET nome = ?, localizacao = ?, tipo_dado = ?
             WHERE id = ?"
        );

        if (!$stmt) {
            error_log($conn->error);
            http_response_code(500);
            exit("Não foi possível preparar a atualização.");
        }

        $stmt->bind_param("sssi", $nome, $localizacao, $tipoDado, $id);

        if ($stmt->execute()) {
            $stmt->close();
            header("Location: listar_sensores.php?sucesso=editado");
            exit;
        }

        error_log($stmt->error);
        $erros[] = "Não foi possível atualizar o sensor.";
        $stmt->close();
    }

    $sensor["nome"] = $nome;
    $sensor["localizacao"] = $localizacao;
    $sensor["tipo_dado"] = $tipoDado;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar sensor - Ferrorama</title>

    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            padding: 30px 15px;
            font-family: Arial, sans-serif;
            background: #f1f3f8;
            color: #111c30;
        }

        .card {
            max-width: 650px;
            margin: 20px auto;
            padding: 28px;
            background: white;
            border: 1px solid #e0e6f0;
            border-radius: 12px;
        }

        label {
            display: block;
            margin: 18px 0 7px;
            font-weight: bold;
        }

        input {
            width: 100%;
            padding: 12px;
            border: 1px solid #d5dce8;
            border-radius: 7px;
        }

        button, .botao {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 16px;
            border: 0;
            border-radius: 7px;
            background: #3324d9;
            color: white;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
        }

        .voltar { background: #e8edf5; color: #172a42; }

        .erro {
            padding: 12px;
            margin: 10px 0;
            border-radius: 7px;
            background: #ffe8eb;
            color: #a51e32;
        }
    </style>
</head>

<body>
<section class="card">
    <h1>Editar sensor</h1>
    <p>Altere os dados do sensor instalado nos trilhos.</p>

    <?php foreach ($erros as $erro): ?>
        <div class="erro"><?= escapar($erro) ?></div>
    <?php endforeach; ?>

    <form method="POST" action="editSensor.php">
        <input type="hidden" name="id" value="<?= (int) $sensor["id"] ?>">
        <input type="hidden" name="csrf" value="<?= escapar($_SESSION["csrf"]) ?>">

        <label for="nome">Nome do sensor</label>
        <input
            id="nome"
            name="nome"
            maxlength="100"
            required
            value="<?= escapar($sensor["nome"]) ?>"
        >

        <label for="localizacao">Localização nos trilhos</label>
        <input
            id="localizacao"
            name="localizacao"
            maxlength="150"
            required
            placeholder="Ex.: KM 12"
            value="<?= escapar($sensor["localizacao"]) ?>"
        >

        <label for="tipo_dado">Tipo de dado</label>
        <input
            id="tipo_dado"
            name="tipo_dado"
            maxlength="100"
            required
            placeholder="Ex.: temperatura, velocidade, vibração"
            value="<?= escapar($sensor["tipo_dado"]) ?>"
        >

        <button type="submit">Salvar alterações</button>
        <a href="listar_sensores.php" class="botao voltar">Cancelar</a>
    </form>
</section>
</body>
</html>
