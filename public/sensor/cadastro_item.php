<?php

/*
|--------------------------------------------------------------------------
| Protege a página: somente usuários logados podem cadastrar sensores
|--------------------------------------------------------------------------
*/

require_once "../infra/auth.php";
require_once "../infra/connect.php";

$erro = '';
$sucesso = '';

/*
|--------------------------------------------------------------------------
| Processa o formulário somente quando for enviado
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /*
    |--------------------------------------------------------------------------
    | Recebe os dados do formulário
    |--------------------------------------------------------------------------
    */

    $nome = trim($_POST['nome'] ?? '');
    $localizacao = trim($_POST['localizacao'] ?? '');
    $tipo_dado = trim($_POST['tipo_dado'] ?? '');

    $trem_id = filter_input(
        INPUT_POST,
        'trem_id',
        FILTER_VALIDATE_INT
    );


    /*
    |--------------------------------------------------------------------------
    | Validação dos campos
    |--------------------------------------------------------------------------
    */

    if (
        $nome === '' ||
        $localizacao === '' ||
        $tipo_dado === ''
    ) {

        $erro = 'Preencha todos os campos.';

    } elseif (!$trem_id) {

        $erro = 'Selecione um trem válido.';

    } else {

        /*
        |--------------------------------------------------------------------------
        | Verifica se o trem existe
        |--------------------------------------------------------------------------
        */

        $sql = "SELECT id FROM trens WHERE id = ?";

        $stmt = mysqli_prepare($conn, $sql);

        if (!$stmt) {

            $erro = 'Não foi possível verificar o trem.';

        } else {

            mysqli_stmt_bind_param(
                $stmt,
                'i',
                $trem_id
            );

            if (!mysqli_stmt_execute($stmt)) {

                $erro = 'Não foi possível verificar o trem.';

            } else {

                $resultado = mysqli_stmt_get_result($stmt);

                if (mysqli_num_rows($resultado) === 0) {

                    $erro = 'O trem selecionado não existe.';
                }
            }

            mysqli_stmt_close($stmt);
        }


        /*
        |--------------------------------------------------------------------------
        | Cadastra o sensor
        |--------------------------------------------------------------------------
        */

        if ($erro === '') {

            $sql = "INSERT INTO sensores
                    (nome, localizacao, tipo_dado, trem_id)
                    VALUES (?, ?, ?, ?)";

            $stmt = mysqli_prepare($conn, $sql);

            if (!$stmt) {

                $erro = 'Não foi possível preparar o cadastro.';

            } else {

                mysqli_stmt_bind_param(
                    $stmt,
                    'sssi',
                    $nome,
                    $localizacao,
                    $tipo_dado,
                    $trem_id
                );

                if (mysqli_stmt_execute($stmt)) {

                    $sucesso = 'Sensor cadastrado com sucesso!';

                } else {

                    $erro = 'Não foi possível cadastrar o sensor.';
                }

                mysqli_stmt_close($stmt);
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| Busca os trens cadastrados para aparecer no formulário
|--------------------------------------------------------------------------
*/

$trens = mysqli_query(
    $conn,
    "SELECT id, identificador, modelo
     FROM trens
     ORDER BY identificador"
);

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Cadastro de Sensor - Ferrorama</title>

<style>

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: Arial, sans-serif;
}

body {
    background-color: #ececec;
}

/* Cabeçalho */

header {
    background: #0f172a;
    height: 200px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}

header h1 {
    font-size: 40px;
}

/* Conteúdo */

.container {
    display: flex;
    justify-content: space-around;
    align-items: flex-start;
    padding: 60px;
}

/* Menu */

.menu {
    display: flex;
    flex-direction: column;
    gap: 45px;
}

.item-btn {
    width: 240px;
    height: 95px;
    border: 3px solid black;
    border-radius: 22px;
    background: white;
    font-size: 22px;
    cursor: pointer;
}

.item-btn:hover {
    background: #dcdcdc;
}

/* Formulário */

.formulario {
    display: flex;
    flex-direction: column;
    gap: 18px;
    align-items: flex-end;
}

.campo {
    display: flex;
    align-items: center;
    gap: 15px;
}

label {
    font-size: 22px;
    text-align: right;
}

input,
select {
    width: 250px;
    height: 40px;
    font-size: 18px;
    padding: 5px;
    border: 1px solid #777;
}

.btn-confirmar {
    margin-top: 30px;
    align-self: center;
    width: 250px;
    height: 65px;
    border: 3px solid black;
    border-radius: 25px;
    background: white;
    font-size: 22px;
    cursor: pointer;
}

.btn-confirmar:hover {
    background: #dcdcdc;
}

/* Mensagens */

.mensagem-erro {
    color: #b91c1c;
    font-size: 18px;
    margin-bottom: 10px;
    text-align: center;
}

.mensagem-sucesso {
    color: #15803d;
    font-size: 18px;
    margin-bottom: 10px;
    text-align: center;
}

</style>

</head>

<body>

<header>

    <h1>Cadastre seu sensor</h1>

</header>

<div class="container">

    <div class="menu">

        <button
            type="button"
            class="item-btn"
            onclick="window.location.href='sensor.php'"
        >
            Sensor
        </button>

    </div>

    <form
        class="formulario"
        method="POST"
    >

        <?php if ($erro !== ''): ?>

            <p class="mensagem-erro">
                <?= htmlspecialchars($erro) ?>
            </p>

        <?php endif; ?>


        <?php if ($sucesso !== ''): ?>

            <p class="mensagem-sucesso">
                <?= htmlspecialchars($sucesso) ?>
            </p>

        <?php endif; ?>


        <div class="campo">

            <label for="nome">
                Dê um nome:
            </label>

            <input
                type="text"
                id="nome"
                name="nome"
                value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>"
                required
            >

        </div>


        <div class="campo">

            <label for="localizacao">
                Localização:
            </label>

            <input
                type="text"
                id="localizacao"
                name="localizacao"
                value="<?= htmlspecialchars($_POST['localizacao'] ?? '') ?>"
                required
            >

        </div>


        <div class="campo">

            <label for="tipo_dado">
                Tipo de dado:
            </label>

            <input
                type="text"
                id="tipo_dado"
                name="tipo_dado"
                placeholder="Ex: Temperatura"
                value="<?= htmlspecialchars($_POST['tipo_dado'] ?? '') ?>"
                required
            >

        </div>


        <div class="campo">

            <label for="trem_id">
                Trem:
            </label>

            <select
                id="trem_id"
                name="trem_id"
                required
            >

                <option value="">
                    Selecione um trem
                </option>

                <?php while ($trem = mysqli_fetch_assoc($trens)): ?>

                    <option
                        value="<?= $trem['id'] ?>"
                        <?= (
                            isset($_POST['trem_id']) &&
                            $_POST['trem_id'] == $trem['id']
                        ) ? 'selected' : ''
                        ?>
                    >

                        <?= htmlspecialchars($trem['identificador']) ?>
                        -
                        <?= htmlspecialchars($trem['modelo']) ?>

                    </option>

                <?php endwhile; ?>

            </select>

        </div>


        <button
            type="submit"
            class="btn-confirmar"
        >
            Cadastrar
        </button>

    </form>

</div>

</body>

</html>