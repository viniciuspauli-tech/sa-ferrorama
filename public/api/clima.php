
<?php

header("Content-Type: application/json; charset=utf-8");

// Localização: Joinville - SC
$latitude = -26.3044;
$longitude = -48.8487;
$localizacao = "Joinville - SC";

// URL da API Open-Meteo
$url = "https://api.open-meteo.com/v1/forecast?" . http_build_query([
    "latitude" => $latitude,
    "longitude" => $longitude,
    "current" => "temperature_2m,relative_humidity_2m,apparent_temperature,is_day,precipitation,weather_code,wind_speed_10m",
    "timezone" => "America/Sao_Paulo"
]);

// Configuração da requisição
$opcoes = [
    "http" => [
        "method" => "GET",
        "timeout" => 10,
        "header" => "User-Agent: Ferrorama/1.0\r\n"
    ]
];

$contexto = stream_context_create($opcoes);

// Consulta a API
$resposta = @file_get_contents($url, false, $contexto);

// Verifica se houve erro
if ($resposta === false) {
    http_response_code(502);

    echo json_encode([
        "erro" => "Não foi possível consultar a API de clima."
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// Converte o JSON para array
$dados = json_decode($resposta, true);

// Verifica os dados recebidos
if (!is_array($dados) || !isset($dados["current"])) {
    http_response_code(502);

    echo json_encode([
        "erro" => "A API retornou dados inválidos."
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$atual = $dados["current"];

// Tradução das condições climáticas
$condicoes = [
    0 => "Céu limpo",
    1 => "Predominantemente limpo",
    2 => "Parcialmente nublado",
    3 => "Nublado",
    45 => "Nevoeiro",
    48 => "Nevoeiro com geada",
    51 => "Garoa leve",
    53 => "Garoa moderada",
    55 => "Garoa intensa",
    61 => "Chuva leve",
    63 => "Chuva moderada",
    65 => "Chuva intensa",
    71 => "Neve leve",
    73 => "Neve moderada",
    75 => "Neve intensa",
    80 => "Pancadas de chuva leves",
    81 => "Pancadas de chuva moderadas",
    82 => "Pancadas de chuva fortes",
    95 => "Tempestade",
    96 => "Tempestade com granizo leve",
    99 => "Tempestade com granizo forte"
];

$codigoClima = (int) ($atual["weather_code"] ?? -1);

$condicao = $condicoes[$codigoClima] ?? "Condição não identificada";

// Retorna os dados
echo json_encode([
    "localizacao" => $localizacao,
    "temperatura" => $atual["temperature_2m"] ?? null,
    "sensacao" => $atual["apparent_temperature"] ?? null,
    "umidade" => $atual["relative_humidity_2m"] ?? null,
    "vento" => $atual["wind_speed_10m"] ?? null,
    "precipitacao" => $atual["precipitation"] ?? null,
    "condicao" => $condicao,
    "atualizado_em" => $atual["time"] ?? null
], JSON_UNESCAPED_UNICODE);

?>
