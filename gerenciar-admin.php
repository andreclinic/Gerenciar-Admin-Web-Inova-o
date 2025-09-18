<?php
/*
Plugin Name: Gerenciar Admin Web Inovação
Description: Gerenciar e estilizar admin do Wordpress.
Version: 1.2
Author: Web Inovação
*/

// Definir caminhos do plugin
define('ADMIN_BAR_MENU_PATH', plugin_dir_path(__FILE__));
define('ADMIN_BAR_MENU_URL', plugin_dir_url(__FILE__));

// Carregar módulos do plugin
include_once ADMIN_BAR_MENU_PATH . '/admin/mpa-admin.php';
include_once ADMIN_BAR_MENU_PATH . '/admin/mpa-adminmenumain.php';
include_once ADMIN_BAR_MENU_PATH . '/admin/mpa-wpadminbar.php';
include_once ADMIN_BAR_MENU_PATH . '/admin/mpa-wpbody.php';
include_once ADMIN_BAR_MENU_PATH . '/admin/mpa-wpcontent.php';
include_once ADMIN_BAR_MENU_PATH . '/admin/mpa-wpfooter.php';
include_once ADMIN_BAR_MENU_PATH . '/admin/mpa-menu-manager.php';
include_once ADMIN_BAR_MENU_PATH . '/admin/class-mpa-analytics-page.php';
include_once ADMIN_BAR_MENU_PATH . '/includes/class-mpa-analytics-client.php';
include_once ADMIN_BAR_MENU_PATH . '/admin/mpa-custom-login.php';
include_once ADMIN_BAR_MENU_PATH . '/admin/mpa-hide-updates.php';



// Controlar exibição da admin bar no frontend apenas para administradores
add_action('after_setup_theme', 'mpa_control_frontend_admin_bar');

// Filtro adicional para controlar a admin bar
add_filter('show_admin_bar', 'mpa_show_admin_bar_filter');

function mpa_control_frontend_admin_bar() {
    // Aplicar apenas no frontend, nunca no admin
    if (!is_admin() && !wp_doing_ajax() && !defined('REST_REQUEST')) {
        $current_user = wp_get_current_user();

        // Verificar se usuário está logado
        if (!$current_user || empty($current_user->roles)) {
            return;
        }

        $user_roles = (array) $current_user->roles;

        // Se não for administrador, esconder admin bar
        if (!in_array('administrator', $user_roles)) {
            show_admin_bar(false);

            // Adicionar CSS para garantir que a admin bar não apareça
            add_action('wp_head', function() {
                echo '<style>
                    #wpadminbar { display: none !important; }
                    html { margin-top: 0 !important; }
                    body { margin-top: 0 !important; }
                    .admin-bar body { margin-top: 0 !important; }
                </style>';
            });
        }
    }
}

// Função do filtro para controlar admin bar
function mpa_show_admin_bar_filter($show_admin_bar) {
    // Aplicar apenas no frontend, nunca no admin
    if (!is_admin() && !wp_doing_ajax() && !defined('REST_REQUEST')) {
        $current_user = wp_get_current_user();

        // Verificar se usuário está logado
        if (!$current_user || empty($current_user->roles)) {
            return $show_admin_bar;
        }

        $user_roles = (array) $current_user->roles;

        // Mostrar admin bar apenas para administradores no frontend
        if (!in_array('administrator', $user_roles)) {
            return false;
        }
    }

    return $show_admin_bar;
}

// Redirecionamento para dashboard analytics
add_action('admin_init', 'mpa_redirect_to_analytics');

function mpa_redirect_to_analytics()
{
    global $pagenow;

    // Verificar se o usuário está na página principal do admin (index.php)
    if ($pagenow == 'index.php' && !isset($_GET['page'])) {
        // Verificar se o usuário tem capacidades administrativas (exclui roles como subscriber, customer)
        if (current_user_can('edit_posts') || current_user_can('manage_options')) {
            // Verificar se não é uma requisição AJAX ou REST
            if (!defined('DOING_AJAX') || !DOING_AJAX) {
                if (!defined('REST_REQUEST') || !REST_REQUEST) {
                    // Redirecionar para mpa-analytics (Dashboard Analytics)
                    wp_redirect(admin_url('admin.php?page=mpa-analytics'));
                    exit;
                }
            }
        }
    }
}

