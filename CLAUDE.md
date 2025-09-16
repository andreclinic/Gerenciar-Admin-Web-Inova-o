# Plugin Gerenciar Admin - Documentação

## Visão Geral

Este plugin WordPress transforma completamente o painel administrativo, implementando um layout moderno baseado no arquivo `modelo_dashboard.html`. O plugin substitui a interface padrão do WordPress por um dashboard analítico profissional com design limpo e funcionalidades modernas.

## 🎯 **MENU MANAGEMENT - SISTEMA AVANÇADO INTEGRADO**

**✅ SISTEMA ATUAL IMPLEMENTADO (baseado no gerenciar-menu-webi):**

O plugin agora utiliza um sistema avançado de gerenciamento de menus por role, baseado no código do plugin `gerenciar-menu-webi`, com todas as funcionalidades adaptadas ao estilo do nosso plugin.

### **Arquivos do Sistema de Menus:**

- **`mpa-menu-functions.php`** - Funções principais do sistema (adaptado)
- **`mpa-menu-settings.php`** - Interface e handlers (adaptado com estilo MPA)
- **`mpa-menu-manager.php`** - Integrador principal (atualizado)

### **Funcionalidades Implementadas:**

#### **1. Gerenciamento por Role:**

- **Global** (\_global): Configurações padrão para todos os usuários
- **Por Role**: Configurações específicas para cada tipo de usuário (admin, editor, etc.)
- **Herança**: Roles herdam configurações globais + suas específicas

#### **2. Menus Personalizados:**

- **URLs Internas**: `admin.php?page=...` ou `/wp-admin/...`
- **URLs Externas**: `https://exemplo.com` (abrem em nova aba)
- **Ícones**: Dashicons customizáveis
- **Posicionamento**: Controle de posição no menu

#### **3. Operações Completas:**

- **Renomear**: Menus e submenus
- **Remover/Restaurar**: Com histórico por role
- **Promover/Demover**: Submenu ↔ Menu principal
- **Reordenar**: Drag-and-drop com persistência

#### **4. Interface Integrada:**

- **Estilo MPA**: Toda interface adaptada ao design do plugin
- **Prévia por Role**: Visualiza como ficará para cada usuário
- **Drag-and-drop**: SortableJS para reordenação
- **Formulários Responsivos**: Layout moderno e intuitivo

### **Funções Principais:**

```php
// ✅ Obter configurações efetivas para usuário atual
mpa_get_effective_settings_for_current_user()

// ✅ Obter configurações para prévia de role específica
mpa_get_effective_settings_for_role($role)

// ✅ Aplicar configurações em arrays (para prévia)
mpa_apply_settings_to_arrays($menu, $submenu, $settings)

// ✅ Router para menus personalizados
mpa_custom_menu_router()

// ✅ Capturar baseline antes das modificações
mpa_capture_menu_baseline()
```

### **Estrutura de Dados:**

```php
// Option: mpa_menu_settings_roles
[
    '_global' => [
        'custom_menus' => [...],
        'rename' => [...],
        'remove' => [...],
        'order_menu' => [...],
        // ... outras configurações
    ],
    'administrator' => [
        // Configurações específicas para admins
    ],
    'editor' => [
        // Configurações específicas para editores
    ]
]
```

### **⚠️ IMPORTANTE - O QUE MUDOU:**

1. **Sistema Anterior**: Função `mpa_apply_menu_order()` - **REMOVIDO**
2. **Sistema Atual**: Hook `admin_menu` com prioridade 9999 - **IMPLEMENTADO**
3. **Baseline Capture**: Hook em prioridade 9980 para capturar menus originais
4. **Interface**: Página `mpa-menu-roles` completamente reformulada

### **Como Usar:**

1. **Acessar**: Admin → Gerenciar Admin → Menus por Role
2. **Selecionar Role**: Dropdown para escolher perfil (Global, Admin, Editor, etc.)
3. **Configurar**: Usar interface drag-and-drop e formulários
4. **Testar**: Visualizar prévia em tempo real para cada role

### **Exemplo de Integração:**

```php
// ❌ ANTIGO: Sistema básico
function mpa_apply_menu_order() { ... }

// ✅ NOVO: Sistema avançado por role
add_action('admin_menu', function() {
    $settings = mpa_get_effective_settings_for_current_user();
    // Aplicação automática de todas as configurações
}, 9999);
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

Quando o limite liberar, vou seguir essas instruções exatamente como especificado no CLAUDE.md para criar um sistema de gerenciamento de menus 100% funcional baseado no código que já funciona.
INSTRUÇÕES PARA EXECUÇÃO:

1. REMOVER COMPLETAMENTE todo o sistema de gerenciamento de menus atual mantando as barras de menus top e lateral e a estilização:

   - admin/mpa-menu-functions.php
   - admin/mpa-menu-settings.php
   - admin/mpa-menu-manager-backup.php
   - assets/css/mpa-menu-settings.css
   - assets/js/mpa-menu-settings.js
   - Reverter admin/mpa-menu-manager.php para estado original

2. PRESERVAR INTACTOS (não mexer):

   - Sistema de analytics (funcionando perfeitamente)
   - Sistema de login customizado (funcionando perfeitamente)
   - Sistema de capabilities (funcionando perfeitamente)

3. RECRIAR IDENTICAMENTE o sistema de gerenciamento de menus:

   - Copiar exatamente do gerenciar-menu-webi/
   - Adaptar apenas os prefixos de gmw* para mpa*
   - Manter toda a lógica, estrutura e funcionamento idênticos
   - Garantir que "Tornar Submenu" e "Renomear" funcionem perfeitamente

4. RESULTADO ESPERADO:

   - Sistema de menus funcionando 100% igual ao gerenciar-menu-webi
   - Todas as outras funcionalidades preservadas
   - Código limpo e funcional
