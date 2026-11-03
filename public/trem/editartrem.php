
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

    <style>
        :root {
            --navy: #0d1b2a;
            --navy-light: #172a42;
            --primary: #3324d9;
            --primary-light: #eeedff;
            --background: #f1f3f8;
            --surface: #ffffff;
            --text: #111c30;
            --muted: #728098;
            --border: #e0e6f0;
            --green: #16875a;
            --red: #d9364b;
            --shadow: 0 4px 16px rgba(16, 32, 58, 0.045);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Roboto", sans-serif;
            background: var(--background);
            color: var(--text);
            font-size: 14px;
            -webkit-font-smoothing: antialiased;
        }

        a {
            text-decoration: none;
        }

        button,
        input,
        select {
            font: inherit;
        }

        /* Cabeçalho superior */

        .navbar {
            background: var(--navy);
            color: white;
            border-bottom: 1px solid rgba(255,255,255,.08);
        }

        .navbar-inner {
            width: min(1400px, calc(100% - 64px));
            min-height: 76px;
            margin: auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            color: white;
        }

        .brand-icon {
            width: 42px;
            height: 42px;
            border-radius: 13px;
            background: white;
            color: var(--primary);
            display: grid;
            place-items: center;
        }

        .train-svg {
            width: 26px;
            height: 26px;
            display: block;
            fill: none;
            stroke: currentColor;
            stroke-width: 1.8;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .brand-name {
            font-size: 18px;
            font-weight: 800;
            letter-spacing: -.5px;
        }

        .brand-subtitle {
            margin-top: 3px;
            font-size: 11px;
            color: #b9c8e5;
        }

        .user-area {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #e1e8f6;
            font-size: 12px;
        }

        .user-icon {
            width: 36px;
            height: 36px;
            display: grid;
            place-items: center;
            border-radius: 50%;
            background: #1a2e47;
            font-size: 17px;
        }

        /* Destaque */

        .hero {
            background:
                radial-gradient(
                    circle at 85% 15%,
                    rgba(92, 75, 230, .22),
                    transparent 35%
                ),
                linear-gradient(115deg, #0d1b2a, #172443);
            color: white;
            padding: 32px 0;
        }

        .hero-inner {
            width: min(1400px, calc(100% - 64px));
            margin: auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
        }

        .eyebrow {
            display: flex;
            align-items: center;
            gap: 9px;
            margin-bottom: 12px;
            color: #bcb5ff;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }

        .eyebrow::before {
            content: "";
            width: 20px;
            height: 2px;
            background: #8f83ff;
            border-radius: 5px;
        }

        .hero h1 {
            margin: 0;
            font-size: clamp(27px, 3vw, 36px);
            font-weight: 800;
            letter-spacing: -1px;
        }

        .hero h1 span {
            color: #a99dff;
        }

        .hero p {
            margin: 10px 0 0;
            color: #c5d0e2;
            line-height: 1.7;
        }

        /* Botões */

        .btn {
            min-height: 40px;
            padding: 0 16px;
            border-radius: 9px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            border: 1px solid transparent;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            transition: background .2s, transform .2s,
                border-color .2s;
            white-space: nowrap;
        }

        .btn:hover {
            transform: translateY(-1px);
        }

        .btn-primary {
            color: white;
            background: var(--primary);
            border-color: var(--primary);
        }

        .btn-primary:hover {
            color: white;
            background: #281ab7;
            border-color: #281ab7;
        }

        .btn-hero {
            color: white;
            border-color: rgba(255,255,255,.25);
            background: rgba(255,255,255,.07);
        }

        .btn-hero:hover {
            color: white;
            background: rgba(255,255,255,.14);
        }

        .btn-outline {
            color: #56647b;
            background: white;
            border-color: #d5dce8;
        }

        .btn-outline:hover {
            color: var(--text);
            background: #f7f8fc;
            border-color: #aab5c8;
        }

        /* Conteúdo */

        .main {
            width: min(1100px, calc(100% - 64px));
            margin: 30px auto 55px;
        }

        .section-heading {
            margin-bottom: 19px;
        }

        .section-heading h2 {
            margin: 0;
            font-size: 19px;
            font-weight: 800;
            letter-spacing: -.4px;
        }

        .section-heading p {
            margin: 6px 0 0;
            color: var(--muted);
            font-size: 13px;
        }

        /* Cartão do formulário */

        .form-card {
            padding: 25px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 14px;
            box-shadow: var(--shadow);
        }

        .form-header {
            display: flex;
            align-items: center;
            gap: 13px;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border);
        }

        .form-icon {
            width: 46px;
            height: 46px;
            flex-shrink: 0;
            display: grid;
            place-items: center;
            border-radius: 12px;
            color: var(--primary);
            background: var(--primary-light);
        }

        .form-icon svg {
            width: 25px;
            height: 25px;
            stroke: currentColor;
            fill: none;
            stroke-width: 1.8;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .form-header h3 {
            margin: 0;
            font-size: 16px;
            font-weight: 800;
        }

        .form-header p {
            margin: 5px 0 0;
            color: var(--muted);
            font-size: 12px;
        }

        /* Campos */

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 21px 22px;
        }

        .field {
            min-width: 0;
        }

        .field-full {
            grid-column: 1 / -1;
        }

        .field label {
            display: block;
            margin-bottom: 8px;
            color: #354258;
            font-size: 13px;
            font-weight: 700;
        }

        .control {
            width: 100%;
            height: 43px;
            padding: 0 13px;
            color: var(--text);
            background: white;
            border: 1px solid #dce2ec;
            border-radius: 9px;
            outline: none;
            transition: border-color .2s, box-shadow .2s;
        }

        .control::placeholder {
            color: #9aa5b7;
        }

        .control:focus {
            border-color: #8275ff;
            box-shadow: 0 0 0 3px rgba(83,65,220,.1);
        }

        .help-text {
            display: block;
            margin-top: 7px;
            color: var(--muted);
            font-size: 12px;
            line-height: 1.5;
        }

        /* Mensagem de erro */

        .alert {
            padding: 14px 17px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 10px;
            line-height: 1.5;
        }

        .alert-danger {
            background: #fff0f1;
            color: #a92939;
            border-color: #ffd5da;
        }

        /* Ações */

        .form-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 28px;
            padding-top: 21px;
            border-top: 1px solid var(--border);
        }

        .footer {
            padding: 22px 0 0;
            text-align: center;
            color: #8b96a9;
            font-size: 11px;
        }

        /* Responsividade */

        @media (max-width: 700px) {
            .navbar-inner,
            .hero-inner {
                width: calc(100% - 32px);
            }

            .main {
                width: calc(100% - 32px);
                margin-top: 22px;
            }

            .hero-inner {
                align-items: flex-start;
                flex-direction: column;
            }

            .form-card {
                padding: 19px;
            }

            .form-grid {
                grid-template-columns: 1fr;
                gap: 18px;
            }

            .field-full {
                grid-column: auto;
            }
        }

        @media (max-width: 400px) {
            .user-area {
                font-size: 0;
            }

            .user-icon {
                font-size: 17px;
            }

            .form-actions .btn {
                flex: 1;
            }
        }
    </style>
</head>

<body>

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
