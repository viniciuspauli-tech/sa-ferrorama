```php
<?php

require_once __DIR__ . "/../../infra/adm.php";
require_once __DIR__ . "/../../infra/connect.php";

function escapar($valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, "UTF-8");
}

$mensagemErro = "";

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    header("Location: listagemtrem.php");
    exit;
}

// Buscar o trem
$sql = "SELECT * FROM trens WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();

$resultado = $stmt->get_result();
$trem = $resultado->fetch_assoc();
$stmt->close();

if (!$trem) {
    header("Location: listagemtrem.php?erro=nao_encontrado");
    exit;
}

// Buscar usuários para selecionar o responsável
$sqlUsuarios = "
    SELECT id, nome, email
    FROM usuarios
    ORDER BY nome ASC
";

$resultadoUsuarios = $conn->query($sqlUsuarios);

if (!$resultadoUsuarios) {
    die("Não foi possível carregar os usuários.");
}

$usuarios = $resultadoUsuarios->fetch_all(MYSQLI_ASSOC);

// Valores atuais do formulário
$identificador = $trem["identificador"];
$modelo = $trem["modelo"];
$status = $trem["status"];
$velocidade = $trem["velocidade_atual"];
$localizacao = $trem["localizacao_atual"] ?? "";
$consumo = $trem["consumo_energia"];
$responsavelId = $trem["responsavel_id"] ?? "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $identificador = trim($_POST["identificador"] ?? "");
    $modelo = trim($_POST["modelo"] ?? "");
    $status = $_POST["status"] ?? "";
    $velocidade = trim($_POST["velocidade_atual"] ?? "");
    $localizacao = trim($_POST["localizacao_atual"] ?? "");
    $consumo = trim($_POST["consumo_energia"] ?? "");
    $responsavelId = $_POST["responsavel_id"] ?? "";

    $statusValidos = [
        "ativo",
        "inativo",
        "manutencao",
        "falha"
    ];

    if ($identificador === "" || $modelo === "") {
        $mensagemErro = "Preencha o identificador e o modelo.";

    } elseif (
        strlen($identificador) > 50 ||
        strlen($modelo) > 100 ||
        strlen($localizacao) > 150
    ) {
        $mensagemErro = "Um ou mais campos ultrapassam o tamanho permitido.";

    } elseif (!in_array($status, $statusValidos, true)) {
        $mensagemErro = "Selecione um status válido.";

    } elseif (
        !is_numeric($velocidade) ||
        (float) $velocidade < 0 ||
        !is_numeric($consumo) ||
        (float) $consumo < 0
    ) {
        $mensagemErro = "Velocidade e consumo devem ser números iguais ou maiores que zero.";

    } else {

        // Validar o responsável selecionado
        $responsavel = null;

        if ($responsavelId !== "") {

            $responsavel = filter_var(
                $responsavelId,
                FILTER_VALIDATE_INT
            );

            if (!$responsavel || $responsavel <= 0) {
                $mensagemErro = "Selecione um usuário responsável válido.";
            } else {
                $stmtUsuario = $conn->prepare(
                    "SELECT id FROM usuarios WHERE id = ?"
                );

                $stmtUsuario->bind_param("i", $responsavel);
                $stmtUsuario->execute();

                $usuarioExiste = $stmtUsuario->get_result()->num_rows > 0;
                $stmtUsuario->close();

                if (!$usuarioExiste) {
                    $mensagemErro = "O usuário responsável selecionado não existe.";
                }
            }
        }

        if ($mensagemErro === "") {

            $velocidade = (float) $velocidade;
            $consumo = (float) $consumo;

            if ($responsavelId === "") {

                $sql = "
                    UPDATE trens
                    SET identificador = ?,
                        modelo = ?,
                        status = ?,
                        velocidade_atual = ?,
                        localizacao_atual = ?,
                        consumo_energia = ?,
                        responsavel_id = NULL
                    WHERE id = ?
                ";

                $stmt = $conn->prepare($sql);

                $stmt->bind_param(
                    "sssd sdi",
                    $identificador,
                    $modelo,
                    $status,
                    $velocidade,
                    $localizacao,
                    $consumo,
                    $id
                );

            } else {

                $sql = "
                    UPDATE trens
                    SET identificador = ?,
                        modelo = ?,
                        status = ?,
                        velocidade_atual = ?,
                        localizacao_atual = ?,
                        consumo_energia = ?,
                        responsavel_id = ?
                    WHERE id = ?
                ";

                $stmt = $conn->prepare($sql);

                $stmt->bind_param(
                    "sssd sdii",
                    $identificador,
                    $modelo,
                    $status,
                    $velocidade,
                    $localizacao,
                    $consumo,
                    $responsavel,
                    $id
                );
            }

            try {

                if (!$stmt) {
                    throw new Exception("Falha ao preparar a atualização.");
                }

                $stmt->execute();
                $stmt->close();

                header("Location: listagemtrem.php?sucesso=editado");
                exit;

            } catch (Throwable $e) {

                if (isset($stmt) && $stmt instanceof mysqli_stmt) {
                    $stmt->close();
                }

                if ($e instanceof mysqli_sql_exception && $e->getCode() === 1062) {
                    $mensagemErro = "Já existe um trem com esse identificador.";
                } else {
                    error_log($e->getMessage());
                    $mensagemErro = "Não foi possível atualizar o trem. Verifique os dados.";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Trem - Ferrorama</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
</head>

<body class="bg-light">

<div class="container py-4" style="max-width: 700px;">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Editar Trem</h1>

        <a href="listagemtrem.php" class="btn btn-secondary">
            Voltar
        </a>
    </div>

    <?php if ($mensagemErro !== ""): ?>
        <div class="alert alert-danger">
            <?= escapar($mensagemErro) ?>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">

            <form method="POST" action="editartrem.php?id=<?= (int) $id ?>">

                <div class="mb-3">
                    <label for="identificador" class="form-label">Identificador</label>
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
                    <select id="status" name="status" class="form-select" required>
                        <option value="ativo" <?= $status === "ativo" ? "selected" : "" ?>>Ativo</option>
                        <option value="inativo" <?= $status === "inativo" ? "selected" : "" ?>>Inativo</option>
                        <option value="manutencao" <?= $status === "manutencao" ? "selected" : "" ?>>Manutenção</option>
                        <option value="falha" <?= $status === "falha" ? "selected" : "" ?>>Falha</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="velocidade_atual" class="form-label">Velocidade (km/h)</label>
                    <input
                        type="number"
                        step="0.01"
                        min="0"
                        id="velocidade_atual"
                        name="velocidade_atual"
                        class="form-control"
                        value="<?= escapar($velocidade) ?>"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label for="localizacao_atual" class="form-label">Localização atual</label>
                    <input
                        type="text"
                        id="localizacao_atual"
                        name="localizacao_atual"
                        maxlength="150"
                        class="form-control"
                        value="<?= escapar($localizacao) ?>"
                    >
                </div>

                <div class="mb-3">
                    <label for="consumo_energia" class="form-label">Consumo de energia (kWh)</label>
                    <input
                        type="number"
                        step="0.01"
                        min="0"
                        id="consumo_energia"
                        name="consumo_energia"
                        class="form-control"
                        value="<?= escapar($consumo) ?>"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label for="responsavel_id" class="form-label">Usuário responsável</label>

                    <select
                        id="responsavel_id"
                        name="responsavel_id"
                        class="form-select"
                    >
                        <option value="">Nenhum responsável</option>

                        <?php foreach ($usuarios as $usuario): ?>
                            <option
                                value="<?= (int) $usuario["id"] ?>"
                                <?= (string) $responsavelId === (string) $usuario["id"] ? "selected" : "" ?>
                            >
                                <?= escapar($usuario["nome"]) ?>
                                (<?= escapar($usuario["email"]) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="listagemtrem.php" class="btn btn-outline-secondary">
                        Cancelar
                    </a>

                    <button type="submit" class="btn btn-primary">
                        Salvar alterações
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>

</body>
</html>
```
