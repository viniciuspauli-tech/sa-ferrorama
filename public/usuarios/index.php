<?php

require_once "../../infra/admin.php";
require_once "../../infra/connect.php";

$sql = "SELECT id, nome, email, perfil, criado_em
        FROM usuarios
        ORDER BY id DESC";

$resultado = mysqli_query($conn, $sql);

if (!$resultado) {
    die("Não foi possível consultar os usuários.");
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Usuários - Ferrorama</title>

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
            max-width: 1100px;
            margin: auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
        }

        h1 {
            margin-bottom: 10px;
        }

        .topo {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .botao {
            display: inline-block;
            padding: 10px 15px;
            background: #111827;
            color: white;
            text-decoration: none;
            border-radius: 6px;
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

        .editar {
            color: #2563eb;
            text-decoration: none;
            margin-right: 10px;
        }

        .excluir {
            color: #dc2626;
            text-decoration: none;
        }

        .perfil-admin {
            font-weight: bold;
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

    <div class="topo">

        <div>

            <h1>Usuários</h1>

            <p>
                Gerenciamento de funcionários e administradores
            </p>

        </div>

        <a
            href="cadastrar.php"
            class="botao"
        >
            + Cadastrar usuário
        </a>

    </div>

    <table>

        <thead>

            <tr>

                <th>ID</th>

                <th>Nome</th>

                <th>E-mail</th>

                <th>Perfil</th>

                <th>Cadastro</th>

                <th>Ações</th>

            </tr>

        </thead>

        <tbody>

        <?php while ($usuario = mysqli_fetch_assoc($resultado)): ?>

            <tr>

                <td>
                    <?= htmlspecialchars($usuario["id"]) ?>
                </td>

                <td>
                    <?= htmlspecialchars($usuario["nome"]) ?>
                </td>

                <td>
                    <?= htmlspecialchars($usuario["email"]) ?>
                </td>

                <td>

                    <?php if ($usuario["perfil"] === "administrador"): ?>

                        <span class="perfil-admin">
                            Administrador
                        </span>

                    <?php else: ?>

                        Usuário

                    <?php endif; ?>

                </td>

                <td>

                    <?= htmlspecialchars(
                        date(
                            "d/m/Y H:i",
                            strtotime($usuario["criado_em"])
                        )
                    ) ?>

                </td>

                <td>

                    <a
                        href="editar.php?id=<?= $usuario["id"] ?>"
                        class="editar"
                    >
                        Editar
                    </a>

                    <a
                        href="excluir.php?id=<?= $usuario["id"] ?>"
                        class="excluir"
                        onclick="return confirm('Tem certeza que deseja excluir este usuário?');"
                    >
                        Excluir
                    </a>

                </td>

            </tr>

        <?php endwhile; ?>

        </tbody>

    </table>

    <a
        href="../sistema.php"
        class="voltar"
    >
        ← Voltar para o sistema
    </a>

</div>

</body>

</html>