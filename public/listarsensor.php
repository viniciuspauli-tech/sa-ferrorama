
<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . "/../infra/auth.php";
require_once __DIR__ . "/../infra/connect.php";

$ehAdministrador =
    ($_SESSION["usuario_perfil"] ?? "") === "administrador";

if (!$ehAdministrador) {
    http_response_code(403);
    exit("Acesso permitido apenas para administradores.");
}

if (empty($_SESSION["csrf"])) {
    $_SESSION["csrf"] = bin2hex(random_bytes(32));
}

function escapar($valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, "UTF-8");
}

$busca = trim($_GET["busca"] ?? "");

$sql = "
    SELECT id, nome, localizacao, tipo_dado
    FROM sensores
    WHERE 1 = 1
";

$params = [];
$tipos = "";

if ($busca !== "") {
    $sql .= "
        AND (
            nome LIKE ?
            OR localizacao LIKE ?
            OR tipo_dado LIKE ?
        )
    ";

    $like = "%" . $busca . "%";

    $tipos = "sss";
    $params = [$like, $like, $like];
}

$sql .= " ORDER BY localizacao ASC, nome ASC";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    error_log($conn->error);
    http_response_code(500);
    exit("Não foi possível consultar os sensores.");
}

if (!empty($params)) {
    $stmt->bind_param($tipos, ...$params);
}

if (!$stmt->execute()) {
    error_log($stmt->error);
    http_response_code(500);
    exit("Não foi possível carregar os sensores.");
}

$resultado = $stmt->get_result();
$sensores = $resultado->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$totalSensores = count($sensores);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sensores - Ferrorama</title>

    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f1f3f8;
            color: #111c30;
        }

        a { text-decoration: none; }

        .navbar {
            background: #0d1b2a;
            color: white;
            padding: 22px 5%;
        }

        .navbar a { color: white; font-weight: bold; }

        .hero {
            background: linear-gradient(115deg, #0d1b2a, #172443);
            color: white;
            padding: 35px 5%;
        }

        .hero h1 { margin: 0 0 10px; }
        .hero p { color: #c5d0e2; line-height: 1.6; }

        main {
            width: 90%;
            max-width: 1400px;
            margin: 30px auto;
        }

        .topo, .acoes, .filtros {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
        }

        .card {
            background: white;
            border: 1px solid #e0e6f0;
            border-radius: 12px;
            padding: 22px;
            margin-bottom: 22px;
            box-shadow: 0 4px 16px rgba(16,32,58,.04);
        }

        .botao {
            display: inline-block;
            padding: 10px 14px;
            border: 0;
            border-radius: 7px;
            cursor: pointer;
            font-weight: bold;
            font-size: 13px;
        }

        .primario { background: #3324d9; color: white; }
        .editar { background: #eeedff; color: #3324d9; }
        .excluir { background: #ffe8eb; color: #c6283d; }
        .secundario { background: #e8edf5; color: #172a42; }

        input {
            padding: 11px;
            border: 1px solid #d5dce8;
            border-radius: 7px;
            min-width: 240px;
        }

        .tabela-wrap { overflow-x: auto; }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            min-width: 650px;
        }

        th, td {
            padding: 14px;
            border-bottom: 1px solid #e5e9f0;
            text-align: left;
        }

        th {
            background: #f7f8fc;
            color: #596780;
            font-size: 12px;
            text-transform: uppercase;
        }

        .id { color: #728098; }
        .mensagem {
            background: #e1f7eb;
            color: #176b45;
            padding: 14px;
            border-radius: 8px;
            margin-bottom: 18px;
        }

        .vazio {
            padding: 30px;
            text-align: center;
            color: #728098;
        }

        footer {
            text-align: center;
            padding: 25px;
            color: #728098;
        }

        @media (max-width: 600px) {
            main { width: 94%; }
            .filtros { align-items: stretch; }
            .filtros input { width: 100%; min-width: 0; }
        }
    </style>
</head>

<body>
<header class="navbar">
    <a href="../sistema.php">🚆 Ferrorama</a>
</header>

<section class="hero">
    <h1>Gerenciamento de sensores</h1>
    <p>Consulte, pesquise, edite e exclua os sensores instalados nos trilhos.</p>
    <a class="botao secundario" href="sensor.php">Voltar ao monitoramento</a>
</section>

<main>
    <?php if (($_GET["sucesso"] ?? "") === "editado"): ?>
        <div class="mensagem">Sensor atualizado com sucesso!</div>
    <?php elseif (($_GET["sucesso"] ?? "") === "excluido"): ?>
        <div class="mensagem">Sensor excluído com sucesso!</div>
    <?php elseif (($_GET["erro"] ?? "") === "dependencias"): ?>
        <div class="mensagem">
            Não foi possível excluir este sensor porque existem leituras
            vinculadas a ele. Preserve o histórico de dados.
        </div>
    <?php elseif (($_GET["erro"] ?? "") === "nao_encontrado"): ?>
        <div class="mensagem">Sensor não encontrado ou já excluído.</div>
    <?php endif; ?>

    <section class="card">
        <div class="topo">
            <div>
                <h2>Sensores cadastrados</h2>
                <p><?= $totalSensores ?> registro(s) encontrado(s)</p>
            </div>
        </div>

        <form method="GET" action="listar_sensores.php" class="filtros">
            <input
                type="text"
                name="busca"
                maxlength="100"
                placeholder="Nome, localização ou tipo..."
                value="<?= escapar($busca) ?>"
            >

            <button type="submit" class="botao primario">Pesquisar</button>
            <a href="listar_sensores.php" class="botao secundario">Limpar</a>
        </form>

        <div class="tabela-wrap">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Localização</th>
                        <th>Tipo de dado</th>
                        <th>Ações</th>
                    </tr>
                </thead>

                <tbody>
                <?php if (empty($sensores)): ?>
                    <tr>
                        <td colspan="5" class="vazio">
                            Nenhum sensor encontrado.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($sensores as $sensor): ?>
                        <tr>
                            <td class="id">#<?= (int) $sensor["id"] ?></td>
                            <td><?= escapar($sensor["nome"]) ?></td>
                            <td><?= escapar($sensor["localizacao"]) ?></td>
                            <td><?= escapar($sensor["tipo_dado"]) ?></td>
                            <td>
                                <div class="acoes">
                                    <a
                                        class="botao editar"
                                        href="editSensor.php?id=<?= (int) $sensor["id"] ?>"
                                    >Editar</a>

                                    <form
                                        method="POST"
                                        action="excluirsensor.php"
                                        class="form-excluir"
                                    >
                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?= (int) $sensor["id"] ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="csrf"
                                            value="<?= escapar($_SESSION["csrf"]) ?>"
                                        >

                                        <button type="submit" class="botao excluir">
                                            Excluir
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <footer>Ferrorama · Sistema de gerenciamento ferroviário</footer>
</main>

<script>
document.querySelectorAll(".form-excluir").forEach(form => {
    form.addEventListener("submit", event => {
        if (!confirm("Tem certeza que deseja excluir este sensor?")) {
            event.preventDefault();
        }
    });
});
</script>
</body>
</html>
