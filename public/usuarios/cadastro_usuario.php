<?php

require_once "../../infra/auth.php";

if (($_SESSION["usuario_perfil"] ?? "") !== "administrador") {
    header("Location: ../home.php");
    exit;
}

require_once "../../infra/connect.php";

// Token CSRF
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

$erro = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email           = trim($_POST['email'] ?? '');
    $senha           = $_POST['senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';
    $perfil = $_POST['perfil'] ?? 'usuario';

    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {

        $erro = "Requisição inválida.";

    } elseif ($email === '' || $senha === '' || $confirmar_senha === '') {
        $erro = "Preencha todos os campos.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $erro = "Digite um e-mail válido.";

    } elseif (strlen($senha) < 8) {

        $erro = "A senha deve possuir pelo menos 8 caracteres.";

    } elseif ($senha !== $confirmar_senha) {

        $erro = "As senhas não são iguais.";

    } elseif ($perfil !== 'usuario' && $perfil !== 'adm') {
        $erro = "perfil inválido.";

    } else {

        // Verifica se o e-mail já existe
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

                // Criptografa a senha
                $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

                // Cadastra o usuário
                $stmt = mysqli_prepare(
                    $conn,
                    "INSERT INTO usuarios (nome, email, senha, perfil) VALUES (?, ?, ?, ?)"
                );

                if (!$stmt) {

                    $erro = "Erro ao preparar o cadastro.";

                } else {
                    mysqli_stmt_bind_param($stmt, "sss", $email, $senha_hash, $perfil);

                    if (mysqli_stmt_execute($stmt)) {

                        mysqli_stmt_close($stmt);

                        $_SESSION['msg'] = "Usuário cadastrado com sucesso!";
                        header('Location: adm.php');
                        exit();
                    } else {

                        $erro = "Não foi possível cadastrar o usuário.";

                        mysqli_stmt_close($stmt);
                    }
                }
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Cadastrar usuário - Ferrorama</title>

   

</head>

<body>

<div class="container">

    <h1>Cadastrar usuário</h1>

    <?php if ($erro !== ""): ?>

        <div class="erro">
            <?= htmlspecialchars($erro) ?>
        </div>

    <?php endif; ?>

    <form method="POST">

        <input
            type="hidden"
            name="csrf"
            value="<?= htmlspecialchars($_SESSION['csrf']) ?>"
        >

        <label for="nome">Nome</label>
<input
    type="text"
    id="nome"
    name="nome"
    maxlength="100"
    value="<?= htmlspecialchars($nome ?? '') ?>"
    required
>
        <label for="email">E-mail</label>

        <input
            type="email"
            id="email"
            name="email"
            maxlength="150"
            value="<?= htmlspecialchars($email ?? '') ?>"
            required
        >

        <label for="senha">Senha</label>

        <input
            type="password"
            id="senha"
            name="senha"
            minlength="8"
            required
        >

        <label for="confirmar_senha">Confirmar senha</label>

        <input
            type="password"
            id="confirmar_senha"
            name="confirmar_senha"
            minlength="8"
            required
        >

        <label for="perfil">Cargo</label>
        <select id="perfil" name="perfil" required>
            <option value=usuario">Usuário comum</option>
            <option value="adm">Administrador</option>
        </select>

        <button type="submit">
            Cadastrar
        </button>

    </form>

    <a href="pagina_adm.php" class="voltar">
        ← Voltar para usuários
    </a>

</div>

</body>

</html>