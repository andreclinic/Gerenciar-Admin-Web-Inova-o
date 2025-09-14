# Plugin Gerenciar Admin - Documentação

## Visão Geral

Este plugin WordPress transforma completamente o painel administrativo, implementando um layout moderno baseado no arquivo `modelo_dashboard.html`. O plugin substitui a interface padrão do WordPress por um dashboard analítico profissional com design limpo e funcionalidades modernas.

## 🎯 **MENU MANAGEMENT - REGRA FUNDAMENTAL**

**SEMPRE que desenvolver recursos relacionados ao sistema de menus WordPress:**

- **PRIORIZE a manipulação da `global $menu`** do WordPress ao invés de usar apenas `add_menu_page()` ou `add_submenu_page()`
- **INTEGRE menus personalizados diretamente na estrutura `global $menu`** para garantir controle total sobre posicionamento
- **USE a função `mpa_apply_menu_order()`** como ponto central de controle para ordenação de todos os menus
- **EVITE conflitos** entre múltiplas funções tentando controlar posicionamento simultaneamente

**Exemplo de Implementação Correta:**

```php
// ✅ CORRETO: Integrar na global $menu
function mpa_apply_menu_order() {
    global $menu;

    // Construir menus personalizados como estruturas WordPress nativas
    $custom_menus_to_add[$slug] = array(
        $title,        // [0] menu_title
        'read',        // [1] capability
        $slug,         // [2] menu_slug
        $title,        // [3] page_title
        'menu-top',    // [4] classes
        '',            // [5] hookname
        $icon,         // [6] icon_url
        $position      // [7] position
    );

    // Aplicar à global $menu diretamente
    $menu = $ordered_menu;
}

// ❌ EVITAR: Apenas add_menu_page() sem controle de posição
add_menu_page(...); // Sem controle sobre onde será posicionado
```

## Estrutura do Plugin

### Arquivo Principal

- **`gerenciar-admin.php`** - Arquivo principal que carrega todos os módulos do plugin

### Módulos Principais (pasta `/admin/`)

#### **`mpa-admin.php`**

- **Finalidade**: Funções globais e carregamento de assets principais
- **Responsabilidades**:
  - Enfileiramento de CSS/JS globais
  - Configurações gerais do plugin

#### **`mpa-menu-manager.php`**

- **Finalidade**: Sistema completo de gerenciamento de menus
- **Responsabilidades**:
  - Gerenciamento por roles (gerentes, editores, etc.)
  - Criação/edição/exclusão de menus personalizados
  - Ordenação por drag-and-drop
  - Integração com `global $menu` do WordPress

#### **`mpa-wpadminbar.php`**

- **Finalidade**: Manipulação da barra superior (Admin Bar)
- **Responsabilidades**:
  - Renderização do header customizado baseado no `modelo_dashboard.html`
  - Controle do usuário logado e informações do perfil
  - Botões de ação (dark mode, pesquisa, notificações)
  - Esconder a admin bar padrão do WordPress

#### **`mpa-adminmenumain.php`**

- **Finalidade**: Manipulação do menu lateral (Sidebar)
- **Responsabilidades**:
  - Renderização do sidebar customizado
  - Navegação principal com ícones e descrições
  - Seção de ferramentas
  - Esconder o menu padrão do WordPress
  - Página de dashboard personalizada

#### **`mpa-wpbody.php`**

- **Finalidade**: Manipulação do container principal e layout
- **Responsabilidades**:
  - Reset do layout padrão do WordPress
  - Implementação do container principal
  - Renderização do conteúdo do dashboard (cards, métricas, gráficos)
  - Estilos globais da aplicação

#### **`mpa-wpcontent.php`**

- **Finalidade**: Manipulação da área de conteúdo
- **Responsabilidades**:
  - Esconder elementos padrão do WordPress desnecessários
  - Limpeza do conteúdo nas páginas customizadas
  - Controle de notices e avisos

#### **`mpa-wpfooter.php`**

- **Finalidade**: Manipulação do rodapé
- **Responsabilidades**:
  - Esconder o rodapé padrão do WordPress
  - Limpar textos de copyright e versão padrões

## 📝 **Padrões de Código**

**Nomenclatura:**

- Prefixo: `mpa_` para todas as funções
- Hooks AJAX: `wp_ajax_mpa_[acao]`
- Options: `mpa_[nome_opcao]`

**Segurança:**

- Sempre usar `wp_verify_nonce()` para verificações
- Sanitizar inputs com `sanitize_text_field()`, `esc_url_raw()`, etc.
- Verificar permissões com `current_user_can()`

**Respostas AJAX:**

- Usar `wp_send_json_success()` para sucesso
- Usar `wp_send_json_error()` para erros
- Sempre incluir mensagens descritivas

## 🗂️ **Estrutura de Dados**

**Menus Personalizados:**

```php
// Option: mpa_custom_menus
[
    'role_name' => [
        'menu_id' => [
            'title' => 'Nome do Menu',
            'icon' => 'dashicons-classe',
            'url' => 'https://exemplo.com'
        ]
    ]
]
```

**Ordem dos Menus:**

```php
// Option: mpa_menu_order
['dashboard', 'custom_123', 'posts', 'mpa_custom_456', ...]
```

**Permissões de Menus:**

```php
// Option: mpa_menu_permissions
[
    'role_name' => [
        'menu_slug' => true/false
    ]
]
```

## ⚠️ **Problemas Conhecidos e Soluções**

1. **Posicionamento de Menus:**

   - Sempre integrar na `global $menu` via `mpa_apply_menu_order()`
   - Usar posições fracionárias (3.5, 4.5) para evitar conflitos
   - Evitar posições padrão do WordPress (2, 5, 10, 15, 20, 25, 60, 65, 70, 75, 80)

2. **Slugs de Menus:**

   - Formato padrão: `mpa_custom_[ID]` para menus personalizados
   - Compatibilidade com formato antigo: `custom_[ID]`

3. **Sincronização entre Interfaces:**
   - Sempre atualizar todas as opções relacionadas (`mpa_custom_menus`, `mpa_menu_order`, `mpa_menu_permissions`)
   - Verificar consistência entre "Menus Personalizados" e "Gerenciar Menus"

### Assets (pasta `/assets/`)

#### **CSS**

- **`mpa-wpadminbar.css`** - Estilos para header/barra superior
- **`mpa-adminmenumain.css`** - Estilos para sidebar/menu lateral
- **`mpa-wpbody.css`** - Estilos para cards, métricas e layout principal
- **`mpa-wpcontent.css`** - Estilos para área de conteúdo
- **`mpa-wpfooter.css`** - Estilos para rodapé

#### **JavaScript**

- **`mpa-wpadminbar.js`** - Funcionalidades do header (dark mode, menu mobile)
- **`mpa-adminmenumain.js`** - Funcionalidades do sidebar
- **`mpa-wpbody.js`** - Funcionalidades dos cards e interações
- **`mpa-wpcontent.js`** - Funcionalidades da área de conteúdo
- **`mpa-wpfooter.js`** - Funcionalidades do rodapé

## Referência de Layout: modelo_dashboard.html

O arquivo `modelo_dashboard.html` na raiz do plugin serve como **referência visual e estrutural** para todo o desenvolvimento. Este arquivo contém:

### Componentes Principais:

1. **Header** - Barra superior com logo, filtros, botões de ação e informações do usuário
2. **Sidebar** - Menu lateral com navegação principal e ferramentas
3. **Main Content** - Área principal com cards de métricas e seções organizadas
4. **Cards de Métricas** - Visualização de dados analíticos
5. **Modo Escuro** - Sistema completo de dark/light mode

### Como Usar o modelo_dashboard.html:

- **Para Desenvolvedores**: Use como referência visual para implementar novos componentes
- **Para Design**: Extraia cores, espaçamentos e estruturas CSS deste arquivo
- **Para Funcionalidades**: Observe os IDs, classes e estruturas JavaScript

## Hooks WordPress Utilizados

### Hooks de Carregamento:

- `admin_enqueue_scripts` - Carregamento de CSS/JS
- `admin_menu` - Adição/remoção de itens do menu
- `admin_bar_menu` - Manipulação da barra admin

### Hooks de Layout:

- `admin_head` - Injeção de estilos CSS
- `in_admin_header` - Renderização de elementos no cabeçalho
- `all_admin_notices` - Controle de notices e conteúdo
- `admin_footer` - Manipulação do rodapé
- `admin_footer_text` / `update_footer` - Customização de textos do rodapé

## 🔧 **Comandos de Teste e Debug**

**Linting e Checagem de Tipos:**

- O projeto não possui comandos específicos de lint/typecheck configurados
- Sempre verificar manualmente a sintaxe PHP e funcionalidades WordPress

### Ativar Plugin:

```bash
wp plugin activate gerenciar-admin
```

### Limpar Cache:

```bash
wp cache flush
```

### Debug:

Adicione `define('WP_DEBUG', true);` no `wp-config.php` para debug.

## Funcionalidades Implementadas

### ✅ Completadas:

- Header customizado com informações do usuário
- Sidebar com navegação moderna
- Sistema de dark/light mode
- Cards de métricas responsivos
- Layout responsivo para mobile
- Limpeza da interface padrão do WordPress
- **Sistema completo de gerenciamento de menus por roles**
- **Ordenação por drag-and-drop com posicionamento correto**
- **Criação/edição/exclusão de menus personalizados**

### 🔄 Em Desenvolvimento:

- Gráficos interativos (Chart.js)
- Mais páginas do dashboard
- Configurações do plugin
- Filtros de data

### 📋 Próximos Passos:

- Integração com dados reais do WordPress
- Sistema de permissões avançado
- Exportação de dados
- Widgets customizáveis

## Exemplo de Expansão - Adicionando Nova Página

```php
// Em mpa-adminmenumain.php
add_action('admin_menu', function() {
    add_submenu_page(
        'mpa-dashboard',
        'Nova Página',
        'Nova Página',
        'manage_options',
        'mpa-nova-pagina',
        'mpa_render_nova_pagina'
    );
});

function mpa_render_nova_pagina() {
    ?>
    <div class="mpa-main-content">
        <h2 class="mpa-section-title">Nova Página</h2>
        <div class="mpa-card">
            <p>Conteúdo da nova página...</p>
        </div>
    </div>
    <?php
}
```

## Compatibilidade

- **WordPress**: 5.0+
- **PHP**: 7.4+
- **Navegadores**: Modernos com suporte a CSS Grid e Flexbox

## Autor

**Web Inovação**  
Plugin desenvolvido para estudo e implementação de painéis administrativos modernos no WordPress.

---

**Última atualização:** 2025-09-10
**Versão das instruções:** 2.0

---

**Nota**: Este plugin modifica substancialmente a interface do WordPress. Use apenas em ambientes de desenvolvimento ou com backup completo.

**Nota**: As respostas devem ser em Português Brasil.

**Nota**: Faça o commit no github apenas quando for solicitado.

**Nota**: Faça o commit no github na Branch submenu.
