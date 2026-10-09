```php
<?php

require_once __DIR__ . "/../infra/auth.php";

$ehAdministrador =
    ($_SESSION["usuario_perfil"] ?? "") === "administrador";

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Ferrorama - Selecione uma categoria</title>

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

        header {
            background: #0f172a;
            min-height: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px;
            text-align: center;
        }

        header h1 {
            color: white;
            font-size: 40px;
            font-weight: bold;
        }

        .container {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 60px;
            padding: 80px 60px;
            flex-wrap: wrap;
        }

        .card-btn {
            width: 240px;
            min-height: 95px;
            padding: 15px;
            border: 3px solid black;
            border-radius: 22px;
            background: white;
            font-size: 22px;
            cursor: pointer;
            text-decoration: none;
            color: black;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            font-weight: 500;
            transition: background 0.2s;
        }

        .card-btn:hover {
            background: #dcdcdc;
        }

        @media (max-width: 600px) {
            header h1 {
                font-size: 30px;
            }

            .container {
                padding: 40px 20px;
                gap: 25px;
            }

            .card-btn {
                width: 100%;
                max-width: 320px;
            }
        }
    </style>
</head>

<body>

    <header>
        <h1>Selecione uma categoria</h1>
    </header>

    <div class="container">

        <!-- Disponível para todos os usuários autorizados -->
        <a
            class="card-btn"
            href="sensor.php"
        >
            Trens e Sensores
        </a>

        <!-- Consulta de trens disponível para todos -->
        <a
            class="card-btn"
            href="trem/listagemtrem.php"
        >
            Consultar Trens
        </a>

        <?php if ($ehAdministrador): ?>

            <!-- Somente administrador -->
            <a
                class="card-btn"
                href="rotas/rotas.php"
            >
                Gerenciar Rotas
            </a>

            <a
                class="card-btn"
                href="cadastro_item.php"
            >
                Cadastrar Sensor
            </a>

            <a
                class="card-btn"
                href="trem/cadastrotrem.php"
            >
                Cadastrar Trem
            </a>

            <a
                class="card-btn"
                href="usuarios/pagina_adm.php"
            >
                Usuários
            </a>

        <?php endif; ?>

        <a
            class="card-btn"
            href="logout.php"
        >
            Sair
        </a>

    </div>

</body>
</html>
```
