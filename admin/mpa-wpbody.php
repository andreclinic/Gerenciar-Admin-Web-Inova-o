<?php
// Verificar se deve desativar para administradores
if (function_exists('mpa_should_disable_for_admin') && mpa_should_disable_for_admin()) {
    return; // Não carregar customizações se modo compatibilidade estiver ativo
}

// Carregar os arquivos CSS e JS corretamente
add_action('admin_enqueue_scripts', 'mpa_wpbody_assets');

function mpa_wpbody_assets($hook)
{
    // Carregar apenas nas páginas do admin
    if (!is_admin())
        return;

    wp_enqueue_style(
        'mpa-wpbody-css',
        ADMIN_BAR_MENU_URL . 'assets/css/mpa-wpbody.css'
    );

    wp_enqueue_script(
        'mpa-wpbody-js',
        ADMIN_BAR_MENU_URL . 'assets/js/mpa-wpbody.js',
        ['jquery'],
        null,
        true
    );
}

// Método correto do WordPress - usando admin_head
add_action('admin_head', 'mpa_correct_wordpress_layout');

function mpa_correct_wordpress_layout() {
    ?>
    <style type="text/css">
        /* Método WordPress correto - mais defensivo */
        body.wp-admin {
            font-family: "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background-color: #f9fafb;
            margin-top: 50px !important;
            padding-top: 0 !important;
        }
        
        /* Esconder elementos padrão */
        #adminmenuback,
        #adminmenuwrap, 
        #adminmenu {
            display: none !important;
        }
        
        #wpadminbar {
            display: none !important;
        }
        
        /* Ajustar conteúdo principal com mais cuidado */
        #wpcontent {
            margin-left: 256px !important; /* 16rem = 256px */
            padding-left: 20px !important;
            transition: margin-left 0.3s ease !important;
        }
        
        /* Quando sidebar estiver recolhida */
        body.sidebar-collapsed #wpcontent {
            margin-left: 0 !important;
        }
        
        /* Solução específica para dashboard widgets */
        div#dashboard-widgets {
            width: auto !important;
            float: none !important;
            margin-top: 20px !important;
            margin-left: 0 !important;
            margin-right: 0 !important;
            padding-top: 0 !important;
        }
        
        /* DESKTOP UNIVERSAL - Padronização para todas as páginas */
        @media screen and (min-width: 783px) {
            
            /* Container principal sempre centralizado */
            #wpbody-content {
                margin-left: 0 !important;
                width: auto !important;
                float: none !important;
                padding-left: 20px !important;
                padding-right: 20px !important;
                padding-top: 20px !important;
                position: relative !important;
                box-sizing: border-box !important;
            }
            
            /* Wrapper universal para qualquer plugin/tema */
            .wrap {
                width: auto !important;
                max-width: none !important;
                margin-left: 0 !important;
                margin-right: 0 !important;
                margin-top: 0 !important;
                padding: 20px !important;
                box-sizing: border-box !important;
            }
            
            /* Todas as tabelas do WordPress/Plugins - padrão universal */
            table.wp-list-table,
            table.widefat,
            table.fixed,
            table.striped {
                width: 100% !important;
                max-width: 100% !important;
                table-layout: auto !important;
                box-sizing: border-box !important;
            }
            
            /* Títulos e botões - padrão universal */
            .wp-admin h1,
            .wp-admin .wp-heading-inline {
                margin-top: 20px !important;
                padding-top: 10px !important;
                display: flex !important;
                align-items: center !important;
                gap: 10px !important;
                flex-wrap: wrap !important;
            }
            
            /* Botões "Adicionar" - universal para todas as páginas */
            .wp-admin .page-title-action,
            .wp-admin .add-new-h2,
            .wp-admin h1 .page-title-action,
            .wp-admin .wp-header-end + .page-title-action,
            .wp-admin .wrap > h1 > .page-title-action,
            .wp-admin .wrap h1.wp-heading-inline + .page-title-action {
                display: inline-block !important;
                visibility: visible !important;
                opacity: 1 !important;
                position: relative !important;
                margin-left: 0 !important;
                margin-top: 0 !important;
                top: auto !important;
                left: auto !important;
                transform: none !important;
                z-index: auto !important;
                float: none !important;
            }
            
            /* Para páginas de edição onde o botão pode estar oculto */
            .wp-admin .wrap > .page-title-action,
            .wp-admin #wpbody-content > .wrap > .page-title-action,
            .wp-admin body.post-php .page-title-action,
            .wp-admin body.post-new-php .page-title-action,
            .wp-admin body.term-php .page-title-action,
            .wp-admin body.edit-php .page-title-action {
                display: inline-block !important;
                visibility: visible !important;
            }
            
            /* Botões customizados e thickbox - universal */
            .wp-admin .button.button-primary,
            .wp-admin .thickbox.button,
            .wp-admin #botao-cadastro-cliente,
            .wp-admin .wrap .button,
            .wp-admin .wrap .button-primary,
            .wp-admin h1 + .button,
            .wp-admin h1 + .button-primary,
            .wp-admin .wp-header-end + .button,
            .wp-admin .wp-header-end + .button-primary {
                display: inline-block !important;
                visibility: visible !important;
                opacity: 1 !important;
                position: relative !important;
                top: auto !important;
                left: auto !important;
                right: auto !important;
                transform: none !important;
                z-index: auto !important;
                float: none !important;
                margin-left: 10px !important;
                margin-top: 0 !important;
            }
            
            /* Forms e metaboxes - padrão universal */
            .wp-admin .form-table,
            .wp-admin .postbox,
            .wp-admin .metabox-holder,
            .wp-admin .meta-box-sortables {
                width: auto !important;
                max-width: 100% !important;
                box-sizing: border-box !important;
            }
            
            /* Plugins com layouts próprios - reset defensivo */
            .wp-admin .plugin-content,
            .wp-admin .theme-browser,
            .wp-admin .plugin-install,
            .wp-admin .update-php {
                width: auto !important;
                max-width: 100% !important;
                margin-left: 0 !important;
                margin-right: 0 !important;
                box-sizing: border-box !important;
            }
            
            /* Containers genéricos de plugins */
            .wp-admin [class*="wrap"],
            .wp-admin [class*="container"],
            .wp-admin [class*="content"],
            .wp-admin [class*="main"] {
                max-width: 100% !important;
                box-sizing: border-box !important;
            }
            
            /* Notices e alerts - padrão universal */
            .wp-admin .notice,
            .wp-admin .error,
            .wp-admin .updated,
            .wp-admin .update-nag {
                margin-top: 15px !important;
                width: auto !important;
                max-width: 100% !important;
            }
        }
        
        /* Estilo específico APENAS para nossa página dashboard customizada mpa-dashboard */
        body.toplevel_page_mpa-dashboard #wpbody-content {
            padding-left: 40px !important;
            padding-right: 20px !important;
            padding-top: 50px !important;
        }
        
        
        /* Fixes específicos para plugins comuns */
        .woocommerce-layout__primary,
        .woocommerce-layout__main,
        .wc-admin-page,
        .woocommerce-reports-wide,
        .woocommerce-layout {
            width: auto !important;
            max-width: none !important;
            margin: 0 !important;
            float: none !important;
        }
        
        /* WooCommerce Header específico - fix para elemento quebrado */
        .woocommerce-layout__header,
        .woocommerce-layout__header-heading {
            position: relative !important;
            top: auto !important;
            margin-top: 20px !important;
            padding-top: 10px !important;
            z-index: auto !important;
        }
        
        /* Container do header do WooCommerce */
        .woocommerce-layout__header-wrapper {
            position: relative !important;
            top: auto !important;
            margin-top: 15px !important;
        }
        
        /* Títulos específicos do WooCommerce */
        h1.woocommerce-layout__header-heading,
        .components-text.woocommerce-layout__header-heading,
        h1[data-wp-component="Text"].woocommerce-layout__header-heading {
            position: relative !important;
            top: auto !important;
            left: auto !important;
            right: auto !important;
            transform: none !important;
            margin-top: 0 !important;
            padding-top: 0 !important;
            z-index: 1 !important;
            display: block !important;
        }
        
        /* Fix para botões e elementos adjacentes do WooCommerce */
        .woocommerce-layout__header-right,
        .woocommerce-layout__header-left-align {
            position: relative !important;
            top: auto !important;
            margin-top: 5px !important;
        }
        
        /* Container geral do WooCommerce */
        .woocommerce-layout__main {
            padding-top: 15px !important;
            margin-top: 0 !important;
        }
        
        /* Páginas específicas do WooCommerce que podem ter problemas */
        .wc-admin-page .components-card__header,
        .wc-admin-page .components-panel__header {
            margin-top: 10px !important;
        }
        
        /* Ocultar Activity Panel do WooCommerce por interferir na navegação */
        .woocommerce-layout__activity-panel-wrapper {
            display: none !important;
        }
        
        /* Ocultar embedded root que pode interferir */
        div#woocommerce-embedded-root {
            display: none !important;
        }
        
        /* Ocultar header do WooCommerce em páginas específicas como customers */
        .woocommerce-layout__header {
            display: none !important;
        }
        
        /* Ocultar coluna de SEO do Rank Math na lista de produtos */
        #rank_math_seo_details,
        .column-rank_math_seo_details {
            display: none !important;
        }
        
        
        
        /* Responsivo - Mobile First */
        @media screen and (max-width: 782px) {
            /* Reset completo para mobile */
            body.wp-admin {
                margin-top: 48px !important;
                padding-top: 0 !important;
            }
            
            #wpcontent {
                margin-left: 0 !important;
                margin-right: 0 !important;
                padding-left: 5px !important;
                padding-right: 5px !important;
                width: 100% !important;
                max-width: 100vw !important;
                box-sizing: border-box !important;
            }
            
            #wpbody {
                padding: 5px !important;
                margin: 0 !important;
                width: 100% !important;
                max-width: 100% !important;
                box-sizing: border-box !important;
            }
            
            #wpbody-content {
                width: 100% !important;
                max-width: 100% !important;
                float: none !important;
                margin: 0 !important;
                padding: 5px !important;
                box-sizing: border-box !important;
            }
            
            .wrap {
                width: 100% !important;
                max-width: 100% !important;
                margin: 15px 0 0 0 !important;
                padding: 10px 5px !important;
                box-sizing: border-box !important;
            }
            
            div#dashboard-widgets {
                width: 100% !important;
                max-width: 100% !important;
                float: none !important;
                margin: 15px 0 0 0 !important;
                padding: 5px !important;
                box-sizing: border-box !important;
            }
            
            /* Forçar override de todas as classes de sidebar */
            body.sidebar-collapsed #wpcontent,
            body.mobile-menu-open #wpcontent,
            #wpcontent {
                margin-left: 0 !important;
                margin-right: 0 !important;
            }
            
            /* Prevent horizontal scroll */
            html, body {
                overflow-x: hidden !important;
            }
            
            /* Tables mobile responsive */
            table.wp-list-table {
                width: 100% !important;
                max-width: 100% !important;
                table-layout: auto !important;
            }
            
            /* Títulos e botões no mobile */
            .wp-admin h1,
            .wp-admin .wp-heading-inline {
                margin-top: 10px !important;
                padding-top: 5px !important;
                font-size: 1.5rem !important;
                word-wrap: break-word !important;
                display: flex !important;
                align-items: center !important;
                gap: 8px !important;
                flex-wrap: wrap !important;
            }
            
            /* Botões "Adicionar" no mobile - universal */
            .wp-admin .page-title-action,
            .wp-admin .add-new-h2,
            .wp-admin h1 .page-title-action,
            .wp-admin .wrap > h1 > .page-title-action,
            .wp-admin .wrap h1.wp-heading-inline + .page-title-action,
            .wp-admin .wrap > .page-title-action,
            .wp-admin #wpbody-content > .wrap > .page-title-action {
                display: inline-block !important;
                visibility: visible !important;
                opacity: 1 !important;
                margin-left: 0 !important;
                margin-top: 5px !important;
                font-size: 0.875rem !important;
                padding: 0.375rem 0.75rem !important;
                position: relative !important;
                top: auto !important;
                left: auto !important;
                transform: none !important;
                z-index: auto !important;
                float: none !important;
            }
            
            /* Botões customizados no mobile - universal */
            .wp-admin .button.button-primary,
            .wp-admin .thickbox.button,
            .wp-admin #botao-cadastro-cliente,
            .wp-admin .wrap .button,
            .wp-admin .wrap .button-primary,
            .wp-admin h1 + .button,
            .wp-admin h1 + .button-primary {
                display: inline-block !important;
                visibility: visible !important;
                opacity: 1 !important;
                position: relative !important;
                top: auto !important;
                left: auto !important;
                right: auto !important;
                transform: none !important;
                z-index: auto !important;
                float: none !important;
                margin-left: 8px !important;
                margin-top: 5px !important;
                font-size: 0.875rem !important;
                padding: 0.375rem 0.75rem !important;
            }
            
            /* Forms e inputs responsivos */
            .wp-admin input[type="text"],
            .wp-admin input[type="email"],
            .wp-admin textarea,
            .wp-admin select {
                width: 100% !important;
                max-width: 100% !important;
                box-sizing: border-box !important;
            }
        }
    </style>
    <?php
}

// JavaScript removido - usando apenas CSS para melhor performance

// Renderizar dashboard customizado se necessário
add_action('all_admin_notices', function () {
    $screen = get_current_screen();
    if ($screen && $screen->base === 'toplevel_page_mpa-dashboard') {
        mpa_render_dashboard_content();
    }
});

function mpa_render_dashboard_content() {
    ?>
    <div class="mpa-main-content">
        <!-- Visitor Analytics Section -->
        <section>
            <h2 class="mpa-section-title">Visitor Analytics</h2>
            <div class="mpa-card-grid mpa-card-grid-4">
                <!-- Users Card -->
                <div class="mpa-card">
                    <div class="mpa-metric-header">
                        <h3 class="mpa-metric-title">Usuários do Site</h3>
                        <span class="mpa-metric-change positive">+12.5%</span>
                    </div>
                    <div class="mpa-metric-value">24,532</div>
                    <p class="mpa-metric-subtitle">vs mês anterior</p>
                </div>
                
                <!-- Page Views Card -->
                <div class="mpa-card">
                    <div class="mpa-metric-header">
                        <h3 class="mpa-metric-title">Visualizações</h3>
                        <span class="mpa-metric-change negative">-3.2%</span>
                    </div>
                    <div class="mpa-metric-value">89,247</div>
                    <p class="mpa-metric-subtitle">vs mês anterior</p>
                </div>
                
                <!-- Bounce Rate Card -->
                <div class="mpa-card">
                    <div class="mpa-metric-header">
                        <h3 class="mpa-metric-title">Taxa de Rejeição</h3>
                        <span class="mpa-metric-change positive">-5.1%</span>
                    </div>
                    <div class="mpa-metric-value">42.3%</div>
                    <p class="mpa-metric-subtitle">vs mês anterior</p>
                </div>
                
                <!-- Session Duration Card -->
                <div class="mpa-card">
                    <div class="mpa-metric-header">
                        <h3 class="mpa-metric-title">Duração Média</h3>
                        <span class="mpa-metric-change positive">+8.7%</span>
                    </div>
                    <div class="mpa-metric-value">3:42</div>
                    <p class="mpa-metric-subtitle">minutos por sessão</p>
                </div>
            </div>
        </section>
        
        <!-- Real Time Section -->
        <section>
            <h2 class="mpa-section-title">Real Time</h2>
            <div class="mpa-card-grid mpa-card-grid-real-time">
                <div class="mpa-card">
                    <h3 class="mpa-metric-title">Usuários Ativos</h3>
                    <div class="mpa-metric-value">127</div>
                    <p class="mpa-metric-subtitle">agora mesmo</p>
                </div>
                
                <div class="mpa-card">
                    <h3 class="mpa-metric-title">Páginas Ativas</h3>
                    <div class="mpa-metric-value">43</div>
                    <p class="mpa-metric-subtitle">sendo visualizadas</p>
                </div>
                
                <div class="mpa-card">
                    <h3 class="mpa-metric-title">Conversões (30min)</h3>
                    <div class="mpa-metric-value" style="color: #ef4444">0</div>
                    <p class="mpa-metric-subtitle">últimos 30 minutos</p>
                </div>
            </div>
        </section>
    </div>
    <?php
}