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

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Login - Ferrorama</title>

    <link
        rel="stylesheet"
        href="../style/style.css"
    >

</head>

<body>

    <div class="login-container">

        <h1>Ferrorama</h1>

        <h2>Login</h2>

        <?php if (!empty($erro)): ?>

            <div class="mensagem-erro">
                <?= htmlspecialchars($erro) ?>
            </div>

        <?php endif; ?>

        <form method="POST">

            <label for="email">
                E-mail
            </label>

            <input
                type="email"
                id="email"
                name="email"
                required
            >

            <label for="senha">
                Senha
            </label>

            <input
                type="password"
                id="senha"
                name="senha"
                required
            >

            <button type="submit">
                Entrar
            </button>

        </form>

        <p>
            Ainda não possui uma conta?
            <a href="signup.php">
                Cadastre-se
            </a>
        </p>

    </div>

</body>

</html>