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
        
        // Processar customizações de menus (nomes e ícones)
        $menu_customizations = array();
        if (isset($_POST['menu_custom_title']) && is_array($_POST['menu_custom_title'])) {
            foreach ($_POST['menu_custom_title'] as $menu_slug => $custom_title) {
                $custom_title = sanitize_text_field($custom_title);
                if (!empty($custom_title)) {
                    $menu_customizations[$menu_slug]['title'] = $custom_title;
                }
            }
        }
        
        if (isset($_POST['menu_custom_icon']) && is_array($_POST['menu_custom_icon'])) {
            foreach ($_POST['menu_custom_icon'] as $menu_slug => $custom_icon) {
                $custom_icon = sanitize_text_field($custom_icon);
                if (!empty($custom_icon)) {
                    $menu_customizations[$menu_slug]['icon'] = $custom_icon;
                }
            }
        }
        
        // Processar customizações de submenus
        if (isset($_POST['submenu_custom_title']) && is_array($_POST['submenu_custom_title'])) {
            foreach ($_POST['submenu_custom_title'] as $submenu_key => $custom_title) {
                $custom_title = sanitize_text_field($custom_title);
                if (!empty($custom_title)) {
                    $menu_customizations['submenu_custom_title'][$submenu_key] = $custom_title;
                    error_log("[MPA SUBMENU DEBUG] Salvando submenu: $submenu_key = $custom_title");
                }
            }
        }
        
        // Salvar customizações
        update_option('mpa_menu_customizations', $menu_customizations);
        
        update_option('mpa_menu_permissions', $current_permissions);
        
        echo '<div class="notice notice-success"><p>Configurações salvas para a role <strong>' . esc_html($selected_role) . '</strong>! <span id="mpa-reload-countdown">Atualizando em 2 segundos...</span></p></div>';
        
        // Adicionar JavaScript para recarregar a página após um delay
        echo '<script>
        let countdown = 2;
        const countdownElement = document.getElementById("mpa-reload-countdown");
        
        const updateCountdown = () => {
            if (countdown > 0) {
                countdownElement.textContent = "Atualizando em " + countdown + " segundo" + (countdown > 1 ? "s" : "") + "...";
                countdown--;
                setTimeout(updateCountdown, 1000);
            } else {
                countdownElement.textContent = "Atualizando...";
                window.location.reload();
            }
        };
        
        setTimeout(updateCountdown, 1000);
        </script>';
    }
    
    // Obter configurações atuais
    $current_permissions = get_option('mpa_menu_permissions', array());
    
    // Obter customizações de menus (nomes e ícones)
    $menu_customizations = get_option('mpa_menu_customizations', array());
    
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
                        <div class="mpa-menu-item" draggable="true" data-menu-slug="<?php echo esc_attr($menu_item['slug']); ?>">
                            <div class="mpa-menu-main">
                                <?php
                                // Verificar se menu está marcado
                                $is_checked = isset($current_permissions[$selected_role][$menu_item['slug']]) 
                                    ? ($current_permissions[$selected_role][$menu_item['slug']] === true)
                                    : true; // Padrão habilitado
                                ?>
                                <span class="mpa-drag-handle dashicons dashicons-menu" title="Arrastar para reordenar"></span>
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
                                    <span class="mpa-menu-title" data-menu-slug="<?php echo esc_attr($menu_item['slug']); ?>">
                                        <?php echo esc_html($menu_item['title']); ?>
                                    </span>
                                    <span class="mpa-edit-icon dashicons dashicons-edit" title="Clique para editar"></span>
                                    <code class="mpa-menu-slug"><?php echo esc_html($menu_item['slug']); ?></code>
                                </label>
                                
                                <!-- Campos de edição de nome e ícone -->
                                <div class="mpa-menu-edit-fields">
                                    <?php 
                                    $current_custom_title = isset($menu_customizations[$menu_item['slug']]['title']) ? $menu_customizations[$menu_item['slug']]['title'] : '';
                                    $current_custom_icon = isset($menu_customizations[$menu_item['slug']]['icon']) ? $menu_customizations[$menu_item['slug']]['icon'] : '';
                                    ?>
                                    <div class="mpa-edit-row">
                                        <label class="mpa-edit-label">Nome personalizado:</label>
                                        <input type="text" 
                                               name="menu_custom_title[<?php echo esc_attr($menu_item['slug']); ?>]" 
                                               value="<?php echo esc_attr($current_custom_title); ?>"
                                               placeholder="<?php echo esc_attr($menu_item['title']); ?>"
                                               class="mpa-custom-title-input" />
                                    </div>
                                    <div class="mpa-edit-row">
                                        <label class="mpa-edit-label">Ícone (dashicon):</label>
                                        <input type="text" 
                                               name="menu_custom_icon[<?php echo esc_attr($menu_item['slug']); ?>]" 
                                               value="<?php echo esc_attr($current_custom_icon); ?>"
                                               placeholder="<?php echo esc_attr($menu_item['icon'] ?: 'dashicons-admin-generic'); ?>"
                                               class="mpa-custom-icon-input" />
                                        <span class="mpa-icon-preview">
                                            <span class="dashicons <?php echo esc_attr($current_custom_icon ?: $menu_item['icon'] ?: 'dashicons-admin-generic'); ?>"></span>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if (!empty($menu_item['submenus'])): ?>
                                <div class="mpa-submenu-header">
                                    <button type="button" class="mpa-submenu-toggle" data-target="submenu-<?php echo esc_attr($menu_item['slug']); ?>">
                                        <span class="dashicons dashicons-plus-alt2"></span>
                                        <span class="mpa-toggle-text">Mostrar submenus (<?php echo count($menu_item['submenus']); ?>)</span>
                                    </button>
                                </div>
                                <div class="mpa-submenu-list collapsed" id="submenu-<?php echo esc_attr($menu_item['slug']); ?>">
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
                                                <span class="mpa-submenu-title" data-submenu-key="<?php echo esc_attr($submenu_key); ?>">
                                                    ↳ <?php echo esc_html($submenu_item['title']); ?>
                                                </span>
                                                <span class="mpa-edit-icon dashicons dashicons-edit" title="Clique para editar"></span>
                                                <code class="mpa-submenu-slug"><?php echo esc_html($submenu_item['slug']); ?></code>
                                            </label>
                                            
                                            <!-- Campos de edição do submenu -->
                                            <div class="mpa-submenu-edit-fields" data-submenu-key="<?php echo esc_attr($submenu_key); ?>">
                                                <div class="mpa-edit-row">
                                                    <label class="mpa-edit-label">Nome personalizado:</label>
                                                    <input type="text" 
                                                           name="submenu_custom_title[<?php echo esc_attr($submenu_key); ?>]"
                                                           value="<?php echo esc_attr($menu_customizations['submenu_custom_title'][$submenu_key] ?? ''); ?>"
                                                           placeholder="<?php echo esc_attr($submenu_item['title']); ?>"
                                                           class="mpa-custom-title-input" />
                                                </div>
                                            </div>
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
        
        <!-- Seção de Export/Import -->
        <div class="mpa-export-import-section">
            <h2>Exportar / Importar Configurações</h2>
            <p class="description">
                Exporte suas configurações de menu para fazer backup ou use em outro site. 
                Importe configurações de outro arquivo JSON.
            </p>
            
            <div class="mpa-export-import-buttons">
                <div class="mpa-export-section">
                    <h3>📤 Exportar Configurações</h3>
                    <p>Baixe um arquivo JSON com todas as configurações atuais de menus por role.</p>
                    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                        <input type="hidden" name="action" value="mpa_export_menu_settings">
                        <?php submit_button('Exportar Configurações JSON', 'primary', 'export', false); ?>
                    </form>
                </div>
                
                <div class="mpa-import-section">
                    <h3>📥 Importar Configurações</h3>
                    <p>Selecione um arquivo JSON de configurações para importar. <strong>Isso substituirá as configurações atuais!</strong></p>
                    <form method="post" enctype="multipart/form-data" action="<?php echo admin_url('admin-post.php'); ?>">
                        <input type="hidden" name="action" value="mpa_import_menu_settings">
                        <input type="file" name="mpa_import_file" accept=".json" required style="margin-bottom: 10px;" />
                        <?php submit_button('Importar Configurações JSON', 'secondary', 'import', false); ?>
                    </form>
                </div>
            </div>
        </div>
        
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
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .mpa-menu-title:hover {
            color: #0073aa;
        }
        
        .mpa-edit-icon {
            color: #666;
            font-size: 16px !important;
            cursor: pointer;
            margin-left: 8px;
            padding: 3px;
            border-radius: 3px;
            transition: all 0.2s;
        }
        
        .mpa-edit-icon:hover {
            color: #0073aa;
            background-color: #f0f6fc;
            transform: scale(1.1);
        }
        
        .mpa-drag-handle {
            color: #999;
            font-size: 16px !important;
            cursor: grab;
            margin-right: 8px;
            padding: 3px;
            border-radius: 3px;
            transition: all 0.2s;
        }
        
        .mpa-drag-handle:hover {
            color: #0073aa;
            background-color: #f0f6fc;
            transform: scale(1.1);
        }
        
        .mpa-drag-handle:active {
            cursor: grabbing;
        }
        
        .mpa-menu-item.dragging {
            opacity: 0.5;
            transform: scale(1.05);
            z-index: 1000;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .mpa-menu-item.drag-over {
            border-top: 3px solid #0073aa;
        }
        
        .mpa-menu-slug {
            font-size: 11px;
            color: #666;
            background: #f1f1f1;
            padding: 2px 6px;
            border-radius: 2px;
        }
        
        /* Header toggle dos submenus */
        .mpa-submenu-header {
            background: #f8f9fa;
            border-top: 1px solid #e1e1e1;
            padding: 0;
        }
        
        .mpa-submenu-toggle {
            width: 100%;
            background: none;
            border: none;
            padding: 10px 15px;
            text-align: left;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #555;
            transition: all 0.2s;
        }
        
        .mpa-submenu-toggle:hover {
            background: #f0f0f0;
            color: #0073aa;
        }
        
        .mpa-submenu-toggle .dashicons {
            font-size: 16px;
            width: 16px;
            height: 16px;
            transition: transform 0.2s;
        }
        
        .mpa-submenu-toggle.expanded .dashicons {
            transform: rotate(45deg);
        }
        
        .mpa-toggle-text {
            font-weight: 500;
        }
        
        /* Submenus */
        .mpa-submenu-list {
            background: #fafafa;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out;
        }
        
        .mpa-submenu-list.expanded {
            max-height: 1000px;
            transition: max-height 0.3s ease-in;
        }
        
        .mpa-submenu-list.collapsed {
            max-height: 0;
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
            font-size: 15px;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .mpa-submenu-title:hover {
            color: #0073aa;
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
        
        /* Seção de Export/Import */
        .mpa-export-import-section {
            margin-top: 40px;
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
        }
        
        .mpa-export-import-section h2 {
            margin-top: 0;
            color: #23282d;
            border-bottom: 2px solid #0073aa;
            padding-bottom: 10px;
        }
        
        .mpa-export-import-buttons {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-top: 20px;
        }
        
        @media (max-width: 768px) {
            .mpa-export-import-buttons {
                grid-template-columns: 1fr;
                gap: 20px;
            }
        }
        
        .mpa-export-section,
        .mpa-import-section {
            padding: 20px;
            border: 1px solid #e1e1e1;
            border-radius: 4px;
            background: #f9f9f9;
        }
        
        .mpa-export-section h3,
        .mpa-import-section h3 {
            margin-top: 0;
            color: #23282d;
            font-size: 16px;
        }
        
        .mpa-export-section p,
        .mpa-import-section p {
            color: #666;
            font-size: 14px;
        }
        
        .mpa-export-section {
            border-left: 4px solid #00a32a;
        }
        
        .mpa-import-section {
            border-left: 4px solid #0073aa;
        }
        
        .mpa-import-section input[type="file"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 3px;
            background: #fff;
        }
        
        /* Campos de edição de menus */
        .mpa-menu-edit-fields {
            margin-top: 10px;
            padding: 15px;
            background: #f8f9fa;
            border: 1px solid #e1e1e1;
            border-radius: 4px;
            display: none; /* Oculto por padrão */
        }
        
        .mpa-menu-edit-fields.active {
            display: block;
        }
        
        .mpa-submenu-edit-fields {
            margin-top: 8px;
            padding: 10px;
            background: #f0f0f1;
            border: 1px solid #ddd;
            border-radius: 3px;
            display: none;
            font-size: 12px;
        }
        
        .mpa-submenu-edit-fields.active {
            display: block;
        }
        
        .mpa-edit-row {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            gap: 10px;
        }
        
        .mpa-edit-row:last-child {
            margin-bottom: 0;
        }
        
        .mpa-edit-label {
            min-width: 140px;
            font-size: 12px;
            font-weight: 500;
            color: #666;
        }
        
        .mpa-custom-title-input,
        .mpa-custom-icon-input {
            flex: 1;
            padding: 6px 10px;
            border: 1px solid #ddd;
            border-radius: 3px;
            font-size: 13px;
        }
        
        .mpa-custom-title-input:focus,
        .mpa-custom-icon-input:focus {
            border-color: #0073aa;
            box-shadow: 0 0 0 1px #0073aa;
            outline: none;
        }
        
        .mpa-icon-preview {
            margin-left: 10px;
            padding: 4px 8px;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 3px;
            min-width: 32px;
            text-align: center;
        }
        
        .mpa-icon-preview .dashicons {
            font-size: 16px;
            width: 16px;
            height: 16px;
            color: #666;
        }
        
        @media (max-width: 768px) {
            .mpa-edit-row {
                flex-direction: column;
                align-items: stretch;
            }
            
            .mpa-edit-label {
                min-width: auto;
            }
            
            .mpa-icon-preview {
                align-self: center;
                margin-left: 0;
                margin-top: 5px;
            }
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
            
            // Atualizar preview de ícones em tempo real
            document.querySelectorAll('.mpa-custom-icon-input').forEach(input => {
                input.addEventListener('input', function() {
                    const preview = this.parentElement.querySelector('.mpa-icon-preview .dashicons');
                    const iconClass = this.value.trim();
                    
                    if (iconClass && iconClass.startsWith('dashicons-')) {
                        preview.className = 'dashicons ' + iconClass;
                    } else if (iconClass) {
                        preview.className = 'dashicons dashicons-' + iconClass;
                    } else {
                        // Usar ícone padrão se vazio
                        const placeholder = this.getAttribute('placeholder');
                        preview.className = 'dashicons ' + (placeholder || 'dashicons-admin-generic');
                    }
                });
            });
            
            // Click-to-edit functionality para ícones de lápis
            document.querySelectorAll('.mpa-edit-icon').forEach(editIcon => {
                editIcon.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const menuItem = this.closest('.mpa-menu-item, .mpa-submenu-item');
                    const editFields = menuItem.querySelector('.mpa-menu-edit-fields, .mpa-submenu-edit-fields');
                    
                    if (editFields) {
                        editFields.classList.toggle('active');
                        
                        // Focar no primeiro input quando abrir
                        if (editFields.classList.contains('active')) {
                            const firstInput = editFields.querySelector('input[type="text"]');
                            if (firstInput) {
                                setTimeout(() => firstInput.focus(), 100);
                            }
                        }
                    }
                });
            });
            
            // Atualização em tempo real dos títulos ao digitar
            document.querySelectorAll('.mpa-custom-title-input').forEach(input => {
                input.addEventListener('input', function() {
                    const menuItem = this.closest('.mpa-menu-item, .mpa-submenu-item');
                    const titleElement = menuItem.querySelector('.mpa-menu-title, .mpa-submenu-title');
                    
                    if (titleElement) {
                        const originalTitle = titleElement.getAttribute('data-original-title') || titleElement.textContent.trim();
                        
                        // Salvar título original na primeira vez
                        if (!titleElement.getAttribute('data-original-title')) {
                            titleElement.setAttribute('data-original-title', originalTitle);
                        }
                        
                        // Verificar se é um submenu (tem ↳)
                        const isSubmenu = titleElement.classList.contains('mpa-submenu-title');
                        const prefix = isSubmenu ? '↳ ' : '';
                        
                        // Atualizar título com valor digitado ou voltar ao original se vazio
                        const newTitle = this.value.trim();
                        if (newTitle) {
                            titleElement.textContent = prefix + newTitle;
                        } else {
                            titleElement.textContent = originalTitle;
                        }
                    }
                });
            });
            
            // Implementação do Drag & Drop para reordenação de menus
            let draggedItem = null;
            
            // Eventos de drag para os menus
            document.querySelectorAll('.mpa-menu-item').forEach(item => {
                item.addEventListener('dragstart', function(e) {
                    draggedItem = this;
                    this.classList.add('dragging');
                    e.dataTransfer.effectAllowed = 'move';
                    e.dataTransfer.setData('text/html', this.outerHTML);
                });
                
                item.addEventListener('dragend', function(e) {
                    this.classList.remove('dragging');
                    document.querySelectorAll('.mpa-menu-item').forEach(item => {
                        item.classList.remove('drag-over');
                    });
                });
                
                item.addEventListener('dragover', function(e) {
                    e.preventDefault();
                    e.dataTransfer.dropEffect = 'move';
                    
                    if (this !== draggedItem) {
                        this.classList.add('drag-over');
                    }
                });
                
                item.addEventListener('dragenter', function(e) {
                    e.preventDefault();
                });
                
                item.addEventListener('dragleave', function(e) {
                    this.classList.remove('drag-over');
                });
                
                item.addEventListener('drop', function(e) {
                    e.preventDefault();
                    this.classList.remove('drag-over');
                    
                    if (this !== draggedItem) {
                        const rect = this.getBoundingClientRect();
                        const mouseY = e.clientY;
                        const itemMiddle = rect.top + rect.height / 2;
                        
                        if (mouseY < itemMiddle) {
                            // Inserir antes
                            this.parentNode.insertBefore(draggedItem, this);
                        } else {
                            // Inserir depois
                            this.parentNode.insertBefore(draggedItem, this.nextSibling);
                        }
                        
                        // Salvar nova ordem
                        saveMenuOrder();
                    }
                });
            });
            
            // Função para salvar a nova ordem dos menus
            function saveMenuOrder() {
                const menuItems = document.querySelectorAll('.mpa-menu-item[data-menu-slug]');
                const order = Array.from(menuItems).map(item => item.getAttribute('data-menu-slug'));
                
                // Enviar via AJAX para salvar no backend
                const formData = new FormData();
                formData.append('action', 'mpa_save_menu_order');
                formData.append('menu_order', JSON.stringify(order));
                formData.append('nonce', '<?php echo wp_create_nonce("mpa_menu_order"); ?>');
                
                fetch('<?php echo admin_url("admin-ajax.php"); ?>', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        console.log('Ordem dos menus salva com sucesso!');
                        // Opcional: mostrar notificação de sucesso
                        showNotification('Ordem dos menus atualizada!', 'success');
                    } else {
                        console.error('Erro ao salvar ordem:', data.data);
                        showNotification('Erro ao salvar ordem dos menus.', 'error');
                    }
                })
                .catch(error => {
                    console.error('Erro na requisição:', error);
                    showNotification('Erro ao salvar ordem dos menus.', 'error');
                });
            }
            
            // Função para mostrar notificações
            function showNotification(message, type = 'info') {
                const notification = document.createElement('div');
                notification.className = `notice notice-${type} is-dismissible`;
                notification.innerHTML = `<p>${message}</p>`;
                notification.style.position = 'fixed';
                notification.style.top = '32px';
                notification.style.right = '20px';
                notification.style.zIndex = '9999';
                notification.style.maxWidth = '300px';
                
                document.body.appendChild(notification);
                
                // Remover após 3 segundos
                setTimeout(() => {
                    notification.remove();
                }, 3000);
            }
            
            // Toggle de submenus - expandir/recolher
            document.querySelectorAll('.mpa-submenu-toggle').forEach(button => {
                button.addEventListener('click', function() {
                    const targetId = this.getAttribute('data-target');
                    const target = document.getElementById(targetId);
                    const icon = this.querySelector('.dashicons');
                    const text = this.querySelector('.mpa-toggle-text');
                    
                    if (!target || !icon || !text) return;
                    
                    if (target.classList.contains('collapsed')) {
                        // Expandir
                        target.classList.remove('collapsed');
                        target.classList.add('expanded');
                        icon.classList.remove('dashicons-plus-alt2');
                        icon.classList.add('dashicons-minus-alt2');
                        text.textContent = text.textContent.replace('Mostrar', 'Ocultar');
                        this.classList.add('expanded');
                    } else {
                        // Recolher
                        target.classList.remove('expanded');
                        target.classList.add('collapsed');
                        icon.classList.remove('dashicons-minus-alt2');
                        icon.classList.add('dashicons-plus-alt2');
                        text.textContent = text.textContent.replace('Ocultar', 'Mostrar');
                        this.classList.remove('expanded');
                    }
                });
            });
            
            // Fechar edição quando clicar fora
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.mpa-menu-item') && !e.target.closest('.mpa-submenu-item')) {
                    document.querySelectorAll('.mpa-menu-edit-fields.active, .mpa-submenu-edit-fields.active').forEach(field => {
                        field.classList.remove('active');
                    });
                }
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

/////////////////////
// EXPORTAÇÃO E IMPORTAÇÃO
/////////////////////

// Adicionar actions para export/import
add_action('admin_post_mpa_export_menu_settings', 'mpa_export_menu_settings_callback');
add_action('admin_post_mpa_import_menu_settings', 'mpa_import_menu_settings_callback');

function mpa_export_menu_settings_callback() {
    if (!current_user_can('manage_options')) {
        wp_die('Sem permissões suficientes.');
    }

    $options = get_option('mpa_menu_permissions', array());
    $json = json_encode($options, JSON_PRETTY_PRINT);

    $filename = 'mpa_menu_permissions_' . date('Y-m-d') . '.json';

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
        $file_content = file_get_contents($_FILES['mpa_import_file']['tmp_name']);
        $data = json_decode($file_content, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
            update_option('mpa_menu_permissions', $data);
            $redirect = add_query_arg('mpa_status', 'success', admin_url('admin.php?page=mpa-menu-roles'));
        } else {
            $redirect = add_query_arg('mpa_status', 'invalid_json', admin_url('admin.php?page=mpa-menu-roles'));
        }
    } else {
        $redirect = add_query_arg('mpa_status', 'no_file', admin_url('admin.php?page=mpa-menu-roles'));
    }

    wp_redirect($redirect);
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
    
    $ordered_menu = array();
    $used_positions = array();
    
    // Criar um mapa dos menus existentes por slug
    $menu_by_slug = array();
    foreach ($menu as $position => $menu_item) {
        if (!empty($menu_item[2])) {
            $slug = $menu_item[2];
            $menu_by_slug[$slug] = array(
                'position' => $position,
                'item' => $menu_item
            );
        }
    }
    
    // Reordenar conforme a ordem customizada
    $new_position = 5; // Começar em posição 5
    foreach ($custom_order as $slug) {
        if (isset($menu_by_slug[$slug])) {
            // Encontrar próxima posição livre
            while (isset($menu[$new_position])) {
                $new_position++;
            }
            
            $ordered_menu[$new_position] = $menu_by_slug[$slug]['item'];
            $used_positions[] = $menu_by_slug[$slug]['position'];
            $new_position++;
        }
    }
    
    // Adicionar menus que não estão na ordem customizada
    foreach ($menu as $position => $menu_item) {
        if (!in_array($position, $used_positions)) {
            // Encontrar próxima posição livre
            while (isset($ordered_menu[$new_position])) {
                $new_position++;
            }
            $ordered_menu[$new_position] = $menu_item;
            $new_position++;
        }
    }
    
    // Aplicar nova ordem
    if (!empty($ordered_menu)) {
        // Manter separadores e itens especiais
        foreach ($menu as $position => $menu_item) {
            if (empty($menu_item[2]) || strpos($menu_item[4], 'wp-menu-separator') !== false) {
                $ordered_menu[$position] = $menu_item;
            }
        }
        
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
        
        // Aplicar customizações se encontradas
        if ($custom_data) {
            if (!empty($custom_data['title'])) {
                $menu[$key][0] = $custom_data['title'];
                
                if (isset($_GET['debug_menus'])) {
                    error_log("CUSTOMIZAÇÃO APLICADA: '{$menu_title}' -> '{$custom_data['title']}'");
                }
            }
            
            if (!empty($custom_data['icon'])) {
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
                    
                    if (!empty($custom_title)) {
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

