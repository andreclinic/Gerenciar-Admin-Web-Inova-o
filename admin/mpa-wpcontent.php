<?php
// Verificar se deve desativar para administradores
if (function_exists('mpa_should_disable_for_admin') && mpa_should_disable_for_admin()) {
    return; // Não carregar customizações se modo compatibilidade estiver ativo
}

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
        .wp-header-end {
            display: none !important;
        }

        /* Esconder apenas nos dashboards customizados, mas manter paginação em outras páginas */
        .toplevel_page_mpa-dashboard .wrap h1.wp-heading-inline,
        .toplevel_page_mpa-dashboard .page-title-action,
        .toplevel_page_mpa-dashboard .tablenav {
            display: none !important;
        }

        /* Esconder notices padrão no dashboard customizado */
        .toplevel_page_mpa-dashboard .notice,
        .toplevel_page_mpa-dashboard .update-nag,
        .toplevel_page_mpa-dashboard .error {
            display: none !important;
        }

        /* Garantir que paginação seja visível em páginas de listagem */
        .edit-php .tablenav,
        .upload-php .tablenav,
        .edit-tags-php .tablenav,
        .users-php .tablenav,
        .post-type-product .tablenav,
        .woocommerce_page_wc-orders .tablenav {
            display: block !important;
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