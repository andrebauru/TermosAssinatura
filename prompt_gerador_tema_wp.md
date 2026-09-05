# PROMPT PARA GERAÇÃO DE TEMA WORDPRESS / WOOCOMMERCE

**Instruções para o usuário:** Copie todo o texto abaixo e cole no seu Gem/Assistente de IA, anexando junto todos os arquivos de imagens, CSS e logos que você possui.

---

**Atue como um Desenvolvedor Sênior Especialista em WordPress, WooCommerce e Engenharia de Front-end.**

## 1. Contexto e Objetivo
O meu objetivo é criar um tema WordPress customizado, responsivo e totalmente integrado ao WooCommerce, construído do zero a partir de um design já consolidado. 
Eu possuo todos os assets visuais, mockups de interface (desktop e mobile), arquivos CSS pré-gerados e a identidade visual completa (logos e ícones em PNG e SVG).

Sua tarefa é analisar os arquivos que estou enviando e me fornecer o código estruturado em PHP, HTML, CSS (integrando com o meu) e JS para montar este tema.

## 2. Arquivos Fornecidos (Em anexo)
Analise cuidadosamente os arquivos anexados antes de gerar qualquer código. Eles estão divididos em:

**A. Mockups de Interface (Layouts):**
*   `cabeçalho e menu.png`
*   `hero desktop e hero mobile.png`
*   `blocos institucionais da home.png`
*   `cards de produtos.png`
*   `rodape e componentes de confiança.png`
*   `home desktop completa.png` e `home mobile completa.png`
*   Arquivos e referências visuais da pasta `Categorias`.

**B. Estilos Pré-Definidos:**
*   Arquivos `.css` contidos na pasta `Css`. Utilize-os como base para a estilização dos componentes.

**C. Identidade Visual (Logos e Ícones):**
*   Arquivos `.svg` e `.png` (ex: `Logotipo horizontal limpo`, `Logotipo vertical`, `Avatar circular`, `selo institucional guardiao`, etc.).
*   Dê preferência absoluta ao uso dos arquivos `.svg` para garantir nitidez e performance, utilizando os `.png` apenas como fallback ou quando estritamente necessário.

## 3. Passo a Passo da Execução Esperada

Por favor, divida o desenvolvimento nas seguintes etapas e me pergunte se desejo avançar para a próxima etapa ao final de cada uma:

**Etapa 1: Estrutura Base e Setup**
*   Gere o código para o `style.css` (cabeçalho obrigatório do tema WordPress) e `functions.php` (configurando suporte a menus, miniaturas, suporte nativo ao WooCommerce e enfileiramento dos meus arquivos CSS anexados).

**Etapa 2: Componentes Globais (Header e Footer)**
*   Crie o `header.php` baseado na imagem `cabeçalho e menu.png`, aplicando o logo correto (fornecido nos anexos) e preparando o menu de navegação.
*   Crie o `footer.php` baseado em `rodape e componentes de confiança.png`, incluindo a lógica para inserir os selos de segurança e logos institucionais.

**Etapa 3: Página Inicial (Front-Page)**
*   Desenvolva o `front-page.php`.
*   Estruture a seção Hero (baseado em `hero desktop e hero mobile.png`).
*   Estruture os blocos de conteúdo institucional (baseado em `blocos institucionais da home.png`).
*   Garanta que a estrutura HTML respeite exatamente o que está nos mockups de `home desktop completa.png` e `home mobile completa.png`.

**Etapa 4: Integração com WooCommerce (Cards e Categorias)**
*   Crie a estrutura de arquivos para sobrescrever os templates do WooCommerce (pasta `woocommerce/`).
*   Desenvolva o template de loop de produtos para que fiquem idênticos ao design de `cards de produtos.png`.
*   Estruture a exibição das categorias conforme os mockups da pasta `Categorias`.

## 4. Regras Técnicas e Boas Práticas Obrigatórias

1.  **Fidelidade ao Design:** O front-end gerado deve ser "pixel-perfect" em relação aos mockups enviados. Preste muita atenção aos espaçamentos, tipografia e cores mostradas nas imagens.
2.  **Responsividade:** O tema deve ser fluido e adaptável. Utilize o mockup `home mobile completa.png` como referência obrigatória para o comportamento em telas menores.
3.  **Código Limpo e Modular:** Não crie arquivos gigantescos. Use `get_template_part()` para separar componentes complexos (como o Hero ou os blocos institucionais).
4.  **Uso dos CSS Fornecidos:** Incorpore os arquivos da pasta `Css` de forma inteligente no `functions.php` via `wp_enqueue_style()`. Não reescreva estilos que já estão prontos, apenas crie o HTML compatível com as classes existentes.
5.  **Acessibilidade e SEO:** Utilize tags HTML5 semânticas (`<header>`, `<nav>`, `<section>`, `<article>`, `<footer>`) e garanta textos alternativos (`alt`) nas imagens.

---
**Aguarde a minha confirmação e o upload dos arquivos para iniciarmos a Etapa 1.**
