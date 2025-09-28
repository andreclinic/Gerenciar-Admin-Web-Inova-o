<?php
// Verificar se deve desativar para administradores
if (function_exists('mpa_should_disable_for_admin') && mpa_should_disable_for_admin()) {
    return; // Não carregar customizações se modo compatibilidade estiver ativo
}

// Carregar os arquivos CSS e JS corretamente
add_action('admin_enqueue_scripts', 'mpa_wpadminbar_assets');

function mpa_wpadminbar_assets($hook)
{
    // Carregar apenas nas páginas do admin
    if (!is_admin())
        return;

    wp_enqueue_style(
        'mpa-wpadminbar-css',
        ADMIN_BAR_MENU_URL . 'assets/css/mpa-wpadminbar.css',
        ['admin-bar'],
        '1.0.1'
    );

    wp_enqueue_script(
        'mpa-wpadminbar-js',
        ADMIN_BAR_MENU_URL . 'assets/js/mpa-wpadminbar.js',
        ['jquery'],
        null,
        true
    );

    // Localize script with AJAX data
    wp_localize_script('mpa-wpadminbar-js', 'mpa_ajax', array(
        'url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('mpa_notifications')
    ));
}

// Implementar header customizado baseado no modelo_dashboard.html
add_action('in_admin_header', 'mpa_render_custom_header', 5);

function mpa_render_custom_header()
{
    $current_user = wp_get_current_user();
    $user_initials = strtoupper(substr($current_user->first_name, 0, 1) . substr($current_user->last_name, 0, 1));
    if (empty($user_initials)) {
        $user_initials = strtoupper(substr($current_user->display_name, 0, 2));
    }

    // Get WordPress notifications
    $notifications = mpa_get_wp_notifications();

    ?>
    <div id="mpa-custom-header" class="mpa-header">
        <div class="mpa-header-left">
            <button id="mpa-sidebar-toggle" class="mpa-sidebar-toggle" type="button">
                <svg class="mpa-icon" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd"
                        d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 15a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z"
                        clip-rule="evenodd" />
                </svg>
            </button>

            <button id="mpa-mobile-menu-btn" class="mpa-mobile-menu-btn">
                <svg class="mpa-icon" viewBox="0 0 24 24" stroke="currentColor" fill="none">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>

            <div class="mpa-logo">
                <?php
                $logo_url = get_option('mpa_logo_url', 'https://www.webinovacao.com.br/wp-content/uploads/2024/07/logo-web-inovacao-horizontal-escura.png');
                if ($logo_url): ?>
                    <img src="<?php echo esc_url($logo_url); ?>" alt="Logo" class="mpa-logo-image" />
                <?php else: ?>
                    Analytics
                    <span class="mpa-logo-accent">Pro</span>
                <?php endif; ?>
            </div>
        </div>
        <div class="mpa-header-right">
            <div class="mpa-header-buttons">
                <button id="mpa-dark-mode-toggle" class="mpa-header-btn" type="button" aria-label="Alternar modo escuro">
                    <svg class="mpa-icon" viewBox="0 0 24 24" stroke="currentColor" fill="none">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                    </svg>
                </button>
                <button id="mpa-fullscreen-toggle" class="mpa-header-btn" type="button" aria-label="Abrir em tela cheia" aria-pressed="false">
                    <svg class="mpa-icon mpa-fullscreen-enter" viewBox="0 0 24 24" stroke="currentColor" fill="none">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 9V5a1 1 0 011-1h4M4 5l5 5M20 9V5a1 1 0 00-1-1h-4m5 5l-5-5M4 15v4a1 1 0 001 1h4m-5-5l5 5M20 15v4a1 1 0 01-1 1h-4m5-5l-5 5" />
                    </svg>
                    <svg class="mpa-icon mpa-fullscreen-exit" viewBox="0 0 24 24" stroke="currentColor" fill="none">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 4H5a1 1 0 00-1 1v4m0-5l6 6M15 4h4a1 1 0 011 1v4m-5-5l6 6M9 20H5a1 1 0 01-1-1v-4m6 5l-6-6M15 20h4a1 1 0 001-1v-4m-6 5l6-6" />
                    </svg>
                </button>
                <div class="mpa-notifications-container">
                    <button class="mpa-header-btn" id="mpa-notifications-btn">
                        <svg class="mpa-icon" viewBox="0 0 24 24" stroke="currentColor" fill="none">
                            <path d="M14.97 21a3.001 3.001 0 01-5.94 0h5.94zM18 8a6 6 0 00-12 0c0 7-3 9-3 9h18s-3-2-3-9z" />
                        </svg>
                        <?php if ($notifications['unread_count'] > 0): ?>
                            <span
                                class="mpa-notification-dot"><?php echo $notifications['unread_count'] > 9 ? '9+' : $notifications['unread_count']; ?></span>
                        <?php endif; ?>
                    </button>

                    <div class="mpa-notifications-dropdown" id="mpa-notifications-dropdown">
                        <div class="mpa-notifications-header">
                            <h3>Notificações</h3>
                            <?php if ($notifications['unread_count'] > 0): ?>
                                <button class="mpa-mark-all-read" id="mpa-mark-all-read">
                                    Marcar todas como lidas
                                </button>
                            <?php endif; ?>
                        </div>

                        <div class="mpa-notifications-list">
                            <?php if (empty($notifications['items'])): ?>
                                <div class="mpa-notification-empty">
                                    <svg class="mpa-empty-icon" viewBox="0 0 24 24" stroke="currentColor" fill="none">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 17h5l-5 5v-5zM9 7h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V9a2 2 0 012-2h4z" />
                                    </svg>
                                    <p>Nenhuma notificação</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($notifications['items'] as $notification): ?>
                                    <div class="mpa-notification-item <?php echo $notification['read'] ? 'read' : 'unread'; ?>"
                                        data-notification-id="<?php echo esc_attr($notification['id']); ?>">
                                        <div class="mpa-notification-icon <?php echo esc_attr($notification['type']); ?>">
                                            <?php echo $notification['icon']; ?>
                                        </div>
                                        <div class="mpa-notification-content">
                                            <div class="mpa-notification-title">
                                                <?php echo esc_html($notification['title']); ?>
                                            </div>
                                            <div class="mpa-notification-message">
                                                <?php echo esc_html($notification['message']); ?>
                                            </div>
                                            <div class="mpa-notification-time">
                                                <?php echo esc_html($notification['time_ago']); ?>
                                            </div>
                                        </div>
                                        <?php if ($notification['action_url']): ?>
                                            <a href="<?php echo esc_url($notification['action_url']); ?>"
                                                class="mpa-notification-action">
                                                Ver
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>
            </div>
            <div class="mpa-user-info" id="mpa-user-info">
                <div class="mpa-user-avatar"><?php echo esc_html($user_initials); ?></div>
                <div class="mpa-user-details">
                    <span class="mpa-user-name">Olá <?php echo esc_html($current_user->display_name); ?></span>
                    <p class="mpa-user-welcome">Bem-vindo de volta!</p>
                </div>
                <svg class="mpa-icon mpa-dropdown-arrow" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd"
                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                        clip-rule="evenodd" />
                </svg>
                <div class="mpa-user-dropdown" id="mpa-user-dropdown">
                    <a href="<?php echo esc_url(home_url('/')); ?>" class="mpa-dropdown-item" target="_blank">
                        <svg class="mpa-dropdown-icon" viewBox="0 0 24 24" stroke="currentColor" fill="none">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9v-9m0-9v9" />
                        </svg>
                        Ver o site
                    </a>
                    <a href="<?php echo esc_url(admin_url('profile.php')); ?>" class="mpa-dropdown-item">
                        <svg class="mpa-dropdown-icon" viewBox="0 0 24 24" stroke="currentColor" fill="none">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        Perfil
                    </a>
                    <div class="mpa-dropdown-divider"></div>
                    <a href="<?php echo esc_url(wp_logout_url(admin_url())); ?>" class="mpa-dropdown-item mpa-logout">
                        <svg class="mpa-dropdown-icon" viewBox="0 0 24 24" stroke="currentColor" fill="none">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        Sair
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php
}

// Esconder a admin bar padrão e adicionar estilos
add_action('admin_head', function () {
    echo '<style>
        #wpadminbar { display: none !important; }
        html.wp-toolbar { padding-top: 0 !important; }
        body { margin-top: 0 !important; }
        
        /* Force logo sizing inline to bypass cache issues */
        .mpa-logo .mpa-logo-image,
        .mpa-header .mpa-logo .mpa-logo-image,
        img.mpa-logo-image {
            max-height: 40px !important;
            width: auto !important;
            max-width: 200px !important;
            object-fit: contain !important;
            height: auto !important;
        }
        
        @media (max-width: 768px) {
            .mpa-logo .mpa-logo-image,
            .mpa-header .mpa-logo .mpa-logo-image,
            img.mpa-logo-image {
                max-height: 30px !important;
                max-width: 200px !important;
            }
        }
        
        @media (max-width: 480px) {
            .mpa-logo .mpa-logo-image,
            .mpa-header .mpa-logo .mpa-logo-image,
            img.mpa-logo-image {
                max-height: 30px !important;
                max-width: 200px !important;
            }
        }
    </style>';
});

// Função para obter notificações do WordPress
function mpa_get_wp_notifications()
{
    $notifications = array(
        'items' => array(),
        'unread_count' => 0
    );

    // Get user's read notifications
    $user_id = get_current_user_id();
    $read_notifications = get_user_meta($user_id, 'mpa_read_notifications', true);
    if (!is_array($read_notifications)) {
        $read_notifications = array();
    }

    // WordPress Updates
    $updates = wp_get_update_data();
    if ($updates['counts']['total'] > 0) {
        $notification_id = 'wp-updates';
        $is_read = in_array($notification_id, $read_notifications);

        // Only add if not read
        if (!$is_read) {
            $notifications['items'][] = array(
                'id' => $notification_id,
                'type' => 'update',
                'icon' => '<svg viewBox="0 0 24 24" stroke="currentColor" fill="none"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>',
                'title' => 'Atualizações Disponíveis',
                'message' => sprintf('%d atualizações disponíveis', $updates['counts']['total']),
                'time_ago' => 'agora',
                'read' => false,
                'action_url' => admin_url('update-core.php')
            );
            $notifications['unread_count']++;
        }
    }

    // Comments awaiting moderation
    $awaiting_mod = wp_count_comments();
    if ($awaiting_mod->moderated > 0) {
        $notification_id = 'comments-moderation';
        $is_read = in_array($notification_id, $read_notifications);

        // Only add if not read
        if (!$is_read) {
            $notifications['items'][] = array(
                'id' => $notification_id,
                'type' => 'comment',
                'icon' => '<svg viewBox="0 0 24 24" stroke="currentColor" fill="none"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>',
                'title' => 'Comentários Pendentes',
                'message' => sprintf('%d comentários aguardando moderação', $awaiting_mod->moderated),
                'time_ago' => 'recente',
                'read' => false,
                'action_url' => admin_url('edit-comments.php?comment_status=moderated')
            );
            $notifications['unread_count']++;
        }
    }

    // Plugin notifications (if any)
    $plugin_updates = get_site_transient('update_plugins');
    if (!empty($plugin_updates->response)) {
        $plugin_count = count($plugin_updates->response);
        $notification_id = 'plugin-updates';
        $is_read = in_array($notification_id, $read_notifications);

        // Only add if not read
        if (!$is_read) {
            $notifications['items'][] = array(
                'id' => $notification_id,
                'type' => 'plugin',
                'icon' => '<svg viewBox="0 0 24 24" stroke="currentColor" fill="none"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>',
                'title' => 'Atualizações de Plugins',
                'message' => sprintf('%d plugins precisam ser atualizados', $plugin_count),
                'time_ago' => '1 hora atrás',
                'read' => false,
                'action_url' => admin_url('update-core.php')
            );
            $notifications['unread_count']++;
        }
    }

    // Theme updates
    $theme_updates = get_site_transient('update_themes');
    if (!empty($theme_updates->response)) {
        $theme_count = count($theme_updates->response);
        $notification_id = 'theme-updates';
        $is_read = in_array($notification_id, $read_notifications);

        // Only add if not read
        if (!$is_read) {
            $notifications['items'][] = array(
                'id' => $notification_id,
                'type' => 'theme',
                'icon' => '<svg viewBox="0 0 24 24" stroke="currentColor" fill="none"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v14a2 2 0 01-2 2h-4zM21 5a2 2 0 00-2-2h-4a2 2 0 00-2 2v14a4 4 0 004 4h4a2 2 0 002-2V5z"/></svg>',
                'title' => 'Atualizações de Temas',
                'message' => sprintf('%d temas precisam ser atualizados', $theme_count),
                'time_ago' => '2 horas atrás',
                'read' => false,
                'action_url' => admin_url('update-core.php')
            );
            $notifications['unread_count']++;
        }
    }

    // Recent posts/pages (for content creators)
    if (current_user_can('edit_posts')) {
        $recent_posts = wp_get_recent_posts(array(
            'numberposts' => 1,
            'post_status' => 'draft'
        ));

        if (!empty($recent_posts)) {
            $notification_id = 'draft-posts';
            $is_read = in_array($notification_id, $read_notifications);

            // Only add if not read
            if (!$is_read) {
                $notifications['items'][] = array(
                    'id' => $notification_id,
                    'type' => 'content',
                    'icon' => '<svg viewBox="0 0 24 24" stroke="currentColor" fill="none"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>',
                    'title' => 'Rascunhos Pendentes',
                    'message' => sprintf('Você tem %d rascunho(s) não publicado(s)', count($recent_posts)),
                    'time_ago' => '1 dia atrás',
                    'read' => false,
                    'action_url' => admin_url('edit.php?post_status=draft')
                );
                $notifications['unread_count']++;
            }
        }
    }

    return $notifications;
}

// AJAX handler para marcar notificações como lidas
add_action('wp_ajax_mpa_mark_notification_read', 'mpa_mark_notification_read');

function mpa_mark_notification_read()
{
    // Verificar se usuário está logado
    if (!is_user_logged_in()) {
        wp_send_json_error('Acesso negado: usuário não autenticado');
    }

    check_ajax_referer('mpa_notifications', 'nonce');

    $notification_id = sanitize_text_field($_POST['notification_id']);
    $user_id = get_current_user_id();

    if (!$user_id || !$notification_id) {
        wp_die('Invalid request');
    }

    // Get current read notifications
    $read_notifications = get_user_meta($user_id, 'mpa_read_notifications', true);
    if (!is_array($read_notifications)) {
        $read_notifications = array();
    }

    // Add notification to read list
    if (!in_array($notification_id, $read_notifications)) {
        $read_notifications[] = $notification_id;
        update_user_meta($user_id, 'mpa_read_notifications', $read_notifications);
    }

    wp_send_json_success(array('message' => 'Notification marked as read'));
}

// AJAX handler para marcar todas as notificações como lidas
add_action('wp_ajax_mpa_mark_all_notifications_read', 'mpa_mark_all_notifications_read');

function mpa_mark_all_notifications_read()
{
    // Verificar se usuário está logado
    if (!is_user_logged_in()) {
        wp_send_json_error('Acesso negado: usuário não autenticado');
    }

    check_ajax_referer('mpa_notifications', 'nonce');

    $user_id = get_current_user_id();

    if (!$user_id) {
        wp_die('Invalid request');
    }

    // Get all current notification IDs
    $notifications = mpa_get_wp_notifications();
    $all_notification_ids = array();

    foreach ($notifications['items'] as $notification) {
        $all_notification_ids[] = $notification['id'];
    }

    // Mark all as read
    update_user_meta($user_id, 'mpa_read_notifications', $all_notification_ids);

    wp_send_json_success(array('message' => 'All notifications marked as read'));
}

// Remover itens nativos da admin bar
add_action('admin_bar_menu', function ($bar) {
    $bar->remove_node('wp-logo');
    $bar->remove_node('comments');
    $bar->remove_node('updates');
    $bar->remove_node('new-content');
}, 999);
