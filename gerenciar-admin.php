<?php
/*
Plugin Name: Gerenciar Admin Web Inovação
Description: Gerenciar e estilizar admin do Wordpress.
Version: 1.1
Author: Web Inovação
*/

// Log de carregamento do plugin
error_log('[MPA NOVO DEBUG] Plugin gerenciar-admin carregando...');

// Caminho absoluto até a pasta do plugin
define('ADMIN_BAR_MENU_PATH', plugin_dir_path(__FILE__));
define('ADMIN_BAR_MENU_URL', plugin_dir_url(__FILE__));


// Carregar todos os módulos (voltando ao método simples para corrigir problemas de permissão)
include_once ADMIN_BAR_MENU_PATH . '/admin/mpa-admin.php';
include_once ADMIN_BAR_MENU_PATH . '/admin/mpa-adminmenumain.php';
include_once ADMIN_BAR_MENU_PATH . '/admin/mpa-wpadminbar.php';
include_once ADMIN_BAR_MENU_PATH . '/admin/mpa-wpbody.php';
include_once ADMIN_BAR_MENU_PATH . '/admin/mpa-wpcontent.php';
include_once ADMIN_BAR_MENU_PATH . '/admin/mpa-wpfooter.php';
include_once ADMIN_BAR_MENU_PATH . '/admin/mpa-menu-manager.php';
include_once ADMIN_BAR_MENU_PATH . '/admin/mpa-capability-manager.php';
include_once ADMIN_BAR_MENU_PATH . '/admin/class-mpa-analytics-page.php';
include_once ADMIN_BAR_MENU_PATH . '/includes/class-mpa-analytics-client.php';
include_once ADMIN_BAR_MENU_PATH . '/debug-menu-slugs.php'; // Debug ativo temporariamente
include_once ADMIN_BAR_MENU_PATH . '/admin/debugar.php';
include_once ADMIN_BAR_MENU_PATH . '/debug-permissions.php'; // Debug de permissões
include_once ADMIN_BAR_MENU_PATH . '/admin/mpa-custom-login.php'; // Tela de login personalizada


// Adicionar debug temporário para verificar o que está acontecendo
add_action('admin_notices', function () {
    if (!current_user_can('manage_options') && isset($_GET['debug_menu_issue'])) {
        $user = wp_get_current_user();
        $menu_permissions = get_option('mpa_menu_permissions', array());

        echo '<div style="background: yellow; border: 2px solid red; padding: 15px; margin: 10px;">';
        echo '<h3>Debug Menu Issue</h3>';
        echo '<p><strong>Usuário:</strong> ' . $user->user_login . '</p>';
        echo '<p><strong>Roles:</strong> ' . implode(', ', $user->roles) . '</p>';
        echo '<p><strong>Configurações salvas:</strong></p>';
        echo '<pre>' . print_r($menu_permissions, true) . '</pre>';

        // Verificar se o menu Posts deveria estar bloqueado
        foreach ($user->roles as $role) {
            if (isset($menu_permissions[$role]['edit.php'])) {
                echo '<p><strong>Posts (edit.php) para role ' . $role . ':</strong> ';
                echo $menu_permissions[$role]['edit.php'] ? 'PERMITIDO' : 'BLOQUEADO';
                echo '</p>';
            }
        }

        echo '</div>';
    }
});

// Redirecionar para mpa-analytics para usuários com permissões administrativas
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