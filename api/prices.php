<?php

// Query params: symbol
// Obter o histórico diário completo da ação
// Os dados estão salvos em arquivos JSON na pasta /data/{symbol}/full.json + /data/{symbol}/{date}.json
// Ordernar pelo results[0].historicalDataPrice[].date
// Deve ter apenas um registro por dia

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Obter o parâmetro symbol
$symbol = $_GET['symbol'] ?? null;

if (!$symbol) {
    http_response_code(400);
    echo json_encode(['error' => 'Parâmetro "symbol" é obrigatório']);
    exit;
}

// Validar o símbolo
$symbol = strtoupper(trim($symbol));
if (!preg_match('/^[A-Z0-9]+$/', $symbol)) {
    http_response_code(400);
    echo json_encode(['error' => 'Símbolo inválido']);
    exit;
}

// Diretório dos dados
$dataDir = __DIR__ . "/../data/{$symbol}";

if (!is_dir($dataDir)) {
    http_response_code(404);
    echo json_encode(['error' => 'Símbolo não encontrado']);
    exit;
}

// Array para armazenar todos os dados históricos
$allHistoricalData = [];

// Ler o arquivo full.json se existir
$fullFile = "{$dataDir}/full.json";
if (file_exists($fullFile)) {
    $fullData = json_decode(file_get_contents($fullFile), true);
    
    if (isset($fullData['results'][0]['historicalDataPrice'])) {
        foreach ($fullData['results'][0]['historicalDataPrice'] as $data) {
            if (isset($data['date'])) {
                // Converter timestamp Unix para data YYYY-MM-DD
                if (is_numeric($data['date'])) {
                    $date = date('Y-m-d', $data['date']);
                } else {
                    $date = substr($data['date'], 0, 10);
                }
                $allHistoricalData[$date] = $data;
            }
        }
    }
}

// Ler todos os arquivos JSON diários (formato YYYY-MM-DD.json)
$files = glob("{$dataDir}/*.json");
foreach ($files as $file) {
    $filename = basename($file);
    
    // Ignorar o arquivo full.json
    if ($filename === 'full.json') {
        continue;
    }
    
    // Verificar se o nome do arquivo está no formato de data
    if (preg_match('/^\d{4}-\d{2}-\d{2}\.json$/', $filename)) {
        $fileData = json_decode(file_get_contents($file), true);
        
        if (isset($fileData['results'][0]['historicalDataPrice'])) {
            foreach ($fileData['results'][0]['historicalDataPrice'] as $data) {
                if (isset($data['date'])) {
                    // Converter timestamp Unix para data YYYY-MM-DD
                    if (is_numeric($data['date'])) {
                        $date = date('Y-m-d', $data['date']);
                    } else {
                        $date = substr($data['date'], 0, 10);
                    }
                    // Sobrescreve se já existir (dados mais recentes têm prioridade)
                    $allHistoricalData[$date] = $data;
                }
            }
        }
    }
}

// Verificar se há dados
if (empty($allHistoricalData)) {
    http_response_code(404);
    echo json_encode(['error' => 'Nenhum dado histórico encontrado para este símbolo']);
    exit;
}

// Ordenar por data (do mais antigo para o mais recente)
ksort($allHistoricalData);

// Converter para array indexado e normalizar a data para ISO (YYYY-MM-DD)
$historicalDataArray = [];
foreach ($allHistoricalData as $dateKey => $data) {
    if (isset($data['date'])) {
        if (is_numeric($data['date'])) {
            $data['date'] = date('Y-m-d', $data['date']);
        } else {
            // Manter apenas YYYY-MM-DD se já vier como string
            $data['date'] = substr($data['date'], 0, 10);
        }
    } else {
        // Garantir data presente com a chave
        $data['date'] = $dateKey;
    }
    $historicalDataArray[] = $data;
}

// Preparar resposta no formato esperado
$response = [
    'symbol' => $symbol,
    'historicalDataPrice' => $historicalDataArray,
    'totalRecords' => count($historicalDataArray)
];

echo json_encode($response, JSON_PRETTY_PRINT);