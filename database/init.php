<?php
require_once __DIR__ . '/../config/config.php';

try {
    // Drop existing tables
    $tables = ['sale_items', 'payments', 'sales', 'products', 'customers', 'settings', 'users'];
    foreach ($tables as $table) {
        $pdo->exec("DROP TABLE IF EXISTS $table");
    }
    
    // Ler o arquivo SQL
    $sql = file_get_contents(__DIR__ . '/init.sql');
    
    // Executar as queries
    $pdo->exec($sql);
    
    echo "Banco de dados inicializado com sucesso!\n";
    
} catch(PDOException $e) {
    die("Erro ao inicializar banco de dados: " . $e->getMessage() . "\n");
}
