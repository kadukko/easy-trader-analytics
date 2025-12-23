<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../config/index.php';

// Configuração do cache
$cacheDir = __DIR__ . '/../cache';
$cacheFile = $cacheDir . '/stocks.json';
$cacheTime = 600; // 10 minutos em segundos

// Criar diretório de cache se não existir
if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0755, true);
}

// Verificar se existe cache válido
if (file_exists($cacheFile)) {
    $cacheAge = time() - filemtime($cacheFile);
    
    if ($cacheAge < $cacheTime) {
        // Cache ainda válido, retornar dados em cache
        $cachedData = file_get_contents($cacheFile);
        header('X-Cache: HIT');
        header('X-Cache-Age: ' . $cacheAge);
        echo $cachedData;
        exit;
    }
}

// Função para calcular RSI (método smoothed - padrão da indústria)
function calculateRSI($prices, $period = 14) {
    if (count($prices) < $period + 1) return null;
    
    // Primeira média simples
    $gains = 0;
    $losses = 0;
    
    for ($i = 1; $i <= $period; $i++) {
        $diff = $prices[$i] - $prices[$i - 1];
        if ($diff >= 0) {
            $gains += $diff;
        } else {
            $losses -= $diff;
        }
    }
    
    $avgGain = $gains / $period;
    $avgLoss = $losses / $period;
    
    // Médias suavizadas (smoothed) para o restante dos dados
    for ($i = $period + 1; $i < count($prices); $i++) {
        $diff = $prices[$i] - $prices[$i - 1];
        $gain = $diff > 0 ? $diff : 0;
        $loss = $diff < 0 ? -$diff : 0;
        
        $avgGain = (($avgGain * ($period - 1)) + $gain) / $period;
        $avgLoss = (($avgLoss * ($period - 1)) + $loss) / $period;
    }
    
    if ($avgLoss == 0) return 100;
    
    $rs = $avgGain / $avgLoss;
    $rsi = 100 - (100 / (1 + $rs));
    
    return round($rsi, 2);
}

// Função para calcular SMA de um array
function calculateSMA($values, $period) {
    if (count($values) < $period) return null;
    
    $sum = 0;
    for ($i = count($values) - $period; $i < count($values); $i++) {
        $sum += $values[$i];
    }
    
    return round($sum / $period, 2);
}

// Função para calcular RSI de todos os candles e retornar array
function calculateRSIArray($prices, $period = 14) {
    $rsiValues = [];
    if (count($prices) < $period + 1) return $rsiValues;
    
    $gains = 0;
    $losses = 0;
    
    for ($i = 1; $i <= $period; $i++) {
        $diff = $prices[$i] - $prices[$i - 1];
        if ($diff >= 0) {
            $gains += $diff;
        } else {
            $losses -= $diff;
        }
    }
    
    $avgGain = $gains / $period;
    $avgLoss = $losses / $period;
    $rs = $avgLoss == 0 ? PHP_INT_MAX : $avgGain / $avgLoss;
    $rsi = $rs == PHP_INT_MAX ? 100 : 100 - (100 / (1 + $rs));
    $rsiValues[] = $rsi;
    
    for ($i = $period + 1; $i < count($prices); $i++) {
        $diff = $prices[$i] - $prices[$i - 1];
        $gain = $diff > 0 ? $diff : 0;
        $loss = $diff < 0 ? -$diff : 0;
        
        $avgGain = (($avgGain * ($period - 1)) + $gain) / $period;
        $avgLoss = (($avgLoss * ($period - 1)) + $loss) / $period;
        $rs = $avgLoss == 0 ? PHP_INT_MAX : $avgGain / $avgLoss;
        $rsi = $rs == PHP_INT_MAX ? 100 : 100 - (100 / (1 + $rs));
        $rsiValues[] = $rsi;
    }
    
    return $rsiValues;
}

// Função para obter dados de uma ação
function getStockData($symbol) {
    $dataDir = __DIR__ . '/../data/' . $symbol;
    
    if (!is_dir($dataDir)) {
        return null;
    }
    
    // Ler full.json
    $fullFile = $dataDir . '/full.json';
    if (!file_exists($fullFile)) {
        return null;
    }
    
    $fullData = json_decode(file_get_contents($fullFile), true);
    if (!$fullData || !isset($fullData['results'][0]['historicalDataPrice'])) {
        return null;
    }
    
    $historicalData = $fullData['results'][0]['historicalDataPrice'];
    
    // Adicionar dados diários
    $dailyFiles = glob($dataDir . '/*.json');
    foreach ($dailyFiles as $file) {
        if (basename($file) === 'full.json') continue;
        
        $dailyData = json_decode(file_get_contents($file), true);
        if ($dailyData && isset($dailyData['results'][0]['historicalDataPrice'])) {
            foreach ($dailyData['results'][0]['historicalDataPrice'] as $candle) {
                $historicalData[] = $candle;
            }
        }
    }
    
    // Ordenar por data (do mais antigo para o mais recente)
    usort($historicalData, function($a, $b) {
        $dateA = is_numeric($a['date']) ? $a['date'] : strtotime($a['date']);
        $dateB = is_numeric($b['date']) ? $b['date'] : strtotime($b['date']);
        return $dateA - $dateB;
    });
    
    // Remover duplicatas (mantém o último registro de cada data)
    $uniqueData = [];
    $dates = [];
    foreach ($historicalData as $candle) {
        $timestamp = is_numeric($candle['date']) ? $candle['date'] : strtotime($candle['date']);
        $dateKey = date('Y-m-d', $timestamp);
        if (!in_array($dateKey, $dates)) {
            $dates[] = $dateKey;
            $uniqueData[] = $candle;
        }
    }
    
    if (empty($uniqueData)) {
        return null;
    }
    
    // Pegar último candle
    $lastCandle = end($uniqueData);
    
    // Converter data do último candle
    $lastDate = is_numeric($lastCandle['date']) 
        ? date('d/m/Y', $lastCandle['date']) 
        : date('d/m/Y', strtotime($lastCandle['date']));
    
    // Extrair preços de fechamento para cálculo do RSI
    $closePrices = array_map(function($candle) {
        return (float)$candle['close'];
    }, $uniqueData);
    
    // Limitar aos últimos 100 candles
    $closePrices = array_slice($closePrices, -100);
    
    return [
        'symbol' => $symbol,
        'lastClose' => (float)$lastCandle['close'],
        'lastVolume' => (int)$lastCandle['volume'],
        'lastDate' => $lastDate
    ];
}

try {
    // Coletar dados de todas as ações
    $stocksData = [];
    foreach (STOCK_SYMBOLS as $symbol => $name) {
        $data = getStockData($symbol);
        if ($data) {
            $data['name'] = $name;
            $stocksData[] = $data;
        }
    }

    // Calcular estatísticas
    $totalStocks = count($stocksData);

    $responseData = json_encode([
        'success' => true,
        'stocks' => $stocksData,
        'stats' => [
            'totalStocks' => $totalStocks
        ]
    ], JSON_UNESCAPED_UNICODE);

    // Salvar no cache
    file_put_contents($cacheFile, $responseData);
    header('X-Cache: MISS');
    
    echo $responseData;

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
