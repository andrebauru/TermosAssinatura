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
$error_message = '';

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

    // Ação: Upload de Imagem da Gira
    if ($action === 'upload_gira') {
        if (isset($_FILES['gira_file']) && $_FILES['gira_file']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['gira_file']['tmp_name'];
            $file_name = $_FILES['gira_file']['name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            $allowed_exts = ['jpg', 'jpeg', 'png', 'webp'];
            
            if (in_array($file_ext, $allowed_exts)) {
                // Nome único para evitar problemas de cache do navegador
                $new_file_name = 'gira_' . time() . '.' . $file_ext;
                $dest_path = 'uploads/' . $new_file_name;
                
                if (move_uploaded_file($file_tmp, $dest_path)) {
                    try {
                        // Opcional: Apagar imagem anterior se ela existir
                        $stmt_prev = $pdo->query("SELECT valor FROM configuracoes WHERE chave = 'gira_imagem'");
                        $prev_img = $stmt_prev->fetchColumn();
                        if (!empty($prev_img) && file_exists($prev_img)) {
                            @unlink($prev_img);
                        }

                        // Atualiza no banco
                        $stmt = $pdo->prepare("UPDATE configuracoes SET valor = :valor WHERE chave = 'gira_imagem'");
                        $stmt->execute([':valor' => $dest_path]);
                        $success_message = 'Imagem da Gira do Dia atualizada com sucesso!';
                    } catch (PDOException $e) {
                        $error_message = 'Erro ao salvar a imagem no banco de dados: ' . $e->getMessage();
                    }
                } else {
                    $error_message = 'Erro ao mover o arquivo enviado para o diretório de destino.';
                }
            } else {
                $error_message = 'Formato inválido. Apenas imagens JPG, PNG e WEBP são aceitas.';
            }
        } else {
            $error_message = 'Nenhum arquivo enviado ou erro no upload.';
        }
    }

    // Ação: Remover Imagem da Gira
    if ($action === 'remove_gira') {
        try {
            $stmt_prev = $pdo->query("SELECT valor FROM configuracoes WHERE chave = 'gira_imagem'");
            $prev_img = $stmt_prev->fetchColumn();
            if (!empty($prev_img) && file_exists($prev_img)) {
                @unlink($prev_img);
            }

            $stmt = $pdo->prepare("UPDATE configuracoes SET valor = '' WHERE chave = 'gira_imagem'");
            $stmt->execute();
            $success_message = 'Imagem da Gira removida. O sistema voltou a exibir a logo padrão.';
        } catch (PDOException $e) {
            $error_message = 'Erro ao atualizar o banco de dados: ' . $e->getMessage();
        }
    }
}

// Recupera informações atuais do banco de dados (se logado)
$termo_atual = '';
$gira_imagem_atual = '';
$visitantes = [];

if ($is_logged) {
    try {
        // Recupera termos
        $stmt = $pdo->query("SELECT valor FROM configuracoes WHERE chave = 'termo_texto'");
        $termo_atual = $stmt->fetchColumn();

        // Recupera imagem da gira
        $stmt_img = $pdo->query("SELECT valor FROM configuracoes WHERE chave = 'gira_imagem'");
        $gira_imagem_atual = $stmt_img->fetchColumn();

        // Recupera lista de visitantes assinados
        $stmt_vis = $pdo->query("SELECT id, nome, sobrenome, data_hora FROM visitantes ORDER BY data_hora DESC");
        $visitantes = $stmt_vis->fetchAll();
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
    <!-- Custom Style (Aproveitando o tema do templo) -->
    <link href="style.css" rel="stylesheet">
    <style>
        .admin-card {
            background: rgba(20, 20, 20, 0.85);
            border: 1px solid rgba(212, 175, 55, 0.4);
        }
        .nav-tabs .nav-link {
            color: var(--text-muted);
            border: none;
            border-bottom: 2px solid transparent;
            font-weight: 500;
        }
        .nav-tabs .nav-link.active {
            background-color: transparent !important;
            color: var(--gold) !important;
            border: none;
            border-bottom: 2px solid var(--gold);
        }
        .table-dark {
            background-color: transparent !important;
        }
        .table-dark th {
            color: var(--gold);
            border-bottom: 2px solid var(--gold);
        }
        .table-dark td {
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .group-header {
            background-color: rgba(140, 20, 20, 0.25);
            border-left: 4px solid var(--gold);
            padding: 0.6rem 1rem;
            margin: 1.5rem 0 0.8rem 0;
            border-radius: 4px;
            font-weight: bold;
            color: var(--text-light);
        }
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
            <p class="text-uppercase tracking-widest text-muted small">Templo TUDO TEKEM</p>
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
                <div class="col-lg-10">
                    
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
                            <span class="text-white fs-5"><i class="fa-solid fa-user-shield text-warning me-2"></i>Sessão Admin Ativa</span>
                            <a href="admin.php?action=logout" class="btn btn-outline-danger btn-sm">
                                Sair <i class="fa-solid fa-right-from-bracket ms-1"></i>
                            </a>
                        </div>

                        <!-- Abas de Navegação -->
                        <ul class="nav nav-tabs mb-4" id="adminTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="historico-tab" data-bs-toggle="tab" data-bs-target="#historico" type="button" role="tab" aria-controls="historico" aria-selected="true">
                                    <i class="fa-solid fa-clock-history me-2"></i>Histórico de Assinaturas
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="termo-tab" data-bs-toggle="tab" data-bs-target="#termo" type="button" role="tab" aria-controls="termo" aria-selected="false">
                                    <i class="fa-solid fa-file-signature me-2"></i>Editar Termos
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="gira-tab" data-bs-toggle="tab" data-bs-target="#gira" type="button" role="tab" aria-controls="gira" aria-selected="false">
                                    <i class="fa-solid fa-image me-2"></i>Gira do Dia
                                </button>
                            </li>
                        </ul>

                        <!-- Conteúdo das Abas -->
                        <div class="tab-content" id="adminTabsContent">
                            
                            <!-- Aba 1: Histórico de Visitantes -->
                            <div class="tab-pane fade show active" id="historico" role="tabpanel" aria-labelledby="historico-tab">
                                <h3 class="h5 mb-4 text-warning">Visitantes Registrados</h3>
                                
                                <?php if (empty($visitantes)): ?>
                                    <div class="text-center py-5 text-muted">
                                        <i class="fa-solid fa-users-slash d-block fs-1 mb-3"></i>
                                        Nenhum visitante assinou o termo até o momento.
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive" style="max-height: 50vh; overflow-y: auto;">
                                        <?php
                                        // Agrupamento de visitantes por data
                                        $data_atual_agrupamento = '';
                                        foreach ($visitantes as $v):
                                            $data_checkin = date('Y-m-d', strtotime($v['data_hora']));
                                            $data_checkin_formatada = date('d/m/Y', strtotime($v['data_hora']));
                                            
                                            if ($data_checkin !== $data_atual_agrupamento) {
                                                if ($data_atual_agrupamento !== '') {
                                                    echo '</tbody></table>'; // Fecha tabela anterior se houver
                                                }
                                                $data_atual_agrupamento = $data_checkin;
                                                echo "<div class='group-header'><i class='fa-regular fa-calendar-days me-2'></i>{$data_checkin_formatada}</div>";
                                                echo '<table class="table table-dark table-striped table-hover align-middle mb-4">
                                                        <thead>
                                                            <tr>
                                                                <th style="width: 25%">ID</th>
                                                                <th style="width: 45%">Nome Completo</th>
                                                                <th style="width: 30%">Horário de Entrada</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>';
                                            }
                                            $horario_checkin = date('H:i:s', strtotime($v['data_hora']));
                                            $nome_completo = $v['nome'] . ' ' . $v['sobrenome'];
                                        ?>
                                            <tr>
                                                <td class="text-muted small"><?= htmlspecialchars($v['id']) ?></td>
                                                <td class="fw-bold text-white"><?= htmlspecialchars($nome_completo) ?></td>
                                                <td><i class="fa-regular fa-clock text-warning me-2"></i><?= htmlspecialchars($horario_checkin) ?></td>
                                            </tr>
                                        <?php endforeach; 
                                        if ($data_atual_agrupamento !== '') {
                                            echo '</tbody></table>'; // Fecha última tabela
                                        }
                                        ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Aba 2: Editar Termos -->
                            <div class="tab-pane fade" id="termo" role="tabpanel" aria-labelledby="termo-tab">
                                <h3 class="h5 mb-3 text-warning">Gerenciar Texto do Termo</h3>
                                <p class="text-muted small mb-3">Você pode utilizar marcação HTML (tags como &lt;strong&gt;, &lt;p&gt;, &lt;ol&gt;, &lt;li&gt;) para manter a formatação visual na página de exibição.</p>
                                
                                <form action="admin.php" method="POST">
                                    <input type="hidden" name="admin_action" value="save_terms">
                                    <div class="mb-4">
                                        <textarea class="form-control" name="termo_texto" rows="12" style="font-family: monospace; font-size: 0.95rem; background: rgba(10,10,10,0.85); color: #fff;" required><?= htmlspecialchars($termo_atual) ?></textarea>
                                    </div>
                                    <div class="text-end">
                                        <button type="submit" class="btn btn-confirm px-4">
                                            <i class="fa-solid fa-save me-2"></i>Salvar Termos
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <!-- Aba 3: Gira do Dia -->
                            <div class="tab-pane fade" id="gira" role="tabpanel" aria-labelledby="gira-tab">
                                <h3 class="h5 mb-4 text-warning">Imagem da Gira do Dia</h3>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-4">
                                        <h4 class="h6 text-muted mb-3">Imagem Ativa</h4>
                                        <?php if (!empty($gira_imagem_atual) && file_exists($gira_imagem_atual)): ?>
                                            <div class="gira-frame text-center" style="max-width: 100%;">
                                                <img src="<?= htmlspecialchars($gira_imagem_atual) ?>" alt="Gira do Dia" class="gira-image" style="height: 250px;">
                                            </div>
                                            <form action="admin.php" method="POST" class="mt-3 text-center">
                                                <input type="hidden" name="admin_action" value="remove_gira">
                                                <button type="submit" class="btn btn-refuse btn-sm">
                                                    <i class="fa-solid fa-trash-can me-2"></i>Remover Imagem e Usar Logo Padrão
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <div class="p-4 border border-secondary rounded text-center text-muted" style="background: rgba(0,0,0,0.25);">
                                                <i class="fa-solid fa-image-portrait d-block fs-1 mb-2"></i>
                                                Nenhuma imagem de gira cadastrada.<br>
                                                O sistema exibirá a logo padrão <strong>Logo TT TEKEM.png</strong>.
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-6">
                                        <h4 class="h6 text-muted mb-3">Subir Nova Imagem</h4>
                                        <div class="p-4 border border-secondary rounded" style="background: rgba(0,0,0,0.25);">
                                            <form action="admin.php" method="POST" enctype="multipart/form-data">
                                                <input type="hidden" name="admin_action" value="upload_gira">
                                                <div class="mb-4">
                                                    <label for="gira_file" class="form-label">Selecione uma imagem (JPG, PNG, WEBP)</label>
                                                    <input class="form-control" type="file" id="gira_file" name="gira_file" accept=".jpg,.jpeg,.png,.webp" required>
                                                </div>
                                                <button type="submit" class="btn btn-confirm w-100">
                                                    <i class="fa-solid fa-cloud-arrow-up me-2"></i>Fazer Upload da Imagem
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div> <!-- /tab-content -->
                    </div> <!-- /glass-panel -->
                </div>
            </div>
        <?php endif; ?>

        <!-- Rodapé -->
        <footer class="text-center text-muted small mt-4">
            &copy; <?= date('Y') ?> Templo TUDO TEKEM • Painel Administrativo
        </footer>
    </div>

    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
