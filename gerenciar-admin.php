<?php
/*
Plugin Name: Gerenciar Admin Web Inovação
Description: Gerenciar e estilizar admin do Wordpress.
Version: 1.1
Author: Web Inovação
*/

// Caminho absoluto até a pasta do plugin
define('ADMIN_BAR_MENU_PATH', plugin_dir_path(__FILE__));
define('ADMIN_BAR_MENU_URL', plugin_dir_url(__FILE__));
// echo '<!-- ' . ADMIN_BAR_MENU_PATH . '/admin/mpa-admin.css';
include_once ADMIN_BAR_MENU_PATH . '/admin/mpa-admin.php';
include_once ADMIN_BAR_MENU_PATH . '/admin/mpa-adminmenumain.php';
include_once ADMIN_BAR_MENU_PATH . '/admin/mpa-wpadminbar.php';
include_once ADMIN_BAR_MENU_PATH . '/admin/mpa-wpbody.php';
include_once ADMIN_BAR_MENU_PATH . '/admin/mpa-wpcontent.php';
include_once ADMIN_BAR_MENU_PATH . '/admin/mpa-wpfooter.php';