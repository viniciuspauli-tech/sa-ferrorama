
<?php

require_once "../../infra/adm.php";
require_once "../../infra/connect.php";

$sql = "SELECT id, nome, email, perfil
        FROM usuarios
        ORDER BY id DESC";

$resultado = mysqli_query($conn, $sql);

if (!$resultado) {
    error_log(mysqli_error($conn));
    die("Não foi possível consultar os usuários.");
}

function escapar($valor): string
{
    return htmlspecialchars(
        (string) $valor,
        ENT_QUOTES,
        "UTF-8"
    );
}

$totalUsuarios = mysqli_num_rows($resultado);

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Gerenciar usuários - Ferrorama</title>

    <link
        href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

    
<link rel="stylesheet" href="../../style/style.css">
</head>

<body class="page-public_usuarios_pagina_adm_php">

<header class="topbar">
    <div class="topbar-inner">

        <a href="../sistema.php" class="brand">
            <span class="brand-icon">
                <svg viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="1.8"
                     stroke-linecap="round" stroke-linejoin="round"
                     aria-hidden="true">
                    <rect x="5" y="3" width="14" height="15" rx="4"></rect>
                    <path d="M8 7h8M8 12h8M8 18l-2 3M16 18l2 3M5 15h14"></path>
                    <circle cx="9" cy="15" r="1"></circle>
                    <circle cx="15" cy="15" r="1"></circle>
                </svg>
            </span>

            <div>
                <div class="brand-name">Ferrorama</div>
                <div class="brand-subtitle">Sistema ferroviário</div>
            </div>
        </a>

        <div class="top-user">
            <div class="top-user-icon">♙</div>
            Área administrativa
        </div>

    </div>
</header>

<section class="hero">
    <div class="hero-inner">

        <div class="eyebrow">Administração do sistema</div>

        <h1>Gerenciamento de <span>usuários</span></h1>

        <p>
            Gerencie funcionários e administradores, consulte os níveis
            de acesso e mantenha as contas do Ferrorama organizadas.
        </p>

    </div>
</section>

<main class="main">

    <div class="section-heading">
        <div>
            <h2>Usuários cadastrados</h2>
            <p>Consulte as contas e gerencie as permissões de acesso.</p>
        </div>

        <div class="counter">
            <span class="counter-icon">♙</span>
            <?= $totalUsuarios ?>
            <?= $totalUsuarios === 1 ? "usuário" : "usuários" ?>
        </div>
    </div>

    <div class="toolbar">

        <div class="toolbar-note">
            Selecione uma ação para continuar.
        </div>

        <div class="toolbar-actions">

            <a href="cadastro_usuario.php" class="button button-primary">
                <span>+</span> Cadastrar usuário
            </a>

            <a href="../home.php" class="button button-secondary">
                ♙ Meu perfil
            </a>

            <a href="../sistema.php" class="button button-secondary">
                ← Voltar
            </a>

        </div>

    </div>

    <section class="table-card">

        <div class="table-header">
            <h3>Lista de usuários</h3>
            <span>Contas registradas no sistema</span>
        </div>

        <div class="table-scroll">

            <table>

                <thead>
                    <tr>
                        <th>Usuário</th>
                        <th>E-mail</th>
                        <th>Cargo</th>
                        <th>Ações</th>
                    </tr>
                </thead>

                <tbody id="lista-usuarios">

                <?php if ($totalUsuarios > 0): ?>

                    <?php while ($usuario = mysqli_fetch_assoc($resultado)): ?>

                        <?php
                            $nome = trim((string) ($usuario["nome"] ?? ""));
                            $email = (string) ($usuario["email"] ?? "");
                            $perfil = (string) ($usuario["perfil"] ?? "usuario");

                            $nomeExibicao = $nome !== "" ? $nome : "Usuário";
                            $iniciais = strtoupper(
                                function_exists("mb_substr")
                                    ? mb_substr($nomeExibicao, 0, 1, "UTF-8")
                                    : substr($nomeExibicao, 0, 1)
                            );

                            $ehAdmin = $perfil === "administrador";
                        ?>

                        <tr data-usuario-id="<?= (int) $usuario["id"] ?>">

                            <td>
                                <div class="user-cell">

                                    <div class="user-avatar">
                                        <?= escapar($iniciais) ?>
                                    </div>

                                    <div>
                                        <div class="user-name">
                                            <?= escapar($nomeExibicao) ?>
                                        </div>

                                        <div class="user-email">
                                            ID #<?= (int) $usuario["id"] ?>
                                        </div>
                                    </div>

                                </div>
                            </td>

                            <td class="email-cell">
                                <?= escapar($email) ?>
                            </td>

                            <td>
                                <span class="perfil <?= $ehAdmin ? "perfil-admin" : "perfil-usuario" ?>">
                                    <span class="perfil-dot"></span>
                                    <?= $ehAdmin ? "Administrador" : "Usuário comum" ?>
                                </span>
                            </td>

                            <td>
                                <div class="acoes">

                                    <a
                                        href="editar.php?id=<?= (int) $usuario["id"] ?>"
                                        class="action-link action-edit"
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
                                            class="action-button action-delete"
                                        >
                                            Excluir
                                        </button>

                                        <p class="mensagem-erro" role="alert"></p>
                                    </form>

                                </div>
                            </td>

                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr id="linha-vazia">
                        <td colspan="4">
                            <div class="empty-state">

                                <div class="empty-icon">♙</div>

                                <h3>Nenhum usuário cadastrado</h3>

                                <p>
                                    Cadastre o primeiro usuário para começar
                                    a gerenciar as contas do sistema.
                                </p>

                                <a
                                    href="cadastro_usuario.php"
                                    class="button button-primary"
                                >
                                    + Cadastrar usuário
                                </a>

                            </div>
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>

    <footer class="footer">
        Ferrorama · Sistema de gerenciamento ferroviário
    </footer>

</main>

<script>
    document.querySelectorAll(".form-excluir").forEach(form => {

        form.addEventListener("submit", async (e) => {
            e.preventDefault();

            const mensagem = form.querySelector(".mensagem-erro");
            const botao = form.querySelector("button");
            const linha = form.closest("tr");

            mensagem.textContent = "";

            if (!confirm("Tem certeza que deseja excluir este usuário?")) {
                return;
            }

            botao.disabled = true;
            botao.textContent = "Excluindo...";

            try {
                const resposta = await fetch(form.action, {
                    method: "POST",
                    body: new FormData(form)
                });

                const dados = await resposta.json();

                if (!resposta.ok || !dados.sucesso) {
                    mensagem.textContent =
                        dados.mensagem || "Não foi possível excluir o usuário.";

                    botao.disabled = false;
                    botao.textContent = "Excluir";
                    return;
                }

                linha.remove();

                const contador = document.querySelector(".counter");
                const linhasRestantes = document.querySelectorAll(
                    "#lista-usuarios tr[data-usuario-id]"
                ).length;

                contador.innerHTML =
                    '<span class="counter-icon">♙</span> ' +
                    linhasRestantes + " " +
                    (linhasRestantes === 1 ? "usuário" : "usuários");

                if (linhasRestantes === 0) {
                    const tbody = document.getElementById("lista-usuarios");

                    tbody.innerHTML = `
                        <tr id="linha-vazia">
                            <td colspan="4">
                                <div class="empty-state">
                                    <div class="empty-icon">♙</div>
                                    <h3>Nenhum usuário cadastrado</h3>
                                    <p>
                                        Cadastre um usuário para começar
                                        a gerenciar as contas do sistema.
                                    </p>
                                    <a
                                        href="cadastro_usuario.php"
                                        class="button button-primary"
                                    >
                                        + Cadastrar usuário
                                    </a>
                                </div>
                            </td>
                        </tr>
                    `;
                }

            } catch (erro) {
                mensagem.textContent =
                    "Erro de comunicação com o servidor. Tente novamente.";

                botao.disabled = false;
                botao.textContent = "Excluir";
            }
        });

    });
</script>

</body>
</html>
