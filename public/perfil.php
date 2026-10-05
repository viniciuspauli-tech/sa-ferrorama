<?php

/*
 * Tela "Meu perfil" (equivalente ao home.php do gabarito).
 * Mostra os dados do usuário logado, com botões de acordo com o perfil.
 */

require_once "../infra/auth.php";
require_once "../infra/connect.php";

$id = (int) $_SESSION["usuario_id"];

$sql = "SELECT nome, email, perfil FROM usuarios WHERE id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();

$usuario = $stmt->get_result()->fetch_assoc();

$stmt->close();

$eAdmin = ($_SESSION["usuario_perfil"] === "administrador");

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Meu perfil - Ferrorama</title>

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background: #f2f2f2;
            padding: 30px;
        }

        .container {
            max-width: 700px;
            margin: auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
        }

        h1 {
            margin-bottom: 20px;
        }

        .botoes {
            margin-bottom: 20px;
        }

        .botao {
            display: inline-block;
            padding: 10px 15px;
            background: #111827;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin-right: 8px;
        }

        .botao:hover {
            background: #374151;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }

        th {
            background: #f3f4f6;
        }

        .voltar {
            display: inline-block;
            margin-top: 20px;
            margin-right: 15px;
            color: #333;
            text-decoration: none;
        }

    </style>

</head>

<body>

<div class="container">

    <h1>
        Bem-vindo, <?= htmlspecialchars($_SESSION["usuario_nome"]) ?>!
    </h1>

    <div class="botoes">

        <?php if ($eAdmin): ?>

            <a class="botao" href="usuarios/index.php">Administração</a>

        <?php endif; ?>

        <a class="botao" href="sistema.php">Sistema</a>

    </div>

    <table>

        <thead>

            <tr>
                <th>Nome</th>
                <th>E-mail</th>
                <th>Perfil</th>
            </tr>

        </thead>

        <tbody>

        <?php if ($usuario): ?>

            <tr>

                <td><?= htmlspecialchars($usuario["nome"]) ?></td>

                <td><?= htmlspecialchars($usuario["email"]) ?></td>

                <td>
                    <?= $usuario["perfil"] === "administrador"
                        ? "Administrador"
                        : "Usuário" ?>
                </td>

            </tr>

        <?php else: ?>

            <tr>
                <td colspan="3">Nenhum usuário encontrado</td>
            </tr>

        <?php endif; ?>

        </tbody>

    </table>

    <a href="sistema.php" class="voltar">← Voltar para o sistema</a>

    <a href="logout.php" class="voltar">Sair</a>

</div>

</body>

</html>