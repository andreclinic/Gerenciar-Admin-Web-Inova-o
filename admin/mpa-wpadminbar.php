<?php
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
        '1.0.0'
    );

    wp_enqueue_script(
        'mpa-wpadminbar-js',
        ADMIN_BAR_MENU_URL . 'assets/js/mpa-wpadminbar.js',
        ['jquery'],
        null,
        true
    );
}

// Implementar header customizado baseado no modelo_dashboard.html
add_action('in_admin_header', 'mpa_render_custom_header', 5);

function mpa_render_custom_header() {
    $current_user = wp_get_current_user();
    $user_initials = strtoupper(substr($current_user->first_name, 0, 1) . substr($current_user->last_name, 0, 1));
    if (empty($user_initials)) {
        $user_initials = strtoupper(substr($current_user->display_name, 0, 2));
    }
    
    ?>
    <div id="mpa-custom-header" class="mpa-header">
        <div class="mpa-header-left">
            <button id="mpa-sidebar-toggle" class="mpa-sidebar-toggle" type="button">
                <svg class="mpa-icon" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 15a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd" />
                </svg>
            </button>
            
            <button id="mpa-mobile-menu-btn" class="mpa-mobile-menu-btn">
                <svg class="mpa-icon" viewBox="0 0 24 24" stroke="currentColor" fill="none">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
            
            <div class="mpa-logo">
                Analytics
                <span class="mpa-logo-accent">Pro</span>
            </div>
        </div>
        <div class="mpa-header-right">
            <div class="mpa-date-filter">📅 Últimos 30 dias</div>
            <div class="mpa-header-buttons">
                <button id="mpa-dark-mode-toggle" class="mpa-header-btn">
                    <svg class="mpa-icon" viewBox="0 0 24 24" stroke="currentColor" fill="none">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                    </svg>
                </button>
                <button class="mpa-header-btn">
                    <svg class="mpa-icon" viewBox="0 0 24 24" stroke="currentColor" fill="none">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </button>
                <button class="mpa-header-btn">
                    <svg class="mpa-icon" viewBox="0 0 24 24" stroke="currentColor" fill="none">
                        <path d="M14.97 21a3.001 3.001 0 01-5.94 0h5.94zM18 8a6 6 0 00-12 0c0 7-3 9-3 9h18s-3-2-3-9z"/>
                    </svg>
                    <span class="mpa-notification-dot"></span>
                </button>
            </div>
            <div class="mpa-user-info">
                <div class="mpa-user-avatar"><?php echo esc_html($user_initials); ?></div>
                <div class="mpa-user-details">
                    <span class="mpa-user-name">Olá <?php echo esc_html($current_user->display_name); ?></span>
                    <p class="mpa-user-welcome">Bem-vindo de volta!</p>
                </div>
            </div>
        </div>
    </div>
    <?php
}

// Esconder a admin bar padrão e adicionar estilos
add_action('admin_head', function() {
    echo '<style>
        #wpadminbar { display: none !important; }
        html.wp-toolbar { padding-top: 0 !important; }
        body { margin-top: 0 !important; }
    </style>';
});

// Remover itens nativos da admin bar
add_action('admin_bar_menu', function ($bar) {
    $bar->remove_node('wp-logo');
    $bar->remove_node('comments');
    $bar->remove_node('updates');
    $bar->remove_node('new-content');
}, 999);