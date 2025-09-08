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

// Esconder elementos padrão do WordPress que não queremos
add_action('admin_head', function () {
    echo '<style>
        /* Esconder elementos que não fazem parte do novo layout */
        #screen-options-link-wrap, 
        #contextual-help-link-wrap,
        .wrap h1.wp-heading-inline,
        .page-title-action,
        .tablenav,
        .wp-header-end { 
            display: none !important; 
        }
        
        /* Esconder notices padrão no dashboard customizado */
        .toplevel_page_mpa-dashboard .notice,
        .toplevel_page_mpa-dashboard .update-nag,
        .toplevel_page_mpa-dashboard .error { 
            display: none !important; 
        }
    </style>';
});

// Limpar conteúdo padrão do dashboard quando estiver na página customizada
add_action('all_admin_notices', function () {
    $screen = get_current_screen();
    if ($screen && $screen->base === 'toplevel_page_mpa-dashboard') {
        // Limpar qualquer output padrão
        ob_start();
        
        // Hook para permitir que outros módulos renderizem o conteúdo
        do_action('mpa_render_dashboard_content');
        
        $content = ob_get_clean();
        if (!empty($content)) {
            echo $content;
        }
    }
});