<?php
/**
 * MPA Hide Updates - Esconder notificações de updates
 *
 * Esconde notificações de updates para roles não-administrador
 * mantendo todas as capabilities intactas
 *
 * @package Gerenciar_Admin
 * @since 1.0.0
 */

// Evitar acesso direto
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Esconder notificações de updates para roles não-administrador
 * Aplica apenas efeitos visuais, sem alterar capabilities
 */
function mpa_hide_update_notifications() {
    // Verificar se é administrador - se for, não aplicar nenhuma restrição
    $current_user = wp_get_current_user();
    $user_roles = (array) $current_user->roles;

    if (in_array('administrator', $user_roles)) {
        return;
    }

    // 1. Remover actions que mostram notificações de update (quando possível)
    remove_action('admin_notices', 'update_nag', 3);
    remove_action('network_admin_notices', 'update_nag', 3);
    remove_action('admin_notices', 'maintenance_nag', 10);

    // 2. Remover actions de core updates
    remove_action('admin_notices', 'wp_update_available_notice');
    remove_action('network_admin_notices', 'wp_update_available_notice');

    // 3. Filtros para esconder contadores de updates
    add_filter('wp_get_update_data', '__return_empty_array');

    // 4. Remover notificações do WooCommerce e outros plugins
    remove_action('admin_notices', 'woocommerce_admin_notices');
    remove_action('admin_notices', 'wc_admin_notices');

    // 5. Filtros para esconder notificações promocionais
    add_filter('woocommerce_admin_onboarding_themes_filter', '__return_empty_array');
    add_filter('woocommerce_marketing_notifications', '__return_empty_array');

    // 6. CSS para esconder elementos visuais de updates
    add_action('admin_head', 'mpa_hide_update_elements_css');
}

/**
 * CSS para esconder visualmente elementos de updates
 */
function mpa_hide_update_elements_css() {
    // Verificar se é administrador - se for, não aplicar CSS
    $current_user = wp_get_current_user();
    $user_roles = (array) $current_user->roles;

    if (in_array('administrator', $user_roles)) {
        return;
    }
    ?>
    <style>
        /* Esconder notificações de updates */
        .update-nag,
        .updated.notice[data-dismissible],
        .notice.notice-warning[data-dismissible],
        .wrap .notice.notice-warning,
        .wrap .update-nag,

        /* Esconder badges de update no menu */
        .update-plugins,
        .plugin-update-tr,
        .theme-update-tr,
        .update-count,
        .awaiting-mod,

        /* Esconder contador na admin bar */
        #wp-admin-bar-updates .ab-label,
        #wp-admin-bar-updates,

        /* Esconder avisos específicos do WordPress */
        .notice[class*="update"],
        .error[class*="update"],

        /* Esconder seção de updates no dashboard */
        #dashboard_right_now .wordpress-update-nag,
        #wp-version-message,

        /* Esconder updates na lista de plugins */
        .plugins tr[data-plugin*="update"],
        .tablenav .subsubsub .update,

        /* Esconder updates na lista de temas */
        .themes .theme-update,
        .theme-browser .theme .update-message,

        /* Esconder botões de update automático */
        .column-auto_updates,
        .auto-update-status,

        /* Esconder notificações de plugins e promocionais */
        .notice.notice-info,
        .notice.notice-success,
        .notice[data-dismissible="forever"],
        div[class*="woocommerce-message"],
        div[class*="wc-admin-"],

        /* Esconder notificações específicas do WooCommerce */
        .woocommerce-admin-notice,
        .wc-admin-notice,
        .woocommerce-message,

        /* Esconder notificações promocionais gerais */
        .notice[style*="border-left-color"],
        .postbox .notice,

        /* Esconder notificações de terceiros com ícones */
        .notice img[src*="icon"],
        .notice .dashicons,

        /* Esconder notificações com botões de ação */
        .notice .button-primary,
        .notice .button-secondary,
        .notice:has(.button)
        {
            display: none !important;
        }

        /* Esconder colunas de auto-update na tabela de plugins */
        .wp-list-table .column-auto-updates {
            display: none !important;
        }

        /* Esconder mensagens de update disponível */
        .wrap h1 + .notice,
        .wrap .notice.is-dismissible,

        /* Esconder todas as notificações na área principal do admin */
        #wpbody-content .notice,
        #wpbody-content .updated,
        #wpbody-content .error,

        /* Esconder notificações do tipo "gift" ou promocionais */
        .notice:has([style*="purple"]),
        .notice:has(.dashicons-megaphone),
        .notice:has(.dashicons-cart),

        /* Esconder notificações do WooCommerce por classe específica */
        .woocommerce-layout__notice,
        .wc-admin-notice,

        /* Esconder notificações com bordas coloridas específicas */
        .notice[style*="border-left"],
        div[style*="border-left-color:#7b68ee"],
        div[style*="border-left-color:#8b5cf6"],
        div[style*="border-left-color: rgb(123"],

        /* Esconder notificações por conteúdo visual */
        .notice img,
        .notice .woocommerce-admin-notice,

        /* Esconder notificações genéricas com botões */
        .notice .button-primary,
        .notice .button-secondary,

        /* Esconder divs com classes de notificação específicas */
        .woocommerce-admin-full-screen,
        .wc-admin-page
        {
            display: none !important;
        }
    </style>
    <?php
}

/**
 * Remover menu de updates do submenu para não-administradores
 */
function mpa_remove_update_submenu() {
    // Verificar se é administrador - se for, não remover
    $current_user = wp_get_current_user();
    $user_roles = (array) $current_user->roles;

    if (in_array('administrator', $user_roles)) {
        return;
    }

    // Remover submenus relacionados a updates
    remove_submenu_page('index.php', 'update-core.php');
    remove_submenu_page('plugins.php', 'plugin-install.php');
    remove_submenu_page('themes.php', 'theme-install.php');
}

/**
 * Filtro para remover dados de update do dashboard
 */
function mpa_filter_dashboard_update_data($wp_get_update_data) {
    // Verificar se é administrador - se for, não filtrar
    $current_user = wp_get_current_user();
    $user_roles = (array) $current_user->roles;

    if (in_array('administrator', $user_roles)) {
        return $wp_get_update_data;
    }

    // Retornar array vazio para esconder contadores
    return array(
        'counts' => array(
            'plugins' => 0,
            'themes' => 0,
            'wordpress' => 0,
            'translations' => 0,
            'total' => 0
        ),
        'title' => ''
    );
}

/**
 * Esconder admin bar updates
 */
function mpa_hide_admin_bar_updates() {
    // Verificar se é administrador - se for, não esconder
    $current_user = wp_get_current_user();
    $user_roles = (array) $current_user->roles;

    if (in_array('administrator', $user_roles)) {
        return;
    }

    global $wp_admin_bar;
    $wp_admin_bar->remove_menu('updates');
}

/**
 * Filtro para esconder version footer para não-administradores
 */
function mpa_hide_wp_version_footer($footer_text) {
    // Verificar se é administrador - se for, mostrar versão normal
    $current_user = wp_get_current_user();
    $user_roles = (array) $current_user->roles;

    if (in_array('administrator', $user_roles)) {
        return $footer_text;
    }

    // Para não-administradores, mostrar texto genérico sem versão
    return 'WordPress';
}

/**
 * Inicializar sistema de esconder updates
 */
function mpa_init_hide_updates() {
    // Aplicar apenas no admin
    if (!is_admin()) {
        return;
    }

    // Hooks principais
    add_action('admin_init', 'mpa_hide_update_notifications');
    add_action('admin_menu', 'mpa_remove_update_submenu', 999);
    add_action('wp_before_admin_bar_render', 'mpa_hide_admin_bar_updates');

    // Filtros
    add_filter('wp_get_update_data', 'mpa_filter_dashboard_update_data');
    add_filter('update_footer', 'mpa_hide_wp_version_footer', 9999);
}

// Inicializar sistema
add_action('init', 'mpa_init_hide_updates');

/**
 * Função para debug - mostrar quais roles têm acesso às notificações
 */
function mpa_debug_update_visibility() {
    $current_user = wp_get_current_user();
    $user_roles = (array) $current_user->roles;

    if (!isset($_GET['debug_updates']) || !in_array('administrator', $user_roles)) {
        return;
    }

    echo '<div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin: 10px;">';
    echo '<h3>🔍 Debug: Visibilidade de Updates</h3>';

    $user = wp_get_current_user();
    echo '<p><strong>Usuário atual:</strong> ' . $user->user_login . '</p>';
    echo '<p><strong>Roles:</strong> ' . implode(', ', $user->roles) . '</p>';
    echo '<p><strong>É administrador:</strong> ' . (in_array('administrator', $user->roles) ? 'SIM' : 'NÃO') . '</p>';
    echo '<p><strong>Vê updates:</strong> ' . (in_array('administrator', $user->roles) ? 'SIM' : 'NÃO') . '</p>';

    echo '</div>';
}
add_action('admin_notices', 'mpa_debug_update_visibility');