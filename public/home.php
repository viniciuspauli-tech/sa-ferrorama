<?php

/*
 * Tela "Meu perfil" (equivalente ao home.php do gabarito).
 * Exige login, mostra e-mail e cargo do usuário logado,
 * exibe os botões de acordo com o perfil e permite sair.
 */

require_once "../infra/auth.php";
require_once "../infra/connect.php";

$id = (int) $_SESSION["usuario_id"];

$sql = "SELECT email, perfil FROM usuarios WHERE id = ?";

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

    
</head>

<body>

<div class="container">

    <h1>
        Bem-vindo, <?= htmlspecialchars($_SESSION["usuario_nome"]) ?>!
    </h1>

    <div class="botoes">

        <?php if ($eAdmin): ?>

            <a class="botao" href="usuarios/pagina_adm.php">Administração</a>

        <?php endif; ?>

        <a class="botao" href="sistema.php">Sistema</a>

    </div>

    <table>

        <thead>

            <tr>
                <th>E-mail</th>
                <th>Cargo</th>
            </tr>

        </thead>

        <tbody>

        <?php if ($usuario): ?>

            <tr>

                <td><?= htmlspecialchars($usuario["email"]) ?></td>

                <td>
                    <?= $usuario["perfil"] === "administrador"
                        ? "Administrador"
                        : "Usuário" ?>
                </td>

            </tr>

        <?php else: ?>

            <tr>
                <td colspan="2">Nenhum usuário encontrado</td>
            </tr>

        <?php endif; ?>

        </tbody>

    </table>

    <a href="logout.php" class="voltar">Sair</a>

</div>

</body>

</html>