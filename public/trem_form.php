<?php

require_once "../../infra/admin.php";
require_once "../../infra/connect.php";

$statusValidos = ["ativo", "inativo", "manutencao", "falha"];
$erro = "";

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT)
    ?: filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);

$trem = [
    "identificador" => "",
    "modelo" => "",
    "status" => "inativo",
    "velocidade_atual" => "0",
    "localizacao_atual" => "",
    "consumo_energia" => "",
];

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    foreach ($trem as $campo => $valor) {
        $trem[$campo] = trim($_POST[$campo] ?? "");
    }

    if ($trem["identificador"] === "" || $trem["modelo"] === "") {
        $erro = "Identificador e modelo são obrigatórios.";
    } elseif (!in_array($trem["status"], $statusValidos, true)) {
        $erro = "Status inválido.";
    } elseif (!is_numeric($trem["velocidade_atual"]) || $trem["velocidade_atual"] < 0) {
        $erro = "Velocidade inválida.";
    } elseif ($trem["consumo_energia"] !== "" && !is_numeric($trem["consumo_energia"])) {
        $erro = "Consumo de energia inválido.";
    } else {

        $velocidade = (float) $trem["velocidade_atual"];
        $consumo = $trem["consumo_energia"] === "" ? null : (float) $trem["consumo_energia"];
        $local = $trem["localizacao_atual"] === "" ? null : $trem["localizacao_atual"];

        try {
            if ($id) {
                $stmt = mysqli_prepare($conn, "UPDATE trens SET identificador=?, modelo=?, status=?,
                    velocidade_atual=?, localizacao_atual=?, consumo_energia=? WHERE id=?");
                mysqli_stmt_bind_param($stmt, "sssdsdi", $trem["identificador"], $trem["modelo"],
                    $trem["status"], $velocidade, $local, $consumo, $id);
            } else {
                $stmt = mysqli_prepare($conn, "INSERT INTO trens
                    (identificador, modelo, status, velocidade_atual, localizacao_atual, consumo_energia)
                    VALUES (?, ?, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt, "sssdsd", $trem["identificador"], $trem["modelo"],
                    $trem["status"], $velocidade, $local, $consumo);
            }

            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            header("Location: trens.php");
            exit;

        } catch (mysqli_sql_exception $e) {
            $erro = "Não foi possível salvar. Verifique se o identificador já existe.";
        }
    }

} elseif ($id) {

    $stmt = mysqli_prepare($conn, "SELECT identificador, modelo, status, velocidade_atual,
        localizacao_atual, consumo_energia FROM trens WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $encontrado = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$encontrado) {
        die("Trem não encontrado.");
    }

    $trem = array_map(fn($v) => (string) $v, $encontrado);
}

function v($valor) { return htmlspecialchars((string) $valor); }
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $id ? "Editar" : "Novo" ?> trem - Ferrorama</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4" style="max-width: 640px;">
    <h1 class="h3 mb-4"><?= $id ? "Editar trem" : "Novo trem" ?></h1>

    <?php if ($erro !== ""): ?>
        <div class="alert alert-danger"><?= v($erro) ?></div>
    <?php endif; ?>

    <form method="post" class="card card-body shadow-sm">
        <input type="hidden" name="id" value="<?= (int) $id ?>">

        <label class="form-label" for="identificador">Identificador</label>
        <input class="form-control mb-3" id="identificador" name="identificador" maxlength="50" required value="<?= v($trem["identificador"]) ?>">

        <label class="form-label" for="modelo">Modelo</label>
        <input class="form-control mb-3" id="modelo" name="modelo" maxlength="100" required value="<?= v($trem["modelo"]) ?>">

        <label class="form-label" for="status">Status</label>
        <select class="form-select mb-3" id="status" name="status">
            <?php foreach ($statusValidos as $s): ?>
                <option value="<?= $s ?>" <?= $trem["status"] === $s ? "selected" : "" ?>><?= ucfirst($s) ?></option>
            <?php endforeach; ?>
        </select>

        <label class="form-label" for="velocidade_atual">Velocidade atual (km/h)</label>
        <input class="form-control mb-3" id="velocidade_atual" name="velocidade_atual" type="number" step="0.01" min="0" value="<?= v($trem["velocidade_atual"]) ?>">

        <label class="form-label" for="localizacao_atual">Localização atual</label>
        <input class="form-control mb-3" id="localizacao_atual" name="localizacao_atual" maxlength="150" value="<?= v($trem["localizacao_atual"]) ?>">

        <label class="form-label" for="consumo_energia">Consumo de energia</label>
        <input class="form-control mb-4" id="consumo_energia" name="consumo_energia" type="number" step="0.01" min="0" value="<?= v($trem["consumo_energia"]) ?>">

        <div class="d-flex gap-2">
            <button class="btn btn-primary" type="submit">Salvar</button>
            <a class="btn btn-outline-secondary" href="trens.php">Cancelar</a>
        </div>
    </form>
</div>
</body>
</html>
