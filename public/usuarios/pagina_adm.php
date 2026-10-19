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

    <style>
        .mensagem-erro {
            color: #c0392b;
            font-size: 0.85rem;
            margin: 6px 0 0;
        }
    </style>

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

            <a href="cadastro_usuario.php" class="botao">Cadastrar usuário</a>
            <a href="../home.php" class="botao">Meu perfil</a>
            <a href="../sistema.php" class="botao">Sistema</a>

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
                            <p class="mensagem-erro"></p>
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

</div>

<script>
    document.querySelectorAll(".form-excluir").forEach(form => {
        form.addEventListener("submit", async (e) => {
            e.preventDefault();

            const mensagem = form.querySelector(".mensagem-erro");
            mensagem.textContent = "";

            if (!confirm("Tem certeza que deseja excluir este usuário?")) {
                return;
            }

            try {
                const resposta = await fetch(form.action, {
                    method: "POST",
                    body: new FormData(form)
                });

                const dados = await resposta.json();

                if (dados.sucesso) {
                    form.closest("tr").remove();
                } else {
                    mensagem.textContent = dados.mensagem;
                }
            } catch (erro) {
                mensagem.textContent = "Erro de comunicação com o servidor.";
            }
        });
    });
</script>

</body>

</html>