
<?php

require_once "../../infra/adm.php";
require_once "../../infra/connect.php";

$erro = "";
$sucesso = "";

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id) {
    die("Usuário inválido.");
}

$nome = "";
$email = "";
$perfil = "usuario";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $idPost = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);

    $nome = trim($_POST["nome"] ?? "");
    $email = trim($_POST["email"] ?? "");
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

            if ($senha !== "") {

                $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

                $sql = "UPDATE usuarios
                        SET nome = ?, email = ?, senha = ?, perfil = ?
                        WHERE id = ?";

                $stmt = mysqli_prepare($conn, $sql);

                mysqli_stmt_bind_param(
                    $stmt,
                    "ssssi",
                    $nome,
                    $email,
                    $senhaHash,
                    $perfil,
                    $id
                );

            } else {

                $sql = "UPDATE usuarios
                        SET nome = ?, email = ?, perfil = ?
                        WHERE id = ?";

                $stmt = mysqli_prepare($conn, $sql);

                mysqli_stmt_bind_param(
                    $stmt,
                    "sssi",
                    $nome,
                    $email,
                    $perfil,
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

$sql = "SELECT id, nome, email, perfil
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
            width: 42px;
            height: 42px;
            display: grid;
            place-items: center;
            background: white;
            border-radius: 13px;
            font-size: 22px;
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
            <div class="brand-icon">🚆</div>

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

</body>
</html>
