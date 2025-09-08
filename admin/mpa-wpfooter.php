<?php

// Carregar os arquivos CSS e JS corretamente
add_action('admin_enqueue_scripts', 'mpa_wpfooter_assets');

function mpa_wpfooter_assets($hook)
{
    // Carregar apenas nas páginas do admin
    if (!is_admin())
        return;

    wp_enqueue_style(
        'mpa-wpfooter-css',
        ADMIN_BAR_MENU_URL . 'assets/css/mpa-wpfooter.css'
    );

    wp_enqueue_script(
        'mpa-wpfooter-js',
        ADMIN_BAR_MENU_URL . 'assets/js/mpa-wpfooter.js',
        ['jquery'],
        null,
        true
    );
}

// Esconder o rodapé padrão do WordPress
add_action('admin_head', function () {
    echo '<style>
        #wpfooter { display: none !important; }
    </style>';
});

// Substituir rodapé padrão por versão customizada (se necessário)
add_filter('admin_footer_text', function ($text) {
    return ''; // Remover texto padrão
});

add_filter('update_footer', function ($text) {
    return ''; // Remover versão padrão
}, 999);