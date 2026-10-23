<?php

require_once __DIR__ . "/../infra/adm.php";
require_once __DIR__ . "/../infra/connect.php";

$mensagemErro = "";
$mensagemSucesso = "";

$nome = "";
$localizacao = "";
$tipoDado = "";
$tremId = "";

// Busca os trens disponíveis
$sqlTrens = "
    SELECT id, identificador, modelo
    FROM trens
    ORDER BY identificador ASC
";

$resTrens = $conn->query($sqlTrens);
$trens = $resTrens
    ? $resTrens->fetch_all(MYSQLI_ASSOC)
    : [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nome = trim($_POST["nome"] ?? "");
    $localizacao = trim($_POST["localizacao"] ?? "");
    $tipoDado = trim($_POST["tipo_dado"] ?? "");
    $tremId = trim($_POST["trem_id"] ?? "");

    if ($nome === "" || $tipoDado === "") {

        $mensagemErro =
            "Preencha o nome e o tipo de dado do sensor.";

    } else {

        $tremIdBanco = $tremId !== ""
            ? filter_var($tremId, FILTER_VALIDATE_INT)
            : null;

        if ($tremId !== "" && (!$tremIdBanco || $tremIdBanco < 1)) {

            $mensagemErro = "Selecione um trem válido.";

        } else {

            // Confere se o trem selecionado realmente existe
            if ($tremIdBanco !== null) {

                $stmtVerificar = $conn->prepare(
                    "SELECT id FROM trens WHERE id = ?"
                );

                $stmtVerificar->bind_param("i", $tremIdBanco);
                $stmtVerificar->execute();

                $resultadoVerificar = $stmtVerificar->get_result();
                $tremExiste = $resultadoVerificar->num_rows > 0;

                $stmtVerificar->close();

                if (!$tremExiste) {
                    $mensagemErro = "O trem selecionado não existe.";
                }
            }

            if ($mensagemErro === "") {

                $sql = "
                    INSERT INTO sensores
                        (nome, localizacao, tipo_dado, trem_id)
                    VALUES (?, ?, ?, ?)
                ";

                $stmt = $conn->prepare($sql);

                if ($stmt) {

                    $stmt->bind_param(
                        "sssi",
                        $nome,
                        $localizacao,
                        $tipoDado,
                        $tremIdBanco
                    );

                    try {

                        $stmt->execute();
                        $stmt->close();

                        header("Location: cadastro_item.php?sucesso=1");
                        exit;

                    } catch (mysqli_sql_exception $e) {

                        error_log($e->getMessage());

                        $mensagemErro =
                            "Não foi possível cadastrar o sensor. Tente novamente.";

                        $stmt->close();
                    }

                } else {

                    error_log($conn->error);

                    $mensagemErro =
                        "Não foi possível preparar o cadastro do sensor.";
                }
            }
        }
    }
}

// Mensagem de sucesso após redirecionamento
if (isset($_GET["sucesso"]) && $_GET["sucesso"] === "1") {
    $mensagemSucesso = "Sensor cadastrado com sucesso!";
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

    <title>Cadastrar Sensor - Ferrorama</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

    <style>
        :root {
            --azul-escuro: #0c192b;
            --azul: #3730c9;
            --azul-claro: #eeedff;
            --fundo: #f2f4f9;
            --texto: #142238;
            --texto-suave: #718096;
            --borda: #e0e6f0;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
            color: var(--texto);
            background: var(--fundo);
        }

        .navbar-ferrorama {
            background: var(--azul-escuro);
            color: white;
            padding: 18px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .marca {
            display: flex;
            align-items: center;
            gap: 12px;
            color: white;
            text-decoration: none;
        }

        .marca-icone {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 43px;
            height: 43px;
            background: white;
            color: var(--azul);
            border-radius: 13px;
            font-size: 23px;
            font-weight: 800;
        }

        .marca-nome {
            font-size: 18px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .marca-subtitulo {
            color: #b7c5dc;
            font-size: 11px;
            margin-top: 2px;
        }

        .pagina {
            width: 100%;
            max-width: 850px;
            margin: 0 auto;
            padding: 42px 22px 60px;
        }

        .cabecalho {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 28px;
        }

        .etiqueta {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--azul);
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 1.4px;
            text-transform: uppercase;
            margin-bottom: 12px;
        }

        .etiqueta::before {
            content: "";
            width: 22px;
            height: 2px;
            background: #8177ff;
            border-radius: 2px;
        }

        h1 {
            margin: 0;
            font-size: clamp(25px, 4vw, 33px);
            font-weight: 800;
            letter-spacing: -1.1px;
            color: var(--azul-escuro);
        }

        .descricao {
            margin: 9px 0 0;
            color: var(--texto-suave);
            font-size: 14px;
            line-height: 1.7;
        }

        .botao-voltar {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            flex-shrink: 0;
            padding: 11px 15px;
            border: 1px solid var(--borda);
            border-radius: 10px;
            background: white;
            color: var(--texto);
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            transition: 0.2s;
        }

        .botao-voltar:hover {
            border-color: #b8b2ff;
            background: var(--azul-claro);
            color: var(--azul);
        }

        .cartao {
            overflow: hidden;
            background: white;
            border: 1px solid var(--borda);
            border-radius: 17px;
            box-shadow: 0 8px 28px rgba(20, 34, 56, 0.045);
        }

        .cartao-topo {
            padding: 23px 28px;
            border-bottom: 1px solid #edf0f6;
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .icone-sensor {
            width: 46px;
            height: 46px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            border-radius: 13px;
            background: var(--azul-claro);
            color: var(--azul);
            font-size: 23px;
        }

        .cartao-titulo {
            margin: 0;
            font-size: 16px;
            font-weight: 700;
        }

        .cartao-subtitulo {
            margin: 5px 0 0;
            font-size: 12px;
            color: var(--texto-suave);
        }

        .cartao-corpo {
            padding: 28px;
        }

        .form-label {
            margin-bottom: 9px;
            font-size: 13px;
            font-weight: 600;
            color: #28384e;
        }

        .form-control,
        .form-select {
            min-height: 46px;
            padding: 11px 13px;
            border: 1px solid #dce3ee;
            border-radius: 9px;
            color: var(--texto);
            background-color: #fff;
            font-family: inherit;
            font-size: 13px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-control::placeholder {
            color: #9aa7ba;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #8279ff;
            box-shadow: 0 0 0 3px rgba(99, 87, 255, 0.12);
        }

        .form-text {
            margin-top: 7px;
            color: var(--texto-suave);
            font-size: 11px;
        }

        .separador {
            height: 1px;
            background: #edf0f6;
            margin: 24px 0;
        }

        .acoes {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            min-height: 42px;
            padding: 10px 17px;
            border-radius: 9px;
            font-family: inherit;
            font-size: 13px;
            font-weight: 600;
            transition: 0.2s;
        }

        .btn-primary {
            border-color: var(--azul);
            background: var(--azul);
        }

        .btn-primary:hover {
            border-color: #2922a8;
            background: #2922a8;
            transform: translateY(-1px);
        }

        .btn-outline-secondary {
            border-color: #d8dfeb;
            background: white;
            color: #4e5d72;
        }

        .btn-outline-secondary:hover {
            background: #f4f6fb;
            border-color: #b9c4d5;
            color: var(--texto);
        }

        .alert {
            margin-bottom: 20px;
            border: 0;
            border-radius: 10px;
            padding: 14px 16px;
            font-size: 13px;
        }

        .alert-danger {
            background: #fff0f0;
            color: #b42332;
        }

        .alert-success {
            background: #eaf9f0;
            color: #18794e;
        }

        .rodape {
            padding-top: 22px;
            text-align: center;
            color: #8b97aa;
            font-size: 11px;
        }

        @media (max-width: 600px) {
            .pagina {
                padding: 28px 15px 40px;
            }

            .cabecalho {
                align-items: flex-start;
                flex-direction: column;
            }

            .cartao-topo,
            .cartao-corpo {
                padding: 21px 18px;
            }

            .acoes {
                flex-direction: column-reverse;
            }

            .acoes .btn {
                width: 100%;
            }
        }
    </style>
</head>

<body>

<header class="navbar-ferrorama">
    <div class="container d-flex justify-content-between align-items-center">
        <a href="sistema.php" class="marca">
            <div class="marca-icone">▤</div>
            <div>
                <div class="marca-nome">Ferrorama</div>
                <div class="marca-subtitulo">Sistema ferroviário</div>
            </div>
        </a>

        <span class="small text-white-50 d-none d-sm-block">
            Painel de gerenciamento
        </span>
    </div>
</header>

<main class="pagina">

    <div class="cabecalho">
        <div>
            <div class="etiqueta">Sensores</div>
            <h1>Cadastrar Sensor</h1>
            <p class="descricao">
                Adicione um sensor para monitorar os recursos ferroviários.
            </p>
        </div>

        <a href="sistema.php" class="botao-voltar">
            <span aria-hidden="true">←</span>
            Voltar ao sistema
        </a>
    </div>

    <?php if ($mensagemErro !== ""): ?>
        <div class="alert alert-danger" role="alert">
            <strong>Não foi possível concluir.</strong>
            <?= escapar($mensagemErro) ?>
        </div>
    <?php endif; ?>

    <?php if ($mensagemSucesso !== ""): ?>
        <div class="alert alert-success" role="status">
            <strong>Sucesso!</strong>
            <?= escapar($mensagemSucesso) ?>
        </div>
    <?php endif; ?>

    <section class="cartao">

        <div class="cartao-topo">
            <div class="icone-sensor" aria-hidden="true">⌁</div>
            <div>
                <h2 class="cartao-titulo">Informações do sensor</h2>
                <p class="cartao-subtitulo">
                    Preencha os dados abaixo para cadastrar um novo dispositivo.
                </p>
            </div>
        </div>

        <div class="cartao-corpo">

            <form method="POST" action="cadastro_item.php">

                <div class="mb-4">
                    <label for="nome" class="form-label">
                        Nome do sensor <span class="text-danger">*</span>
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        id="nome"
                        name="nome"
                        maxlength="100"
                        placeholder="Ex.: Sensor de Temperatura Norte"
                        value="<?= escapar($nome) ?>"
                        required
                    >
                </div>

                <div class="mb-4">
                    <label for="localizacao" class="form-label">
                        Localização
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        id="localizacao"
                        name="localizacao"
                        maxlength="150"
                        placeholder="Ex.: Vagão 01 / Vagão Motor"
                        value="<?= escapar($localizacao) ?>"
                    >

                    <div class="form-text">
                        Informe onde o sensor está instalado.
                    </div>
                </div>

                <div class="mb-4">
                    <label for="tipo_dado" class="form-label">
                        Tipo de dado monitorado <span class="text-danger">*</span>
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        id="tipo_dado"
                        name="tipo_dado"
                        maxlength="100"
                        placeholder="Ex.: Temperatura, Pressão ou Velocidade"
                        value="<?= escapar($tipoDado) ?>"
                        required
                    >

                    <div class="form-text">
                        Especifique a informação que o dispositivo coleta.
                    </div>
                </div>

                <div class="mb-3">
                    <label for="trem_id" class="form-label">
                        Trem associado
                    </label>

                    <select
                        class="form-select"
                        id="trem_id"
                        name="trem_id"
                    >
                        <option value="">
                            Nenhum trem associado
                        </option>

                        <?php foreach ($trens as $trem): ?>
                            <option
                                value="<?= (int) $trem["id"] ?>"
                                <?= (string) $tremId === (string) $trem["id"] ? "selected" : "" ?>
                            >
                                <?= escapar($trem["identificador"]) ?>
                                — <?= escapar($trem["modelo"]) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <div class="form-text">
                        Opcional. Você pode associar o sensor a um trem cadastrado.
                    </div>
                </div>

                <div class="separador"></div>

                <div class="acoes">
                    <a href="sistema.php" class="btn btn-outline-secondary">
                        Voltar
                    </a>

                    <button type="submit" class="btn btn-primary">
                        Cadastrar sensor →
                    </button>
                </div>

            </form>

        </div>
    </section>

    <div class="rodape">
        Ferrorama · Sistema de gerenciamento ferroviário
    </div>

</main>

</body>
</html>