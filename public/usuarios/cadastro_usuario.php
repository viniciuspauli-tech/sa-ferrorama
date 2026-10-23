
<?php

require_once "../../infra/auth.php";

if (($_SESSION["usuario_perfil"] ?? "") !== "administrador") {
    header("Location: ../home.php");
    exit;
}

require_once "../../infra/connect.php";

// Token CSRF
if (empty($_SESSION["csrf"])) {
    $_SESSION["csrf"] = bin2hex(random_bytes(32));
}

$erro = "";
$nome = "";
$email = "";
$perfil = "usuario";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nome = trim($_POST["nome"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $senha = $_POST["senha"] ?? "";
    $confirmar_senha = $_POST["confirmar_senha"] ?? "";
    $perfil = $_POST["perfil"] ?? "usuario";

    if (!hash_equals($_SESSION["csrf"], $_POST["csrf"] ?? "")) {

        $erro = "Requisição inválida. Atualize a página e tente novamente.";

    } elseif (
        $nome === "" ||
        $email === "" ||
        $senha === "" ||
        $confirmar_senha === ""
    ) {

        $erro = "Preencha todos os campos.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $erro = "Digite um e-mail válido.";

    } elseif (strlen($senha) < 8) {

        $erro = "A senha deve possuir pelo menos 8 caracteres.";

    } elseif ($senha !== $confirmar_senha) {

        $erro = "As senhas não são iguais.";

    } elseif ($perfil !== "usuario" && $perfil !== "administrador") {

        $erro = "Perfil inválido.";

    } else {

        // Verificar se o e-mail já existe
        $stmt = mysqli_prepare(
            $conn,
            "SELECT id FROM usuarios WHERE email = ?"
        );

        if (!$stmt) {

            $erro = "Erro ao preparar a consulta.";

        } else {

            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);

            $existe = mysqli_stmt_num_rows($stmt) > 0;

            mysqli_stmt_close($stmt);

            if ($existe) {

                $erro = "Este e-mail já está cadastrado.";

            } else {

                // Criptografar a senha
                $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

                // Inserir usuário no banco
                $stmt = mysqli_prepare(
                    $conn,
                    "INSERT INTO usuarios (nome, email, senha, perfil)
                     VALUES (?, ?, ?, ?)"
                );

                if (!$stmt) {

                    $erro = "Erro ao preparar o cadastro.";

                } else {

                    mysqli_stmt_bind_param(
                        $stmt,
                        "ssss",
                        $nome,
                        $email,
                        $senha_hash,
                        $perfil
                    );

                    if (mysqli_stmt_execute($stmt)) {

                        mysqli_stmt_close($stmt);

                        $_SESSION["msg"] = "Usuário cadastrado com sucesso!";

                        header("Location: pagina_adm.php");
                        exit();

                    } else {

                        error_log(mysqli_stmt_error($stmt));

                        $erro = "Não foi possível cadastrar o usuário.";

                        mysqli_stmt_close($stmt);
                    }
                }
            }
        }
    }
}

function escaparCadastro($valor): string
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

    <title>Cadastrar usuário - Ferrorama</title>

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
            max-width: 1280px;
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
            display: grid;
            place-items: center;
            border-radius: 13px;
            background: white;
            color: #3322c7;
            font-size: 23px;
            font-weight: 800;
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

        .hero {
            padding: 39px 24px 42px;
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
            max-width: 1000px;
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
            max-width: 600px;
            color: #c7d2e7;
            font-size: 14px;
            line-height: 1.8;
        }

        .main {
            max-width: 1000px;
            margin: auto;
            padding: 32px 24px 55px;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
            color: #5545d2;
            font-size: 13px;
            font-weight: 700;
        }

        .back-link:hover {
            color: var(--navy);
        }

        .layout {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 285px;
            align-items: start;
            gap: 22px;
        }

        .form-card,
        .side-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 17px;
            box-shadow: 0 8px 28px rgba(23,40,70,.045);
        }

        .form-card {
            padding: 30px;
        }

        .card-heading {
            padding-bottom: 23px;
            margin-bottom: 24px;
            border-bottom: 1px solid #edf0f5;
        }

        .card-heading h2 {
            margin-bottom: 8px;
            font-size: 20px;
            letter-spacing: -.5px;
        }

        .card-heading p {
            color: var(--muted);
            font-size: 13px;
            line-height: 1.7;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 19px 16px;
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

        .optional {
            color: var(--muted);
            font-size: 11px;
            font-weight: 400;
        }

        .input-wrap {
            position: relative;
        }

        input,
        select {
            width: 100%;
            min-height: 46px;
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

        input:focus,
        select:focus {
            border-color: var(--purple);
            box-shadow: 0 0 0 3px rgba(101,84,232,.12);
        }

        .field-hint {
            margin-top: 7px;
            color: var(--muted);
            font-size: 11px;
            line-height: 1.6;
        }

        .password-toggle {
            position: absolute;
            top: 50%;
            right: 12px;
            transform: translateY(-50%);
            padding: 5px;
            color: #687791;
            background: transparent;
            border: 0;
            cursor: pointer;
            font-family: inherit;
            font-size: 11px;
            font-weight: 700;
        }

        .password-input {
            padding-right: 58px;
        }

        .alert-error {
            margin-bottom: 22px;
            padding: 14px 15px;
            color: var(--danger);
            background: var(--danger-bg);
            border: 1px solid #f4d3d7;
            border-radius: 10px;
            font-size: 13px;
            line-height: 1.6;
        }

        .form-actions {
            margin-top: 27px;
            padding-top: 22px;
            display: flex;
            justify-content: flex-end;
            flex-wrap: wrap;
            gap: 11px;
            border-top: 1px solid #edf0f5;
        }

        .button {
            min-height: 44px;
            padding: 0 18px;
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

        .side-card {
            padding: 24px 22px;
        }

        .side-icon {
            width: 47px;
            height: 47px;
            margin-bottom: 17px;
            display: grid;
            place-items: center;
            color: #4b3ac8;
            background: var(--purple-light);
            border-radius: 14px;
            font-size: 23px;
        }

        .side-card h3 {
            margin-bottom: 9px;
            font-size: 16px;
        }

        .side-card > p {
            margin-bottom: 22px;
            color: var(--muted);
            font-size: 12px;
            line-height: 1.7;
        }

        .tip {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-top: 16px;
        }

        .tip-check {
            flex: 0 0 22px;
            width: 22px;
            height: 22px;
            display: grid;
            place-items: center;
            color: #5140d4;
            background: #eeecff;
            border-radius: 7px;
            font-size: 12px;
            font-weight: 800;
        }

        .tip strong {
            display: block;
            margin-bottom: 4px;
            font-size: 12px;
        }

        .tip span {
            display: block;
            color: var(--muted);
            font-size: 11px;
            line-height: 1.6;
        }

        .security-note {
            margin-top: 20px;
            padding: 13px;
            color: #4c5b74;
            background: #f6f7fb;
            border-radius: 9px;
            font-size: 11px;
            line-height: 1.7;
        }

        .footer {
            margin-top: 25px;
            color: #8a96aa;
            text-align: center;
            font-size: 11px;
        }

        @media (max-width: 760px) {
            .layout {
                grid-template-columns: 1fr;
            }

            .side-card {
                order: 2;
            }
        }

        @media (max-width: 560px) {
            .topbar-inner {
                padding: 0 16px;
            }

            .top-label {
                font-size: 0;
            }

            .top-avatar {
                font-size: 16px;
            }

            .hero {
                padding: 32px 18px;
            }

            .main {
                padding: 23px 14px 38px;
            }

            .form-card {
                padding: 22px 18px;
            }

            .form-grid {
                grid-template-columns: 1fr;
                gap: 17px;
            }

            .field-full {
                grid-column: auto;
            }

            .form-actions {
                flex-direction: column-reverse;
            }

            .form-actions .button {
                width: 100%;
            }
        }
    </style>
</head>

<body>

<header class="topbar">
    <div class="topbar-inner">

        <a href="../sistema.php" class="brand">
            <div class="brand-icon">🚆</div>

            <div>
                <div class="brand-name">Ferrorama</div>
                <div class="brand-subtitle">Sistema ferroviário</div>
            </div>
        </a>

        <div class="top-label">
            <div class="top-avatar">♙</div>
            Área administrativa
        </div>

    </div>
</header>

<section class="hero">
    <div class="hero-inner">

        <div class="eyebrow">Gestão de usuários</div>

        <h1>Cadastrar usuário</h1>

        <p>
            Crie uma nova conta e defina as permissões de acesso
            para utilizar o sistema Ferrorama.
        </p>

    </div>
</section>

<main class="main">

    <a href="pagina_adm.php" class="back-link">
        ← Voltar para usuários
    </a>

    <div class="layout">

        <section class="form-card">

            <div class="card-heading">
                <h2>Dados da conta</h2>
                <p>
                    Preencha as informações abaixo para registrar
                    um novo usuário.
                </p>
            </div>

            <?php if ($erro !== ""): ?>
                <div class="alert-error" role="alert">
                    <strong>Não foi possível concluir o cadastro.</strong>
                    <br>
                    <?= escaparCadastro($erro) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="cadastro_usuario.php">

                <input
                    type="hidden"
                    name="csrf"
                    value="<?= escaparCadastro($_SESSION["csrf"]) ?>"
                >

                <div class="form-grid">

                    <div class="field field-full">
                        <label for="nome">Nome completo</label>

                        <input
                            type="text"
                            id="nome"
                            name="nome"
                            maxlength="100"
                            placeholder="Ex.: João da Silva"
                            value="<?= escaparCadastro($nome) ?>"
                            autocomplete="name"
                            required
                        >
                    </div>

                    <div class="field field-full">
                        <label for="email">E-mail</label>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            maxlength="150"
                            placeholder="nome@exemplo.com"
                            value="<?= escaparCadastro($email) ?>"
                            autocomplete="email"
                            required
                        >

                        <p class="field-hint">
                            O e-mail será utilizado para identificar a conta.
                        </p>
                    </div>

                    <div class="field">
                        <label for="senha">Senha</label>

                        <div class="input-wrap">
                            <input
                                class="password-input"
                                type="password"
                                id="senha"
                                name="senha"
                                minlength="8"
                                placeholder="Mínimo de 8 caracteres"
                                autocomplete="new-password"
                                required
                            >

                            <button
                                class="password-toggle"
                                type="button"
                                data-target="senha"
                                aria-label="Mostrar senha"
                            >
                                Mostrar
                            </button>
                        </div>

                        <p class="field-hint">
                            Utilize pelo menos 8 caracteres.
                        </p>
                    </div>

                    <div class="field">
                        <label for="confirmar_senha">Confirmar senha</label>

                        <div class="input-wrap">
                            <input
                                class="password-input"
                                type="password"
                                id="confirmar_senha"
                                name="confirmar_senha"
                                minlength="8"
                                placeholder="Digite novamente"
                                autocomplete="new-password"
                                required
                            >

                            <button
                                class="password-toggle"
                                type="button"
                                data-target="confirmar_senha"
                                aria-label="Mostrar confirmação de senha"
                            >
                                Mostrar
                            </button>
                        </div>
                    </div>

                    <div class="field field-full">
                        <label for="perfil">Cargo e nível de acesso</label>

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

                        <p class="field-hint">
                            Administradores podem acessar a área de gestão
                            de usuários e as funcionalidades administrativas.
                        </p>
                    </div>

                </div>

                <div class="form-actions">

                    <a
                        href="pagina_adm.php"
                        class="button button-secondary"
                    >
                        Voltar
                    </a>

                    <button type="submit" class="button button-primary">
                        + Cadastrar usuário
                    </button>

                </div>

            </form>

        </section>

        <aside class="side-card">

            <div class="side-icon">♙</div>

            <h3>Orientações de cadastro</h3>

            <p>
                Confira estas informações antes de criar a conta.
            </p>

            <div class="tip">
                <div class="tip-check">✓</div>
                <div>
                    <strong>Dados corretos</strong>
                    <span>Utilize o nome completo e um e-mail válido.</span>
                </div>
            </div>

            <div class="tip">
                <div class="tip-check">✓</div>
                <div>
                    <strong>Senha segura</strong>
                    <span>A senha deve possuir pelo menos 8 caracteres.</span>
                </div>
            </div>

            <div class="tip">
                <div class="tip-check">✓</div>
                <div>
                    <strong>Permissões adequadas</strong>
                    <span>
                        Conceda acesso de administrador apenas a quem
                        realmente precisa dessas permissões.
                    </span>
                </div>
            </div>

            <div class="security-note">
                <strong>Segurança:</strong> as senhas são armazenadas
                utilizando hash, e o formulário mantém a proteção CSRF.
            </div>

        </aside>

    </div>

    <footer class="footer">
        Ferrorama · Sistema de gerenciamento ferroviário
    </footer>

</main>

<script>
    document.querySelectorAll(".password-toggle").forEach(function (botao) {
        botao.addEventListener("click", function () {
            const campo = document.getElementById(this.dataset.target);
            const mostrar = campo.type === "password";

            campo.type = mostrar ? "text" : "password";
            this.textContent = mostrar ? "Ocultar" : "Mostrar";
            this.setAttribute(
                "aria-label",
                mostrar ? "Ocultar senha" : "Mostrar senha"
            );
        });
    });
</script>

</body>
</html>
