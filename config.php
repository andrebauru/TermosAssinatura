<?php
// Configurações de acesso ao banco de dados
define('DB_HOST', 'localhost');
define('DB_NAME', 'templo_checkin');
define('DB_USER', 'root');
define('DB_PASS', '');

try {
    // Inicialização da conexão PDO com charset utf8mb4
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    // Em produção, deve-se ocultar os detalhes do erro para o usuário
    die("Erro ao conectar com o banco de dados. Por favor, tente novamente mais tarde.");
}
?>
