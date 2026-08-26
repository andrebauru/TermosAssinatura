<?php
require_once 'config.php';

$gira_imagem = '';
$gira_titulo  = '';
try {
    $stmt = $pdo->query("SELECT chave, valor FROM configuracoes WHERE chave IN ('gira_imagem','gira_titulo')");
    foreach ($stmt->fetchAll(PDO::FETCH_KEY_PAIR) as $k => $v) {
        if ($k === 'gira_imagem') $gira_imagem = $v;
        if ($k === 'gira_titulo')  $gira_titulo  = $v;
    }
} catch (PDOException $e) {
    // Ignora erro de conexão temporariamente
}

// Fallback para a logo do Templo
$gira_ativa = 'Files/Logo TT TEKEM.png';
$tem_gira   = false;
if (!empty($gira_imagem) && file_exists($gira_imagem)) {
    $gira_ativa = $gira_imagem;
    $tem_gira   = true;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="TEKEM">
    <meta name="theme-color" content="#0f0f0f">
    <title>Check-in | Templo TUDO TEKEM</title>
    <!-- PWA Manifest -->
    <link rel="manifest" href="manifest.json">
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

    <!-- Botão de Saída (protegido por senha) -->
    <button class="btn-exit" id="exitBtn" title="Sair do modo quiosque">
        <i class="fa-solid fa-right-from-bracket"></i> Sair
    </button>

    <!-- Modal de Senha para Saída -->
    <div class="exit-modal-overlay" id="exitModalOverlay">
        <div class="exit-modal-box">
            <div class="exit-icon"><i class="fa-solid fa-lock"></i></div>
            <h3>Saída Restrita</h3>
            <p>Digite a senha para sair do modo quiosque.</p>
            <input
                type="password"
                class="exit-pin-input"
                id="exitPinInput"
                maxlength="10"
                placeholder="••••"
                autocomplete="off"
            >
            <div class="exit-error-msg" id="exitErrorMsg"></div>
            <div class="d-flex gap-2 mt-3">
                <button class="btn btn-outline-secondary flex-fill" id="exitCancelBtn">Cancelar</button>
                <button class="btn btn-confirm flex-fill" id="exitConfirmBtn">
                    <i class="fa-solid fa-unlock me-1"></i> Confirmar
                </button>
            </div>
        </div>
    </div>

    <div class="container kiosk-container">
        <!-- Cabeçalho -->
        <header class="text-center pt-4 mb-3">
            <h1 class="temple-title display-5 mb-1">Templo TUDO TEKEM</h1>
            <p class="small mb-0" style="letter-spacing: 3px; text-transform: uppercase; color: var(--text-muted);">
                Quimbanda &bull; Umbanda &bull; Wilhelm Cardoso
            </p>
        </header>

        <!-- Conteúdo Central -->
        <main class="text-center my-auto">
            <div class="glass-panel mx-auto" style="max-width: 640px;">
                <h2 class="h4 mb-3 text-white"><?= $tem_gira ? 'Gira de Hoje' : 'Bem-vindo ao Templo' ?></h2>

                <!-- Título da Gira (se definido) -->
                <?php if ($tem_gira && !empty($gira_titulo)): ?>
                    <div class="mb-3">
                        <span class="gira-titulo-badge">
                            <i class="fa-solid fa-fire"></i><?= htmlspecialchars($gira_titulo) ?>
                        </span>
                    </div>
                <?php endif; ?>

                <!-- Frame da Imagem da Gira ou Logo -->
                <div class="gira-frame <?= !$tem_gira ? 'border-0 bg-transparent shadow-none' : '' ?>">
                    <img src="<?= htmlspecialchars($gira_ativa) ?>" alt="<?= $tem_gira ? 'Gira de Hoje' : 'Logo Templo' ?>" class="gira-image" style="<?= !$tem_gira ? 'object-fit: contain; opacity: 0.95; filter: drop-shadow(0 0 15px rgba(212, 175, 55, 0.4));' : '' ?>">
                </div>

                <!-- Separador -->
                <hr class="section-divider">

                <!-- ★ BOTÃO DE AÇÃO — ACIMA DOS CARDS ★ -->
                <div class="mb-4">
                    <a href="termos.php" class="btn btn-pulsate d-inline-block text-decoration-none">
                        Toque aqui para assinar <i class="fa-solid fa-signature ms-2"></i>
                    </a>
                </div>

                <!-- Mestres da Casa -->
                <div class="mb-3">
                    <p class="section-label"><i class="fa-solid fa-star me-1"></i>Mestres da Casa</p>
                    <div class="masters-section">
                        <div class="master-card">
                            <span class="master-icon"><i class="fa-solid fa-crown"></i></span>
                            <span class="master-name">Mestre Will</span>
                            <span class="master-role">Wilhelm Cardoso</span>
                        </div>
                        <div class="master-card">
                            <span class="master-icon"><i class="fa-solid fa-hat-wizard"></i></span>
                            <span class="master-name">Tatá André</span>
                            <span class="master-role">Guardião do Templo</span>
                        </div>
                    </div>
                </div>

                <!-- Entidades Chefes -->
                <div class="mb-3">
                    <p class="section-label"><i class="fa-solid fa-khanda me-1"></i>Entidades Chefes</p>
                    <div class="masters-section">
                        <div class="entity-card">
                            <span class="entity-icon"><i class="fa-solid fa-skull-crossbones"></i></span>
                            <span class="entity-name">Exu Mirim</span>
                            <span class="entity-role">Entidade Chefe</span>
                        </div>
                        <div class="entity-card">
                            <!-- Ícone de navalha SVG customizado -->
                            <span class="entity-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="1.5rem" height="1.5rem" fill="currentColor" style="display:inline-block;vertical-align:middle;">
                                    <path d="M20.5 2C19.12 2 18 3.12 18 4.5c0 .68.28 1.29.73 1.73L5.46 19.5 4 21l1.5.5 1.04-1.04 1.27 1.27.71-.71-1.27-1.27 1.77-1.77 1.27 1.27.71-.71-1.27-1.27 1.77-1.77 1.27 1.27.71-.71-1.27-1.27L20.5 7c.39.0.76-.08 1.1-.22C22.41 6.36 23 5.5 23 4.5 23 3.12 21.88 2 20.5 2zm0 3.5c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1z"/>
                                </svg>
                            </span>
                            <span class="entity-name">Zé Navalha</span>
                            <span class="entity-role">Entidade Chefe</span>
                        </div>
                    </div>
                </div>

            </div>
        </main>

        <!-- Rodapé -->
        <footer class="text-center small pb-2" style="color: var(--text-muted);">
            &copy; <?= date('Y') ?> Templo TUDO TEKEM. Todos os direitos reservados.
        </footer>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Módulo de Quiosque (PWA + fullscreen persistente + wake lock) -->
    <script src="kiosk.js"></script>

    <script>
        /* ── Fullscreen Toggle (botão manual) ── */
        const fullscreenToggle = document.getElementById('fullscreenToggle');
        const fullscreenIcon   = document.getElementById('fullscreenIcon');

        fullscreenToggle.addEventListener('click', () => {
            if (!document.fullscreenElement) {
                KioskMode.enter();
            }
            // Não permite sair pelo botão — apenas pela senha
        });

        /* ── Exit Modal (senha protegida) ── */
        const EXIT_PASSWORD    = '2307';
        const exitBtn          = document.getElementById('exitBtn');
        const exitModalOverlay = document.getElementById('exitModalOverlay');
        const exitPinInput     = document.getElementById('exitPinInput');
        const exitErrorMsg     = document.getElementById('exitErrorMsg');
        const exitCancelBtn    = document.getElementById('exitCancelBtn');
        const exitConfirmBtn   = document.getElementById('exitConfirmBtn');

        function openExitModal() {
            exitPinInput.value = '';
            exitErrorMsg.textContent = '';
            exitPinInput.classList.remove('error');
            exitModalOverlay.classList.add('active');
            setTimeout(() => exitPinInput.focus(), 100);
        }

        function closeExitModal() {
            exitModalOverlay.classList.remove('active');
        }

        function tryExit() {
            if (exitPinInput.value === EXIT_PASSWORD) {
                closeExitModal();
                KioskMode.exit();    // libera o fullscreen com permissão
            } else {
                exitPinInput.classList.add('error');
                exitErrorMsg.textContent = 'Senha incorreta. Tente novamente.';
                exitPinInput.value = '';
                setTimeout(() => exitPinInput.classList.remove('error'), 400);
            }
        }

        exitBtn.addEventListener('click', openExitModal);
        exitCancelBtn.addEventListener('click', closeExitModal);
        exitConfirmBtn.addEventListener('click', tryExit);

        exitPinInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') tryExit();
            // Escape no campo de senha só fecha o modal (não sai do fullscreen)
            if (e.key === 'Escape') { e.stopPropagation(); closeExitModal(); }
        });

        // Fechar clicando fora do box
        exitModalOverlay.addEventListener('click', (e) => {
            if (e.target === exitModalOverlay) closeExitModal();
        });

        /* ── Auto-inicia o modo quiosque se já estiver em fullscreen ── */
        if (document.fullscreenElement) {
            KioskMode.enter();
        }
    </script>
</body>
</html>
