
<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . "/../infra/auth.php";
require_once __DIR__ . "/../infra/connect.php";

if (($_SESSION["usuario_perfil"] ?? "") !== "administrador") {
    http_response_code(403);
    exit("Acesso permitido apenas para administradores.");
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    exit("Método não permitido.");
}

if (
    empty($_SESSION["csrf"]) ||
    !hash_equals($_SESSION["csrf"], $_POST["csrf"] ?? "")
) {
    http_response_code(403);
    exit("Requisição inválida. Atualize a página e tente novamente.");
}

$id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    header("Location: listar_sensores.php?erro=nao_encontrado");
    exit;
}

$stmt = $conn->prepare("DELETE FROM sensores WHERE id = ?");

if (!$stmt) {
    error_log($conn->error);
    http_response_code(500);
    exit("Não foi possível preparar a exclusão.");
}

$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    $excluido = $stmt->affected_rows > 0;
    $stmt->close();

    if ($excluido) {
        header("Location: listar_sensores.php?sucesso=excluido");
    } else {
        header("Location: listar_sensores.php?erro=nao_encontrado");
    }

    exit;
}

$codigoErro = $stmt->errno;
error_log($stmt->error);
$stmt->close();

// O sensor pode possuir leituras no histórico.
if ($codigoErro === 1451) {
    header("Location: listar_sensores.php?erro=dependencias");
    exit;
}

http_response_code(500);
exit("Não foi possível excluir o sensor.");
