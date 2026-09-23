<?php

require_once "../../infra/admin.php";
require_once "../../infra/connect.php";

$erro = "";
$sucesso = "";

$id = filter_input(
    INPUT_GET,
    "id",
    FILTER_VALIDATE_INT
);

if (!$id) {

    die("Usuário inválido.");

}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $id = filter_input(
        INPUT_POST,
        "id",
        FILTER_VALIDATE_INT
    );

    $nome = trim($_POST["nome"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $senha = $_POST["senha"] ?? "";
    $perfil = $_POST["perfil"] ?? "";

    if (!$id) {

        $erro = "Usuário inválido.";

    } elseif (empty($nome) || empty($email)) {

        $erro = "Nome e e-mail são obrigatórios.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $erro = "Digite um e-mail válido.";

    } elseif (
        $perfil !== "usuario" &&
        $perfil !== "administrador"
    ) {

        $erro = "Perfil inválido.";

    } elseif (
        !empty($senha) &&
        strlen($senha) < 8
    ) {

        $erro = "A senha deve possuir pelo menos 8 caracteres.";

    } else {

        /*
         * Verifica se outro usuário
         * já possui este e-mail.
         */

        $sql = "SELECT id
                FROM usuarios
                WHERE email = ?
                AND id != ?";

        $stmt = mysqli_prepare($conn, $sql);

        mysqli_stmt_bind_param(
            $stmt,
            "si",
            $email,
            $id
        );

        mysqli_stmt_execute($stmt);

        $resultado = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($resultado) > 0) {

            $erro = "Este e-mail já está sendo utilizado.";

        } else {

            if (!empty($senha)) {

                $senha_hash = password_hash(
                    $senha,
                    PASSWORD_DEFAULT
                );

                $sql = "UPDATE usuarios
                        SET nome = ?,
                            email = ?,
                            senha = ?,
                            perfil = ?
                        WHERE id = ?";

                $stmt = mysqli_prepare($conn, $sql);

                mysqli_stmt_bind_param(
                    $stmt,
                    "ssssi",
                    $nome,
                    $email,
                    $senha_hash,
                    $perfil,
                    $id
                );

            } else {

                $sql = "UPDATE usuarios
                        SET nome = ?,
                            email = ?,
                            perfil = ?
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

                $sucesso =
                    "Usuário atualizado com sucesso.";

            } else {

                $erro =
                    "Não foi possível atualizar o usuário.";
            }

        }

        mysqli_stmt_close($stmt);
    }
}

/*
 * Busca os dados atuais do usuário.
 */

$sql = "SELECT id, nome, email, perfil
        FROM usuarios
        WHERE id = ?";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id
);

mysqli_stmt_execute($stmt);

$resultado = mysqli_stmt_get_result($stmt);

$usuario = mysqli_fetch_assoc($resultado);

if (!$usuario) {

    die("Usuário não encontrado.");

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

    <title>Editar usuário - Ferrorama</title>

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

    <h1>Editar usuário</h1>

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

        <input
            type="hidden"
            name="id"
            value="<?= htmlspecialchars($usuario["id"]) ?>"
        >

        <label for="nome">
            Nome
        </label>

        <input
            type="text"
            id="nome"
            name="nome"
            value="<?= htmlspecialchars($usuario["nome"]) ?>"
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
            value="<?= htmlspecialchars($usuario["email"]) ?>"
            maxlength="150"
            required
        >

        <label for="senha">
            Nova senha
        </label>

        <input
            type="password"
            id="senha"
            name="senha"
            minlength="8"
        >

        <small>
            Deixe vazio para manter a senha atual.
        </small>

        <label for="perfil">
            Perfil
        </label>

        <select
            id="perfil"
            name="perfil"
            required
        >

            <option
                value="usuario"
                <?= $usuario["perfil"] === "usuario" ? "selected" : "" ?>
            >
                Usuário
            </option>

            <option
                value="administrador"
                <?= $usuario["perfil"] === "administrador" ? "selected" : "" ?>
            >
                Administrador
            </option>

        </select>

        <button type="submit">
            Salvar alterações
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