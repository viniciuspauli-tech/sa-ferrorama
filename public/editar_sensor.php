
<?php

require_once __DIR__ . "/../infra/adm.php";
require_once __DIR__ . "/../infra/connect.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function escapar($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, "UTF-8");
}

$erro = "";
$sucesso = "";

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    header("Location: cadastro_item.php");
    exit;
}

// BUSCAR SENSOR
$stmt = $conn->prepare(
    "SELECT id, nome, localizacao, tipo_dado
     FROM sensores
     WHERE id = ?"
);

$stmt->bind_param("i", $id);
$stmt->execute();

$resultado = $stmt->get_result();
$sensor = $resultado->fetch_assoc();

$stmt->close();

if (!$sensor) {
    header("Location: cadastro_item.php?erro=nao_encontrado");
    exit;
}

// DADOS INICIAIS
$nome = $sensor["nome"];
$localizacao = $sensor["localizacao"];
$tipoDado = $sensor["tipo_dado"];

// ATUALIZAR SENSOR
if ($_SERVER["REQUEST_METHOD"] === "POST") {

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

            header("Location: cadastro_item.php?sucesso=atualizado");
            exit;

        } catch (mysqli_sql_exception $e) {

            error_log($e->getMessage());

            $erro = "Não foi possível atualizar o sensor. Tente novamente.";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Editar Sensor - Ferrorama</title>

    <link
        href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

    

<link rel="stylesheet" href="../style/style.css">
</head>

<body class="page-public_editar_sensor_php">

<!-- CABEÇALHO -->
<header class="topbar">

    <div class="topbar-inner">

        <a href="sistema.php" class="brand">

            <div class="brand-icon">

                <!-- LOGO PADRONIZADA DO FERRORAMA -->

                <svg viewBox="0 0 24 24"
                     fill="none"
                     xmlns="http://www.w3.org/2000/svg"
                     stroke="#3020E8"
                     stroke-width="1.8"
                     stroke-linecap="round"
                     stroke-linejoin="round">

                    <rect x="5" y="3" width="14" height="16" rx="4"/>

                    <path d="M8 7H16M8 11H16M5 15H19"/>

                    <circle cx="9" cy="15" r="1" fill="#3020E8"/>
                    <circle cx="15" cy="15" r="1" fill="#3020E8"/>

                    <path d="M8 19L6 21M16 19L18 21"/>

                </svg>

            </div>

            <div>

                <div class="brand-name">Ferrorama</div>
                <div class="brand-subtitle">Sistema ferroviário</div>

            </div>

        </a>

        <div class="top-label">

            <div class="top-avatar">

                <svg width="19" height="19"
                     viewBox="0 0 24 24"
                     fill="none"
                     stroke="currentColor"
                     stroke-width="1.7"
                     stroke-linecap="round"
                     stroke-linejoin="round">

                    <circle cx="12" cy="8" r="4"/>
                    <path d="M5 21v-2a7 7 0 0 1 14 0v2"/>

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

        <h1>Editar <span>Sensor.</span></h1>

        <p>
            Atualize as informações do sensor cadastrado
            no sistema de monitoramento ferroviário.
        </p>

    </div>

</section>

<!-- CONTEÚDO -->
<main class="main">

    <div class="page-heading">

        <div>

            <h2>Gerenciamento de sensores</h2>

            <p>
                Edite as informações do sensor selecionado.
            </p>

        </div>

        <a href="cadastro_item.php" class="button button-secondary">
            ← Voltar aos sensores
        </a>

    </div>

    <?php if ($erro !== ""): ?>

        <div class="alert alert-error" role="alert">
            <?= escapar($erro) ?>
        </div>

    <?php endif; ?>

    <!-- FORMULÁRIO -->
    <section class="content-card">

        <div class="card-heading">

            <div>

                <h3>Editar sensor #<?= (int)$id ?></h3>

                <p>
                    Altere os campos desejados e salve as modificações.
                </p>

            </div>

            <div class="card-icon">

                <svg viewBox="0 0 24 24"
                     fill="none"
                     stroke="currentColor"
                     stroke-width="1.8"
                     stroke-linecap="round"
                     stroke-linejoin="round">

                    <path d="M12 20h9"/>
                    <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4Z"/>

                </svg>

            </div>

        </div>

        <div class="card-body">

            <form method="POST"
                  action="editar_sensor.php?id=<?= (int)$id ?>">

                <div class="field">

                    <label for="nome">Nome do sensor</label>

                    <input
                        type="text"
                        id="nome"
                        name="nome"
                        maxlength="100"
                        value="<?= escapar($nome) ?>"
                        placeholder="Ex.: Sensor de temperatura"
                        required
                    >

                </div>

                <div class="field">

                    <label for="localizacao">Localização no trilho</label>

                    <input
                        type="text"
                        id="localizacao"
                        name="localizacao"
                        maxlength="150"
                        value="<?= escapar($localizacao) ?>"
                        placeholder="Ex.: KM 12 - Trecho Norte"
                        required
                    >

                </div>

                <div class="field">

                    <label for="tipo_dado">Tipo de dado</label>

                    <input
                        type="text"
                        id="tipo_dado"
                        name="tipo_dado"
                        maxlength="100"
                        value="<?= escapar($tipoDado) ?>"
                        placeholder="Ex.: Temperatura, velocidade ou pressão"
                        required
                    >

                </div>

                <div class="form-actions">

                    <a href="cadastro_item.php"
                       class="button button-secondary">
                        Voltar
                    </a>

                    <button type="submit"
                            class="button button-primary">
                        Salvar alterações →
                    </button>

                </div>

            </form>

        </div>

    </section>

    <div class="footer">

        Ferrorama © <?= date("Y") ?> — Sistema de gerenciamento ferroviário.

    </div>

</main>

</body>
</html>
