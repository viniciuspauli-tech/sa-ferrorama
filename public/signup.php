<?php

require_once "../conexao/connect.php";

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

    } elseif (strlen($senha) < 6) {

        $erro = "A senha deve possuir pelo menos 6 caracteres.";

    } else {

        $sql = "SELECT id FROM usuarios WHERE email = ?";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param("s", $email);

        $stmt->execute();

        $resultado = $stmt->get_result();

        if ($resultado->num_rows > 0) {

            $erro = "Este e-mail já está cadastrado.";

        } else {

            $senha_hash = password_hash(
                $senha,
                PASSWORD_DEFAULT
            );

            $sql = "INSERT INTO usuarios
                    (nome, email, senha)
                    VALUES (?, ?, ?)";

            $stmt = $conn->prepare($sql);

            $stmt->bind_param(
                "sss",
                $nome,
                $email,
                $senha_hash
            );

            if ($stmt->execute()) {

                $mensagem =
                    "Cadastro realizado com sucesso!";

            } else {

                $erro =
                    "Erro ao realizar o cadastro.";
            }
        }

        $stmt->close();
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

    <title>Cadastro - Ferrorama</title>

    <link
        rel="stylesheet"
        href="../style/signup.css"
    >

</head>

<body>

    <div class="login-container">

        <h1>Ferrorama</h1>

        <h2>Criar conta</h2>

        <?php if (!empty($erro)): ?>

            <div class="mensagem-erro">
                <?= htmlspecialchars($erro) ?>
            </div>

        <?php endif; ?>

        <?php if (!empty($mensagem)): ?>

            <div class="mensagem-sucesso">

                <?= htmlspecialchars($mensagem) ?>

                <br>

                <a href="sigin.php">
                    Ir para o login
                </a>

            </div>

        <?php endif; ?>

        <form method="POST">

            <label for="nome">
                Nome
            </label>

            <input
                type="text"
                id="nome"
                name="nome"
                required
            >

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

            <label for="confirmar_senha">
                Confirmar senha
            </label>

            <input
                type="password"
                id="confirmar_senha"
                name="confirmar_senha"
                required
            >

            <button type="submit">
                Cadastrar
            </button>

        </form>

        <p>
            Já possui uma conta?
            <a href="sigin.php">
                Fazer login
            </a>
        </p>

    </div>

</body>

</html>