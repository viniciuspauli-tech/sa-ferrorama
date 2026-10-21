<?php

session_start();

require_once "../infra/connect.php";

$erro = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $senha = $_POST["senha"] ?? "";

    if (empty($email) || empty($senha)) {

        $erro = "Preencha todos os campos.";

    } else {

        $sql = "SELECT id, nome, email, senha, perfil
                FROM usuarios
                WHERE email = ?";

        $stmt = $conn->prepare($sql);

        if (!$stmt) {

            $erro = "Erro ao preparar a consulta.";

        } else {

            $stmt->bind_param("s", $email);

            $stmt->execute();

            $resultado = $stmt->get_result();

            if ($resultado->num_rows === 1) {

                $usuario = $resultado->fetch_assoc();

                if (password_verify($senha, $usuario["senha"])) {

                    session_regenerate_id(true);

                    $_SESSION["usuario_id"] = $usuario["id"];
                    $_SESSION["usuario_nome"] = $usuario["nome"];
                    $_SESSION["usuario_email"] = $usuario["email"];
                    $_SESSION["usuario_perfil"] = $usuario["perfil"];

                    header("Location: sistema.php");
                    exit;

                } else {

                    $erro = "E-mail ou senha inválidos.";

                }

            } else {

                $erro = "E-mail ou senha inválidos.";

            }

            $stmt->close();
        }
    }
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Entrar | Ferrorama</title>

    <link rel="stylesheet" href="../style/signup.css">
</head>

<body class="auth-page">

    <main class="auth-container">

        <!-- Painel lateral -->
        <aside class="auth-sidebar">

            <a href="../index.php" class="brand">
                <span class="brand-icon">F</span>
                <span>Ferro<span class="brand-highlight">rama</span></span>
            </a>

            <div class="sidebar-content">

                <span class="sidebar-label">BEM-VINDO DE VOLTA</span>

                <h2>
                    Bom ter você
                    <span>por aqui!</span>
                </h2>

                <div class="sidebar-divider"></div>

                <p>
                    Entre na sua conta e continue acompanhando
                    sua experiência no Ferrorama.
                </p>

            </div>

            <div class="sidebar-decoration"></div>

        </aside>

        <!-- Formulário de login -->
        <section class="auth-content">

            <div class="auth-header">

                <span class="form-eyebrow">ACESSE SUA CONTA</span>

                <h1>Faça login</h1>

                <p>Informe seus dados para continuar.</p>

            </div>

            <?php if (!empty($erro)): ?>
                <div class="mensagem-erro" role="alert">
                    <?= htmlspecialchars($erro) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($mensagem)): ?>
                <div class="mensagem-sucesso" role="status">
                    <?= htmlspecialchars($mensagem) ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="auth-form">

                <div class="form-group">

                    <label for="email">E-mail</label>

                    <div class="input-wrapper">

                        <span class="input-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24">
                                <rect x="3" y="5" width="18" height="14" rx="2"></rect>
                                <path d="m3 7 9 6 9-6"></path>
                            </svg>
                        </span>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            placeholder="Digite seu e-mail"
                            autocomplete="email"
                            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                            required
                        >

                    </div>

                </div>

                <div class="form-group">

                    <label for="senha">Senha</label>

                    <div class="input-wrapper">

                        <span class="input-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24">
                                <rect x="4" y="10" width="16" height="11" rx="2"></rect>
                                <path d="M8 10V7a4 4 0 0 1 8 0v3"></path>
                            </svg>
                        </span>

                        <input
                            type="password"
                            id="senha"
                            name="senha"
                            placeholder="Digite sua senha"
                            autocomplete="current-password"
                            required
                        >

                        <button
                            type="button"
                            class="password-toggle"
                            data-target="senha"
                            aria-label="Mostrar senha"
                            aria-pressed="false"
                        >
                            <svg viewBox="0 0 24 24">
                                <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>

                    </div>

                </div>

                <button type="submit" class="auth-submit">
                    <span>Entrar na minha conta</span>
                    <span aria-hidden="true">→</span>
                </button>

            </form>

            <div class="auth-footer">
                <span>Ainda não possui uma conta?</span>
                <a href="signup.php">Criar conta</a>
            </div>

        </section>

    </main>

    <script>
        document.querySelectorAll(".password-toggle").forEach(function (button) {
            button.addEventListener("click", function () {
                const input = document.getElementById(this.dataset.target);
                const mostrar = input.type === "password";

                input.type = mostrar ? "text" : "password";

                this.setAttribute("aria-pressed", String(mostrar));
                this.setAttribute(
                    "aria-label",
                    mostrar ? "Ocultar senha" : "Mostrar senha"
                );
            });
        });
    </script>

</body>
</html>
