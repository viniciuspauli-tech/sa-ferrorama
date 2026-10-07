<?php

/*
 * Tela de administração (equivalente ao adm.php do gabarito).
 * Somente administradores: lista os usuários cadastrados.
 */

require_once "../../infra/adm.php";
require_once "../../infra/connect.php";

$sql = "SELECT id, email, perfil
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

    <title>Tela de ADM - Ferrorama</title>

    <link rel="stylesheet" href="../../style/style.css">

</head>

<body>

<div class="container">

    <div class="topo">

        <div>

            <h1>
                Bem-vindo, <?= htmlspecialchars($_SESSION["usuario_nome"]) ?>!
            </h1>

            <p>
                Gerenciamento de funcionários e administradores
            </p>

        </div>

        <div class="botoes">

            <a
                href="cadastro_usuario.php"
                class="botao"
            >
                Cadastrar usuário
            </a>

            <a
                href="home.php"
                class="botao"
            >
                Meu perfil
            </a>

            <a
                href="sistema.php"
                class="botao"
            >
                Sistema
            </a>

        </div>

    </div>

    <table>

        <thead>

            <tr>

                <th>E-mail</th>

                <th>Cargo</th>

                <th>Ações</th>

            </tr>

        </thead>

        <tbody>

        <?php if (mysqli_num_rows($resultado) > 0): ?>

            <?php while ($usuario = mysqli_fetch_assoc($resultado)): ?>

                <tr>

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

                        <a
                            href="editar.php?id=<?= (int) $usuario["id"] ?>"
                            class="editar"
                        >
                            Editar
                        </a>

                        <form
                            method="POST"
                            action="excluir_usuario.php"
                            class="form-excluir"
                            onsubmit="return confirm('Tem certeza que deseja excluir este usuário?');"
                        >
                            <input
                                type="hidden"
                                name="id"
                                value="<?= (int) $usuario["id"] ?>"
                            >
                            <button
                                type="submit"
                                class="excluir"
                            >
                                Excluir
                            </button>
                        </form>

                    </td>

                </tr>

            <?php endwhile; ?>

        <?php else: ?>

            <tr>
                <td colspan="3">Nenhum usuário encontrado</td>
            </tr>

        <?php endif; ?>

        </tbody>

    </table>

    <a
        href="../sistema.php"
        class="voltar"
    >
        ← Voltar para o sistema
    </a>

    <a
        href="../logout.php"
        class="voltar"
    >
        Sair
    </a>

</div>

</body>

</html>