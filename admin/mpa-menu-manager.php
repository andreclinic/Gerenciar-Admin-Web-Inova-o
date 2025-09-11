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
        $admin_menus = mpa_get_admin_menus($selected_role);
        
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
        $menu_customizations = get_option('mpa_menu_customizations', array());
        if (isset($_POST['menu_custom_title']) && is_array($_POST['menu_custom_title'])) {
            foreach ($_POST['menu_custom_title'] as $menu_slug => $custom_title) {
                $custom_title = sanitize_text_field($custom_title);
                if (!empty(trim($custom_title))) {
                    $menu_customizations[$menu_slug]['title'] = $custom_title;
                } else {
                    // Se estiver vazio, remover a customização para voltar ao original
                    if (isset($menu_customizations[$menu_slug]['title'])) {
                        unset($menu_customizations[$menu_slug]['title']);
                    }
                    // Se não há mais customizações para este menu, remover completamente
                    if (empty($menu_customizations[$menu_slug])) {
                        unset($menu_customizations[$menu_slug]);
                    }
                }
            }
        }
        
        if (isset($_POST['menu_custom_icon']) && is_array($_POST['menu_custom_icon'])) {
            foreach ($_POST['menu_custom_icon'] as $menu_slug => $custom_icon) {
                $custom_icon = sanitize_text_field($custom_icon);
                if (!empty(trim($custom_icon))) {
                    $menu_customizations[$menu_slug]['icon'] = $custom_icon;
                } else {
                    // Se estiver vazio, remover a customização para voltar ao original
                    if (isset($menu_customizations[$menu_slug]['icon'])) {
                        unset($menu_customizations[$menu_slug]['icon']);
                    }
                    // Se não há mais customizações para este menu, remover completamente
                    if (empty($menu_customizations[$menu_slug])) {
                        unset($menu_customizations[$menu_slug]);
                    }
                }
            }
        }
        
        // Processar customizações de submenus
        if (isset($_POST['submenu_custom_title']) && is_array($_POST['submenu_custom_title'])) {
            foreach ($_POST['submenu_custom_title'] as $submenu_key => $custom_title) {
                $custom_title = sanitize_text_field($custom_title);
                if (!empty(trim($custom_title))) {
                    $menu_customizations['submenu_custom_title'][$submenu_key] = $custom_title;
                    error_log("[MPA SUBMENU DEBUG] Salvando submenu: $submenu_key = $custom_title");
                } else {
                    // Se estiver vazio, remover a customização para voltar ao original
                    if (isset($menu_customizations['submenu_custom_title'][$submenu_key])) {
                        unset($menu_customizations['submenu_custom_title'][$submenu_key]);
                        error_log("[MPA SUBMENU DEBUG] Removendo customização: $submenu_key");
                    }
                    // Se não há mais customizações de submenu, limpar o array
                    if (empty($menu_customizations['submenu_custom_title'])) {
                        unset($menu_customizations['submenu_custom_title']);
                    }
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
    $admin_menus = mpa_get_admin_menus($selected_role);
    
    ?>
    <div class="wrap">
        <h1>Gerenciar Menus por Role</h1>
        <p>Selecione uma role e configure quais menus ela pode acessar. Desmarque os menus que você deseja remover.</p>
        
        <!-- Seletor de Role -->
        <div class="mpa-role-selector">
                <div class="mpa-selector-group">
                    <label for="role-select"><h2>Selecione a Role:</h2></label>
                    <select name="role" id="role-select" onchange="changeRole(this.value)">
                        <?php foreach ($all_roles as $role_key => $role_name): ?>
                            <?php
                            // Calcular contador de menus bloqueados
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
                            $display_text = $role_name;
                            if ($blocked_count > 0) {
                                $display_text .= " ({$blocked_count} bloqueados)";
                            }
                            ?>
                            <option value="<?php echo esc_attr($role_key); ?>" <?php selected($selected_role, $role_key); ?>>
                                <?php echo esc_html($display_text); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mpa-view-options">
                    <button type="button" 
                            onclick="toggleView('menu')" 
                            class="mpa-view-btn <?php echo (!isset($_GET['view']) || ($_GET['view'] !== 'icons' && $_GET['view'] !== 'custom_menus')) ? 'active' : ''; ?>" 
                            id="menu-view-btn">
                        📋 Gerenciar Menus
                    </button>
                    <button type="button" 
                            onclick="toggleView('custom_menus')" 
                            class="mpa-view-btn <?php echo (isset($_GET['view']) && $_GET['view'] === 'custom_menus') ? 'active' : ''; ?>" 
                            id="custom-menus-view-btn">
                        🔗 Menus Personalizados
                    </button>
                    <button type="button" 
                            onclick="toggleView('icons')" 
                            class="mpa-view-btn <?php echo (isset($_GET['view']) && $_GET['view'] === 'icons') ? 'active' : ''; ?>" 
                            id="icons-view-btn">
                        🎨 Guia de Ícones
                    </button>
                </div>
            </div>
        </div>
        
        <?php if (isset($_GET['view']) && $_GET['view'] === 'icons'): ?>
        <div class="mpa-icons-section">
            <h2>📚 Guia de Ícones do WordPress</h2>
            <p class="description">
                Clique no ícone desejado para copiar seu nome. Use esses nomes nos campos de ícone personalizado dos menus.
            </p>
            
            <div class="mpa-icons-search">
                <input type="text" id="mpa-icon-search" placeholder="🔍 Pesquisar ícones..." />
                <span class="mpa-search-clear" onclick="clearSearch()">✕</span>
            </div>
            
            <div class="mpa-icons-grid" id="mpa-icons-container">
                <?php
                // Lista completa de ícones do WordPress
                $wordpress_icons = array(
                    'admin-appearance', 'admin-collapse', 'admin-comments', 'admin-customizer', 'admin-generic',
                    'admin-home', 'admin-media', 'admin-multisite', 'admin-network', 'admin-page',
                    'admin-plugins', 'admin-post', 'admin-settings', 'admin-site', 'admin-site-alt',
                    'admin-site-alt2', 'admin-site-alt3', 'admin-tools', 'admin-users',
                    'airplane', 'album', 'align-center', 'align-full-width', 'align-left', 'align-none', 
                    'align-pull-left', 'align-pull-right', 'align-right', 'align-wide', 'analytics',
                    'archive', 'arrow-down', 'arrow-down-alt', 'arrow-down-alt2', 'arrow-left',
                    'arrow-left-alt', 'arrow-left-alt2', 'arrow-right', 'arrow-right-alt',
                    'arrow-right-alt2', 'arrow-up', 'arrow-up-alt', 'arrow-up-alt2', 'arrow-up-duplicate',
                    'art', 'awards', 'backup', 'bank', 'beer', 'bell', 'block-default', 'book',
                    'book-alt', 'buddicons-activity', 'buddicons-bbpress-logo', 'buddicons-buddypress-logo',
                    'buddicons-community', 'buddicons-forums', 'buddicons-friends', 'buddicons-groups',
                    'buddicons-pm', 'buddicons-replies', 'buddicons-topics', 'buddicons-tracking',
                    'building', 'businessman', 'button', 'calendar', 'calendar-alt', 'camera',
                    'camera-alt', 'carrot', 'cart', 'category', 'chart-area', 'chart-bar', 'chart-line',
                    'chart-pie', 'clipboard', 'clock', 'cloud', 'cloud-saved', 'cloud-upload', 'code-standards',
                    'coffee', 'color-picker', 'columns', 'comment', 'controls-back', 'controls-forward',
                    'controls-pause', 'controls-play', 'controls-repeat', 'controls-skipback',
                    'controls-skipforward', 'controls-volumeoff', 'controls-volumeon', 'cover-image',
                    'dashboard', 'database', 'database-add', 'database-export', 'database-import',
                    'database-remove', 'database-view', 'desktop', 'dismiss', 'download', 'edit',
                    'editor-aligncenter', 'editor-alignleft', 'editor-alignright', 'editor-bold',
                    'editor-break', 'editor-code', 'editor-contract', 'editor-customchar', 'editor-expand',
                    'editor-help', 'editor-indent', 'editor-insertmore', 'editor-italic', 'editor-justify',
                    'editor-kitchensink', 'editor-ltr', 'editor-ol', 'editor-ol-rtl', 'editor-outdent',
                    'editor-paragraph', 'editor-paste-text', 'editor-paste-word', 'editor-quote',
                    'editor-removeformatting', 'editor-rtl', 'editor-spellcheck', 'editor-strikethrough',
                    'editor-table', 'editor-textcolor', 'editor-ul', 'editor-ul-rtl', 'editor-underline',
                    'editor-unlink', 'editor-video', 'email', 'email-alt', 'email-alt2', 'embed-audio',
                    'embed-generic', 'embed-photo', 'embed-post', 'embed-video', 'excerpt-view', 'exit',
                    'external', 'facebook', 'facebook-alt', 'feedback', 'filter', 'flag', 'format-aside',
                    'format-audio', 'format-chat', 'format-gallery', 'format-image', 'format-quote',
                    'format-status', 'format-video', 'forms', 'fullscreen-alt', 'fullscreen-exit-alt',
                    'games', 'google', 'googleplus', 'grid-view', 'groups', 'hammer', 'heading', 'heart',
                    'hidden', 'hourglass', 'html', 'id', 'id-alt', 'image-crop', 'image-filter',
                    'image-flip-horizontal', 'image-flip-vertical', 'image-rotate', 'image-rotate-left',
                    'image-rotate-right', 'images-alt', 'images-alt2', 'index-card', 'info', 'info-outline',
                    'insert', 'instagram', 'laptop', 'layout', 'leftright', 'lightbulb', 'linkedin',
                    'list-view', 'location', 'location-alt', 'lock', 'marker', 'media-archive', 'media-audio',
                    'media-code', 'media-default', 'media-document', 'media-interactive', 'media-spreadsheet',
                    'media-text', 'media-video', 'megaphone', 'menu', 'menu-alt', 'menu-alt2', 'menu-alt3',
                    'microphone', 'migrate', 'minus', 'money', 'money-alt', 'move', 'nametag', 'networking',
                    'no', 'no-alt', 'open-folder', 'palmtree', 'paperclip', 'performance', 'pets',
                    'phone', 'pinterest', 'playlist-audio', 'playlist-video', 'plus', 'plus-alt', 'plus-alt2',
                    'portfolio', 'post-status', 'pressthis', 'printer', 'privacy', 'products', 'redo',
                    'reddit', 'rest-api', 'rss', 'saved', 'schedule', 'screenoptions', 'search', 'share',
                    'share-alt', 'share-alt2', 'shield', 'shield-alt', 'slides', 'smartphone', 'smiley',
                    'sort', 'sos', 'star-empty', 'star-filled', 'star-half', 'sticky', 'store',
                    'superhero', 'superhero-alt', 'tablet', 'tag', 'tagcloud', 'testimonial', 'text',
                    'text-page', 'thumbs-down', 'thumbs-up', 'tickets', 'tickets-alt', 'tide', 'translation',
                    'trash', 'twitter', 'twitter-alt', 'undo', 'unlock', 'update', 'update-alt', 'upload',
                    'vault', 'video-alt', 'video-alt2', 'video-alt3', 'visibility', 'warning', 'welcome-add-page',
                    'welcome-comments', 'welcome-learn-more', 'welcome-view-site', 'welcome-widgets-menus',
                    'welcome-write-blog', 'wordpress', 'wordpress-alt', 'yes', 'yes-alt', 'youtube'
                );
                
                foreach ($wordpress_icons as $icon): ?>
                    <div class="mpa-icon-item" data-icon="dashicons-<?php echo esc_attr($icon); ?>" onclick="copyIcon('dashicons-<?php echo esc_attr($icon); ?>')">
                        <span class="dashicons dashicons-<?php echo esc_attr($icon); ?>"></span>
                        <span class="mpa-icon-name">dashicons-<?php echo esc_attr($icon); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="mpa-copy-notification" id="mpa-copy-notification">
                <strong>✅ Copiado!</strong> O ícone foi copiado para a área de transferência.
            </div>
        </div>
        <?php elseif (!empty($selected_role) && isset($_GET['view']) && $_GET['view'] === 'custom_menus'): ?>
        <!-- Seção de Menus Personalizados -->
        <div class="mpa-custom-menus-section">
            <h2>🔗 Menus Personalizados para: <span class="mpa-role-name"><?php echo esc_html($all_roles[$selected_role]); ?></span></h2>
            <p class="description">
                Crie menus personalizados com título, ícone e link de destino. Esses menus aparecerão no painel administrativo para usuários com a função selecionada.
            </p>

            <?php
            // Obter menus personalizados existentes para a role selecionada
            $custom_menus = get_option('mpa_custom_menus', array());
            $role_custom_menus = isset($custom_menus[$selected_role]) ? $custom_menus[$selected_role] : array();
            ?>

            <!-- Formulário para adicionar novo menu personalizado -->
            <div class="mpa-add-custom-menu">
                <h3>➕ Adicionar Novo Menu Personalizado</h3>
                <div class="mpa-custom-menu-form">
                    <div class="mpa-custom-form-row">
                        <label for="custom-menu-title">Título do Menu:</label>
                        <input type="text" id="custom-menu-title" placeholder="Digite o título do menu..." />
                    </div>
                    <div class="mpa-custom-form-row">
                        <label for="custom-menu-icon">Ícone (dashicon):</label>
                        <div class="mpa-icon-input-wrapper">
                            <input type="text" id="custom-menu-icon" placeholder="dashicons-admin-generic" />
                            <span class="mpa-custom-icon-preview">
                                <span class="dashicons dashicons-admin-generic"></span>
                            </span>
                        </div>
                    </div>
                    <div class="mpa-custom-form-row">
                        <label for="custom-menu-url">URL de Destino:</label>
                        <input type="url" id="custom-menu-url" placeholder="https://exemplo.com ou admin.php?page=minha-pagina" />
                    </div>
                    <div class="mpa-custom-form-actions">
                        <button type="button" id="add-custom-menu-btn" class="button button-primary">
                            🔗 Adicionar Menu Personalizado
                        </button>
                    </div>
                </div>
            </div>

            <!-- Lista de menus personalizados existentes -->
            <div class="mpa-existing-custom-menus">
                <h3>📋 Menus Personalizados Existentes</h3>
                <div id="custom-menus-list">
                    <?php if (!empty($role_custom_menus)): ?>
                        <?php foreach ($role_custom_menus as $menu_id => $menu_data): ?>
                        <div class="mpa-custom-menu-item" data-menu-id="<?php echo esc_attr($menu_id); ?>">
                            <div class="mpa-custom-menu-info">
                                <span class="mpa-custom-menu-icon">
                                    <span class="dashicons <?php echo esc_attr($menu_data['icon'] ?? 'dashicons-admin-generic'); ?>"></span>
                                </span>
                                <span class="mpa-custom-menu-title"><?php echo esc_html($menu_data['title'] ?? ''); ?></span>
                                <span class="mpa-custom-menu-url"><?php echo esc_html($menu_data['url'] ?? ''); ?></span>
                            </div>
                            <div class="mpa-custom-menu-actions">
                                <button type="button" class="button edit-custom-menu" title="Editar">
                                    <span class="dashicons dashicons-edit"></span>
                                </button>
                                <button type="button" class="button delete-custom-menu" title="Excluir">
                                    <span class="dashicons dashicons-trash"></span>
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="mpa-no-custom-menus">
                            <p>Nenhum menu personalizado criado ainda. Use o formulário acima para criar o primeiro!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php elseif (!empty($selected_role)): ?>
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
                                        <?php if (isset($menu_item['custom']) && $menu_item['custom']): ?>
                                            <span class="mpa-custom-menu-badge">📎 Link Personalizado</span>
                                        <?php endif; ?>
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
                                        <div class="mpa-edit-input-group">
                                            <input type="text" 
                                                   name="menu_custom_title[<?php echo esc_attr($menu_item['slug']); ?>]" 
                                                   value="<?php echo esc_attr($current_custom_title); ?>"
                                                   placeholder="<?php echo esc_attr($menu_item['title']); ?>"
                                                   class="mpa-custom-title-input" />
                                        </div>
                                    </div>
                                    <div class="mpa-edit-row">
                                        <label class="mpa-edit-label">Ícone (dashicon):</label>
                                        <div class="mpa-edit-input-group">
                                            <input type="text" 
                                                   name="menu_custom_icon[<?php echo esc_attr($menu_item['slug']); ?>]" 
                                                   value="<?php echo esc_attr($current_custom_icon); ?>"
                                                   placeholder="<?php echo esc_attr($menu_item['icon'] ?: 'dashicons-admin-generic'); ?>"
                                                   class="mpa-custom-icon-input" />
                                        </div>
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
                                                    <div class="mpa-edit-input-group">
                                                        <input type="text" 
                                                               name="submenu_custom_title[<?php echo esc_attr($submenu_key); ?>]"
                                                               value="<?php echo esc_attr($menu_customizations['submenu_custom_title'][$submenu_key] ?? ''); ?>"
                                                               placeholder="<?php echo esc_attr($submenu_item['title']); ?>"
                                                               class="mpa-custom-title-input" />
                                                    </div>
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
            
            <!-- Seção de Reset -->
            <div class="mpa-reset-section">
                <h3>🔄 Resetar Configurações</h3>
                <p>Restaura todas as configurações de menu ao estado inicial padrão do WordPress. <strong>Esta ação não pode ser desfeita!</strong></p>
                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" onsubmit="return confirmReset();">
                    <input type="hidden" name="action" value="mpa_reset_menu_settings">
                    <?php wp_nonce_field('mpa_reset_settings', 'mpa_reset_nonce'); ?>
                    <?php submit_button('Resetar Todas as Configurações', 'delete', 'reset', false); ?>
                </form>
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
        
        .mpa-role-selector {
            background: #fff;
            border: 1px solid #ccd0d4;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 4px;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
        }
        
        .mpa-selector-group {
            margin-bottom: 20px;
        }
        
        .mpa-selector-group h2 {
            margin: 0 0 10px 0;
            font-size: 16px;
            color: #23282d;
        }
        
        #role-select {
            min-width: 300px;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: #fff;
            font-size: 14px;
            box-shadow: inset 0 1px 2px rgba(0,0,0,.07);
        }
        
        #role-select:focus {
            border-color: #0073aa;
            outline: none;
            box-shadow: 0 0 0 2px rgba(0, 115, 170, 0.2);
        }
        
        .mpa-view-options {
            display: flex;
            gap: 10px;
        }
        
        .mpa-view-btn {
            padding: 10px 20px;
            background: #f1f1f1;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            color: #555;
            text-decoration: none;
            transition: all 0.2s;
        }
        
        .mpa-view-btn:hover {
            background: #e0e0e0;
            color: #333;
        }
        
        .mpa-view-btn.active {
            background: #0073aa;
            color: white;
            border-color: #005a87;
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
        
        .mpa-custom-menu-badge {
            background: #e8f4fd;
            color: #2271b1;
            font-size: 11px;
            font-weight: 500;
            padding: 2px 6px;
            border-radius: 12px;
            border: 1px solid #c3dbf0;
            margin-left: 8px;
            white-space: nowrap;
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
        
        /* Seção de Reset */
        .mpa-reset-section {
            margin-top: 20px;
            padding: 20px;
            border: 1px solid #e1e1e1;
            border-left: 4px solid #dc3232;
            border-radius: 4px;
            background: #fdf2f2;
        }
        
        .mpa-reset-section h3 {
            margin-top: 0;
            color: #dc3232;
            font-size: 16px;
        }
        
        .mpa-reset-section p {
            color: #666;
            font-size: 14px;
        }
        
        /* Melhorias nos botões principais */
        .mpa-submit-section .button-primary,
        .mpa-export-section .button-primary,
        .mpa-import-section .button-secondary,
        .mpa-reset-section .button.delete {
            font-size: 16px !important;
            font-weight: 600 !important;
            padding: 12px 24px !important;
            height: auto !important;
            line-height: 1.4 !important;
            border-radius: 6px !important;
            text-shadow: none !important;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1) !important;
            transition: all 0.3s ease !important;
            min-width: 180px !important;
            text-align: center !important;
        }
        
        /* Botão de salvar configurações */
        .mpa-submit-section .button-primary {
            background: linear-gradient(135deg, #00a32a 0%, #008a20 100%) !important;
            border-color: #008a20 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.5px !important;
        }
        
        .mpa-submit-section .button-primary:hover {
            background: linear-gradient(135deg, #008a20 0%, #007017 100%) !important;
            border-color: #007017 !important;
            transform: translateY(-1px) !important;
            box-shadow: 0 4px 8px rgba(0,0,0,0.15) !important;
        }
        
        /* Botão de exportar */
        .mpa-export-section .button-primary {
            background: linear-gradient(135deg, #0073aa 0%, #005a87 100%) !important;
            border-color: #005a87 !important;
        }
        
        .mpa-export-section .button-primary:hover {
            background: linear-gradient(135deg, #005a87 0%, #004766 100%) !important;
            border-color: #004766 !important;
            transform: translateY(-1px) !important;
            box-shadow: 0 4px 8px rgba(0,0,0,0.15) !important;
        }
        
        /* Botão de importar */
        .mpa-import-section .button-secondary {
            background: linear-gradient(135deg, #2271b1 0%, #135e96 100%) !important;
            color: #fff !important;
            border-color: #135e96 !important;
        }
        
        .mpa-import-section .button-secondary:hover {
            background: linear-gradient(135deg, #135e96 0%, #0f4c7a 100%) !important;
            color: #fff !important;
            border-color: #0f4c7a !important;
            transform: translateY(-1px) !important;
            box-shadow: 0 4px 8px rgba(0,0,0,0.15) !important;
        }
        
        /* Botão de reset */
        .mpa-reset-section .button.delete {
            background: linear-gradient(135deg, #dc3232 0%, #b32d2e 100%) !important;
            border-color: #b32d2e !important;
            color: #fff !important;
            text-transform: uppercase !important;
            letter-spacing: 0.5px !important;
        }
        
        .mpa-reset-section .button.delete:hover {
            background: linear-gradient(135deg, #b32d2e 0%, #8f2728 100%) !important;
            border-color: #8f2728 !important;
            color: #fff !important;
            transform: translateY(-1px) !important;
            box-shadow: 0 4px 8px rgba(220,50,50,0.3) !important;
        }
        
        /* Ícones nos botões */
        .mpa-export-section .button-primary:before {
            content: "📤 ";
            font-size: 18px;
            margin-right: 8px;
        }
        
        .mpa-import-section .button-secondary:before {
            content: "📥 ";
            font-size: 18px;
            margin-right: 8px;
        }
        
        .mpa-reset-section .button.delete:before {
            content: "🔄 ";
            font-size: 18px;
            margin-right: 8px;
        }
        
        .mpa-submit-section .button-primary:before {
            content: "💾 ";
            font-size: 18px;
            margin-right: 8px;
        }
        
        /* Responsividade para botões */
        @media (max-width: 768px) {
            .mpa-submit-section .button-primary,
            .mpa-export-section .button-primary,
            .mpa-import-section .button-secondary,
            .mpa-reset-section .button.delete {
                font-size: 14px !important;
                padding: 10px 20px !important;
                min-width: 150px !important;
            }
        }
        
        /* Indicadores de Auto-Save */
        .mpa-save-indicator {
            position: absolute;
            right: -120px;
            top: 50%;
            transform: translateY(-50%);
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            white-space: nowrap;
            z-index: 1000;
            animation: fadeIn 0.3s ease;
        }
        
        .mpa-save-saving {
            background: #0073aa;
            color: #fff;
        }
        
        .mpa-save-success {
            background: #00a32a;
            color: #fff;
        }
        
        .mpa-save-error {
            background: #dc3232;
            color: #fff;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-50%) translateX(10px); }
            to { opacity: 1; transform: translateY(-50%) translateX(0); }
        }
        
        /* Posicionamento relativo para os containers dos campos de edição */
        .mpa-edit-input-group {
            position: relative;
        }
        
        /* Responsividade para indicadores */
        @media (max-width: 768px) {
            .mpa-save-indicator {
                right: -90px;
                font-size: 10px;
                padding: 2px 6px;
            }
        }
        
        /* Seção de Ícones */
        .mpa-icons-section {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 30px;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
            margin-bottom: 20px;
        }
        
        .mpa-icons-section h2 {
            margin-top: 0;
            color: #23282d;
            border-bottom: 3px solid #0073aa;
            padding-bottom: 15px;
        }
        
        .mpa-icons-search {
            position: relative;
            margin: 20px 0;
            max-width: 400px;
        }
        
        .mpa-icons-search input {
            width: 100%;
            padding: 12px 40px 12px 15px;
            font-size: 16px;
            border: 2px solid #ddd;
            border-radius: 25px;
            transition: all 0.3s ease;
        }
        
        .mpa-icons-search input:focus {
            outline: none;
            border-color: #0073aa;
            box-shadow: 0 0 0 3px rgba(0,115,170,0.1);
        }
        
        .mpa-search-clear {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #999;
            font-size: 18px;
            display: none;
        }
        
        .mpa-search-clear:hover {
            color: #dc3232;
        }
        
        .mpa-icons-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 15px;
            margin: 30px 0;
        }
        
        .mpa-icon-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px 15px;
            border: 2px solid #e1e1e1;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #fff;
        }
        
        .mpa-icon-item:hover {
            border-color: #0073aa;
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0,115,170,0.15);
            background: #f8fbff;
        }
        
        .mpa-icon-item:active {
            transform: translateY(-1px);
            box-shadow: 0 2px 6px rgba(0,115,170,0.2);
        }
        
        .mpa-icon-item .dashicons {
            font-size: 32px !important;
            color: #0073aa;
            margin-bottom: 10px;
        }
        
        .mpa-icon-name {
            font-size: 12px;
            color: #666;
            text-align: center;
            word-break: break-all;
            font-family: 'Monaco', 'Consolas', monospace;
            background: #f1f1f1;
            padding: 4px 8px;
            border-radius: 4px;
        }
        
        .mpa-copy-notification {
            position: fixed;
            top: 50px;
            right: 20px;
            background: #00a32a;
            color: #fff;
            padding: 15px 25px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            transform: translateX(100%);
            transition: transform 0.3s ease;
            z-index: 10000;
        }
        
        .mpa-copy-notification.show {
            transform: translateX(0);
        }
        
        .mpa-icons-tab {
            background: linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%) !important;
            border-color: #8e44ad !important;
            color: white !important;
        }
        
        .mpa-icons-tab:hover {
            background: linear-gradient(135deg, #8e44ad 0%, #732d91 100%) !important;
            border-color: #732d91 !important;
            color: white !important;
        }
        
        /* Estilos para Seção de Menus Personalizados */
        .mpa-custom-menus-section {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 30px;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
            margin-bottom: 20px;
        }
        
        .mpa-custom-menus-section h2 {
            margin-top: 0;
            color: #23282d;
            border-bottom: 3px solid #0073aa;
            padding-bottom: 15px;
        }
        
        .mpa-add-custom-menu {
            background: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 25px;
            margin: 25px 0;
        }
        
        .mpa-add-custom-menu h3 {
            margin-top: 0;
            color: #2c5282;
        }
        
        .mpa-custom-menu-form {
            display: grid;
            gap: 20px;
        }
        
        .mpa-custom-form-row {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .mpa-custom-form-row label {
            font-weight: 600;
            color: #374151;
            font-size: 14px;
        }
        
        .mpa-custom-form-row input {
            padding: 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
            transition: all 0.2s ease;
        }
        
        .mpa-custom-form-row input:focus {
            outline: none;
            border-color: #0073aa;
            box-shadow: 0 0 0 3px rgba(0,115,170,0.1);
        }
        
        .mpa-icon-input-wrapper {
            display: flex;
            gap: 12px;
            align-items: center;
        }
        
        .mpa-icon-input-wrapper input {
            flex: 1;
        }
        
        .mpa-custom-icon-preview {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background: #f3f4f6;
            border: 1px solid #d1d5db;
            border-radius: 6px;
        }
        
        .mpa-custom-icon-preview .dashicons {
            font-size: 20px !important;
            color: #0073aa;
        }
        
        .mpa-custom-form-actions {
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
        }
        
        .mpa-existing-custom-menus {
            margin-top: 30px;
        }
        
        .mpa-existing-custom-menus h3 {
            color: #1f2937;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 12px;
        }
        
        .mpa-custom-menu-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            margin: 10px 0;
            transition: all 0.2s ease;
        }
        
        .mpa-custom-menu-item:hover {
            border-color: #0073aa;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .mpa-custom-menu-info {
            display: flex;
            align-items: center;
            gap: 12px;
            flex: 1;
        }
        
        .mpa-custom-menu-icon .dashicons {
            font-size: 18px !important;
            color: #0073aa;
        }
        
        .mpa-custom-menu-title {
            font-weight: 600;
            color: #1f2937;
            margin-right: 15px;
        }
        
        .mpa-custom-menu-url {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            color: #6b7280;
            background: #f9fafb;
            padding: 4px 8px;
            border-radius: 4px;
        }
        
        .mpa-custom-menu-actions {
            display: flex;
            gap: 5px;
        }
        
        .mpa-custom-menu-actions .button {
            padding: 8px;
            min-width: auto;
            height: auto;
        }
        
        .mpa-custom-menu-actions .edit-custom-menu {
            color: #0073aa;
            border-color: #0073aa;
        }
        
        .mpa-custom-menu-actions .delete-custom-menu {
            color: #dc2626;
            border-color: #dc2626;
        }
        
        .mpa-custom-menu-actions .button:hover {
            opacity: 0.8;
        }
        
        .mpa-no-custom-menus {
            text-align: center;
            padding: 40px 20px;
            color: #6b7280;
            background: #f9fafb;
            border-radius: 8px;
            border: 1px dashed #d1d5db;
        }
        
        #add-custom-menu-btn {
            background: linear-gradient(135deg, #0073aa 0%, #005a87 100%);
            border: none;
            padding: 12px 24px;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        
        #add-custom-menu-btn:hover {
            background: linear-gradient(135deg, #005a87 0%, #004466 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        /* Responsividade para menus personalizados */
        @media (max-width: 768px) {
            .mpa-custom-menu-item {
                flex-direction: column;
                align-items: stretch;
                gap: 15px;
            }
            
            .mpa-custom-menu-info {
                justify-content: center;
                text-align: center;
            }
            
            .mpa-custom-menu-actions {
                justify-content: center;
            }
            
            .mpa-icon-input-wrapper {
                flex-direction: column;
                align-items: stretch;
            }
            
            .mpa-custom-icon-preview {
                align-self: center;
            }
        }
        
        /* Responsividade para ícones */
        @media (max-width: 768px) {
            .mpa-icons-grid {
                grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
                gap: 10px;
            }
            
            .mpa-icon-item {
                padding: 15px 10px;
            }
            
            .mpa-icon-item .dashicons {
                font-size: 24px !important;
            }
            
            .mpa-copy-notification {
                right: 10px;
                font-size: 14px;
            }
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
        // Função de confirmação para reset
        function confirmReset() {
            return confirm(
                '⚠️ ATENÇÃO: Esta ação irá resetar TODAS as configurações do plugin!\n\n' +
                '• Todas as permissões de menu serão removidas\n' +
                '• Todos os nomes personalizados de menus serão removidos\n' +
                '• A ordem personalizada dos menus será removida\n' +
                '• Os menus voltarão ao estado padrão do WordPress\n\n' +
                'Esta ação NÃO PODE ser desfeita!\n\n' +
                'Tem certeza que deseja continuar?'
            );
        }
        
        // Garantir que ajaxurl está disponível
        if (typeof ajaxurl === 'undefined') {
            var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Verificar se estamos na página de gerenciamento de menus (não na página de ícones)
            const isMenuManagementPage = !document.querySelector('.mpa-icons-section');
            
            if (isMenuManagementPage) {
                // Botão Marcar Todos
                const selectAllBtn = document.getElementById('mpa-select-all');
                if (selectAllBtn) {
                    selectAllBtn.addEventListener('click', function() {
                        document.querySelectorAll('.mpa-menu-checkbox, .mpa-submenu-checkbox').forEach(cb => {
                            cb.checked = true;
                        });
                    });
                }
                
                // Botão Desmarcar Todos
                const selectNoneBtn = document.getElementById('mpa-select-none');
                if (selectNoneBtn) {
                    selectNoneBtn.addEventListener('click', function() {
                        document.querySelectorAll('.mpa-menu-checkbox, .mpa-submenu-checkbox').forEach(cb => {
                            cb.checked = false;
                        });
                    });
                }
                
                // Botão Apenas Core WP (desmarcar taxonomias e plugins)
                const selectCoreBtn = document.getElementById('mpa-select-core');
                if (selectCoreBtn) {
                    selectCoreBtn.addEventListener('click', function() {
                        document.querySelectorAll('.mpa-menu-checkbox').forEach(cb => {
                            const menuType = cb.getAttribute('data-menu-type');
                            cb.checked = (menuType === 'core');
                        });
                        // Desmarcar todos os submenus para simplificar
                        document.querySelectorAll('.mpa-submenu-checkbox').forEach(cb => {
                            cb.checked = true;
                        });
                    });
                }
                
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
            
            // Atualização em tempo real dos títulos ao digitar COM AUTO-SAVE
            document.querySelectorAll('.mpa-custom-title-input, .mpa-custom-icon-input').forEach(input => {
                input.addEventListener('input', function() {
                    const menuItem = this.closest('.mpa-menu-item, .mpa-submenu-item');
                    
                    // Atualizar título em tempo real apenas para campos de título
                    if (this.classList.contains('mpa-custom-title-input')) {
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
                    }
                    
                    // Auto-save com debounce
                    clearTimeout(this.autoSaveTimer);
                    this.autoSaveTimer = setTimeout(() => {
                        autoSaveCustomization(this);
                    }, 1000); // Salva após 1 segundo de inatividade
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
            } // Fim da verificação isMenuManagementPage
        });

        // Função para copiar ícone para clipboard
        function copyIcon(iconName) {
            navigator.clipboard.writeText(iconName).then(function() {
                // Mostrar notificação
                const notification = document.getElementById('mpa-copy-notification');
                if (notification) {
                    notification.classList.add('show');
                    setTimeout(() => {
                        notification.classList.remove('show');
                    }, 2000);
                }
            }).catch(function(err) {
                console.error('Erro ao copiar: ', err);
                // Fallback para navegadores antigos
                const textArea = document.createElement('textarea');
                textArea.value = iconName;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                
                // Mostrar notificação
                const notification = document.getElementById('mpa-copy-notification');
                if (notification) {
                    notification.classList.add('show');
                    setTimeout(() => {
                        notification.classList.remove('show');
                    }, 2000);
                }
            });
        }

        // Função para limpar busca
        function clearSearch() {
            const searchInput = document.getElementById('mpa-icon-search');
            if (searchInput) {
                searchInput.value = '';
                searchInput.dispatchEvent(new Event('input'));
                searchInput.focus();
            }
        }

        // Função de busca de ícones
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('mpa-icon-search');
            const iconsContainer = document.getElementById('mpa-icons-container');
            
            if (searchInput && iconsContainer) {
                searchInput.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase();
                    const iconItems = iconsContainer.querySelectorAll('.mpa-icon-item');
                    
                    iconItems.forEach(function(item) {
                        const iconName = item.getAttribute('data-icon');
                        if (iconName && iconName.toLowerCase().includes(searchTerm)) {
                            item.style.display = 'flex';
                        } else {
                            item.style.display = 'none';
                        }
                    });
                });
            }
        });

        // =====================================
        // FUNCIONALIDADES DE MENUS PERSONALIZADOS
        // =====================================

        // Preview dinâmico do ícone personalizado
        const customIconInput = document.getElementById('custom-menu-icon');
        const customIconPreview = document.querySelector('.mpa-custom-icon-preview .dashicons');
        
        if (customIconInput && customIconPreview) {
            customIconInput.addEventListener('input', function() {
                const iconClass = this.value.trim();
                if (iconClass) {
                    if (iconClass.startsWith('dashicons-')) {
                        customIconPreview.className = 'dashicons ' + iconClass;
                    } else {
                        customIconPreview.className = 'dashicons dashicons-' + iconClass;
                    }
                } else {
                    customIconPreview.className = 'dashicons dashicons-admin-generic';
                }
            });
        }

        // Botão "Adicionar Menu Personalizado"
        const addCustomMenuBtn = document.getElementById('add-custom-menu-btn');
        if (addCustomMenuBtn) {
            addCustomMenuBtn.addEventListener('click', function() {
                const title = document.getElementById('custom-menu-title').value.trim();
                const icon = document.getElementById('custom-menu-icon').value.trim();
                const url = document.getElementById('custom-menu-url').value.trim();

                // Validação
                if (!title) {
                    alert('Por favor, insira um título para o menu.');
                    document.getElementById('custom-menu-title').focus();
                    return;
                }

                if (!url) {
                    alert('Por favor, insira uma URL de destino.');
                    document.getElementById('custom-menu-url').focus();
                    return;
                }

                // Validar URL básica
                try {
                    if (!url.startsWith('http') && !url.startsWith('admin.php') && !url.startsWith('/')) {
                        throw new Error('URL deve começar com http, admin.php ou /');
                    }
                } catch (e) {
                    alert('Por favor, insira uma URL válida (exemplo: https://exemplo.com ou admin.php?page=minha-pagina).');
                    document.getElementById('custom-menu-url').focus();
                    return;
                }

                // Chamar função AJAX para salvar
                addCustomMenu(title, icon || 'dashicons-admin-generic', url);
            });
        }

        // Função para adicionar menu personalizado via AJAX
        function addCustomMenu(title, icon, url) {
            // Obter role atual da URL
            const currentUrl = new URL(window.location);
            const selectedRole = currentUrl.searchParams.get('role');

            if (!selectedRole) {
                alert('Erro: Role não selecionada.');
                return;
            }

            // Verificar se está em modo de edição
            const editingId = addCustomMenuBtn.dataset.editingId;
            const isEditing = editingId && editingId.trim() !== '';

            // Mostrar indicador de carregamento
            addCustomMenuBtn.disabled = true;
            addCustomMenuBtn.innerHTML = isEditing ? '⏳ Atualizando...' : '⏳ Salvando...';

            // Fazer requisição AJAX
            const fallbackUrl = '<?php echo str_replace("https://localhost", "http://localhost", admin_url('admin-ajax.php')); ?>';
            const ajaxUrl = typeof mpa_ajax_object !== 'undefined' ? mpa_ajax_object.ajax_url : fallbackUrl;
            
            console.log('🐛 AJAX Debug:', {
                'mpa_ajax_object': typeof mpa_ajax_object !== 'undefined' ? mpa_ajax_object : 'undefined',
                'fallbackUrl': fallbackUrl,
                'ajaxUrl': ajaxUrl,
                'selectedRole': selectedRole,
                'title': title,
                'icon': icon,
                'url': url,
                'isEditing': isEditing,
                'editingId': editingId
            });

            // Preparar parâmetros baseado no modo (edição ou criação)
            const requestParams = {
                action: isEditing ? 'mpa_edit_custom_menu' : 'mpa_add_custom_menu',
                role: selectedRole,
                title: title,
                icon: icon,
                url: url,
                _ajax_nonce: typeof mpa_ajax_object !== 'undefined' ? mpa_ajax_object.ajax_nonce : '<?php echo wp_create_nonce('mpa_custom_menu'); ?>'
            };

            // Se está editando, adicionar o menu_id
            if (isEditing) {
                requestParams.menu_id = editingId;
            }
            
            fetch(ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams(requestParams)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Limpar campos do formulário
                    document.getElementById('custom-menu-title').value = '';
                    document.getElementById('custom-menu-icon').value = '';
                    document.getElementById('custom-menu-url').value = '';
                    document.querySelector('.mpa-custom-icon-preview .dashicons').className = 'dashicons dashicons-admin-generic';

                    // Mostrar sucesso baseado no modo
                    const successMessage = isEditing ? '✅ Menu personalizado atualizado com sucesso!' : '✅ Menu personalizado adicionado com sucesso!';
                    alert(successMessage);

                    // Recarregar a página para mostrar o novo/atualizado menu
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    const errorMessage = isEditing ? '❌ Erro ao atualizar menu: ' : '❌ Erro ao adicionar menu: ';
                    alert(errorMessage + (data.data || 'Erro desconhecido'));
                }
            })
            .catch(error => {
                console.error('Erro na requisição:', error);
                alert('❌ Erro ao comunicar com o servidor. Tente novamente.');
            })
            .finally(() => {
                // Limpar modo de edição se estava editando
                if (isEditing) {
                    delete addCustomMenuBtn.dataset.editingId;
                }
                
                // Restaurar botão
                addCustomMenuBtn.disabled = false;
                addCustomMenuBtn.innerHTML = '🔗 Adicionar Menu Personalizado';
            });
        }

        // Botões de ação dos menus existentes (editar/excluir)
        document.querySelectorAll('.edit-custom-menu').forEach(btn => {
            btn.addEventListener('click', function() {
                const menuItem = this.closest('.mpa-custom-menu-item');
                const menuId = menuItem.getAttribute('data-menu-id');
                const currentTitle = menuItem.querySelector('.mpa-custom-menu-title').textContent;
                const currentIcon = menuItem.querySelector('.mpa-custom-menu-icon .dashicons').className.replace('dashicons ', '');
                const currentUrl = menuItem.querySelector('.mpa-custom-menu-url').textContent;

                // Preencher formulário com dados existentes para edição
                document.getElementById('custom-menu-title').value = currentTitle;
                document.getElementById('custom-menu-icon').value = currentIcon;
                document.getElementById('custom-menu-url').value = currentUrl;
                document.querySelector('.mpa-custom-icon-preview .dashicons').className = 'dashicons ' + currentIcon;

                // Rolar para o formulário
                document.querySelector('.mpa-add-custom-menu').scrollIntoView({ behavior: 'smooth' });

                // Alterar o texto do botão temporariamente
                const btn = document.getElementById('add-custom-menu-btn');
                btn.innerHTML = '✏️ Atualizar Menu Personalizado';
                btn.dataset.editingId = menuId;
            });
        });

        document.querySelectorAll('.delete-custom-menu').forEach(btn => {
            btn.addEventListener('click', function() {
                if (!confirm('🗑️ Tem certeza que deseja excluir este menu personalizado? Esta ação não pode ser desfeita.')) {
                    return;
                }

                const menuItem = this.closest('.mpa-custom-menu-item');
                const menuId = menuItem.getAttribute('data-menu-id');
                const menuTitle = menuItem.querySelector('.mpa-custom-menu-title').textContent;

                // Obter role atual
                const currentUrl = new URL(window.location);
                const selectedRole = currentUrl.searchParams.get('role');

                if (!selectedRole) {
                    alert('Erro: Role não selecionada.');
                    return;
                }

                // Fazer requisição AJAX para excluir
                const ajaxUrl = typeof mpa_ajax_object !== 'undefined' ? mpa_ajax_object.ajax_url : '<?php echo admin_url('admin-ajax.php'); ?>';
                fetch(ajaxUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'mpa_delete_custom_menu',
                        role: selectedRole,
                        menu_id: menuId,
                        nonce: typeof mpa_ajax_object !== 'undefined' ? mpa_ajax_object.ajax_nonce : '<?php echo wp_create_nonce('mpa_custom_menu'); ?>'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Remover o elemento da interface
                        menuItem.style.transition = 'all 0.3s ease';
                        menuItem.style.opacity = '0';
                        menuItem.style.transform = 'scale(0.9)';
                        
                        setTimeout(() => {
                            menuItem.remove();
                            
                            // Verificar se não há mais menus personalizados
                            const remainingMenus = document.querySelectorAll('.mpa-custom-menu-item');
                            if (remainingMenus.length === 0) {
                                document.getElementById('custom-menus-list').innerHTML = 
                                    '<div class="mpa-no-custom-menus"><p>Nenhum menu personalizado criado ainda. Use o formulário acima para criar o primeiro!</p></div>';
                            }
                        }, 300);

                        // Mostrar mensagem de sucesso
                        alert('✅ Menu "' + menuTitle + '" excluído com sucesso!');
                    } else {
                        alert('❌ Erro ao excluir menu: ' + (data.data || 'Erro desconhecido'));
                    }
                })
                .catch(error => {
                    console.error('Erro na requisição:', error);
                    alert('❌ Erro ao comunicar com o servidor. Tente novamente.');
                });
            });
        });

        // Funções para o seletor de role
        function changeRole(roleValue) {
            const currentUrl = new URL(window.location);
            currentUrl.searchParams.set('role', roleValue);
            // Manter view se estiver definida
            if (currentUrl.searchParams.has('view')) {
                const view = currentUrl.searchParams.get('view');
                if (view !== 'icons') {
                    currentUrl.searchParams.delete('view');
                }
            }
            window.location.href = currentUrl.toString();
        }

        function toggleView(viewType) {
            const currentUrl = new URL(window.location);
            const roleSelect = document.getElementById('role-select');
            const selectedRole = roleSelect ? roleSelect.value : null;
            
            if (selectedRole) {
                currentUrl.searchParams.set('role', selectedRole);
            }
            
            // Definir a view baseada no tipo
            if (viewType === 'icons') {
                currentUrl.searchParams.set('view', 'icons');
            } else if (viewType === 'custom_menus') {
                currentUrl.searchParams.set('view', 'custom_menus');
            } else {
                // viewType === 'menu' ou outro - remover view para mostrar gerenciar menus
                currentUrl.searchParams.delete('view');
            }
            
            window.location.href = currentUrl.toString();
        }

        // Função de Auto-Save para customizações
        function autoSaveCustomization(inputElement) {
            const menuItem = inputElement.closest('.mpa-menu-item, .mpa-submenu-item');
            const isSubmenu = menuItem.classList.contains('mpa-submenu-item');
            
            // Verificar se é um menu personalizado
            const isCustomMenu = menuItem.querySelector('.mpa-custom-menu-badge');
            
            // Obter dados básicos
            let menuSlug, submenuKey, fieldType, fieldValue, customMenuId;
            
            if (isCustomMenu) {
                // Para menus personalizados
                customMenuId = menuItem.getAttribute('data-custom-id') || menuItem.querySelector('[data-menu-id]')?.getAttribute('data-menu-id');
                menuSlug = menuItem.getAttribute('data-menu-slug');
                fieldType = inputElement.classList.contains('mpa-custom-title-input') ? 'title' : 
                          inputElement.classList.contains('mpa-custom-icon-input') ? 'icon' : 'url';
                fieldValue = inputElement.value.trim();
                
                if (customMenuId) {
                    saveCustomMenuEdit(customMenuId, fieldType, fieldValue, inputElement);
                    return;
                }
            } else if (isSubmenu) {
                // Para submenus
                const menuContainer = menuItem.closest('.mpa-menu-item');
                menuSlug = menuContainer.getAttribute('data-menu-slug');
                submenuKey = inputElement.closest('.mpa-submenu-edit-fields').getAttribute('data-submenu-key');
                fieldType = inputElement.classList.contains('mpa-custom-title-input') ? 'title' : 'icon';
                fieldValue = inputElement.value.trim();
            } else {
                // Para menus principais
                menuSlug = menuItem.getAttribute('data-menu-slug');
                fieldType = inputElement.classList.contains('mpa-custom-title-input') ? 'title' : 'icon';
                fieldValue = inputElement.value.trim();
            }
            
            // Mostrar indicador de salvamento
            showSaveIndicator(inputElement, 'saving');
            
            // Preparar dados para envio
            const formData = new FormData();
            formData.append('action', 'mpa_auto_save_customization');
            formData.append('menu_slug', menuSlug);
            formData.append('field_type', fieldType);
            formData.append('field_value', fieldValue);
            formData.append('nonce', '<?php echo wp_create_nonce("mpa_auto_save"); ?>');
            
            if (isSubmenu) {
                formData.append('submenu_key', submenuKey);
                formData.append('is_submenu', '1');
            }
            
            // Enviar via AJAX
            fetch('<?php echo admin_url("admin-ajax.php"); ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSaveIndicator(inputElement, 'success');
                    console.log('Auto-save realizado com sucesso:', data.data);
                } else {
                    showSaveIndicator(inputElement, 'error');
                    console.error('Erro no auto-save:', data.data);
                }
            })
            .catch(error => {
                showSaveIndicator(inputElement, 'error');
                console.error('Erro na requisição de auto-save:', error);
            });
        }
        
        // Função para salvar edição de menus personalizados
        function saveCustomMenuEdit(menuId, fieldType, fieldValue, inputElement) {
            // Obter role atual
            const currentUrl = new URL(window.location);
            const selectedRole = currentUrl.searchParams.get('role');
            
            if (!selectedRole) {
                showSaveIndicator(inputElement, 'error');
                console.error('Role não selecionada');
                return;
            }
            
            // Mostrar indicador de salvamento
            showSaveIndicator(inputElement, 'saving');
            
            // Preparar dados para envio
            const ajaxUrl = typeof mpa_ajax_object !== 'undefined' ? mpa_ajax_object.ajax_url : '<?php echo admin_url("admin-ajax.php"); ?>';
            const nonce = typeof mpa_ajax_object !== 'undefined' ? mpa_ajax_object.ajax_nonce : '<?php echo wp_create_nonce("mpa_custom_menu"); ?>';
            
            // Obter valores atuais de todos os campos
            const menuItem = inputElement.closest('.mpa-menu-item');
            const titleInput = menuItem.querySelector('input[name*="custom_title"]');
            const iconInput = menuItem.querySelector('input[name*="custom_icon"]');
            
            const requestData = {
                action: 'mpa_edit_custom_menu',
                role: selectedRole,
                menu_id: menuId,
                title: titleInput ? titleInput.value : '',
                icon: iconInput ? iconInput.value || 'dashicons-admin-generic' : 'dashicons-admin-generic',
                url: fieldValue, // Para este caso, sempre será a URL
                _ajax_nonce: nonce
            };
            
            // Fazer requisição AJAX
            fetch(ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams(requestData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSaveIndicator(inputElement, 'success');
                    
                    // Atualizar interface se necessário
                    if (fieldType === 'title') {
                        const titleElement = menuItem.querySelector('.mpa-menu-title');
                        if (titleElement && fieldValue.trim()) {
                            const badge = titleElement.querySelector('.mpa-custom-menu-badge');
                            titleElement.textContent = fieldValue;
                            if (badge) titleElement.appendChild(badge);
                        }
                    } else if (fieldType === 'icon') {
                        const iconElement = menuItem.querySelector('.mpa-menu-icon .dashicons');
                        if (iconElement && fieldValue.trim()) {
                            const iconClass = fieldValue.startsWith('dashicons-') ? fieldValue : 'dashicons-' + fieldValue;
                            iconElement.className = 'dashicons ' + iconClass;
                        }
                    }
                    
                    console.log('Menu personalizado atualizado com sucesso!');
                } else {
                    showSaveIndicator(inputElement, 'error');
                    console.error('Erro ao atualizar menu personalizado:', data.data);
                }
            })
            .catch(error => {
                showSaveIndicator(inputElement, 'error');
                console.error('Erro na requisição:', error);
            });
        }

        // Função para mostrar indicadores visuais de salvamento
        function showSaveIndicator(inputElement, status) {
            // Remover indicadores existentes
            const existingIndicator = inputElement.parentNode.querySelector('.mpa-save-indicator');
            if (existingIndicator) {
                existingIndicator.remove();
            }
            
            // Criar novo indicador
            const indicator = document.createElement('span');
            indicator.className = 'mpa-save-indicator mpa-save-' + status;
            
            let text, icon;
            switch (status) {
                case 'saving':
                    text = 'Salvando...';
                    icon = '⏳';
                    break;
                case 'success':
                    text = 'Salvo!';
                    icon = '✅';
                    break;
                case 'error':
                    text = 'Erro';
                    icon = '❌';
                    break;
            }
            
            indicator.innerHTML = icon + ' ' + text;
            inputElement.parentNode.appendChild(indicator);
            
            // Auto-remover indicador após delay
            if (status !== 'saving') {
                setTimeout(() => {
                    if (indicator.parentNode) {
                        indicator.remove();
                    }
                }, 2000);
            }
        }
    </script>
    <?php
}

// Função para obter todos os menus do admin (ATUALIZADA COM TAXONOMIAS)
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
        // Ignorar menus personalizados - eles são controlados pela própria função mpa_add_custom_menus_to_admin
        if (isset($menu_item['custom']) && $menu_item['custom']) {
            continue;
        }
        
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
        $file_content = file_get_contents($_FILES['mpa_import_file']['tmp_name']);
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

    wp_redirect($redirect);
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
                    wp_redirect($url);
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

