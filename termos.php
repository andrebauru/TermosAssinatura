<?php
require_once 'config.php';

$termo_texto = '';
try {
    $stmt = $pdo->query("SELECT valor FROM configuracoes WHERE chave = 'termo_texto'");
    $termo_texto = $stmt->fetchColumn();
} catch (PDOException $e) {
    // Fallback de segurança se o banco estiver indisponível
    $termo_texto = '<p class="text-danger fw-bold">Erro ao conectar com o banco de dados para recuperar o termo de responsabilidade. Por favor, acione um assistente.</p>';
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
    <meta name="theme-color" content="#0f0f0f">
    <title>Termo de Consentimento | Templo TUDO TEKEM</title>
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

    <div class="container kiosk-container py-3">
        <!-- Cabeçalho -->
        <header class="text-center pt-2 mb-3">
            <h1 class="temple-title h3 mb-1">Templo TUDO TEKEM</h1>
            <p class="text-uppercase tracking-widest text-muted small mb-0" style="letter-spacing: 2px;">
                Consentimento Digital e Legal
            </p>
        </header>

        <!-- Painel Principal -->
        <main class="my-auto">
            <div class="glass-panel mx-auto" style="max-width: 750px; padding: 2rem;">
                
                <!-- Barra Superior com botão de rolagem -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h5 mb-0 text-white flex-grow-1"><i class="fa-solid fa-file-contract text-warning me-2"></i>Termo de Responsabilidade e Conduta</h2>
                    <button type="button" class="btn btn-scroll-down" id="btnScrollDown">
                        Role até o final <i class="fa-solid fa-arrow-down ms-1 animate-bounce"></i>
                    </button>
                </div>

                <!-- Corpo do Termo -->
                <div class="termo-container text-justify" id="termoContainer" style="text-align: justify; line-height: 1.6; font-size: 1.05rem;">
                    <?= $termo_texto ?>
                </div>

                <!-- Formulário de Consentimento -->
                <form action="processa.php" method="POST" id="formConsentimento" class="mt-2">
                    <div class="row g-3 mb-4" id="formRodape">
                        <div class="col-md-6">
                            <label for="nome" class="form-label">Nome</label>
                            <input type="text" class="form-control" id="nome" name="nome" placeholder="Digite seu primeiro nome" required autocomplete="off">
                        </div>
                        <div class="col-md-6">
                            <label for="sobrenome" class="form-label">Sobrenome</label>
                            <input type="text" class="form-control" id="sobrenome" name="sobrenome" placeholder="Digite seu sobrenome" required autocomplete="off">
                        </div>
                    </div>

                    <!-- Botões de Ação -->
                    <div class="d-flex gap-3 justify-content-end">
                        <button type="button" class="btn btn-refuse px-4 py-3 flex-grow-1 flex-md-grow-0" id="btnRecusar">
                            <i class="fa-solid fa-xmark me-2"></i>Recusar
                        </button>
                        <button type="submit" class="btn btn-confirm px-5 py-3 flex-grow-1 flex-md-grow-0">
                            <i class="fa-solid fa-check me-2"></i>Confirmar
                        </button>
                    </div>
                </form>

            </div>
        </main>

        <!-- Rodapé -->
        <footer class="text-center text-muted small mt-3">
            Templo TUDO TEKEM • Recepção Digital
        </footer>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Módulo de Quiosque (mantém fullscreen nesta página também) -->
    <script src="kiosk.js"></script>

    <!-- Scripts de Interação -->
    <script>
        /* ── Mantém o modo quiosque ao navegar para esta página ── */
        // Solicita fullscreen assim que a página carrega (browser permite após navegação
        // iniciada pelo usuário na página anterior)
        window.addEventListener('load', () => {
            if (!document.fullscreenElement) {
                KioskMode.enter();
            }
        });

        // Função de rolagem suave até o formulário no rodapé
        const btnScrollDown = document.getElementById('btnScrollDown');
        const formRodape = document.getElementById('formRodape');
        const termoContainer = document.getElementById('termoContainer');

        btnScrollDown.addEventListener('click', () => {
            // Rola o próprio container do termo até o final
            termoContainer.scrollTo({
                top: termoContainer.scrollHeight,
                behavior: 'smooth'
            });
            // Rola a página suavemente até o formulário de assinatura
            formRodape.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
        });

        // Evento do botão Recusar — volta para index mantendo o quiosque
        const btnRecusar = document.getElementById('btnRecusar');
        btnRecusar.addEventListener('click', () => {
            alert("Por favor, chame um assistente do trabalho.");
            window.location.href = "index.php";
        });
    </script>
</body>
</html>
