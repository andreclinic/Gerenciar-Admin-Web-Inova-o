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

    wp_enqueue_script(
        'mpa-admin-js',
        ADMIN_BAR_MENU_URL . 'assets/js/mpa-admin.js',
        ['jquery'],
        null,
        true
    );
}