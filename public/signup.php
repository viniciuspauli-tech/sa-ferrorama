<?php

require_once "../infra/connect.php";

$mensagem = "";
$erro = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nome = trim($_POST["nome"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $senha = $_POST["senha"] ?? "";
    $confirmar_senha = $_POST["confirmar_senha"] ?? "";

    if (
        empty($nome) ||
        empty($email) ||
        empty($senha) ||
        empty($confirmar_senha)
    ) {

        $erro = "Preencha todos os campos.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $erro = "Digite um e-mail válido.";

    } elseif ($senha !== $confirmar_senha) {

        $erro = "As senhas não são iguais.";

    } elseif (strlen($senha) < 8) {

        $erro = "A senha deve possuir pelo menos 8 caracteres.";

    } else {

        // Verifica se o e-mail já existe
        $sql = "SELECT id FROM usuarios WHERE email = ?";

        $stmt = $conn->prepare($sql);

        if (!$stmt) {

            $erro = "Não foi possível realizar o cadastro.";

        } else {

            $stmt->bind_param("s", $email);

            $stmt->execute();

            $resultado = $stmt->get_result();

            if ($resultado->num_rows > 0) {

                $erro = "Este e-mail já está cadastrado.";

            } else {

                // Protege a senha
                $senha_hash = password_hash(
                    $senha,
                    PASSWORD_DEFAULT
                );

                // Cadastra sempre como usuário comum
                $sql = "INSERT INTO usuarios
                        (nome, email, senha, perfil)
                        VALUES (?, ?, ?, 'usuario')";

                $stmt->close();

                $stmt = $conn->prepare($sql);

                if (!$stmt) {

                    $erro = "Não foi possível realizar o cadastro.";

                } else {

                    $stmt->bind_param(
                        "sss",
                        $nome,
                        $email,
                        $senha_hash
                    );

                    if ($stmt->execute()) {

                        $mensagem = "Cadastro realizado com sucesso!";

                    } else {

                        $erro = "Erro ao realizar o cadastro.";
                    }
                }
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

    <title>Criar conta | Ferrorama</title>

    <link rel="stylesheet" href="../style/signup.css">
</head>

<body class="auth-page">

    <main class="auth-container">

        <!-- Painel lateral da marca -->
        <aside class="auth-sidebar">

            <a href="../index.php" class="brand">
                <span class="brand-icon">F</span>
                <span>Ferro<span class="brand-highlight">rama</span></span>
            </a>

            <div class="sidebar-content">
                <span class="sidebar-label">FAÇA PARTE</span>

                <h2>
                    Tudo pronto para
                    <span>começar?</span>
                </h2>

                <div class="sidebar-divider"></div>

                <p>
                    Crie sua conta e descubra uma nova forma
                    de acompanhar sua experiência no Ferrorama.
                </p>
            </div>

            <div class="sidebar-decoration"></div>

        </aside>

        <!-- Formulário de cadastro -->
        <section class="auth-content">

            <div class="auth-header">
                <span class="form-eyebrow">FAÇA PARTE</span>
                <h1>Crie sua conta</h1>
                <p>Preencha os dados abaixo para começar.</p>
            </div>

            <?php if (!empty($erro)): ?>
                <div class="mensagem-erro" role="alert">
                    <?= htmlspecialchars($erro) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($mensagem)): ?>
                <div class="mensagem-sucesso" role="status">
                    <?= htmlspecialchars($mensagem) ?>

                    <a href="sigin.php">Ir para o login</a>
                </div>
            <?php endif; ?>

            <form method="POST" class="auth-form">

                <div class="form-group">
                    <label for="nome">Nome completo</label>

                    <div class="input-wrapper">
                        <span class="input-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24">
                                <circle cx="12" cy="8" r="4"></circle>
                                <path d="M4 21v-2a8 8 0 0 1 16 0v2"></path>
                            </svg>
                        </span>

                        <input
                            type="text"
                            id="nome"
                            name="nome"
                            placeholder="Digite seu nome completo"
                            value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>"
                            autocomplete="name"
                            required
                        >
                    </div>
                </div>

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
                            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                            autocomplete="email"
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
                            placeholder="Mínimo de 8 caracteres"
                            autocomplete="new-password"
                            minlength="8"
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

                <div class="form-group">
                    <label for="confirmar_senha">Confirmar senha</label>

                    <div class="input-wrapper">
                        <span class="input-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24">
                                <rect x="4" y="10" width="16" height="11" rx="2"></rect>
                                <path d="M8 10V7a4 4 0 0 1 8 0v3"></path>
                            </svg>
                        </span>

                        <input
                            type="password"
                            id="confirmar_senha"
                            name="confirmar_senha"
                            placeholder="Digite a senha novamente"
                            autocomplete="new-password"
                            minlength="8"
                            required
                        >

                        <button
                            type="button"
                            class="password-toggle"
                            data-target="confirmar_senha"
                            aria-label="Mostrar confirmação de senha"
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
                    <span>Criar minha conta</span>
                    <span aria-hidden="true">→</span>
                </button>

            </form>

            <div class="auth-footer">
                <span>Já possui uma conta?</span>
                <a href="sigin.php">Fazer login</a>
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