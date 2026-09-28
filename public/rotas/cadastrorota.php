<?php

require_once "../infra/connect.php";

$erros = [];

$nome = trim($_POST["nome"] ?? "");
$origem = trim($_POST["origem"] ?? "");
$destino = trim($_POST["destino"] ?? "");
$distancia = trim($_POST["distancia_km"] ?? "");
$tempo = trim($_POST["tempo_estimado"] ?? "");
$status = $_POST["status"] ?? "ativa";


if ($_SERVER["REQUEST_METHOD"] === "POST") {


    // VALIDAÇÕES

    if ($nome === "") {
        $erros[] = "Informe o nome da rota.";
    }


    if ($origem === "") {
        $erros[] = "Informe a origem.";
    }


    if ($destino === "") {
        $erros[] = "Informe o destino.";
    }


    if (
        $distancia === "" ||
        !is_numeric($distancia) ||
        $distancia <= 0
    ) {

        $erros[] = "Informe uma distância válida.";

    }


    if ($tempo === "") {
        $erros[] = "Informe o tempo estimado.";
    }


    $statusValidos = [
        "ativa",
        "inativa",
        "manutencao"
    ];


    if (!in_array($status, $statusValidos)) {

        $erros[] = "Status inválido.";

    }


    // CADASTRO

    if (empty($erros)) {

        $sql = "
            INSERT INTO rotas
            (
                nome,
                origem,
                destino,
                distancia_km,
                tempo_estimado,
                status
            )
            VALUES (?, ?, ?, ?, ?, ?)
        ";


        $stmt = $conn->prepare($sql);


        if (!$stmt) {

            $erros[] =
                "Erro ao preparar cadastro: "
                . $conn->error;

        } else {

            $distancia = (float)$distancia;


            $stmt->bind_param(
                "sssdss",
                $nome,
                $origem,
                $destino,
                $distancia,
                $tempo,
                $status
            );


            if ($stmt->execute()) {

                $stmt->close();

                header(
                    "Location: rotas.php?sucesso="
                    . urlencode(
                        "Rota cadastrada com sucesso!"
                    )
                );

                exit;

            } else {

                $erros[] =
                    "Erro ao cadastrar rota: "
                    . $stmt->error;

            }


            $stmt->close();

        }

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

    <title>Cadastro de Rota - Ferrorama</title>


    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

</head>


<body class="bg-light">


<div class="container py-5">

    <div class="row justify-content-center">

        <div class="col-md-8">


            <div class="d-flex justify-content-between align-items-center mb-4">

                <h1>
                    Cadastro de Rota
                </h1>

                <a
                    href="rotas.php"
                    class="btn btn-secondary">

                    Voltar

                </a>

            </div>


            <?php if (!empty($erros)): ?>

                <div class="alert alert-danger">

                    <ul class="mb-0">

                        <?php foreach ($erros as $erro): ?>

                            <li>
                                <?= htmlspecialchars($erro) ?>
                            </li>

                        <?php endforeach; ?>

                    </ul>

                </div>

            <?php endif; ?>


            <div class="card shadow-sm">

                <div class="card-body p-4">


                    <form method="POST">


                        <!-- NOME -->

                        <div class="mb-3">

                            <label class="form-label">

                                Nome da rota

                            </label>

                            <input
                                type="text"
                                name="nome"
                                class="form-control"
                                maxlength="100"
                                value="<?= htmlspecialchars($nome) ?>"
                                required
                            >

                        </div>


                        <!-- ORIGEM -->

                        <div class="mb-3">

                            <label class="form-label">

                                Origem

                            </label>

                            <input
                                type="text"
                                name="origem"
                                class="form-control"
                                maxlength="150"
                                value="<?= htmlspecialchars($origem) ?>"
                                required
                            >

                        </div>


                        <!-- DESTINO -->

                        <div class="mb-3">

                            <label class="form-label">

                                Destino

                            </label>

                            <input
                                type="text"
                                name="destino"
                                class="form-control"
                                maxlength="150"
                                value="<?= htmlspecialchars($destino) ?>"
                                required
                            >

                        </div>


                        <div class="row">


                            <!-- DISTÂNCIA -->

                            <div class="col-md-6 mb-3">

                                <label class="form-label">

                                    Distância (km)

                                </label>

                                <input
                                    type="number"
                                    name="distancia_km"
                                    class="form-control"
                                    step="0.01"
                                    min="0.01"
                                    value="<?= htmlspecialchars($distancia) ?>"
                                    required
                                >

                            </div>


                            <!-- TEMPO -->

                            <div class="col-md-6 mb-3">

                                <label class="form-label">

                                    Tempo estimado

                                </label>

                                <input
                                    type="text"
                                    name="tempo_estimado"
                                    class="form-control"
                                    placeholder="Ex.: 02:30:00"
                                    maxlength="20"
                                    value="<?= htmlspecialchars($tempo) ?>"
                                    required
                                >

                            </div>

                        </div>


                        <!-- STATUS -->

                        <div class="mb-4">

                            <label class="form-label">

                                Status

                            </label>

                            <select
                                name="status"
                                class="form-select"
                            >

                                <option
                                    value="ativa"
                                    <?= $status === "ativa"
                                        ? "selected"
                                        : "" ?>
                                >
                                    Ativa
                                </option>


                                <option
                                    value="inativa"
                                    <?= $status === "inativa"
                                        ? "selected"
                                        : "" ?>
                                >
                                    Inativa
                                </option>


                                <option
                                    value="manutencao"
                                    <?= $status === "manutencao"
                                        ? "selected"
                                        : "" ?>
                                >
                                    Manutenção
                                </option>

                            </select>

                        </div>


                        <button
                            type="submit"
                            class="btn btn-primary w-100">

                            Cadastrar Rota

                        </button>


                    </form>


                </div>

            </div>

        </div>

    </div>

</div>


</body>

</html>