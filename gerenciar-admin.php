<?php
/*
Plugin Name: Gerenciar Admin Web Inovação
Description: Gerenciar e estilizar admin do Wordpress.
Version: 1.1
Author: Web Inovação
*/

// Plugin carregamento

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
include_once ADMIN_BAR_MENU_PATH . '/admin/mpa-custom-login.php'; // Tela de login personalizada
include_once ADMIN_BAR_MENU_PATH . '/admin/mpa-hide-updates.php'; // Sistema para esconder updates



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

################################################
################################################
################################################
################################################
################################################
################################################
################################################
################################################
################################################
################################################
################################################
################################################
################################################
################################################
################################################



################################################



// add_action('admin_menu', function () {
//     mover_menu_item('index.php', 3); // Exemplo: move "Páginas" para posição 3
// }, 1); // prioridade alta

// function mover_menu_item($menu_slug, $nova_posicao)
// {
//     global $menu;

//     // Encontra o menu pelo slug
//     foreach ($menu as $index => $item) {
//         if (isset($item[2]) && $item[2] === $menu_slug) {
//             // Salva os dados originais
//             $titulo = $item[0];
//             $capability = $item[1];
//             $icone = isset($item[6]) ? $item[6] : '';
//             $callback = ''; // sem callback (para menus nativos do WP)

//             // Remove o menu original
//             remove_menu_page($menu_slug);

//             // Adiciona novamente com a nova posição
//             add_menu_page(
//                 $titulo,
//                 $titulo,
//                 $capability,
//                 $menu_slug,
//                 $callback,
//                 $icone,
//                 $nova_posicao
//             );

//             break;
//         }
//     }
// }


// add_action('admin_menu', function () {
//     global $menu, $submenu;

//     echo '<pre>';
//     print_r($menu);     // Menu principal
//     print_r('SUBMENUS<br>');     // Menu principal
//     print_r('SUBMENUS<br>');     // Menu principal
//     print_r('SUBMENUS<br>');     // Menu principal
//     print_r('SUBMENUS<br>');     // Menu principal
//     print_r($submenu);  // Submenus
//     echo '</pre>';
// });