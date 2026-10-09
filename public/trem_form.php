
<?php

require_once __DIR__ . "/../infra/adm.php";
require_once __DIR__ . "/../infra/connect.php";

$statusValidos = ["ativo", "inativo", "manutencao", "falha"];
$erro = "";

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id) {
    $id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);
}

$trem = [
    "identificador" => "",
    "modelo" => "",
    "status" => "inativo",
    "velocidade_atual" => "0",
    "localizacao_atual" => "",
    "consumo_energia" => "",
    "responsavel_id" => ""
];

// Buscar usuários para o campo responsável
$sqlUsuarios = "
    SELECT id, nome, email
    FROM usuarios
    ORDER BY nome ASC
";

$resultadoUsuarios = mysqli_query($conn, $sqlUsuarios);

if (!$resultadoUsuarios) {
    die("Erro ao consultar usuários.");
}

$usuarios = mysqli_fetch_all($resultadoUsuarios, MYSQLI_ASSOC);

$idsUsuarios = array_map(
    fn($usuario) => (int) $usuario["id"],
    $usuarios
);

// Se estiver editando, carregar os dados do trem
if ($id && $_SERVER["REQUEST_METHOD"] !== "POST") {

    $stmt = mysqli_prepare(
        $conn,
        "SELECT identificador, modelo, status, velocidade_atual,
                localizacao_atual, consumo_energia, responsavel_id
         FROM trens
         WHERE id = ?"
    );

    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);

    $encontrado = mysqli_fetch_assoc(
        mysqli_stmt_get_result($stmt)
    );

    mysqli_stmt_close($stmt);

    if (!$encontrado) {
        http_response_code(404);
        exit("Trem não encontrado.");
    }

    $trem = array_merge($trem, $encontrado);

    $trem["responsavel_id"] =
        $encontrado["responsavel_id"] === null
            ? ""
            : (string) $encontrado["responsavel_id"];
}

// Processar formulário
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    foreach ($trem as $campo => $valor) {
        $trem[$campo] = trim((string) ($_POST[$campo] ?? ""));
    }

    $responsavelId = filter_var(
        $trem["responsavel_id"],
        FILTER_VALIDATE_INT
    );

    if (
        $trem["identificador"] === "" ||
        $trem["modelo"] === ""
    ) {
        $erro = "Identificador e modelo são obrigatórios.";

    } elseif (!in_array($trem["status"], $statusValidos, true)) {
        $erro = "Status inválido.";

    } elseif (
        !is_numeric($trem["velocidade_atual"]) ||
        (float) $trem["velocidade_atual"] < 0
    ) {
        $erro = "Informe uma velocidade válida.";

    } elseif (
        $trem["consumo_energia"] !== "" &&
        (
            !is_numeric($trem["consumo_energia"]) ||
            (float) $trem["consumo_energia"] < 0
        )
    ) {
        $erro = "Informe um consumo de energia válido.";

    } elseif (
        !$responsavelId ||
        !in_array($responsavelId, $idsUsuarios, true)
    ) {
        $erro = "Selecione um usuário responsável válido.";

    } else {

        $velocidade = (float) $trem["velocidade_atual"];

        $consumo = $trem["consumo_energia"] === ""
            ? null
            : (float) $trem["consumo_energia"];

        $local = $trem["localizacao_atual"] === ""
            ? null
            : $trem["localizacao_atual"];

        try {

            if ($id) {

                $stmt = mysqli_prepare(
                    $conn,
                    "UPDATE trens
                     SET identificador = ?,
                         modelo = ?,
                         status = ?,
                         velocidade_atual = ?,
                         localizacao_atual = ?,
                         consumo_energia = ?,
                         responsavel_id = ?
                     WHERE id = ?"
                );

                mysqli_stmt_bind_param(
                    $stmt,
                    "sssdsdii",
                    $trem["identificador"],
                    $trem["modelo"],
                    $trem["status"],
                    $velocidade,
                    $local,
                    $consumo,
                    $responsavelId,
                    $id
                );

            } else {

                $stmt = mysqli_prepare(
                    $conn,
                    "INSERT INTO trens
                     (identificador, modelo, status, velocidade_atual,
                      localizacao_atual, consumo_energia, responsavel_id)
                     VALUES (?, ?, ?, ?, ?, ?, ?)"
                );

                mysqli_stmt_bind_param(
                    $stmt,
                    "sssdsdi",
                    $trem["identificador"],
                    $trem["modelo"],
                    $trem["status"],
                    $velocidade,
                    $local,
                    $consumo,
                    $responsavelId
                );
            }

            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            header("Location: trens.php");
            exit;

        } catch (mysqli_sql_exception $e) {

            if (isset($stmt) && $stmt instanceof mysqli_stmt) {
                mysqli_stmt_close($stmt);
            }

            if ($e->getCode() === 1062) {
                $erro = "Esse identificador já está cadastrado.";
            } else {
                error_log($e->getMessage());
                $erro = "Não foi possível salvar o trem. Verifique os dados e tente novamente.";
            }
        }
    }
}

function v($valor): string
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
    <title><?= $id ? "Editar" : "Cadastrar" ?> trem - Ferrorama</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
</head>

<body class="bg-light">

<div class="container py-4" style="max-width: 640px;">

    <h1 class="h3 mb-4">
        <?= $id ? "Editar trem" : "Cadastrar trem" ?>
    </h1>

    <?php if ($erro !== ""): ?>
        <div class="alert alert-danger">
            <?= v($erro) ?>
        </div>
    <?php endif; ?>

    <form method="post" class="card card-body shadow-sm">

        <input type="hidden" name="id" value="<?= (int) ($id ?? 0) ?>">

        <label for="identificador" class="form-label">Identificador</label>
        <input
            class="form-control mb-3"
            id="identificador"
            name="identificador"
            maxlength="50"
            required
            value="<?= v($trem["identificador"]) ?>"
        >

        <label for="modelo" class="form-label">Modelo</label>
        <input
            class="form-control mb-3"
            id="modelo"
            name="modelo"
            maxlength="100"
            required
            value="<?= v($trem["modelo"]) ?>"
        >

        <label for="status" class="form-label">Status</label>
        <select class="form-select mb-3" id="status" name="status" required>
            <?php foreach ($statusValidos as $status): ?>
                <option
                    value="<?= v($status) ?>"
                    <?= $trem["status"] === $status ? "selected" : "" ?>
                >
                    <?= v(ucfirst($status)) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="velocidade_atual" class="form-label">
            Velocidade atual (km/h)
        </label>
        <input
            class="form-control mb-3"
            id="velocidade_atual"
            name="velocidade_atual"
            type="number"
            step="0.01"
            min="0"
            required
            value="<?= v($trem["velocidade_atual"]) ?>"
        >

        <label for="localizacao_atual" class="form-label">
            Localização atual
        </label>
        <input
            class="form-control mb-3"
            id="localizacao_atual"
            name="localizacao_atual"
            maxlength="150"
            value="<?= v($trem["localizacao_atual"]) ?>"
        >

        <label for="consumo_energia" class="form-label">
            Consumo de energia
        </label>
        <input
            class="form-control mb-3"
            id="consumo_energia"
            name="consumo_energia"
            type="number"
            step="0.01"
            min="0"
            value="<?= v($trem["consumo_energia"]) ?>"
        >

        <label for="responsavel_id" class="form-label">
            Usuário responsável
        </label>
        <select
            class="form-select mb-4"
            id="responsavel_id"
            name="responsavel_id"
            required
        >
            <option value="">Selecione um usuário</option>

            <?php foreach ($usuarios as $usuario): ?>
                <option
                    value="<?= (int) $usuario["id"] ?>"
                    <?= (string) $trem["responsavel_id"] ===
                        (string) $usuario["id"] ? "selected" : "" ?>
                >
                    <?= v($usuario["nome"]) ?>
                    (<?= v($usuario["email"]) ?>)
                </option>
            <?php endforeach; ?>
        </select>

        <div class="d-flex gap-2">
            <button class="btn btn-primary" type="submit">
                Salvar
            </button>

            <a class="btn btn-outline-secondary" href="trens.php">
                Cancelar
            </a>
        </div>

    </form>
</div>

</body>
</html>