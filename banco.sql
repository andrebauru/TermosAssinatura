-- Script de criação do Banco de Dados
CREATE DATABASE IF NOT EXISTS templo_checkin CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE templo_checkin;

CREATE TABLE IF NOT EXISTS visitantes (
    id VARCHAR(50) NOT NULL,
    nome VARCHAR(100) NOT NULL,
    sobrenome VARCHAR(100) NOT NULL,
    latitude DECIMAL(10,8) DEFAULT NULL,
    longitude DECIMAL(11,8) DEFAULT NULL,
    data_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS configuracoes (
    chave VARCHAR(50) NOT NULL,
    valor TEXT NOT NULL,
    PRIMARY KEY (chave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir valores padrão
INSERT INTO configuracoes (chave, valor) VALUES 
('termo_texto', '<p class="text-warning fw-bold mb-3">Para garantir um ambiente seguro e harmonioso, ao confirmar abaixo, você concorda que:</p>\r\n\r\n<ol class="ps-3 text-light">\r\n    <li class="mb-3">\r\n        <strong class="text-warning">Atendimento Espiritual não é Medicina/Advocacia:</strong> \r\n        Nessas orientações e rituais não substituem tratamentos médicos, psicológicos ou aconselhamento legal.\r\n    </li>\r\n    <li class="mb-3">\r\n        <strong class="text-warning">Responsabilidade por Menores:</strong> \r\n        O terreiro não possui cuidadores ou monitores. Se você trouxe crianças ou bebês, a segurança, controle e saúde deles no ambiente (que contém fumaça de charuto e som alto) são de sua total e absoluta responsabilidade.\r\n    </li>\r\n    <li class="mb-3">\r\n        <strong class="text-warning">Pertences e Veículos:</strong> \r\n        Seus objetos pessoais, veículos no estacionamento e situação legal civil são de sua responsabilidade. Não revistamos bolsas, logo, você responde legalmente por qualquer item que portar.\r\n    </li>\r\n    <li class="mb-3">\r\n        <strong class="text-warning">Postura e Respeito:</strong> \r\n        Exigimos silêncio na área externa. É proibido qualquer tipo de discurso de ódio, discussão política ou proselitismo religioso no local.\r\n    </li>\r\n    <li class="mb-0">\r\n        <strong class="text-warning">Privacidade (Sem Fotos/Vídeos):</strong> \r\n        É estritamente proibido fotografar, filmar, gravar áudio ou fazer postagens em redes sociais sem autorização prévia da Direção.\r\n    </li>\r\n</ol>')
ON DUPLICATE KEY UPDATE valor = VALUES(valor);

INSERT INTO configuracoes (chave, valor) VALUES 
('gira_imagem', '')
ON DUPLICATE KEY UPDATE valor = VALUES(valor);

INSERT INTO configuracoes (chave, valor) VALUES 
('gira_titulo', '')
ON DUPLICATE KEY UPDATE valor = VALUES(valor);

INSERT INTO configuracoes (chave, valor) VALUES 
('solicitar_geolocalizacao', '0')
ON DUPLICATE KEY UPDATE valor = VALUES(valor);
