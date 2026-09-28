<?php
require_once "../infra/connect.php";

$busca = trim($_GET["busca"] ?? "");
$statusFiltro = $_GET["status"] ?? "";

$sql = "SELECT id, nome, origem, destino, distancia_km, tempo_estimado, status
        FROM rotas
        WHERE 1=1";

$tipos = "";
$params = [];

if ($busca !== "") {
    $sql .= " AND (nome LIKE ? OR origem LIKE ? OR destino LIKE ?)";

    $like = "%$busca%";

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

$sql .= " ORDER BY nome ASC";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Erro ao preparar consulta: " . $conn->error);
}

if (!empty($params)) {
    $stmt->bind_param($tipos, ...$params);
}

$stmt->execute();

$resultado = $stmt->get_result();
$rotas = $resultado->fetch_all(MYSQLI_ASSOC);

$stmt->close();

function corStatus($status)
{
    switch ($status) {
        case "ativa":
            return "success";

        case "inativa":
            return "secondary";

        case "manutencao":
            return "warning";

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

    <title>Rotas - Ferrorama</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

</head>

<body class="bg-light">

<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <h1>Rotas</h1>

        <div>

            <a href="listagemtrem.php"
               class="btn btn-secondary">
                Trens
            </a>

            <a href="cadastrorota.php"
               class="btn btn-primary">
                Cadastrar rota
            </a>

        </div>

    </div>


    <?php if (isset($_GET["sucesso"])): ?>

        <div class="alert alert-success">

            <?= htmlspecialchars($_GET["sucesso"]) ?>

        </div>

    <?php endif; ?>


    <?php if (isset($_GET["erro"])): ?>

        <div class="alert alert-danger">

            <?= htmlspecialchars($_GET["erro"]) ?>

        </div>

    <?php endif; ?>


    <!-- FILTRO -->

    <form method="GET"
          class="row g-2 mb-4">

        <div class="col-md-5">

            <input
                type="text"
                name="busca"
                class="form-control"
                placeholder="Buscar rota..."
                value="<?= htmlspecialchars($busca) ?>"
            >

        </div>


        <div class="col-md-3">

            <select name="status"
                    class="form-select">

                <option value="">
                    Todos os status
                </option>

                <option value="ativa"
                    <?= $statusFiltro == "ativa" ? "selected" : "" ?>>
                    Ativa
                </option>

                <option value="inativa"
                    <?= $statusFiltro == "inativa" ? "selected" : "" ?>>
                    Inativa
                </option>

                <option value="manutencao"
                    <?= $statusFiltro == "manutencao" ? "selected" : "" ?>>
                    Manutenção
                </option>

            </select>

        </div>


        <div class="col-md-2">

            <button
                type="submit"
                class="btn btn-dark w-100">

                Buscar

            </button>

        </div>


        <div class="col-md-2">

            <a
                href="rotas.php"
                class="btn btn-outline-secondary w-100">

                Limpar

            </a>

        </div>

    </form>


    <!-- TABELA -->

    <div class="card">

        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-hover align-middle">

                    <thead class="table-dark">

                        <tr>

                            <th>ID</th>

                            <th>Nome</th>

                            <th>Origem</th>

                            <th>Destino</th>

                            <th>Distância</th>

                            <th>Tempo</th>

                            <th>Status</th>

                            <th>Ações</th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php if (empty($rotas)): ?>

                        <tr>

                            <td colspan="8"
                                class="text-center">

                                Nenhuma rota encontrada.

                            </td>

                        </tr>

                    <?php else: ?>


                        <?php foreach ($rotas as $rota): ?>

                            <tr>

                                <td>
                                    <?= $rota["id"] ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($rota["nome"]) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($rota["origem"]) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($rota["destino"]) ?>
                                </td>

                                <td>
                                    <?= number_format(
                                        $rota["distancia_km"],
                                        2,
                                        ",",
                                        "."
                                    ) ?>
                                    km
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $rota["tempo_estimado"]
                                    ) ?>
                                </td>

                                <td>

                                    <span
                                        class="badge bg-<?= corStatus($rota["status"]) ?>">

                                        <?= ucfirst(
                                            htmlspecialchars(
                                                $rota["status"]
                                            )
                                        ) ?>

                                    </span>

                                </td>

                                <td>

                                    <a
                                        href="editrotas.php?id=<?= $rota["id"] ?>"
                                        class="btn btn-sm btn-primary">

                                        Editar

                                    </a>

                                    <form
                                        method="POST"
                                        action="editrotas.php"
                                        class="d-inline"
                                        onsubmit="return confirm('Deseja realmente excluir esta rota?');"
                                    >

                                        <input
                                            type="hidden"
                                            name="acao"
                                            value="excluir"
                                        >

                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?= $rota["id"] ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="btn btn-sm btn-danger">

                                            Excluir

                                        </button>

                                    </form>

                                </td>

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