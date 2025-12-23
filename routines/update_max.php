<?php

require_once __DIR__ . '/../config/index.php';

$api_key = BRAPI_API_KEY;
$symbols = STOCK_SYMBOLS;

// Data de hoje
$today = new DateTime('now', new DateTimeZone('America/Sao_Paulo'));
$today = $today->format('Y-m-d');

// Função para fazer a requisição à API
function fetchStockData($symbol, $api_key) {
    $url = "https://brapi.dev/api/quote/{$symbol}?range=max&interval=1d";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: Bearer {$api_key}"
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    curl_close($ch);
    
    if ($http_code == 200) {
        $data = json_decode($response, true);
        
        if (isset($data['results'][0]['historicalDataPrice']) && 
            count($data['results'][0]['historicalDataPrice']) > 0) {

            $historical = &$data['results'][0]['historicalDataPrice'];

            $filtered = array_filter($historical, function ($record) {
                return !isset($record['volume']) || (int)$record['volume'] !== 0;
            });

            if (count($filtered) !== count($historical)) {
                $historical = array_values($filtered);
                echo "  → Removidos registros com volume 0\n";
            }
        }
        
        return $data;
    } else {
        echo "  ✗ Falha ao obter dados para {$symbol}: HTTP {$http_code} - {$response}\n";
    }
    
    return null;
}

// Função para salvar os dados em arquivo JSON
function saveStockData($symbol, $data) {
    // Criar diretório se não existir
    $dir = __DIR__ . "/../data/{$symbol}";
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
    
    // Caminho do arquivo
    $filename = "{$dir}/full.json";
    
    // Salvar o arquivo
    file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT));
    
    return $filename;
}

// Processar cada símbolo
foreach ($symbols as $symbol => $name) {
    echo "Processando {$name} ({$symbol})...\n";
    
    // Verificar se o arquivo já existe
    $dir = __DIR__ . "/../data/{$symbol}";
    $filename = "{$dir}/full.json";
    
    if (file_exists($filename)) {
        echo "  ✓ Arquivo já existe: {$filename}\n";
        echo "  Pulando download...\n\n";
        continue;
    }
    
    // Fazer a requisição
    echo "  Baixando dados da API...\n";
    $data = fetchStockData($symbol, $api_key);
    
    if ($data) {
        // Salvar os dados
        $saved_file = saveStockData($symbol, $data);
        echo "  ✓ Dados salvos em: {$saved_file}\n";
    } else {
        echo "  ✗ Erro ao baixar dados para {$symbol}\n";
    }
    
    // Aguardar 2 segundos antes da próxima requisição (exceto no último)
    if ($symbol !== array_key_last($symbols)) {
        echo "  Aguardando 2 segundos...\n\n";
        sleep(2);
    } else {
        echo "\n";
    }
}

echo "Processo concluído!\n";