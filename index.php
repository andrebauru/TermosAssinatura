<?php
require_once 'config.php';

$gira_imagem = '';
try {
    $stmt = $pdo->query("SELECT valor FROM configuracoes WHERE chave = 'gira_imagem'");
    $gira_imagem = $stmt->fetchColumn();
} catch (PDOException $e) {
    // Ignora erro de conexão temporariamente
}

// Fallback para a logo do Templo
$gira_ativa = 'Files/Logo TT TEKEM.png';
$tem_gira = false;
if (!empty($gira_imagem) && file_exists($gira_imagem)) {
    $gira_ativa = $gira_imagem;
    $tem_gira = true;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Check-in | Templo TUDO TEKEM</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <!-- Custom Style -->
    <link href="style.css" rel="stylesheet">
</head>
<body>

    <!-- Logo Animada de Fundo -->
    <div class="bg-logo-container">
        <div class="bg-logo"></div>
    </div>

    <!-- Botão de Tela Cheia -->
    <button class="btn-fullscreen" id="fullscreenToggle" title="Alternar Tela Cheia">
        <i class="fa-solid fa-expand" id="fullscreenIcon"></i>
    </button>

    <div class="container kiosk-container">
        <!-- Cabeçalho -->
        <header class="text-center pt-4 mb-4">
            <h1 class="temple-title display-5 mb-2">Templo TUDO TEKEM</h1>
            <p class="text-uppercase tracking-widest text-muted small" style="letter-spacing: 3px;">
                Quimbanda • Umbanda • Wilhelm Cardoso
            </p>
        </header>

        <!-- Conteúdo Central -->
        <main class="text-center my-auto">
            <div class="glass-panel mx-auto" style="max-width: 600px;">
                <h2 class="h4 mb-4 text-white"><?= $tem_gira ? 'Gira de Hoje' : 'Bem-vindo ao Templo' ?></h2>
                
                <!-- Frame da Imagem da Gira ou Logo -->
                <div class="gira-frame <?= !$tem_gira ? 'border-0 bg-transparent shadow-none' : '' ?>">
                    <img src="<?= htmlspecialchars($gira_ativa) ?>" alt="<?= $tem_gira ? 'Gira de Hoje' : 'Logo Templo' ?>" class="gira-image" style="<?= !$tem_gira ? 'object-fit: contain; opacity: 0.95; filter: drop-shadow(0 0 15px rgba(212, 175, 55, 0.4));' : '' ?>">
                </div>

                <!-- Botão de Ação -->
                <div class="mt-4">
                    <a href="termos.php" class="btn btn-pulsate d-inline-block text-decoration-none">
                        Toque aqui para assinar <i class="fa-solid fa-signature ms-2"></i>
                    </a>
                </div>
            </div>
        </main>

        <!-- Rodapé -->
        <footer class="text-center text-muted small pb-2">
            &copy; <?= date('Y') ?> Templo TUDO TEKEM. Todos os direitos reservados.
        </footer>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Fullscreen API Script -->
    <script>
        const fullscreenToggle = document.getElementById('fullscreenToggle');
        const fullscreenIcon = document.getElementById('fullscreenIcon');

        fullscreenToggle.addEventListener('click', () => {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().then(() => {
                    fullscreenIcon.classList.remove('fa-expand');
                    fullscreenIcon.classList.add('fa-compress');
                }).catch(err => {
                    console.error(`Erro ao tentar ativar o modo tela cheia: ${err.message}`);
                });
            } else {
                document.exitFullscreen().then(() => {
                    fullscreenIcon.classList.remove('fa-compress');
                    fullscreenIcon.classList.add('fa-expand');
                });
            }
        });

        // Evento para atualizar o ícone caso a tela cheia seja desativada de outra forma (ex: tecla Esc)
        document.addEventListener('fullscreenchange', () => {
            if (!document.fullscreenElement) {
                fullscreenIcon.classList.remove('fa-compress');
                fullscreenIcon.classList.add('fa-expand');
            } else {
                fullscreenIcon.classList.remove('fa-expand');
                fullscreenIcon.classList.add('fa-compress');
            }
        });
    </script>
</body>
</html>
