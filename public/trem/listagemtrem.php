<?php
require_once __DIR__ . "/../../infra/auth.php";
require_once __DIR__ . "/../../infra/connect.php";

$ehAdministrador = ($_SESSION["usuario_perfil"] ?? "") === "administrador";
$busca = trim($_GET["busca"] ?? "");
$statusFiltro = $_GET["status"] ?? "";

$statusValidos = ["ativo", "inativo", "manutencao", "falha"];
if (!in_array($statusFiltro, $statusValidos, true)) {
    $statusFiltro = "";
}

// Busca os trens e o nome do administrador responsável
$sql = "SELECT trens.*, usuarios.nome AS responsavel_nome
        FROM trens
        LEFT JOIN usuarios ON usuarios.id = trens.responsavel_id
        WHERE 1=1";
$tipos = "";
$parametros = [];

if ($busca !== "") {
    $sql .= " AND (trens.identificador LIKE ? OR trens.modelo LIKE ? OR trens.localizacao_atual LIKE ?)";
    $like = "%$busca%";
    $tipos .= "sss";
    $parametros[] = $like;
    $parametros[] = $like;
    $parametros[] = $like;
}

if ($statusFiltro !== "") {
    $sql .= " AND trens.status = ?";
    $tipos .= "s";
    $parametros[] = $statusFiltro;
}

$sql .= " ORDER BY trens.identificador";
$stmt = $conn->prepare($sql);

if ($tipos !== "") {
    $stmt->bind_param($tipos, ...$parametros);
}

$stmt->execute();
$trens = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

function escapar($valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, "UTF-8");
}

function corStatusTrem($status): string
{
    return $status;
}

function nomeStatusTrem($status): string
{
    $nomes = [
        "ativo" => "Ativo",
        "inativo" => "Inativo",
        "manutencao" => "Manutenção",
        "falha" => "Falha"
    ];
    return $nomes[$status] ?? "Desconhecido";
}

$totalTrens = count($trens);
$totalAtivos = 0;
$totalManutencao = 0;
$totalFalhas = 0;

foreach ($trens as $trem) {
    if ($trem["status"] === "ativo") $totalAtivos++;
    if ($trem["status"] === "manutencao") $totalManutencao++;
    if ($trem["status"] === "falha") $totalFalhas++;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Trens - Ferrorama</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >