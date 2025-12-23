<?php
require_once __DIR__ . '/../config/index.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Ações</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
        .stat-card {
            transition: all 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0 30px rgba(139, 92, 246, 0.5);
        }
    </style>
</head>
<body class="min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="glass-effect rounded-2xl shadow-2xl p-6 mb-6 neon-border">
            <div class="flex justify-between items-center">
                <h1 class="text-4xl font-bold bg-gradient-to-r from-purple-400 via-pink-500 to-red-500 bg-clip-text text-transparent">Lista de Ações</h1>
                <a href="stock.php" class="px-6 py-2 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-lg hover:from-purple-700 hover:to-pink-700 transition-all shadow-lg hover:shadow-purple-500/50">
                    Ver Gráficos
                </a>
            </div>
        </div>

        <!-- Loading -->
        <div id="loading" class="glass-effect rounded-2xl shadow-2xl p-6 mb-6">
            <div class="flex items-center justify-center">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-500"></div>
                <span class="ml-3 text-purple-300">Carregando dados...</span>
            </div>
        </div>

        <!-- Error Message -->
        <div id="error" class="hidden bg-red-500/10 border border-red-500/50 rounded-lg p-4 mb-6">
            <p class="text-red-400" id="error-message"></p>
        </div>

        <!-- Summary -->
        <div id="summary" class="glass-effect rounded-2xl shadow-2xl p-6 mb-6 hidden">
            <h3 class="text-lg font-semibold text-purple-300 mb-4">Resumo</h3>
            <div class="grid grid-cols-1 md:grid-cols-1 gap-4">
                <div class="stat-card glass-effect rounded-xl p-6 border border-blue-500/30">
                    <p class="text-sm text-blue-300 mb-2">Quantidade de Ações</p>
                    <p class="text-4xl font-bold text-blue-400" id="total-stocks">0</p>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div id="table-container" class="glass-effect rounded-2xl shadow-2xl overflow-hidden hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-700">
                    <thead class="bg-slate-800/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-purple-300 uppercase tracking-wider">
                                Símbolo
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-purple-300 uppercase tracking-wider">
                                Data
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-purple-300 uppercase tracking-wider">
                                Último Fechamento
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-purple-300 uppercase tracking-wider">
                                Volume
                            </th>
                        </tr>
                    </thead>
                    <tbody id="table-body" class="bg-slate-900/30 divide-y divide-slate-700">
                        <!-- Dados carregados via JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <script>
        let allStocks = [];

        // Carregar dados ao iniciar
        document.addEventListener('DOMContentLoaded', function() {
            loadStocksData();
        });

        async function loadStocksData() {
            showLoading();
            hideError();

            try {
                const response = await fetch('../api/stocks.php');
                const data = await response.json();

                if (!response.ok || !data.success) {
                    throw new Error(data.error || 'Erro ao carregar dados');
                }

                allStocks = data.stocks;
                updateStats(data.stats);
                renderTable(allStocks);

                hideLoading();
                document.getElementById('summary').classList.remove('hidden');
                document.getElementById('table-container').classList.remove('hidden');

            } catch (error) {
                console.error(error);
                hideLoading();
                showError(error.message);
            }
        }

        function updateStats(stats) {
            document.getElementById('total-stocks').textContent = stats.totalStocks;
        }

        function renderTable(stocks) {
            const tbody = document.getElementById('table-body');
            tbody.innerHTML = '';

            stocks.forEach(stock => {
                const tr = document.createElement('tr');
                tr.className = 'stock-row hover:bg-slate-800/50 transition-all';

                tr.innerHTML = `
                    <td class="px-6 py-4 whitespace-nowrap">
                        <a href="stock.php?symbol=${stock.symbol}" class="text-purple-400 hover:text-purple-300 font-semibold transition-colors">
                            ${stock.symbol}
                        </a>
                        <div class="text-xs text-gray-400">${stock.name}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                        ${stock.lastDate}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                        R$ ${formatNumber(stock.lastClose, 2)}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                        ${formatNumber(stock.lastVolume, 0)}
                    </td>
                `;

                tbody.appendChild(tr);
            });
        }

        function formatNumber(num, decimals) {
            return new Intl.NumberFormat('pt-BR', {
                minimumFractionDigits: decimals,
                maximumFractionDigits: decimals
            }).format(num);
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


    </script>
</body>
</html>
