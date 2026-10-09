
<?php

require_once __DIR__ . "/../../infra/adm.php";
require_once __DIR__ . "/../../infra/connect.php";

$mensagemErro = "";
$mensagemSucesso = "";

$identificador = "";
$modelo = "";
$status = "ativo";
$velocidade = "0.00";
$localizacao = "";
$consumo = "0.00";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $identificador = trim($_POST["identificador"] ?? "");
    $modelo = trim($_POST["modelo"] ?? "");
    $status = $_POST["status"] ?? "ativo";
    $velocidade = $_POST["velocidade_atual"] ?? "0";
    $localizacao = trim($_POST["localizacao_atual"] ?? "");
    $consumo = $_POST["consumo_energia"] ?? "0";

    $statusValidos = ["ativo", "inativo", "manutencao", "falha"];

    if ($identificador === "" || $modelo === "") {

        $mensagemErro = "Preencha o identificador e o modelo do trem.";

    } elseif (!in_array($status, $statusValidos, true)) {

        $mensagemErro = "Selecione um status válido.";

    } elseif (
        !is_numeric($velocidade) ||
        (float) $velocidade < 0
    ) {

        $mensagemErro = "Informe uma velocidade válida.";

    } elseif (
        !is_numeric($consumo) ||
        (float) $consumo < 0
    ) {

        $mensagemErro = "Informe um consumo de energia válido.";

    } else {

        $velocidade = (float) $velocidade;
        $consumo = (float) $consumo;

        $sql = "
            INSERT INTO trens (
                identificador,
                modelo,
                status,
                velocidade_atual,
                localizacao_atual,
                consumo_energia
            )
            VALUES (?, ?, ?, ?, ?, ?)
        ";

        $stmt = $conn->prepare($sql);

        if ($stmt) {

            $stmt->bind_param(
                "sssdsd",
                $identificador,
                $modelo,
                $status,
                $velocidade,
                $localizacao,
                $consumo
            );

            try {

                $stmt->execute();
                $stmt->close();

                header("Location: listagemtrem.php?sucesso=cadastrado");
                exit;

            } catch (mysqli_sql_exception $e) {

                if ($e->getCode() === 1062) {
                    $mensagemErro =
                        "Já existe um trem com esse identificador. Escolha outro.";
                } else {
                    error_log($e->getMessage());

                    $mensagemErro =
                        "Não foi possível cadastrar o trem. Tente novamente.";
                }

                $stmt->close();
            }

        } else {

            error_log($conn->error);

            $mensagemErro =
                "Não foi possível preparar o cadastro do trem.";
        }
    }
}

function escapar($valor): string
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

    <title>Cadastrar Trem - Ferrorama</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
</head>

<body class="bg-light">

<div class="container py-4" style="max-width: 600px;">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Cadastrar Trem</h1>

        <a href="listagemtrem.php" class="btn btn-secondary">
            Voltar
        </a>
    </div>

    <?php if ($mensagemErro !== ""): ?>
        <div class="alert alert-danger" role="alert">
            <?= escapar($mensagemErro) ?>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">

            <form method="POST" action="cadastrotrem.php">

                <div class="mb-3">
                    <label for="identificador" class="form-label">
                        Identificador (Ex: TR-005)
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        id="identificador"
                        name="identificador"
                        maxlength="50"
                        value="<?= escapar($identificador) ?>"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label for="modelo" class="form-label">Modelo</label>

                    <input
                        type="text"
                        class="form-control"
                        id="modelo"
                        name="modelo"
                        maxlength="100"
                        value="<?= escapar($modelo) ?>"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label for="status" class="form-label">Status</label>

                    <select class="form-select" id="status" name="status" required>
                        <option value="ativo" <?= $status === "ativo" ? "selected" : "" ?>>
                            Ativo
                        </option>

                        <option value="inativo" <?= $status === "inativo" ? "selected" : "" ?>>
                            Inativo
                        </option>

                        <option value="manutencao" <?= $status === "manutencao" ? "selected" : "" ?>>
                            Manutenção
                        </option>

                        <option value="falha" <?= $status === "falha" ? "selected" : "" ?>>
                            Falha
                        </option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="velocidade_atual" class="form-label">
                        Velocidade Atual (km/h)
                    </label>

                    <input
                        type="number"
                        step="0.01"
                        min="0"
                        class="form-control"
                        id="velocidade_atual"
                        name="velocidade_atual"
                        value="<?= escapar($velocidade) ?>"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label for="localizacao_atual" class="form-label">
                        Localização Atual
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        id="localizacao_atual"
                        name="localizacao_atual"
                        maxlength="150"
                        placeholder="Ex: KM 12 - Trecho Norte"
                        value="<?= escapar($localizacao) ?>"
                    >
                </div>

                <div class="mb-3">
                    <label for="consumo_energia" class="form-label">
                        Consumo de Energia (kWh)
                    </label>

                    <input
                        type="number"
                        step="0.01"
                        min="0"
                        class="form-control"
                        id="consumo_energia"
                        name="consumo_energia"
                        value="<?= escapar($consumo) ?>"
                        required
                    >
                </div>

                <div class="d-flex justify-content-end gap-2">

                    <a
                        href="listagemtrem.php"
                        class="btn btn-outline-secondary"
                    >
                        Cancelar
                    </a>

                    <button type="submit" class="btn btn-primary">
                        Salvar Trem
                    </button>

                </div>

            </form>

        </div>
    </div>

</div>

</body>
</html>