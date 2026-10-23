
<?php

require_once "../infra/auth.php";
require_once "../infra/connect.php";

$id = (int) $_SESSION["usuario_id"];

$sql = "SELECT email, perfil FROM usuarios WHERE id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();

$usuario = $stmt->get_result()->fetch_assoc();

$stmt->close();

$eAdmin = ($_SESSION["usuario_perfil"] ?? "") === "administrador";

$nome = htmlspecialchars(
    $_SESSION["usuario_nome"] ?? "Usuário",
    ENT_QUOTES,
    "UTF-8"
);

$email = htmlspecialchars(
    $usuario["email"] ?? "",
    ENT_QUOTES,
    "UTF-8"
);

$cargo = ($usuario["perfil"] ?? "") === "administrador"
    ? "Administrador"
    : "Usuário comum";

$iniciais = strtoupper(
    substr($_SESSION["usuario_nome"] ?? "U", 0, 1)
);

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Meu perfil - Ferrorama</title>

    <link
        href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

    <style>
        :root {
            --navy: #0c192b;
            --navy-light: #14243a;
            --purple: #6554e8;
            --purple-light: #eeecff;
            --background: #f2f4f9;
            --white: #ffffff;
            --text: #132238;
            --muted: #73819a;
            --border: #e0e6f0;
            --danger: #dc414b;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            min-height: 100vh;
            font-family: "Roboto", sans-serif;
            background: var(--background);
            color: var(--text);
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

        .top-user {
            display: flex;
            align-items: center;
            gap: 11px;
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
            font-size: 17px;
        }

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
            max-width: 1000px;
            margin: auto;
        }

        .eyebrow {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 17px;
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
            margin-bottom: 13px;
            font-size: clamp(28px, 4vw, 38px);
            line-height: 1.2;
            letter-spacing: -1.2px;
        }

        .hero h1 span {
            color: #a69aff;
        }

        .hero p {
            max-width: 580px;
            color: #c7d2e7;
            font-size: 14px;
            line-height: 1.8;
        }

        .main {
            max-width: 1000px;
            margin: 0 auto;
            padding: 36px 24px 56px;
        }

        .section-heading {
            margin-bottom: 22px;
        }

        .section-heading h2 {
            font-size: 20px;
            letter-spacing: -.4px;
        }

        .section-heading p {
            margin-top: 7px;
            color: var(--muted);
            font-size: 13px;
            line-height: 1.6;
        }

        .profile-card {
            display: grid;
            grid-template-columns: 230px minmax(0, 1fr);
            overflow: hidden;
            background: white;
            border: 1px solid var(--border);
            border-radius: 18px;
            box-shadow: 0 8px 28px rgba(23, 40, 70, .045);
        }

        .profile-side {
            padding: 30px 24px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            background: linear-gradient(180deg, #fafaff, #f1f0ff);
            border-right: 1px solid var(--border);
        }

        .avatar {
            width: 82px;
            height: 82px;
            margin-bottom: 17px;
            display: grid;
            place-items: center;
            color: white;
            background: linear-gradient(135deg, #7665f1, #4735c8);
            border-radius: 25px;
            box-shadow: 0 9px 20px rgba(101,84,232,.22);
            font-size: 32px;
            font-weight: 800;
        }

        .profile-side h2 {
            max-width: 100%;
            overflow-wrap: anywhere;
            font-size: 17px;
            line-height: 1.4;
        }

        .role-badge {
            margin-top: 11px;
            padding: 7px 12px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border-radius: 999px;
            color: #4b3ac8;
            background: #e8e5ff;
            font-size: 11px;
            font-weight: 700;
        }

        .profile-content {
            padding: 30px;
        }

        .profile-content h3 {
            margin-bottom: 7px;
            font-size: 17px;
        }

        .profile-description {
            margin-bottom: 24px;
            color: var(--muted);
            font-size: 13px;
            line-height: 1.6;
        }

        .info-label {
            margin-bottom: 8px;
            color: var(--muted);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .7px;
            text-transform: uppercase;
        }

        .info-value {
            min-height: 49px;
            margin-bottom: 22px;
            padding: 14px 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            overflow-wrap: anywhere;
            background: #f8f9fc;
            border: 1px solid var(--border);
            border-radius: 10px;
            font-size: 14px;
        }

        .info-icon {
            color: var(--purple);
            font-size: 17px;
        }

        .actions {
            margin-top: 5px;
            display: flex;
            flex-wrap: wrap;
            gap: 11px;
        }

        .button {
            min-height: 44px;
            padding: 0 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            border: 1px solid transparent;
            border-radius: 10px;
            font-family: inherit;
            font-size: 13px;
            font-weight: 700;
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
            background: #5140d4;
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
            background: #fff;
            border-color: #f0d9dc;
        }

        .button-danger:hover {
            background: #fff4f5;
        }

        .footer {
            padding-top: 24px;
            color: #8a96aa;
            text-align: center;
            font-size: 11px;
        }

        @media (max-width: 650px) {
            .topbar-inner {
                padding: 0 18px;
            }

            .top-user {
                font-size: 0;
            }

            .top-user-icon {
                font-size: 17px;
            }

            .hero {
                padding: 35px 20px;
            }

            .main {
                padding: 26px 16px 40px;
            }

            .profile-card {
                grid-template-columns: 1fr;
            }

            .profile-side {
                padding: 28px 20px;
                border-right: 0;
                border-bottom: 1px solid var(--border);
            }

            .profile-content {
                padding: 24px 20px;
            }

            .actions {
                flex-direction: column;
            }

            .button {
                width: 100%;
            }
        }
    </style>
</head>

<body>

<header class="topbar">
    <div class="topbar-inner">
        <a href="sistema.php" class="brand">
            <div class="brand-icon">🚆</div>
            <div>
                <div class="brand-name">Ferrorama</div>
                <div class="brand-subtitle">Sistema ferroviário</div>
            </div>
        </a>

        <div class="top-user">
            <div class="top-user-icon">♙</div>
            <span>Área do usuário <?= $eAdmin ? "Administrador" : "Usuário" ?></span>
        </div>
    </div>
</header>

<section class="hero">
    <div class="hero-inner">
        <div class="eyebrow">Minha conta</div>

        <h1>Bem-vindo, <span><?= $nome ?></span></h1>

        <p>
            Consulte as informações da sua conta e acesse as funcionalidades
            do sistema Ferrorama de forma simples e segura.
        </p>
    </div>
</section>

<main class="main">

    <div class="section-heading">
        <h2>Meu perfil</h2>
        <p>Confira seus dados de acesso e escolha para onde deseja ir.</p>
    </div>

    <section class="profile-card">

        <div class="profile-side">
            <div class="avatar">
                <?= htmlspecialchars($iniciais, ENT_QUOTES, "UTF-8") ?>
            </div>

            <h2><?= $nome ?></h2>

            <div class="role-badge">
                <span>●</span>
                <?= htmlspecialchars($cargo, ENT_QUOTES, "UTF-8") ?>
            </div>
        </div>

        <div class="profile-content">

            <h3>Informações da conta</h3>

            <p class="profile-description">
                Estes são os dados associados à sua conta no sistema.
            </p>

            <div class="info-label">E-mail cadastrado</div>

            <div class="info-value">
                <span class="info-icon">✉</span>
                <span><?= $email !== "" ? $email : "E-mail não disponível" ?></span>
            </div>

            <div class="info-label">Nível de acesso</div>

            <div class="info-value">
                <span class="info-icon">♙</span>
                <span><?= htmlspecialchars($cargo, ENT_QUOTES, "UTF-8") ?></span>
            </div>

            <div class="actions">

                <?php if ($eAdmin): ?>
                    <a href="usuarios/pagina_adm.php" class="button button-primary">
                        Voltar
                    </a>
                <?php endif; ?>


                <a href="logout.php" class="button button-danger">
                    ↪ Sair da conta
                </a>

            </div>
        </div>

    </section>

    <footer class="footer">
        Ferrorama · Sistema de gerenciamento ferroviário
    </footer>

</main>

</body>
</html>
