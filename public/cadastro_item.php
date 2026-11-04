
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
    && $acao === "salvar"
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Gerenciar Sensores - Ferrorama</title>

    <link
        href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

    <style>
        :root {
            --navy: #0c192b;
            --purple: #6554e8;
            --purple-dark: #5140d4;
            --purple-light: #eeecff;
            --background: #f2f4f9;
            --white: #ffffff;
            --text: #132238;
            --muted: #73819a;
            --border: #dfe5ef;
            --danger: #b52d3b;
            --danger-bg: #fff0f1;
            --success: #18794e;
            --success-bg: #eaf8f0;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            min-height: 100vh;
            font-family: "Roboto", sans-serif;
            color: var(--text);
            background: var(--background);
        }

        a {
            text-decoration: none;
        }

        /* CABEÇALHO */

        .topbar {
            background: var(--navy);
            color: white;
            border-bottom: 1px solid rgba(255,255,255,.08);
        }

        .topbar-inner {
            max-width: 1500px;
            min-height: 76px;
            margin: auto;
            padding: 0 28px;
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
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            border-radius: 13px;
            background: white;
            color: #2415d5;
        }

        .brand-icon svg {
            width: 26px;
            height: 26px;
            display: block;
        }

        .brand-name {
            font-size: 19px;
            font-weight: 800;
            letter-spacing: -.5px;
        }

        .brand-subtitle {
            margin-top: 3px;
            color: #b6c6e4;
            font-size: 11px;
        }

        .top-label {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #edf2ff;
            font-size: 13px;
        }

        .top-avatar {
            width: 38px;
            height: 38px;
            display: grid;
            place-items: center;
            border-radius: 50%;
            background: #1c2d45;
        }

        /* DESTAQUE */

        .hero {
            padding: 45px 24px 48px;
            color: white;
            background:
                radial-gradient(
                    circle at 85% 20%,
                    rgba(101,84,232,.25),
                    transparent 38%
                ),
                linear-gradient(115deg, #0c192b, #17243e);
        }

        .hero-inner {
            max-width: 1068px;
            margin: auto;
        }

        .eyebrow {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
            color: #c5c0ff;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 1.4px;
            text-transform: uppercase;
        }

        .eyebrow::before {
            content: "";
            width: 20px;
            height: 2px;
            background: #9286ff;
        }

        .hero h1 {
            margin-bottom: 12px;
            font-size: clamp(28px, 4vw, 36px);
            letter-spacing: -1px;
        }

        .hero p {
            max-width: 620px;
            color: #c7d2e7;
            font-size: 14px;
            line-height: 1.8;
        }

        /* CONTEÚDO */

        .main {
            max-width: 1116px;
            margin: auto;
            padding: 34px 24px 60px;
        }

        .page-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 24px;
        }

        .page-heading h2 {
            font-size: 20px;
            font-weight: 700;
        }

        .page-heading p {
            margin-top: 6px;
            color: var(--muted);
            font-size: 13px;
        }

        /* BOTÕES */

        .button {
            min-height: 42px;
            padding: 0 17px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: 1px solid transparent;
            border-radius: 9px;
            font-family: inherit;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            transition: .2s ease;
        }

        .button:hover {
            transform: translateY(-1px);
        }

        .button-primary {
            color: white;
            background: var(--purple);
            box-shadow: 0 5px 12px rgba(101,84,232,.15);
        }

        .button-primary:hover {
            color: white;
            background: var(--purple-dark);
        }

        .button-secondary {
            color: var(--text);
            background: white;
            border-color: var(--border);
        }

        .button-secondary:hover {
            color: var(--purple);
            border-color: #c9c2ff;
        }

        .button-danger {
            color: var(--danger);
            background: var(--danger-bg);
            border-color: #f4d3d7;
        }

        .button-danger:hover {
            color: white;
            background: var(--danger);
        }

        .button-small {
            min-height: 34px;
            padding: 0 12px;
            font-size: 12px;
        }

        /* ALERTAS */

        .alert {
            margin-bottom: 20px;
            padding: 14px 16px;
            border-radius: 10px;
            font-size: 13px;
            line-height: 1.6;
        }

        .alert-success {
            color: var(--success);
            background: var(--success-bg);
            border: 1px solid #c9ead6;
        }

        .alert-error {
            color: var(--danger);
            background: var(--danger-bg);
            border: 1px solid #f4d3d7;
        }

        /* CARTÕES */

        .content-card {
            margin-bottom: 22px;
            overflow: hidden;
            background: white;
            border: 1px solid var(--border);
            border-radius: 16px;
            box-shadow: 0 5px 20px rgba(23,40,70,.035);
        }

        .card-heading {
            padding: 21px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            border-bottom: 1px solid #edf0f5;
        }

        .card-heading h3 {
            font-size: 16px;
            font-weight: 700;
        }

        .card-heading p {
            margin-top: 5px;
            color: var(--muted);
            font-size: 12px;
        }

        .card-icon {
            width: 40px;
            height: 40px;
            display: grid;
            place-items: center;
            flex-shrink: 0;
            color: #4b3ac8;
            background: var(--purple-light);
            border-radius: 12px;
        }

        .card-icon svg {
            width: 21px;
            height: 21px;
        }

        .card-body {
            padding: 25px;
        }

        /* FORMULÁRIO */

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
        }

        .field {
            min-width: 0;
        }

        .field-full {
            grid-column: 1 / -1;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-size: 13px;
            font-weight: 700;
        }

        input {
            width: 100%;
            min-height: 45px;
            padding: 11px 13px;
            color: var(--text);
            background: white;
            border: 1px solid #d8dfeb;
            border-radius: 9px;
            outline: none;
            font-family: inherit;
            font-size: 13px;
            transition: border-color .2s, box-shadow .2s;
        }

        input::placeholder {
            color: #a1adbf;
        }

        input:focus {
            border-color: var(--purple);
            box-shadow: 0 0 0 3px rgba(101,84,232,.12);
        }

        .form-actions {
            margin-top: 25px;
            padding-top: 21px;
            display: flex;
            justify-content: flex-end;
            flex-wrap: wrap;
            gap: 11px;
            border-top: 1px solid #edf0f5;
        }

        /* TABELA */

        .table-container {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        thead {
            background: #f8f9fc;
        }

        th {
            padding: 15px 18px;
            color: #46546b;
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
            border-bottom: 1px solid var(--border);
        }

        td {
            padding: 15px 18px;
            color: #35445c;
            font-size: 13px;
            border-bottom: 1px solid #edf0f5;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        tbody tr:hover {
            background: #fafaff;
        }

        .sensor-id {
            color: #5140d4;
            font-weight: 700;
        }

        .sensor-name {
            color: var(--text);
            font-weight: 700;
        }

        .empty-state {
            padding: 35px 20px;
            color: var(--muted);
            text-align: center;
        }

        .empty-icon {
            width: 48px;
            height: 48px;
            margin: 0 auto 12px;
            display: grid;
            place-items: center;
            color: #6554e8;
            background: var(--purple-light);
            border-radius: 14px;
        }

        .empty-icon svg {
            width: 24px;
            height: 24px;
        }

        .empty-state strong {
            display: block;
            margin-bottom: 5px;
            color: var(--text);
            font-size: 14px;
        }

        .empty-state span {
            font-size: 12px;
        }

        .actions {
            display: flex;
            align-items: center;
            gap: 7px;
        }

        /* RODAPÉ */

        .footer {
            margin-top: 30px;
            color: #8a96aa;
            text-align: center;
            font-size: 11px;
        }

        /* RESPONSIVIDADE */

        @media (max-width: 700px) {
            .topbar-inner {
                padding: 0 18px;
            }

            .hero {
                padding: 35px 20px;
            }

            .main {
                padding: 25px 16px 40px;
            }

            .form-grid {
                grid-template-columns: 1fr;
                gap: 17px;
            }

            .field-full {
                grid-column: auto;
            }

            .card-body {
                padding: 20px;
            }

            .page-heading {
                align-items: flex-start;
                flex-direction: column;
            }

            .form-actions {
                flex-direction: column-reverse;
            }

            .form-actions .button {
                width: 100%;
            }
        }

        @media (max-width: 480px) {
            .top-label {
                font-size: 0;
            }

            .top-avatar {
                width: 36px;
                height: 36px;
            }

            .brand-name {
                font-size: 17px;
            }

            .card-heading {
                padding: 17px;
            }

            .table-container {
                overflow-x: auto;
            }

            th, td {
                padding: 13px 12px;
            }

            .actions {
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>
</head>

<body>

<!-- CABEÇALHO -->
<header class="topbar">
    <div class="topbar-inner">

        <a href="sistema.php" class="brand">

            <span class="brand-icon">
                <svg viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="1.8"
                     stroke-linecap="round" stroke-linejoin="round"
                     aria-hidden="true">
                    <rect x="5" y="3" width="14" height="15" rx="4"></rect>
                    <path d="M8 7h8M8 12h8M8 18l-2 3M16 18l2 3M5 15h14"></path>
                    <circle cx="9" cy="15" r="1"></circle>
                    <circle cx="15" cy="15" r="1"></circle>
                </svg>
            </span>

            <div>
                <div class="brand-name">Ferrorama</div>
                <div class="brand-subtitle">Sistema ferroviário</div>
            </div>

        </a>

        <div class="top-label">
            <div class="top-avatar">
                <svg width="19" height="19" viewBox="0 0 24 24"
                     fill="none" stroke="currentColor"
                     stroke-width="1.7" stroke-linecap="round"
                     stroke-linejoin="round">
                    <circle cx="12" cy="8" r="4"></circle>
                    <path d="M5 21v-2a7 7 0 0 1 14 0v2"></path>
                </svg>
            </div>
            Área administrativa
        </div>

    </div>
</header>

<!-- DESTAQUE -->
<section class="hero">
    <div class="hero-inner">

        <div class="eyebrow">Painel de controle</div>

        <h1>Gerenciar <span style="color:#a99cff;">Sensores.</span></h1>

        <p>
            Cadastre, consulte e gerencie os sensores responsáveis
            pelo monitoramento das informações do sistema ferroviário.
        </p>

    </div>
</section>

<!-- CONTEÚDO PRINCIPAL -->
<main class="main">

    <div class="page-heading">

        <div>
            <h2>Gerenciamento de sensores</h2>
            <p>Gerencie os sensores cadastrados no sistema Ferrorama.</p>
        </div>

        <a href="sistema.php" class="button button-secondary">
            ← Voltar ao sistema
        </a>

    </div>

    <!-- MENSAGENS -->
    <?php if ($sucesso !== ""): ?>
        <div class="alert alert-success" role="alert">
            ✓ <?= escapar($sucesso) ?>
        </div>
    <?php endif; ?>

    <?php if ($erro !== ""): ?>
        <div class="alert alert-error" role="alert">
            <?= escapar($erro) ?>
        </div>
    <?php endif; ?>

    <!-- FORMULÁRIO -->
    <section class="content-card">

        <div class="card-heading">

            <div>
                <h3>
                    <?= $modoEdicao
                        ? "Editar sensor #" . $id
                        : "Cadastrar sensor" ?>
                </h3>

                <p>
                    <?= $modoEdicao
                        ? "Atualize as informações do sensor selecionado."
                        : "Preencha os campos para adicionar um novo sensor." ?>
                </p>
            </div>

            <div class="card-icon">
                <svg viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="1.8"
                     stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="9"></circle>
                    <path d="M12 8v8M8 12h8"></path>
                </svg>
            </div>

        </div>

        <div class="card-body">

            <form method="POST" action="cadastro_item.php">

                <input type="hidden" name="acao" value="salvar">

                <?php if ($modoEdicao): ?>
                    <input type="hidden" name="id" value="<?= $id ?>">
                <?php endif; ?>

                <div class="form-grid">

                    <div class="field field-full">

                        <label for="nome">Nome do sensor</label>

                        <input
                            type="text"
                            id="nome"
                            name="nome"
                            maxlength="100"
                            placeholder="Ex.: Sensor de temperatura"
                            value="<?= escapar($nome) ?>"
                            required
                        >

                    </div>

                    <div class="field field-full">

                        <label for="localizacao">Localização no trilho</label>

                        <input
                            type="text"
                            id="localizacao"
                            name="localizacao"
                            maxlength="150"
                            placeholder="Ex.: KM 12 - Trecho Norte"
                            value="<?= escapar($localizacao) ?>"
                            required
                        >

                    </div>

                    <div class="field field-full">

                        <label for="tipo_dado">Tipo de dado</label>

                        <input
                            type="text"
                            id="tipo_dado"
                            name="tipo_dado"
                            maxlength="100"
                            placeholder="Ex.: Temperatura, velocidade ou pressão"
                            value="<?= escapar($tipoDado) ?>"
                            required
                        >

                    </div>

                </div>

                <div class="form-actions">

                    <?php if ($modoEdicao): ?>

                        <a href="cadastro_item.php"
                           class="button button-secondary">
                            Cancelar edição
                        </a>

                    <?php endif; ?>

                    <button type="submit" class="button button-primary">

                        <?= $modoEdicao
                            ? "Salvar alterações →"
                            : "Cadastrar sensor →" ?>

                    </button>

                </div>

            </form>

        </div>

    </section>

    <!-- LISTAGEM DE SENSORES -->
    <section class="content-card">

        <div class="card-heading">

            <div>
                <h3>Sensores cadastrados</h3>
                <p>Consulte, edite ou exclua os sensores registrados.</p>
            </div>

            <div class="card-icon">
                <svg viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="1.8"
                     stroke-linecap="round" stroke-linejoin="round">
                    <rect x="4" y="4" width="16" height="16" rx="3"></rect>
                    <path d="M8 9h8M8 13h8M8 17h4"></path>
                </svg>
            </div>

        </div>

        <div class="table-container">

            <table>

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
                            <td colspan="5">

                                <div class="empty-state">

                                    <div class="empty-icon">
                                        <svg viewBox="0 0 24 24" fill="none"
                                             stroke="currentColor"
                                             stroke-width="1.8"
                                             stroke-linecap="round"
                                             stroke-linejoin="round">
                                            <rect x="4" y="4" width="16" height="16" rx="3"></rect>
                                            <path d="M8 9h8M8 13h8"></path>
                                        </svg>
                                    </div>

                                    <strong>Nenhum sensor cadastrado.</strong>

                                    <span>
                                        Os sensores adicionados aparecerão nesta lista.
                                    </span>

                                </div>

                            </td>
                        </tr>

                    <?php endif; ?>

                    <?php foreach ($sensores as $s): ?>

                        <tr>

                            <td class="sensor-id">
                                #<?= (int)$s["id"] ?>
                            </td>

                            <td class="sensor-name">
                                <?= escapar($s["nome"]) ?>
                            </td>

                            <td>
                                <?= escapar($s["localizacao"]) ?>
                            </td>

                            <td>
                                <?= escapar($s["tipo_dado"]) ?>
                            </td>

                            <td>

                                <div class="actions">

                                    <a
                                        href="cadastro_item.php?id=<?= (int)$s["id"] ?>"
                                        class="button button-primary button-small"
                                    >
                                        Editar
                                    </a>

                                    <form
                                        method="POST"
                                        action="cadastro_item.php"
                                        onsubmit="return confirm('Deseja realmente excluir este sensor?');"
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
                                            class="button button-danger button-small"
                                        >
                                            Excluir
                                        </button>

                                    </form>

                                </div>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    </section>

    <div class="footer">
        Ferrorama © <?= date("Y") ?> — Sistema de gerenciamento ferroviário.
    </div>

</main>

</body>
</html>
