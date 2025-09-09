<?php
// Gerenciador de Menus por Role

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
        'mpa_menu_roles_page'          // Function
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
function mpa_menu_roles_page() {
    // Salvar configurações se formulário foi submetido
    if (isset($_POST['submit'])) {
        check_admin_referer('mpa_menu_roles_nonce');
        
        // Obter role selecionada
        $selected_role = $_POST['selected_role'] ?? '';
        if (empty($selected_role) || $selected_role === 'administrator') {
            echo '<div class="notice notice-error"><p>Role inválida selecionada!</p></div>';
            return;
        }
        
        // Obter configurações atuais
        $current_permissions = get_option('mpa_menu_permissions', array());
        $admin_menus = mpa_get_admin_menus();
        
        // Inicializar a role se não existir
        if (!isset($current_permissions[$selected_role])) {
            $current_permissions[$selected_role] = array();
        }
        if (!isset($current_permissions[$selected_role]['submenus'])) {
            $current_permissions[$selected_role]['submenus'] = array();
        }
        
        // Processar cada menu principal para a role selecionada
        foreach ($admin_menus as $menu_item) {
            $menu_slug = $menu_item['slug'];
            
            // Verificar se o checkbox foi marcado
            $is_checked = isset($_POST['menu_permissions'][$menu_slug]);
            $current_permissions[$selected_role][$menu_slug] = $is_checked;
            
            // Processar submenus se existirem
            if (!empty($menu_item['submenus'])) {
                foreach ($menu_item['submenus'] as $submenu_item) {
                    $submenu_key = $menu_item['slug'] . '|' . $submenu_item['slug'];
                    
                    // Verificar se o checkbox do submenu foi marcado
                    $is_submenu_checked = isset($_POST['submenu_permissions'][$submenu_key]);
                    $current_permissions[$selected_role]['submenus'][$submenu_key] = $is_submenu_checked;
                }
            }
        }
        
        update_option('mpa_menu_permissions', $current_permissions);
        
        echo '<div class="notice notice-success"><p>Configurações salvas para a role <strong>' . esc_html($selected_role) . '</strong>!</p></div>';
    }
    
    // Obter configurações atuais
    $current_permissions = get_option('mpa_menu_permissions', array());
    
    // Obter todas as roles do WordPress (exceto administrator)
    $roles = wp_roles();
    $all_roles = $roles->get_names();
    unset($all_roles['administrator']); // Remover administrator
    
    // Role selecionada (padrão: primeira role disponível)
    $selected_role = $_GET['role'] ?? $_POST['selected_role'] ?? array_key_first($all_roles);
    
    // Obter todos os menus do admin
    $admin_menus = mpa_get_admin_menus();
    
    ?>
    <div class="wrap">
        <h1>Gerenciar Menus por Role</h1>
        <p>Selecione uma role e configure quais menus ela pode acessar. Desmarque os menus que você deseja remover.</p>
        
        <!-- Seletor de Role -->
        <div class="mpa-role-selector">
            <h2>Selecione a Role:</h2>
            <div class="mpa-role-tabs">
                <?php foreach ($all_roles as $role_key => $role_name): ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=mpa-menu-roles&role=' . $role_key)); ?>" 
                       class="mpa-role-tab <?php echo ($selected_role === $role_key) ? 'active' : ''; ?>">
                        <?php echo esc_html($role_name); ?>
                        <?php
                        // Mostrar contador de menus bloqueados
                        $blocked_count = 0;
                        if (isset($current_permissions[$role_key])) {
                            foreach ($current_permissions[$role_key] as $key => $value) {
                                if ($key !== 'submenus' && $value === false) {
                                    $blocked_count++;
                                }
                            }
                            if (isset($current_permissions[$role_key]['submenus'])) {
                                foreach ($current_permissions[$role_key]['submenus'] as $value) {
                                    if ($value === false) {
                                        $blocked_count++;
                                    }
                                }
                            }
                        }
                        if ($blocked_count > 0): ?>
                            <span class="mpa-blocked-count"><?php echo $blocked_count; ?></span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        
        <?php if (!empty($selected_role)): ?>
        <form method="post" action="">
            <?php wp_nonce_field('mpa_menu_roles_nonce'); ?>
            <input type="hidden" name="selected_role" value="<?php echo esc_attr($selected_role); ?>" />
            
            <div class="mpa-role-config">
                <h3>Configurações para: <span class="mpa-role-name"><?php echo esc_html($all_roles[$selected_role]); ?></span></h3>
                
                <!-- Botões de ação rápida -->
                <div class="mpa-quick-actions">
                    <button type="button" id="mpa-select-all" class="button">✓ Marcar Todos</button>
                    <button type="button" id="mpa-select-none" class="button">✗ Desmarcar Todos</button>
                    <button type="button" id="mpa-select-core" class="button">⚙️ Apenas Core WP</button>
                </div>
                
                <div class="mpa-menu-list">
                    <?php foreach ($admin_menus as $menu_item): ?>
                        <div class="mpa-menu-item">
                            <div class="mpa-menu-main">
                                <?php
                                // Verificar se menu está marcado
                                $is_checked = isset($current_permissions[$selected_role][$menu_item['slug']]) 
                                    ? ($current_permissions[$selected_role][$menu_item['slug']] === true)
                                    : true; // Padrão habilitado
                                ?>
                                <label class="mpa-menu-label">
                                    <input type="checkbox" 
                                           name="menu_permissions[<?php echo esc_attr($menu_item['slug']); ?>]" 
                                           value="1" 
                                           <?php checked($is_checked); ?>
                                           class="mpa-menu-checkbox"
                                           data-menu-type="<?php echo (strpos($menu_item['title'], '🏷️') !== false) ? 'taxonomy' : 'core'; ?>" />
                                    <span class="mpa-menu-icon">
                                        <?php if (!empty($menu_item['icon'])): ?>
                                            <span class="dashicons <?php echo esc_attr($menu_item['icon']); ?>"></span>
                                        <?php else: ?>
                                            <span class="dashicons dashicons-admin-generic"></span>
                                        <?php endif; ?>
                                    </span>
                                    <span class="mpa-menu-title"><?php echo esc_html($menu_item['title']); ?></span>
                                    <code class="mpa-menu-slug"><?php echo esc_html($menu_item['slug']); ?></code>
                                </label>
                            </div>
                            
                            <?php if (!empty($menu_item['submenus'])): ?>
                                <div class="mpa-submenu-list">
                                    <?php foreach ($menu_item['submenus'] as $submenu_item): ?>
                                        <div class="mpa-submenu-item">
                                            <?php
                                            $submenu_key = $menu_item['slug'] . '|' . $submenu_item['slug'];
                                            $is_sub_checked = isset($current_permissions[$selected_role]['submenus'][$submenu_key])
                                                ? ($current_permissions[$selected_role]['submenus'][$submenu_key] === true)
                                                : true; // Padrão habilitado
                                            ?>
                                            <label class="mpa-submenu-label">
                                                <input type="checkbox" 
                                                       name="submenu_permissions[<?php echo esc_attr($submenu_key); ?>]" 
                                                       value="1" 
                                                       <?php checked($is_sub_checked); ?>
                                                       class="mpa-submenu-checkbox" />
                                                <span class="mpa-submenu-title">↳ <?php echo esc_html($submenu_item['title']); ?></span>
                                                <code class="mpa-submenu-slug"><?php echo esc_html($submenu_item['slug']); ?></code>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="mpa-submit-section">
                <?php submit_button('Salvar Configurações para ' . $all_roles[$selected_role], 'primary', 'submit', false); ?>
                <p class="description">
                    As configurações serão aplicadas apenas para a role <strong><?php echo esc_html($all_roles[$selected_role]); ?></strong>.
                    Para configurar outras roles, use as abas acima.
                </p>
            </div>
        </form>
        <?php endif; ?>
    </div>
    
    <style>
        /* Seletor de Role */
        .mpa-role-selector {
            margin: 20px 0;
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 15px;
        }
        
        .mpa-role-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin-top: 10px;
        }
        
        .mpa-role-tab {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 8px 15px;
            background: #f1f1f1;
            border: 1px solid #ddd;
            border-radius: 3px;
            text-decoration: none;
            color: #555;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .mpa-role-tab:hover {
            background: #e0e0e0;
            color: #333;
            text-decoration: none;
        }
        
        .mpa-role-tab.active {
            background: #0073aa;
            color: white;
            border-color: #005a87;
        }
        
        .mpa-blocked-count {
            background: #d63638;
            color: white;
            border-radius: 50%;
            font-size: 11px;
            font-weight: bold;
            padding: 2px 6px;
            min-width: 18px;
            text-align: center;
        }
        
        /* Configuração da Role */
        .mpa-role-config {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .mpa-role-name {
            color: #0073aa;
            font-weight: bold;
        }
        
        .mpa-quick-actions {
            margin: 15px 0;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 3px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        /* Lista de Menus */
        .mpa-menu-list {
            margin-top: 20px;
        }
        
        .mpa-menu-item {
            border: 1px solid #e1e1e1;
            border-radius: 4px;
            margin-bottom: 10px;
            background: #fff;
        }
        
        .mpa-menu-main {
            padding: 12px 15px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .mpa-menu-label {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            font-weight: 500;
        }
        
        .mpa-menu-checkbox {
            transform: scale(1.2);
            margin: 0;
        }
        
        .mpa-menu-icon .dashicons {
            color: #666;
            font-size: 18px;
            width: 18px;
            height: 18px;
        }
        
        .mpa-menu-title {
            flex: 1;
            color: #23282d;
        }
        
        .mpa-menu-slug {
            font-size: 11px;
            color: #666;
            background: #f1f1f1;
            padding: 2px 6px;
            border-radius: 2px;
        }
        
        /* Submenus */
        .mpa-submenu-list {
            background: #fafafa;
        }
        
        .mpa-submenu-item {
            padding: 8px 15px 8px 40px;
            border-top: 1px solid #f0f0f0;
        }
        
        .mpa-submenu-label {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .mpa-submenu-checkbox {
            transform: scale(1.1);
            margin: 0;
        }
        
        .mpa-submenu-title {
            flex: 1;
            color: #555;
        }
        
        .mpa-submenu-slug {
            font-size: 10px;
            color: #999;
            background: #eee;
            padding: 1px 4px;
            border-radius: 2px;
        }
        
        /* Submit Section */
        .mpa-submit-section {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }
        
        /* Hover effects */
        .mpa-menu-item:hover .mpa-menu-main {
            background: #f9f9f9;
        }
        
        .mpa-submenu-item:hover {
            background: #f5f5f5;
        }
        
        /* Estados dos checkboxes */
        .mpa-menu-checkbox:checked + .mpa-menu-icon .dashicons {
            color: #00a32a;
        }
        
        .mpa-submenu-checkbox:checked ~ .mpa-submenu-title {
            color: #00a32a;
        }
    </style>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Botão Marcar Todos
            document.getElementById('mpa-select-all').addEventListener('click', function() {
                document.querySelectorAll('.mpa-menu-checkbox, .mpa-submenu-checkbox').forEach(cb => {
                    cb.checked = true;
                });
            });
            
            // Botão Desmarcar Todos
            document.getElementById('mpa-select-none').addEventListener('click', function() {
                document.querySelectorAll('.mpa-menu-checkbox, .mpa-submenu-checkbox').forEach(cb => {
                    cb.checked = false;
                });
            });
            
            // Botão Apenas Core WP (desmarcar taxonomias e plugins)
            document.getElementById('mpa-select-core').addEventListener('click', function() {
                document.querySelectorAll('.mpa-menu-checkbox').forEach(cb => {
                    const menuType = cb.getAttribute('data-menu-type');
                    cb.checked = (menuType === 'core');
                });
                // Desmarcar todos os submenus para simplificar
                document.querySelectorAll('.mpa-submenu-checkbox').forEach(cb => {
                    cb.checked = true;
                });
            });
        });
    </script>
    <?php
}

// Função para obter todos os menus do admin (ATUALIZADA COM TAXONOMIAS)
function mpa_get_admin_menus() {
    global $menu, $submenu;
    
    $admin_menus = [];
    
    if (!is_array($menu)) return $admin_menus;
    
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
    
    // Adicionar taxonomias como itens separados
    $taxonomies = get_taxonomies(['public' => true, 'show_ui' => true], 'objects');
    foreach ($taxonomies as $taxonomy) {
        if (in_array($taxonomy->name, ['nav_menu', 'link_category', 'post_format'])) {
            continue; // Pular taxonomias especiais
        }
        
        $admin_menus[] = [
            'title'    => '🏷️ ' . $taxonomy->label,
            'slug_raw' => 'edit-tags.php?taxonomy=' . $taxonomy->name,
            'slug'     => $taxonomy->name,  // usar taxonomy name como chave
            'icon'     => 'dashicons-tag',
            'submenus' => []
        ];
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
    error_log("[MPA NOVO DEBUG] === mpa_user_can_see_menu ===");
    error_log("[MPA NOVO DEBUG] Verificando menu: $menu_slug");
    error_log("[MPA NOVO DEBUG] User roles: " . implode(', ', $user_roles));
    error_log("[MPA NOVO DEBUG] Permissões disponíveis: " . print_r($menu_permissions, true));

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

// 4. Aplicação robusta de restrições
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
        $slug = mpa_normalize_slug($menu_item['slug']);
        
        if (!mpa_user_can_see_menu($slug, $roles, $opts)) {
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

// 6. Bloqueio robusto por current_screen - REMOVIDO A PEDIDO DO USUÁRIO  
/*
add_action('current_screen', function($screen) {
    $user = wp_get_current_user();
    if (in_array('administrator', (array) $user->roles, true)) return;

    $user = wp_get_current_user();
    $roles = (array) $user->roles;
    $opts = get_option('mpa_menu_permissions', []);
    
    if (empty($roles) || empty($opts)) return;

    $screen_id = $screen->id;
    $map = mpa_screen_to_menu_slug_map();

    if (isset($map[$screen_id])) {
        [$parent_slug, $child_slug] = $map[$screen_id];
        $parent_slug = mpa_normalize_slug($parent_slug);
        
        $allowed = mpa_user_can_see_menu($parent_slug, $roles, $opts);

        if (!$allowed) {
            wp_die(
                '<h1>🚫 Acesso Negado</h1>' .
                '<p>Você não tem permissão para acessar esta tela.</p>' .
                '<p>Screen ID: <code>' . esc_html($screen_id) . '</code></p>' .
                '<p>Menu: <code>' . esc_html($parent_slug) . '</code></p>' .
                '<p><a href="' . esc_url(admin_url()) . '">← Voltar ao painel</a></p>', 
                'Acesso Negado', 
                ['response' => 403]
            );
        }

        // Verificar submenu se aplicável
        if ($child_slug) {
            $child_slug = mpa_normalize_slug($child_slug);
            $allowed_sub = mpa_user_can_see_submenu($parent_slug, $child_slug, $roles, $opts);
            
            if (!$allowed_sub) {
                wp_die(
                    '<h1>🚫 Acesso Negado</h1>' .
                    '<p>Você não tem permissão para acessar este submenu.</p>' .
                    '<p>Screen ID: <code>' . esc_html($screen_id) . '</code></p>' .
                    '<p>Submenu: <code>' . esc_html($parent_slug . '|' . $child_slug) . '</code></p>' .
                    '<p><a href="' . esc_url(admin_url()) . '">← Voltar ao painel</a></p>', 
                    'Acesso Negado', 
                    ['response' => 403]
                );
            }
        }
    }
});
*/

// 7. Geração dinâmica do mapa de screen ID para slug
function mpa_generate_screen_to_slug_map() {
    global $menu, $submenu;
    
    $map = [];
    
    foreach ($menu as $menu_item) {
        $parent_slug = $menu_item[2] ?? '';
        if (!$parent_slug) continue;
        
        $screen_id = 'toplevel_page_' . sanitize_title($parent_slug);
        $map[$screen_id] = [$parent_slug, null];
        
        if (isset($submenu[$parent_slug])) {
            foreach ($submenu[$parent_slug] as $sub_item) {
                $child_slug = $sub_item[2] ?? '';
                if (!$child_slug) continue;
                
                $screen_id = sanitize_title($parent_slug) . '_page_' . sanitize_title($child_slug);
                $map[$screen_id] = [$parent_slug, $child_slug];
            }
        }
    }
    
    return $map;
}

// 8. Cache do mapa de screen ID
function mpa_cached_screen_to_menu_slug_map() {
    static $cached_map = null;
    if ($cached_map === null) {
        $cached_map = array_merge(
            mpa_generate_screen_to_slug_map(),
            mpa_screen_to_menu_slug_map() // Manter mapa manual existente
        );
    }
    return $cached_map;
}

// 9. Normalização alternativa para alguns casos
function mpa_normalize_slug_alt($slug) {
    $slug = str_replace('admin.php?page=', '', $slug);
    $slug = str_replace('edit.php?', '', $slug);
    $slug = str_replace('.php', '', $slug);
    $slug = str_replace(['?', '=', '&'], '-', $slug);
    return sanitize_title($slug);
}

// 10. Bloqueio robusto por acesso direto a URLs - REMOVIDO A PEDIDO DO USUÁRIO
// add_action('admin_init', 'mpa_block_direct_page_access');

function mpa_block_direct_page_access_DISABLED() {
    // Log básico SEMPRE para verificar se função executa
    error_log('[MPA NOVO DEBUG] === FUNÇÃO EXECUTADA ===');
    
    $user = wp_get_current_user();
    
    // Administradores têm acesso total
    if (in_array('administrator', (array) $user->roles, true)) {
        error_log('[MPA NOVO DEBUG] Usuário é administrador, liberando acesso');
        return;
    }
    
    error_log('[MPA NOVO DEBUG] Usuário não é admin: ' . $user->user_login . ' (roles: ' . implode(', ', $user->roles) . ')');
    error_log('[MPA NOVO DEBUG] URL acessada: ' . $_SERVER['REQUEST_URI']);
    
    $roles = (array) $user->roles;
    $opts = get_option('mpa_menu_permissions', []);
    
    if (empty($roles) || empty($opts)) {
        error_log('[MPA NOVO DEBUG] Saindo - roles vazias ou permissões vazias');
        return;
    }
    
    $current_page = $_GET['page'] ?? '';
    $current_file = basename($_SERVER['PHP_SELF']);
    
    // Debug para desenvolvimento
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('[MPA DEBUG] Bloqueio - Page: ' . $current_page . ', File: ' . $current_file);
        error_log('[MPA DEBUG] User roles: ' . implode(', ', $roles));
        error_log('[MPA DEBUG] Permissions: ' . print_r($opts, true));
    }
    
    // Verificar admin.php?page=...
    if ($current_page) {
        $normalized_page = mpa_normalize_slug($current_page);
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        $screen_id = is_object($screen) ? $screen->id : '';
        
        // Log detalhado para debugging
        error_log('[MPA NOVO DEBUG] === PÁGINA DETECTADA ===');
        error_log('[MPA NOVO DEBUG] Current page raw: ' . $current_page);
        error_log('[MPA NOVO DEBUG] Current page normalized: ' . $normalized_page);
        error_log('[MPA NOVO DEBUG] Screen ID: ' . $screen_id);
        
        $map = mpa_cached_screen_to_menu_slug_map();
        
        if (isset($map[$screen_id])) {
            [$parent_slug, $child_slug] = $map[$screen_id];
            
            $parent_normalized = mpa_normalize_slug($parent_slug);
            $child_normalized = $child_slug ? mpa_normalize_slug($child_slug) : null;
            
            if (!mpa_user_can_see_menu($parent_normalized, $roles, $opts)) {
                wp_die(
                    '<h1>🚫 Acesso Negado</h1>' .
                    '<p>Você não tem permissão para acessar este menu.</p>' .
                    '<p>Menu: <code>' . esc_html($parent_slug) . '</code></p>' .
                    '<p>Screen ID: <code>' . esc_html($screen_id) . '</code></p>' .
                    '<p><a href="' . esc_url(admin_url()) . '">← Voltar ao painel</a></p>',
                    'Acesso Negado',
                    ['response' => 403]
                );
            }
            
            if ($child_normalized && !mpa_user_can_see_submenu($parent_normalized, $child_normalized, $roles, $opts)) {
                wp_die(
                    '<h1>🚫 Acesso Negado</h1>' .
                    '<p>Você não tem permissão para acessar este submenu.</p>' .
                    '<p>Submenu: <code>' . esc_html($child_slug) . '</code></p>' .
                    '<p>Screen ID: <code>' . esc_html($screen_id) . '</code></p>' .
                    '<p><a href="' . esc_url(admin_url()) . '">← Voltar ao painel</a></p>',
                    'Acesso Negado',
                    ['response' => 403]
                );
            }
        } else {
            // Fallback: testar diretamente o slug normalizado
            error_log('[MPA NOVO DEBUG] Página não encontrada no mapa, testando diretamente: ' . $normalized_page);
            
            // Testar vários formatos da página
            $test_slugs = [
                $normalized_page,           // ex: wc-admin
                $current_page,             // ex: wc-admin&path=%2Fcustomers
                strtok($current_page, '&') // ex: wc-admin (apenas antes do &)
            ];
            
            $page_blocked = true;
            foreach ($test_slugs as $test_slug) {
                if (mpa_user_can_see_menu($test_slug, $roles, $opts)) {
                    error_log('[MPA NOVO DEBUG] Página permitida pelo slug: ' . $test_slug);
                    $page_blocked = false;
                    break;
                }
                error_log('[MPA NOVO DEBUG] Testado slug: ' . $test_slug . ' - BLOQUEADO');
            }
            
            if ($page_blocked) {
                error_log('[MPA NOVO DEBUG] BLOQUEANDO ACESSO À PÁGINA: ' . $current_page);
                wp_die(
                    '<h1>🚫 Acesso Negado</h1>' .
                    '<p>Você não tem permissão para acessar esta página.</p>' .
                    '<p>Página: <code>' . esc_html($current_page) . '</code></p>' .
                    '<p>Slugs testados: <code>' . implode(', ', $test_slugs) . '</code></p>' .
                    '<p><a href="' . esc_url(admin_url()) . '">← Voltar ao painel</a></p>',
                    'Acesso Negado',
                    ['response' => 403]
                );
            }
        }
    }
    
    // Verificar arquivos diretos como edit.php, upload.php, etc.
    if ($current_file && $current_file !== 'admin.php') {
        $normalized_file = mpa_normalize_slug($current_file);
        
        if (!mpa_user_can_see_menu($normalized_file, $roles, $opts)) {
            wp_die(
                '<h1>🚫 Acesso Negado</h1>' .
                '<p>Você não tem permissão para acessar este arquivo.</p>' .
                '<p>Arquivo: <code>' . esc_html($current_file) . '</code></p>' .
                '<p><a href="' . esc_url(admin_url()) . '">← Voltar ao painel</a></p>',
                'Acesso Negado',
                ['response' => 403]
            );
        }
    }
    
    // Verificar acesso a taxonomias (categorias, tags, etc.)
    if ($current_file === 'edit-tags.php') {
        $taxonomy = $_GET['taxonomy'] ?? '';
        if ($taxonomy) {
            // Como as taxonomias agora são salvas usando taxonomy->name,
            // precisamos testar diretamente o nome da taxonomia
            $taxonomy_blocked = !mpa_user_can_see_menu($taxonomy, $roles, $opts);
            
            // Debug SEMPRE ativo para debugging
            error_log('[MPA NOVO DEBUG] === TESTE DE TAXONOMIA ===');
            error_log('[MPA NOVO DEBUG] Taxonomia: ' . $taxonomy);
            error_log('[MPA NOVO DEBUG] User roles: ' . implode(', ', $roles));
            error_log('[MPA NOVO DEBUG] Permissões disponíveis: ' . print_r($opts, true));
            error_log('[MPA NOVO DEBUG] Resultado mpa_user_can_see_menu: ' . ($taxonomy_blocked ? 'BLOQUEADO' : 'PERMITIDO'));
            error_log('[MPA NOVO DEBUG] ========================');
            
            if ($taxonomy_blocked) {
                wp_die(
                    '<h1>🚫 Acesso Negado</h1>' .
                    '<p>Você não tem permissão para acessar esta taxonomia.</p>' .
                    '<p>Taxonomia: <code>' . esc_html($taxonomy) . '</code></p>' .
                    '<p>Arquivo: <code>edit-tags.php</code></p>' .
                    '<p><a href="' . esc_url(admin_url()) . '">← Voltar ao painel</a></p>',
                    'Acesso Negado',
                    ['response' => 403]
                );
            }
        }
    }
    
    // Verificar acesso a outros arquivos específicos com parâmetros
    $file_param_checks = [
        'edit.php' => [
            'post_type' => [
                'page' => ['edit.php?post_type=page', 'page'],
                'product' => ['edit.php?post_type=product', 'product'],
            ]
        ],
        'post-new.php' => [
            'post_type' => [
                'page' => ['post-new.php?post_type=page', 'page'],
                'product' => ['post-new.php?post_type=product', 'product'],
            ]
        ]
    ];
    
    if (isset($file_param_checks[$current_file])) {
        foreach ($file_param_checks[$current_file] as $param => $types) {
            $param_value = $_GET[$param] ?? '';
            if ($param_value && isset($types[$param_value])) {
                $test_slugs = $types[$param_value];
                
                $blocked = true;
                foreach ($test_slugs as $test_slug) {
                    if (mpa_user_can_see_menu($test_slug, $roles, $opts)) {
                        $blocked = false;
                        break;
                    }
                }
                
                if ($blocked) {
                    wp_die(
                        '<h1>🚫 Acesso Negado</h1>' .
                        '<p>Você não tem permissão para acessar este tipo de conteúdo.</p>' .
                        '<p>Arquivo: <code>' . esc_html($current_file) . '</code></p>' .
                        '<p>Parâmetro: <code>' . esc_html($param . '=' . $param_value) . '</code></p>' .
                        '<p><a href="' . esc_url(admin_url()) . '">← Voltar ao painel</a></p>',
                        'Acesso Negado',
                        ['response' => 403]
                    );
                }
            }
        }
    }
    
    // Log se chegou ao final sem bloquear nada
    error_log('[MPA NOVO DEBUG] Fim da função - Nenhum bloqueio aplicado');
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

