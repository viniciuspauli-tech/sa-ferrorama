<?php
require_once __DIR__ . "/../infra/adm.php";
require_once __DIR__ . "/../infra/connect.php";
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function escapar($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, "UTF-8");
}

$erro = "";
$nome = $localizacao = $tipoDado = $rotaId = "";
$rotas = $conn->query("SELECT id, nome FROM rotas ORDER BY nome");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nome = trim($_POST["nome"] ?? "");
    $localizacao = trim($_POST["localizacao"] ?? "");
    $tipoDado = trim($_POST["tipo_dado"] ?? "");
    $rotaId = $_POST["rota_id"] ?? "";

    if ($nome === "" || $localizacao === "" || $tipoDado === "" || $rotaId === "") {
        $erro = "Preencha todos os campos.";
    } elseif (mb_strlen($nome) > 100 || mb_strlen($localizacao) > 150 || mb_strlen($tipoDado) > 100) {
        $erro = "Um dos campos ultrapassou o tamanho permitido.";
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO sensores (nome, localizacao, tipo_dado, rota_id) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sssi", $nome, $localizacao, $tipoDado, $rotaId);
            $stmt->execute();
            $stmt->close();
            header("Location: cadastro_item.php?sucesso=cadastrado");
            exit;
        } catch (mysqli_sql_exception $e) {
            error_log($e->getMessage());
            if ($e->getCode() === 1062) {
                $erro = "Já existe um sensor com esses dados.";
            } elseif ($e->getCode() === 1452) {
                $erro = "Rota inválida. Verifique o banco de dados.";
            } else {
                $erro = "Não foi possível cadastrar o sensor.";
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
    <title>Cadastrar Sensor - Ferrorama</title>
<<<<<<< HEAD
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style/style.css">
=======

    <link
        href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

    

<link rel="stylesheet" href="../style/style.css?v=3">
>>>>>>> 407f0342fb9bd9972593c7fea78bacdc199277cb
</head>
<body class="page-public_cadastrar_sensor_php">

<header class="topbar">
    <div class="topbar-inner">
        <a href="sistema.php" class="brand">
            <span class="brand-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
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
                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="8" r="4"></circle>
                    <path d="M5 21v-2a7 7 0 0 1 14 0v2"></path>
                </svg>
            </div>
            Área administrativa
        </div>
    </div>
</header>

<section class="hero">
    <div class="hero-inner">
        <div class="eyebrow">Painel de controle</div>
        <h1>Cadastrar <span>Sensor.</span></h1>
        <p>Adicione um novo sensor ao sistema para monitorar as informações e condições do sistema ferroviário.</p>
    </div>
</section>

<main class="main">
    <div class="page-heading">
        <div>
            <h2>Novo sensor</h2>
            <p>Preencha as informações para cadastrar um sensor.</p>
        </div>
        <a href="cadastro_item.php" class="button button-secondary">← Voltar aos sensores</a>
    </div>

    <?php if ($erro !== ""): ?>
        <div class="alert alert-error" role="alert"><?= escapar($erro) ?></div>
    <?php endif; ?>

    <section class="content-card">
        <div class="card-heading">
            <div>
                <h3>Cadastrar sensor</h3>
                <p>Preencha os campos para adicionar um novo sensor.</p>
            </div>
            <div class="card-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="9"></circle>
                    <path d="M12 8v8M8 12h8"></path>
                </svg>
            </div>
        </div>

        <div class="card-body">
            <form method="POST" action="cadastrar_sensor.php">
                <div class="field">
                    <label for="nome">Nome do sensor</label>
                    <input type="text" id="nome" name="nome" maxlength="100" placeholder="Ex.: Sensor de temperatura" value="<?= escapar($nome) ?>" required>
                </div>

                <div class="field">
                    <label for="localizacao">Localização no trilho</label>
                    <input type="text" id="localizacao" name="localizacao" maxlength="150" placeholder="Ex.: KM 12 - Trecho Norte" value="<?= escapar($localizacao) ?>" required>
                </div>

                <div class="field">
                    <label for="tipo_dado">Tipo de dado</label>
                    <input type="text" id="tipo_dado" name="tipo_dado" maxlength="100" placeholder="Ex.: Temperatura, velocidade ou pressão" value="<?= escapar($tipoDado) ?>" required>
                </div>

                <div class="field">
                    <label for="rota_id">Rota</label>
                    <select id="rota_id" name="rota_id" required>
                        <option value="">Selecione uma rota</option>
                        <?php while ($r = $rotas->fetch_assoc()): ?>
                            <option value="<?= $r["id"] ?>" <?= $rotaId == $r["id"] ? "selected" : "" ?>>
                                <?= escapar($r["nome"]) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-actions">
                    <a href="cadastro_item.php" class="button button-secondary">Voltar</a>
                    <button type="submit" class="button button-primary">Cadastrar sensor →</button>
                </div>
            </form>
        </div>
    </section>

    <div class="footer">Ferrorama © <?= date("Y") ?> — Sistema de gerenciamento ferroviário.</div>
</main>

</body>
</html>