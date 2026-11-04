
<?php

header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store");

// Localização padrão: Joinville - SC
$latitude = -26.3044;
$longitude = -48.8487;
$localizacao = "Joinville, Santa Catarina";

// Monta a URL da API
$url = "https://api.open-meteo.com/v1/forecast?" . http_build_query([
    "latitude" => $latitude,
    "longitude" => $longitude,
    "current" => "temperature_2m,relative_humidity_2m,apparent_temperature,is_day,precipitation,weather_code,wind_speed_10m",
    "timezone" => "America/Sao_Paulo"
]);

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

// Verifica falha na comunicação
if ($resposta === false) {
    http_response_code(502);

    echo json_encode([
        "erro" => "Não foi possível consultar o clima."
    ]);

    exit;
}

// Converte o JSON
$dados = json_decode($resposta, true);

if (!is_array($dados) || !isset($dados["current"])) {
    http_response_code(502);

    echo json_encode([
        "erro" => "A API retornou dados inválidos."
    ]);

    exit;
}

$atual = $dados["current"];

// Traduz os códigos meteorológicos
$condicoes = [
    0 => "Céu limpo",
    1 => "Predominantemente limpo",
    2 => "Parcialmente nublado",
    3 => "Nublado",
    45 => "Neblina",
    48 => "Neblina com geada",
    51 => "Garoa leve",
    53 => "Garoa moderada",
    55 => "Garoa intensa",
    61 => "Chuva fraca",
    63 => "Chuva moderada",
    65 => "Chuva forte",
    71 => "Neve fraca",
    73 => "Neve moderada",
    75 => "Neve forte",
    80 => "Pancadas de chuva fracas",
    81 => "Pancadas de chuva moderadas",
    82 => "Pancadas de chuva fortes",
    95 => "Trovoadas",
    96 => "Trovoadas com granizo",
    99 => "Trovoadas com granizo forte"
];

$codigo = (int) $atual["weather_code"];

$descricao = $condicoes[$codigo] ?? "Condição não identificada";

// Retorna os dados para o JavaScript
echo json_encode([
    "localizacao" => $localizacao,
    "temperatura" => $atual["temperature_2m"],
    "sensacao" => $atual["apparent_temperature"],
    "umidade" => $atual["relative_humidity_2m"],
    "vento" => $atual["wind_speed_10m"],
    "precipitacao" => $atual["precipitation"],
    "condicao" => $descricao,
    "atualizado_em" => $atual["time"]
], JSON_UNESCAPED_UNICODE);

