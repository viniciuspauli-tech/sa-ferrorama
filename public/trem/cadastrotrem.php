<body class="page-public_trem_cadastrotrem_php">

<header class="navbar-ferrorama">
    <div class="navbar-content">

        <a href="../sistema.php" class="brand">
            <div class="brand-icon">
                <svg viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" stroke-linejoin="round"
                    aria-hidden="true">
                    <rect x="5" y="3" width="14" height="15" rx="4"/>
                    <path d="M8 7h8M8 11h2m4 0h2M8 18l-2 3m10-3 2 3M8 15h8"/>
                </svg>
            </div>

            <div>
                <div class="brand-name">Ferrorama</div>
                <div class="brand-subtitle">Sistema ferroviário</div>
            </div>
        </a>

        <div class="navbar-label">
            <svg viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="1.8"
                stroke-linecap="round" stroke-linejoin="round"
                aria-hidden="true">
                <circle cx="12" cy="8" r="4"/>
                <path d="M5 21v-2a7 7 0 0 1 14 0v2"/>
            </svg>
            Área administrativa
        </div>

    </div>
</header>

<section class="hero">
    <div class="hero-content">

        <div>
            <div class="eyebrow">Gestão ferroviária</div>

            <h1>Cadastrar <span>trem.</span></h1>

            <p class="hero-description">
                Adicione uma nova composição ao Ferrorama e configure
                suas informações operacionais em um só lugar.
            </p>
        </div>

        <a href="listagemtrem.php" class="back-button">
            <svg viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round"
                aria-hidden="true">
                <path d="m15 18-6-6 6-6"/>
                <path d="M9 12h12"/>
            </svg>
            Voltar para trens
        </a>

    </div>
</section>

<main class="main-content">

    <div class="section-heading">
        <h2>Informações do trem</h2>
        <p>
            Preencha os dados abaixo para cadastrar uma nova composição.
            Os campos marcados com * são obrigatórios.
        </p>
    </div>

    <?php if ($mensagemErro !== ""): ?>
        <div class="alert-custom alert-error" role="alert">
            <svg viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round"
                aria-hidden="true">
                <circle cx="12" cy="12" r="10"/>
                <path d="M12 8v4m0 4h.01"/>
            </svg>

            <div><?= escapar($mensagemErro) ?></div>
        </div>
    <?php endif; ?>

    <section class="form-card">

        <div class="form-card-header">
            <div class="form-icon">
                <svg viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" stroke-linejoin="round"
                    aria-hidden="true">
                    <rect x="5" y="3" width="14" height="15" rx="4"/>
                    <path d="M8 7h8M8 11h2m4 0h2M8 15h8M8 18l-2 3m10-3 2 3"/>
                </svg>
            </div>

            <div>
                <h3>Dados cadastrais</h3>
                <p>Identificação, status e informações operacionais</p>
            </div>
        </div>

        <div class="form-card-body">

            <form method="POST" action="cadastrotrem.php">

                <div class="row g-4">

                    <div class="col-md-6">
                        <label for="identificador" class="form-label">
                            Identificador <span class="required-mark">*</span>
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="identificador"
                            name="identificador"
                            maxlength="50"
                            placeholder="Ex.: TR-005"
                            value="<?= escapar($identificador) ?>"
                            autocomplete="off"
                            required
                        >

                        <div class="field-hint">
                            Código único de identificação do trem.
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label for="modelo" class="form-label">
                            Modelo <span class="required-mark">*</span>
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="modelo"
                            name="modelo"
                            maxlength="100"
                            placeholder="Ex.: Expresso 4000"
                            value="<?= escapar($modelo) ?>"
                            required
                        >

                        <div class="field-hint">
                            Nome ou modelo da composição ferroviária.
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label for="status" class="form-label">
                            Status operacional <span class="required-mark">*</span>
                        </label>

                        <select
                            class="form-select"
                            id="status"
                            name="status"
                            required
                        >
                            <option value="ativo"
                                <?= $status === "ativo" ? "selected" : "" ?>>
                                Ativo
                            </option>

                            <option value="inativo"
                                <?= $status === "inativo" ? "selected" : "" ?>>
                                Inativo
                            </option>

                            <option value="manutencao"
                                <?= $status === "manutencao" ? "selected" : "" ?>>
                                Em manutenção
                            </option>

                            <option value="falha"
                                <?= $status === "falha" ? "selected" : "" ?>>
                                Com falha
                            </option>
                        </select>

                        <div class="field-hint">
                            Situação atual da composição.
                        </div>
                    </div>