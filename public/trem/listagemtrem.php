
<?php

require_once __DIR__ . "/../../infra/auth.php";
require_once __DIR__ . "/../../infra/connect.php";

$ehAdministrador =
    ($_SESSION["usuario_perfil"] ?? "") === "administrador";

$busca = trim($_GET["busca"] ?? "");
$statusFiltro = $_GET["status"] ?? "";

$statusValidos = ["ativo", "inativo", "manutencao", "falha"];

if ($statusFiltro !== "" && !in_array($statusFiltro, $statusValidos, true)) {
    $statusFiltro = "";
}

$sql = "
    SELECT
        id,
        identificador,
        modelo,
        status,
        velocidade_atual,
        localizacao_atual,
        consumo_energia,
        atualizado_em
    FROM trens
    WHERE 1=1
";

$tipos = "";
$params = [];

if ($busca !== "") {
    $sql .= "
        AND (
            identificador LIKE ?
            OR modelo LIKE ?
            OR localizacao_atual LIKE ?
        )
    ";

    $like = "%{$busca}%";
    $tipos .= "sss";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($statusFiltro !== "") {
    $sql .= " AND status = ?";
    $tipos .= "s";
    $params[] = $statusFiltro;
}

$sql .= " ORDER BY identificador ASC";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    error_log($conn->error);
    http_response_code(500);
    exit("Não foi possível consultar os trens.");
}

if (!empty($params)) {
    $stmt->bind_param($tipos, ...$params);
}

if (!$stmt->execute()) {
    error_log($stmt->error);
    http_response_code(500);
    exit("Não foi possível carregar os trens.");
}

$resultado = $stmt->get_result();
$trens = $resultado->fetch_all(MYSQLI_ASSOC);
$stmt->close();

function escapar($valor): string
{
    return htmlspecialchars(
        (string) $valor,
        ENT_QUOTES,
        "UTF-8"
    );
}

function corStatusTrem($status): string
{
    switch ($status) {
        case "ativo":
            return "success";
        case "inativo":
            return "secondary";
        case "manutencao":
            return "warning";
        case "falha":
            return "danger";
        default:
            return "secondary";
    }
}

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Listagem de Trens - Ferrorama</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
</head>

<body class="bg-light">

<div class="container py-4">

    <!-- CABEÇALHO -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <h1>Trens</h1>

        <div class="d-flex gap-2 flex-wrap">

            <a href="../sistema.php" class="btn btn-primary">
                Voltar ao Sistema
            </a>

            <a href="../rotas/rotas.php" class="btn btn-secondary">
                Rotas
            </a>

            <?php if ($ehAdministrador): ?>
                <a href="cadastrotrem.php" class="btn btn-primary">
                    Cadastrar trem
                </a>
            <?php endif; ?>

        </div>
    </div>

    <!-- FILTROS -->
    <form method="GET" class="row g-2 mb-4">

        <div class="col-md-5">
            <input
                type="text"
                name="busca"
                class="form-control"
                placeholder="Buscar trem..."
                maxlength="100"
                value="<?= escapar($busca) ?>"
            >
        </div>

        <div class="col-md-3">
            <select name="status" class="form-select">
                <option value="">Todos os status</option>

                <option value="ativo" <?= $statusFiltro === "ativo" ? "selected" : "" ?>>
                    Ativo
                </option>

                <option value="inativo" <?= $statusFiltro === "inativo" ? "selected" : "" ?>>
                    Inativo
                </option>

                <option value="manutencao" <?= $statusFiltro === "manutencao" ? "selected" : "" ?>>
                    Manutenção
                </option>

                <option value="falha" <?= $statusFiltro === "falha" ? "selected" : "" ?>>
                    Falha
                </option>
            </select>
        </div>

        <div class="col-md-2">
            <button type="submit" class="btn btn-dark w-100">
                Buscar
            </button>
        </div>

        <div class="col-md-2">
            <a href="listagemtrem.php" class="btn btn-outline-secondary w-100">
                Limpar
            </a>
        </div>

    </form>

    <!-- TABELA -->
    <div class="card shadow-sm">
        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-hover align-middle">

                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Identificador</th>
                            <th>Modelo</th>
                            <th>Status</th>
                            <th>Velocidade</th>
                            <th>Localização</th>
                            <th>Consumo</th>
                            <th>Atualizado</th>

                            <?php if ($ehAdministrador): ?>
                                <th>Ações</th>
                            <?php endif; ?>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if (empty($trens)): ?>

                        <tr>
                            <td
                                colspan="<?= $ehAdministrador ? 9 : 8 ?>"
                                class="text-center"
                            >
                                Nenhum trem encontrado.
                            </td>
                        </tr>

                    <?php else: ?>

                        <?php foreach ($trens as $trem): ?>

                            <tr>
                                <td><?= (int) $trem["id"] ?></td>

                                <td>
                                    <strong>
                                        <?= escapar($trem["identificador"]) ?>
                                    </strong>
                                </td>

                                <td><?= escapar($trem["modelo"]) ?></td>

                                <td>
                                    <span class="badge bg-<?= escapar(corStatusTrem($trem["status"])) ?>">
                                        <?= escapar(ucfirst($trem["status"])) ?>
                                    </span>
                                </td>

                                <td>
                                    <?= number_format(
                                        (float) $trem["velocidade_atual"],
                                        2,
                                        ",",
                                        "."
                                    ) ?> km/h
                                </td>

                                <td>
                                    <?= escapar(
                                        $trem["localizacao_atual"] ?? "Não informado"
                                    ) ?>
                                </td>

                                <td>
                                    <?= number_format(
                                        (float) $trem["consumo_energia"],
                                        2,
                                        ",",
                                        "."
                                    ) ?> kWh
                                </td>

                                <td>
                                    <?php if (!empty($trem["atualizado_em"])): ?>
                                        <?= escapar(
                                            date(
                                                "d/m/Y H:i",
                                                strtotime($trem["atualizado_em"])
                                            )
                                        ) ?>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>

                                <?php if ($ehAdministrador): ?>
                                    <td>
                                        <a
                                            href="editartrem.php?id=<?= (int) $trem["id"] ?>"
                                            class="btn btn-sm btn-outline-primary"
                                        >
                                            Editar
                                        </a>
                                    </td>
                                <?php endif; ?>

                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>
    </div>

</div>

</body>
</html>