<?php

/*
 * Execute uma vez no banco (se a coluna ainda não existir):
 *
 *   ALTER TABLE usuarios
 *       ADD COLUMN cpf CHAR(11) NULL UNIQUE AFTER email,
 *       ADD COLUMN cep CHAR(8) NULL,
 *       ADD COLUMN logradouro VARCHAR(150) NULL,
 *       ADD COLUMN bairro VARCHAR(100) NULL,
 *       ADD COLUMN cidade VARCHAR(100) NULL,
 *       ADD COLUMN uf CHAR(2) NULL;
 */

require_once "../../infra/auth.php";

if (($_SESSION["usuario_perfil"] ?? "") !== "administrador") {
    header("Location: ../home.php");
    exit;
}

require_once "../../infra/connect.php";

// ---------------------------------------------------------
// Funções de CPF
// ---------------------------------------------------------

function limparCpf($cpf): string
{
    return preg_replace('/\D/', '', (string) $cpf);
}

function validarCpf($cpf): bool
{
    $cpf = limparCpf($cpf);

    if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
        return false;
    }

    for ($t = 9; $t < 11; $t++) {
        $soma = 0;

        for ($i = 0; $i < $t; $i++) {
            $soma += (int) $cpf[$i] * (($t + 1) - $i);
        }

        if ((int) $cpf[$t] !== ((10 * $soma) % 11) % 10) {
            return false;
        }
    }

    return true;
}

function cpfJaCadastrado($conn, $cpf): bool
{
    $stmt = mysqli_prepare($conn, "SELECT id FROM usuarios WHERE cpf = ?");

    if (!$stmt) {
        return false;
    }

    $cpf = limparCpf($cpf);

    mysqli_stmt_bind_param($stmt, "s", $cpf);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    $existe = mysqli_stmt_num_rows($stmt) > 0;

    mysqli_stmt_close($stmt);

    return $existe;
}

// ---------------------------------------------------------
// API de CPF: GET cadastro_usuario.php?api=cpf&cpf=00000000000
// ---------------------------------------------------------

if (($_GET["api"] ?? "") === "cpf") {

    header("Content-Type: application/json; charset=utf-8");
    header("Cache-Control: no-store");

    if ($_SERVER["REQUEST_METHOD"] !== "GET") {
        http_response_code(405);
        echo json_encode(["erro" => "Método não permitido."], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $cpfApi = limparCpf($_GET["cpf"] ?? "");

    if (strlen($cpfApi) !== 11) {
        http_response_code(400);
        echo json_encode(["erro" => "Informe um CPF com 11 números."], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (!validarCpf($cpfApi)) {
        http_response_code(422);
        echo json_encode(["valido" => false, "erro" => "CPF inválido."], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode([
        "valido" => true,
        "cpf" => $cpfApi,
        "formatado" => substr($cpfApi, 0, 3) . "." . substr($cpfApi, 3, 3) . "."
            . substr($cpfApi, 6, 3) . "-" . substr($cpfApi, 9, 2)
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// Token CSRF
if (empty($_SESSION["csrf"])) {
    $_SESSION["csrf"] = bin2hex(random_bytes(32));
}

$erro = "";
$nome = "";
$email = "";
$cpf = "";
$cep = "";
$logradouro = "";
$bairro = "";
$cidade = "";
$uf = "";
$perfil = "usuario";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nome = trim($_POST["nome"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $cpf = limparCpf($_POST["cpf"] ?? "");
    $cep = preg_replace('/\D/', '', $_POST["cep"] ?? "");
    $logradouro = trim($_POST["logradouro"] ?? "");
    $bairro = trim($_POST["bairro"] ?? "");
    $cidade = trim($_POST["cidade"] ?? "");
    $uf = strtoupper(trim($_POST["uf"] ?? ""));
    $senha = $_POST["senha"] ?? "";
    $confirmar_senha = $_POST["confirmar_senha"] ?? "";
    $perfil = $_POST["perfil"] ?? "usuario";

    if (!hash_equals($_SESSION["csrf"], $_POST["csrf"] ?? "")) {

        $erro = "Requisição inválida. Atualize a página e tente novamente.";

    } elseif (
        $nome === "" ||
        $email === "" ||
        $cpf === "" ||
        $cep === "" ||
        $senha === "" ||
        $confirmar_senha === ""
    ) {

        $erro = "Preencha todos os campos.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $erro = "Digite um e-mail válido.";

    } elseif (!validarCpf($cpf)) {

        $erro = "Digite um CPF válido.";

    } elseif (cpfJaCadastrado($conn, $cpf)) {

        $erro = "Este CPF já está cadastrado.";

    } elseif (strlen($cep) !== 8) {

        $erro = "Informe um CEP válido com 8 números.";

    } elseif ($cidade === "" || !preg_match('/^[A-Z]{2}$/', $uf)) {

        $erro = "Consulte o CEP para preencher o endereço.";

    } elseif (strlen($senha) < 8) {

        $erro = "A senha deve possuir pelo menos 8 caracteres.";

    } elseif ($senha !== $confirmar_senha) {

        $erro = "As senhas não são iguais.";

    } elseif ($perfil !== "usuario" && $perfil !== "administrador") {

        $erro = "Perfil inválido.";

    } else {

        // Verificar se o e-mail já existe
        $stmt = mysqli_prepare(
            $conn,
            "SELECT id FROM usuarios WHERE email = ?"
        );

        if (!$stmt) {

            $erro = "Erro ao preparar a consulta.";

        } else {

            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);

            $existe = mysqli_stmt_num_rows($stmt) > 0;

            mysqli_stmt_close($stmt);

            if ($existe) {

                $erro = "Este e-mail já está cadastrado.";

            } else {

                // Criptografar a senha
                $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

                // Inserir usuário no banco
                $stmt = mysqli_prepare(
                    $conn,
                    "INSERT INTO usuarios
                        (nome, email, cpf, cep, logradouro, bairro, cidade, uf, senha, perfil)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                );

                if (!$stmt) {

                    $erro = "Erro ao preparar o cadastro.";

                } else {

                    mysqli_stmt_bind_param(
                        $stmt,
                        "ssssssssss",
                        $nome,
                        $email,
                        $cpf,
                        $cep,
                        $logradouro,
                        $bairro,
                        $cidade,
                        $uf,
                        $senha_hash,
                        $perfil
                    );

                    if (mysqli_stmt_execute($stmt)) {

                        mysqli_stmt_close($stmt);

                        $_SESSION["msg"] = "Usuário cadastrado com sucesso!";

                        header("Location: pagina_adm.php");
                        exit();

                    } else {

                        error_log(mysqli_stmt_error($stmt));

                        $erro = "Não foi possível cadastrar o usuário.";

                        mysqli_stmt_close($stmt);
                    }
                }
            }
        }
    }
}

function escaparCadastro($valor): string
{
    return htmlspecialchars(
        (string) $valor,
        ENT_QUOTES,
        "UTF-8"
    );
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Cadastrar usuário - Ferrorama</title>

    <link
        href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

    
<link rel="stylesheet" href="../../style/style.css?v=3">
</head>

<body class="page-public_usuarios_cadastro_usuario_php">

<header class="topbar">
    <div class="topbar-inner">

        <a href="../sistema.php" class="brand">
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

            <div>
                <div class="brand-name">Ferrorama</div>
                <div class="brand-subtitle">Sistema ferroviário</div>
            </div>
        </a>

        <div class="top-label">
            <div class="top-avatar">♙</div>
            Área administrativa
        </div>

    </div>
</header>

<section class="hero">
    <div class="hero-inner">

        <div class="eyebrow">Gestão de usuários</div>

        <h1>Cadastrar usuário</h1>

        <p>
            Crie uma nova conta e defina as permissões de acesso
            para utilizar o sistema Ferrorama.
        </p>

    </div>
</section>

<main class="main">

    <a href="pagina_adm.php" class="back-link">
        ← Voltar para usuários
    </a>

    <div class="layout">

        <section class="form-card">

            <div class="card-heading">
                <h2>Dados da conta</h2>
                <p>
                    Preencha as informações abaixo para registrar
                    um novo usuário.
                </p>
            </div>

            <?php if ($erro !== ""): ?>
                <div class="alert-error" role="alert">
                    <strong>Não foi possível concluir o cadastro.</strong>
                    <br>
                    <?= escaparCadastro($erro) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="cadastro_usuario.php">

                <input
                    type="hidden"
                    name="csrf"
                    value="<?= escaparCadastro($_SESSION["csrf"]) ?>"
                >

                <div class="form-grid">

                    <div class="field field-full">
                        <label for="nome">Nome completo</label>

                        <input
                            type="text"
                            id="nome"
                            name="nome"
                            maxlength="100"
                            placeholder="Ex.: João da Silva"
                            value="<?= escaparCadastro($nome) ?>"
                            autocomplete="name"
                            required
                        >
                    </div>

                    <div class="field field-full">
                        <label for="email">E-mail</label>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            maxlength="150"
                            placeholder="nome@exemplo.com"
                            value="<?= escaparCadastro($email) ?>"
                            autocomplete="email"
                            required
                        >

                        <p class="field-hint">
                            O e-mail será utilizado para identificar a conta.
                        </p>
                    </div>

                    <div class="field field-full">
                        <label for="cpf">CPF</label>

                        <input
                            type="text"
                            id="cpf"
                            name="cpf"
                            maxlength="14"
                            placeholder="000.000.000-00"
                            value="<?= escaparCadastro($cpf) ?>"
                            inputmode="numeric"
                            autocomplete="off"
                            required
                        >
                    </div>

                    <div class="field">
                        <label for="cep">CEP</label>

                        <input
                            type="text"
                            id="cep"
                            name="cep"
                            maxlength="9"
                            placeholder="Ex.: 89201-000"
                            value="<?= escaparCadastro($cep) ?>"
                            inputmode="numeric"
                            autocomplete="postal-code"
                            required
                        >

                        <p class="field-hint" id="cep-message">
                            O endereço é preenchido automaticamente.
                        </p>
                    </div>

                    <div class="field">
                        <label for="logradouro">Logradouro</label>

                        <input
                            type="text"
                            id="logradouro"
                            name="logradouro"
                            value="<?= escaparCadastro($logradouro) ?>"
                            readonly
                        >
                    </div>

                    <div class="field">
                        <label for="bairro">Bairro</label>

                        <input
                            type="text"
                            id="bairro"
                            name="bairro"
                            value="<?= escaparCadastro($bairro) ?>"
                            readonly
                        >
                    </div>

                    <div class="field">
                        <label for="cidade">Cidade</label>

                        <input
                            type="text"
                            id="cidade"
                            name="cidade"
                            value="<?= escaparCadastro($cidade) ?>"
                            readonly
                        >
                    </div>

                    <div class="field">
                        <label for="uf">Estado (UF)</label>

                        <input
                            type="text"
                            id="uf"
                            name="uf"
                            maxlength="2"
                            value="<?= escaparCadastro($uf) ?>"
                            readonly
                        >
                    </div>

                    <div class="field">
                        <label for="senha">Senha</label>

                        <div class="input-wrap">
                            <input
                                class="password-input"
                                type="password"
                                id="senha"
                                name="senha"
                                minlength="8"
                                placeholder="Mínimo de 8 caracteres"
                                autocomplete="new-password"
                                required
                            >

                            <button
                                class="password-toggle"
                                type="button"
                                data-target="senha"
                                aria-label="Mostrar senha"
                            >
                                Mostrar
                            </button>
                        </div>

                        <p class="field-hint">
                            Utilize pelo menos 8 caracteres.
                        </p>
                    </div>

                    <div class="field">
                        <label for="confirmar_senha">Confirmar senha</label>

                        <div class="input-wrap">
                            <input
                                class="password-input"
                                type="password"
                                id="confirmar_senha"
                                name="confirmar_senha"
                                minlength="8"
                                placeholder="Digite novamente"
                                autocomplete="new-password"
                                required
                            >

                            <button
                                class="password-toggle"
                                type="button"
                                data-target="confirmar_senha"
                                aria-label="Mostrar confirmação de senha"
                            >
                                Mostrar
                            </button>
                        </div>
                    </div>

                    <div class="field field-full">
                        <label for="perfil">Cargo e nível de acesso</label>

                        <select id="perfil" name="perfil" required>
                            <option
                                value="usuario"
                                <?= $perfil === "usuario" ? "selected" : "" ?>
                            >
                                Usuário comum
                            </option>

                            <option
                                value="administrador"
                                <?= $perfil === "administrador" ? "selected" : "" ?>
                            >
                                Administrador
                            </option>
                        </select>

                        <p class="field-hint">
                            Administradores podem acessar a área de gestão
                            de usuários e as funcionalidades administrativas.
                        </p>
                    </div>

                </div>

                <div class="form-actions">

                    <a
                        href="pagina_adm.php"
                        class="button button-secondary"
                    >
                        Voltar
                    </a>

                    <button type="submit" class="button button-primary">
                        + Cadastrar usuário
                    </button>

                </div>

            </form>

        </section>

    </div>

    <footer class="footer">
        Ferrorama · Sistema de gerenciamento ferroviário
    </footer>

</main>

<script>
    document.querySelectorAll(".password-toggle").forEach(function (botao) {
        botao.addEventListener("click", function () {
            const campo = document.getElementById(this.dataset.target);
            const mostrar = campo.type === "password";

            campo.type = mostrar ? "text" : "password";
            this.textContent = mostrar ? "Ocultar" : "Mostrar";
            this.setAttribute(
                "aria-label",
                mostrar ? "Ocultar senha" : "Mostrar senha"
            );
        });
    });
</script>

    <script>
        // Máscara e validação do CPF via API
        (function () {
            const campo = document.getElementById("cpf");

            campo.addEventListener("input", function () {
                let v = this.value.replace(/\D/g, "").slice(0, 11);
                v = v.replace(/(\d{3})(\d)/, "$1.$2")
                     .replace(/(\d{3})(\d)/, "$1.$2")
                     .replace(/(\d{3})(\d{1,2})$/, "$1-$2");
                this.value = v;
                this.setCustomValidity("");
            });

            campo.addEventListener("blur", function () {
                const cpf = this.value.replace(/\D/g, "");
                if (cpf.length === 0) return;

                fetch("cadastro_usuario.php?api=cpf&cpf=" + encodeURIComponent(cpf))
                    .then(function (r) { return r.json(); })
                    .then(function (d) {
                        campo.setCustomValidity(d.valido ? "" : (d.erro || "CPF inválido."));
                        if (!d.valido) campo.reportValidity();
                    })
                    .catch(function () { /* o servidor valida no envio */ });
            });
        })();
    </script>

<script>
    // CEP: máscara e preenchimento automático pela API (api/cep.php -> ViaCEP)
    (function () {
        const campoCep = document.getElementById("cep");
        const mensagem = document.getElementById("cep-message");
        const campos = ["logradouro", "bairro", "cidade", "uf"];

        function limparEndereco() {
            campos.forEach(function (id) {
                document.getElementById(id).value = "";
            });
        }

        function aviso(texto, erro) {
            mensagem.textContent = texto;
            mensagem.style.color = erro ? "#b52d3b" : "#167447";
        }

        campoCep.addEventListener("input", function () {
            let v = this.value.replace(/\D/g, "").slice(0, 8);

            if (v.length > 5) {
                v = v.substring(0, 5) + "-" + v.substring(5);
            }

            this.value = v;
        });

        async function consultarCep() {
            const cep = campoCep.value.replace(/\D/g, "");

            limparEndereco();

            if (cep.length !== 8) {
                aviso("Informe um CEP válido com 8 números.", true);
                return;
            }

            aviso("Consultando ViaCEP...", false);

            try {
                const resposta = await fetch(
                    "../api/cep.php?cep=" + encodeURIComponent(cep)
                );

                const dados = await resposta.json();

                if (!resposta.ok) {
                    throw new Error(dados.erro || "Erro na consulta.");
                }

                campos.forEach(function (id) {
                    document.getElementById(id).value = dados[id] || "";
                });

                aviso("Endereço encontrado com sucesso!", false);

            } catch (erro) {
                aviso(erro.message, true);
            }
        }

        campoCep.addEventListener("blur", consultarCep);
    })();
</script>

</body>
</html>