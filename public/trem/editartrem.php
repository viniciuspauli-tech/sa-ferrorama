<?php
require_once __DIR__ . "/../../infra/adm.php";
require_once __DIR__ . "/../../infra/connect.php";

function escapar($valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, "UTF-8");
}

$mensagemErro = "";
$id = (int) ($_GET["id"] ?? 0);

if ($id <= 0) {
    header("Location: listagemtrem.php");
    exit;
}

// Busca o trem
$stmt = $conn->prepare("SELECT * FROM trens WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$trem = $stmt->get_result()->fetch_assoc();

if (!$trem) {
    header("Location: listagemtrem.php?erro=nao_encontrado");
    exit;
}

// Busca os administradores
$resultadoUsuarios = $conn->query(
    "SELECT id, nome, email FROM usuarios WHERE perfil = 'administrador' ORDER BY nome"
);
$usuarios = $resultadoUsuarios->fetch_all(MYSQLI_ASSOC);

// Valores atuais do trem
$identificador = $trem["identificador"];
$modelo = $trem["modelo"];
$status = $trem["status"];
$velocidade = $trem["velocidade_atual"];
$localizacao = $trem["localizacao_atual"] ?? "";
$consumo = $trem["consumo_energia"];
$responsavelId = $trem["responsavel_id"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $identificador = trim($_POST["identificador"] ?? "");
    $modelo = trim($_POST["modelo"] ?? "");
    $status = $_POST["status"] ?? "";
    $velocidade = $_POST["velocidade_atual"] ?? "";
    $localizacao = trim($_POST["localizacao_atual"] ?? "");
    $consumo = $_POST["consumo_energia"] ?? "";
    $responsavelId = (int) ($_POST["responsavel_id"] ?? 0);

    $statusValidos = ["ativo", "inativo", "manutencao", "falha"];
    $idsAdministradores = array_map("intval", array_column($usuarios, "id"));

    if ($identificador === "" || $modelo === "") {
        $mensagemErro = "Preencha o identificador e o modelo.";
    } elseif (!in_array($status, $statusValidos, true)) {
        $mensagemErro = "Selecione um status válido.";
    } elseif (!is_numeric($velocidade) || $velocidade < 0 || $velocidade > 9999.99) {
        $mensagemErro = "Informe uma velocidade válida.";
    } elseif (!is_numeric($consumo) || $consumo < 0 || $consumo > 999999.99) {
        $mensagemErro = "Informe um consumo válido.";
    } elseif (!in_array($responsavelId, $idsAdministradores, true)) {
        $mensagemErro = "Selecione um administrador responsável.";
    } else {
        $sql = "UPDATE trens SET
            identificador = ?, modelo = ?, status = ?, velocidade_atual = ?,
            localizacao_atual = ?, consumo_energia = ?, responsavel_id = ?
            WHERE id = ?";

        $stmt = $conn->prepare($sql);
        $velocidade = (float) $velocidade;
        $consumo = (float) $consumo;
        $localizacaoBanco = $localizacao === "" ? null : $localizacao;

        $stmt->bind_param(
            "sssdsdii",
            $identificador, $modelo, $status, $velocidade,
            $localizacaoBanco, $consumo, $responsavelId, $id
        );

        if ($stmt->execute()) {
            header("Location: listagemtrem.php?sucesso=editado");
            exit;
        }

        $mensagemErro = "Não foi possível atualizar. Verifique se o identificador já existe.";
    }
}
?>



<!DOCTYPE html>
<html lang="pt-BR">