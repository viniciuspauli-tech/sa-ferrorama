<?php

require_once "../infra/connect.php";


$busca = trim($_GET["busca"] ?? "");
$statusFiltro = $_GET["status"] ?? "";


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


$sql .= " ORDER BY identificador ASC";


$stmt = $conn->prepare($sql);


if (!$stmt) {

    die(
        "Erro ao preparar consulta: "
        . $conn->error
    );

}


if (!empty($params)) {

    $stmt->bind_param(
        $tipos,
        ...$params
    );

}


$stmt->execute();


$resultado = $stmt->get_result();

$trens = $resultado->fetch_all(
    MYSQLI_ASSOC
);


$stmt->close();


function corStatusTrem($status)
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

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Listagem de Trens - Ferrorama</title>


    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

</head>


<body class="bg-light">


<div class="container py-4">


    <!-- CABEÇALHO -->

    <div class="d-flex justify-content-between align-items-center mb-4">


        <h1>
            Trens
        </h1>


        <div>

            <a
                href="rotas.php"
                class="btn btn-secondary">

                Rotas

            </a>

        </div>


    </div>


    <!-- FILTRO -->

    <form
        method="GET"
        class="row g-2 mb-4"
    >


        <div class="col-md-5">

            <input
                type="text"
                name="busca"
                class="form-control"
                placeholder="Buscar trem..."
                value="<?= htmlspecialchars($busca) ?>"
            >

        </div>


        <div class="col-md-3">

            <select
                name="status"
                class="form-select"
            >

                <option value="">
                    Todos os status
                </option>


                <option
                    value="ativo"
                    <?= $statusFiltro == "ativo"
                        ? "selected"
                        : "" ?>
                >
                    Ativo
                </option>


                <option
                    value="inativo"
                    <?= $statusFiltro == "inativo"
                        ? "selected"
                        : "" ?>
                >
                    Inativo
                </option>


                <option
                    value="manutencao"
                    <?= $statusFiltro == "manutencao"
                        ? "selected"
                        : "" ?>
                >
                    Manutenção
                </option>


                <option
                    value="falha"
                    <?= $statusFiltro == "falha"
                        ? "selected"
                        : "" ?>
                >
                    Falha
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
                href="listagemtrem.php"
                class="btn btn-outline-secondary w-100">

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

                        </tr>

                    </thead>


                    <tbody>


                    <?php if (empty($trens)): ?>


                        <tr>

                            <td
                                colspan="8"
                                class="text-center"
                            >

                                Nenhum trem encontrado.

                            </td>

                        </tr>


                    <?php else: ?>


                        <?php foreach ($trens as $trem): ?>


                            <tr>


                                <td>

                                    <?= $trem["id"] ?>

                                </td>


                                <td>

                                    <strong>

                                        <?= htmlspecialchars(
                                            $trem["identificador"]
                                        ) ?>

                                    </strong>

                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        $trem["modelo"]
                                    ) ?>

                                </td>


                                <td>

                                    <span
                                        class="badge bg-<?= corStatusTrem(
                                            $trem["status"]
                                        ) ?>"
                                    >

                                        <?= ucfirst(
                                            htmlspecialchars(
                                                $trem["status"]
                                            )
                                        ) ?>

                                    </span>

                                </td>


                                <td>

                                    <?= number_format(
                                        (float)$trem["velocidade_atual"],
                                        2,
                                        ",",
                                        "."
                                    ) ?>

                                    km/h

                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        $trem["localizacao_atual"]
                                        ?? "Não informado"
                                    ) ?>

                                </td>


                                <td>

                                    <?= number_format(
                                        (float)$trem["consumo_energia"],
                                        2,
                                        ",",
                                        "."
                                    ) ?>

                                    kWh

                                </td>


                                <td>

                                    <?php

                                    if (!empty($trem["atualizado_em"])) {

                                        echo date(
                                            "d/m/Y H:i",
                                            strtotime(
                                                $trem["atualizado_em"]
                                            )
                                        );

                                    } else {

                                        echo "—";

                                    }

                                    ?>

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