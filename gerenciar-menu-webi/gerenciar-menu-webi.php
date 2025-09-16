<?php
/**
 * Plugin Name: Gerenciar Menu Webi
 * Plugin URI: https://webi.dev.br/
 * Description: Gerencie o menu administrativo do WordPress: renomear, mover, remover, promover/demover e reordenar menus e submenus — agora com configurações por role e pré-visualização por role.
 * Version: 1.2.0
 * Author: Webi
 * Author URI: https://webi.dev.br/
 * License: GPL2 or later
 * Text Domain: gerenciar-menu-webi
 * Domain Path: /languages
 */

defined('ABSPATH') || exit;

define('GMW_PATH', plugin_dir_path(__FILE__));
define('GMW_URL', plugin_dir_url(__FILE__));
define('GMW_VERSION', '1.2.0');

require_once GMW_PATH . 'includes/menu-functions.php';   // lógica de aplicação + helpers
require_once GMW_PATH . 'admin/settings-page.php';       // interface + handlers