<?php

require_once __DIR__ . '/../config/index.php';

$symbols = STOCK_SYMBOLS;

function deleteDirectory(string $dir): bool
{
    if (!is_dir($dir)) {
        return false;
    }

    $iterator = new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS);
    $files = new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::CHILD_FIRST);

    foreach ($files as $file) {
        $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
    }

    return rmdir($dir);
}

foreach ($symbols as $symbol => $name) {
    echo "Processando {$name} ({$symbol})...\n";
    $dir = __DIR__ . "/../data/{$symbol}";

    if (!is_dir($dir)) {
        echo "  ✗ Diretório não encontrado: {$dir}\n\n";
        continue;
    }

    if (deleteDirectory($dir)) {
        echo "  ✓ Diretório removido: {$dir}\n\n";
    } else {
        echo "  ✗ Falha ao remover: {$dir}\n\n";
    }
}

echo "Processo concluído!\n";
