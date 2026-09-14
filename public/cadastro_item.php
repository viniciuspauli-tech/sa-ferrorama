<?php

require_once "../infra/connect.php";

$erro = '';
$sucesso = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $localizacao = trim($_POST['localizacao'] ?? '');
    $dataCriacao = trim($_POST['data_criacao'] ?? '');

    if ($nome === '' || $localizacao === '' || $dataCriacao === '') {
        $erro = 'Preencha todos os campos.';
    } else {
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO sensores (nome, localizacao, data_criacao) VALUES (:nome, :localizacao, :data_criacao)'
            );
            $stmt->execute([
                ':nome' => $nome,
                ':localizacao' => $localizacao,
                ':data_criacao' => $dataCriacao,
            ]);

            header('Location: sistema.php');
            exit;
        } catch (PDOException $e) {
            $erro = 'Erro ao cadastrar sensor: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cadastro de Itens</title>

<style>
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family: Arial, sans-serif;
}

body{
    background-color:#ececec;
}

/* Cabeçalho */
header{
    background:#0f172a;
    height:200px;
    display:flex;
    align-items:center;
    justify-content:center;
    position:relative;
    color: white;
}

.logo{
    position:absolute;
    left:20px;
    text-align:center;
}

.logo img{
    width:110px;
}

.logo h3{
    color:rgb(248, 243, 243);
    margin-top:5px;
    font-size:40px;
}

header h1{
    color:white;
    font-size:40px;
}

/* Conteúdo */
.container{
    display:flex;
    justify-content:space-around;
    padding:60px;
}

/* Botões da esquerda */
.menu{
    display:flex;
    flex-direction:column;
    gap:45px;
}

.item-btn{
    width:240px;
    height:95px;
    border:3px solid black;
    border-radius:22px;
    background:white;
    font-size:22px;
    cursor:pointer;
}

.item-btn:hover{
    background:#dcdcdc;
}

/* Formulário */
.formulario{
    display:flex;
    flex-direction:column;
    gap:18px;
    align-items:flex-end;
}

.campo{
    display:flex;
    align-items:center;
    gap:15px;
}

label{
    font-size:22px;
    text-align:right;
}

input{
    width:220px;
    height:40px;
    font-size:18px;
    padding:5px;
}

.btn-confirmar{
    margin-top:30px;
    align-self:center;
    width:250px;
    height:65px;
    border:3px solid black;
    border-radius:25px;
    background:white;
    font-size:22px;
    cursor:pointer;
}

.btn-confirmar:hover{
    background:#dcdcdc;
}

.mensagem-erro{
    color:#b91c1c;
    font-size:18px;
    margin-bottom:10px;
    text-align:center;
}
</style>
</head>
<body>

<header>
    <div class="logo">

        <h3></h3>
    </div>

    <h1>Cadastre seu sensor</h1>
</header>

<div class="container">

    <div class="menu">

        <button type="button" class="item-btn">Sensor</button>

    </div>

    <form class="formulario" method="POST" action="cadastro.php">

        <?php if ($erro !== ''): ?>
            <p class="mensagem-erro"><?= htmlspecialchars($erro) ?></p>
        <?php endif; ?>

        <div class="campo">
            <label for="nome">De um nome:</label>
            <input type="text" id="nome" name="nome" required>
        </div>

        <div class="campo">
            <label for="localizacao">Localização:</label>
            <input type="text" id="localizacao" name="localizacao" required>
        </div>

        <div class="campo">
            <label for="data_criacao">Data de Criação:</label>
            <input type="date" id="data_criacao" name="data_criacao" required>
        </div>

        <button type="submit" class="btn-confirmar">Cadastrar</button>

    </form>

</div>

</body>
</html>