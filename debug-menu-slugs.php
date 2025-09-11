<?php
// Arquivo temporário para debug dos slugs de menu
// Adicione este código temporariamente no final do mpa-menu-manager.php para debug

add_action('admin_footer', 'mpa_debug_menu_slugs');

function mpa_debug_menu_slugs() {
    if (!current_user_can('manage_options')) return;
    if (!isset($_GET['debug_menus'])) return;
    
    global $menu, $submenu;
    
    echo '<div style="background: white; border: 2px solid red; padding: 20px; margin: 20px; position: fixed; top: 50px; right: 50px; z-index: 9999; max-width: 400px; max-height: 500px; overflow: auto;">';
    echo '<h3>Debug - Menu Slugs</h3>';
    
    if (is_array($menu)) {
        echo '<h4>Menus Principais:</h4>';
        foreach ($menu as $menu_item) {
            if (empty($menu_item[0]) || $menu_item[0] === '') continue;
            echo '<div><strong>' . wp_strip_all_tags($menu_item[0]) . '</strong>: ' . $menu_item[2] . '</div>';
        }
    }
    
    if (is_array($submenu)) {
        echo '<h4>Submenus:</h4>';
        foreach ($submenu as $parent_slug => $submenu_items) {
            echo '<div><strong>Parent: ' . $parent_slug . '</strong></div>';
            foreach ($submenu_items as $submenu_item) {
                if (empty($submenu_item[0])) continue;
                echo '<div style="margin-left: 20px;">↳ ' . wp_strip_all_tags($submenu_item[0]) . ': ' . $submenu_item[2] . '</div>';
            }
        }
    }
    
    // Debug das configurações salvas
    $menu_permissions = get_option('mpa_menu_permissions', array());
    echo '<h4>Configurações Salvas:</h4>';
    echo '<pre>' . print_r($menu_permissions, true) . '</pre>';
    
    echo '<button onclick="this.parentElement.style.display=\'none\'">Fechar</button>';
    echo '</div>';
}