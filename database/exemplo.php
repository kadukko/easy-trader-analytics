<?php
/**
 * Arquivo de Exemplo - Uso do Banco de Dados
 * 
 * Este arquivo demonstra como utilizar a configuração do banco de dados
 * para realizar operações CRUD (Create, Read, Update, Delete)
 */

// Inclui o arquivo de configuração
require_once 'config.php';

// Obtém a conexão com o banco de dados
$db = getDB();

echo "<h1>Exemplos de Uso do Banco de Dados</h1>";

// ==========================================
// 1. CRIAR TABELA (Executar apenas uma vez)
// ==========================================
try {
    $sql = "CREATE TABLE IF NOT EXISTS usuarios (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        idade INT,
        criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    $db->exec($sql);
    echo "<p>✓ Tabela 'usuarios' criada com sucesso!</p>";
    
} catch (PDOException $e) {
    echo "<p>Erro ao criar tabela: " . $e->getMessage() . "</p>";
}

// ==========================================
// 2. INSERIR DADOS (CREATE)
// ==========================================
try {
    $sql = "INSERT INTO usuarios (nome, email, idade) VALUES (?, ?, ?)";
    $stmt = $db->prepare($sql);
    
    // Inserir primeiro usuário
    $stmt->execute(['João Silva', 'joao@email.com', 28]);
    echo "<p>✓ Usuário João inserido com sucesso! ID: " . $db->lastInsertId() . "</p>";
    
    // Inserir segundo usuário
    $stmt->execute(['Maria Santos', 'maria@email.com', 32]);
    echo "<p>✓ Usuária Maria inserida com sucesso! ID: " . $db->lastInsertId() . "</p>";
    
    // Inserir terceiro usuário
    $stmt->execute(['Pedro Oliveira', 'pedro@email.com', 25]);
    echo "<p>✓ Usuário Pedro inserido com sucesso! ID: " . $db->lastInsertId() . "</p>";
    
} catch (PDOException $e) {
    echo "<p>Erro ao inserir: " . $e->getMessage() . "</p>";
}

// ==========================================
// 3. CONSULTAR TODOS OS DADOS (READ ALL)
// ==========================================
echo "<h2>Lista de Todos os Usuários</h2>";
try {
    $sql = "SELECT * FROM usuarios ORDER BY id";
    $stmt = $db->query($sql);
    $usuarios = $stmt->fetchAll();
    
    if (count($usuarios) > 0) {
        echo "<table border='1' cellpadding='10'>";
        echo "<tr><th>ID</th><th>Nome</th><th>Email</th><th>Idade</th><th>Criado em</th></tr>";
        
        foreach ($usuarios as $usuario) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($usuario['id']) . "</td>";
            echo "<td>" . htmlspecialchars($usuario['nome']) . "</td>";
            echo "<td>" . htmlspecialchars($usuario['email']) . "</td>";
            echo "<td>" . htmlspecialchars($usuario['idade']) . "</td>";
            echo "<td>" . htmlspecialchars($usuario['criado_em']) . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>Nenhum usuário encontrado.</p>";
    }
    
} catch (PDOException $e) {
    echo "<p>Erro ao consultar: " . $e->getMessage() . "</p>";
}

// ==========================================
// 4. CONSULTAR UM REGISTRO (READ ONE)
// ==========================================
echo "<h2>Buscar Usuário por ID</h2>";
try {
    $sql = "SELECT * FROM usuarios WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([1]);
    $usuario = $stmt->fetch();
    
    if ($usuario) {
        echo "<p><strong>ID:</strong> " . htmlspecialchars($usuario['id']) . "</p>";
        echo "<p><strong>Nome:</strong> " . htmlspecialchars($usuario['nome']) . "</p>";
        echo "<p><strong>Email:</strong> " . htmlspecialchars($usuario['email']) . "</p>";
        echo "<p><strong>Idade:</strong> " . htmlspecialchars($usuario['idade']) . "</p>";
    } else {
        echo "<p>Usuário não encontrado.</p>";
    }
    
} catch (PDOException $e) {
    echo "<p>Erro ao buscar: " . $e->getMessage() . "</p>";
}

// ==========================================
// 5. ATUALIZAR DADOS (UPDATE)
// ==========================================
try {
    $sql = "UPDATE usuarios SET idade = ?, nome = ? WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([29, 'João Silva Junior', 1]);
    
    echo "<p>✓ Usuário atualizado! Linhas afetadas: " . $stmt->rowCount() . "</p>";
    
} catch (PDOException $e) {
    echo "<p>Erro ao atualizar: " . $e->getMessage() . "</p>";
}

// ==========================================
// 6. BUSCAR COM FILTRO (SEARCH)
// ==========================================
echo "<h2>Buscar Usuários com Idade Maior que 25</h2>";
try {
    $sql = "SELECT * FROM usuarios WHERE idade > ? ORDER BY nome";
    $stmt = $db->prepare($sql);
    $stmt->execute([25]);
    $resultados = $stmt->fetchAll();
    
    echo "<ul>";
    foreach ($resultados as $usuario) {
        echo "<li>" . htmlspecialchars($usuario['nome']) . " - " . htmlspecialchars($usuario['idade']) . " anos</li>";
    }
    echo "</ul>";
    
} catch (PDOException $e) {
    echo "<p>Erro ao buscar: " . $e->getMessage() . "</p>";
}

// ==========================================
// 7. CONTAR REGISTROS (COUNT)
// ==========================================
try {
    $sql = "SELECT COUNT(*) as total FROM usuarios";
    $stmt = $db->query($sql);
    $resultado = $stmt->fetch();
    
    echo "<p><strong>Total de usuários:</strong> " . $resultado['total'] . "</p>";
    
} catch (PDOException $e) {
    echo "<p>Erro ao contar: " . $e->getMessage() . "</p>";
}

// ==========================================
// 8. TRANSAÇÃO (Para operações múltiplas)
// ==========================================
try {
    // Inicia a transação
    $db->beginTransaction();
    
    $sql = "INSERT INTO usuarios (nome, email, idade) VALUES (?, ?, ?)";
    $stmt = $db->prepare($sql);
    $stmt->execute(['Ana Costa', 'ana@email.com', 30]);
    $stmt->execute(['Carlos Lima', 'carlos@email.com', 35]);
    
    // Confirma a transação
    $db->commit();
    echo "<p>✓ Transação concluída com sucesso!</p>";
    
} catch (PDOException $e) {
    // Reverte a transação em caso de erro
    $db->rollBack();
    echo "<p>Erro na transação: " . $e->getMessage() . "</p>";
}

// ==========================================
// 9. DELETAR REGISTRO (DELETE)
// ==========================================
// Descomente as linhas abaixo para deletar um usuário
/*
try {
    $sql = "DELETE FROM usuarios WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([5]);
    
    echo "<p>✓ Usuário deletado! Linhas afetadas: " . $stmt->rowCount() . "</p>";
    
} catch (PDOException $e) {
    echo "<p>Erro ao deletar: " . $e->getMessage() . "</p>";
}
*/

// ==========================================
// 10. BUSCA COM LIKE (Pesquisa parcial)
// ==========================================
echo "<h2>Buscar Usuários com Nome Contendo 'Silva'</h2>";
try {
    $sql = "SELECT * FROM usuarios WHERE nome LIKE ?";
    $stmt = $db->prepare($sql);
    $stmt->execute(['%Silva%']);
    $resultados = $stmt->fetchAll();
    
    echo "<ul>";
    foreach ($resultados as $usuario) {
        echo "<li>" . htmlspecialchars($usuario['nome']) . " (" . htmlspecialchars($usuario['email']) . ")</li>";
    }
    echo "</ul>";
    
} catch (PDOException $e) {
    echo "<p>Erro ao buscar: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><em>Exemplos executados com sucesso!</em></p>";
?>
