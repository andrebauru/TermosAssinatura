<?php
/**
 * migrate.php — Script de migração do banco de dados.
 * Execute uma vez no navegador para adicionar novas colunas e configurações.
 * Apague ou proteja este arquivo após executar.
 */
require_once 'config.php';

$msgs = [];

// ── Migração 1: Adicionar 'gira_titulo' na tabela configuracoes ──
try {
    $pdo->exec("INSERT IGNORE INTO configuracoes (chave, valor) VALUES ('gira_titulo', '')");
    $msgs[] = '✅ Configuração <strong>gira_titulo</strong> verificada/inserida com sucesso.';
} catch (PDOException $e) {
    $msgs[] = '❌ Erro ao inserir gira_titulo: ' . htmlspecialchars($e->getMessage());
}

// ── Migração 2: Adicionar coluna 'latitude' na tabela visitantes ──
try {
    $pdo->exec("ALTER TABLE visitantes ADD COLUMN latitude DECIMAL(10,8) DEFAULT NULL");
    $msgs[] = '✅ Coluna <strong>latitude</strong> adicionada à tabela visitantes.';
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        $msgs[] = '⏭️ Coluna <strong>latitude</strong> já existe — ignorada.';
    } else {
        $msgs[] = '❌ Erro ao adicionar latitude: ' . htmlspecialchars($e->getMessage());
    }
}

// ── Migração 3: Adicionar coluna 'longitude' na tabela visitantes ──
try {
    $pdo->exec("ALTER TABLE visitantes ADD COLUMN longitude DECIMAL(11,8) DEFAULT NULL");
    $msgs[] = '✅ Coluna <strong>longitude</strong> adicionada à tabela visitantes.';
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        $msgs[] = '⏭️ Coluna <strong>longitude</strong> já existe — ignorada.';
    } else {
        $msgs[] = '❌ Erro ao adicionar longitude: ' . htmlspecialchars($e->getMessage());
    }
}

// ── Migração 4: Adicionar configuração 'solicitar_geolocalizacao' ──
try {
    $pdo->exec("INSERT IGNORE INTO configuracoes (chave, valor) VALUES ('solicitar_geolocalizacao', '0')");
    $msgs[] = '✅ Configuração <strong>solicitar_geolocalizacao</strong> verificada/inserida com sucesso (padrão: desativada).';
} catch (PDOException $e) {
    $msgs[] = '❌ Erro ao inserir solicitar_geolocalizacao: ' . htmlspecialchars($e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Migração do Banco | Templo TUDO TEKEM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body class="p-5">
    <div class="glass-panel mx-auto" style="max-width: 700px;">
        <h2 class="temple-title h3 mb-4"><i class="fa-solid fa-database me-2"></i>Migração do Banco de Dados</h2>

        <div class="mb-4">
            <?php foreach ($msgs as $m): ?>
                <div class="p-2 mb-2 rounded" style="background: rgba(0,0,0,0.3); border: 1px solid rgba(212,175,55,0.15);">
                    <?= $m ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="p-3 rounded mb-4" style="background: rgba(0,0,0,0.3); border: 1px solid rgba(212,175,55,0.15);">
            <h5 class="text-warning mb-3"><i class="fa-solid fa-list-check me-2"></i>Resumo das Migrações</h5>
            <table class="table table-dark table-sm mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Descrição</th>
                        <th>Tipo</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>1</td>
                        <td>Configuração <code>gira_titulo</code></td>
                        <td><span class="badge text-bg-secondary">INSERT IGNORE</span></td>
                    </tr>
                    <tr>
                        <td>2</td>
                        <td>Coluna <code>visitantes.latitude</code></td>
                        <td><span class="badge text-bg-warning text-dark">ALTER TABLE</span></td>
                    </tr>
                    <tr>
                        <td>3</td>
                        <td>Coluna <code>visitantes.longitude</code></td>
                        <td><span class="badge text-bg-warning text-dark">ALTER TABLE</span></td>
                    </tr>
                    <tr>
                        <td>4</td>
                        <td>Configuração <code>solicitar_geolocalizacao</code></td>
                        <td><span class="badge text-bg-secondary">INSERT IGNORE</span></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <hr class="my-4 border-secondary">
        <p class="text-muted small">
            ⚠️ <strong>Apague este arquivo</strong> (<code>migrate.php</code>) após concluir a migração por segurança.
        </p>
        <a href="admin.php" class="btn btn-confirm"><i class="fa-solid fa-arrow-right me-2"></i>Ir para o Painel Admin</a>
    </div>
</body>
</html>
