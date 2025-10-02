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

// Detecta o modo garçom para aplicar layout fullscreen
function mpa_is_modo_garcom_screen() {
    return is_admin() && isset($_GET['page']) && $_GET['page'] === 'modo-garcom-wc';
}

// Adiciona classes no body para isolar o escopo das regras de fullscreen
add_filter('admin_body_class', function ($classes) {
    if (!mpa_is_modo_garcom_screen()) {
        return $classes;
    }

    if (strpos($classes, 'dwi-theme') === false) {
        $classes .= ' dwi-theme';
    }

    if (strpos($classes, 'mpa-garcom-fullscreen') === false) {
        $classes .= ' mpa-garcom-fullscreen';
    }

    return $classes;
});

// Aplica estilos fullscreen apenas na página modo garçom
add_action('admin_head', function () {
    if (!mpa_is_modo_garcom_screen()) {
        return;
    }
    ?>
    <style type="text/css">
        body.dwi-theme.mpa-garcom-fullscreen,
        body.dwi-theme.mpa-garcom-fullscreen #wpwrap {
            margin: 0 !important;
            padding: 0 !important;
            background: #fff;
            min-height: 100vh;
        }

        body.dwi-theme.mpa-garcom-fullscreen #wpadminbar,
        body.dwi-theme.mpa-garcom-fullscreen #adminmenuback,
        body.dwi-theme.mpa-garcom-fullscreen #adminmenuwrap,
        body.dwi-theme.mpa-garcom-fullscreen #adminmenu,
        body.dwi-theme.mpa-garcom-fullscreen #mpa-custom-header,
        body.dwi-theme.mpa-garcom-fullscreen .mpa-header,
        body.dwi-theme.mpa-garcom-fullscreen #mpa-sidebar,
        body.dwi-theme.mpa-garcom-fullscreen #mpa-sidebar-overlay,
        body.dwi-theme.mpa-garcom-fullscreen #screen-meta,
        body.dwi-theme.mpa-garcom-fullscreen #screen-meta-links,
        body.dwi-theme.mpa-garcom-fullscreen #wpfooter {
            display: none !important;
        }

        body.dwi-theme.mpa-garcom-fullscreen #wpcontent,
        body.dwi-theme.mpa-garcom-fullscreen #wpbody,
        body.dwi-theme.mpa-garcom-fullscreen #wpbody-content {
            margin: 0 !important;
            padding: 0 !important;
            min-height: 100vh;
        }

        body.dwi-theme.mpa-garcom-fullscreen #wpbody-content > .wrap,
        body.dwi-theme.mpa-garcom-fullscreen #wpcontent > .wrap,
        body.dwi-theme.mpa-garcom-fullscreen .wrap {
            margin: 0 !important;
            padding: 0 !important;
            max-width: 100% !important;
            box-shadow: none !important;
            background: transparent !important;
        }
    </style>
    <?php
});
