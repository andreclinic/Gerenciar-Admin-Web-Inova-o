<?php
/**
 * Plugin Name: MPA Menu Permissions Tools
 * Description: Exporta e importa as configurações de 'mpa_menu_permissions' via JSON.
 * Version: 1.0
 * Author: Seu Nome
 */

if (!defined('ABSPATH'))
    exit;

// Adiciona página no admin
add_action('admin_menu', function () {
    add_menu_page(
        'MPA Menu Permissions',
        'MPA Permissions',
        'manage_options',
        'mpa-permissions-tools',
        'mpa_permissions_tools_page_html',
        'dashicons-download',
        80
    );
});

// HTML da página do plugin
function mpa_permissions_tools_page_html()
{
    ?>
<div class="wrap">
    <h1>Exportar / Importar Permissões do Menu</h1>

    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
        <input type="hidden" name="action" value="mpa_export_settings">
        <?php submit_button('Exportar configurações para JSON', 'primary'); ?>
    </form>

    <hr>

    <form method="post" enctype="multipart/form-data" action="<?php echo admin_url('admin-post.php'); ?>">
        <input type="hidden" name="action" value="mpa_import_settings">
        <input type="file" name="mpa_import_file" accept=".json" required />
        <?php submit_button('Importar configurações do JSON', 'secondary'); ?>
    </form>
</div>
<?php
}

/////////////////////
// EXPORTAÇÃO
/////////////////////

add_action('admin_post_mpa_export_settings', 'mpa_export_settings_callback');

function mpa_export_settings_callback()
{
    if (!current_user_can('manage_options')) {
        wp_die('Sem permissões suficientes.');
    }

    $options = get_option('mpa_menu_permissions', array());
    $json = json_encode($options, JSON_PRETTY_PRINT);

    // Nome do arquivo com data
    $filename = 'mpa_menu_permissions_' . date('Y-m-d') . '.json';

    // Headers para forçar download
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . strlen($json));

    ob_clean();
    flush();
    echo $json;
    exit;
}

/////////////////////
// IMPORTAÇÃO
/////////////////////

add_action('admin_post_mpa_import_settings', 'mpa_import_settings_callback');

function mpa_import_settings_callback()
{
    if (!current_user_can('manage_options')) {
        wp_die('Sem permissões suficientes.');
    }

    if (!empty($_FILES['mpa_import_file']['tmp_name'])) {
        $file_content = file_get_contents($_FILES['mpa_import_file']['tmp_name']);
        $data = json_decode($file_content, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
            update_option('mpa_menu_permissions', $data);
            $redirect = add_query_arg('mpa_status', 'success', admin_url('admin.php?page=mpa-permissions-tools'));
        } else {
            $redirect = add_query_arg('mpa_status', 'invalid_json', admin_url('admin.php?page=mpa-permissions-tools'));
        }
    } else {
        $redirect = add_query_arg('mpa_status', 'no_file', admin_url('admin.php?page=mpa-permissions-tools'));
    }

    wp_redirect($redirect);
    exit;
}

/////////////////////
// MENSAGENS DE FEEDBACK
/////////////////////

add_action('admin_notices', function () {
    if (!isset($_GET['page']) || $_GET['page'] !== 'mpa-permissions-tools')
        return;

    if (isset($_GET['mpa_status'])) {
        switch ($_GET['mpa_status']) {
            case 'success':
                echo '<div class="notice notice-success is-dismissible"><p>Configurações importadas com sucesso!</p></div>';
                break;
            case 'invalid_json':
                echo '<div class="notice notice-error is-dismissible"><p>Arquivo JSON inválido.</p></div>';
                break;
            case 'no_file':
                echo '<div class="notice notice-warning is-dismissible"><p>Nenhum arquivo foi enviado.</p></div>';
                break;
        }
    }
});



// ############################################################################################################################################



// Adiciona submenu para gerenciamento de capabilities
add_action('admin_menu', function () {
    add_submenu_page(
        'mpa-permissions-tools',
        'Gerenciar Capabilities',
        'Gerenciar Capabilities',
        'manage_options',
        'mpa-manage-capabilities',
        'mpa_manage_capabilities_page'
    );
});

// Página principal com abas por grupo
function mpa_manage_capabilities_page()
{
    if (!current_user_can('manage_options')) {
        wp_die('Sem permissão.');
    }

    $roles = get_editable_roles();
    $selected_role = $_GET['role'] ?? key($roles);
    $role_object = get_role($selected_role);

    // Coletar capabilities
    $all_capabilities = [];
    foreach ($roles as $role_data) {
        if (!empty($role_data['capabilities'])) {
            $all_capabilities = array_merge($all_capabilities, array_keys($role_data['capabilities']));
        }
    }

    $all_capabilities = array_unique($all_capabilities);
    sort($all_capabilities);

    // Agrupar por categorias
    // Força a exibição dessas abas mesmo que não tenham capabilities ainda
    $grouped_caps = [
        'Edição' => [],
        'Exclusão' => [],
        'Visibilidade' => [],
        'Taxonomias' => [],
        'Mídia' => [],
        'Comentários' => [],
        'Usuários' => [],
        'Administrador' => [],
        'Temas' => [],
        'Plugins' => [],
        'PublishPress Capabilities' => [],
        'WooCommerce' => [],
        'Elementor' => [],
        'Crocoblock / JetPlugins' => [],
        'CookieYes' => [],
        'Rank Math SEO' => [],
        'Wordfence Security' => [],
        'Loco Translate' => [],
        'WordPress Core' => [],
        'Outros' => [],
    ];

    foreach ($all_capabilities as $cap) {
        $prefix = 'Outros';

        if (str_starts_with($cap, 'edit_') || str_starts_with($cap, 'publish_')) {
            $prefix = 'Edição';
        } elseif (str_starts_with($cap, 'delete_')) {
            $prefix = 'Exclusão';
        } elseif (
            str_starts_with($cap, 'read_') || in_array($cap, ['read', 'list_users', 'moderate_comments'])
        ) {
            $prefix = 'Visibilidade';
        } elseif (
            str_contains($cap, 'term') || str_contains($cap, 'category') || str_contains($cap, 'taxonomy')
        ) {
            $prefix = 'Taxonomias';
        } elseif (
            str_contains($cap, 'upload') || str_contains($cap, 'media') || str_contains($cap, 'file')
        ) {
            $prefix = 'Mídia';
        } elseif (str_contains($cap, 'comment')) {
            $prefix = 'Comentários';
        } elseif (str_contains($cap, 'user')) {
            $prefix = 'Usuários';
        } elseif (
            in_array($cap, ['manage_options', 'unfiltered_html', 'edit_dashboard']) ||
            str_contains($cap, 'capabilities')
        ) {
            $prefix = 'Administrador';
        } elseif (str_contains($cap, 'theme')) {
            $prefix = 'Temas';
        } elseif (str_contains($cap, 'plugin')) {
            $prefix = 'Plugins';
        } elseif (str_starts_with($cap, 'publishpress_')) {
            $prefix = 'PublishPress Capabilities';
        } elseif (
            str_starts_with($cap, 'woocommerce_') ||
            str_contains($cap, 'shop') || str_contains($cap, 'order') ||
            str_contains($cap, 'product') || str_contains($cap, 'customer') || str_contains($cap, 'coupon')
        ) {
            $prefix = 'WooCommerce';
        } elseif (str_contains($cap, 'elementor')) {
            $prefix = 'Elementor';
        } elseif (
            str_starts_with($cap, 'jet_') || str_starts_with($cap, 'jetengine_') || str_starts_with($cap, 'jetwoo_') ||
            str_starts_with($cap, 'jetsmartfilters_') || str_starts_with($cap, 'jetform_') ||
            str_starts_with($cap, 'jetappointment_') || str_starts_with($cap, 'jetbooking_') ||
            str_starts_with($cap, 'jetmenu_') || str_starts_with($cap, 'jetpopup_') ||
            str_starts_with($cap, 'jetblog_') || str_starts_with($cap, 'jettricks_')
        ) {
            $prefix = 'Crocoblock / JetPlugins';
        } elseif (
            str_starts_with($cap, 'cookieyes_') || str_starts_with($cap, 'cookie_') || str_contains($cap, 'cookie')
        ) {
            $prefix = 'CookieYes';
        } elseif (str_starts_with($cap, 'rank_math_')) {
            $prefix = 'Rank Math SEO';
        } elseif (str_starts_with($cap, 'wordfence')) {
            $prefix = 'Wordfence Security';
        } elseif (str_starts_with($cap, 'loco')) {
            $prefix = 'Loco Translate';
        } elseif (
            str_starts_with($cap, 'manage_') || str_starts_with($cap, 'update_')
        ) {
            $prefix = 'WordPress Core';
        }

        $grouped_caps[$prefix][] = $cap;
    }

    ksort($grouped_caps);
    $current_caps = $role_object ? $role_object->capabilities : [];
    ?>

<div class="wrap">
    <h1>Gerenciar Capabilities por Função</h1>

    <form method="get" style="margin-bottom: 20px;">
        <input type="hidden" name="page" value="mpa-manage-capabilities">
        <label for="role">Selecione uma função:</label>
        <select name="role" id="role" onchange="this.form.submit()">
            <?php foreach ($roles as $role_key => $role_info): ?>
            <option value="<?php echo esc_attr($role_key); ?>" <?php selected($selected_role, $role_key); ?>>
                <?php echo esc_html($role_info['name']); ?>
            </option>
            <?php endforeach; ?>
        </select>
    </form>

    <div class="mpa-tabs-wrapper">
        <div class="mpa-tabs-sidebar">
            <?php foreach (array_keys($grouped_caps) as $index => $group): ?>
            <button type="button" class="mpa-tab-btn<?php echo $index === 0 ? ' active' : ''; ?>"
                data-target="tab-<?php echo esc_attr(sanitize_title($group)); ?>">
                <?php echo esc_html($group); ?>
            </button>
            <?php endforeach; ?>
        </div>

        <div class="mpa-tabs-content">
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <input type="hidden" name="action" value="mpa_save_role_capabilities">
                <input type="hidden" name="role" value="<?php echo esc_attr($selected_role); ?>">

                <?php foreach ($grouped_caps as $group => $caps_in_group): ?>
                <div class="mpa-tab-content" id="tab-<?php echo esc_attr(sanitize_title($group)); ?>">
                    <h2><?php echo esc_html($group); ?></h2>
                    <table class="widefat fixed striped">
                        <thead>
                            <tr>
                                <th style="width: 30px;"></th>
                                <th>Capability</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($caps_in_group as $cap): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" name="capabilities[]" value="<?php echo esc_attr($cap); ?>"
                                        <?php checked(isset($current_caps[$cap]), true); ?>>
                                </td>
                                <td><code><?php echo esc_html($cap); ?></code></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endforeach; ?>

                <p style="margin-top: 20px;">
                    <?php submit_button('Salvar Permissões'); ?>
                </p>
            </form>
        </div>
    </div>
</div>
<?php
}

// Salva capabilities
add_action('admin_post_mpa_save_role_capabilities', function () {
    if (!current_user_can('manage_options')) {
        wp_die('Sem permissão.');
    }

    if (isset($_POST['role']) && isset($_POST['capabilities']) && is_array($_POST['capabilities'])) {
        $role_key = sanitize_text_field($_POST['role']);
        $selected_caps = array_map('sanitize_text_field', $_POST['capabilities']);

        $role = get_role($role_key);
        if ($role) {
            foreach ($role->capabilities as $cap => $grant) {
                $role->remove_cap($cap);
            }

            foreach ($selected_caps as $cap) {
                $role->add_cap($cap);
            }

            wp_redirect(add_query_arg([
                'page' => 'mpa-manage-capabilities',
                'role' => $role_key,
                'updated' => 'true'
            ], admin_url('admin.php')));
            exit;
        }
    }

    wp_redirect(admin_url('admin.php?page=mpa-manage-capabilities&error=1'));
    exit;
});

// Notificações
add_action('admin_notices', function () {
    if (!isset($_GET['page']) || $_GET['page'] !== 'mpa-manage-capabilities')
        return;

    if (isset($_GET['updated'])) {
        echo '<div class="notice notice-success is-dismissible"><p>Permissões salvas com sucesso!</p></div>';
    }

    if (isset($_GET['error'])) {
        echo '<div class="notice notice-error is-dismissible"><p>Erro ao salvar permissões.</p></div>';
    }
});

// CSS
add_action('admin_head', function () {
    if ($_GET['page'] !== 'mpa-manage-capabilities')
        return;
    echo '<style>
        .mpa-tabs-wrapper {
            display: flex;
            gap: 20px;
        }
        .mpa-tabs-sidebar {
            width: 220px;
            border-right: 1px solid #ccc;
        }
        .mpa-tab-btn {
            display: block;
            width: 100%;
            text-align: left;
            padding: 10px;
            margin-bottom: 4px;
            background: #f1f1f1;
            border: none;
            cursor: pointer;
        }
        .mpa-tab-btn.active {
            background-color: #0073aa;
            color: #fff;
            font-weight: bold;
        }
        .mpa-tabs-content {
            flex: 1;
        }
        .mpa-tab-content {
            display: none;
        }
        .mpa-tab-content.active {
            display: block;
        }
        code {
            font-family: Consolas, monospace;
            font-size: 13px;
        }
    </style>';
});

// JS para alternar abas
add_action('admin_footer', function () {
    if ($_GET['page'] !== 'mpa-manage-capabilities')
        return;
    echo '<script>
    document.addEventListener("DOMContentLoaded", function () {
        const tabs = document.querySelectorAll(".mpa-tab-btn");
        const contents = document.querySelectorAll(".mpa-tab-content");

        tabs.forEach(tab => {
            tab.addEventListener("click", function () {
                tabs.forEach(btn => btn.classList.remove("active"));
                contents.forEach(c => c.classList.remove("active"));

                this.classList.add("active");
                const target = this.getAttribute("data-target");
                document.getElementById(target).classList.add("active");
            });
        });

        if (tabs.length > 0) {
            tabs[0].click();
        }
    });
    </script>';
});