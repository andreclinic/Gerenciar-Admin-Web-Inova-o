<?php
/**
 * Simple diagnostic script to check custom menu data
 */

// WordPress environment
$wp_root = dirname(dirname(dirname(__DIR__)));
require_once($wp_root . '/wp-config.php');
require_once($wp_root . '/wp-includes/wp-db.php');
require_once($wp_root . '/wp-includes/functions.php');

echo "=== DEBUG: Custom Menu Data ===\n";

// Check if custom menus option exists
$custom_menus = get_option('mpa_custom_menus', array());
echo "Custom menus data:\n";
print_r($custom_menus);
echo "\n";

// Check current user data (simulate admin environment)
if (function_exists('wp_get_current_user')) {
    $user = wp_get_current_user();
    echo "Current user: " . ($user ? $user->user_login : 'Not logged in') . "\n";
    echo "User roles: " . (isset($user->roles) ? implode(', ', $user->roles) : 'None') . "\n";
} else {
    echo "wp_get_current_user function not available\n";
}

echo "\n=== End Debug ===\n";
?>