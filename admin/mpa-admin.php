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
    if (strpos($hook, 'mpa-menu-roles') !== false || strpos($hook, 'mpa-main') !== false || isset($_GET['page']) && $_GET['page'] === 'mpa-menu-roles') {
        wp_enqueue_style(
            'mpa-menu-settings-css',
            ADMIN_BAR_MENU_URL . 'assets/css/mpa-menu-settings.css',
            ['mpa-admin-css'],
            '1.0.1' // Adicionar versão para forçar atualização do cache
        );

        wp_enqueue_script(
            'mpa-menu-settings-js',
            ADMIN_BAR_MENU_URL . 'assets/js/mpa-menu-settings.js',
            ['jquery'],
            '1.0.1',
            true
        );
    }

    wp_enqueue_script(
        'mpa-admin-js',
        ADMIN_BAR_MENU_URL . 'assets/js/mpa-admin.js',
        ['jquery'],
        null,
        true
    );
}