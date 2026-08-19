<?php
/**
 * migrate.php — Script de migração do banco de dados.
 * Execute uma vez no navegador para adicionar novas configurações.
 * Apague ou proteja este arquivo após executar.
 */
require_once 'config.php';

$msgs = [];

try {
    // Adiciona 'gira_titulo' se não existir
    $pdo->exec("INSERT IGNORE INTO configuracoes (chave, valor) VALUES ('gira_titulo', '')");
    $msgs[] = '✅ Configuração <strong>gira_titulo</strong> verificada/inserida com sucesso.';
} catch (PDOException $e) {
    $msgs[] = '❌ Erro: ' . htmlspecialchars($e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Migração do Banco | Templo TUDO TEKEM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body class="p-5">
    <div class="glass-panel mx-auto" style="max-width: 600px;">
        <h2 class="temple-title h3 mb-4">Migração do Banco de Dados</h2>
        <?php foreach ($msgs as $m): ?>
            <p><?= $m ?></p>
        <?php endforeach; ?>
        <hr class="my-4 border-secondary">
        <p class="text-muted small">
            ⚠️ <strong>Apague este arquivo</strong> (<code>migrate.php</code>) após concluir a migração por segurança.
        </p>
        <a href="admin.php" class="btn btn-confirm">Ir para o Painel Admin</a>
    </div>
</body>
</html>
