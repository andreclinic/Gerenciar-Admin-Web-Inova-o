<?php

// Carregar os arquivos CSS e JS corretamente
add_action('admin_enqueue_scripts', 'mpa_wpcontent_assets');

function mpa_wpcontent_assets($hook)
{
    // Carregar apenas nas páginas do admin
    if (!is_admin())
        return;

    wp_enqueue_style(
        'mpa-wpcontent-css',
        ADMIN_BAR_MENU_URL . 'assets/css/mpa-wpcontent.css'
    );

    wp_enqueue_script(
        'mpa-wpcontent-js',
        ADMIN_BAR_MENU_URL . 'assets/js/mpa-wpcontent.js',
        ['jquery'],
        null,
        true
    );
}


// 1) Adicionar um bloco no topo da área de notificações do admin

add_action('all_admin_notices', function () {
    echo '<div class="notice notice-info is-dismissible">
            <p><strong>wpcontent:</strong> *** bloco injetado no topo da área de conteúdo.</p>
          </div>';
});

// 2) Mostrar só em telas específicas (ex.: listagem de Posts) em Posts

add_action('all_admin_notices', function () {
    $s = get_current_screen();
    if ($s && $s->id === 'edit-post') {
        echo '<div class="notice notice-success is-dismissible"><p>Somente na tela de Posts.</p></div>';
    }
});

// 3) Esconder elementos comuns (Ajuda / Opções de Tela) no topo à direita
// Exemplo: esconder a barra admin, ajuda e opções de tela adicionando o ID #wpadminbar
add_action('admin_head', function () {
    echo '<style>
        #screen-options-link-wrap, #contextual-help-link-wrap { display:none !important; }
    </style>';
});

add_action('all_admin_notices', function () {
    echo '<div class="notice notice-info is-dismissible">
            <p>Clique neste aviso para testar o evento JS...</p>
          </div>';
});