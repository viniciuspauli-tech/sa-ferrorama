<?php

require_once "../../infra/connect.php";

$mensagemErro = "";
$mensagemSucesso = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $identificador = trim($_POST["identificador"] ?? "");
    $modelo = trim($_POST["modelo"] ?? "");
    $status = $_POST["status"] ?? "ativo";
    $velocidade = (float)($_POST["velocidade_atual"] ?? 0);
    $localizacao = trim($_POST["localizacao_atual"] ?? "");
    $consumo = (float)($_POST["consumo_energia"] ?? 0);

    if (empty($identificador) || empty($modelo)) {
        $mensagemErro = "Por favor, preencha o Identificador e o Modelo do trem.";
    } else {
        $sql = "INSERT INTO trens (identificador, modelo, status, velocidade_atual, localizacao_atual, consumo_energia) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("sssdsd", $identificador, $modelo, $status, $velocidade, $localizacao, $consumo);
            
            if ($stmt->execute()) {
                header("Location: listagemtrem.php");
                exit;
            } else {
                $mensagemErro = "Erro ao cadastrar trem: " . $conn->error;
            }
            $stmt->close();
        } else {
            $mensagemErro = "Erro na preparação da consulta: " . $conn->error;
        }
    }
}

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Trem - Ferrorama</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container py-4" style="max-width: 600px;">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Cadastrar Trem</h1>
        <a href="listagemtrem.php" class="btn btn-secondary">
            Voltar
        </a>
    </div>

    <?php if (!empty($mensagemErro)): ?>
        <div class="alert alert-danger" role="alert">
            <?= htmlspecialchars($mensagemErro) ?>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="cadastrotrem.php">
                <div class="mb-3">
                    <label for="identificador" class="form-label">Identificador (Ex: TR-005)</label>
                    <input type="text" class="form-control" id="identificador" name="identificador" required>
                </div>

                <div class="mb-3">
                    <label for="modelo" class="form-label">Modelo</label>
                    <input type="text" class="form-control" id="modelo" name="modelo" required>
                </div>

                <div class="mb-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="ativo">Ativo</option>
                        <option value="inativo">Inativo</option>
                        <option value="manutencao">Manutenção</option>
                        <option value="falha">Falha</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="velocidade_atual" class="form-label">Velocidade Atual (km/h)</label>
                    <input type="number" step="0.01" class="form-control" id="velocidade_atual" name="velocidade_atual" value="0.00">
                </div>

                <div class="mb-3">
                    <label for="localizacao_atual" class="form-label">Localização Atual</label>
                    <input type="text" class="form-control" id="localizacao_atual" name="localizacao_atual" placeholder="Ex: KM 12 - Trecho Norte">
                </div>

                <div class="mb-3">
                    <label for="consumo_energia" class="form-label">Consumo de Energia (kWh)</label>
                    <input type="number" step="0.01" class="form-control" id="consumo_energia" name="consumo_energia" value="0.00">
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="listagemtrem.php" class="btn btn-outline-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Salvar Trem</button>
                </div>
            </form>
        </div>
    </div>

</div>

</body>
</html>