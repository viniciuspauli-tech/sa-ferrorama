
<?php
require_once "../infra/auth.php";
require_once "../infra/connect.php";

// Buscar trens
$sqlTrens = "SELECT id, identificador, modelo, status,
                    velocidade_atual, localizacao_atual
             FROM trens
             ORDER BY identificador";

$resultadoTrens = mysqli_query($conn, $sqlTrens);

if (!$resultadoTrens) {
    die("Erro ao buscar trens.");
}

$trens = mysqli_fetch_all($resultadoTrens, MYSQLI_ASSOC);

// Buscar sensores sem associá-los aos trens
$sqlSensores = "SELECT id, nome, localizacao, tipo_dado
                FROM sensores
                ORDER BY localizacao, nome";

$resultadoSensores = mysqli_query($conn, $sqlSensores);

if (!$resultadoSensores) {
    die("Erro ao buscar sensores.");
}

$sensores = mysqli_fetch_all($resultadoSensores, MYSQLI_ASSOC);

// Identificar quilômetros informados, como KM 12
$quilometros = [];

foreach ($sensores as $sensor) {
    if (preg_match(
        '/(?:KM|QUILOMETRO|QUILÔMETRO)\s*(\d+(?:[.,]\d+)?)/iu',
        $sensor["localizacao"],
        $resultado
    )) {
        $quilometros[] = (float) str_replace(",", ".", $resultado[1]);
    }
}

$maiorKm = count($quilometros) > 0 ? max($quilometros) : 0;

function e($valor) {
    return htmlspecialchars((string)($valor ?? ""), ENT_QUOTES, "UTF-8");
}

function textoStatus($status) {
    $statusMap = [
        "ativo" => "Em operação",
        "inativo" => "Inativo",
        "manutencao" => "Em manutenção",
        "falha" => "Falha"
    ];

    return $statusMap[$status] ?? "Não informado";
}

function posicaoSensor($localizacao, $indice, $total, $maiorKm) {
    if (preg_match(
        '/(?:KM|QUILOMETRO|QUILÔMETRO)\s*(\d+(?:[.,]\d+)?)/iu',
        $localizacao,
        $resultado
    )) {
        $km = (float) str_replace(",", ".", $resultado[1]);

        if ($maiorKm > 0) {
            return max(5, min(95, ($km / $maiorKm) * 90 + 5));
        }
    }

    // Localizações sem quilômetro: distribuir apenas como referência visual
    if ($total <= 1) {
        return 50;
    }

    return 5 + ($indice * 90 / ($total - 1));
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Monitoramento - Ferrorama</title>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
        }

        body {
            background: #f1f5f9;
            color: #0f172a;
        }

        .navbar {
            background: #0f172a;
            padding: 20px;
        }

        .navbar a {
            color: white;
            text-decoration: none;
            font-size: 20px;
            font-weight: bold;
        }

        .principal {
            max-width: 1200px;
            margin: 35px auto;
            padding: 0 20px;
        }

        h1 {
            margin-bottom: 28px;
        }

        .secao {
            margin-bottom: 35px;
        }

        .secao h2 {
            margin-bottom: 18px;
        }

        .grade {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 18px;
        }

        .card {
            background: white;
            border-radius: 12px;
            padding: 22px;
            box-shadow: 0 2px 10px #00000010;
            overflow-wrap: anywhere;
        }

        .card h3 {
            margin-bottom: 12px;
        }

        .card p {
            margin-top: 9px;
            line-height: 1.5;
        }

        .status {
            font-weight: bold;
        }

        .ativo {
            color: #15803d;
        }

        .inativo {
            color: #64748b;
        }

        .manutencao {
            color: #b45309;
        }

        .falha {
            color: #dc2626;
        }

        .vazio {
            color: #64748b;
            background: white;
            border-radius: 10px;
            padding: 20px;
        }

        /* Esquema visual do trilho */
        .mapa {
            position: relative;
            background: #e2e8f0;
            border-radius: 12px;
            padding: 75px 10px;
            overflow: hidden;
        }

        .trilho {
            position: absolute;
            top: 50%;
            left: 0;
            width: 100%;
            height: 12px;
            transform: translateY(-50%);
            background: #475569;
            border-top: 3px solid #94a3b8;
            border-bottom: 3px solid #94a3b8;
        }

        .sensor-ponto {
            position: absolute;
            top: 50%;
            transform: translate(-50%, -50%);
            z-index: 2;
            text-align: center;
        }

        .sensor-bolinha {
            width: 20px;
            height: 20px;
            margin: 0 auto 8px;
            border-radius: 50%;
            background: #dc2626;
            border: 3px solid white;
            box-shadow: 0 0 0 2px #991b1b;
        }

        .sensor-legenda {
            background: white;
            border-radius: 5px;
            padding: 5px 7px;
            font-size: 12px;
            white-space: nowrap;
            box-shadow: 0 1px 4px #00000020;
        }

        .nota {
            margin-top: 12px;
            color: #475569;
            font-size: 13px;
            line-height: 1.5;
        }

        @media (max-width: 600px) {
            .principal {
                margin-top: 24px;
                padding: 0 14px;
            }

            .mapa {
                overflow-x: auto;
                min-width: 0;
            }

            .sensor-legenda {
                font-size: 10px;
            }
        }
    </style>
</head>

<body>

<nav class="navbar">
    <a href="sistema.php">VOLTAR AO SISTEMA</a>
</nav>

<main class="principal">

    <h1>Monitoramento Ferrorama</h1>

    <!-- SEÇÃO 1: TRENS -->
    <section class="secao">
        <h2>Trens</h2>

        <?php if (count($trens) > 0): ?>
            <div class="grade">

                <?php foreach ($trens as $trem): ?>
                    <article class="card">
                        <h3><?= e($trem["identificador"]) ?></h3>

                        <p>
                            <strong>Modelo:</strong>
                            <?= e($trem["modelo"]) ?>
                        </p>

                        <p>
                            <strong>Status:</strong>
                            <span class="status <?= e($trem["status"]) ?>">
                                <?= e(textoStatus($trem["status"])) ?>
                            </span>
                        </p>

                        <p>
                            <strong>Velocidade:</strong>
                            <?= e($trem["velocidade_atual"]) ?> km/h
                        </p>

                        <p>
                            <strong>Localização atual:</strong>
                            <?= e($trem["localizacao_atual"] ?: "Não informada") ?>
                        </p>
                    </article>
                <?php endforeach; ?>

            </div>
        <?php else: ?>
            <p class="vazio">Nenhum trem cadastrado.</p>
        <?php endif; ?>
    </section>

    <!-- SEÇÃO 2: SENSORES -->
    <section class="secao">
        <h2>Sensores instalados no trilho</h2>

        <?php if (count($sensores) > 0): ?>
            <div class="grade">

                <?php foreach ($sensores as $sensor): ?>
                    <article class="card">
                        <h3><?= e($sensor["nome"]) ?></h3>

                        <p>
                            <strong>Localização:</strong>
                            <?= e($sensor["localizacao"]) ?>
                        </p>

                        <p>
                            <strong>Tipo de dado:</strong>
                            <?= e($sensor["tipo_dado"]) ?>
                        </p>
                    </article>
                <?php endforeach; ?>

            </div>

            <h2 style="margin-top: 30px;">Posição dos sensores no trilho</h2>

            <div class="mapa">
                <div class="trilho"></div>

                <?php foreach ($sensores as $indice => $sensor): ?>
                    <?php
                    $posicao = posicaoSensor(
                        $sensor["localizacao"],
                        $indice,
                        count($sensores),
                        $maiorKm
                    );
                    ?>

                    <div
                        class="sensor-ponto"
                        style="left: <?= e($posicao) ?>%;"
                        title="<?= e($sensor["nome"] . " - " . $sensor["localizacao"]) ?>"
                    >
                        <div class="sensor-bolinha"></div>

                        <div class="sensor-legenda">
                            <?= e($sensor["localizacao"]) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <p class="nota">
                O desenho é apenas uma representação ilustrativa do trilho.
                Sensores com localização em quilômetros são posicionados
                proporcionalmente no esquema. Outras localizações aparecem
                em posições aproximadas, não em coordenadas reais.
            </p>

        <?php else: ?>
            <p class="vazio">Nenhum sensor cadastrado.</p>
        <?php endif; ?>
    </section>

</main>

</body>
</html>
