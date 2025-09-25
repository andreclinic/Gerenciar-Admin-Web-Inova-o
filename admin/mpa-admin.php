<?php


// Carregar os arquivos CSS e JS corretamente
add_action('admin_enqueue_scripts', 'mpa_admin_assets');

function mpa_admin_assets($hook)
{
    // Carregar apenas nas páginas do admin
    if (!is_admin())
        return;

    wp_enqueue_style(
        'mpa-admin-css',
        ADMIN_BAR_MENU_URL . 'assets/css/mpa-admin.css'
    );

    // Carregar CSS e JS específicos para páginas de gerenciamento de menus
    if (isset($_GET['page']) && $_GET['page'] === 'mpa-menu-roles') {
        wp_enqueue_style(
            'mpa-menu-settings-css',
            ADMIN_BAR_MENU_URL . 'assets/css/mpa-menu-settings.css',
            ['mpa-admin-css'],
            '1.0.1'
        );

        wp_enqueue_script(
            'mpa-menu-settings-js',
            ADMIN_BAR_MENU_URL . 'assets/js/mpa-menu-settings.js',
            ['jquery'],
            '5.0.1',
            true
        );

        // Localizar script para scroll restoration
        wp_localize_script('mpa-menu-settings-js', 'mpaMenuSettings', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mpa_menu_settings'),
            'currentPage' => admin_url('admin.php?page=mpa-menu-roles')
        ]);
    }

    wp_enqueue_script(
        'mpa-admin-js',
        ADMIN_BAR_MENU_URL . 'assets/js/mpa-admin.js',
        ['jquery'],
        null,
        true
    );

    wp_enqueue_style(
        'mpa-preloader-css',
        ADMIN_BAR_MENU_URL . 'assets/css/mpa-preloader.css',
        [],
        null
    );

    wp_enqueue_script(
        'mpa-preloader-js',
        ADMIN_BAR_MENU_URL . 'assets/js/mpa-preloader.js',
        ['jquery'],
        null,
        true
    );
}

// CSS para targets com scroll-margin (evitar header fixo)
add_action('admin_head', function(){
    if (isset($_GET['page']) && $_GET['page'] === 'mpa-menu-roles') {
        echo '<style>:target{scroll-margin-top:72px}</style>';
    }
});

// Preloader global para feedback em navegação do admin
add_action('admin_footer', 'mpa_render_admin_preloader');

function mpa_render_admin_preloader() {
    ?>
    <div id="mpa-preloader" class="mpa-preloader" aria-hidden="true">
        <div class="mpa-spinner" role="status" aria-live="polite"></div>
        <p><?php esc_html_e('Carregando...', 'gerenciar-admin'); ?></p>
    </div>
    <?php
}
