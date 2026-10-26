
<?php
require_once __DIR__ . "/../infra/adm.php";
require_once __DIR__ . "/../infra/connect.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function escapar($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, "UTF-8");
}

$erro = "";
$sucesso = "";
$id = 0;
$nome = "";
$localizacao = "";
$tipoDado = "";

// Identifica a operação solicitada
$acao = $_POST["acao"] ?? "";
$idRecebido = $_POST["id"] ?? $_GET["id"] ?? "";

if ($idRecebido !== "") {
    $idValidado = filter_var($idRecebido, FILTER_VALIDATE_INT);
    if ($idValidado === false || $idValidado < 1) {
        $erro = "ID do sensor inválido.";
    } else {
        $id = $idValidado;
    }
}

// EXCLUIR SENSOR
if ($_SERVER["REQUEST_METHOD"] === "POST" && $acao === "excluir") {
    if ($id < 1) {
        $erro = "Informe um ID válido para excluir.";
    } else {
        try {
            $stmt = $conn->prepare(
                "DELETE FROM sensores WHERE id = ?"
            );
            $stmt->bind_param("i", $id);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                $stmt->close();
                header("Location: cadastro_item.php?sucesso=excluido");
                exit;
            }

            $stmt->close();
            $erro = "Sensor não encontrado.";

        } catch (mysqli_sql_exception $e) {
            error_log($e->getMessage());

            $erro = $e->getCode() === 1451
                ? "Não é possível excluir: o sensor está vinculado a outros registros."
                : "Erro ao excluir o sensor.";
        }
    }
}

// CARREGAR SENSOR PARA EDIÇÃO
if (
    $_SERVER["REQUEST_METHOD"] !== "POST"
    && $id > 0
    && $erro === ""
) {
    try {
        $stmt = $conn->prepare(
            "SELECT nome, localizacao, tipo_dado
             FROM sensores
             WHERE id = ?"
        );
        $stmt->bind_param("i", $id);
        $stmt->execute();

        $sensor = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($sensor) {
            $nome = $sensor["nome"];
            $localizacao = $sensor["localizacao"];
            $tipoDado = $sensor["tipo_dado"];
        } else {
            $erro = "Sensor não encontrado.";
            $id = 0;
        }

    } catch (mysqli_sql_exception $e) {
        error_log($e->getMessage());
        $erro = "Erro ao carregar os dados do sensor.";
    }
}

// CADASTRAR OU EDITAR SENSOR
if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    && ($acao === "salvar")
) {
    $nome = trim($_POST["nome"] ?? "");
    $localizacao = trim($_POST["localizacao"] ?? "");
    $tipoDado = trim($_POST["tipo_dado"] ?? "");

    if ($nome === "" || $localizacao === "" || $tipoDado === "") {
        $erro = "Preencha todos os campos.";

    } elseif (
        mb_strlen($nome) > 100 ||
        mb_strlen($localizacao) > 150 ||
        mb_strlen($tipoDado) > 100
    ) {
        $erro = "Um dos campos ultrapassou o tamanho permitido.";

    } else {
        try {
            if ($id > 0) {
                // ATUALIZAR SENSOR
                $stmt = $conn->prepare(
                    "UPDATE sensores
                     SET nome = ?, localizacao = ?, tipo_dado = ?
                     WHERE id = ?"
                );

                $stmt->bind_param(
                    "sssi",
                    $nome,
                    $localizacao,
                    $tipoDado,
                    $id
                );

                $stmt->execute();
                $stmt->close();

                header("Location: cadastro_item.php?sucesso=editado");
                exit;

            } else {
                // INSERIR SENSOR
                $stmt = $conn->prepare(
                    "INSERT INTO sensores
                     (nome, localizacao, tipo_dado)
                     VALUES (?, ?, ?)"
                );

                $stmt->bind_param(
                    "sss",
                    $nome,
                    $localizacao,
                    $tipoDado
                );

                $stmt->execute();
                $stmt->close();

                header("Location: cadastro_item.php?sucesso=cadastrado");
                exit;
            }

        } catch (mysqli_sql_exception $e) {
            error_log($e->getMessage());

            if ($e->getCode() === 1062) {
                $erro = "Já existe um sensor com esses dados.";
            } elseif ($e->getCode() === 1452) {
                $erro = "O sensor precisa estar associado a um trem válido. Verifique o banco de dados.";
            } else {
                $erro = "Não foi possível salvar o sensor.";
            }
        }
    }
}

// MENSAGENS DE SUCESSO
$mensagens = [
    "cadastrado" => "Sensor cadastrado com sucesso!",
    "editado" => "Sensor atualizado com sucesso!",
    "excluido" => "Sensor excluído com sucesso!"
];

$sucesso = $mensagens[$_GET["sucesso"] ?? ""] ?? "";

// LISTAR TODOS OS SENSORES
$sensores = [];

try {
    $resultado = $conn->query(
        "SELECT id, nome, localizacao, tipo_dado
         FROM sensores
         ORDER BY id DESC"
    );

    $sensores = $resultado->fetch_all(MYSQLI_ASSOC);

} catch (mysqli_sql_exception $e) {
    error_log($e->getMessage());
    $erro = "Não foi possível carregar a lista de sensores.";
}

$modoEdicao = ($id > 0 && $acao !== "excluir");
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Gerenciar Sensores - Ferrorama</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
</head>

<body class="bg-light">

<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Gerenciar Sensores</h1>
        <a href="sistema.php" class="btn btn-outline-secondary">
            Voltar ao sistema
        </a>
    </div>

    <?php if ($sucesso !== ""): ?>
        <div class="alert alert-success">
            <?= escapar($sucesso) ?>
        </div>
    <?php endif; ?>

    <?php if ($erro !== ""): ?>
        <div class="alert alert-danger">
            <?= escapar($erro) ?>
        </div>
    <?php endif; ?>

    <!-- FORMULÁRIO DE CADASTRO E EDIÇÃO -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white">
            <h2 class="h5 mb-0">
                <?= $modoEdicao ? "Editar sensor #" . $id : "Cadastrar sensor" ?>
            </h2>
        </div>

        <div class="card-body">
            <form method="POST" action="cadastro_item.php">

                <input type="hidden" name="acao" value="salvar">

                <?php if ($modoEdicao): ?>
                    <input type="hidden" name="id" value="<?= $id ?>">
                <?php endif; ?>

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
                        value="<?= escapar($nome) ?>"
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
                        value="<?= escapar($localizacao) ?>"
                        required
                    >
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
                        value="<?= escapar($tipoDado) ?>"
                        required
                    >
                </div>

                <button type="submit" class="btn btn-primary">
                    <?= $modoEdicao ? "Salvar alterações" : "Cadastrar sensor" ?>
                </button>

                <?php if ($modoEdicao): ?>
                    <a href="cadastro_item.php" class="btn btn-secondary">
                        Cancelar edição
                    </a>
                <?php endif; ?>

            </form>
        </div>
    </div>

    <!-- LISTAGEM E EXCLUSÃO -->
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h2 class="h5 mb-0">Sensores cadastrados</h2>
        </div>

        <div class="table-responsive">
            <table class="table table-striped table-hover mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Localização</th>
                        <th>Tipo de dado</th>
                        <th>Ações</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (empty($sensores)): ?>
                        <tr>
                            <td colspan="5" class="text-center">
                                Nenhum sensor cadastrado.
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($sensores as $s): ?>
                        <tr>
                            <td><?= (int)$s["id"] ?></td>
                            <td><?= escapar($s["nome"]) ?></td>
                            <td><?= escapar($s["localizacao"]) ?></td>
                            <td><?= escapar($s["tipo_dado"]) ?></td>

                            <td class="text-nowrap">
                                <a
                                    href="cadastro_item.php?id=<?= (int)$s["id"] ?>"
                                    class="btn btn-sm btn-primary"
                                >
                                    Editar
                                </a>

                                <form
                                    method="POST"
                                    action="cadastro_item.php"
                                    class="d-inline"
                                    onsubmit="return confirm('Deseja excluir este sensor?');"
                                >
                                    <input
                                        type="hidden"
                                        name="acao"
                                        value="excluir"
                                    >

                                    <input
                                        type="hidden"
                                        name="id"
                                        value="<?= (int)$s["id"] ?>"
                                    >

                                    <button
                                        type="submit"
                                        class="btn btn-sm btn-danger"
                                    >
                                        Excluir
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

</body>
</html>
