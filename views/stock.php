<?php
require_once __DIR__ . '/../config/index.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gráfico de Ações</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/lightweight-charts@4.1.2/dist/lightweight-charts.standalone.production.min.js"></script>
    <style>
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
        }
        .glass-effect {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(148, 163, 184, 0.1);
        }
        .neon-border {
            box-shadow: 0 0 20px rgba(139, 92, 246, 0.3);
        }
    </style>
</head>
<body class="min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="glass-effect rounded-2xl shadow-2xl p-6 mb-6 neon-border">
            <h1 class="text-4xl font-bold bg-gradient-to-r from-purple-400 via-pink-500 to-red-500 bg-clip-text text-transparent mb-4">Gráfico de Candlestick</h1>
            
            <!-- Form de Seleção de Ação -->
            <div class="flex gap-4 items-end">
                <div class="flex-1">
                    <label for="symbol" class="block text-sm font-medium text-purple-300 mb-2">
                        Selecione a Ação
                    </label>
                    <select 
                        id="symbol" 
                        class="w-full px-4 py-2 bg-slate-800 text-gray-100 border border-purple-500/30 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all"
                    >
                        <?php 
                        $sortedSymbols = STOCK_SYMBOLS;
                        ksort($sortedSymbols);
                        foreach ($sortedSymbols as $symbol => $name): 
                        ?>
                        <option value="<?= $symbol ?>" <?= $symbol === 'PETR3' ? 'selected' : '' ?>>
                            <?= $symbol ?> - <?= $name ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <a 
                    href="table.php"
                    class="px-6 py-2 bg-gradient-to-r from-purple-600 to-pink-600 text-white font-medium rounded-lg hover:from-purple-700 hover:to-pink-700 transition-all shadow-lg hover:shadow-purple-500/50 inline-block text-center"
                >
                    Voltar à Tabela
                </a>
            </div>
        </div>

        <!-- Loading -->
        <div id="loading" class="hidden glass-effect rounded-2xl shadow-2xl p-6 mb-6">
            <div class="flex items-center justify-center">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-500"></div>
                <span class="ml-3 text-purple-300">Carregando dados...</span>
            </div>
        </div>

        <!-- Error Message -->
        <div id="error" class="hidden bg-red-500/10 border border-red-500/50 rounded-lg p-4 mb-6">
            <p class="text-red-400" id="error-message"></p>
        </div>

        <!-- Chart Container -->
        <div class="glass-effect rounded-2xl shadow-2xl p-6 mb-4">
            <h3 class="text-lg font-semibold text-purple-300 mb-3">Preço (Candlestick)</h3>
            <div id="chart"></div>
        </div>

        <!-- RSI Container -->
        <div class="glass-effect rounded-2xl shadow-2xl p-6 mb-4">
            <div class="flex justify-between items-center mb-3">
                <h3 class="text-lg font-semibold text-purple-300">RSI (Relative Strength Index)</h3>
                <div class="flex items-center gap-2">
                    <label for="rsi-period" class="text-sm text-purple-300">Período:</label>
                    <select 
                        id="rsi-period" 
                        onchange="loadStockData()"
                        class="px-3 py-1 text-sm bg-slate-800 text-gray-100 border border-purple-500/30 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                    >
                        <option value="9">9</option>
                        <option value="14" selected>14</option>
                        <option value="21">21</option>
                        <option value="25">25</option>
                        <option value="30">30</option>
                    </select>
                </div>
            </div>
            <div class="flex gap-4 mb-3 text-sm">
                <div class="flex items-center gap-2">
                    <div class="w-4 h-0.5" style="background-color: #a78bfa;"></div>
                    <span class="text-gray-300">RSI</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-4 h-0.5" style="background-color: #f59e0b;"></div>
                    <span class="text-gray-300">MA RSI</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-4 h-0.5 border-t border-dashed" style="border-color: #ef4444;"></div>
                    <span class="text-gray-300">Sobrecompra (70)</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-4 h-0.5 border-t border-dashed" style="border-color: #3b82f6;"></div>
                    <span class="text-gray-300">Sobrevenda (30)</span>
                </div>
            </div>
            <div id="chart-rsi"></div>
        </div>

        <!-- Moving Averages Container -->
        <div class="glass-effect rounded-2xl shadow-2xl p-6 mb-4">
            <h3 class="text-lg font-semibold text-purple-300 mb-3">Médias Móveis</h3>
            <div class="flex gap-4 mb-3 text-sm">
                <div class="flex items-center gap-2">
                    <div class="w-4 h-0.5" style="background-color: #3b82f6;"></div>
                    <span class="text-gray-300">MA20</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-4 h-0.5" style="background-color: #f59e0b;"></div>
                    <span class="text-gray-300">MA50</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-4 h-0.5" style="background-color: #8b5cf6;"></div>
                    <span class="text-gray-300">MA100</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-4 h-0.5" style="background-color: #6b7280;"></div>
                    <span class="text-gray-300">MA200</span>
                </div>
            </div>
            <div id="chart-ma"></div>
        </div>
    </div>

    <script>
        const WINDOW_SIZE = 90; // manter sempre 90 candles visíveis
        let chart = null;
        let candleSeries = null;
        let maChart = null;
        let ma20Series = null;
        let ma50Series = null;
        let ma100Series = null;
        let ma200Series = null;
        let rsiChart = null;
        let rsiSeries = null;
        let rsiMaSeries = null;
        let allCandles = [];
        let isAdjustingRange = false;
        let latestRangeDebug = null;

        // Obter símbolo da URL
        function getSymbolFromURL() {
            const urlParams = new URLSearchParams(window.location.search);
            return urlParams.get('symbol') || 'PETR3';
        }

        // Atualizar URL com símbolo
        function updateURL(symbol) {
            const url = new URL(window.location);
            url.searchParams.set('symbol', symbol.toUpperCase());
            window.history.pushState({}, '', url);
        }

        // Inicializar símbolo no campo e carregar dados
        document.addEventListener('DOMContentLoaded', function() {
            const symbol = getSymbolFromURL();
            document.getElementById('symbol').value = symbol.toUpperCase();
            
            // Carregar dados após inicializar
            loadStockData();
        });

        async function loadStockData() {
            const symbol = document.getElementById('symbol').value.trim().toUpperCase();
            if (!symbol) {
                showError('Por favor, insira um símbolo de ação');
                return;
            }

            // Atualizar URL
            updateURL(symbol);

            hideError();
            showLoading();

            try {
                const response = await fetch(`../api/prices.php?symbol=${symbol}`);
                const data = await response.json();

                if (!response.ok) throw new Error(data.error || 'Erro ao carregar dados');
                if (!data.historicalDataPrice || data.historicalDataPrice.length === 0)
                    throw new Error('Nenhum dado encontrado para este símbolo');

                hideLoading();

                // Sanitizar dados: datas válidas e números finitos
                    allCandles = data.historicalDataPrice
                        .map((item, idx) => {
                            if (!item.date) return null;
                            const iso = String(item.date).substring(0, 10);
                            const open = Number(item.open);
                            const high = Number(item.high);
                            const low = Number(item.low);
                            const close = Number(item.close);
                            if (!Number.isFinite(open) || !Number.isFinite(high) || !Number.isFinite(low) || !Number.isFinite(close)) {
                                console.warn('Ignorando candle com valor não numérico', { idx, iso, open, high, low, close });
                                return null;
                            }
                            return { time: iso, open, high, low, close };
                        })
                        .filter(Boolean);

                const rsiPeriod = parseInt(document.getElementById('rsi-period').value) || 14;
                
                const ma20 = computeSMA(allCandles, 20);
                const ma50 = computeSMA(allCandles, 50);
                const ma100 = computeSMA(allCandles, 100);
                const ma200 = computeSMA(allCandles, 200);
                const rsi14 = computeRSI(allCandles, rsiPeriod);
                const rsiMa = computeSMAFromData(rsi14, rsiPeriod);

                initCharts();
                candleSeries.setData(allCandles);
                if (ma20.length) ma20Series.setData(ma20);
                if (ma50.length) ma50Series.setData(ma50);
                if (ma100.length) ma100Series.setData(ma100);
                if (ma200.length) ma200Series.setData(ma200);
                if (rsi14.length) rsiSeries.setData(rsi14);
                if (rsiMa.length) rsiMaSeries.setData(rsiMa);

                setLastWindow();
                syncRSIRange();
            } catch (error) {
                console.log(error);
                hideLoading();
                showError(error.message);
            }
        }

        function initCharts() {
            const priceContainer = document.querySelector('#chart');
            const maContainer = document.querySelector('#chart-ma');
            const rsiContainer = document.querySelector('#chart-rsi');
            priceContainer.innerHTML = '';
            maContainer.innerHTML = '';
            rsiContainer.innerHTML = '';

            const { createChart, CrosshairMode } = window.LightweightCharts || {};
            if (!createChart) {
                showError('Biblioteca de gráficos não carregou. Verifique sua conexão.');
                return;
            }

            chart = createChart(priceContainer, {
                height: 500,
                layout: { background: { color: '#1e293b' }, textColor: '#e2e8f0' },
                grid: {
                    vertLines: { color: '#334155' },
                    horzLines: { color: '#334155' }
                },
                timeScale: {
                    borderColor: '#475569'
                },
                rightPriceScale: { borderColor: '#475569' },
                crosshair: { mode: CrosshairMode.Normal }
            });

            if (!chart.addCandlestickSeries) {
                showError('API de candlestick não disponível nesta versão do gráfico.');
                return;
            }

            candleSeries = chart.addCandlestickSeries({
                upColor: '#22c55e',
                downColor: '#f43f5e',
                borderVisible: false,
                wickUpColor: '#22c55e',
                wickDownColor: '#f43f5e'
            });

            // Moving Averages chart
            maChart = createChart(maContainer, {
                height: 300,
                layout: { background: { color: '#1e293b' }, textColor: '#e2e8f0' },
                grid: {
                    vertLines: { color: '#334155' },
                    horzLines: { color: '#334155' }
                },
                timeScale: {
                    borderColor: '#475569'
                },
                rightPriceScale: { borderColor: '#475569' },
                crosshair: { mode: CrosshairMode.Normal }
            });

            ma20Series = maChart.addLineSeries({ color: '#3b82f6', lineWidth: 2 });
            ma50Series = maChart.addLineSeries({ color: '#f59e0b', lineWidth: 2 });
            ma100Series = maChart.addLineSeries({ color: '#8b5cf6', lineWidth: 2 });
            ma200Series = maChart.addLineSeries({ color: '#6b7280', lineWidth: 2 });

            // RSI chart
            rsiChart = createChart(rsiContainer, {
                height: 200,
                layout: { background: { color: '#1e293b' }, textColor: '#e2e8f0' },
                grid: {
                    vertLines: { color: '#334155' },
                    horzLines: { color: '#334155' }
                },
                timeScale: {
                    borderColor: '#475569'
                },
                rightPriceScale: { 
                    borderColor: '#475569',
                    visible: true,
                    mode: 0
                },
                crosshair: { mode: CrosshairMode.Normal }
            });

            rsiSeries = rsiChart.addLineSeries({ 
                color: '#a78bfa', 
                lineWidth: 2,
                autoscaleInfoProvider: () => ({
                    priceRange: {
                        minValue: 20,
                        maxValue: 80
                    },
                    margins: {
                        above: 0,
                        below: 0
                    }
                })
            });
            
            rsiMaSeries = rsiChart.addLineSeries({ 
                color: '#f59e0b', 
                lineWidth: 2,
                lineStyle: 0
            });
            
            rsiChart.priceScale('right').applyOptions({
                scaleMargins: {
                    top: 0.1,
                    bottom: 0.1
                },
                autoScale: false
            });
            
            rsiSeries.createPriceLine({ price: 70, color: '#ef4444', lineWidth: 1, lineStyle: 2, axisLabelVisible: true });
            rsiSeries.createPriceLine({ price: 30, color: '#3b82f6', lineWidth: 1, lineStyle: 2, axisLabelVisible: true });

            // Sincronizar tempo entre gráficos
            chart.timeScale().subscribeVisibleLogicalRangeChange(range => {
                if (isAdjustingRange || !range || range.from == null || range.to == null) return;
                syncMARange();
                syncRSIRange();
            });
            maChart.timeScale().subscribeVisibleLogicalRangeChange(range => {
                if (isAdjustingRange || !range || range.from == null || range.to == null) return;
                syncPriceRangeFromMA();
            });
            rsiChart.timeScale().subscribeVisibleLogicalRangeChange(range => {
                if (isAdjustingRange || !range || range.from == null || range.to == null) return;
                syncPriceRangeFromRSI();
            });
        }

        function enforceWindow(range) {
            const total = allCandles.length;
            if (total === 0 || !range || range.from == null || range.to == null) return;

            latestRangeDebug = {
                total,
                rangeFrom: range.from,
                rangeTo: range.to,
                sampleFrom: allCandles[Math.max(0, Math.floor(range.from))]?.time,
                sampleTo: allCandles[Math.min(total - 1, Math.floor(range.to))]?.time
            };

            const target = WINDOW_SIZE;
            const current = Math.round(range.to - range.from);

            if (!Number.isFinite(current) || current <= 0) return;
            if (current === target) return;

            let end = Math.round(range.to);
            let start = end - target;

            if (start < 0) {
                start = 0;
                end = Math.min(target, total - 1);
            }
            if (end >= total) {
                end = total - 1;
                start = Math.max(0, end - target);
            }

            const fromTime = allCandles[start].time;
            const toTime = allCandles[end].time;
            if (!fromTime || !toTime) return;

            isAdjustingRange = true;
            chart.timeScale().setVisibleRange({ from: fromTime, to: toTime });
            isAdjustingRange = false;
        }

        function setLastWindow() {
            const total = allCandles.length;
            if (total === 0) return;

            const end = total - 1;
            const start = Math.max(0, end - WINDOW_SIZE + 1);
            if (!allCandles[start]?.time || !allCandles[end]?.time) return;
            chart.timeScale().setVisibleRange({
                from: allCandles[start].time,
                to: allCandles[end].time
            });
            syncMARange();
            syncRSIRange();
        }

        function syncMARange() {
            if (!chart || !maChart) return;
            try {
                const range = chart.timeScale().getVisibleRange();
                if (!range || range.from == null || range.to == null) return;
                if (typeof range.from !== 'number' && typeof range.from !== 'string') return;
                if (typeof range.to !== 'number' && typeof range.to !== 'string') return;
                isAdjustingRange = true;
                maChart.timeScale().setVisibleRange({ from: range.from, to: range.to });
                isAdjustingRange = false;
            } catch (err) {
                console.error('syncMARange error:', err, { range: chart.timeScale().getVisibleRange() });
            }
        }

        function syncRSIRange() {
            if (!chart || !rsiChart) return;
            try {
                const range = chart.timeScale().getVisibleRange();
                if (!range || range.from == null || range.to == null) return;
                if (typeof range.from !== 'number' && typeof range.from !== 'string') return;
                if (typeof range.to !== 'number' && typeof range.to !== 'string') return;
                isAdjustingRange = true;
                rsiChart.timeScale().setVisibleRange({ from: range.from, to: range.to });
                isAdjustingRange = false;
            } catch (err) {
                console.error('syncRSIRange error:', err, { range: chart.timeScale().getVisibleRange() });
            }
        }

        function syncPriceRangeFromRSI() {
            if (!chart || !rsiChart || !maChart) return;
            try {
                const range = rsiChart.timeScale().getVisibleRange();
                if (!range || range.from == null || range.to == null) return;
                if (typeof range.from !== 'number' && typeof range.from !== 'string') return;
                if (typeof range.to !== 'number' && typeof range.to !== 'string') return;
                isAdjustingRange = true;
                chart.timeScale().setVisibleRange({ from: range.from, to: range.to });
                maChart.timeScale().setVisibleRange({ from: range.from, to: range.to });
                isAdjustingRange = false;
            } catch (err) {
                console.error('syncPriceRangeFromRSI error:', err);
            }
        }

        function syncPriceRangeFromMA() {
            if (!chart || !maChart || !rsiChart) return;
            try {
                const range = maChart.timeScale().getVisibleRange();
                if (!range || range.from == null || range.to == null) return;
                if (typeof range.from !== 'number' && typeof range.from !== 'string') return;
                if (typeof range.to !== 'number' && typeof range.to !== 'string') return;
                isAdjustingRange = true;
                chart.timeScale().setVisibleRange({ from: range.from, to: range.to });
                rsiChart.timeScale().setVisibleRange({ from: range.from, to: range.to });
                isAdjustingRange = false;
            } catch (err) {
                console.error('syncPriceRangeFromMA error:', err);
            }
        }

        function computeSMA(candles, period) {
            const result = [];
            if (candles.length < period) return result;
            let sum = 0;
            for (let i = 0; i < candles.length; i++) {
                sum += candles[i].close;
                if (i >= period) sum -= candles[i - period].close;
                if (i >= period - 1) {
                    const value = sum / period;
                    if (Number.isFinite(value)) {
                        result.push({ time: candles[i].time, value });
                    }
                }
            }
            return result;
        }

        function computeSMAFromData(data, period) {
            const result = [];
            if (data.length < period) return result;
            let sum = 0;
            for (let i = 0; i < data.length; i++) {
                sum += data[i].value;
                if (i >= period) sum -= data[i - period].value;
                if (i >= period - 1) {
                    const value = sum / period;
                    if (Number.isFinite(value)) {
                        result.push({ time: data[i].time, value });
                    }
                }
            }
            return result;
        }

        function computeRSI(candles, period) {
            const result = [];
            if (candles.length < period + 1) return result;
            let gains = 0;
            let losses = 0;

            for (let i = 1; i <= period; i++) {
                const diff = candles[i].close - candles[i - 1].close;
                if (diff >= 0) gains += diff; else losses -= diff;
            }

            let avgGain = gains / period;
            let avgLoss = losses / period;
            let rs = avgLoss === 0 ? Infinity : avgGain / avgLoss;
            let firstRsi = rs === Infinity ? 100 : 100 - (100 / (1 + rs));
            result.push({ time: candles[period].time, value: firstRsi });

            for (let i = period + 1; i < candles.length; i++) {
                const diff = candles[i].close - candles[i - 1].close;
                const gain = diff > 0 ? diff : 0;
                const loss = diff < 0 ? -diff : 0;

                avgGain = ((avgGain * (period - 1)) + gain) / period;
                avgLoss = ((avgLoss * (period - 1)) + loss) / period;
                rs = avgLoss === 0 ? Infinity : avgGain / avgLoss;
                const rsi = rs === Infinity ? 100 : 100 - (100 / (1 + rs));
                if (Number.isFinite(rsi)) {
                    result.push({ time: candles[i].time, value: rsi });
                }
            }

            return result;
        }

        function showLoading() {
            document.getElementById('loading').classList.remove('hidden');
        }

        function hideLoading() {
            document.getElementById('loading').classList.add('hidden');
        }

        function showError(message) {
            document.getElementById('error-message').textContent = message;
            document.getElementById('error').classList.remove('hidden');
        }

        function hideError() {
            document.getElementById('error').classList.add('hidden');
        }

        // Atualizar ao mudar seleção
        document.getElementById('symbol').addEventListener('change', function() {
            loadStockData();
        });
    </script>
</body>
</html>
