
<?php

header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store");

// Recebe o CEP
$cep = preg_replace('/\D/', '', $_GET["cep"] ?? "");

// Verifica se possui 8 números
if (strlen($cep) !== 8) {

    http_response_code(400);

    echo json_encode([
        "erro" => "Informe um CEP válido com 8 números."
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// URL do ViaCEP
$url = "https://viacep.com.br/ws/" . $cep . "/json/";

// Configura a requisição
$contexto = stream_context_create([
    "http" => [
        "timeout" => 10,
        "ignore_errors" => true
    ],
    "ssl" => [
        "verify_peer" => true,
        "verify_peer_name" => true
    ]
]);

$resposta = @file_get_contents($url, false, $contexto);

// Verifica falha de comunicação
if ($resposta === false) {

    http_response_code(502);

    echo json_encode([
        "erro" => "Falha de comunicação com o ViaCEP."
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// Converte JSON
$dados = json_decode($resposta, true);

if (!is_array($dados)) {

    http_response_code(502);

    echo json_encode([
        "erro" => "Resposta inválida da API."
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// Verifica CEP inexistente
if (isset($dados["erro"])) {

    http_response_code(404);

    echo json_encode([
        "erro" => "CEP não encontrado."
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// Retorna os dados
echo json_encode([
    "cep" => $dados["cep"] ?? "",
    "logradouro" => $dados["logradouro"] ?? "",
    "bairro" => $dados["bairro"] ?? "",
    "cidade" => $dados["localidade"] ?? "",
    "uf" => $dados["uf"] ?? ""
], JSON_UNESCAPED_UNICODE);

