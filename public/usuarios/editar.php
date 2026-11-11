<?php

require_once "../../infra/adm.php";
require_once "../../infra/connect.php";

// ---------------------------------------------------------
// Funções de CPF
// ---------------------------------------------------------

function limparCpf($cpf): string
{
    return preg_replace('/\D/', '', (string) $cpf);
}

function validarCpf($cpf): bool
{
    $cpf = limparCpf($cpf);

    if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
        return false;
    }

    for ($t = 9; $t < 11; $t++) {
        $soma = 0;

        for ($i = 0; $i < $t; $i++) {
            $soma += (int) $cpf[$i] * (($t + 1) - $i);
        }

        if ((int) $cpf[$t] !== ((10 * $soma) % 11) % 10) {
            return false;
        }
    }

    return true;
}

// CPF já usado por outro usuário?
function cpfEmUso($conn, $cpf, $idAtual): bool
{
    $stmt = mysqli_prepare(
        $conn,
        "SELECT id FROM usuarios WHERE cpf = ? AND id != ?"
    );

    mysqli_stmt_bind_param($stmt, "si", $cpf, $idAtual);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    $existe = mysqli_stmt_num_rows($stmt) > 0;

    mysqli_stmt_close($stmt);

    return $existe;
}

$erro = "";
$sucesso = "";

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id) {
    die("Usuário inválido.");
}

$nome = "";
$email = "";
$cpf = "";
$cep = "";
$logradouro = "";
$bairro = "";
$cidade = "";
$uf = "";
$perfil = "usuario";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $idPost = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);

    $nome = trim($_POST["nome"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $cpf = limparCpf($_POST["cpf"] ?? "");
    $cep = preg_replace('/\D/', '', $_POST["cep"] ?? "");
    $logradouro = trim($_POST["logradouro"] ?? "");
    $bairro = trim($_POST["bairro"] ?? "");
    $cidade = trim($_POST["cidade"] ?? "");
    $uf = strtoupper(trim($_POST["uf"] ?? ""));
    $senha = $_POST["senha"] ?? "";
    $perfil = $_POST["perfil"] ?? "";

    if (!$idPost || $idPost !== $id) {

        $erro = "Usuário inválido.";

    } elseif ($nome === "" || $email === "") {

        $erro = "Nome e e-mail são obrigatórios.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $erro = "Digite um e-mail válido.";

    } elseif (
        $perfil !== "usuario" &&
        $perfil !== "administrador"
    ) {

        $erro = "Perfil inválido.";

    } elseif (
        (int) $id === (int) ($_SESSION["usuario_id"] ?? 0) &&
        $perfil !== "administrador"
    ) {

        $erro = "Você não pode remover seu próprio perfil de administrador.";

    } elseif ($cpf !== "" && !validarCpf($cpf)) {

        $erro = "Digite um CPF válido.";

    } elseif ($cpf !== "" && cpfEmUso($conn, $cpf, $id)) {

        $erro = "Este CPF já está cadastrado em outro usuário.";

    } elseif ($cep !== "" && strlen($cep) !== 8) {

        $erro = "Informe um CEP válido com 8 números.";

    } elseif (
        $cep !== "" &&
        ($cidade === "" || !preg_match('/^[A-Z]{2}$/', $uf))
    ) {

        $erro = "Consulte o CEP para preencher o endereço.";

    } elseif ($senha !== "" && strlen($senha) < 8) {

        $erro = "A senha deve possuir pelo menos 8 caracteres.";

    } else {

        $sql = "SELECT id FROM usuarios WHERE email = ? AND id != ?";
        $stmt = mysqli_prepare($conn, $sql);

        mysqli_stmt_bind_param($stmt, "si", $email, $id);
        mysqli_stmt_execute($stmt);

        $resultadoEmail = mysqli_stmt_get_result($stmt);
        $emailExiste = mysqli_num_rows($resultadoEmail) > 0;

        mysqli_stmt_close($stmt);

        if ($emailExiste) {

            $erro = "Este e-mail já está sendo utilizado.";

        } else {

            // CPF vazio vira NULL (a coluna é única)
            $cpfDb = $cpf === "" ? null : $cpf;

            // CEP vazio limpa o endereço
            if ($cep === "") {
                $logradouro = $bairro = $cidade = $uf = "";
            }

            if ($senha !== "") {

                $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

                $sql = "UPDATE usuarios
                        SET nome = ?, email = ?, senha = ?, perfil = ?,
                            cpf = ?, cep = ?, logradouro = ?,
                            bairro = ?, cidade = ?, uf = ?
                        WHERE id = ?";

                $stmt = mysqli_prepare($conn, $sql);

                mysqli_stmt_bind_param(
                    $stmt,
                    "ssssssssssi",
                    $nome,
                    $email,
                    $senhaHash,
                    $perfil,
                    $cpfDb,
                    $cep,
                    $logradouro,
                    $bairro,
                    $cidade,
                    $uf,
                    $id
                );

            } else {

                $sql = "UPDATE usuarios
                        SET nome = ?, email = ?, perfil = ?,
                            cpf = ?, cep = ?, logradouro = ?,
                            bairro = ?, cidade = ?, uf = ?
                        WHERE id = ?";

                $stmt = mysqli_prepare($conn, $sql);

                mysqli_stmt_bind_param(
                    $stmt,
                    "sssssssssi",
                    $nome,
                    $email,
                    $perfil,
                    $cpfDb,
                    $cep,
                    $logradouro,
                    $bairro,
                    $cidade,
                    $uf,
                    $id
                );
            }

            if (mysqli_stmt_execute($stmt)) {

                $sucesso = "Usuário atualizado com sucesso.";

            } else {

                error_log(mysqli_stmt_error($stmt));
                $erro = "Não foi possível atualizar o usuário.";
            }

            mysqli_stmt_close($stmt);
        }
    }
}

$sql = "SELECT id, nome, email, perfil,
               cpf, cep, logradouro, bairro, cidade, uf
        FROM usuarios
        WHERE id = ?";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);

$resultado = mysqli_stmt_get_result($stmt);
$usuario = mysqli_fetch_assoc($resultado);

mysqli_stmt_close($stmt);

if (!$usuario) {
    die("Usuário não encontrado.");
}

// Mantém os valores enviados caso ocorra um erro de validação.
if ($_SERVER["REQUEST_METHOD"] !== "POST" || $sucesso !== "") {
    $nome = $usuario["nome"];
    $email = $usuario["email"];
    $perfil = $usuario["perfil"];
    $cpf = $usuario["cpf"] ?? "";
    $cep = $usuario["cep"] ?? "";
    $logradouro = $usuario["logradouro"] ?? "";
    $bairro = $usuario["bairro"] ?? "";
    $cidade = $usuario["cidade"] ?? "";
    $uf = $usuario["uf"] ?? "";
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

    <title>Editar usuário - Ferrorama</title>

    <link
        href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

    <style>
        :root {
            --navy: #0c192b;
            --navy-light: #17243e;
            --purple: #6554e8;
            --purple-dark: #5140d4;
            --purple-light: #eeecff;
            --background: #f2f4f9;
            --white: #ffffff;
            --text: #132238;
            --muted: #73819a;
            --border: #e0e6f0;
            --danger: #dc414b;
            --danger-bg: #fff0f1;
            --success: #16805d;
            --success-bg: #e9f8f0;
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

        .topbar {
            background: var(--navy);
            color: white;
            border-bottom: 1px solid rgba(255,255,255,.08);
        }

        .topbar-inner {
            max-width: 1200px;
            min-height: 76px;
            margin: auto;
            padding: 0 26px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            color: white;
        }

        .brand-icon {
            width: 43px;
            height: 43px;
            border-radius: 13px;
            background: #ffffff;
            color: #2415d5;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
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

        .top-user {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #edf2ff;
            font-size: 13px;
        }

        .top-user-icon {
            width: 38px;
            height: 38px;
            display: grid;
            place-items: center;
            background: #1c2d45;
            border-radius: 50%;
            font-size: 18px;
        }

        .hero {
            padding: 35px 24px 38px;
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
            max-width: 900px;
            margin: auto;
        }

        .eyebrow {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 13px;
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
            margin-bottom: 10px;
            font-size: clamp(28px, 4vw, 35px);
            letter-spacing: -1px;
        }

        .hero h1 span {
            color: #a69aff;
        }

        .hero p {
            color: #c7d2e7;
            font-size: 14px;
            line-height: 1.8;
        }

        .main {
            max-width: 900px;
            margin: auto;
            padding: 30px 24px 55px;
        }

        .breadcrumb {
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 9px;
            color: var(--muted);
            font-size: 12px;
        }

        .breadcrumb a {
            color: var(--purple);
            font-weight: 600;
        }

        .breadcrumb a:hover {
            color: var(--purple-dark);
        }

        .form-card {
            overflow: hidden;
            background: white;
            border: 1px solid var(--border);
            border-radius: 16px;
            box-shadow: 0 8px 28px rgba(23,40,70,.05);
        }

        .card-heading {
            padding: 23px 27px;
            display: flex;
            align-items: center;
            gap: 15px;
            border-bottom: 1px solid var(--border);
        }

        .heading-icon {
            width: 48px;
            height: 48px;
            flex: 0 0 48px;
            display: grid;
            place-items: center;
            color: var(--purple);
            background: var(--purple-light);
            border-radius: 14px;
            font-size: 22px;
        }

        .card-heading h2 {
            margin-bottom: 5px;
            font-size: 17px;
        }

        .card-heading p {
            color: var(--muted);
            font-size: 12px;
            line-height: 1.6;
        }

        .card-body {
            padding: 27px;
        }

        .alert {
            margin-bottom: 22px;
            padding: 14px 16px;
            border: 1px solid transparent;
            border-radius: 10px;
            font-size: 13px;
            line-height: 1.6;
        }

        .alert-error {
            color: #b42332;
            background: var(--danger-bg);
            border-color: #ffd5d9;
        }

        .alert-success {
            color: var(--success);
            background: var(--success-bg);
            border-color: #c8efdc;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 22px 20px;
        }

        .field {
            min-width: 0;
        }

        .field-full {
            grid-column: 1 / -1;
        }

        .field label {
            margin-bottom: 9px;
            display: block;
            color: #283951;
            font-size: 12px;
            font-weight: 700;
        }

        .field input,
        .field select {
            width: 100%;
            height: 46px;
            padding: 0 13px;
            outline: none;
            border: 1px solid #d8e0ec;
            border-radius: 9px;
            background: #fff;
            color: var(--text);
            font-family: inherit;
            font-size: 13px;
            transition: border-color .2s, box-shadow .2s;
        }

        .field input:focus,
        .field select:focus {
            border-color: var(--purple);
            box-shadow: 0 0 0 3px rgba(101,84,232,.12);
        }

        .field input::placeholder {
            color: #9aa6b8;
        }

        .field small {
            margin-top: 8px;
            display: block;
            color: var(--muted);
            font-size: 11px;
            line-height: 1.7;
        }

        .field-hint {
            margin-top: 8px;
            padding: 10px 12px;
            display: flex;
            align-items: flex-start;
            gap: 8px;
            color: #5d6c83;
            background: #f6f7fb;
            border-radius: 8px;
            font-size: 11px;
            line-height: 1.7;
        }

        .card-footer {
            padding: 20px 27px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            background: #fafbfe;
            border-top: 1px solid var(--border);
        }

        .footer-note {
            color: var(--muted);
            font-size: 11px;
            line-height: 1.6;
        }

        .footer-actions {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        .button {
            min-height: 42px;
            padding: 0 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: 1px solid transparent;
            border-radius: 9px;
            font-family: inherit;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: background .2s, border-color .2s, transform .2s;
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

        .footer {
            margin-top: 25px;
            color: #8a96aa;
            text-align: center;
            font-size: 11px;
        }

        @media (max-width: 600px) {
            .topbar-inner {
                padding: 0 16px;
            }

            .top-user {
                font-size: 0;
            }

            .top-user-icon {
                font-size: 18px;
            }

            .hero {
                padding: 29px 18px;
            }

            .main {
                padding: 22px 14px 40px;
            }

            .card-heading {
                padding: 19px;
            }

            .card-body {
                padding: 20px;
            }

            .form-grid {
                grid-template-columns: 1fr;
                gap: 19px;
            }

            .field-full {
                grid-column: auto;
            }

            .card-footer {
                padding: 18px 20px;
                align-items: stretch;
                flex-direction: column;
            }

            .footer-actions {
                display: grid;
                grid-template-columns: 1fr 1fr;
            }

            .footer-actions .button {
                padding: 0 10px;
            }

            .footer-actions .button-primary {
                grid-column: 1 / -1;
                grid-row: 1;
            }

            .footer-actions .button-secondary {
                grid-row: 2;
            }
        }
    </style>
</head>

<body>

<header class="topbar">
    <div class="topbar-inner">

        <a href="../sistema.php" class="brand">
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

        <div class="top-user">
            <div class="top-user-icon">♙</div>
            Área administrativa
        </div>

    </div>
</header>

<section class="hero">
    <div class="hero-inner">

        <div class="eyebrow">Administração do sistema</div>

        <h1>Editar <span>usuário</span></h1>

        <p>
            Atualize os dados da conta, gerencie o nível de acesso
            e altere a senha quando necessário.
        </p>

    </div>
</section>

<main class="main">

    <div class="breadcrumb">
        <a href="pagina_adm.php">Usuários</a>
        <span>/</span>
        <span>Editar usuário</span>
    </div>

    <section class="form-card">

        <div class="card-heading">

            <div class="heading-icon">♙</div>

            <div>
                <h2>Dados da conta</h2>
                <p>Edite as informações abaixo e salve as alterações.</p>
            </div>

        </div>

        <div class="card-body">

            <?php if ($erro !== ""): ?>
                <div class="alert alert-error" role="alert">
                    <strong>Não foi possível salvar.</strong><br>
                    <?= escapar($erro) ?>
                </div>
            <?php endif; ?>

            <?php if ($sucesso !== ""): ?>
                <div class="alert alert-success" role="status">
                    <strong>Alterações salvas!</strong><br>
                    <?= escapar($sucesso) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="editar.php?id=<?= (int) $id ?>">

                <input
                    type="hidden"
                    name="id"
                    value="<?= (int) $id ?>"
                >

                <div class="form-grid">

                    <div class="field">
                        <label for="nome">Nome completo</label>

                        <input
                            type="text"
                            id="nome"
                            name="nome"
                            value="<?= escapar($nome) ?>"
                            maxlength="100"
                            placeholder="Digite o nome completo"
                            autocomplete="name"
                            required
                        >
                    </div>

                    <div class="field">
                        <label for="email">E-mail</label>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="<?= escapar($email) ?>"
                            maxlength="150"
                            placeholder="nome@empresa.com"
                            autocomplete="email"
                            required
                        >
                    </div>

                    <div class="field field-full">
                        <label for="cpf">CPF</label>

                        <input
                            type="text"
                            id="cpf"
                            name="cpf"
                            value="<?= escapar($cpf) ?>"
                            maxlength="14"
                            placeholder="000.000.000-00"
                            inputmode="numeric"
                            autocomplete="off"
                        >
                    </div>

                    <div class="field">
                        <label for="cep">CEP</label>

                        <input
                            type="text"
                            id="cep"
                            name="cep"
                            value="<?= escapar($cep) ?>"
                            maxlength="9"
                            placeholder="Ex.: 89201-000"
                            inputmode="numeric"
                            autocomplete="postal-code"
                        >

                        <small id="cep-message">
                            O endereço é preenchido automaticamente.
                        </small>
                    </div>

                    <div class="field">
                        <label for="logradouro">Logradouro</label>

                        <input
                            type="text"
                            id="logradouro"
                            name="logradouro"
                            value="<?= escapar($logradouro) ?>"
                            readonly
                        >
                    </div>

                    <div class="field">
                        <label for="bairro">Bairro</label>

                        <input
                            type="text"
                            id="bairro"
                            name="bairro"
                            value="<?= escapar($bairro) ?>"
                            readonly
                        >
                    </div>

                    <div class="field">
                        <label for="cidade">Cidade</label>

                        <input
                            type="text"
                            id="cidade"
                            name="cidade"
                            value="<?= escapar($cidade) ?>"
                            readonly
                        >
                    </div>

                    <div class="field">
                        <label for="uf">Estado (UF)</label>

                        <input
                            type="text"
                            id="uf"
                            name="uf"
                            value="<?= escapar($uf) ?>"
                            maxlength="2"
                            readonly
                        >
                    </div>

                    <div class="field field-full">
                        <label for="senha">Nova senha</label>

                        <input
                            type="password"
                            id="senha"
                            name="senha"
                            minlength="8"
                            autocomplete="new-password"
                            placeholder="Digite apenas se quiser alterar a senha"
                        >

                        <div class="field-hint">
                            <span>ⓘ</span>
                            <span>
                                Deixe este campo vazio para manter a senha atual.
                                Se alterar, a nova senha deve ter pelo menos
                                8 caracteres.
                            </span>
                        </div>
                    </div>

                    <div class="field field-full">
                        <label for="perfil">Cargo e permissões</label>

                        <select id="perfil" name="perfil" required>

                            <option
                                value="usuario"
                                <?= $perfil === "usuario" ? "selected" : "" ?>
                            >
                                Usuário comum
                            </option>

                            <option
                                value="administrador"
                                <?= $perfil === "administrador" ? "selected" : "" ?>
                            >
                                Administrador
                            </option>

                        </select>

                        <small>
                            Administradores têm acesso às funcionalidades
                            administrativas do sistema.
                        </small>
                    </div>

                </div>

                <div class="footer-actions" style="margin-top: 27px; justify-content: flex-end;">

                    <a
                        href="pagina_adm.php"
                        class="button button-secondary"
                    >
                        Voltar
                    </a>

                    <button type="submit" class="button button-primary">
                        ✓ Salvar alterações
                    </button>

                </div>

            </form>

        </div>

    </section>

    <footer class="footer">
        Ferrorama · Sistema de gerenciamento ferroviário
    </footer>

</main>

<script>
    // CPF: máscara e validação pela API
    (function () {
        const campo = document.getElementById("cpf");

        function mascara() {
            let v = campo.value.replace(/\D/g, "").slice(0, 11);
            v = v.replace(/(\d{3})(\d)/, "$1.$2")
                 .replace(/(\d{3})(\d)/, "$1.$2")
                 .replace(/(\d{3})(\d{1,2})$/, "$1-$2");
            campo.value = v;
        }

        mascara();

        campo.addEventListener("input", function () {
            mascara();
            campo.setCustomValidity("");
        });

        campo.addEventListener("blur", function () {
            const cpf = campo.value.replace(/\D/g, "");
            if (cpf.length === 0) return;

            fetch("cadastro_usuario.php?api=cpf&cpf=" + encodeURIComponent(cpf))
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    campo.setCustomValidity(d.valido ? "" : (d.erro || "CPF inválido."));
                    if (!d.valido) campo.reportValidity();
                })
                .catch(function () { /* o servidor valida no envio */ });
        });
    })();

    // CEP: máscara e preenchimento automático (api/cep.php -> ViaCEP)
    (function () {
        const campoCep = document.getElementById("cep");
        const mensagem = document.getElementById("cep-message");
        const campos = ["logradouro", "bairro", "cidade", "uf"];

        function aviso(texto, erro) {
            mensagem.textContent = texto;
            mensagem.style.color = erro ? "#b42332" : "#16805d";
        }

        function mascara() {
            let v = campoCep.value.replace(/\D/g, "").slice(0, 8);

            if (v.length > 5) {
                v = v.substring(0, 5) + "-" + v.substring(5);
            }

            campoCep.value = v;
        }

        mascara();

        campoCep.addEventListener("input", mascara);

        campoCep.addEventListener("blur", async function () {
            const cep = campoCep.value.replace(/\D/g, "");

            campos.forEach(function (id) {
                document.getElementById(id).value = "";
            });

            if (cep.length === 0) {
                aviso("Deixe vazio para remover o endereço.", false);
                return;
            }

            if (cep.length !== 8) {
                aviso("Informe um CEP válido com 8 números.", true);
                return;
            }

            aviso("Consultando ViaCEP...", false);

            try {
                const resposta = await fetch(
                    "../api/cep.php?cep=" + encodeURIComponent(cep)
                );

                const dados = await resposta.json();

                if (!resposta.ok) {
                    throw new Error(dados.erro || "Erro na consulta.");
                }

                campos.forEach(function (id) {
                    document.getElementById(id).value = dados[id] || "";
                });

                aviso("Endereço encontrado com sucesso!", false);

            } catch (erro) {
                aviso(erro.message, true);
            }
        });
    })();
</script>

</body>
</html>