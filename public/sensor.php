
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

    <style>

        :root {
            --primary: #3324d9;
            --primary-light: #6655f5;
            --navy: #0c1929;
            --navy-light: #14263b;
            --background: #f1f3f9;
            --white: #ffffff;
            --text: #142033;
            --muted: #778397;
            --border: #e0e6f0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Roboto', sans-serif;
            background: var(--background);
            color: var(--text);
            min-height: 100vh;
        }

        a {
            text-decoration: none;
        }

        /* CABEÇALHO PADRONIZADO */

        .topbar {
            min-height: 76px;
            padding: 0 7%;
            background: var(--navy);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }

        .brand-icon {
            width: 43px;
            height: 43px;
            border-radius: 13px;
            background: var(--white);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .brand-icon svg {
            width: 26px;
            height: 26px;
        }

        .brand-name {
            display: block;
            color: var(--white);
            font-size: 21px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .brand-subtitle {
            display: block;
            color: #aebbd0;
            font-size: 12px;
            font-weight: 400;
            margin-top: 4px;
        }

        .voltar {
            color: #dce4ef;
            font-size: 13px;
            font-weight: 600;
            padding: 11px 17px;
            border-radius: 9px;
            background: rgba(255,255,255,0.09);
            transition: 0.2s;
        }

        .voltar:hover {
            background: rgba(255,255,255,0.17);
            color: white;
        }

        /* HERO */

        .hero {
            background: linear-gradient(115deg, #0c1929, #242d64);
            color: white;
            padding: 40px 7%;
        }

        .hero-conteudo {
            max-width: 1200px;
            margin: auto;
        }

        .subtitulo {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #a9a2ff;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.8px;
            margin-bottom: 14px;
        }

        .subtitulo::before {
            content: "";
            width: 22px;
            height: 2px;
            background: #827bff;
            border-radius: 5px;
        }

        .hero h1 {
            font-size: 32px;
            font-weight: 800;
            letter-spacing: -0.8px;
            margin-bottom: 10px;
        }

        .hero p {
            color: #c5d0e2;
            font-size: 14px;
            line-height: 1.7;
        }

        /* CONTEÚDO PRINCIPAL */

        .principal {
            max-width: 1200px;
            margin: 0 auto;
            padding: 38px 24px 50px;
        }

        .secao {
            margin-bottom: 35px;
        }

        .secao-titulo {
            display: flex;
            align-items: center;
            gap: 11px;
            margin-bottom: 19px;
        }

        .secao-titulo::before {
            content: "";
            width: 4px;
            height: 24px;
            border-radius: 5px;
            background: var(--primary-light);
        }

        .secao-titulo h2 {
            font-size: 20px;
            font-weight: 700;
            color: var(--navy);
        }

        /* CARDS */

        .grade {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 18px;
        }

        .card,
        .sensor-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 15px;
            padding: 22px;
            box-shadow: 0 4px 16px rgba(18,32,56,0.035);
            transition: transform 0.2s, box-shadow 0.2s;
            min-width: 0;
        }

        .card:hover,
        .sensor-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(18,32,56,0.08);
        }

        .card h3,
        .sensor-card h3 {
            font-size: 17px;
            font-weight: 800;
            color: var(--navy);
            margin-bottom: 15px;
            overflow-wrap: anywhere;
        }

        .card p,
        .sensor-card p {
            font-size: 13px;
            color: #475569;
            margin-bottom: 11px;
            line-height: 1.6;
            overflow-wrap: anywhere;
        }

        .card p:last-child,
        .sensor-card p:last-child {
            margin-bottom: 0;
        }

        .card strong,
        .sensor-card strong {
            color: var(--text);
            font-weight: 700;
        }

        /* STATUS */

        .status {
            font-weight: 700;
        }

        .ativo {
            color: #16a34a;
        }

        .inativo {
            color: #64748b;
        }

        .manutencao {
            color: #d97706;
        }

        .falha {
            color: #dc2626;
        }

        /* MAPA FERROVIÁRIO */

        .mapa-container {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 15px;
            padding: 22px;
            box-shadow: 0 4px 16px rgba(18,32,56,0.035);
        }

        .mapa {
            position: relative;
            background: #e2e8f0;
            border-radius: 10px;
            padding: 75px 10px;
            overflow: hidden;
            min-height: 150px;
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
            color: var(--navy);
            border-radius: 6px;
            padding: 5px 8px;
            font-size: 11px;
            font-weight: 700;
            white-space: nowrap;
            box-shadow: 0 2px 6px rgba(0,0,0,0.12);
        }

        .nota {
            margin-top: 14px;
            color: var(--muted);
            font-size: 12px;
            line-height: 1.6;
        }

        /* ESTADO VAZIO */

        .vazio {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 13px;
            padding: 25px;
            color: var(--muted);
            font-size: 14px;
            text-align: center;
        }

        /* RODAPÉ */

        .rodape {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px 24px 28px;
            border-top: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            color: var(--muted);
            font-size: 12px;
        }

        .rodape strong {
            color: #526078;
            font-weight: 700;
        }

        /* RESPONSIVIDADE */

        @media (max-width: 1000px) {
            .grade {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 600px) {

            .topbar {
                min-height: 68px;
                padding: 0 18px;
            }

            .brand-name {
                font-size: 19px;
            }

            .brand-subtitle {
                font-size: 10px;
            }

            .voltar {
                font-size: 11px;
                padding: 10px 12px;
            }

            .hero {
                padding: 32px 20px;
            }

            .hero h1 {
                font-size: 25px;
            }

            .principal {
                padding: 30px 18px 42px;
            }

            .grade {
                grid-template-columns: 1fr;
                gap: 14px;
            }

            .card,
            .sensor-card {
                padding: 19px;
            }

            .mapa-container {
                padding: 12px;
            }

            .sensor-legenda {
                font-size: 9px;
                padding: 4px;
            }

            .rodape {
                padding: 20px;
                flex-direction: column;
                align-items: flex-start;
                line-height: 1.6;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                transition-duration: 0.01ms !important;
            }
        }

    </style>

</head>

<body>

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

                <section class="secao" style="margin-top: 35px;">

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
