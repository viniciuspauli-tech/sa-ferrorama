<?php
require_once "../infra/connect.php";

$statusValidos = ["ativa", "inativa", "manutencao"];
$erros = [];

// Excluir rota
if (($_POST["acao"] ?? "") === "excluir") {
    $idExcluir = (int)($_POST["id"] ?? 0);

    $stmt = $conn->prepare("DELETE FROM rotas WHERE id = ?");
    $stmt->bind_param("i", $idExcluir);
    $stmt->execute();

    header("Location: rotas.php?sucesso=" . urlencode("Rota excluída com sucesso!"));
    exit;
}

// ID da rota (via link ?id= ou via formulário)
$id = (int)($_GET["id"] ?? $_POST["id"] ?? 0);

if ($id <= 0) {
    header("Location: rotas.php?erro=" . urlencode("Rota não informada."));
    exit;
}

// Salvar alterações
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $rota = [
        "id" => $id,
        "nome" => trim($_POST["nome"] ?? ""),
        "origem" => trim($_POST["origem"] ?? ""),
        "destino" => trim($_POST["destino"] ?? ""),
        "distancia_km" => trim($_POST["distancia_km"] ?? ""),
        "tempo_estimado" => trim($_POST["tempo_estimado"] ?? ""),
        "status" => $_POST["status"] ?? "ativa",
    ];

    if ($rota["nome"] === "") $erros[] = "Informe o nome da rota.";
    if ($rota["origem"] === "") $erros[] = "Informe a origem.";
    if ($rota["destino"] === "") $erros[] = "Informe o destino.";
    if (!is_numeric($rota["distancia_km"]) || $rota["distancia_km"] <= 0) $erros[] = "Informe uma distância válida.";
    if ($rota["tempo_estimado"] === "") $erros[] = "Informe o tempo estimado.";
    if (!in_array($rota["status"], $statusValidos)) $erros[] = "Status inválido.";

    if (empty($erros)) {
        $sql = "UPDATE rotas SET nome=?, origem=?, destino=?, distancia_km=?, tempo_estimado=?, status=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $distancia = (float)$rota["distancia_km"];

        $stmt->bind_param(
            "sssdssi",
            $rota["nome"],
            $rota["origem"],
            $rota["destino"],
            $distancia,
            $rota["tempo_estimado"],
            $rota["status"],
            $id
        );

        if ($stmt->execute()) {
            header("Location: rotas.php?sucesso=" . urlencode("Rota atualizada com sucesso!"));
            exit;
        }

        $erros[] = "Erro ao atualizar rota: " . $stmt->error;
    }
} else {
    // Carrega dados atuais da rota
    $stmt = $conn->prepare("SELECT * FROM rotas WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $rota = $stmt->get_result()->fetch_assoc();

    if (!$rota) {
        header("Location: rotas.php?erro=" . urlencode("Rota não encontrada."));
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Rota - Ferrorama</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Editar Rota</h1>
                <a href="rotas.php" class="btn btn-secondary">Voltar</a>
            </div>

            <?php if (!empty($erros)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($erros as $erro): ?>
                            <li><?= htmlspecialchars($erro) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <form method="POST" action="editrotas.php">
                        <input type="hidden" name="id" value="<?= (int)$rota["id"] ?>">

                        <div class="mb-3">
                            <label class="form-label">Nome da rota</label>
                            <input type="text" name="nome" class="form-control" maxlength="100"
                                   value="<?= htmlspecialchars($rota["nome"]) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Origem</label>
                            <input type="text" name="origem" class="form-control" maxlength="150"
                                   value="<?= htmlspecialchars($rota["origem"]) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Destino</label>
                            <input type="text" name="destino" class="form-control" maxlength="150"
                                   value="<?= htmlspecialchars($rota["destino"]) ?>" required>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Distância (km)</label>
                                <input type="number" name="distancia_km" class="form-control" step="0.01" min="0.01"
                                       value="<?= htmlspecialchars($rota["distancia_km"]) ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tempo estimado</label>
                                <input type="text" name="tempo_estimado" class="form-control" placeholder="Ex.: 02:30:00"
                                       maxlength="20" value="<?= htmlspecialchars($rota["tempo_estimado"]) ?>" required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <?php foreach ($statusValidos as $opcao): ?>
                                    <option value="<?= $opcao ?>" <?= $rota["status"] === $opcao ? "selected" : "" ?>>
                                        <?= ucfirst($opcao) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary w-100">Salvar alterações</button>
                            <a href="rotas.php" class="btn btn-outline-secondary w-100">Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

</body>
</html>