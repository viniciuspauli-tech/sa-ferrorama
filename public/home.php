
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

    
<link rel="stylesheet" href="../style/style.css">
</head>

<body class="page-public_home_php">

<header class="topbar">

    <div class="topbar-inner">

        <a href="sistema.php" class="brand">

            <div class="brand-icon">

                <svg viewBox="0 0 24 24"
                     fill="none"
                     stroke="currentColor"
                     stroke-width="1.8"
                     stroke-linecap="round"
                     stroke-linejoin="round"
                     aria-hidden="true">

                    <rect x="5" y="3" width="14" height="15" rx="4"></rect>
                    <path d="M8 7h8M8 12h8M8 18l-2 3M16 18l2 3M5 15h14"></path>
                    <circle cx="9" cy="15" r="1"></circle>
                    <circle cx="15" cy="15" r="1"></circle>

                </svg>

            </div>

            <div>
                <div class="brand-name">Ferrorama</div>
                <div class="brand-subtitle">Sistema ferroviário</div>
            </div>

        </a>

        <div class="top-user">

            <div class="top-user-icon">♙</div>

            <span>
                Área do usuário
                <?= $eAdmin ? "Administrador" : "Usuário" ?>
            </span>

        </div>

    </div>

</header>

<section class="hero">

    <div class="hero-inner">

        <div class="eyebrow">Minha conta</div>

        <h1>
            Bem-vindo, <span><?= $nome ?></span>
        </h1>

        <p>
            Consulte as informações da sua conta e acesse as funcionalidades
            do sistema Ferrorama de forma simples e segura.
        </p>

    </div>

</section>

<main class="main">

    <div class="section-heading">

        <h2>Meu perfil</h2>

        <p>
            Confira seus dados de acesso e escolha para onde deseja ir.
        </p>

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
                <span>
                    <?= $email !== "" ? $email : "E-mail não disponível" ?>
                </span>
            </div>

            <div class="info-label">Nível de acesso</div>

            <div class="info-value">
                <span class="info-icon">♙</span>
                <span>
                    <?= htmlspecialchars($cargo, ENT_QUOTES, "UTF-8") ?>
                </span>
            </div>

            <div class="actions">


                    <a href="sistema.php" class="button button-primary">
                        Voltar
                    </a>


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
