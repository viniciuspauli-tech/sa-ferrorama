<?php

require_once "../../infra/admin.php";
require_once "../../infra/connect.php";

$erro = "";
$sucesso = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nome = trim($_POST["nome"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $senha = $_POST["senha"] ?? "";
    $confirmar_senha = $_POST["confirmar_senha"] ?? "";
    $perfil = $_POST["perfil"] ?? "usuario";

    if (
        empty($nome) ||
        empty($email) ||
        empty($senha) ||
        empty($confirmar_senha)
    ) {

        $erro = "Preencha todos os campos.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $erro = "Digite um e-mail válido.";

    } elseif (strlen($senha) < 8) {

        $erro = "A senha deve possuir pelo menos 8 caracteres.";

    } elseif ($senha !== $confirmar_senha) {

        $erro = "As senhas não são iguais.";

    } elseif (
        $perfil !== "usuario" &&
        $perfil !== "administrador"
    ) {

        $erro = "Perfil inválido.";

    } else {

        $sql = "SELECT id
                FROM usuarios
                WHERE email = ?";

        $stmt = mysqli_prepare($conn, $sql);

        if (!$stmt) {

            $erro = "Erro ao preparar a consulta.";

        } else {

            mysqli_stmt_bind_param(
                $stmt,
                "s",
                $email
            );

            mysqli_stmt_execute($stmt);

            $resultado = mysqli_stmt_get_result($stmt);

            if (mysqli_num_rows($resultado) > 0) {

                $erro = "Este e-mail já está cadastrado.";

            } else {

                $senha_hash = password_hash(
                    $senha,
                    PASSWORD_DEFAULT
                );

                $sql = "INSERT INTO usuarios
                        (nome, email, senha, perfil)
                        VALUES (?, ?, ?, ?)";

                $stmt = mysqli_prepare($conn, $sql);

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

                        $sucesso =
                            "Usuário cadastrado com sucesso.";

                    } else {

                        $erro =
                            "Não foi possível cadastrar o usuário.";
                    }

                }

            }

            mysqli_stmt_close($stmt);
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

    <title>Cadastrar usuário - Ferrorama</title>

    <style>

        * {
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background: #f2f2f2;
            padding: 30px;
        }

        .container {
            max-width: 600px;
            margin: auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
        }

        h1 {
            margin-bottom: 25px;
        }

        label {
            display: block;
            margin-top: 15px;
            margin-bottom: 5px;
        }

        input,
        select {
            width: 100%;
            padding: 11px;
            border: 1px solid #ccc;
            border-radius: 6px;
        }

        button {
            width: 100%;
            margin-top: 25px;
            padding: 12px;
            border: none;
            border-radius: 6px;
            background: #111827;
            color: white;
            cursor: pointer;
        }

        button:hover {
            background: #374151;
        }

        .erro {
            background: #fee2e2;
            color: #991b1b;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 15px;
        }

        .sucesso {
            background: #dcfce7;
            color: #166534;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 15px;
        }

        .voltar {
            display: inline-block;
            margin-top: 20px;
            color: #333;
            text-decoration: none;
        }

    </style>

</head>

<body>

<div class="container">

    <h1>Cadastrar usuário</h1>

    <?php if (!empty($erro)): ?>

        <div class="erro">
            <?= htmlspecialchars($erro) ?>
        </div>

    <?php endif; ?>

    <?php if (!empty($sucesso)): ?>

        <div class="sucesso">
            <?= htmlspecialchars($sucesso) ?>
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
            maxlength="100"
            required
        >

        <label for="email">
            E-mail
        </label>

        <input
            type="email"
            id="email"
            name="email"
            maxlength="150"
            required
        >

        <label for="senha">
            Senha
        </label>

        <input
            type="password"
            id="senha"
            name="senha"
            minlength="8"
            required
        >

        <label for="confirmar_senha">
            Confirmar senha
        </label>

        <input
            type="password"
            id="confirmar_senha"
            name="confirmar_senha"
            minlength="8"
            required
        >

        <label for="perfil">
            Perfil
        </label>

        <select
            id="perfil"
            name="perfil"
            required
        >

            <option value="usuario">
                Usuário
            </option>

            <option value="administrador">
                Administrador
            </option>

        </select>

        <button type="submit">
            Cadastrar
        </button>

    </form>

    <a
        href="index.php"
        class="voltar"
    >
        ← Voltar para usuários
    </a>

</div>

</body>

</html>