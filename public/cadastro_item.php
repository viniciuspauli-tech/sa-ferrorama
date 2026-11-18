
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

    
<link rel="stylesheet" href="../style/style.css?v=3">
</head>

<body class="page-public_cadastro_item_php">

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

        <div class="inline-style-1">

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
