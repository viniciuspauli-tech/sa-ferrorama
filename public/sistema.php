<?php

require_once __DIR__ . "/../infra/auth.php";

$ehAdministrador =
    ($_SESSION["usuario_perfil"] ?? "") === "administrador";

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Ferrorama | Painel de Controle</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #0d009c;
            --primary-light: #4038a0;
            --primary-hover: #080078;
            --navy: #0c1929;
            --navy-light: #14263b;
            --background: #f3f5f9;
            --white: #ffffff;
            --text: #142033;
            --muted: #778397;
            --border: #e1e6ef;
            --danger: #c62828;
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
            color: inherit;
        }

        /* CABEÇALHO */

        .topbar {
            min-height: 76px;
            padding: 0 7%;
            background: var(--navy);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
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
        }

        .brand-icon svg {
            width: 26px;
            height: 26px;
        }

        .brand-name {
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

        .user-area {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #dce4ef;
            font-size: 14px;
        }

        .user-icon {
            width: 38px;
            height: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: var(--navy-light);
            color: #ffffff;
        }

        .user-icon svg {
            width: 21px;
            height: 21px;
        }

        .role-label {
            color: #b6c4d8;
            font-size: 12px;
            margin-top: 4px;
        }

        /* BOAS-VINDAS */

        .hero {
            background:
                radial-gradient(
                    circle at 85% 15%,
                    rgba(64, 56, 160, 0.35),
                    transparent 32%
                ),
                linear-gradient(120deg, var(--navy), #172743);
            padding: 58px 7% 64px;
            position: relative;
            overflow: hidden;
        }

        .hero-content {
            max-width: 1200px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #c7c5ff;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.8px;
            margin-bottom: 18px;
        }

        .eyebrow::before {
            content: "";
            width: 22px;
            height: 2px;
            background: #827bff;
            border-radius: 5px;
        }

        .hero h1 {
            color: var(--white);
            font-size: clamp(30px, 4vw, 43px);
            font-weight: 700;
            letter-spacing: -1.2px;
            line-height: 1.2;
            margin-bottom: 14px;
        }

        .hero h1 span {
            color: #aaa5ff;
        }

        .hero p {
            max-width: 540px;
            color: #bdc9da;
            font-size: 16px;
            line-height: 1.7;
        }

        /* CONTEÚDO PRINCIPAL */

        .main-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 38px 24px 60px;
        }

        .section-heading {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 23px;
        }

        .section-heading h2 {
            font-size: 21px;
            font-weight: 700;
            letter-spacing: -0.4px;
            margin-bottom: 7px;
        }

        .section-heading p {
            color: var(--muted);
            font-size: 14px;
            line-height: 1.5;
        }

        .section-count {
            flex-shrink: 0;
            padding: 8px 12px;
            background: #e9e8ff;
            color: var(--primary);
            border-radius: 9px;
            font-size: 12px;
            font-weight: 700;
        }

        /* CARDS */

        .card-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 20px;
        }

        .menu-card {
            --accent: var(--primary);

            min-width: 0;
            min-height: 205px;
            position: relative;
            display: flex;
            flex-direction: column;
            padding: 25px;
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 17px;
            text-decoration: none;
            overflow: hidden;
            box-shadow: 0 4px 16px rgba(18, 32, 56, 0.025);
            transition:
                transform 0.22s ease,
                box-shadow 0.22s ease,
                border-color 0.22s ease;
        }

        .menu-card::before {
            content: "";
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            height: 3px;
            background: var(--accent);
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.22s ease;
        }

        .menu-card:hover {
            transform: translateY(-5px);
            border-color: #c9c5ff;
            box-shadow: 0 14px 32px rgba(23, 25, 75, 0.09);
        }

        .menu-card:hover::before {
            transform: scaleX(1);
        }

        .menu-card:focus-visible,
        .logout-link:focus-visible {
            outline: 3px solid #827bff;
            outline-offset: 4px;
        }

        .card-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 21px;
        }

        .card-icon {
            width: 49px;
            height: 49px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 14px;
            background: #eeedff;
            color: var(--accent);
        }

        .card-icon svg {
            width: 25px;
            height: 25px;
        }

        .card-arrow {
            color: #a1aabd;
            font-size: 23px;
            line-height: 1;
            transition: color 0.2s, transform 0.2s;
        }

        .menu-card:hover .card-arrow {
            color: var(--accent);
            transform: translate(3px, -3px);
        }

        .menu-card h3 {
            font-size: 17px;
            font-weight: 700;
            margin-bottom: 9px;
            letter-spacing: -0.2px;
        }

        .menu-card p {
            color: var(--muted);
            font-size: 13px;
            line-height: 1.6;
        }

        .card-action {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            margin-top: auto;
            padding-top: 20px;
            color: var(--accent);
            font-size: 13px;
            font-weight: 700;
        }

        .card-action span {
            transition: transform 0.2s;
        }

        .menu-card:hover .card-action span {
            transform: translateX(4px);
        }

        /* SAIR */

        .logout-card {
            --accent: var(--danger);
        }

        .logout-card .card-icon {
            background: #fff0f0;
        }

        .logout-card:hover {
            border-color: #f3cccc;
        }

        .logout-link {
            text-decoration: none;
        }

        /* RODAPÉ */

        .footer {
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

        .footer strong {
            color: #526078;
            font-weight: 700;
        }

        /* RESPONSIVIDADE */

        @media (max-width: 900px) {
            .topbar {
                padding: 0 5%;
            }

            .hero {
                padding: 48px 5% 52px;
            }

            .card-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 560px) {
            .topbar {
                min-height: 68px;
                padding: 0 20px;
            }

            .brand-name {
                font-size: 19px;
            }

            .brand-subtitle {
                font-size: 11px;
            }

            .user-area {
                gap: 0;
            }

            .user-info {
                display: none;
            }

            .hero {
                padding: 42px 22px 46px;
            }

            .hero h1 {
                font-size: 32px;
            }

            .hero p {
                font-size: 14px;
            }

            .main-content {
                padding: 30px 18px 42px;
            }

            .section-heading {
                align-items: flex-start;
            }

            .section-heading h2 {
                font-size: 19px;
            }

            .section-count {
                font-size: 11px;
            }

            .card-grid {
                grid-template-columns: 1fr;
                gap: 14px;
            }

            .menu-card {
                min-height: 185px;
                padding: 22px;
            }

            .footer {
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
                scroll-behavior: auto !important;
            }
        }
    </style>
</head>

<body>

    <header class="topbar">
        <a class="brand" href="sistema.php" aria-label="Ferrorama - página inicial">
            <span class="brand-icon">
                <svg viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="1.8"
                     stroke-linecap="round" stroke-linejoin="round"
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

        <div class="user-area">
            <span class="user-icon">
                <svg viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="1.7"
                     stroke-linecap="round" stroke-linejoin="round"
                     aria-hidden="true">
                    <circle cx="12" cy="8" r="4"></circle>
                    <path d="M4 21a8 8 0 0 1 16 0"></path>
                </svg>
            </span>

            <span class="user-info">
                <span>Área do usuário</span>
                <span class="role-label">
                    <?= $ehAdministrador ? "Administrador" : "Usuário" ?>
                </span>
            </span>
        </div>
    </header>

    <section class="hero">
        <div class="hero-content">
            <div class="eyebrow">Painel de controle</div>

            <h1>
                Bem-vindo ao <span>Ferrorama.</span>
            </h1>

            <p>
                Gerencie os recursos ferroviários, consulte informações
                e acompanhe as funcionalidades do sistema em um só lugar.
            </p>
        </div>
    </section>

    <main class="main-content">

        <div class="section-heading">
            <div>
                <h2>O que você deseja acessar?</h2>
                <p>Selecione uma das opções disponíveis abaixo.</p>
            </div>

            <span class="section-count">
                <?= $ehAdministrador ? "6 opções" : "3 opções" ?>
            </span>
        </div>

        <div class="card-grid">

            <!-- Disponível para todos os usuários autorizados -->
            <a class="menu-card" href="sensor.php">
                <div class="card-top">
                    <span class="card-icon">
                        <svg viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="1.8"
                             stroke-linecap="round" stroke-linejoin="round"
                             aria-hidden="true">
                            <rect x="4" y="3" width="16" height="13" rx="3"></rect>
                            <path d="M7 7h10M7 11h10M8 16l-3 5M16 16l3 5M4 13h16"></path>
                            <circle cx="8" cy="13" r="1"></circle>
                            <circle cx="16" cy="13" r="1"></circle>
                        </svg>
                    </span>
                    <span class="card-arrow" aria-hidden="true">↗</span>
                </div>

                <h3>Trens e Sensores</h3>
                <p>Acesse a área de trens e sensores do sistema ferroviário.</p>

                <span class="card-action">
                    Acessar funcionalidade <span>→</span>
                </span>
            </a>

            <!-- Consulta de trens disponível para todos -->
            <a class="menu-card" href="trem/listagemtrem.php">
                <div class="card-top">
                    <span class="card-icon">
                        <svg viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="1.8"
                             stroke-linecap="round" stroke-linejoin="round"
                             aria-hidden="true">
                            <circle cx="10.5" cy="10.5" r="6.5"></circle>
                            <path d="m16 16 4.5 4.5M7.5 8.5h6M7.5 11.5h6"></path>
                        </svg>
                    </span>
                    <span class="card-arrow" aria-hidden="true">↗</span>
                </div>

                <h3>Consultar Trens</h3>
                <p>Consulte os trens cadastrados e visualize suas informações.</p>

                <span class="card-action">
                    Acessar funcionalidade <span>→</span>
                </span>
            </a>

            <?php if ($ehAdministrador): ?>

                <!-- Somente administrador -->
                <a class="menu-card" href="rotas/rotas.php">
                    <div class="card-top">
                        <span class="card-icon">
                            <svg viewBox="0 0 24 24" fill="none"
                                 stroke="currentColor" stroke-width="1.8"
                                 stroke-linecap="round" stroke-linejoin="round"
                                 aria-hidden="true">
                                <circle cx="6" cy="18" r="3"></circle>
                                <circle cx="18" cy="6" r="3"></circle>
                                <path d="M8.5 16.5 15.5 7.5M13 6h2M6 13v2"></path>
                            </svg>
                        </span>
                        <span class="card-arrow" aria-hidden="true">↗</span>
                    </div>

                    <h3>Gerenciar Rotas</h3>
                    <p>Gerencie os trajetos e as informações das rotas ferroviárias.</p>

                    <span class="card-action">
                        Acessar funcionalidade <span>→</span>
                    </span>
                </a>

                <a class="menu-card" href="cadastro_item.php">
                    <div class="card-top">
                        <span class="card-icon">
                            <svg viewBox="0 0 24 24" fill="none"
                                 stroke="currentColor" stroke-width="1.8"
                                 stroke-linecap="round" stroke-linejoin="round"
                                 aria-hidden="true">
                                <circle cx="12" cy="12" r="8"></circle>
                                <path d="M12 8v8M8 12h8"></path>
                            </svg>
                        </span>
                        <span class="card-arrow" aria-hidden="true">↗</span>
                    </div>

                    <h3>Cadastrar Sensor</h3>
                    <p>Cadastre novos sensores para ampliar o monitoramento ferroviário.</p>

                    <span class="card-action">
                        Acessar funcionalidade <span>→</span>
                    </span>
                </a>

                <a class="menu-card" href="usuarios/pagina_adm.php">
                    <div class="card-top">
                        <span class="card-icon">
                            <svg viewBox="0 0 24 24" fill="none"
                                 stroke="currentColor" stroke-width="1.8"
                                 stroke-linecap="round" stroke-linejoin="round"
                                 aria-hidden="true">
                                <circle cx="9" cy="8" r="3.5"></circle>
                                <path d="M2.5 20a6.5 6.5 0 0 1 13 0M16 5.5a3.5 3.5 0 0 1 0 6.5M18 14a5.5 5.5 0 0 1 3.5 5"></path>
                            </svg>
                        </span>
                        <span class="card-arrow" aria-hidden="true">↗</span>
                    </div>

                    <h3>Usuários</h3>
                    <p>Gerencie os usuários e os acessos ao sistema.</p>

                    <span class="card-action">
                        Acessar funcionalidade <span>→</span>
                    </span>
                </a>

            <?php endif; ?>

            <a class="menu-card logout-card" href="logout.php">
                <div class="card-top">
                    <span class="card-icon">
                        <svg viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="1.8"
                             stroke-linecap="round" stroke-linejoin="round"
                             aria-hidden="true">
                            <path d="M10 17l5-5-5-5M15 12H3"></path>
                            <path d="M12 3h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-6"></path>
                        </svg>
                    </span>
                    <span class="card-arrow" aria-hidden="true">↗</span>
                </div>

                <h3>Sair do sistema</h3>
                <p>Encerre sua sessão de acesso ao Ferrorama.</p>

                <span class="card-action">
                    Encerrar sessão <span>→</span>
                </span>
            </a>

        </div>
    </main>

    <footer class="footer">
        <span><strong>Ferrorama</strong> · Sistema de gerenciamento ferroviário</span>
        <span>Painel de controle</span>
    </footer>

</body>
</html>
```