
<?php

require_once __DIR__ . "/../infra/auth.php";
require_once __DIR__ . "/../infra/connect.php";

$isAdmin = ($_SESSION["usuario_perfil"] ?? "") === "administrador";

// Inicializar parâmetros dos filtros
$statusFiltro = trim($_GET["status"] ?? "");
$busca = trim($_GET["busca"] ?? "");

$statusValidos = ["ativo", "inativo", "manutencao", "falha"];

if (!in_array($statusFiltro, $statusValidos, true)) {
    $statusFiltro = "";
}

$tipos = "";
$parametros = [];

// Consulta de trens com o usuário responsável
$sql = "
    SELECT
        t.id,
        t.identificador,
        t.modelo,
        t.status,
        t.velocidade_atual,
        t.localizacao_atual,
        t.consumo_energia,
        t.atualizado_em,
        t.responsavel_id,
        u.nome AS responsavel_nome,
        u.email AS responsavel_email
    FROM trens AS t
    LEFT JOIN usuarios AS u
        ON u.id = t.responsavel_id
    WHERE 1 = 1
";

// Filtro por status
if ($statusFiltro !== "") {
    $sql .= " AND t.status = ?";
    $tipos .= "s";
    $parametros[] = $statusFiltro;
}

// Busca por identificador, modelo ou localização
if ($busca !== "") {
    $sql .= "
        AND (
            t.identificador LIKE ?
            OR t.modelo LIKE ?
            OR t.localizacao_atual LIKE ?
        )
    ";

    $tipos .= "sss";

    $buscaLike = "%" . $busca . "%";

    $parametros[] = $buscaLike;
    $parametros[] = $buscaLike;
    $parametros[] = $buscaLike;
}

$sql .= " ORDER BY t.identificador ASC";

// Preparar e executar consulta
$stmt = $conn->prepare($sql);

if (!$stmt) {
    error_log($conn->error);
    http_response_code(500);
    exit("Não foi possível preparar a consulta dos trens.");
}

if (!empty($parametros)) {
    $stmt->bind_param($tipos, ...$parametros);
}

if (!$stmt->execute()) {
    error_log($stmt->error);
    http_response_code(500);
    exit("Não foi possível carregar os trens.");
}

$resultado = $stmt->get_result();

$trens = [];

while ($trem = $resultado->fetch_assoc()) {
    $trens[] = $trem;
}

$stmt->close();

// Escapar valores apresentados no HTML
function h($valor): string
{
    return htmlspecialchars(
        (string) ($valor ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}

// Definir a cor do status
function badgeStatus(string $status): string
{
    return match ($status) {
        "ativo" => "success",
        "manutencao" => "warning",
        "falha" => "danger",
        default => "secondary"
    };
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Ferrorama - Listagem de Trens</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
</head>

<body class="bg-light">

<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <h1 class="h3 mb-0">Trens cadastrados</h1>

        <?php if ($isAdmin): ?>
            <a href="trem_form.php" class="btn btn-primary">
                Novo trem
            </a>
        <?php endif; ?>
    </div>

    <!-- Mensagens de resultado -->
    <?php if (($_GET["sucesso"] ?? "") === "excluido"): ?>
        <div class="alert alert-success">
            Trem excluído com sucesso.
        </div>
    <?php endif; ?>

    <?php if (($_GET["erro"] ?? "") === "dependencias"): ?>
        <div class="alert alert-danger">
            Não foi possível excluir o trem porque existem dados relacionados.
        </div>
    <?php endif; ?>

    <?php if (($_GET["erro"] ?? "") === "nao_encontrado"): ?>
        <div class="alert alert-warning">
            Trem não encontrado ou já excluído.
        </div>
    <?php endif; ?>

    <!-- Filtros -->
    <form method="get" class="row g-2 mb-4">

        <div class="col-md-5">
            <input
                type="text"
                name="busca"
                class="form-control"
                placeholder="Buscar por identificador, modelo ou localização"
                value="<?= h($busca) ?>"
            >
        </div>

        <div class="col-md-3">
            <select name="status" class="form-select">
                <option value="">Todos os status</option>

                <option value="ativo"
                    <?= $statusFiltro === "ativo" ? "selected" : "" ?>>
                    Ativo
                </option>

                <option value="inativo"
                    <?= $statusFiltro === "inativo" ? "selected" : "" ?>>
                    Inativo
                </option>

                <option value="manutencao"
                    <?= $statusFiltro === "manutencao" ? "selected" : "" ?>>
                    Manutenção
                </option>

                <option value="falha"
                    <?= $statusFiltro === "falha" ? "selected" : "" ?>>
                    Falha
                </option>
            </select>
        </div>

        <div class="col-md-2">
            <button type="submit" class="btn btn-outline-secondary w-100">
                Filtrar
            </button>
        </div>

        <div class="col-md-2">
            <a href="trens.php" class="btn btn-outline-dark w-100">
                Limpar
            </a>
        </div>

    </form>

    <!-- Listagem -->
    <?php if (empty($trens)): ?>

        <div class="alert alert-info">
            Nenhum trem encontrado com os filtros aplicados.
        </div>

    <?php else: ?>

        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle bg-white">

                <thead class="table-dark">
                    <tr>
                        <th>Identificador</th>
                        <th>Modelo</th>
                        <th>Status</th>
                        <th>Velocidade (km/h)</th>
                        <th>Localização</th>
                        <th>Consumo (kWh)</th>
                        <th>Responsável</th>
                        <th>Atualizado em</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>

                <tbody>
                <?php foreach ($trens as $trem): ?>

                    <tr>
                        <td><?= h($trem["identificador"]) ?></td>

                        <td><?= h($trem["modelo"]) ?></td>

                        <td>
                            <span class="badge bg-<?= badgeStatus($trem["status"]) ?>">
                                <?= h(ucfirst($trem["status"])) ?>
                            </span>
                        </td>

                        <td>
                            <?= number_format(
                                (float) $trem["velocidade_atual"],
                                2,
                                ",",
                                "."
                            ) ?>
                        </td>

                        <td><?= h($trem["localizacao_atual"] ?? "—") ?></td>

                        <td>
                            <?= number_format(
                                (float) ($trem["consumo_energia"] ?? 0),
                                2,
                                ",",
                                "."
                            ) ?>
                        </td>

                        <td>
                            <?php if (!empty($trem["responsavel_nome"])): ?>
                                <?= h($trem["responsavel_nome"]) ?>
                                <br>
                                <small class="text-muted">
                                    <?= h($trem["responsavel_email"]) ?>
                                </small>
                            <?php else: ?>
                                <span class="text-muted">Sem responsável</span>
                            <?php endif; ?>
                        </td>

                        <td>
                            <?php if (!empty($trem["atualizado_em"])): ?>
                                <?= h(
                                    date(
                                        "d/m/Y H:i",
                                        strtotime($trem["atualizado_em"])
                                    )
                                ) ?>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>

                        <td class="text-end">
                            <div class="d-flex justify-content-end flex-wrap gap-1">

                                <?php if ($isAdmin): ?>

                                    <a
                                        href="trem_form.php?id=<?= (int) $trem["id"] ?>"
                                        class="btn btn-sm btn-outline-primary"
                                    >
                                        Editar
                                    </a>

                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-danger"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalExcluir"
                                        data-id="<?= (int) $trem["id"] ?>"
                                        data-identificador="<?= h($trem["identificador"]) ?>"
                                    >
                                        Excluir
                                    </button>

                                <?php endif; ?>

                            </div>
                        </td>
                    </tr>

                <?php endforeach; ?>
                </tbody>

            </table>
        </div>

    <?php endif; ?>

</div>

<?php if ($isAdmin): ?>

    <!-- Modal de confirmação de exclusão -->
    <div
        class="modal fade"
        id="modalExcluir"
        tabindex="-1"
        aria-labelledby="tituloModalExcluir"
        aria-hidden="true"
    >
        <div class="modal-dialog">

            <form method="post" action="trem_excluir.php" class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title" id="tituloModalExcluir">
                        Confirmar exclusão
                    </h5>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Fechar"
                    ></button>
                </div>

                <div class="modal-body">
                    Tem certeza que deseja excluir o trem
                    <strong id="modalIdentificador"></strong>?

                    <p class="text-danger mb-0 mt-2">
                        Essa ação não pode ser desfeita.
                    </p>
                </div>

                <div class="modal-footer">
                    <input type="hidden" name="id" id="modalId">

                    <button
                        type="button"
                        class="btn btn-secondary"
                        data-bs-dismiss="modal"
                    >
                        Cancelar
                    </button>

                    <button type="submit" class="btn btn-danger">
                        Excluir
                    </button>
                </div>

            </form>

        </div>
    </div>

<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<?php if ($isAdmin): ?>
<script>
document.getElementById("modalExcluir").addEventListener(
    "show.bs.modal",
    function (event) {
        const button = event.relatedTarget;

        document.getElementById("modalId").value =
            button.getAttribute("data-id");

        document.getElementById("modalIdentificador").textContent =
            button.getAttribute("data-identificador");
    }
);
</script>
<?php endif; ?>

</body>
</html>