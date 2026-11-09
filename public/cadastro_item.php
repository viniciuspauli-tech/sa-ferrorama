
<?php
require_once __DIR__ . "/../infra/adm.php";
require_once __DIR__ . "/../infra/connect.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function escapar($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, "UTF-8");
}

$erro = "";

$mensagens = [
    "cadastrado" => "Sensor cadastrado com sucesso!",
    "editado" => "Sensor atualizado com sucesso!",
    "excluido" => "Sensor excluído com sucesso!"
];

$sucesso = $mensagens[$_GET["sucesso"] ?? ""] ?? "";

// EXCLUIR SENSOR
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["acao"] ?? "") === "excluir") {

    $id = filter_var($_POST["id"] ?? null, FILTER_VALIDATE_INT);

    if (!$id || $id < 1) {
        $erro = "ID do sensor inválido.";
    } else {
        try {
            $stmt = $conn->prepare("DELETE FROM sensores WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();

            $excluido = $stmt->affected_rows > 0;
            $stmt->close();

            if ($excluido) {
                header("Location: cadastro_item.php?sucesso=excluido");
                exit;
            }

            $erro = "Sensor não encontrado.";

        } catch (mysqli_sql_exception $e) {
            error_log($e->getMessage());

            $erro = $e->getCode() === 1451
                ? "Não é possível excluir: o sensor está vinculado a outros registros."
                : "Erro ao excluir o sensor.";
        }
    }
}

// LISTAR SENSORES
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
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Gerenciamento de Sensores - Ferrorama</title>

    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            min-height: 100vh;
            font-family: "Roboto", sans-serif;
            color: #132238;
            background: #f2f4f9;
        }

        a {
            text-decoration: none;
        }

        /* CABEÇALHO ORIGINAL FERRORAMA */

        .topbar {
            background: #0c192b;
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
                radial-gradient(circle at 85% 20%, rgba(101,84,232,.25), transparent 38%),
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

        .hero h1 span {
            color: #a99cff;
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
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            margin-bottom: 24px;
        }

        .page-heading h2 {
            font-size: 20px;
        }

        .page-heading p {
            margin-top: 6px;
            color: #73819a;
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
            background: #6554e8;
            box-shadow: 0 5px 12px rgba(101,84,232,.15);
        }

        .button-primary:hover {
            color: white;
            background: #5140d4;
        }

        .button-secondary {
            color: #132238;
            background: white;
            border-color: #dfe5ef;
        }

        .button-secondary:hover {
            color: #5140d4;
            border-color: #c9c2ff;
        }

        .button-danger {
            color: #b52d3b;
            background: white;
            border-color: #f4d3d7;
        }

        .button-danger:hover {
            color: white;
            background: #b52d3b;
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
            color: #18794e;
            background: #eaf8f0;
            border: 1px solid #c9ead6;
        }

        .alert-error {
            color: #b52d3b;
            background: #fff0f1;
            border: 1px solid #f4d3d7;
        }

        /* CARTÃO */

        .content-card {
            margin-bottom: 22px;
            overflow: hidden;
            background: white;
            border: 1px solid #dfe5ef;
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
        }

        .card-heading p {
            margin-top: 5px;
            color: #73819a;
            font-size: 12px;
        }

        .card-icon {
            width: 40px;
            height: 40px;
            display: grid;
            place-items: center;
            flex-shrink: 0;
            color: #4b3ac8;
            background: #eeecff;
            border-radius: 12px;
        }

        .card-icon svg {
            width: 21px;
            height: 21px;
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
            border-bottom: 1px solid #dfe5ef;
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
            color: #132238;
            font-weight: 700;
        }

        .actions {
            display: flex;
            align-items: center;
            gap: 7px;
        }

        .actions form {
            margin: 0;
        }

        .empty-state {
            padding: 45px 20px;
            color: #73819a;
            text-align: center;
        }

        .empty-icon {
            width: 48px;
            height: 48px;
            margin: 0 auto 12px;
            display: grid;
            place-items: center;
            color: #6554e8;
            background: #eeecff;
            border-radius: 14px;
        }

        .empty-icon svg {
            width: 24px;
            height: 24px;
        }

        .empty-state strong {
            display: block;
            margin-bottom: 5px;
            color: #132238;
            font-size: 14px;
        }

        .empty-state span {
            font-size: 12px;
        }

        .footer {
            margin-top: 30px;
            color: #8a96aa;
            text-align: center;
            font-size: 11px;
        }

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

            .page-heading {
                align-items: flex-start;
                flex-direction: column;
            }

            .card-heading {
                padding: 17px;
            }

            th, td {
                padding: 13px 12px;
            }

            .actions {
                flex-direction: column;
                align-items: stretch;
            }
        }

        @media (max-width: 480px) {
            .top-label {
                font-size: 0;
            }

            .brand-name {
                font-size: 17px;
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
                <!-- MESMO SÍMBOLO DO CÓDIGO ORIGINAL -->
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

        <h1>Gerenciar <span>Sensores.</span></h1>

        <p>
            Consulte e gerencie os sensores responsáveis
            pelo monitoramento das informações do sistema ferroviário.
        </p>

    </div>
</section>

<!-- CONTEÚDO -->
<main class="main">

    <div class="page-heading">

        <div>
            <h2>Gerenciamento de sensores</h2>
            <p>Consulte e gerencie os sensores cadastrados no Ferrorama.</p>
        </div>

        <div style="display:flex; gap:10px; flex-wrap:wrap;">

            <a href="sistema.php" class="button button-secondary">
                ← Voltar ao sistema
            </a>

            <a href="cadastrar_sensor.php" class="button button-primary">
                + Cadastrar sensor
            </a>

        </div>

    </div>

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

    <!-- LISTAGEM -->
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
                                            <path d="M8 9h8M8 13h8M8 17h4"></path>
                                        </svg>
                                    </div>

                                    <strong>Nenhum sensor cadastrado.</strong>

                                    <span>
                                        Os sensores adicionados aparecerão nesta lista.
                                    </span>

                                </div>

                            </td>
                        </tr>

                    <?php else: ?>

                        <?php foreach ($sensores as $sensor): ?>

                            <tr>

                                <td class="sensor-id">
                                    #<?= (int)$sensor["id"] ?>
                                </td>

                                <td class="sensor-name">
                                    <?= escapar($sensor["nome"]) ?>
                                </td>

                                <td>
                                    <?= escapar($sensor["localizacao"]) ?>
                                </td>

                                <td>
                                    <?= escapar($sensor["tipo_dado"]) ?>
                                </td>

                                <td>

                                    <div class="actions">

                                        <a
                                            href="editar_sensor.php?id=<?= (int)$sensor["id"] ?>"
                                            class="button button-secondary button-small"
                                        >
                                            Editar
                                        </a>

                                        <form
                                            method="POST"
                                            action="cadastro_item.php"
                                            onsubmit="return confirm('Deseja realmente excluir este sensor?');"
                                        >

                                            <input type="hidden" name="acao" value="excluir">

                                            <input
                                                type="hidden"
                                                name="id"
                                                value="<?= (int)$sensor["id"] ?>"
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

                    <?php endif; ?>

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
