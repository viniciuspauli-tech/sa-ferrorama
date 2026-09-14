<?php
require_once __DIR__ . '/config/db.php';

// Filtro simples por status e busca por identificador/modelo
$statusFiltro = $_GET['status'] ?? '';
$busca        = trim($_GET['busca'] ?? '');

$sql = 'SELECT id, identificador, modelo, status, velocidade_atual, localizacao_atual, consumo_energia, atualizado_em
        FROM trens
        WHERE 1=1';
$params = [];

if ($statusFiltro !== '') {
    $sql .= ' AND status = :status';
    $params[':status'] = $statusFiltro;
}

if ($busca !== '') {
    $sql .= ' AND (identificador LIKE :busca OR modelo LIKE :busca)';
    $params[':busca'] = "%{$busca}%";
}

$sql .= ' ORDER BY identificador ASC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$trens = $stmt->fetchAll();

// Mapeia o status para uma cor de badge do Bootstrap

function badgeStatus(string $status): string
{
    return match ($status) {
        'ativo'       => 'success',
        'manutencao'  => 'warning',
        'falha'       => 'danger',
        default       => 'secondary', // inativo
    };
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ferrorama - Listagem de Trens</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Trens cadastrados</h1>
        <a href="trem_form.php" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Novo trem
        </a>
    </div>

    <!-- Filtros -->
    <form method="get" class="row g-2 mb-4">
        <div class="col-md-4">
            <input
                type="text"
                name="busca"
                class="form-control"
                placeholder="Buscar por identificador ou modelo"
                value="<?= htmlspecialchars($busca) ?>"
            >
        </div>
        <div class="col-md-3">
            <select name="status" class="form-select">
                <option value="">Todos os status</option>
                <option value="ativo" <?= $statusFiltro === 'ativo' ? 'selected' : '' ?>>Ativo</option>
                <option value="inativo" <?= $statusFiltro === 'inativo' ? 'selected' : '' ?>>Inativo</option>
                <option value="manutencao" <?= $statusFiltro === 'manutencao' ? 'selected' : '' ?>>Manutenção</option>
                <option value="falha" <?= $statusFiltro === 'falha' ? 'selected' : '' ?>>Falha</option>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-outline-secondary w-100">Filtrar</button>
        </div>
    </form>

    <?php if (empty($trens)): ?>
        <div class="alert alert-info">Nenhum trem encontrado com os filtros aplicados.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped align-middle bg-white">
                <thead class="table-dark">
                    <tr>
                        <th>Identificador</th>
                        <th>Modelo</th>
                        <th>Status</th>
                        <th>Velocidade (km/h)</th>
                        <th>Localização</th>
                        <th>Consumo (kWh)</th>
                        <th>Atualizado em</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($trens as $trem): ?>
                        <tr>
                            <td><?= htmlspecialchars($trem['identificador']) ?></td>
                            <td><?= htmlspecialchars($trem['modelo']) ?></td>
                            <td>
                                <span class="badge bg-<?= badgeStatus($trem['status']) ?>">
                                    <?= htmlspecialchars(ucfirst($trem['status'])) ?>
                                </span>
                            </td>
                            <td><?= number_format((float) $trem['velocidade_atual'], 2, ',', '.') ?></td>
                            <td><?= htmlspecialchars($trem['localizacao_atual'] ?? '—') ?></td>
                            <td><?= number_format((float) $trem['consumo_energia'], 2, ',', '.') ?></td>
                            <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($trem['atualizado_em']))) ?></td>
                            <td class="text-end">
                                <a href="trem_detalhe.php?id=<?= (int) $trem['id'] ?>"
                                   class="btn btn-sm btn-outline-secondary" title="Ver detalhes">
                                    Ver
                                </a>
                                <a href="trem_form.php?id=<?= (int) $trem['id'] ?>"
                                   class="btn btn-sm btn-outline-primary" title="Editar">
                                    Editar
                                </a>
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-danger"
                                    title="Excluir"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modalExcluir"
                                    data-id="<?= (int) $trem['id'] ?>"
                                    data-identificador="<?= htmlspecialchars($trem['identificador']) ?>"
                                >
                                    Excluir
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

</div>

<!-- Modal de confirmação de exclusão -->
<div class="modal fade" id="modalExcluir" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="post" action="trem_excluir.php" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar exclusão</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Tem certeza que deseja excluir o trem <strong id="modalIdentificador"></strong>?
                Essa ação não pode ser desfeita.
            </div>
            <div class="modal-footer">
                <input type="hidden" name="id" id="modalId" value="">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-danger">Excluir</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Preenche o modal de exclusão com os dados do trem clicado
    document.getElementById('modalExcluir').addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        document.getElementById('modalId').value = button.getAttribute('data-id');
        document.getElementById('modalIdentificador').textContent = button.getAttribute('data-identificador');
    });
</script>

</body>
</html>