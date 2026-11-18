
<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . "/../infra/auth.php";
require_once __DIR__ . "/../infra/connect.php";

// ==========================================
// BUSCAR TRENS
// ==========================================

$sqlTrens = "
    SELECT id, identificador, modelo, status,
           velocidade_atual, localizacao_atual
    FROM trens
    ORDER BY identificador
";

$resultadoTrens = $conn->query($sqlTrens);

if (!$resultadoTrens) {
    error_log($conn->error);
    exit("Erro ao buscar trens.");
}

$trens = $resultadoTrens->fetch_all(MYSQLI_ASSOC);

// ==========================================
// BUSCAR SENSORES
// ==========================================

$sqlSensores = "
    SELECT id, nome, localizacao, tipo_dado
    FROM sensores
    ORDER BY localizacao, nome
";

$resultadoSensores = $conn->query($sqlSensores);

if (!$resultadoSensores) {
    error_log($conn->error);
    exit("Erro ao buscar sensores.");
}

$sensores = $resultadoSensores->fetch_all(MYSQLI_ASSOC);

// ==========================================
// IDENTIFICAR QUILÔMETROS
// ==========================================

$quilometros = [];

foreach ($sensores as $sensor) {

    $localizacao = (string)($sensor["localizacao"] ?? "");

    if (preg_match(
        '/(?:KM|QUILOMETRO|QUILÔMETRO)\s*[-:]?\s*(\d+(?:[.,]\d+)?)/iu',
        $localizacao,
        $resultado
    )) {
        $quilometros[] = (float)str_replace(",", ".", $resultado[1]);
    }
}

$maiorKm = count($quilometros) > 0 ? max($quilometros) : 0;

// ==========================================
// FUNÇÕES AUXILIARES
// ==========================================

function e($valor): string {
    return htmlspecialchars(
        (string)($valor ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}

function textoStatus($status): string {

    $statusMap = [
        "ativo" => "Em operação",
        "inativo" => "Inativo",
        "manutencao" => "Em manutenção",
        "falha" => "Falha"
    ];

    return $statusMap[$status] ?? "Não informado";
}

function posicaoSensor($localizacao, $indice, $total, $maiorKm): float {

    if (preg_match(
        '/(?:KM|QUILOMETRO|QUILÔMETRO)\s*[-:]?\s*(\d+(?:[.,]\d+)?)/iu',
        (string)$localizacao,
        $resultado
    )) {

        $km = (float)str_replace(",", ".", $resultado[1]);

        if ($maiorKm > 0) {
            return max(5, min(95, ($km / $maiorKm) * 90 + 5));
        }
    }

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

    <title>Monitoramento | Ferrorama</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    

<link rel="stylesheet" href="../style/style.css?v=3">
</head>

<body class="page-public_sensor_php">

    <!-- CABEÇALHO -->

    <header class="topbar">

        <a class="brand" href="sistema.php" aria-label="Ferrorama - página inicial">

            <span class="brand-icon">

                <svg viewBox="0 0 24 24"
                     fill="none"
                     stroke="currentColor"
                     stroke-width="1.8"
                     stroke-linecap="round"
                     stroke-linejoin="round"
                     aria-hidden="true">

                    <rect x="5" y="3" width="14" height="15" rx="4"></rect>

                    <path d="M8 7h8M8 12h8M8 18l-2 3M16 18l2 3M5 15h14"></path>

                    <circle cx="9" cy="15" r="1"></circle>
                    <circle cx="15" cy="15" r="1"></circle>

                </svg>

            </span>

            <span>
                <span class="brand-name">Ferrorama</span>
                <span class="brand-subtitle">Sistema ferroviário</span>
            </span>

        </a>

        <a href="sistema.php" class="voltar">
            Voltar ao sistema
        </a>

    </header>

    <!-- HERO -->

    <section class="hero">

        <div class="hero-conteudo">

            <div class="subtitulo">
                PAINEL DE CONTROLE
            </div>

            <h1>Monitoramento Ferroviário.</h1>

            <p>
                Acompanhe os trens cadastrados, consulte os sensores
                instalados e visualize suas posições ao longo da ferrovia.
            </p>

        </div>

    </section>

    <!-- CONTEÚDO PRINCIPAL -->

    <main class="principal">

        <!-- TRENS -->

        <section class="secao">

            <div class="secao-titulo">
                <h2>Trens monitorados</h2>
            </div>

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

                <div class="vazio">
                    Nenhum trem cadastrado no sistema.
                </div>

            <?php endif; ?>

        </section>

        <!-- SENSORES -->

        <section class="secao">

            <div class="secao-titulo">
                <h2>Sensores instalados no trilho</h2>
            </div>

            <?php if (count($sensores) > 0): ?>

                <div class="grade">

                    <?php foreach ($sensores as $sensor): ?>

                        <article class="sensor-card">

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

                <!-- POSIÇÃO DOS SENSORES -->

                <section class="secao inline-style-1">

                    <div class="secao-titulo">
                        <h2>Posição dos sensores no trilho</h2>
                    </div>

                    <div class="mapa-container">

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
                                    class="sensor-ponto inline-style-2"
                                   
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
                            O desenho representa ilustrativamente o trilho ferroviário.
                            Sensores com localização em quilômetros são posicionados
                            proporcionalmente no esquema. Outras localizações aparecem
                            em posições aproximadas, não em coordenadas reais.
                        </p>

                    </div>

                </section>

            <?php else: ?>

                <div class="vazio">
                    Nenhum sensor cadastrado no sistema.
                </div>

            <?php endif; ?>

        </section>

    </main>

    <!-- RODAPÉ -->

    <footer class="rodape">

        <span>
            <strong>Ferrorama</strong> · Sistema de gerenciamento ferroviário
        </span>

        <span>Monitoramento ferroviário</span>

    </footer>

</body>
</html>
