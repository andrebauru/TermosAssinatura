<?php
session_start();
require_once 'config.php';

// Define a senha de acesso administrativo
define('ADMIN_PASSWORD', '230788');

// Processamento de Logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    unset($_SESSION['admin_logged']);
    session_destroy();
    header('Location: admin.php');
    exit;
}

// Exportar CSV
if (isset($_GET['action']) && $_GET['action'] === 'export_csv' && isset($_SESSION['admin_logged']) && $_SESSION['admin_logged'] === true) {
    try {
        $conditions = [];
        $params = [];
        if (!empty($_GET['data_filtro'])) {
            $conditions[] = "DATE(data_hora) = :data_filtro";
            $params[':data_filtro'] = $_GET['data_filtro'];
        }
        if (!empty($_GET['busca'])) {
            $conditions[] = "(nome LIKE :busca OR sobrenome LIKE :busca)";
            $params[':busca'] = '%' . $_GET['busca'] . '%';
        }
        $where = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
        $stmt = $pdo->prepare("SELECT id, nome, sobrenome, latitude, longitude, data_hora FROM visitantes $where ORDER BY data_hora DESC");
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="visitantes_' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8
        fputcsv($out, ['ID', 'Nome', 'Sobrenome', 'Latitude', 'Longitude', 'Data/Hora'], ';');
        foreach ($rows as $row) {
            fputcsv($out, [$row['id'], $row['nome'], $row['sobrenome'], $row['latitude'] ?? '', $row['longitude'] ?? '', $row['data_hora']], ';');
        }
        fclose($out);
        exit;
    } catch (PDOException $e) {
        die('Erro ao exportar: ' . $e->getMessage());
    }
}

// Processamento de Login
$login_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    if ($password === ADMIN_PASSWORD) {
        $_SESSION['admin_logged'] = true;
        header('Location: admin.php');
        exit;
    } else {
        $login_error = 'Senha incorreta. Tente novamente.';
    }
}

// Verifica se está logado
$is_logged = isset($_SESSION['admin_logged']) && $_SESSION['admin_logged'] === true;

// Processamento de ações administrativas (apenas se logado)
$success_message = '';
$error_message   = '';

if ($is_logged && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['admin_action']) ? $_POST['admin_action'] : '';

    // Ação: Editar Termos
    if ($action === 'save_terms') {
        $novo_termo = isset($_POST['termo_texto']) ? $_POST['termo_texto'] : '';
        try {
            $stmt = $pdo->prepare("UPDATE configuracoes SET valor = :valor WHERE chave = 'termo_texto'");
            $stmt->execute([':valor' => $novo_termo]);
            $success_message = 'Termo de responsabilidade atualizado com sucesso!';
        } catch (PDOException $e) {
            $error_message = 'Erro ao salvar o termo no banco de dados: ' . $e->getMessage();
        }
    }

    // Ação: Upload de Imagem da Gira + Título
    if ($action === 'upload_gira') {
        // Salvar título da gira
        $novo_titulo = isset($_POST['gira_titulo']) ? trim($_POST['gira_titulo']) : '';
        try {
            $stmt_t = $pdo->prepare("UPDATE configuracoes SET valor = :valor WHERE chave = 'gira_titulo'");
            $stmt_t->execute([':valor' => $novo_titulo]);
        } catch (PDOException $e) {
            $error_message = 'Erro ao salvar o título: ' . $e->getMessage();
        }

        // Upload de imagem (opcional — se não enviar arquivo, apenas salva o título)
        if (isset($_FILES['gira_file']) && $_FILES['gira_file']['error'] === UPLOAD_ERR_OK) {
            $file_tmp  = $_FILES['gira_file']['tmp_name'];
            $file_name = $_FILES['gira_file']['name'];
            $file_ext  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            $allowed_exts = ['jpg', 'jpeg', 'png', 'webp'];

            if (in_array($file_ext, $allowed_exts)) {
                $new_file_name = 'gira_' . time() . '.' . $file_ext;
                $dest_path     = 'uploads/' . $new_file_name;

                if (move_uploaded_file($file_tmp, $dest_path)) {
                    try {
                        $stmt_prev = $pdo->query("SELECT valor FROM configuracoes WHERE chave = 'gira_imagem'");
                        $prev_img  = $stmt_prev->fetchColumn();
                        // Não apaga a imagem anterior — fica no histórico
                        $stmt = $pdo->prepare("UPDATE configuracoes SET valor = :valor WHERE chave = 'gira_imagem'");
                        $stmt->execute([':valor' => $dest_path]);

                        // Salvar no histórico de giras
                        $stmt_hist = $pdo->prepare("INSERT INTO giras (titulo, imagem_path) VALUES (:titulo, :imagem)");
                        $stmt_hist->execute([':titulo' => $novo_titulo, ':imagem' => $dest_path]);

                        $success_message = 'Gira do Dia atualizada com sucesso! Título: "' . htmlspecialchars($novo_titulo) . '"';
                    } catch (PDOException $e) {
                        $error_message = 'Erro ao salvar a imagem no banco de dados: ' . $e->getMessage();
                    }
                } else {
                    $error_message = 'Erro ao mover o arquivo enviado para o diretório de destino.';
                }
            } else {
                $error_message = 'Formato inválido. Apenas imagens JPG, PNG e WEBP são aceitas.';
            }
        } elseif (empty($error_message)) {
            // Nenhum arquivo enviado, mas título foi salvo — salva no histórico sem imagem
            try {
                $stmt_hist = $pdo->prepare("INSERT INTO giras (titulo, imagem_path) VALUES (:titulo, NULL)");
                $stmt_hist->execute([':titulo' => $novo_titulo]);
            } catch (PDOException $e) {
                // Ignora se tabela giras não existir ainda
            }
            $success_message = 'Título da Gira atualizado para: "' . htmlspecialchars($novo_titulo) . '"';
        }
    }

    // Ação: Remover Imagem da Gira
    if ($action === 'remove_gira') {
        try {
            $stmt_prev = $pdo->query("SELECT valor FROM configuracoes WHERE chave = 'gira_imagem'");
            $prev_img  = $stmt_prev->fetchColumn();
            if (!empty($prev_img) && file_exists($prev_img)) {
                @unlink($prev_img);
            }
            $stmt = $pdo->prepare("UPDATE configuracoes SET valor = '' WHERE chave = 'gira_imagem'");
            $stmt->execute();
            $stmt_t = $pdo->prepare("UPDATE configuracoes SET valor = '' WHERE chave = 'gira_titulo'");
            $stmt_t->execute();
            $success_message = 'Imagem da Gira removida. O sistema voltará a exibir a logo padrão.';
        } catch (PDOException $e) {
            $error_message = 'Erro ao atualizar o banco de dados: ' . $e->getMessage();
        }
    }

    // Ação: Toggle Geolocalização
    if ($action === 'toggle_geolocalizacao') {
        $novo_valor = isset($_POST['solicitar_geolocalizacao']) ? '1' : '0';
        try {
            $stmt = $pdo->prepare("UPDATE configuracoes SET valor = :valor WHERE chave = 'solicitar_geolocalizacao'");
            $stmt->execute([':valor' => $novo_valor]);
            $success_message = 'Geolocalização ' . ($novo_valor === '1' ? 'ativada' : 'desativada') . ' com sucesso!';
        } catch (PDOException $e) {
            $error_message = 'Erro ao salvar configuração: ' . $e->getMessage();
        }
    }
}

// Recupera informações do banco de dados (se logado)
$termo_atual        = '';
$gira_imagem_atual  = '';
$gira_titulo_atual  = '';
$solicitar_geo      = false;
$visitantes         = [];
$historico_giras    = [];
$total_hoje         = 0;
$total_geral        = 0;
$ultima_assinatura  = null;
$data_filtro        = isset($_GET['data_filtro']) ? $_GET['data_filtro'] : '';
$busca              = isset($_GET['busca']) ? trim($_GET['busca']) : '';

if ($is_logged) {
    try {
        // Recupera configurações
        $stmt = $pdo->query("SELECT chave, valor FROM configuracoes WHERE chave IN ('termo_texto','gira_imagem','gira_titulo','solicitar_geolocalizacao')");
        foreach ($stmt->fetchAll(PDO::FETCH_KEY_PAIR) as $k => $v) {
            if ($k === 'termo_texto')              $termo_atual       = $v;
            if ($k === 'gira_imagem')              $gira_imagem_atual = $v;
            if ($k === 'gira_titulo')              $gira_titulo_atual = $v;
            if ($k === 'solicitar_geolocalizacao') $solicitar_geo     = ($v === '1');
        }

        // Estatísticas
        $stmt_hoje  = $pdo->query("SELECT COUNT(*) FROM visitantes WHERE DATE(data_hora) = CURDATE()");
        $total_hoje = (int)$stmt_hoje->fetchColumn();

        $stmt_total  = $pdo->query("SELECT COUNT(*) FROM visitantes");
        $total_geral = (int)$stmt_total->fetchColumn();

        $stmt_ultima    = $pdo->query("SELECT nome, sobrenome, data_hora FROM visitantes ORDER BY data_hora DESC LIMIT 1");
        $ultima_assinatura = $stmt_ultima->fetch();

        // Recupera visitantes (com filtro de data e busca por nome)
        $conditions = [];
        $params = [];
        if (!empty($data_filtro)) {
            $conditions[] = "DATE(data_hora) = :data_filtro";
            $params[':data_filtro'] = $data_filtro;
        }
        if (!empty($busca)) {
            $conditions[] = "(nome LIKE :busca OR sobrenome LIKE :busca)";
            $params[':busca'] = '%' . $busca . '%';
        }
        $where = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
        $stmt_vis = $pdo->prepare("SELECT id, nome, sobrenome, latitude, longitude, data_hora FROM visitantes $where ORDER BY data_hora DESC");
        $stmt_vis->execute($params);
        $visitantes = $stmt_vis->fetchAll();

        // Recupera histórico de giras
        try {
            $stmt_giras = $pdo->query("SELECT id, titulo, imagem_path, data_gira FROM giras ORDER BY data_gira DESC LIMIT 50");
            $historico_giras = $stmt_giras->fetchAll();
        } catch (PDOException $e) {
            // Tabela giras pode não existir ainda — ignorar
            $historico_giras = [];
        }

    } catch (PDOException $e) {
        $error_message = 'Erro ao conectar e recuperar dados: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Admin | Templo TUDO TEKEM</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <!-- Quill WYSIWYG Editor CSS -->
    <link href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" rel="stylesheet">
    <!-- Custom Style -->
    <link href="style.css" rel="stylesheet">
    <style>
        body { -webkit-user-select: auto; user-select: auto; }
        .nav-tabs { border-bottom: 1px solid rgba(212,175,55,0.2); }
        textarea.form-control { -webkit-user-select: auto; user-select: auto; }
    </style>
</head>
<body class="py-4">

    <!-- Logo Animada de Fundo -->
    <div class="bg-logo-container">
        <div class="bg-logo"></div>
    </div>

    <div class="container position-relative" style="z-index: 10;">
        <!-- Cabeçalho -->
        <header class="text-center mb-4">
            <h1 class="temple-title h2 mb-1">Painel Administrativo</h1>
            <p class="small text-uppercase mb-0" style="letter-spacing: 3px; color: var(--text-muted);">Templo TUDO TEKEM</p>
        </header>

        <?php if (!$is_logged): ?>
            <!-- Tela de Login -->
            <div class="row justify-content-center pt-5">
                <div class="col-md-5">
                    <div class="glass-panel text-center admin-card">
                        <div class="mb-4 text-warning" style="font-size: 3.5rem;">
                            <i class="fa-solid fa-lock"></i>
                        </div>
                        <h2 class="h4 mb-4 text-white">Acesso Restrito</h2>

                        <?php if ($login_error): ?>
                            <div class="alert alert-danger py-2 text-center" role="alert">
                                <i class="fa-solid fa-circle-exclamation me-2"></i><?= htmlspecialchars($login_error) ?>
                            </div>
                        <?php endif; ?>

                        <form action="admin.php" method="POST">
                            <input type="hidden" name="action" value="login">
                            <div class="mb-4">
                                <label for="password" class="form-label d-block text-start">Senha Administrativa</label>
                                <input type="password" class="form-control text-center" id="password" name="password" placeholder="Digite a senha" required autocomplete="off">
                            </div>
                            <button type="submit" class="btn btn-pulsate w-100 py-2 fs-5">
                                Entrar <i class="fa-solid fa-right-to-bracket ms-2"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <!-- Painel Administrativo Autenticado -->
            <div class="row justify-content-center">
                <div class="col-lg-11">

                    <!-- Alertas de Sucesso / Erro -->
                    <?php if ($success_message): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fa-solid fa-circle-check me-2"></i><?= htmlspecialchars($success_message) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fa-solid fa-circle-exclamation me-2"></i><?= htmlspecialchars($error_message) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <div class="glass-panel admin-card p-4">
                        <!-- Barra Superior com botão de logout -->
                        <div class="d-flex justify-content-between align-items-center mb-4 border-bottom border-secondary pb-3">
                            <span class="text-white fs-5">
                                <i class="fa-solid fa-user-shield text-warning me-2"></i>Sessão Admin Ativa
                            </span>
                            <div class="d-flex gap-2">
                                <a href="index.php" class="btn btn-outline-secondary btn-sm" target="_blank">
                                    <i class="fa-solid fa-eye me-1"></i>Ver Quiosque
                                </a>
                                <a href="admin.php?action=logout" class="btn btn-outline-danger btn-sm">
                                    Sair <i class="fa-solid fa-right-from-bracket ms-1"></i>
                                </a>
                            </div>
                        </div>

                        <!-- Abas de Navegação -->
                        <ul class="nav nav-tabs mb-4" id="adminTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="dashboard-tab" data-bs-toggle="tab" data-bs-target="#dashboard" type="button" role="tab">
                                    <i class="fa-solid fa-chart-pie me-2"></i>Dashboard
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="historico-tab" data-bs-toggle="tab" data-bs-target="#historico" type="button" role="tab">
                                    <i class="fa-solid fa-clock-history me-2"></i>Assinaturas
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="gira-tab" data-bs-toggle="tab" data-bs-target="#gira" type="button" role="tab">
                                    <i class="fa-solid fa-fire me-2"></i>Gira do Dia
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="termo-tab" data-bs-toggle="tab" data-bs-target="#termo" type="button" role="tab">
                                    <i class="fa-solid fa-file-signature me-2"></i>Editar Termos
                                </button>
                            </li>
                        </ul>

                        <!-- Conteúdo das Abas -->
                        <div class="tab-content" id="adminTabsContent">

                            <!-- ═══ ABA 1: DASHBOARD ═══ -->
                            <div class="tab-pane fade show active" id="dashboard" role="tabpanel">
                                <h3 class="h5 mb-4 text-warning"><i class="fa-solid fa-gauge-high me-2"></i>Visão Geral</h3>

                                <div class="row g-3 mb-4">
                                    <!-- Stat: Total Hoje -->
                                    <div class="col-md-4">
                                        <div class="stat-card">
                                            <div class="stat-icon"><i class="fa-solid fa-calendar-day"></i></div>
                                            <div class="stat-number"><?= $total_hoje ?></div>
                                            <div class="stat-label">Assinaturas Hoje</div>
                                        </div>
                                    </div>
                                    <!-- Stat: Total Geral -->
                                    <div class="col-md-4">
                                        <div class="stat-card">
                                            <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
                                            <div class="stat-number"><?= $total_geral ?></div>
                                            <div class="stat-label">Total de Assinaturas</div>
                                        </div>
                                    </div>
                                    <!-- Stat: Gira Ativa -->
                                    <div class="col-md-4">
                                        <div class="stat-card">
                                            <div class="stat-icon"><i class="fa-solid fa-fire"></i></div>
                                            <div class="stat-number" style="font-size: 1.1rem; padding-top: 0.5rem;">
                                                <?= !empty($gira_titulo_atual) ? htmlspecialchars($gira_titulo_atual) : '<span style="font-size:0.85rem;color:var(--text-muted)">Sem título</span>' ?>
                                            </div>
                                            <div class="stat-label">Gira do Dia</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Última assinatura -->
                                <?php if ($ultima_assinatura): ?>
                                <div class="p-3 rounded" style="background: rgba(0,0,0,0.3); border: 1px solid rgba(212,175,55,0.15);">
                                    <p class="text-muted small mb-1"><i class="fa-regular fa-clock me-1"></i>Última assinatura registrada:</p>
                                    <p class="text-white mb-0 fs-5 fw-bold">
                                        <?= htmlspecialchars($ultima_assinatura['nome'] . ' ' . $ultima_assinatura['sobrenome']) ?>
                                        <small class="text-muted fw-normal ms-2" style="font-size: 0.8rem;">
                                            <?= date('d/m/Y \à\s H:i', strtotime($ultima_assinatura['data_hora'])) ?>
                                        </small>
                                    </p>
                                </div>
                                <?php else: ?>
                                <div class="text-center py-4 text-muted">
                                    <i class="fa-solid fa-inbox d-block fs-1 mb-3 opacity-50"></i>
                                    Nenhuma assinatura registrada ainda.
                                </div>
                                <?php endif; ?>

                                <!-- Toggle de Geolocalização -->
                                <div class="mt-4 p-3 rounded" style="background: rgba(0,0,0,0.3); border: 1px solid rgba(212,175,55,0.15);">
                                    <form action="admin.php" method="POST" class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                        <input type="hidden" name="admin_action" value="toggle_geolocalizacao">
                                        <div>
                                            <p class="text-white mb-1 fw-bold">
                                                <i class="fa-solid fa-location-dot text-warning me-2"></i>Solicitar Geolocalização
                                            </p>
                                            <p class="text-muted small mb-0">
                                                Quando ativado, o sistema solicita a localização do visitante no check-in.
                                            </p>
                                        </div>
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" role="switch" id="geoSwitch"
                                                       name="solicitar_geolocalizacao" value="1"
                                                       <?= $solicitar_geo ? 'checked' : '' ?>
                                                       style="width: 3rem; height: 1.5rem; cursor: pointer;">
                                                <label class="form-check-label ms-2" for="geoSwitch" style="cursor: pointer;">
                                                    <span class="badge <?= $solicitar_geo ? 'text-bg-success' : 'text-bg-secondary' ?>">
                                                        <?= $solicitar_geo ? 'Ativado' : 'Desativado' ?>
                                                    </span>
                                                </label>
                                            </div>
                                            <button type="submit" class="btn btn-sm btn-outline-warning">
                                                <i class="fa-solid fa-save me-1"></i>Salvar
                                            </button>
                                        </div>
                                    </form>
                                </div>

                                <!-- Atalhos rápidos -->
                                <div class="mt-4">
                                    <p class="text-muted small mb-2">Atalhos rápidos:</p>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <button class="btn btn-sm btn-outline-warning" onclick="document.getElementById('gira-tab').click()">
                                            <i class="fa-solid fa-fire me-1"></i>Configurar Gira
                                        </button>
                                        <button class="btn btn-sm btn-outline-warning" onclick="document.getElementById('historico-tab').click()">
                                            <i class="fa-solid fa-list me-1"></i>Ver Assinaturas
                                        </button>
                                        <a href="admin.php?action=export_csv" class="btn btn-sm btn-outline-success">
                                            <i class="fa-solid fa-file-csv me-1"></i>Exportar Tudo (CSV)
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- ═══ ABA 2: HISTÓRICO DE ASSINATURAS ═══ -->
                            <div class="tab-pane fade" id="historico" role="tabpanel">
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                                    <h3 class="h5 text-warning mb-0"><i class="fa-solid fa-list me-2"></i>Visitantes Registrados</h3>
                                    <div class="d-flex gap-2 align-items-center flex-wrap">
                                        <!-- Exportar CSV (respeita filtros ativos) -->
                                        <?php
                                        $csv_params = [];
                                        if (!empty($data_filtro)) $csv_params[] = 'data_filtro=' . urlencode($data_filtro);
                                        if (!empty($busca)) $csv_params[] = 'busca=' . urlencode($busca);
                                        $csv_query = !empty($csv_params) ? '&' . implode('&', $csv_params) : '';
                                        ?>
                                        <a href="admin.php?action=export_csv<?= $csv_query ?>" class="btn btn-sm btn-outline-success">
                                            <i class="fa-solid fa-file-csv me-1"></i>Exportar CSV
                                        </a>
                                    </div>
                                </div>

                                <!-- Barra de Filtros: Data + Busca por nome -->
                                <div class="p-3 mb-4 rounded" style="background: rgba(0,0,0,0.3); border: 1px solid rgba(212,175,55,0.15);">
                                    <form method="GET" action="admin.php" class="row g-2 align-items-end">
                                        <input type="hidden" name="tab" value="historico">
                                        <!-- Busca por nome -->
                                        <div class="col-md-5">
                                            <label class="form-label small text-muted mb-1">
                                                <i class="fa-solid fa-magnifying-glass me-1"></i>Buscar por nome
                                            </label>
                                            <input type="text" name="busca" value="<?= htmlspecialchars($busca) ?>"
                                                   class="form-control form-control-sm"
                                                   placeholder="Digite o nome ou sobrenome...">
                                        </div>
                                        <!-- Filtro de data -->
                                        <div class="col-md-3">
                                            <label class="form-label small text-muted mb-1">
                                                <i class="fa-regular fa-calendar me-1"></i>Data
                                            </label>
                                            <input type="date" name="data_filtro" value="<?= htmlspecialchars($data_filtro) ?>"
                                                   class="form-control form-control-sm">
                                        </div>
                                        <!-- Botões -->
                                        <div class="col-md-4 d-flex gap-2">
                                            <button type="submit" class="btn btn-sm btn-outline-warning flex-grow-1">
                                                <i class="fa-solid fa-filter me-1"></i>Filtrar
                                            </button>
                                            <?php if (!empty($data_filtro) || !empty($busca)): ?>
                                                <a href="admin.php?tab=historico" class="btn btn-sm btn-outline-secondary">
                                                    <i class="fa-solid fa-xmark me-1"></i>Limpar
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </form>
                                </div>

                                <?php if (empty($visitantes)): ?>
                                    <div class="text-center py-5 text-muted">
                                        <i class="fa-solid fa-users-slash d-block fs-1 mb-3"></i>
                                        Nenhum visitante encontrado<?= !empty($data_filtro) ? ' nesta data.' : ' até o momento.' ?>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive" style="max-height: 55vh; overflow-y: auto;">
                                        <?php
                                        $data_atual_agrupamento = '';
                                        foreach ($visitantes as $v):
                                            $data_checkin           = date('Y-m-d', strtotime($v['data_hora']));
                                            $data_checkin_formatada = date('d/m/Y', strtotime($v['data_hora']));

                                            if ($data_checkin !== $data_atual_agrupamento) {
                                                if ($data_atual_agrupamento !== '') {
                                                    echo '</tbody></table>';
                                                }
                                                $data_atual_agrupamento = $data_checkin;
                                                echo "<div class='group-header'><i class='fa-regular fa-calendar-days me-2'></i>{$data_checkin_formatada}</div>";
                                                echo '<table class="table table-dark table-striped table-hover align-middle mb-4">
                                                        <thead>
                                                            <tr>
                                                                <th style="width: 15%">ID</th>
                                                                <th style="width: 35%">Nome Completo</th>
                                                                <th style="width: 25%">Localização</th>
                                                                <th style="width: 25%">Horário</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>';
                                            }
                                            $horario_checkin = date('H:i:s', strtotime($v['data_hora']));
                                            $nome_completo   = $v['nome'] . ' ' . $v['sobrenome'];
                                            $tem_localizacao = !empty($v['latitude']) && !empty($v['longitude']);
                                        ?>
                                            <tr>
                                                <td class="text-muted small"><?= htmlspecialchars($v['id']) ?></td>
                                                <td class="fw-bold text-white"><?= htmlspecialchars($nome_completo) ?></td>
                                                <td>
                                                    <?php if ($tem_localizacao): ?>
                                                        <a href="https://www.google.com/maps?q=<?= $v['latitude'] ?>,<?= $v['longitude'] ?>" 
                                                           target="_blank" class="text-decoration-none" title="Abrir no Google Maps">
                                                            <span class="badge text-bg-success">
                                                                <i class="fa-solid fa-location-dot me-1"></i>Ver Mapa
                                                            </span>
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="badge text-bg-secondary"><i class="fa-solid fa-location-crosshairs me-1"></i>Não informada</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><i class="fa-regular fa-clock text-warning me-2"></i><?= htmlspecialchars($horario_checkin) ?></td>
                                            </tr>
                                        <?php endforeach;
                                        if ($data_atual_agrupamento !== '') {
                                            echo '</tbody></table>';
                                        }
                                        ?>
                                    </div>
                                    <p class="text-muted small text-end mt-1">
                                        <?= count($visitantes) ?> registro(s) encontrado(s)
                                        <?= !empty($data_filtro) ? 'em ' . date('d/m/Y', strtotime($data_filtro)) : '' ?>
                                        <?= !empty($busca) ? ' para "' . htmlspecialchars($busca) . '"' : '' ?>
                                    </p>
                                <?php endif; ?>
                            </div>

                            <!-- ═══ ABA 3: GIRA DO DIA ═══ -->
                            <div class="tab-pane fade" id="gira" role="tabpanel">
                                <h3 class="h5 mb-4 text-warning"><i class="fa-solid fa-fire me-2"></i>Configurar Gira do Dia</h3>

                                <div class="row g-4">
                                    <!-- Coluna: imagem ativa -->
                                    <div class="col-md-5">
                                        <h4 class="h6 mb-3" style="color: var(--text-muted);">Imagem Ativa</h4>
                                        <?php if (!empty($gira_imagem_atual) && file_exists($gira_imagem_atual)): ?>
                                            <div class="gira-frame text-center" style="max-width: 100%;">
                                                <img src="<?= htmlspecialchars($gira_imagem_atual) ?>" alt="Gira do Dia" class="gira-image" style="height: 220px;">
                                            </div>
                                            <?php if (!empty($gira_titulo_atual)): ?>
                                                <p class="text-center mt-2 small" style="color: var(--gold-light);">
                                                    <i class="fa-solid fa-fire me-1"></i><?= htmlspecialchars($gira_titulo_atual) ?>
                                                </p>
                                            <?php endif; ?>
                                            <form action="admin.php" method="POST" class="mt-3 text-center">
                                                <input type="hidden" name="admin_action" value="remove_gira">
                                                <button type="submit" class="btn btn-refuse btn-sm">
                                                    <i class="fa-solid fa-trash-can me-2"></i>Remover Gira e Usar Logo Padrão
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <div class="p-4 border border-secondary rounded text-center text-muted" style="background: rgba(0,0,0,0.25);">
                                                <i class="fa-solid fa-image-portrait d-block fs-1 mb-2"></i>
                                                Nenhuma imagem cadastrada.<br>
                                                O sistema exibirá a logo padrão <strong>Logo TT TEKEM.png</strong>.
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Coluna: novo upload + título -->
                                    <div class="col-md-7">
                                        <h4 class="h6 mb-3" style="color: var(--text-muted);">Definir Gira do Dia</h4>
                                        <div class="p-4 border border-secondary rounded" style="background: rgba(0,0,0,0.25);">
                                            <form action="admin.php" method="POST" enctype="multipart/form-data">
                                                <input type="hidden" name="admin_action" value="upload_gira">

                                                <!-- Título da Gira -->
                                                <div class="mb-3">
                                                    <label for="gira_titulo" class="form-label">
                                                        <i class="fa-solid fa-tag me-1"></i>Título da Gira
                                                    </label>
                                                    <input type="text" class="form-control" id="gira_titulo" name="gira_titulo"
                                                           placeholder="Ex: Gira de Exu Mirim"
                                                           value="<?= htmlspecialchars($gira_titulo_atual) ?>">
                                                    <div class="form-text" style="color: var(--text-muted);">
                                                        Aparece no quiosque abaixo da imagem.
                                                    </div>
                                                </div>

                                                <!-- Upload de Imagem -->
                                                <div class="mb-4">
                                                    <label for="gira_file" class="form-label">
                                                        <i class="fa-solid fa-image me-1"></i>Imagem da Gira <span class="fw-normal text-muted">(opcional)</span>
                                                    </label>
                                                    <input class="form-control" type="file" id="gira_file" name="gira_file" accept=".jpg,.jpeg,.png,.webp">
                                                    <div class="form-text" style="color: var(--text-muted);">JPG, PNG ou WEBP. Deixe em branco para apenas atualizar o título.</div>
                                                </div>

                                                <button type="submit" class="btn btn-confirm w-100">
                                                    <i class="fa-solid fa-cloud-arrow-up me-2"></i>Salvar Gira do Dia
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <!-- Histórico de Giras Realizadas -->
                                <hr class="border-secondary my-4">
                                <h4 class="h6 mb-3 text-warning"><i class="fa-solid fa-clock-rotate-left me-2"></i>Histórico de Giras</h4>

                                <?php if (empty($historico_giras)): ?>
                                    <div class="text-center py-4 text-muted">
                                        <i class="fa-solid fa-fire-flame-curved d-block fs-1 mb-2 opacity-50"></i>
                                        Nenhuma gira registrada no histórico.
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive" style="max-height: 40vh; overflow-y: auto;">
                                        <table class="table table-dark table-striped table-hover align-middle mb-0">
                                            <thead>
                                                <tr>
                                                    <th style="width: 8%">#</th>
                                                    <th style="width: 37%">Título</th>
                                                    <th style="width: 25%">Imagem</th>
                                                    <th style="width: 30%">Data</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($historico_giras as $g): ?>
                                                <tr>
                                                    <td class="text-muted"><?= $g['id'] ?></td>
                                                    <td class="fw-bold text-white">
                                                        <i class="fa-solid fa-fire text-warning me-1"></i>
                                                        <?= !empty($g['titulo']) ? htmlspecialchars($g['titulo']) : '<span class="text-muted fst-italic">Sem título</span>' ?>
                                                    </td>
                                                    <td>
                                                        <?php if (!empty($g['imagem_path'])): ?>
                                                            <?php if (file_exists($g['imagem_path'])): ?>
                                                                <img src="<?= htmlspecialchars($g['imagem_path']) ?>" alt="Gira" 
                                                                     style="height: 40px; width: 60px; object-fit: cover; border-radius: 4px; border: 1px solid rgba(212,175,55,0.3);">
                                                            <?php else: ?>
                                                                <span class="badge text-bg-secondary"><i class="fa-solid fa-image-slash me-1"></i>Removida</span>
                                                            <?php endif; ?>
                                                        <?php else: ?>
                                                            <span class="badge text-bg-secondary"><i class="fa-solid fa-minus me-1"></i>Sem imagem</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <i class="fa-regular fa-calendar text-warning me-1"></i>
                                                        <?= date('d/m/Y \à\s H:i', strtotime($g['data_gira'])) ?>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <p class="text-muted small text-end mt-1"><?= count($historico_giras) ?> gira(s) no histórico</p>
                                <?php endif; ?>
                            </div>

                            <!-- ═══ ABA 4: EDITAR TERMOS (WYSIWYG) ═══ -->
                            <div class="tab-pane fade" id="termo" role="tabpanel">
                                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                                    <h3 class="h5 text-warning mb-0"><i class="fa-solid fa-file-signature me-2"></i>Editor de Termos</h3>
                                    <span class="badge text-bg-secondary">Editor Visual — sem necessidade de código HTML</span>
                                </div>
                                <p class="small mb-3" style="color: var(--text-muted);">
                                    Use a barra de ferramentas abaixo para formatar o texto. O conteúdo é salvo automaticamente em formato compatível com a página de assinatura.
                                </p>

                                <form action="admin.php" method="POST" id="termosForm">
                                    <input type="hidden" name="admin_action" value="save_terms">
                                    <!-- Campo oculto onde o Quill insere o HTML -->
                                    <input type="hidden" name="termo_texto" id="termo_texto_hidden">

                                    <!-- Editor Quill -->
                                    <div id="quillEditor" style="min-height: 300px;"></div>

                                    <div class="text-end mt-3">
                                        <button type="submit" class="btn btn-confirm px-4" id="salvarTermosBtn">
                                            <i class="fa-solid fa-save me-2"></i>Salvar Termos
                                        </button>
                                    </div>
                                </form>
                            </div>

                        </div><!-- /tab-content -->
                    </div><!-- /glass-panel -->
                </div>
            </div>
        <?php endif; ?>

        <!-- Rodapé -->
        <footer class="text-center small mt-4" style="color: var(--text-muted);">
            &copy; <?= date('Y') ?> Templo TUDO TEKEM &bull; Painel Administrativo
        </footer>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Quill WYSIWYG Editor JS -->
    <script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>

    <?php if ($is_logged): ?>
    <script>
        /* ── Restaurar aba ativa via URL hash ── */
        const urlParams = new URLSearchParams(window.location.search);
        const tabParam  = urlParams.get('tab');
        if (tabParam) {
            const tabEl = document.getElementById(tabParam + '-tab');
            if (tabEl) new bootstrap.Tab(tabEl).show();
        }

        /* ── Quill WYSIWYG Editor ── */
        const initialContent = <?= json_encode($termo_atual) ?>;

        const quill = new Quill('#quillEditor', {
            theme: 'snow',
            modules: {
                toolbar: [
                    [{ 'header': [1, 2, 3, false] }],
                    ['bold', 'italic', 'underline', 'strike'],
                    [{ 'color': [] }, { 'background': [] }],
                    [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                    [{ 'indent': '-1' }, { 'indent': '+1' }],
                    ['blockquote', 'link'],
                    ['clean']
                ]
            },
            placeholder: 'Digite o texto dos termos aqui...'
        });

        // Carrega o conteúdo HTML existente no editor
        quill.clipboard.dangerouslyPasteHTML(initialContent || '');

        // Antes de submeter, copia o HTML do Quill para o campo hidden
        document.getElementById('termosForm').addEventListener('submit', function(e) {
            document.getElementById('termo_texto_hidden').value = quill.root.innerHTML;
        });
    </script>
    <?php endif; ?>
</body>
</html>
