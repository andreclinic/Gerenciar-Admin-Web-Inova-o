<?php
// Gerenciador de Menus por Role

// Incluir funções do sistema de menus
require_once plugin_dir_path(__FILE__) . 'mpa-menu-functions.php';
require_once plugin_dir_path(__FILE__) . 'mpa-menu-settings.php';
require_once plugin_dir_path(__FILE__) . 'mpa-migration-tools.php';
require_once plugin_dir_path(__FILE__) . 'mpa-quick-fix.php';

// Adicionar menu principal do plugin apenas para administradores
add_action('admin_menu', 'mpa_add_main_menu');

function mpa_add_main_menu() {
    // Verificar se o usuário é administrador
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Menu principal do plugin
    add_menu_page(
        'Gerenciar Admin',           // Page title
        'Gerenciar Admin',           // Menu title
        'manage_options',            // Capability
        'mpa-main',                  // Menu slug
        'mpa_main_page',            // Function
        'dashicons-admin-settings',  // Icon
        2                           // Position (after Dashboard)
    );
    
    // Submenu para gerenciar menus por role
    add_submenu_page(
        'mpa-main',                     // Parent slug
        'Gerenciar Menus por Role',     // Page title
        'Menus por Role',               // Menu title
        'manage_options',               // Capability
        'mpa-menu-roles',              // Menu slug
        'mpa_render_settings_page'     // Function
    );
    
    // Submenu para configurações gerais
    add_submenu_page(
        'mpa-main',                     // Parent slug
        'Configurações Gerais',         // Page title
        'Configurações',                // Menu title
        'manage_options',               // Capability
        'mpa-settings',                 // Menu slug
        'mpa_settings_page'             // Function
    );
    
    // Submenu Dashboard (redireciona para Analytics)
    add_submenu_page(
        'mpa-main',                     // Parent slug
        'Dashboard Analytics',          // Page title
        'Dashboard',                    // Menu title
        'manage_options',               // Capability
        'mpa-dashboard',                // Menu slug
        'mpa_dashboard_redirect_to_analytics' // Function
    );
    
    // Submenu Analytics
    add_submenu_page(
        'mpa-main',                     // Parent slug
        'Analytics',                    // Page title
        'Analytics',                    // Menu title
        'manage_options',               // Capability
        'mpa-analytics',                // Menu slug
        'mpa_render_analytics_page'     // Function wrapper
    );
    
    // Submenu Analytics Config
    add_submenu_page(
        'mpa-main',                     // Parent slug
        'Analytics - Configurações',   // Page title
        'Analytics Config',             // Menu title
        'manage_options',               // Capability
        'mpa-config-analytics',          // Menu slug (corrigido para corresponder à URL)
        'mpa_render_analytics_settings_page' // Function wrapper
    );

    // Submenu Ferramentas de Migração
    add_submenu_page(
        'mpa-main',                     // Parent slug
        'Ferramentas de Migração',     // Page title
        'Migração/Limpeza',            // Menu title
        'manage_options',               // Capability
        'mpa-migration-tools',          // Menu slug
        'mpa_render_migration_tools_page' // Function
    );

    // Submenu Quick Fix (Correção Rápida)
    add_submenu_page(
        'mpa-main',                     // Parent slug
        'Correção Rápida',             // Page title
        '🚀 Quick Fix',                // Menu title
        'manage_options',               // Capability
        'mpa-quick-fix',                // Menu slug
        'mpa_render_quick_fix_page'     // Function
    );
}

// Função para redirecionar o Dashboard para Analytics
function mpa_dashboard_redirect_to_analytics() {
    wp_safe_redirect(admin_url('admin.php?page=mpa-analytics'));
    exit;
}

// Página principal do plugin
function mpa_main_page() {
    ?>
    <div class="wrap">
        <h1>Gerenciar Admin - Web Inovação</h1>
        <div class="mpa-admin-dashboard">
            <div class="mpa-admin-cards">
                <div class="mpa-admin-card">
                    <h3>Menus por Role</h3>
                    <p>Configure quais menus cada role de usuário pode acessar.</p>
                    <a href="<?php echo admin_url('admin.php?page=mpa-menu-roles'); ?>" class="button button-primary">
                        Gerenciar Menus
                    </a>
                </div>
                
                <div class="mpa-admin-card">
                    <h3>Configurações</h3>
                    <p>Configure as opções gerais do plugin.</p>
                    <a href="<?php echo admin_url('admin.php?page=mpa-settings'); ?>" class="button button-secondary">
                        Ver Configurações
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <style>
        .mpa-admin-dashboard {
            margin-top: 20px;
        }
        
        .mpa-admin-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .mpa-admin-card {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
        }
        
        .mpa-admin-card h3 {
            margin-top: 0;
            color: #23282d;
        }
        
        .mpa-admin-card p {
            color: #666;
            margin-bottom: 15px;
        }
    </style>
    <?php
}

// Página de gerenciamento de menus por role
// Função mpa_menu_roles_page() substituída pela nova do sistema integrado

function mpa_get_admin_menus($selected_role = null) {
    global $menu, $submenu;
    
    $admin_menus = [];
    
    if (!is_array($menu)) return $admin_menus;
    
    // Obter ordem customizada
    $custom_order = get_option('mpa_menu_order', array());
    
    foreach ($menu as $menu_item) {
        if (empty($menu_item[0]) || $menu_item[0] === '') continue;
        
        $slug_raw = $menu_item[2];                      // ex.: edit.php?post_type=page
        $slug = mpa_normalize_slug($slug_raw);          // ex.: edit.php
        
        // Extrair ícone se existir
        $icon = '';
        if (!empty($menu_item[6])) {
            if (strpos($menu_item[6], 'dashicons-') === 0) {
                $icon = $menu_item[6];
            }
        }
        
        $menu_data = [
            'title'    => wp_strip_all_tags($menu_item[0]),
            'slug_raw' => $slug_raw,    // usar no remove_menu_page
            'slug'     => $slug,        // usar como chave de permissão
            'icon'     => $icon,
            'submenus' => []
        ];
        
        // Adicionar submenus se existirem
        if (isset($submenu[$slug_raw]) && is_array($submenu[$slug_raw])) {
            foreach ($submenu[$slug_raw] as $submenu_item) {
                if (empty($submenu_item[0])) continue;
                
                $sm_raw = $submenu_item[2];
                $sm_nrm = mpa_normalize_slug($sm_raw);
                
                $menu_data['submenus'][] = [
                    'title'    => wp_strip_all_tags($submenu_item[0]),
                    'slug_raw' => $sm_raw,   // para remove_submenu_page
                    'slug'     => $sm_nrm    // para chaves de permissão
                ];
            }
        }
        
        $admin_menus[] = $menu_data;
    }
    
    
    // Adicionar menus personalizados APENAS para a role selecionada
    $custom_menus = get_option('mpa_custom_menus', array());
    if (!empty($custom_menus) && $selected_role) {
        // Buscar apenas menus da role selecionada (incluindo variações)
        $roles_to_check = [$selected_role];
        
        // Verificar variações de role (gerentes/gerente)
        if ($selected_role === 'gerentes') {
            $roles_to_check[] = 'gerente';
        } elseif ($selected_role === 'gerente') {
            $roles_to_check[] = 'gerentes';
        }
        
        foreach ($roles_to_check as $role_variant) {
            if (isset($custom_menus[$role_variant]) && !empty($custom_menus[$role_variant])) {
                foreach ($custom_menus[$role_variant] as $menu_id => $custom_menu) {
                    $slug = 'mpa_custom_' . $menu_id;
                    
                    $admin_menus[] = [
                        'title'     => $custom_menu['title'],
                        'slug_raw'  => $slug,
                        'slug'      => $slug,
                        'icon'      => $custom_menu['icon'],
                        'submenus'  => [],
                        'custom'    => true,
                        'custom_url' => $custom_menu['url'],
                        'custom_id'  => $menu_id,
                        'custom_role' => $role_variant
                    ];
                }
            }
        }
    }
    
    // Aplicar ordem customizada se existir
    if (!empty($custom_order)) {
        $ordered_menus = array();
        
        // Primeiro, adicionar menus conforme a ordem customizada
        foreach ($custom_order as $slug) {
            foreach ($admin_menus as $index => $menu_data) {
                if ($menu_data['slug'] === $slug) {
                    $ordered_menus[] = $menu_data;
                    unset($admin_menus[$index]);
                    break;
                }
            }
        }
        
        // Depois, adicionar menus restantes
        foreach ($admin_menus as $menu_data) {
            $ordered_menus[] = $menu_data;
        }
        
        $admin_menus = $ordered_menus;
    }
    
    return $admin_menus;
}

// Página de configurações gerais
function mpa_settings_page() {
    ?>
    <div class="wrap">
        <h1>Configurações Gerais</h1>
        <p>Configurações gerais do plugin Gerenciar Admin estarão disponíveis em breve.</p>
        
        <div class="mpa-settings-info">
            <h3>Recursos Atuais</h3>
            <ul>
                <li>✅ Header customizado com notificações</li>
                <li>✅ Sistema de notificações com persistência</li>
                <li>✅ Redirecionamento automático para dashboard</li>
                <li>✅ Gerenciamento de menus por role</li>
            </ul>
            
            <h3>Próximos Recursos</h3>
            <ul>
                <li>⏳ Sistema de busca global</li>
                <li>⏳ Calendário integrado</li>
                <li>⏳ Customização de cores e temas</li>
            </ul>
        </div>
    </div>
    
    <style>
        .mpa-settings-info {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
            margin-top: 20px;
        }
        
        .mpa-settings-info ul {
            margin-left: 20px;
        }
        
        .mpa-settings-info li {
            margin-bottom: 5px;
        }
    </style>
    <?php
}

// ===== SISTEMA ROBUSTO DE RESTRIÇÕES DE MENU =====

// 1. Função para normalizar slugs (CORRIGIDA PARA PRESERVAR PARÂMETROS IMPORTANTES)
function mpa_normalize_slug($slug) {
    $slug = html_entity_decode($slug);
    $slug = preg_replace('#^' . preg_quote(admin_url(), '#') . '#', '', $slug);
    $slug = preg_replace('#^/wp-admin/#', '', $slug);
    $slug = preg_replace('#^/#', '', $slug);
    
    // NÃO remover parâmetros de query importantes como post_type
    // Isso permite distinguir entre edit.php (Posts) e edit.php?post_type=page (Páginas)
    // $slug = preg_replace('/\?.*$/', '', $slug); // REMOVIDO
    
    return $slug;
}

// 2. Função para decidir se usuário pode ver menu (OR lógico entre roles)
function mpa_user_can_see_menu($menu_slug, array $user_roles, array $menu_permissions) {
    $decision = true; // padrão habilitado
    $found_config = false;

    // Debug detalhado SEMPRE ATIVO para debugging
    // Debug removido por segurança - dados sensíveis não devem aparecer em logs de produção

    foreach ($user_roles as $role) {
        $rolePerms = $menu_permissions[$role] ?? null;
        if (!$rolePerms) {
            error_log("[MPA NOVO DEBUG] Role $role: Sem permissões configuradas");
            continue;
        }

        if (array_key_exists($menu_slug, $rolePerms)) {
            $found_config = true;
            $perm_value = $rolePerms[$menu_slug];
            error_log("[MPA NOVO DEBUG] Role $role: Encontrou config para $menu_slug = " . var_export($perm_value, true));
            
            // Se QUALQUER role marcar TRUE, libera imediatamente
            if ($perm_value === true) {
                error_log("[MPA NOVO DEBUG] RESULTADO: PERMITIDO (role $role marcou true)");
                return true;
            }
            // Se esta role marca FALSE, continua verificando outras roles
            $decision = false;
        } else {
            error_log("[MPA NOVO DEBUG] Role $role: Menu $menu_slug não encontrado nas permissões");
        }
    }

    // Comportamento padrão: Se não encontrou configuração, mantém padrão (permitir)
    // Se encontrou configuração mas todas marcaram false, retorna false
    $result = $found_config ? $decision : true;
    error_log("[MPA NOVO DEBUG] RESULTADO FINAL: " . ($result ? 'PERMITIDO' : 'BLOQUEADO') . " (found_config: " . ($found_config ? 'SIM' : 'NÃO') . ", decision: " . ($decision ? 'PERMITIR' : 'BLOQUEAR') . ")");

    return $result;
}

// 3. Função para decidir se usuário pode ver submenu
function mpa_user_can_see_submenu($parent_slug, $submenu_slug, array $user_roles, array $menu_permissions) {
    $key = $parent_slug . '|' . $submenu_slug;
    $decision = true;
    $found_config = false;
    
    foreach ($user_roles as $role) {
        $rolePerms = $menu_permissions[$role]['submenus'] ?? [];
        if (array_key_exists($key, $rolePerms)) {
            $found_config = true;
            if ($rolePerms[$key] === true) {
                return true;
            }
            $decision = false;
        }
    }
    
    return $found_config ? $decision : true;
}

// 4. Aplicação robusta de restrições - REATIVADO
add_action('admin_menu', function() {

    $user = wp_get_current_user();
    if (in_array('administrator', (array) $user->roles, true)) return;

    $user = wp_get_current_user();
    $roles = (array) $user->roles;
    $opts = get_option('mpa_menu_permissions', []);
    
    if (empty($roles) || empty($opts)) return;

    $debug_info = [];
    $debug_info[] = "Sistema robusto executado para: " . $user->user_login;
    $debug_info[] = "Roles: " . implode(', ', $roles);

    // Obter todos os menus disponíveis
    $admin_menus = mpa_get_admin_menus();
    
    foreach ($admin_menus as $menu_item) {
        // Ignorar menus personalizados - eles são controlados pela própria função mpa_add_custom_menus_to_admin
        if (isset($menu_item['custom']) && $menu_item['custom']) {
            continue;
        }
        
        $slug = mpa_normalize_slug($menu_item['slug']);

        if (!mpa_user_can_see_menu($slug, $roles, $opts)) {
            // PROTEÇÃO: Nunca remover menus críticos (Rank Math, Analytics Config)
            if ($menu_item['slug'] === 'mpa-config-analytics') {
                $debug_info[] = "PROTEGIDO Analytics Config: {$menu_item['slug']} (não removido)";
                continue; // Pular remoção para Analytics Config
            }
            if (strpos($menu_item['slug'], 'rank-math') !== false) {
                $debug_info[] = "PROTEGIDO Rank Math: {$menu_item['slug']} (não removido)";
                continue; // Pular remoção para Rank Math
            }

            remove_menu_page($menu_item['slug']); // usar slug original para remoção
            $debug_info[] = "REMOVIDO menu: {$menu_item['slug']} (normalizado: $slug)";

            if ($slug === 'edit.php') {
                $debug_info[] = "*** POSTS REMOVIDO ***";
            }
        }

        // Processar submenus
        if (!empty($menu_item['submenus'])) {
            foreach ($menu_item['submenus'] as $sub) {
                $sub_slug = mpa_normalize_slug($sub['slug']);

                if (!mpa_user_can_see_submenu($slug, $sub_slug, $roles, $opts)) {
                    // PROTEÇÃO: Nunca remover submenus críticos (Rank Math, Analytics Config)
                    if ($sub['slug'] === 'mpa-config-analytics') {
                        $debug_info[] = "PROTEGIDO Analytics Config submenu: {$sub['slug']} (não removido)";
                        continue; // Pular remoção para submenus de Analytics Config
                    }
                    if (strpos($sub['slug'], 'rank-math') !== false) {
                        $debug_info[] = "PROTEGIDO Rank Math submenu: {$sub['slug']} (não removido)";
                        continue; // Pular remoção para submenus Rank Math
                    }

                    remove_submenu_page($menu_item['slug'], $sub['slug']); // usar slugs originais
                    $debug_info[] = "REMOVIDO submenu: {$menu_item['slug']} -> {$sub['slug']}";
                }
            }
        }
    }
    
    // Debug visual
    if (isset($_GET['debug_mpa_robust'])) {
        add_action('admin_notices', function() use ($debug_info, $user, $roles, $opts) {
            echo '<div style="background: #e7f3ff; border: 2px solid #0073aa; padding: 15px; margin: 15px;">';
            echo '<h3>🔒 MPA Sistema Robusto Debug</h3>';
            echo '<p><strong>Usuário:</strong> ' . $user->user_login . '</p>';
            echo '<p><strong>Roles:</strong> ' . implode(', ', $roles) . '</p>';
            echo '<h4>Ações executadas:</h4><ul>';
            foreach ($debug_info as $info) {
                echo '<li>' . $info . '</li>';
            }
            echo '</ul>';
            echo '<button onclick="this.parentElement.style.display=\'none\'">✕ Fechar</button>';
            echo '</div>';
        });
    }
    
}, PHP_INT_MAX); // Prioridade máxima

// 5. Mapeamento de screen ID para slugs de menu
function mpa_screen_to_menu_slug_map() {
    return [
        // Plugin pages
        'toplevel_page_mpa-main'           => ['mpa-main', null],
        'gerenciar-admin_page_mpa-menu-roles' => ['mpa-main', 'mpa-menu-roles'],
        'gerenciar-admin_page_mpa-settings'   => ['mpa-main', 'mpa-settings'],
        
        // Core WordPress screens
        'edit-post'                        => ['edit.php', null],
        'post'                             => ['post-new.php', null],
        'upload'                           => ['upload.php', null],
        'plugins'                          => ['plugins.php', null],
        'users'                            => ['users.php', null],
        'themes'                           => ['themes.php', null],
        'options-general'                  => ['options-general.php', null],
        'tools'                            => ['tools.php', null],
        'edit-page'                        => ['edit.php?post_type=page', null],
        'page'                             => ['post-new.php?post_type=page', null],
        'edit-comments'                    => ['edit-comments.php', null],
        'dashboard'                        => ['index.php', null],
        
        // Media
        'media'                            => ['upload.php', null],
        
        // Appearance
        'nav-menus'                        => ['nav-menus.php', null],
        'widgets'                          => ['widgets.php', null],
        'customize'                        => ['customize.php', null],
    ];
}




// ===== UTILITÁRIOS DE DEBUG =====

// Debug de menu e submenu para administradores
add_action('admin_notices', function() {
    if (!current_user_can('manage_options') || !isset($_GET['mpa_dump_menus'])) return;
    
    global $menu, $submenu;
    echo '<div class="notice notice-info" style="max-height: 400px; overflow: auto;">';
    echo '<h3>🔍 MPA Debug - Dump de Menus</h3>';
    echo '<h4>$menu Global:</h4><pre style="font-size: 11px;">' . print_r($menu, true) . '</pre>';
    echo '<h4>$submenu Global:</h4><pre style="font-size: 11px;">' . print_r($submenu, true) . '</pre>';
    echo '</div>';
});

// Debug de screen ID
add_action('current_screen', function($screen) {
    if (!isset($_GET['mpa_screen_debug'])) return;
    
    add_action('admin_notices', function() use ($screen) {
        echo '<div class="notice notice-warning">';
        echo '<p><strong>🎯 Screen Debug:</strong> <code>' . esc_html($screen->id) . '</code></p>';
        echo '<p><strong>Base:</strong> <code>' . esc_html($screen->base) . '</code></p>';
        echo '<p><strong>Parent Base:</strong> <code>' . esc_html($screen->parent_base) . '</code></p>';
        echo '</div>';
    });
});

// Debug completo do sistema para usuários não-admin
add_action('admin_notices', function() {
    if (current_user_can('manage_options') || !isset($_GET['mpa_debug_full'])) return;
    
    $user = wp_get_current_user();
    $roles = (array) $user->roles;
    $opts = get_option('mpa_menu_permissions', []);
    
    echo '<div class="notice notice-info">';
    echo '<h3>🔧 MPA Debug Completo</h3>';
    echo '<p><strong>Usuário:</strong> ' . $user->user_login . '</p>';
    echo '<p><strong>Roles:</strong> ' . implode(', ', $roles) . '</p>';
    echo '<p><strong>Configurações:</strong></p>';
    echo '<pre style="max-height: 200px; overflow: auto; font-size: 11px;">' . print_r($opts, true) . '</pre>';
    
    // Testar alguns menus principais
    $test_menus = ['edit.php', 'upload.php', 'plugins.php', 'users.php'];
    echo '<p><strong>Teste de Permissões:</strong></p><ul>';
    foreach ($test_menus as $menu) {
        $allowed = mpa_user_can_see_menu($menu, $roles, $opts);
        $status = $allowed ? '✅ PERMITIDO' : '❌ BLOQUEADO';
        echo '<li>' . $menu . ': ' . $status . '</li>';
    }
    echo '</ul>';
    echo '</div>';
});

// Debug do bloqueio direto por link
add_action('admin_notices', function() {
    if (!isset($_GET['mpa_debug_block'])) return;
    
    $user = wp_get_current_user();
    $current_page = $_GET['page'] ?? '';
    $current_file = basename($_SERVER['PHP_SELF']);
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    $screen_id = is_object($screen) ? $screen->id : '';
    
    echo '<div class="notice notice-warning">';
    echo '<h3>🛡️ Debug Bloqueio Direto</h3>';
    echo '<p><strong>Usuário:</strong> ' . $user->user_login . '</p>';
    echo '<p><strong>Roles:</strong> ' . implode(', ', $user->roles) . '</p>';
    echo '<p><strong>Current Page:</strong> <code>' . esc_html($current_page) . '</code></p>';
    echo '<p><strong>Current File:</strong> <code>' . esc_html($current_file) . '</code></p>';
    echo '<p><strong>Screen ID:</strong> <code>' . esc_html($screen_id) . '</code></p>';
    
    if ($current_page) {
        $normalized = mpa_normalize_slug($current_page);
        echo '<p><strong>Page Normalizada:</strong> <code>' . esc_html($normalized) . '</code></p>';
    }
    
    if ($current_file !== 'admin.php') {
        $normalized_file = mpa_normalize_slug($current_file);
        echo '<p><strong>File Normalizado:</strong> <code>' . esc_html($normalized_file) . '</code></p>';
    }
    
    // Debug específico para taxonomias
    if ($current_file === 'edit-tags.php') {
        $taxonomy = $_GET['taxonomy'] ?? '';
        if ($taxonomy) {
            echo '<p><strong>🏷️ TAXONOMIA DEBUG:</strong></p>';
            echo '<p><strong>Taxonomy Raw:</strong> <code>' . esc_html($taxonomy) . '</code></p>';
            
            $roles = (array) $user->roles;
            $opts = get_option('mpa_menu_permissions', []);
            
            // Testar apenas o nome direto da taxonomia (como agora são salvas)
            $allowed = mpa_user_can_see_menu($taxonomy, $roles, $opts);
            $status = $allowed ? '✅ PERMITIDO' : '❌ BLOQUEADO';
            
            echo '<p><strong>Teste de Permissão:</strong></p>';
            echo '<p>Taxonomia <code>' . esc_html($taxonomy) . '</code>: ' . $status . '</p>';
            
            echo '<p><strong>Roles do Usuário:</strong> ' . implode(', ', $roles) . '</p>';
            
            echo '<p><strong>Configurações de Permissões para suas Roles:</strong></p>';
            foreach ($roles as $role) {
                if (isset($opts[$role])) {
                    echo '<p><strong>Role ' . esc_html($role) . ':</strong></p>';
                    echo '<pre style="font-size:10px; max-height:150px; overflow:auto;">';
                    print_r($opts[$role]);
                    echo '</pre>';
                } else {
                    echo '<p><strong>Role ' . esc_html($role) . ':</strong> Sem configurações</p>';
                }
            }
        }
    }
    
    echo '<p><em>Sistema de bloqueio ativo!</em></p>';
    echo '</div>';
});

/////////////////////
// EXPORTAÇÃO E IMPORTAÇÃO
/////////////////////

// Adicionar actions para export/import
add_action('admin_post_mpa_export_menu_settings', 'mpa_export_menu_settings_callback');
add_action('admin_post_mpa_import_menu_settings', 'mpa_import_menu_settings_callback');
add_action('admin_post_mpa_reset_menu_settings', 'mpa_reset_menu_settings_callback');

function mpa_export_menu_settings_callback() {
    if (!current_user_can('manage_options')) {
        wp_die('Sem permissões suficientes.');
    }

    // Exportar todas as configurações do plugin
    $export_data = array(
        'mpa_menu_permissions' => get_option('mpa_menu_permissions', array()),
        'mpa_menu_customizations' => get_option('mpa_menu_customizations', array()),
        'mpa_menu_order' => get_option('mpa_menu_order', array())
    );
    
    $json = json_encode($export_data, JSON_PRETTY_PRINT);

    $filename = 'mpa_menu_settings_' . date('Y-m-d') . '.json';

    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . strlen($json));

    ob_clean();
    flush();
    echo $json;
    exit;
}

function mpa_import_menu_settings_callback() {
    if (!current_user_can('manage_options')) {
        wp_die('Sem permissões suficientes.');
    }

    if (!empty($_FILES['mpa_import_file']['tmp_name'])) {
        // Usar wp_handle_upload para segurança
        $upload_overrides = array(
            'test_form' => false,
            'mimes' => array('json' => 'application/json')
        );

        $uploaded_file = wp_handle_upload($_FILES['mpa_import_file'], $upload_overrides);

        if (isset($uploaded_file['error'])) {
            wp_die('Tipo de arquivo não permitido: ' . $uploaded_file['error']);
        }

        // Validação adicional de tipo MIME
        $file_type = wp_check_filetype($uploaded_file['file']);
        if ($file_type['ext'] !== 'json') {
            unlink($uploaded_file['file']); // Limpar arquivo
            wp_die('Apenas arquivos JSON são permitidos.');
        }

        $file_content = file_get_contents($uploaded_file['file']);
        // Limpar arquivo após leitura
        unlink($uploaded_file['file']);

        $data = json_decode($file_content, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
            // Importar todas as configurações
            if (isset($data['mpa_menu_permissions'])) {
                update_option('mpa_menu_permissions', $data['mpa_menu_permissions']);
            }
            if (isset($data['mpa_menu_customizations'])) {
                update_option('mpa_menu_customizations', $data['mpa_menu_customizations']);
            }
            if (isset($data['mpa_menu_order'])) {
                update_option('mpa_menu_order', $data['mpa_menu_order']);
            }
            
            // Compatibilidade com exports antigos (só permissões)
            if (!isset($data['mpa_menu_permissions']) && !isset($data['mpa_menu_customizations']) && !isset($data['mpa_menu_order'])) {
                update_option('mpa_menu_permissions', $data);
            }
            
            $redirect = add_query_arg('mpa_status', 'success', admin_url('admin.php?page=mpa-menu-roles'));
        } else {
            $redirect = add_query_arg('mpa_status', 'invalid_json', admin_url('admin.php?page=mpa-menu-roles'));
        }
    } else {
        $redirect = add_query_arg('mpa_status', 'no_file', admin_url('admin.php?page=mpa-menu-roles'));
    }

    wp_safe_redirect($redirect);
    exit;
}

// Função de Reset
function mpa_reset_menu_settings_callback() {
    if (!current_user_can('manage_options')) {
        wp_die('Sem permissões suficientes.');
    }
    
    // Verificar nonce de segurança
    if (!wp_verify_nonce($_POST['mpa_reset_nonce'], 'mpa_reset_settings')) {
        wp_die('Erro de segurança. Tente novamente.');
    }
    
    // Deletar todas as opções do plugin
    delete_option('mpa_menu_permissions');
    delete_option('mpa_menu_customizations');
    delete_option('mpa_menu_order');
    delete_option('mpa_custom_menus');
    
    // Redirect com mensagem de sucesso
    $redirect = add_query_arg('mpa_status', 'reset_success', admin_url('admin.php?page=mpa-menu-roles'));
    wp_safe_redirect($redirect);
    exit;
}

// Mensagens de feedback para export/import
add_action('admin_notices', function () {
    if (!isset($_GET['page']) || $_GET['page'] !== 'mpa-menu-roles')
        return;

    if (isset($_GET['mpa_status'])) {
        switch ($_GET['mpa_status']) {
            case 'success':
                echo '<div class="notice notice-success is-dismissible"><p>Configurações importadas com sucesso!</p></div>';
                break;
            case 'invalid_json':
                echo '<div class="notice notice-error is-dismissible"><p>Arquivo JSON inválido.</p></div>';
                break;
            case 'no_file':
                echo '<div class="notice notice-warning is-dismissible"><p>Nenhum arquivo foi enviado.</p></div>';
                break;
            case 'reset_success':
                echo '<div class="notice notice-success is-dismissible"><p><strong>✅ Reset concluído!</strong> Todas as configurações do plugin foram restauradas ao padrão.</p></div>';
                break;
        }
    }
});

// Aplicar customizações de nomes e ícones aos menus
// Hook com prioridade 999 para garantir que todos os plugins já registraram os menus
add_action('admin_menu', 'mpa_apply_menu_customizations', 999);

// AJAX handler para salvar ordem dos menus
add_action('wp_ajax_mpa_save_menu_order', 'mpa_save_menu_order_callback');

function mpa_save_menu_order_callback() {
    // Verificar nonce
    if (!wp_verify_nonce($_POST['nonce'], 'mpa_menu_order')) {
        wp_die('Nonce inválido');
    }
    
    // Verificar permissões
    if (!current_user_can('manage_options')) {
        wp_die('Sem permissões suficientes');
    }
    
    // Obter nova ordem
    $menu_order = json_decode(stripslashes($_POST['menu_order']), true);
    
    if (!is_array($menu_order)) {
        wp_send_json_error('Dados inválidos');
        return;
    }
    
    // Salvar ordem customizada
    update_option('mpa_menu_order', $menu_order);
    
    wp_send_json_success('Ordem salva com sucesso');
}

// Aplicar ordem customizada dos menus
add_action('admin_menu', 'mpa_apply_menu_order', 999);

function mpa_apply_menu_order() {
    global $menu;
    
    $custom_order = get_option('mpa_menu_order', array());
    
    if (empty($custom_order) || !is_array($menu)) {
        return;
    }
    
    // Obter menus personalizados para adicionar à estrutura
    $custom_menus = get_option('mpa_custom_menus', array());
    $custom_menus_to_add = array();
    
    if (!empty($custom_menus)) {
        $user = wp_get_current_user();
        if ($user && !empty($user->roles)) {
            foreach ($user->roles as $user_role) {
                $roles_to_check = [$user_role];
                if ($user_role === 'gerentes') {
                    $roles_to_check[] = 'gerente';
                } elseif ($user_role === 'gerente') {
                    $roles_to_check[] = 'gerentes';
                }
                
                foreach ($roles_to_check as $role_variant) {
                    if (isset($custom_menus[$role_variant]) && !empty($custom_menus[$role_variant])) {
                        foreach ($custom_menus[$role_variant] as $custom_menu) {
                            $menu_id = $custom_menu['id'];
                            
                            // Criar slug único interno para este menu personalizado
                            $internal_slug = 'mpa_custom_' . $menu_id;
                            
                            // Adicionar menu com slug interno (sem redirecionamento aqui - isso é feito na mpa_add_custom_menus_to_admin)
                            $custom_menus_to_add[$internal_slug] = array(
                                $custom_menu['title'],                    // [0] menu_title
                                'read',                                   // [1] capability
                                $internal_slug,                           // [2] menu_slug (slug interno)
                                $custom_menu['title'],                    // [3] page_title
                                'menu-top',                               // [4] classes
                                '',                                       // [5] hookname
                                $custom_menu['icon'],                     // [6] icon_url
                                99                                        // [7] position (será reorganizada)
                            );
                        }
                    }
                }
            }
        }
    }
    
    // Criar um mapa dos menus existentes por slug
    $menu_by_slug = array();
    $separators = array();
    $non_reorderable = array();
    
    foreach ($menu as $position => $menu_item) {
        if (!empty($menu_item[2])) {
            $slug = $menu_item[2];
            $menu_by_slug[$slug] = array(
                'position' => $position,
                'item' => $menu_item
            );
        } elseif (empty($menu_item[2]) || strpos($menu_item[4], 'wp-menu-separator') !== false) {
            $separators[$position] = $menu_item;
        } else {
            $non_reorderable[$position] = $menu_item;
        }
    }
    
    // Adicionar menus personalizados ao mapa
    foreach ($custom_menus_to_add as $slug => $menu_item) {
        $menu_by_slug[$slug] = array(
            'position' => 999, // posição temporária
            'item' => $menu_item
        );
    }
    
    // Definir posições base para reorganização
    $wordpress_positions = array(2 => true, 5 => true, 10 => true, 15 => true, 20 => true, 25 => true, 60 => true, 65 => true, 70 => true, 75 => true, 80 => true);
    $ordered_menu = array();
    $used_positions = array();
    
    // Primeiro, preservar separadores e menus não reorganizáveis
    foreach ($separators as $pos => $item) {
        $ordered_menu[$pos] = $item;
        $used_positions[] = $pos;
    }
    foreach ($non_reorderable as $pos => $item) {
        $ordered_menu[$pos] = $item;
        $used_positions[] = $pos;
    }
    
    // Aplicar ordem customizada com posicionamento inteligente
    $current_position = 3; // Começar após Dashboard (posição 2)
    
    foreach ($custom_order as $slug) {
        // Verificar tanto formato completo quanto formato simplificado
        $target_slug = $slug;
        if (strpos($slug, 'custom_') === 0 && !isset($menu_by_slug[$slug])) {
            $target_slug = str_replace('custom_', 'mpa_custom_', $slug);
        }
        
        if (isset($menu_by_slug[$target_slug])) {
            // Encontrar próxima posição livre
            while (isset($ordered_menu[$current_position]) || 
                   isset($wordpress_positions[$current_position]) ||
                   in_array($current_position, $used_positions)) {
                $current_position += 0.5;
            }
            
            $ordered_menu[$current_position] = $menu_by_slug[$target_slug]['item'];
            $used_positions[] = $menu_by_slug[$target_slug]['position'];
            $current_position += 1;
        }
    }
    
    // Adicionar menus que não estão na ordem customizada no final
    $current_position = 90;
    foreach ($menu_by_slug as $slug => $menu_data) {
        if (!in_array($menu_data['position'], $used_positions)) {
            while (isset($ordered_menu[$current_position])) {
                $current_position++;
            }
            $ordered_menu[$current_position] = $menu_data['item'];
            $current_position++;
        }
    }
    
    // Aplicar nova ordem mantendo a estrutura
    if (!empty($ordered_menu)) {
        $menu = $ordered_menu;
        ksort($menu);
    }
}

function mpa_apply_menu_customizations() {
    global $menu, $submenu;
    
    $menu_customizations = get_option('mpa_menu_customizations', array());
    
    if (empty($menu_customizations) || !is_array($menu)) {
        return;
    }
    
    // Debug: mostrar todos os menus encontrados
    if (isset($_GET['debug_menus'])) {
        error_log('=== MENUS ENCONTRADOS ===');
        foreach ($menu as $key => $menu_item) {
            if (!empty($menu_item[0]) && !empty($menu_item[2])) {
                error_log("Menu $key: Título='{$menu_item[0]}', Slug='{$menu_item[2]}'");
            }
        }
    }
    
    // Aplicar customizações aos menus principais
    foreach ($menu as $key => $menu_item) {
        if (empty($menu_item[0]) || empty($menu_item[2])) {
            continue;
        }
        
        $menu_slug = $menu_item[2];
        $menu_title = $menu_item[0];
        
        // Procurar customização por diferentes estratégias
        $custom_data = null;
        
        // 1. Buscar pelo slug exato
        if (isset($menu_customizations[$menu_slug])) {
            $custom_data = $menu_customizations[$menu_slug];
        }
        
        // 2. Buscar pelo slug simplificado
        if (!$custom_data) {
            $simple_slug = str_replace(['admin.php?page=', 'edit.php?post_type='], '', $menu_slug);
            if (isset($menu_customizations[$simple_slug])) {
                $custom_data = $menu_customizations[$simple_slug];
            }
        }
        
        // 3. Buscar por slugs conhecidos baseados no texto do menu
        if (!$custom_data) {
            $menu_text_lower = strtolower(strip_tags($menu_title));
            
            if (strpos($menu_text_lower, 'woocommerce') !== false && isset($menu_customizations['woocommerce'])) {
                $custom_data = $menu_customizations['woocommerce'];
            } elseif (strpos($menu_text_lower, 'elementor') !== false && isset($menu_customizations['elementor'])) {
                $custom_data = $menu_customizations['elementor'];
            } elseif (strpos($menu_text_lower, 'users') !== false && isset($menu_customizations['users'])) {
                $custom_data = $menu_customizations['users'];
            } elseif (strpos($menu_text_lower, 'posts') !== false && isset($menu_customizations['posts'])) {
                $custom_data = $menu_customizations['posts'];
            } elseif (strpos($menu_text_lower, 'pages') !== false && isset($menu_customizations['pages'])) {
                $custom_data = $menu_customizations['pages'];
            }
        }
        
        // Aplicar customizações se encontradas e não estiverem vazias
        if ($custom_data) {
            // Aplicar título personalizado apenas se não estiver vazio
            if (isset($custom_data['title']) && trim($custom_data['title']) !== '') {
                $menu[$key][0] = $custom_data['title'];
                
                if (isset($_GET['debug_menus'])) {
                    error_log("CUSTOMIZAÇÃO APLICADA: '{$menu_title}' -> '{$custom_data['title']}'");
                }
            }
            
            // Aplicar ícone personalizado apenas se não estiver vazio
            if (isset($custom_data['icon']) && trim($custom_data['icon']) !== '') {
                $custom_icon = $custom_data['icon'];
                if (!str_starts_with($custom_icon, 'dashicons-')) {
                    $custom_icon = 'dashicons-' . $custom_icon;
                }
                $menu[$key][6] = $custom_icon;
            }
        }
    }
    
    // Aplicar customizações aos submenus
    if (is_array($submenu)) {
        foreach ($submenu as $parent_slug => $submenus) {
            if (!is_array($submenus)) continue;
            
            foreach ($submenus as $sub_key => $submenu_item) {
                if (empty($submenu_item[0]) || empty($submenu_item[2])) {
                    continue;
                }
                
                $submenu_slug = $submenu_item[2];
                $submenu_title = $submenu_item[0];
                
                // Criar chave do submenu para buscar customização
                $submenu_key = $parent_slug . '|' . $submenu_slug;
                
                // Debug: mostrar chave do submenu
                if (isset($_GET['debug_menus'])) {
                    error_log("[MPA SUBMENU DEBUG] Verificando submenu: $submenu_key (parent: $parent_slug, slug: $submenu_slug)");
                }
                
                // Buscar customização do submenu
                if (isset($menu_customizations['submenu_custom_title'][$submenu_key])) {
                    $custom_title = $menu_customizations['submenu_custom_title'][$submenu_key];
                    
                    // Aplicar customização apenas se não estiver vazia
                    if (trim($custom_title) !== '') {
                        $submenu[$parent_slug][$sub_key][0] = $custom_title;
                        
                        if (isset($_GET['debug_menus'])) {
                            error_log("CUSTOMIZAÇÃO SUBMENU APLICADA: '{$submenu_title}' -> '{$custom_title}'");
                        }
                    }
                } else {
                    if (isset($_GET['debug_menus'])) {
                        error_log("[MPA SUBMENU DEBUG] Customização não encontrada para: $submenu_key");
                    }
                }
            }
        }
    }
}

// Removido JavaScript complexo - dependendo apenas do PHP hook acima

// Função JavaScript removida - usando apenas o PHP hook

// AJAX handler para auto-save de customizações
add_action('wp_ajax_mpa_auto_save_customization', 'mpa_auto_save_customization_handler');

function mpa_auto_save_customization_handler() {
    // Verificar nonce de segurança
    if (!wp_verify_nonce($_POST['nonce'], 'mpa_auto_save')) {
        wp_send_json_error('Erro de segurança');
    }
    
    // Verificar permissões
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Sem permissões');
    }
    
    // Validar dados recebidos
    if (!isset($_POST['menu_slug']) || !isset($_POST['field_type'])) {
        wp_send_json_error('Dados inválidos');
    }
    
    $menu_slug = sanitize_text_field($_POST['menu_slug']);
    $field_type = sanitize_text_field($_POST['field_type']);
    $field_value = isset($_POST['field_value']) ? wp_strip_all_tags($_POST['field_value']) : '';
    
    // Verificar se é submenu
    $is_submenu = isset($_POST['is_submenu']) && $_POST['is_submenu'] === 'true';
    
    // Obter customizações atuais
    $menu_customizations = get_option('mpa_menu_customizations', array());
    
    try {
        if ($is_submenu) {
            // Para submenus
            if ($field_type === 'title') {
                if (!isset($menu_customizations['submenu_custom_title'])) {
                    $menu_customizations['submenu_custom_title'] = array();
                }
                $menu_customizations['submenu_custom_title'][$menu_slug] = $field_value;
            }
        } else {
            // Para menus principais
            if (!isset($menu_customizations[$menu_slug])) {
                $menu_customizations[$menu_slug] = array();
            }
            
            if ($field_type === 'title') {
                $menu_customizations[$menu_slug]['title'] = $field_value;
            } elseif ($field_type === 'icon') {
                $menu_customizations[$menu_slug]['icon'] = $field_value;
            }
            
            // Sincronizar com menus personalizados se for um menu custom
            if (strpos($menu_slug, 'mpa_custom_') === 0) {
                $custom_menu_id = str_replace('mpa_custom_', '', $menu_slug);
                $custom_menus = get_option('mpa_custom_menus', array());
                
                // Procurar o menu personalizado em todas as roles
                foreach ($custom_menus as $role => &$role_menus) {
                    if (isset($role_menus[$custom_menu_id])) {
                        if ($field_type === 'title') {
                            $role_menus[$custom_menu_id]['title'] = $field_value;
                        } elseif ($field_type === 'icon') {
                            $role_menus[$custom_menu_id]['icon'] = $field_value;
                        }
                        $role_menus[$custom_menu_id]['updated_at'] = current_time('mysql');
                        break; // Encontrou e atualizou, pode parar
                    }
                }
                
                // Salvar alterações no sistema de menus personalizados
                update_option('mpa_custom_menus', $custom_menus);
            }
        }
        
        // Salvar no banco de dados
        $result = update_option('mpa_menu_customizations', $menu_customizations);
        
        if ($result !== false) {
            wp_send_json_success(array(
                'message' => 'Salvo automaticamente',
                'data' => array(
                    'menu_slug' => $menu_slug,
                    'field_type' => $field_type,
                    'field_value' => $field_value,
                    'is_submenu' => $is_submenu
                )
            ));
        } else {
            wp_send_json_error('Erro ao salvar no banco de dados');
        }
        
    } catch (Exception $e) {
        wp_send_json_error('Erro interno: ' . $e->getMessage());
    }
}

// ============================================================
// HANDLERS AJAX PARA MENUS PERSONALIZADOS
// ============================================================

// Registrar os handlers AJAX para menus personalizados
add_action('wp_ajax_mpa_add_custom_menu', 'mpa_add_custom_menu_handler');
add_action('wp_ajax_mpa_delete_custom_menu', 'mpa_delete_custom_menu_handler');
add_action('wp_ajax_mpa_edit_custom_menu', 'mpa_edit_custom_menu_handler');

// Handler para adicionar menu personalizado
function mpa_add_custom_menu_handler() {
    // Verificar permissões
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Sem permissão para gerenciar menus.');
    }

    // Verificar nonce
    check_ajax_referer('mpa_custom_menu', '_ajax_nonce');

    // Validar e sanitizar dados
    $role = sanitize_text_field($_POST['role'] ?? '');
    $title = sanitize_text_field($_POST['title'] ?? '');
    $icon = sanitize_text_field($_POST['icon'] ?? 'dashicons-admin-generic');
    $url = esc_url_raw($_POST['url'] ?? '');

    if (empty($role) || empty($title) || empty($url)) {
        wp_send_json_error('Todos os campos são obrigatórios.');
    }

    try {
        // Obter menus personalizados existentes
        $custom_menus = get_option('mpa_custom_menus', array());
        
        // Inicializar array para a role se não existir
        if (!isset($custom_menus[$role])) {
            $custom_menus[$role] = array();
        }

        // Gerar ID único para o menu
        $menu_id = 'custom_menu_' . time() . '_' . wp_rand(100, 999);

        // Garantir que o ícone tenha o prefixo correto
        if (!str_starts_with($icon, 'dashicons-')) {
            $icon = 'dashicons-' . ltrim($icon, 'dashicons-');
        }

        // Adicionar o novo menu
        $custom_menus[$role][$menu_id] = array(
            'title' => $title,
            'icon' => $icon,
            'url' => $url,
            'created_at' => current_time('mysql')
        );

        // Salvar no banco de dados
        if (update_option('mpa_custom_menus', $custom_menus)) {
            wp_send_json_success(array(
                'message' => 'Menu personalizado adicionado com sucesso!',
                'menu_id' => $menu_id,
                'menu_data' => $custom_menus[$role][$menu_id]
            ));
        } else {
            wp_send_json_error('Erro ao salvar menu no banco de dados.');
        }

    } catch (Exception $e) {
        error_log('Erro ao adicionar menu personalizado: ' . $e->getMessage());
        wp_send_json_error('Erro interno do servidor.');
    }
}

// Handler para excluir menu personalizado
function mpa_delete_custom_menu_handler() {
    // Verificar permissões
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Sem permissão para gerenciar menus.');
    }

    // Verificar nonce
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'mpa_custom_menu')) {
        wp_send_json_error('Nonce inválido.');
    }

    // Validar dados
    $role = sanitize_text_field($_POST['role'] ?? '');
    $menu_id_raw = sanitize_text_field($_POST['menu_id'] ?? '');

    if (empty($role) || empty($menu_id_raw)) {
        wp_send_json_error('Role e ID do menu são obrigatórios.');
    }

    // O menu_id já vem completo (com prefixo 'custom_menu_') do frontend
    // Não precisamos modificar o ID, pois ele é armazenado com o prefixo
    $menu_id = $menu_id_raw;

    try {
        // Obter menus personalizados existentes
        $custom_menus = get_option('mpa_custom_menus', array());
        
        // Verificar se a role e o menu existem
        if (!isset($custom_menus[$role]) || !isset($custom_menus[$role][$menu_id])) {
            wp_send_json_error('Menu personalizado não encontrado.');
        }

        // Salvar informações do menu antes de excluir (para log)
        $deleted_menu = $custom_menus[$role][$menu_id];
        
        // Excluir o menu
        unset($custom_menus[$role][$menu_id]);

        // Se não há mais menus personalizados para esta role, limpar o array
        if (empty($custom_menus[$role])) {
            unset($custom_menus[$role]);
        }

        // Salvar alterações dos menus personalizados
        update_option('mpa_custom_menus', $custom_menus);
        
        // Remover também das permissões de menu para sincronização
        $menu_permissions = get_option('mpa_menu_permissions', array());
        $custom_slug = 'custom_' . $menu_id;
        
        foreach ($menu_permissions as $role_key => $role_perms) {
            if (isset($role_perms['menus'][$custom_slug])) {
                unset($menu_permissions[$role_key]['menus'][$custom_slug]);
            }
        }
        
        update_option('mpa_menu_permissions', $menu_permissions);
        
        // Remover da ordem de menus
        $menu_order = get_option('mpa_menu_order', array());
        foreach ($menu_order as $role_key => $order) {
            if (is_array($order) && ($key = array_search($custom_slug, $order)) !== false) {
                unset($menu_order[$role_key][$key]);
                $menu_order[$role_key] = array_values($menu_order[$role_key]);
            }
        }
        
        update_option('mpa_menu_order', $menu_order);
        
        // Log da exclusão
        error_log("Menu personalizado completamente removido - Role: {$role}, ID: {$menu_id}, Título: {$deleted_menu['title']}");
        
        wp_send_json_success(array(
            'message' => 'Menu personalizado removido completamente de todas as telas!',
            'deleted_menu' => $deleted_menu
        ));

    } catch (Exception $e) {
        error_log('Erro ao excluir menu personalizado: ' . $e->getMessage());
        wp_send_json_error('Erro interno do servidor.');
    }
}

// Handler para editar menu personalizado
function mpa_edit_custom_menu_handler() {
    // Verificar permissões
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Sem permissão para gerenciar menus.');
    }

    // Verificar nonce
    check_ajax_referer('mpa_custom_menu', '_ajax_nonce');

    // Validar dados
    $role = sanitize_text_field($_POST['role'] ?? '');
    $menu_id = sanitize_text_field($_POST['menu_id'] ?? '');
    $new_title = sanitize_text_field($_POST['title'] ?? '');
    $new_icon = sanitize_text_field($_POST['icon'] ?? 'dashicons-admin-generic');
    $new_url = esc_url_raw($_POST['url'] ?? '');

    if (empty($role) || empty($menu_id) || empty($new_title) || empty($new_url)) {
        wp_send_json_error('Todos os campos são obrigatórios.');
    }

    try {
        // Obter menus personalizados existentes
        $custom_menus = get_option('mpa_custom_menus', array());
        
        // Verificar se a role e o menu existem
        if (!isset($custom_menus[$role]) || !isset($custom_menus[$role][$menu_id])) {
            wp_send_json_error('Menu personalizado não encontrado.');
        }

        // Atualizar o menu
        $custom_menus[$role][$menu_id]['title'] = $new_title;
        $custom_menus[$role][$menu_id]['icon'] = $new_icon;
        $custom_menus[$role][$menu_id]['url'] = $new_url;
        $custom_menus[$role][$menu_id]['updated_at'] = current_time('mysql');
        
        // Salvar alterações no sistema de menus personalizados
        update_option('mpa_custom_menus', $custom_menus);
        
        // Sincronizar com o sistema de customizações de menu (para a barra lateral)
        $menu_slug = 'mpa_custom_' . $menu_id;
        $menu_customizations = get_option('mpa_menu_customizations', array());
        
        if (!isset($menu_customizations[$menu_slug])) {
            $menu_customizations[$menu_slug] = array();
        }
        
        $menu_customizations[$menu_slug]['title'] = $new_title;
        $menu_customizations[$menu_slug]['icon'] = $new_icon;
        
        // Salvar alterações no sistema de customizações
        update_option('mpa_menu_customizations', $menu_customizations);
        
        // Log da edição
        error_log("Menu personalizado editado - Role: {$role}, ID: {$menu_id}, Novo Título: {$new_title}");
        
        wp_send_json_success(array(
            'message' => 'Menu personalizado atualizado com sucesso!',
            'updated_menu' => $custom_menus[$role][$menu_id]
        ));
        
    } catch (Exception $e) {
        error_log('Erro ao editar menu personalizado: ' . $e->getMessage());
        wp_send_json_error('Erro interno do servidor.');
    }
}

// ============================================================
// INTEGRAÇÃO DOS MENUS PERSONALIZADOS NO ADMIN
// ============================================================

// Adicionar menus personalizados ao admin do WordPress
add_action('admin_menu', 'mpa_add_custom_menus_to_admin', 998); // Ativar antes da reordenação

function mpa_add_custom_menus_to_admin() {
    // Verificar se o usuário está logado
    if (!is_user_logged_in()) {
        return;
    }

    // Obter o usuário atual
    $user = wp_get_current_user();
    if (!$user || empty($user->roles)) {
        return;
    }

    // Obter menus personalizados
    $custom_menus = get_option('mpa_custom_menus', array());
    
    if (empty($custom_menus)) {
        return;
    }

    // Adicionar menus personalizados para cada role do usuário
    foreach ($user->roles as $user_role) {
        
        // Lista de roles para verificar (incluindo variações plural/singular)
        $roles_to_check = [$user_role];
        
        // Adicionar variações comuns
        if ($user_role === 'gerentes') {
            $roles_to_check[] = 'gerente';
        } elseif ($user_role === 'gerente') {
            $roles_to_check[] = 'gerentes';
        } elseif ($user_role === 'editores') {
            $roles_to_check[] = 'editor';
        } elseif ($user_role === 'editor') {
            $roles_to_check[] = 'editores';
        }
        
        $role_menus = array();
        
        // Coletar menus de todas as variações do role
        foreach ($roles_to_check as $role_variation) {
            if (isset($custom_menus[$role_variation]) && !empty($custom_menus[$role_variation])) {
                $role_menus = array_merge($role_menus, $custom_menus[$role_variation]);
            }
        }
        
        if (empty($role_menus)) {
            continue;
        }


        // Obter ordem dos menus salvos para determinar posições corretas
        $custom_order = get_option('mpa_menu_order', array());
        
        // Obter ordem global dos menus para posicionamento correto
        $current_role_order = !empty($custom_order) ? $custom_order : array();
        
        // Criar array de menus na ordem correta
        $ordered_role_menus = array();
        
        // Primeiro, adicionar menus que estão na ordem definida
        foreach ($current_role_order as $ordered_slug) {
            // Converter tanto custom_ quanto mpa_custom_ para pegar o ID
            if (strpos($ordered_slug, 'mpa_custom_') === 0) {
                $menu_id_from_slug = str_replace('mpa_custom_', '', $ordered_slug);
            } elseif (strpos($ordered_slug, 'custom_') === 0) {
                $menu_id_from_slug = str_replace('custom_', '', $ordered_slug);
            } else {
                continue;
            }
            
            if (isset($role_menus[$menu_id_from_slug])) {
                $ordered_role_menus[$menu_id_from_slug] = $role_menus[$menu_id_from_slug];
                unset($role_menus[$menu_id_from_slug]);
            }
        }
        
        // Depois, adicionar menus que não estão na ordem (novos menus)
        foreach ($role_menus as $menu_id => $menu_data) {
            $ordered_role_menus[$menu_id] = $menu_data;
        }
        
        // Adicionar cada menu personalizado SEM posicionamento específico
        // O posicionamento será feito pela função mpa_apply_menu_order()
        foreach ($ordered_role_menus as $menu_id => $menu_data) {
            $title = sanitize_text_field($menu_data['title'] ?? 'Menu Personalizado');
            $icon = sanitize_text_field($menu_data['icon'] ?? 'dashicons-admin-generic');
            $url = $menu_data['url'] ?? '#';

            // Gerar um slug único para o menu
            $menu_slug = 'mpa_custom_' . $menu_id;
            
            
            $result = add_menu_page(
                $title,                              // page_title
                $title,                              // menu_title
                'read',                              // capability
                $menu_slug,                          // menu_slug
                function() use ($url) {              // callback - redirecionar diretamente
                    wp_safe_redirect($url);
                    exit;
                },
                $icon,                               // icon_url
                null                                 // Deixar WordPress escolher posição, mpa_apply_menu_order() reorganizará
            );
            
        }
    }
}

// Callback vazio para menus de redirect
function mpa_custom_menu_redirect() {
    // Esta função nunca será chamada pois o redirect acontece antes
    wp_die('Redirecionamento não funcionou corretamente.');
}

// Página para URLs que não são redirects
function mpa_custom_menu_page($title, $url) {
    echo '<div class="wrap">';
    echo '<h1>' . esc_html($title) . '</h1>';
    
    if (filter_var($url, FILTER_VALIDATE_URL)) {
        // URL externa - mostrar iframe ou link
        echo '<div class="notice notice-info">';
        echo '<p><strong>Este menu aponta para um link externo:</strong></p>';
        echo '<p><a href="' . esc_url($url) . '" target="_blank" class="button button-primary">' . esc_html($title) . ' ➚</a></p>';
        echo '</div>';
        
        echo '<iframe src="' . esc_url($url) . '" style="width: 100%; height: 600px; border: 1px solid #ddd;" title="' . esc_attr($title) . '"></iframe>';
    } else {
        // URL inválida ou não reconhecida
        echo '<div class="notice notice-warning">';
        echo '<p><strong>Configuração do menu:</strong></p>';
        echo '<p>URL configurada: <code>' . esc_html($url) . '</code></p>';
        echo '<p>Este menu personalizado pode estar configurado incorretamente. Entre em contato com o administrador.</p>';
        echo '</div>';
    }
    
    echo '</div>';
}

// Enfileirar scripts necessários para AJAX na página de administração
add_action('admin_enqueue_scripts', function($hook) {
    // Verificar se estamos na página correta
    if (strpos($hook, 'mpa-menu-roles') !== false) {
        // Enfileirar jQuery primeiro
        wp_enqueue_script('jquery');
        
        // Usar ajaxurl padrão do WordPress para AJAX
        wp_localize_script('jquery', 'mpa_ajax_object', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'ajax_nonce' => wp_create_nonce('mpa_custom_menu')
        ));
    }
});

// Log de debug para menus personalizados (apenas em desenvolvimento)
add_action('wp_loaded', function() {
    if (defined('WP_DEBUG') && WP_DEBUG && isset($_GET['debug_custom_menus'])) {
        $user = wp_get_current_user();
        $custom_menus = get_option('mpa_custom_menus', array());
        
        echo '<pre style="background: #f1f1f1; padding: 15px; margin: 10px; border: 1px solid #ddd;">';
        echo '<h3>DEBUG: Menus Personalizados</h3>';
        echo '<strong>Usuário:</strong> ' . $user->user_login . ' (ID: ' . $user->ID . ')<br>';
        echo '<strong>Roles:</strong> ' . implode(', ', $user->roles) . '<br><br>';
        echo '<strong>Menus personalizados no banco:</strong><br>';
        print_r($custom_menus);
        echo '</pre>';
    }
});

