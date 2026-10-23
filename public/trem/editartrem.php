
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

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap"
        rel="stylesheet"
    >

    <style>
        :root {
            --azul: #0d6efd;
            --azul-hover: #0b5ed7;
            --azul-escuro: #172554;
            --fundo: #f5f6f8;
            --texto: #111827;
            --cinza: #6b7280;
            --borda: #d9dee7;
            --branco: #ffffff;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            background: var(--fundo);
            color: var(--texto);
            font-family: "Roboto", Arial, sans-serif;
        }

        /* Cabeçalho */

        .pagina {
            width: calc(100% - 40px);
            max-width: 1296px;
            margin: 0 auto;
            padding: 32px 0 50px;
        }

        .cabecalho {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 26px;
        }

        .cabecalho h1 {
            margin: 0;
            color: var(--azul-escuro);
            font-size: 34px;
            font-weight: 700;
            letter-spacing: -0.7px;
        }

        .subtitulo {
            margin: 7px 0 0;
            color: var(--cinza);
            font-size: 14px;
        }

        /* Botões */

        .botao {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 40px;
            padding: 9px 16px;
            border: 1px solid transparent;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: background-color 0.2s, border-color 0.2s;
        }

        .botao-azul {
            color: var(--branco);
            background: var(--azul);
            border-color: var(--azul);
        }

        .botao-azul:hover {
            color: var(--branco);
            background: var(--azul-hover);
            border-color: var(--azul-hover);
        }

        .botao-secundario {
            color: #374151;
            background: var(--branco);
            border-color: #9ca3af;
        }

        .botao-secundario:hover {
            color: var(--azul-escuro);
            background: #f1f5f9;
            border-color: #6b7280;
        }

        /* Cartão principal */

        .cartao {
            overflow: hidden;
            background: var(--branco);
            border: 1px solid #d7d7d7;
            border-radius: 7px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.06);
        }

        .cartao-faixa {
            height: 6px;
            background: var(--azul);
        }

        .cartao-conteudo {
            padding: 28px 30px;
        }

        .cartao-cabecalho {
            display: flex;
            align-items: center;
            gap: 13px;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e5e7eb;
        }

        .icone-secao {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            width: 44px;
            height: 44px;
            color: var(--azul);
            background: #edf4ff;
            border: 1px solid #d8e7ff;
            border-radius: 7px;
            font-size: 22px;
            font-weight: 700;
        }

        .cartao-cabecalho h2 {
            margin: 0;
            color: var(--azul-escuro);
            font-size: 19px;
            font-weight: 700;
        }

        .cartao-cabecalho p {
            margin: 5px 0 0;
            color: var(--cinza);
            font-size: 13px;
        }

        /* Campos */

        .formulario-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            column-gap: 24px;
            row-gap: 21px;
        }

        .campo {
            min-width: 0;
        }

        .campo-completo {
            grid-column: 1 / -1;
        }

        .campo label {
            display: block;
            margin-bottom: 8px;
            color: #1f2937;
            font-size: 14px;
            font-weight: 600;
        }

        .campo input,
        .campo select {
            display: block;
            width: 100%;
            min-height: 43px;
            padding: 10px 12px;
            color: var(--texto);
            background: var(--branco);
            border: 1px solid var(--borda);
            border-radius: 6px;
            font-family: inherit;
            font-size: 14px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .campo input::placeholder {
            color: #9ca3af;
        }

        .campo input:focus,
        .campo select:focus {
            outline: none;
            border-color: var(--azul);
            box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.12);
        }

        .ajuda {
            display: block;
            margin-top: 7px;
            color: var(--cinza);
            font-size: 12px;
        }

        /* Mensagens */

        .alerta {
            margin-bottom: 20px;
            padding: 13px 16px;
            border: 1px solid transparent;
            border-radius: 6px;
            font-size: 14px;
        }

        .alerta-erro {
            color: #842029;
            background: #f8d7da;
            border-color: #f1aeb5;
        }

        /* Ações */

        .acoes {
            display: flex;
            justify-content: flex-end;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 29px;
            padding-top: 21px;
            border-top: 1px solid #e5e7eb;
        }

        /* Responsividade */

        @media (max-width: 650px) {
            .pagina {
                width: calc(100% - 28px);
                padding-top: 24px;
            }

            .cabecalho {
                align-items: flex-start;
            }

            .cabecalho h1 {
                font-size: 27px;
            }

            .subtitulo {
                line-height: 1.5;
            }

            .cartao-conteudo {
                padding: 22px 18px;
            }

            .formulario-grid {
                grid-template-columns: 1fr;
                row-gap: 18px;
            }

            .campo-completo {
                grid-column: auto;
            }
        }

        @media (max-width: 400px) {
            .cabecalho {
                flex-direction: column;
            }

            .acoes .botao {
                flex: 1;
            }
        }
    </style>
</head>

<body>

<div class="pagina">

    <header class="cabecalho">
        <div>
            <h1>Editar Trem</h1>
            <p class="subtitulo">
                Atualize as informações e os dados operacionais do trem.
            </p>
        </div>

        <a href="listagemtrem.php" class="botao botao-azul">
            Voltar ao sistema
        </a>
    </header>

    <?php if ($mensagemErro !== ""): ?>
        <div class="alerta alerta-erro" role="alert">
            <?= escapar($mensagemErro) ?>
        </div>
    <?php endif; ?>

    <main class="cartao">

        <div class="cartao-faixa"></div>

        <div class="cartao-conteudo">

            <div class="cartao-cabecalho">
                <div class="icone-secao" aria-hidden="true">T</div>

                <div>
                    <h2>Informações do trem</h2>
                    <p>
                        Edite os dados cadastrais e operacionais abaixo.
                    </p>
                </div>
            </div>

            <form
                method="POST"
                action="editartrem.php?id=<?= (int) $id ?>"
            >

                <div class="formulario-grid">

                    <div class="campo">
                        <label for="identificador">
                            Identificador
                        </label>

                        <input
                            type="text"
                            id="identificador"
                            name="identificador"
                            maxlength="50"
                            value="<?= escapar($identificador) ?>"
                            placeholder="Ex.: TR-001"
                            required
                        >
                    </div>

                    <div class="campo">
                        <label for="modelo">Modelo</label>

                        <input
                            type="text"
                            id="modelo"
                            name="modelo"
                            maxlength="100"
                            value="<?= escapar($modelo) ?>"
                            placeholder="Ex.: Expresso 4000"
                            required
                        >
                    </div>

                    <div class="campo">
                        <label for="status">
                            Status operacional
                        </label>

                        <select id="status" name="status" required>
                            <option
                                value="ativo"
                                <?= $status === "ativo" ? "selected" : "" ?>
                            >
                                Ativo
                            </option>

                            <option
                                value="inativo"
                                <?= $status === "inativo" ? "selected" : "" ?>
                            >
                                Inativo
                            </option>

                            <option
                                value="manutencao"
                                <?= $status === "manutencao" ? "selected" : "" ?>
                            >
                                Manutenção
                            </option>

                            <option
                                value="falha"
                                <?= $status === "falha" ? "selected" : "" ?>
                            >
                                Falha
                            </option>
                        </select>
                    </div>

                    <div class="campo">
                        <label for="velocidade_atual">
                            Velocidade atual (km/h)
                        </label>

                        <input
                            type="number"
                            id="velocidade_atual"
                            name="velocidade_atual"
                            step="0.01"
                            min="0"
                            max="9999.99"
                            value="<?= escapar($velocidade) ?>"
                            required
                        >
                    </div>

                    <div class="campo">
                        <label for="localizacao_atual">
                            Localização atual
                        </label>

                        <input
                            type="text"
                            id="localizacao_atual"
                            name="localizacao_atual"
                            maxlength="150"
                            value="<?= escapar($localizacao) ?>"
                            placeholder="Ex.: KM 12 - Trecho Norte"
                        >
                    </div>

                    <div class="campo">
                        <label for="consumo_energia">
                            Consumo de energia (kWh)
                        </label>

                        <input
                            type="number"
                            id="consumo_energia"
                            name="consumo_energia"
                            step="0.01"
                            min="0"
                            max="999999.99"
                            value="<?= escapar($consumo) ?>"
                            required
                        >

                        <small class="ajuda">
                            Consumo de energia registrado para este trem.
                        </small>
                    </div>

                    <?php if ($temResponsavel): ?>
                        <div class="campo campo-completo">
                            <label for="responsavel_id">
                                Usuário responsável
                            </label>

                            <select
                                id="responsavel_id"
                                name="responsavel_id"
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
                        </div>
                    <?php endif; ?>

                </div>

                <div class="acoes">
                    <a
                        href="listagemtrem.php"
                        class="botao botao-secundario"
                    >
                        Voltar
                    </a>

                    <button type="submit" class="botao botao-azul">
                        Salvar alterações
                    </button>
                </div>

            </form>

        </div>

    </main>

</div>

</body>
</html>
