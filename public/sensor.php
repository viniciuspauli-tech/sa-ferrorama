<?php

require_once "../infra/connect.php";

/*
|--------------------------------------------------------------------------
| Buscar os trens
|--------------------------------------------------------------------------
*/

$sqlTrens = "
    SELECT 
        id,
        identificador,
        modelo,
        status,
        velocidade_atual,
        localizacao_atual
    FROM trens
    ORDER BY identificador
";

$resultadoTrens = mysqli_query($conn, $sqlTrens);

if (!$resultadoTrens) {
    die("Erro ao buscar trens: " . mysqli_error($conn));
}


/*
|--------------------------------------------------------------------------
| Buscar sensores
|--------------------------------------------------------------------------
*/

$sqlSensores = "
    SELECT
        sensores.id,
        sensores.nome,
        sensores.localizacao,
        sensores.tipo_dado,
        sensores.trem_id,
        trens.identificador
    FROM sensores
    INNER JOIN trens
        ON sensores.trem_id = trens.id
    ORDER BY sensores.trem_id, sensores.id
";

$resultadoSensores = mysqli_query($conn, $sqlSensores);

if (!$resultadoSensores) {
    die("Erro ao buscar sensores: " . mysqli_error($conn));
}


/*
|--------------------------------------------------------------------------
| Transformar os sensores em um array
|--------------------------------------------------------------------------
*/

$sensores = [];

while ($sensor = mysqli_fetch_assoc($resultadoSensores)) {

    $sensores[] = $sensor;
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

    <title>Monitoramento de Trens - Ferrorama</title>


    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }


        body {
            background-color: #f1f5f9;
        }


        /* NAVBAR */

        .navbar {
            background-color: #0f172a;
            padding: 20px;
        }


        .navbar .container {
            max-width: 1200px;
            margin: auto;
        }


        .navbar-brand {
            color: white;
            text-decoration: none;
            font-size: 25px;
            font-weight: bold;
        }


        /* CONTAINER */

        .principal {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }


        h1 {
            margin-bottom: 30px;
            color: #0f172a;
        }


        /* TRENS */

        .trens {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }


        .card-trem {
            background-color: white;
            padding: 25px;
            border-radius: 12px;
            cursor: pointer;
            border: 2px solid transparent;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.10);
            transition: 0.2s;
        }


        .card-trem:hover {
            transform: scale(1.02);
            border-color: #2563eb;
        }


        .card-trem h2 {
            margin-bottom: 10px;
        }


        .card-trem p {
            margin-top: 7px;
        }


        /* STATUS */

        .status-ativo {
            color: green;
            font-weight: bold;
        }


        .status-inativo {
            color: #64748b;
            font-weight: bold;
        }


        .status-manutencao {
            color: #d97706;
            font-weight: bold;
        }


        .status-falha {
            color: red;
            font-weight: bold;
        }


        /* ÁREA DE INFORMAÇÕES */

        .area {
            display: grid;
            grid-template-columns: 40% 60%;
            gap: 25px;
            margin-top: 40px;
        }


        .caixa {
            background-color: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.10);
        }


        .caixa h2 {
            margin-bottom: 20px;
            color: #0f172a;
        }


        /* SENSOR */

        .sensor-card {
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
        }


        .sensor-card h3 {
            margin-bottom: 10px;
        }


        .sensor-card p {
            margin-top: 6px;
        }


        .sem-sensor {
            color: #64748b;
        }


        /* MAPA */

        .mapa {
            background-color: #dbeafe;
            height: 350px;
            border-radius: 12px;
            position: relative;
            overflow: hidden;
        }


        .linha {
            position: absolute;
            width: 90%;
            height: 8px;
            background-color: #475569;
            top: 50%;
            left: 5%;
            border-radius: 10px;
        }


        .trem-mapa {
            position: absolute;
            width: 80px;
            height: 35px;
            background-color: #2563eb;
            border-radius: 8px;
            top: 45%;
            left: 20%;
            color: white;
            text-align: center;
            line-height: 35px;
            font-size: 12px;
            font-weight: bold;
        }


        .ponto-sensor {
            position: absolute;
            width: 20px;
            height: 20px;
            background-color: red;
            border-radius: 50%;
            top: 43%;
            border: 3px solid white;
        }


        /* RESPONSIVIDADE */

        @media (max-width: 800px) {

            .area {
                grid-template-columns: 1fr;
            }

        }

    </style>

</head>


<body>


    <!-- NAVBAR -->

    <nav class="navbar">

        <div class="container">

            <a
                class="navbar-brand"
                href="sistema.php"
            >
                FERRORAMA
            </a>

        </div>

    </nav>


    <!-- CONTEÚDO -->

    <main class="principal">


        <h1>
            Monitoramento de Trens
        </h1>


        <!-- LISTA DE TRENS -->

        <div class="trens">

            <?php if (mysqli_num_rows($resultadoTrens) > 0): ?>

                <?php while ($trem = mysqli_fetch_assoc($resultadoTrens)): ?>

                    <?php

                    $status = $trem["status"];

                    $statusTexto = "";

                    if ($status === "ativo") {
                        $statusTexto = "Em operação";
                    } elseif ($status === "inativo") {
                        $statusTexto = "Inativo";
                    } elseif ($status === "manutencao") {
                        $statusTexto = "Em manutenção";
                    } elseif ($status === "falha") {
                        $statusTexto = "Falha";
                    }

                    ?>

                    <div
                        class="card-trem"
                        onclick="mostrarTrem(<?= $trem['id'] ?>)"
                    >

                        <h2>

                            <?= htmlspecialchars($trem["identificador"]) ?>

                        </h2>


                        <p>

                            <strong>Modelo:</strong>

                            <?= htmlspecialchars($trem["modelo"]) ?>

                        </p>


                        <p>

                            <strong>Status:</strong>

                            <span class="status-<?= htmlspecialchars($status) ?>">

                                <?= $statusTexto ?>

                            </span>

                        </p>


                        <p>

                            <strong>Velocidade:</strong>

                            <?= htmlspecialchars($trem["velocidade_atual"]) ?>

                            km/h

                        </p>


                        <p>

                            <strong>Localização:</strong>

                            <?= htmlspecialchars($trem["localizacao_atual"] ?? "Não informada") ?>

                        </p>

                    </div>

                <?php endwhile; ?>

            <?php else: ?>

                <p>
                    Nenhum trem cadastrado.
                </p>

            <?php endif; ?>

        </div>


        <!-- INFORMAÇÕES -->

        <div class="area">


            <!-- SENSORES -->

            <div class="caixa">

                <h2>
                    Sensores
                </h2>


                <div id="sensoresContainer">

                    <p class="sem-sensor">

                        Clique em um trem para visualizar seus sensores.

                    </p>

                </div>

            </div>


            <!-- MAPA -->

            <div class="caixa">

                <h2>
                    Localização
                </h2>


                <div class="mapa">

                    <div class="linha"></div>


                    <div
                        class="trem-mapa"
                        id="tremMapa"
                    >
                        Trem
                    </div>


                    <?php

                    $posicoes = [
                        15,
                        30,
                        45,
                        60,
                        75
                    ];

                    $contador = 0;

                    foreach ($sensores as $sensor):

                        $posicao = $posicoes[$contador % count($posicoes)];

                    ?>

                        <div
                            class="ponto-sensor sensor-mapa"
                            data-trem="<?= $sensor["trem_id"] ?>"
                            style="left: <?= $posicao ?>%; display: none;"
                            title="<?= htmlspecialchars($sensor["nome"]) ?>"
                        ></div>

                    <?php

                        $contador++;

                    endforeach;

                    ?>

                </div>

            </div>

        </div>

    </main>


    <!-- JAVASCRIPT -->

    <script>


        /*
        |--------------------------------------------------------------------------
        | Sensores vindos do PHP
        |--------------------------------------------------------------------------
        */

        const sensores = <?= json_encode(
            $sensores,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        ) ?>;


        /*
        |--------------------------------------------------------------------------
        | Mostrar trem
        |--------------------------------------------------------------------------
        */

        function mostrarTrem(tremId) {


            const container =
                document.getElementById(
                    "sensoresContainer"
                );


            const tremMapa =
                document.getElementById(
                    "tremMapa"
                );


            /*
            | Limpar sensores anteriores
            */

            container.innerHTML = "";


            /*
            | Esconder todos os pontos do mapa
            */

            const pontos =
                document.querySelectorAll(
                    ".sensor-mapa"
                );


            pontos.forEach(function(ponto) {

                ponto.style.display = "none";

            });


            /*
            | Filtrar sensores do trem escolhido
            */

            const sensoresDoTrem =
                sensores.filter(function(sensor) {

                    return Number(sensor.trem_id) === Number(tremId);

                });


            /*
            | Se não tiver sensores
            */

            if (sensoresDoTrem.length === 0) {

                container.innerHTML = `
                    <p class="sem-sensor">
                        Este trem não possui sensores cadastrados.
                    </p>
                `;

            }


            /*
            | Mostrar sensores
            */

            sensoresDoTrem.forEach(function(sensor) {


                const card =
                    document.createElement("div");


                card.className =
                    "sensor-card";


                card.innerHTML = `

                    <h3>
                        ${escapeHtml(sensor.nome)}
                    </h3>

                    <p>
                        <strong>Localização:</strong>
                        ${escapeHtml(sensor.localizacao)}
                    </p>

                    <p>
                        <strong>Tipo de dado:</strong>
                        ${escapeHtml(sensor.tipo_dado)}
                    </p>

                `;


                container.appendChild(card);

            });


            /*
            | Mostrar os sensores no mapa
            */

            pontos.forEach(function(ponto) {

                if (
                    Number(ponto.dataset.trem)
                    ===
                    Number(tremId)
                ) {

                    ponto.style.display = "block";

                }

            });


            /*
            | Mover o trem no mapa
            */

            let posicao =
                Math.floor(
                    Math.random() * 70
                ) + 10;


            tremMapa.style.left =
                posicao + "%";


            tremMapa.innerText =
                "Trem " + tremId;

        }


        /*
        |--------------------------------------------------------------------------
        | Segurança para texto vindo do banco
        |--------------------------------------------------------------------------
        */

        function escapeHtml(texto) {

            const div =
                document.createElement("div");

            div.textContent =
                texto ?? "";

            return div.innerHTML;

        }


    </script>


</body>

</html>