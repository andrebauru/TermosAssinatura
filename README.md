# ✅ Termos & Check-in Digital — Templo TUDO TEKEM

Sistema web **PWA** para check-in de visitantes e assinatura digital de termos de consentimento, projetado para funcionar em modo **kiosk** em tablets e totens.

---

## 📋 Funcionalidades

- **Check-in rápido** — Visitante informa nome e sobrenome com interface touch-friendly
- **Termo de consentimento dinâmico** — Texto editável pelo admin via editor WYSIWYG
- **Painel administrativo** — Dashboard com lista de visitantes, filtro por data e exportação CSV
- **Modo Kiosk / PWA** — Funciona em tela cheia, sem barra de navegação, ideal para tablets
- **Imagem de gira configurável** — Upload de banner da gira do dia diretamente pelo painel admin
- **Cadastro de mestres e entidades** — Cards visuais exibidos na tela de check-in
- **Responsivo e offline-ready** — Service Worker para cache e funcionamento sem internet

## 🛠️ Tecnologias

| Camada      | Tecnologia                           |
|-------------|--------------------------------------|
| Back-end    | PHP 8+ / PDO (MySQL/MariaDB)        |
| Front-end   | HTML5, CSS3, Bootstrap 5, JavaScript |
| PWA         | Service Worker, Web App Manifest     |
| Banco       | MySQL / MariaDB (utf8mb4)            |
| Ícones      | Font Awesome 6                       |

## 🚀 Instalação

1. Clone o repositório:
   ```bash
   git clone https://github.com/andrebauru/TermosAssinatura.git
   ```

2. Crie o arquivo `config.php` na raiz com suas credenciais de banco:
   ```php
   <?php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'templo_checkin');
   define('DB_USER', 'seu_usuario');
   define('DB_PASS', 'sua_senha');

   try {
       $pdo = new PDO(
           "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
           DB_USER, DB_PASS,
           [
               PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
               PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
               PDO::ATTR_EMULATE_PREPARES => false,
           ]
       );
   } catch (PDOException $e) {
       die("Erro ao conectar com o banco de dados.");
   }
   ?>
   ```

3. Importe o banco de dados:
   ```bash
   mysql -u seu_usuario -p < banco.sql
   ```

4. Configure o servidor web (Apache/Nginx) apontando para a pasta do projeto.

5. Acesse o painel admin em `/admin.php`.

## 📁 Estrutura do Projeto

```
├── index.php       # Tela de check-in dos visitantes
├── termos.php      # Exibição do termo de consentimento
├── processa.php    # Processamento do formulário de check-in
├── admin.php       # Painel administrativo
├── config.php      # Configurações de banco (NÃO versionado)
├── banco.sql       # Script de criação do banco de dados
├── style.css       # Estilos customizados
├── kiosk.js        # Lógica de modo kiosk e interações
├── manifest.json   # Manifesto PWA
├── sw.js           # Service Worker para cache offline
├── Files/          # Assets estáticos (fontes, logos)
└── uploads/        # Imagens enviadas pelo admin
```

## ⚙️ Configuração

O arquivo `config.php` contém as credenciais de acesso ao banco de dados e **não é versionado** por segurança. Crie-o manualmente seguindo o modelo acima.

## 📄 Licença

Este projeto é de uso privado do Templo TUDO TEKEM.

---

> Desenvolvido com 🖤 para o Templo TUDO TEKEM
