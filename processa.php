<?php
require_once 'config.php';

// Inicializa variáveis de erro/status
$erro = false;
$nome = '';
$sobrenome = '';

// Verifica se os dados foram enviados via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = isset($_POST['nome']) ? $_POST['nome'] : '';
    $sobrenome = isset($_POST['sobrenome']) ? $_POST['sobrenome'] : '';

    // Remove espaços extras nas extremidades
    $nome = trim($nome);
    $sobrenome = trim($sobrenome);

    if (empty($nome) || empty($sobrenome)) {
        $erro = "Por favor, preencha todos os campos obrigatórios.";
    } else {
        try {
            // Gera um ID alfanumérico aleatório e seguro
            $id = uniqid('v', false) . bin2hex(random_bytes(4));

            // Prepara a consulta SQL para inserção segura
            $sql = "INSERT INTO visitantes (id, nome, sobrenome) VALUES (:id, :nome, :sobrenome)";
            $stmt = $pdo->prepare($sql);
            
            // Associa os parâmetros
            $stmt->bindValue(':id', $id, PDO::PARAM_STR);
            $stmt->bindValue(':nome', $nome, PDO::PARAM_STR);
            $stmt->bindValue(':sobrenome', $sobrenome, PDO::PARAM_STR);
            
            // Executa
            $stmt->execute();
        } catch (PDOException $e) {
            // Log do erro real no servidor e definição de mensagem genérica para o usuário
            error_log("Erro de inserção: " . $e->getMessage());
            $erro = "Ocorreu um erro ao salvar o seu check-in. Por favor, solicite ajuda ao assistente.";
        } catch (Exception $e) {
            error_log("Erro de geração de bytes: " . $e->getMessage());
            $erro = "Ocorreu um erro no processamento. Por favor, tente novamente.";
        }
    }
} else {
    // Se não for POST, redireciona para a index
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Check-in Processado | Templo TUDO TEKEM</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <!-- Custom Style -->
    <link href="style.css" rel="stylesheet">
    <!-- Redirecionamento automático após 4 segundos -->
    <?php if (!$erro): ?>
    <meta http-equiv="refresh" content="4;url=index.php">
    <?php endif; ?>
</head>
<body>

    <div class="container-fluid success-screen">
        <?php if ($erro): ?>
            <!-- Tela de Erro -->
            <div class="glass-panel text-center p-5" style="max-width: 600px;">
                <div class="text-danger mb-4" style="font-size: 5rem;">
                    <i class="fa-solid fa-circle-exclamation"></i>
                </div>
                <h1 class="h3 text-danger mb-3">Ops! Algo deu errado</h1>
                <p class="text-light mb-4"><?= htmlspecialchars($erro) ?></p>
                <a href="termos.php" class="btn btn-refuse px-4 py-2">
                    <i class="fa-solid fa-arrow-left me-2"></i>Voltar e tentar novamente
                </a>
            </div>
        <?php else: ?>
            <!-- Tela de Sucesso -->
            <div class="glass-panel text-center p-5 position-relative overflow-hidden" style="max-width: 650px;">
                <div class="success-icon">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
                <h1 class="success-message">
                    OK, seja bem-vindo(a) ao Templo / Terreiro!
                </h1>
                <p class="success-sub mb-4">
                    Obrigado, <strong class="text-warning"><?= htmlspecialchars($nome) ?></strong>. Seu consentimento foi registrado com sucesso.
                </p>
                <div class="d-flex align-items-center justify-content-center text-muted small">
                    <i class="fa-solid fa-spinner fa-spin me-2"></i> Retornando à tela inicial em alguns segundos...
                </div>
                <!-- Barra de contagem regressiva animada (4s) -->
                <div class="countdown-bar"></div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <?php if (!$erro): ?>
    <!-- Fallback de redirecionamento em JS caso o meta-refresh falhe -->
    <script>
        setTimeout(() => {
            window.location.href = "index.php";
        }, 4000);
    </script>
    <?php endif; ?>
</body>
</html>
