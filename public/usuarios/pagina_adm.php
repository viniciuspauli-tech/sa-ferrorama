
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

    <style>
        :root {
            --navy: #0c192b;
            --navy-light: #17243e;
            --purple: #6554e8;
            --purple-dark: #5140d4;
            --purple-light: #eeecff;
            --background: #f2f4f9;
            --white: #ffffff;
            --text: #132238;
            --muted: #73819a;
            --border: #e0e6f0;
            --danger: #dc414b;
            --danger-light: #fff0f1;
            --success: #16805d;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            min-height: 100vh;
            font-family: "Roboto", sans-serif;
            color: var(--text);
            background: var(--background);
        }

        a {
            text-decoration: none;
        }

        .topbar {
            background: var(--navy);
            color: white;
            border-bottom: 1px solid rgba(255,255,255,.08);
        }

        .topbar-inner {
            max-width: 1280px;
            min-height: 76px;
            margin: auto;
            padding: 0 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            color: white;
        }

        .brand-icon {
            width: 42px;
            height: 42px;
            display: grid;
            place-items: center;
            border-radius: 13px;
            background: white;
            color: #3322c7;
            font-size: 23px;
        }

        .brand-name {
            font-size: 19px;
            font-weight: 800;
            letter-spacing: -.5px;
        }

        .brand-subtitle {
            margin-top: 3px;
            color: #b6c6e4;
            font-size: 11px;
        }

        .top-user {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #edf2ff;
            font-size: 13px;
        }

        .top-user-icon {
            width: 38px;
            height: 38px;
            display: grid;
            place-items: center;
            background: #1c2d45;
            border-radius: 50%;
            font-size: 17px;
        }

        .hero {
            padding: 40px 24px 43px;
            color: white;
            background:
                radial-gradient(
                    circle at 85% 20%,
                    rgba(101,84,232,.25),
                    transparent 38%
                ),
                linear-gradient(115deg, #0c192b, #17243e);
        }

        .hero-inner {
            max-width: 1100px;
            margin: auto;
        }

        .eyebrow {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
            color: #c5c0ff;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 1.4px;
            text-transform: uppercase;
        }

        .eyebrow::before {
            content: "";
            width: 20px;
            height: 2px;
            background: #9286ff;
        }

        .hero h1 {
            margin-bottom: 12px;
            font-size: clamp(27px, 4vw, 36px);
            letter-spacing: -1px;
        }

        .hero h1 span {
            color: #a69aff;
        }

        .hero p {
            max-width: 620px;
            color: #c7d2e7;
            font-size: 14px;
            line-height: 1.8;
        }

        .main {
            max-width: 1100px;
            margin: auto;
            padding: 32px 24px 55px;
        }

        .section-heading {
            margin-bottom: 22px;
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 15px;
        }

        .section-heading h2 {
            margin-bottom: 7px;
            font-size: 20px;
            letter-spacing: -.4px;
        }

        .section-heading p {
            color: var(--muted);
            font-size: 13px;
            line-height: 1.6;
        }

        .counter {
            padding: 9px 13px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #4b3ac8;
            background: var(--purple-light);
            border-radius: 9px;
            font-size: 12px;
            font-weight: 700;
        }

        .counter-icon {
            font-size: 16px;
        }

        .toolbar {
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }

        .toolbar-note {
            color: var(--muted);
            font-size: 12px;
        }

        .toolbar-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .button {
            min-height: 42px;
            padding: 0 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: 1px solid transparent;
            border-radius: 9px;
            font-family: inherit;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: background .2s, border-color .2s, transform .2s;
        }

        .button:hover {
            transform: translateY(-1px);
        }

        .button-primary {
            color: white;
            background: var(--purple);
            box-shadow: 0 5px 12px rgba(101,84,232,.15);
        }

        .button-primary:hover {
            color: white;
            background: var(--purple-dark);
        }

        .button-secondary {
            color: var(--text);
            background: white;
            border-color: var(--border);
        }

        .button-secondary:hover {
            color: var(--purple);
            border-color: #c9c2ff;
        }

        .table-card {
            overflow: hidden;
            background: white;
            border: 1px solid var(--border);
            border-radius: 15px;
            box-shadow: 0 8px 28px rgba(23,40,70,.045);
        }

        .table-header {
            padding: 20px 22px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            border-bottom: 1px solid var(--border);
        }

        .table-header h3 {
            font-size: 15px;
        }

        .table-header span {
            color: var(--muted);
            font-size: 11px;
        }

        .table-scroll {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        thead {
            background: #f8f9fc;
        }

        th {
            padding: 15px 22px;
            color: #687791;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .8px;
            text-transform: uppercase;
            white-space: nowrap;
        }

        td {
            padding: 17px 22px;
            border-top: 1px solid #edf0f5;
            font-size: 13px;
            vertical-align: middle;
        }

        tbody tr {
            transition: background .15s;
        }

        tbody tr:hover {
            background: #fafaff;
        }

        .user-cell {
            min-width: 190px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-avatar {
            flex: 0 0 38px;
            width: 38px;
            height: 38px;
            display: grid;
            place-items: center;
            color: #5140d4;
            background: var(--purple-light);
            border-radius: 12px;
            font-size: 14px;
            font-weight: 800;
        }

        .user-name {
            margin-bottom: 4px;
            font-size: 13px;
            font-weight: 700;
            overflow-wrap: anywhere;
        }

        .user-email {
            color: var(--muted);
            font-size: 11px;
            overflow-wrap: anywhere;
        }

        .email-cell {
            min-width: 180px;
            overflow-wrap: anywhere;
        }

        .perfil {
            padding: 7px 10px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            white-space: nowrap;
        }

        .perfil-admin {
            color: #5140c7;
            background: #eeecff;
        }

        .perfil-usuario {
            color: #52647e;
            background: #edf1f7;
        }

        .perfil-dot {
            width: 6px;
            height: 6px;
            display: inline-block;
            border-radius: 50%;
            background: currentColor;
        }

        .acoes {
            min-width: 150px;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
        }

        .action-link,
        .action-button {
            min-height: 34px;
            padding: 0 11px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid transparent;
            border-radius: 7px;
            font-family: inherit;
            font-size: 11px;
            font-weight: 700;
            cursor: pointer;
            transition: .2s;
        }

        .action-edit {
            color: #4e40c9;
            background: #f0eeff;
        }

        .action-edit:hover {
            color: white;
            background: var(--purple);
        }

        .action-delete {
            color: var(--danger);
            background: var(--danger-light);
        }

        .action-delete:hover {
            color: white;
            background: var(--danger);
        }

        .form-excluir {
            display: inline-flex;
            flex-direction: column;
            align-items: flex-start;
        }

        .mensagem-erro {
            max-width: 200px;
            margin-top: 5px;
            color: var(--danger);
            font-size: 11px;
            line-height: 1.5;
        }

        .empty-state {
            padding: 50px 20px;
            text-align: center;
        }

        .empty-icon {
            width: 58px;
            height: 58px;
            margin: 0 auto 15px;
            display: grid;
            place-items: center;
            color: var(--purple);
            background: var(--purple-light);
            border-radius: 17px;
            font-size: 25px;
        }

        .empty-state h3 {
            margin-bottom: 8px;
            font-size: 16px;
        }

        .empty-state p {
            margin-bottom: 19px;
            color: var(--muted);
            font-size: 12px;
            line-height: 1.7;
        }

        .footer {
            margin-top: 25px;
            color: #8a96aa;
            text-align: center;
            font-size: 11px;
        }

        @media (max-width: 650px) {
            .topbar-inner {
                padding: 0 16px;
            }

            .top-user {
                font-size: 0;
            }

            .top-user-icon {
                font-size: 17px;
            }

            .hero {
                padding: 32px 18px;
            }

            .main {
                padding: 25px 14px 40px;
            }

            .toolbar {
                align-items: stretch;
                flex-direction: column;
            }

            .toolbar-actions {
                display: grid;
                grid-template-columns: 1fr 1fr;
            }

            .toolbar-actions .button:first-child {
                grid-column: 1 / -1;
            }

            .table-header {
                padding: 17px;
            }

            th,
            td {
                padding: 14px 15px;
            }

            .section-heading {
                align-items: flex-start;
                flex-direction: column;
            }
        }
    </style>
</head>

<body>

<header class="topbar">
    <div class="topbar-inner">

        <a href="../sistema.php" class="brand">
            <div class="brand-icon">🚆</div>

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
