
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

/*
|--------------------------------------------------------------------------
| Verificar se a coluna responsavel_id existe
|--------------------------------------------------------------------------
| Isso permite que a tela funcione mesmo se essa coluna ainda não existir
| na tabela trens.
*/

$resultadoColuna = $conn->query(
    "SHOW COLUMNS FROM trens LIKE 'responsavel_id'"
);

$temResponsavel = $resultadoColuna
    && $resultadoColuna->num_rows > 0;

/*
|--------------------------------------------------------------------------
| Buscar trem
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("SELECT * FROM trens WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

$resultado = $stmt->get_result();
$trem = $resultado->fetch_assoc();

$stmt->close();

if (!$trem) {
    header("Location: listagemtrem.php?erro=nao_encontrado");
    exit;
}

/*
|--------------------------------------------------------------------------
| Buscar usuários responsáveis, se a coluna existir
|--------------------------------------------------------------------------
*/

$usuarios = [];

if ($temResponsavel) {
    $resultadoUsuarios = $conn->query(
        "SELECT id, nome, email FROM usuarios ORDER BY nome ASC"
    );

    if (!$resultadoUsuarios) {
        error_log($conn->error);
        die("Não foi possível carregar os usuários.");
    }

    $usuarios = $resultadoUsuarios->fetch_all(MYSQLI_ASSOC);
}

/*
|--------------------------------------------------------------------------
| Valores iniciais
|--------------------------------------------------------------------------
*/

$identificador = $trem["identificador"];
$modelo = $trem["modelo"];
$status = $trem["status"];
$velocidade = $trem["velocidade_atual"];
$localizacao = $trem["localizacao_atual"] ?? "";
$consumo = $trem["consumo_energia"];
$responsavelId = $trem["responsavel_id"] ?? "";

/*
|--------------------------------------------------------------------------
| Processar formulário
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $identificador = trim($_POST["identificador"] ?? "");
    $modelo = trim($_POST["modelo"] ?? "");
    $status = $_POST["status"] ?? "";
    $velocidade = trim($_POST["velocidade_atual"] ?? "");
    $localizacao = trim($_POST["localizacao_atual"] ?? "");
    $consumo = trim($_POST["consumo_energia"] ?? "");

    $responsavelId = $temResponsavel
        ? ($_POST["responsavel_id"] ?? "")
        : "";

    $statusValidos = [
        "ativo",
        "inativo",
        "manutencao",
        "falha"
    ];

    if ($identificador === "" || $modelo === "") {

        $mensagemErro = "Preencha o identificador e o modelo do trem.";

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
        (float) $velocidade > 9999.99
    ) {

        $mensagemErro = "Informe uma velocidade entre 0 e 9999,99 km/h.";

    } elseif (
        !is_numeric($consumo) ||
        (float) $consumo < 0 ||
        (float) $consumo > 999999.99
    ) {

        $mensagemErro = "Informe um consumo válido entre 0 e 999999,99 kWh.";

    } else {

        $responsavel = null;

        /*
        | Validar usuário responsável
        */

        if ($temResponsavel && $responsavelId !== "") {

            $responsavel = filter_var(
                $responsavelId,
                FILTER_VALIDATE_INT
            );

            if ($responsavel === false || $responsavel <= 0) {

                $mensagemErro = "Selecione um responsável válido.";

            } else {

                $stmtUsuario = $conn->prepare(
                    "SELECT id FROM usuarios WHERE id = ?"
                );

                $stmtUsuario->bind_param("i", $responsavel);
                $stmtUsuario->execute();

                $usuarioExiste =
                    $stmtUsuario->get_result()->num_rows > 0;

                $stmtUsuario->close();

                if (!$usuarioExiste) {
                    $mensagemErro =
                        "O usuário responsável selecionado não existe.";
                }
            }
        }

        /*
        | Atualizar registro
        */

        if ($mensagemErro === "") {

            $velocidade = (float) $velocidade;
            $consumo = (float) $consumo;

            $localizacaoBanco = $localizacao === ""
                ? null
                : $localizacao;

            try {

                if ($temResponsavel) {

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

                    if (!$stmt) {
                        throw new Exception(
                            "Falha ao preparar a atualização."
                        );
                    }

                    $stmt->bind_param(
                        "sssdsdii",
                        $identificador,
                        $modelo,
                        $status,
                        $velocidade,
                        $localizacaoBanco,
                        $consumo,
                        $responsavel,
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
                            consumo_energia = ?
                        WHERE id = ?
                    ";

                    $stmt = $conn->prepare($sql);

                    if (!$stmt) {
                        throw new Exception(
                            "Falha ao preparar a atualização."
                        );
                    }

                    $stmt->bind_param(
                        "sssdsdi",
                        $identificador,
                        $modelo,
                        $status,
                        $velocidade,
                        $localizacaoBanco,
                        $consumo,
                        $id
                    );
                }

                $stmt->execute();
                $stmt->close();

                header("Location: listagemtrem.php?sucesso=editado");
                exit;

            } catch (Throwable $e) {

                if (isset($stmt) && $stmt instanceof mysqli_stmt) {
                    $stmt->close();
                }

                if (
                    $e instanceof mysqli_sql_exception &&
                    $e->getCode() === 1062
                ) {

                    $mensagemErro =
                        "Já existe um trem com esse identificador.";

                } else {

                    error_log($e->getMessage());

                    $mensagemErro =
                        "Não foi possível atualizar o trem. Verifique os dados.";
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

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

    
<link rel="stylesheet" href="../../style/style.css?v=3">
</head>

<body class="page-public_trem_editartrem_php">

<!-- Barra superior -->
<header class="navbar">
    <div class="navbar-inner">

        <a href="../sistema.php" class="brand">

            <div class="brand-icon">
                <svg class="train-svg" viewBox="0 0 24 24"
                     xmlns="http://www.w3.org/2000/svg">
                    <rect x="5" y="3" width="14" height="15" rx="4"/>
                    <path d="M8 7h8M8 12h8M8 18l-2 3M16 18l2 3M5 15h14"/>
                    <circle cx="9" cy="15" r="0.5"/>
                    <circle cx="15" cy="15" r="0.5"/>
                </svg>
            </div>

            <div>
                <div class="brand-name">Ferrorama</div>
                <div class="brand-subtitle">Sistema ferroviário</div>
            </div>

        </a>

        <div class="user-area">
            <div class="user-icon">♙</div>
            <span>Área do usuário Administrador</span>
        </div>

    </div>
</header>

<!-- Cabeçalho da página -->
<section class="hero">
    <div class="hero-inner">

        <div>
            <div class="eyebrow">Gestão ferroviária</div>

            <h1>
                Editar <span>trem.</span>
            </h1>

            <p>
                Atualize as informações cadastrais e operacionais
                do veículo ferroviário.
            </p>
        </div>

        <div>
            <a href="listagemtrem.php" class="btn btn-hero">
                ← Voltar à frota
            </a>
        </div>

    </div>
</section>

<main class="main">

    <div class="section-heading">
        <h2>Informações do trem</h2>
        <p>
            Edite os dados cadastrais e operacionais abaixo.
        </p>
    </div>

    <?php if ($mensagemErro !== ""): ?>
        <div class="alert alert-danger" role="alert">
            <?= escapar($mensagemErro) ?>
        </div>
    <?php endif; ?>

    <section class="form-card">

        <div class="form-header">

            <div class="form-icon">
                <svg viewBox="0 0 24 24"
                     xmlns="http://www.w3.org/2000/svg">
                    <rect x="5" y="3" width="14" height="15" rx="4"/>
                    <path d="M8 7h8M8 12h8M8 18l-2 3M16 18l2 3M5 15h14"/>
                    <circle cx="9" cy="15" r="0.5"/>
                    <circle cx="15" cy="15" r="0.5"/>
                </svg>
            </div>

            <div>
                <h3>Dados cadastrais e operacionais</h3>
                <p>
                    Confira os campos e salve as alterações realizadas.
                </p>
            </div>

        </div>

        <form
            method="POST"
            action="editartrem.php?id=<?= (int) $id ?>"
        >

            <div class="form-grid">

                <div class="field">
                    <label for="identificador">
                        Identificador
                    </label>

                    <input
                        type="text"
                        id="identificador"
                        name="identificador"
                        class="control"
                        maxlength="50"
                        value="<?= escapar($identificador) ?>"
                        placeholder="Ex.: TR-001"
                        required
                    >
                </div>

                <div class="field">
                    <label for="modelo">Modelo</label>

                    <input
                        type="text"
                        id="modelo"
                        name="modelo"
                        class="control"
                        maxlength="100"
                        value="<?= escapar($modelo) ?>"
                        placeholder="Ex.: Expresso 4000"
                        required
                    >
                </div>

                <div class="field">
                    <label for="status">
                        Status operacional
                    </label>

                    <select
                        id="status"
                        name="status"
                        class="control"
                        required
                    >
                        <option value="ativo"
                            <?= $status === "ativo" ? "selected" : "" ?>>
                            Ativo
                        </option>

                        <option value="inativo"
                            <?= $status === "inativo" ? "selected" : "" ?>>
                            Inativo
                        </option>

                        <option value="manutencao"
                            <?= $status === "manutencao" ? "selected" : "" ?>>
                            Manutenção
                        </option>

                        <option value="falha"
                            <?= $status === "falha" ? "selected" : "" ?>>
                            Falha
                        </option>
                    </select>
                </div>

                <div class="field">
                    <label for="velocidade_atual">
                        Velocidade atual (km/h)
                    </label>

                    <input
                        type="number"
                        id="velocidade_atual"
                        name="velocidade_atual"
                        class="control"
                        step="0.01"
                        min="0"
                        max="9999.99"
                        value="<?= escapar($velocidade) ?>"
                        required
                    >
                </div>

                <div class="field">
                    <label for="localizacao_atual">
                        Localização atual
                    </label>

                    <input
                        type="text"
                        id="localizacao_atual"
                        name="localizacao_atual"
                        class="control"
                        maxlength="150"
                        value="<?= escapar($localizacao) ?>"
                        placeholder="Ex.: KM 12 - Trecho Norte"
                    >
                </div>

                <div class="field">
                    <label for="consumo_energia">
                        Consumo de energia (kWh)
                    </label>

                    <input
                        type="number"
                        id="consumo_energia"
                        name="consumo_energia"
                        class="control"
                        step="0.01"
                        min="0"
                        max="999999.99"
                        value="<?= escapar($consumo) ?>"
                        required
                    >

                    <small class="help-text">
                        Consumo de energia registrado para este trem.
                    </small>
                </div>

                <?php if ($temResponsavel): ?>

                    <div class="field field-full">
                        <label for="responsavel_id">
                            Usuário responsável
                        </label>

                        <select
                            id="responsavel_id"
                            name="responsavel_id"
                            class="control"
                        >

                            <option value="">
                                Nenhum responsável
                            </option>

                            <?php foreach ($usuarios as $usuario): ?>

                                <option
                                    value="<?= (int) $usuario["id"] ?>"
                                    <?= (string) $responsavelId ===
                                        (string) $usuario["id"]
                                        ? "selected"
                                        : "" ?>
                                >
                                    <?= escapar($usuario["nome"]) ?>
                                    (<?= escapar($usuario["email"]) ?>)
                                </option>

                            <?php endforeach; ?>

                        </select>

                        <small class="help-text">
                            Selecione o usuário responsável pelo trem.
                        </small>
                    </div>

                <?php endif; ?>

            </div>

            <div class="form-actions">

                <a
                    href="listagemtrem.php"
                    class="btn btn-outline"
                >
                    Voltar
                </a>

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    ✓ Salvar alterações
                </button>

            </div>

        </form>

    </section>

    <footer class="footer">
        Ferrorama · Sistema de gerenciamento ferroviário
    </footer>

</main>

</body>
</html>
