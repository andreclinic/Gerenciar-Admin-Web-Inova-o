<?php
defined('ABSPATH') || exit;

/**
 * Página do plugin no admin.
 */
// Menu já criado pelo mpa-menu-manager.php

/** [ROLE] Helpers UI */
function mpa_get_ui_role(): string
{
    $r = isset($_GET['role']) ? sanitize_key($_GET['role']) : '_global';
    return $r ?: '_global';
}
function mpa_get_role_settings(string $role): array
{
    $all = get_option('mpa_menu_settings_roles', []);
    return $all[$role] ?? [];
}
function mpa_update_role_settings(string $role, array $settings): void
{
    $all = get_option('mpa_menu_settings_roles', []);
    $all[$role] = $settings;
    update_option('mpa_menu_settings_roles', $all);
}

/**
 * Handlers (POST) — por role
 */
add_action('admin_init', function () {

    $role_key = isset($_POST['mpa_role']) ? sanitize_key($_POST['mpa_role']) : '_global';
    $role_key = $role_key ?: '_global';

    // Renomear SUBMENU
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mpa_rename_submenu_submit'])) {
        if (!current_user_can('manage_options') || !check_admin_referer('mpa_rename_submenu'))
            wp_die('Permissão negada ou nonce inválido');

        $menu_pai = sanitize_text_field($_POST['mpa_menu_pai'] ?? '');
        $submenu_slug = sanitize_text_field($_POST['mpa_submenu_slug'] ?? '');
        $novo_nome = sanitize_text_field($_POST['mpa_submenu_new_name'] ?? '');

        if ($menu_pai && $submenu_slug && $novo_nome) {
            $cfg = mpa_get_role_settings($role_key);
            $cfg['rename_submenu'][$menu_pai][$submenu_slug] = $novo_nome;
            mpa_update_role_settings($role_key, $cfg);
            wp_redirect(admin_url('admin.php?page=mpa-menu-roles&role=' . $role_key . '&renomeado=1'));
            exit;
        }
    }

    // Remover MENU
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mpa_remove_menu_submit'])) {
        if (!current_user_can('manage_options') || !check_admin_referer('mpa_remove_menu'))
            wp_die('Permissão negada ou nonce inválido');

        $slug = sanitize_text_field($_POST['mpa_menu_slug_remove'] ?? '');
        if ($slug) {
            $cfg = mpa_get_role_settings($role_key);
            $cfg['remove'] = $cfg['remove'] ?? [];
            if (!in_array($slug, $cfg['remove'], true))
                $cfg['remove'][] = $slug;
            mpa_update_role_settings($role_key, $cfg);
            wp_redirect(admin_url('admin.php?page=mpa-menu-roles&role=' . $role_key . '&removido=1'));
            exit;
        }
    }

    // Remover SUBMENU
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mpa_remove_submenu_submit'])) {
        if (!current_user_can('manage_options') || !check_admin_referer('mpa_remove_submenu'))
            wp_die('Permissão negada ou nonce inválido');

        $menu_pai = sanitize_text_field($_POST['mpa_menu_pai_remove'] ?? '');
        $submenu_slug = sanitize_text_field($_POST['mpa_submenu_slug_remove'] ?? '');

        if ($menu_pai && $submenu_slug) {
            $cfg = mpa_get_role_settings($role_key);
            $cfg['remove_submenu'][$menu_pai] = $cfg['remove_submenu'][$menu_pai] ?? [];
            if (!in_array($submenu_slug, $cfg['remove_submenu'][$menu_pai], true))
                $cfg['remove_submenu'][$menu_pai][] = $submenu_slug;
            mpa_update_role_settings($role_key, $cfg);
            wp_redirect(admin_url('admin.php?page=mpa-menu-roles&role=' . $role_key . '&subremovido=1'));
            exit;
        }
    }

    // Restaurar MENU removido
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mpa_restore_menu_submit'])) {
        if (!current_user_can('manage_options') || !check_admin_referer('mpa_restore_menu'))
            wp_die('Permissão negada ou nonce inválido');

        $slug = sanitize_text_field($_POST['mpa_restore_menu_slug'] ?? '');
        $cfg = mpa_get_role_settings($role_key);
        if ($slug && !empty($cfg['remove'])) {
            $cfg['remove'] = array_values(array_diff($cfg['remove'], [$slug]));
            mpa_update_role_settings($role_key, $cfg);
        }
        wp_redirect(admin_url('admin.php?page=mpa-menu-roles&role=' . $role_key . '&restaurado=1'));
        exit;
    }

    // Restaurar SUBMENU removido
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mpa_restore_submenu_submit'])) {
        if (!current_user_can('manage_options') || !check_admin_referer('mpa_restore_submenu'))
            wp_die('Permissão negada ou nonce inválido');

        $menu_pai = sanitize_text_field($_POST['mpa_restore_menu_pai'] ?? '');
        $submenu_slug = sanitize_text_field($_POST['mpa_restore_submenu_slug'] ?? '');
        $cfg = mpa_get_role_settings($role_key);
        if ($menu_pai && $submenu_slug && !empty($cfg['remove_submenu'][$menu_pai])) {
            $cfg['remove_submenu'][$menu_pai] = array_values(array_diff($cfg['remove_submenu'][$menu_pai], [$submenu_slug]));
            if (empty($cfg['remove_submenu'][$menu_pai]))
                unset($cfg['remove_submenu'][$menu_pai]);
            mpa_update_role_settings($role_key, $cfg);
        }
        wp_redirect(admin_url('admin.php?page=mpa-menu-roles&role=' . $role_key . '&subrestaurado=1'));
        exit;
    }

    // Promover SUBMENU -> MENU
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mpa_promote_submenu_submit'])) {
        if (!current_user_can('manage_options') || !check_admin_referer('mpa_promote_submenu'))
            wp_die('Permissão negada ou nonce inválido');

        $menu_pai = sanitize_text_field($_POST['mpa_promote_menu_pai'] ?? '');
        $submenu_slug = sanitize_text_field($_POST['mpa_promote_submenu_slug'] ?? '');
        $submenu_nome = sanitize_text_field($_POST['mpa_promote_submenu_nome'] ?? '');

        if ($menu_pai && $submenu_slug && $submenu_nome) {
            $cfg = mpa_get_role_settings($role_key);
            $cfg['promote_submenu'] = $cfg['promote_submenu'] ?? [];
            $cfg['promote_submenu'][] = ['parent' => $menu_pai, 'slug' => $submenu_slug, 'name' => $submenu_nome, 'icon' => 'dashicons-admin-generic', 'pos' => 80];
            mpa_update_role_settings($role_key, $cfg);
            wp_redirect(admin_url('admin.php?page=mpa-menu-roles&role=' . $role_key . '&promovido=1'));
            exit;
        }
    }

    // Demover MENU -> SUBMENU
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mpa_demote_menu_submit'])) {
        if (!current_user_can('manage_options') || !check_admin_referer('mpa_demote_menu'))
            wp_die('Permissão negada ou nonce inválido');

        $menu_slug = sanitize_text_field($_POST['mpa_demote_menu_slug'] ?? '');
        $new_parent = sanitize_text_field($_POST['mpa_new_parent'] ?? '');

        if ($menu_slug && $new_parent) {
            $cfg = mpa_get_role_settings($role_key);
            $cfg['demote_menu'] = $cfg['demote_menu'] ?? [];
            $cfg['demote_menu'][] = ['slug' => $menu_slug, 'parent' => $new_parent];
            mpa_update_role_settings($role_key, $cfg);
            wp_redirect(admin_url('admin.php?page=mpa-menu-roles&role=' . $role_key . '&rebaixado=1'));
            exit;
        }
    }

    // Restaurar MENU rebaixado
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mpa_restore_demoted_menu_submit'])) {
        if (!current_user_can('manage_options') || !check_admin_referer('mpa_restore_demoted_menu'))
            wp_die('Permissão negada ou nonce inválido');

        $index = intval($_POST['mpa_restore_index'] ?? -1);
        $cfg = mpa_get_role_settings($role_key);
        if ($index >= 0 && !empty($cfg['demote_menu'][$index])) {
            unset($cfg['demote_menu'][$index]);
            $cfg['demote_menu'] = array_values($cfg['demote_menu']);
            mpa_update_role_settings($role_key, $cfg);
        }
        wp_redirect(admin_url('admin.php?page=mpa-menu-roles&role=' . $role_key . '&demoterestaurado=1'));
        exit;
    }

    // Restaurar SUBMENU promovido
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mpa_restore_promoted_submenu_submit'])) {
        if (!current_user_can('manage_options') || !check_admin_referer('mpa_restore_promoted_submenu'))
            wp_die('Permissão negada ou nonce inválido');

        $index = intval($_POST['mpa_restore_index'] ?? -1);
        $cfg = mpa_get_role_settings($role_key);
        if ($index >= 0 && !empty($cfg['promote_submenu'][$index])) {
            unset($cfg['promote_submenu'][$index]);
            $cfg['promote_submenu'] = array_values($cfg['promote_submenu']);
            mpa_update_role_settings($role_key, $cfg);
        }
        wp_redirect(admin_url('admin.php?page=mpa-menu-roles&role=' . $role_key . '&promoterestaurado=1'));
        exit;
    }

    // [CUSTOM] Adicionar MENU personalizado
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mpa_add_custom_menu_submit'])) {
        if (!current_user_can('manage_options') || !check_admin_referer('mpa_custom_menu'))
            wp_die('Permissão negada ou nonce inválido');

        $title = sanitize_text_field($_POST['mpa_custom_title'] ?? '');
        $url = esc_url_raw($_POST['mpa_custom_url'] ?? '');
        $icon = sanitize_text_field($_POST['mpa_custom_icon'] ?? '');

        if ($title && $url) {
            $cfg = mpa_get_role_settings($role_key);
            $cfg['custom_menus'] = $cfg['custom_menus'] ?? [];

            // id único estável
            $id = 'cm_' . substr(md5(uniqid('', true) . $title . $url), 0, 10);

            $cfg['custom_menus'][] = [
                'id' => $id,
                'title' => $title,
                'url' => $url,
                'icon' => $icon ?: 'dashicons-admin-links',
                'pos' => 82
            ];
            mpa_update_role_settings($role_key, $cfg);
            wp_redirect(admin_url('admin.php?page=mpa-menu-roles&role=' . $role_key . '&customadd=1'));
            exit;
        }
    }

    // [CUSTOM] Editar MENU personalizado
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mpa_edit_custom_menu_submit'])) {
        if (!current_user_can('manage_options') || !check_admin_referer('mpa_custom_menu'))
            wp_die('Permissão negada ou nonce inválido');

        $id = sanitize_text_field($_POST['mpa_custom_id'] ?? '');
        $title = sanitize_text_field($_POST['mpa_custom_title'] ?? '');
        $url = esc_url_raw($_POST['mpa_custom_url'] ?? '');
        $icon = sanitize_text_field($_POST['mpa_custom_icon'] ?? '');

        if ($id && $title && $url) {
            $cfg = mpa_get_role_settings($role_key);
            if (!empty($cfg['custom_menus'])) {
                foreach ($cfg['custom_menus'] as &$cm) {
                    if (!empty($cm['id']) && $cm['id'] === $id) {
                        $cm['title'] = $title;
                        $cm['url'] = $url;
                        $cm['icon'] = $icon ?: 'dashicons-admin-links';
                        break;
                    }
                }
                unset($cm);
                mpa_update_role_settings($role_key, $cfg);
            }
            wp_redirect(admin_url('admin.php?page=mpa-menu-roles&role=' . $role_key . '&customedit=1'));
            exit;
        }
    }

    // [CUSTOM] Excluir MENU personalizado
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mpa_delete_custom_menu_submit'])) {
        if (!current_user_can('manage_options') || !check_admin_referer('mpa_custom_menu'))
            wp_die('Permissão negada ou nonce inválido');

        $id = sanitize_text_field($_POST['mpa_custom_id'] ?? '');
        if ($id) {
            $cfg = mpa_get_role_settings($role_key);
            if (!empty($cfg['custom_menus'])) {
                $cfg['custom_menus'] = array_values(array_filter($cfg['custom_menus'], function ($cm) use ($id) {
                    return !empty($cm['id']) && $cm['id'] !== $id;
                }));
                mpa_update_role_settings($role_key, $cfg);
            }
            wp_redirect(admin_url('admin.php?page=mpa-menu-roles&role=' . $role_key . '&customdel=1'));
            exit;
        }
    }

    // Salvar ORDEM (menus e submenus por pai)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['mpa_menu_order']) || isset($_POST['mpa_submenu_order']))) {
        if (!current_user_can('manage_options') || !check_admin_referer('mpa_save_order'))
            wp_die('Permissão negada ou nonce inválido');

        $cfg = mpa_get_role_settings($role_key);

        if (!empty($_POST['mpa_menu_order'])) {
            $order = json_decode(stripslashes($_POST['mpa_menu_order']), true);
            if (is_array($order))
                $cfg['order_menu'] = $order;
        }
        if (!empty($_POST['mpa_submenu_order']) && is_array($_POST['mpa_submenu_order'])) {
            foreach ($_POST['mpa_submenu_order'] as $parent => $order_json) {
                $order = json_decode(stripslashes($order_json), true);
                if (is_array($order))
                    $cfg['order_submenu'][$parent] = $order;
            }
        }

        mpa_update_role_settings($role_key, $cfg);
        wp_redirect(admin_url('admin.php?page=mpa-menu-roles&role=' . $role_key . '&ordenado=1'));
        exit;
    }


    // Renomear MENU principal (por role)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mpa_rename_menu_submit'])) {
        if (!current_user_can('manage_options') || !check_admin_referer('mpa_rename_menu')) {
            wp_die('Permissão negada ou nonce inválido');
        }

        $role_key = isset($_POST['mpa_role']) ? sanitize_key($_POST['mpa_role']) : '_global';
        $role_key = $role_key ?: '_global';

        $slug = sanitize_text_field($_POST['mpa_menu_slug'] ?? '');
        $novo_nome = sanitize_text_field($_POST['mpa_new_name'] ?? '');

        if ($slug && $novo_nome) {
            $cfg = mpa_get_role_settings($role_key);

            // mapeamento de renome por slug
            $cfg['rename'][$slug] = $novo_nome;

            // (opcional) se for um menu personalizado, já atualiza o título na lista de custom
            if (strpos($slug, 'mpa_custom_') === 0 && !empty($cfg['custom_menus'])) {
                foreach ($cfg['custom_menus'] as &$cm) {
                    $cm_slug = 'mpa_custom_' . sanitize_key($cm['id'] ?? '');
                    if ($cm_slug === $slug) {
                        $cm['title'] = $novo_nome;
                        break;
                    }
                }
                unset($cm);
            }

            mpa_update_role_settings($role_key, $cfg);
            wp_redirect(admin_url('admin.php?page=mpa-menu-roles&role=' . $role_key . '&renomeado=1'));
            exit;
        }
    }
    // Reset de configurações por role
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mpa_reset_config_submit'])) {
        if (!current_user_can('manage_options') || !check_admin_referer('mpa_reset_config'))
            wp_die('Permissão negada ou nonce inválido');

        $role_key = sanitize_text_field($_POST['mpa_role'] ?? '_global');

        // Remover todas as configurações para este role
        $all_settings = get_option('mpa_menu_settings_roles', []);
        if (isset($all_settings[$role_key])) {
            unset($all_settings[$role_key]);
            update_option('mpa_menu_settings_roles', $all_settings);
        }

        wp_redirect(admin_url('admin.php?page=mpa-menu-roles&role=' . $role_key . '&resetado=1'));
        exit;
    }

    // Exportar configurações
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mpa_export_config_submit'])) {
        if (!current_user_can('manage_options') || !check_admin_referer('mpa_export_config'))
            wp_die('Permissão negada ou nonce inválido');

        $export_type = sanitize_text_field($_POST['mpa_export_type'] ?? 'all');
        $role_key = sanitize_text_field($_POST['mpa_role'] ?? '_global');

        // Obter configurações para exportar
        $all_settings = get_option('mpa_menu_settings_roles', []);
        $export_data = [];

        if ($export_type === 'current' && isset($all_settings[$role_key])) {
            // Exportar apenas role atual
            $export_data = [$role_key => $all_settings[$role_key]];
            $filename = "mpa-menu-config-{$role_key}-" . date('Y-m-d-H-i-s') . '.json';
        } else {
            // Exportar todas as configurações
            $export_data = $all_settings;
            $filename = "mpa-menu-config-all-" . date('Y-m-d-H-i-s') . '.json';
        }

        // Adicionar metadados
        $export_package = [
            'version' => '1.0',
            'plugin' => 'Gerenciar Admin Web Inovação',
            'export_date' => date('Y-m-d H:i:s'),
            'export_type' => $export_type,
            'wordpress_version' => get_bloginfo('version'),
            'site_url' => get_site_url(),
            'data' => $export_data
        ];

        // Forçar download do arquivo JSON
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen(wp_json_encode($export_package, JSON_PRETTY_PRINT)));
        header('Pragma: no-cache');
        header('Expires: 0');

        echo wp_json_encode($export_package, JSON_PRETTY_PRINT);
        exit;
    }

    // Importar configurações
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mpa_import_config_submit'])) {
        if (!current_user_can('manage_options') || !check_admin_referer('mpa_import_config'))
            wp_die('Permissão negada ou nonce inválido');

        $import_mode = sanitize_text_field($_POST['mpa_import_mode'] ?? 'replace');

        if (!isset($_FILES['mpa_import_file']) || $_FILES['mpa_import_file']['error'] !== UPLOAD_ERR_OK) {
            wp_redirect(admin_url('admin.php?page=mpa-menu-roles&import_error=1'));
            exit;
        }

        $upload_file = $_FILES['mpa_import_file'];

        // Validar tipo de arquivo
        if ($upload_file['type'] !== 'application/json' &&
            !str_ends_with($upload_file['name'], '.json')) {
            wp_redirect(admin_url('admin.php?page=mpa-menu-roles&import_error=2'));
            exit;
        }

        // Ler conteúdo do arquivo
        $file_content = file_get_contents($upload_file['tmp_name']);
        $import_data = json_decode($file_content, true);

        if (json_last_error() !== JSON_ERROR_NONE || !isset($import_data['data'])) {
            wp_redirect(admin_url('admin.php?page=mpa-menu-roles&import_error=3'));
            exit;
        }

        // Validar estrutura do arquivo
        if (!isset($import_data['plugin']) || $import_data['plugin'] !== 'Gerenciar Admin Web Inovação') {
            wp_redirect(admin_url('admin.php?page=mpa-menu-roles&import_error=4'));
            exit;
        }

        $current_settings = get_option('mpa_menu_settings_roles', []);
        $new_data = $import_data['data'];

        if ($import_mode === 'merge') {
            // Mesclar com configurações existentes
            foreach ($new_data as $role => $settings) {
                if (isset($current_settings[$role])) {
                    $current_settings[$role] = array_merge($current_settings[$role], $settings);
                } else {
                    $current_settings[$role] = $settings;
                }
            }
            $final_data = $current_settings;
        } else {
            // Substituir completamente
            $final_data = $new_data;
        }

        update_option('mpa_menu_settings_roles', $final_data);
        wp_redirect(admin_url('admin.php?page=mpa-menu-roles&importado=1'));
        exit;
    }
});

// Handler AJAX para reset de configurações
add_action('wp_ajax_mpa_reset_config', function() {
    if (!current_user_can('manage_options') || !check_admin_referer('mpa_reset_config', 'nonce'))
        wp_die('Permissão negada ou nonce inválido');

    $role = sanitize_text_field($_POST['role'] ?? '_global');

    // Remover todas as configurações para este role
    $all_settings = get_option('mpa_menu_settings_roles', []);
    if (isset($all_settings[$role])) {
        unset($all_settings[$role]);
        update_option('mpa_menu_settings_roles', $all_settings);
    }

    wp_send_json_success([
        'message' => $role === '_global'
            ? 'Configurações globais resetadas com sucesso!'
            : "Configurações para o perfil '{$role}' resetadas com sucesso!"
    ]);
});

/**
 * Interface — agora usando PRÉVIA por role + Menus Personalizados
 */
if (!function_exists('mpa_render_settings_page')) {
    function mpa_render_settings_page()
    {
        require_once ABSPATH . 'wp-admin/includes/user.php';
        $role_current = mpa_get_ui_role();

        // Mensagens
        foreach ([
            'removido' => 'Menu removido com sucesso!',
            'subremovido' => 'Submenu removido com sucesso!',
            'restaurado' => 'Menu restaurado com sucesso!',
            'subrestaurado' => 'Submenu restaurado com sucesso!',
            'promovido' => 'Submenu transformado em menu principal com sucesso!',
            'rebaixado' => 'Menu transformado em submenu com sucesso!',
            'demoterestaurado' => 'Menu restaurado como principal com sucesso!',
            'promoterestaurado' => 'Submenu restaurado ao menu pai original com sucesso!',
            'ordenado' => 'Ordem salva com sucesso!',
            'customadd' => 'Menu personalizado adicionado!',
            'customedit' => 'Menu personalizado editado!',
            'customdel' => 'Menu personalizado excluído!',
            'resetado' => 'Configurações resetadas com sucesso!',
            'importado' => 'Configurações importadas com sucesso!'
        ] as $param => $msg) {
            if (isset($_GET[$param]) && $_GET[$param] == '1') {
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($msg) . '</p></div>';
            }
        }

        // Mensagens de erro da importação
        $import_errors = [
            1 => 'Erro ao fazer upload do arquivo.',
            2 => 'Arquivo deve ser do tipo JSON (.json).',
            3 => 'Arquivo JSON inválido ou corrompido.',
            4 => 'Arquivo não pertence ao plugin Gerenciar Admin Web Inovação.'
        ];

        foreach ($import_errors as $error_code => $error_msg) {
            if (isset($_GET['import_error']) && $_GET['import_error'] == $error_code) {
                echo '<div class="notice notice-error is-dismissible"><p><strong>Erro na Importação:</strong> ' . esc_html($error_msg) . '</p></div>';
            }
        }

        // PRÉVIA por role (baseline + settings da role)
        $base_menu = $GLOBALS['mpa_menu_baseline'] ?? $GLOBALS['menu'];
        $base_submenu = $GLOBALS['mpa_submenu_baseline'] ?? $GLOBALS['submenu'];
        $preview_menu = $base_menu;
        $preview_submenu = $base_submenu;

        $settings_preview = mpa_get_effective_settings_for_role($role_current);
        mpa_apply_settings_to_arrays($preview_menu, $preview_submenu, $settings_preview);

        $roles = get_editable_roles();
        $cfg_role = mpa_get_role_settings($role_current);

        // Mapa slug -> label do menu (para títulos de blocos)
        $menu_labels = [];
        foreach ($preview_menu as $m) {
            if (!empty($m[2]))
                $menu_labels[$m[2]] = $m[0];
        }

        ?>
        <div class="wrap">
            <h1>Gerenciar Menu Webi</h1>

            <!-- Seletor de role -->
            <form method="get" style="margin: 10px 0 20px; display:flex; gap:8px; align-items:center;">
                <input type="hidden" name="page" value="mpa-menu-roles">
                <label for="mpa_role_sel"><strong>Perfil (role):</strong></label>
                <select id="mpa_role_sel" name="role">
                    <option value="_global" <?php selected($role_current, '_global'); ?>>Global (padrão)</option>
                    <?php foreach ($roles as $slug => $data): ?>
                        <option value="<?php echo esc_attr($slug); ?>" <?php selected($role_current, $slug); ?>>
                            <?php echo esc_html($data['name']); ?> (<?php echo esc_html($slug); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <button class="button">Trocar</button>
                <span style="opacity:.7;">— editando regras para:
                    <strong><?php echo esc_html($role_current === '_global' ? 'Global' : $roles[$role_current]['name'] . " ({$role_current})"); ?></strong></span>
            </form>

            <!-- Menus Principais (PRÉVIA) -->
            <h2>Menus Principais (prévia de <?php echo esc_html($role_current); ?>)</h2>
            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Slug</th>
                        <th>Ícone</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody id="mpa-menus">
                    <?php foreach ($preview_menu as $item):
                        $nome_atual = $item[0];
                        $slug = $item[2];
                        $icone = $item[6] ?? '';
                        ?>
                        <tr data-slug="<?php echo esc_attr($slug); ?>">
                            <td><?php echo esc_html($nome_atual); ?></td>
                            <td><?php echo esc_html($slug); ?></td>
                            <td><span class="dashicons <?php echo esc_attr($icone); ?>"></span></td>
                            <td style="display:flex; gap:8px; flex-wrap:wrap;">
                                <!-- Renomear MENU principal (por role) -->
                                <form method="post" style="display:inline-flex; gap:6px;">
                                    <?php wp_nonce_field('mpa_rename_menu'); ?>
                                    <input type="hidden" name="mpa_role" value="<?php echo esc_attr($role_current); ?>">
                                    <input type="hidden" name="mpa_menu_slug" value="<?php echo esc_attr($slug); ?>">
                                    <input type="text" name="mpa_new_name" placeholder="Novo nome" class="regular-text">
                                    <button type="submit" name="mpa_rename_menu_submit" class="button">Renomear</button>
                                </form>

                                <!-- Remover MENU -->
                                <form method="post" onsubmit="return confirm('Tem certeza que deseja remover este menu?');">
                                    <?php wp_nonce_field('mpa_remove_menu'); ?>
                                    <input type="hidden" name="mpa_role" value="<?php echo esc_attr($role_current); ?>">
                                    <input type="hidden" name="mpa_menu_slug_remove" value="<?php echo esc_attr($slug); ?>">
                                    <button type="submit" name="mpa_remove_menu_submit"
                                        class="button button-danger">Remover</button>
                                </form>

                                <!-- Tornar Submenu -->
                                <form method="post" style="display:inline-flex; gap:6px;">
                                    <?php wp_nonce_field('mpa_demote_menu'); ?>
                                    <input type="hidden" name="mpa_role" value="<?php echo esc_attr($role_current); ?>">
                                    <input type="hidden" name="mpa_demote_menu_slug" value="<?php echo esc_attr($slug); ?>">
                                    <select name="mpa_new_parent" required>
                                        <option value="">-- Escolher Menu Pai --</option>
                                        <?php foreach ($preview_menu as $pai): ?>
                                            <option value="<?php echo esc_attr($pai[2]); ?>"><?php echo esc_html($pai[0]); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" name="mpa_demote_menu_submit" class="button">Tornar Submenu</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- [CUSTOM] Adicionar Menu Personalizado -->
            <h2 style="margin-top:40px;">Adicionar Menu Personalizado</h2>
            <form method="post" style="display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end;">
                <?php wp_nonce_field('mpa_custom_menu'); ?>
                <input type="hidden" name="mpa_role" value="<?php echo esc_attr($role_current); ?>">
                <p>
                    <label><strong>Nome</strong><br>
                        <input type="text" name="mpa_custom_title" class="regular-text" required
                            placeholder="Ex.: Suporte Webi">
                    </label>
                </p>
                <p style="min-width:380px;">
                    <label><strong>URL</strong><br>
                        <input type="text" name="mpa_custom_url" class="regular-text" required
                            placeholder="/wp-admin/admin.php?page=...">
                        <br><small>Interna: <code>/wp-admin/...</code> ou <code>admin.php?page=...</code> — Externa:
                            <code>https://seusite.com</code> (abrirá em nova aba)</small>
                    </label>
                </p>
                <p>
                    <label><strong>Ícone (Dashicons)</strong><br>
                        <input type="text" name="mpa_custom_icon" class="regular-text" placeholder="Ex.: dashicons-admin-links">
                    </label>
                </p>
                <p>
                    <button type="submit" name="mpa_add_custom_menu_submit" class="button button-primary">Adicionar</button>
                </p>
            </form>

            <!-- [CUSTOM] Lista de Menus Personalizados (editar/excluir) -->
            <h2 style="margin-top:20px;">Menus Personalizados (<?php echo esc_html($role_current); ?>)</h2>
            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>URL</th>
                        <th>Ícone</th>
                        <th>Slug</th>
                        <th style="width:240px;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($cfg_role['custom_menus'])):
                        foreach ($cfg_role['custom_menus'] as $cm):
                            $id = $cm['id'] ?? '';
                            $name = $cm['title'] ?? '';
                            $url = $cm['url'] ?? '';
                            $icon = $cm['icon'] ?? 'dashicons-admin-links';
                            $slug = 'mpa_custom_' . sanitize_key($id);
                            ?>
                            <tr>
                                <td>
                                    <form method="post" style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
                                        <?php wp_nonce_field('mpa_custom_menu'); ?>
                                        <input type="hidden" name="mpa_role" value="<?php echo esc_attr($role_current); ?>">
                                        <input type="hidden" name="mpa_custom_id" value="<?php echo esc_attr($id); ?>">
                                        <input type="text" name="mpa_custom_title" value="<?php echo esc_attr($name); ?>"
                                            class="regular-text">
                                </td>
                                <td>
                                    <input type="text" name="mpa_custom_url" value="<?php echo esc_attr($url); ?>" class="regular-text"
                                        style="min-width:340px;">
                                </td>
                                <td>
                                    <input type="text" name="mpa_custom_icon" value="<?php echo esc_attr($icon); ?>"
                                        class="regular-text" placeholder="dashicons-...">
                                </td>
                                <td><code><?php echo esc_html($slug); ?></code></td>
                                <td style="display:flex; gap:8px;">
                                    <button type="submit" name="mpa_edit_custom_menu_submit" class="button">Salvar</button>
                                    </form>

                                    <form method="post" onsubmit="return confirm('Excluir este menu personalizado?');"
                                        style="display:inline;">
                                        <?php wp_nonce_field('mpa_custom_menu'); ?>
                                        <input type="hidden" name="mpa_role" value="<?php echo esc_attr($role_current); ?>">
                                        <input type="hidden" name="mpa_custom_id" value="<?php echo esc_attr($id); ?>">
                                        <button type="submit" name="mpa_delete_custom_menu_submit"
                                            class="button button-danger">Excluir</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; else: ?>
                        <tr>
                            <td colspan="5">Nenhum menu personalizado para este perfil.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>



            <!-- Submenus (PRÉVIA) -->
            <h2 style="margin-top:40px;">Submenus (prévia de <?php echo esc_html($role_current); ?> — arraste dentro do pai)
            </h2>
            <?php
            if (!empty($preview_submenu)):
                foreach ($preview_submenu as $menu_pai => $subitems):
                    $parent_id = preg_replace('/[^a-zA-Z0-9_-]/', '-', $menu_pai);
                    $parent_label = $menu_labels[$menu_pai] ?? $menu_pai;
                    ?>
                    <h3><?php echo esc_html($parent_label); ?> <small style="opacity:.7;">(<?php echo esc_html($menu_pai); ?>)</small>
                    </h3>
                    <table class="widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Slug</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody id="mpa-submenu-<?php echo esc_attr($parent_id); ?>"
                            data-mpa-submenu-parent="<?php echo esc_attr($menu_pai); ?>">
                            <?php foreach ($subitems as $sub):
                                $submenu_nome = $sub[0];
                                $submenu_slug = $sub[2];
                                ?>
                                <tr data-slug="<?php echo esc_attr($submenu_slug); ?>">
                                    <td><?php echo esc_html($submenu_nome); ?></td>
                                    <td><?php echo esc_html($submenu_slug); ?></td>
                                    <td style="display:flex; gap:8px; flex-wrap:wrap;">
                                        <!-- Renomear -->
                                        <form method="post" style="display:inline-flex; gap:6px;">
                                            <?php wp_nonce_field('mpa_rename_submenu'); ?>
                                            <input type="hidden" name="mpa_role" value="<?php echo esc_attr($role_current); ?>">
                                            <input type="hidden" name="mpa_menu_pai" value="<?php echo esc_attr($menu_pai); ?>">
                                            <input type="hidden" name="mpa_submenu_slug" value="<?php echo esc_attr($submenu_slug); ?>">
                                            <input type="text" name="mpa_submenu_new_name" placeholder="Novo nome" class="regular-text">
                                            <button type="submit" name="mpa_rename_submenu_submit" class="button">Renomear</button>
                                        </form>

                                        <!-- Remover -->
                                        <form method="post" onsubmit="return confirm('Tem certeza que deseja remover este submenu?');">
                                            <?php wp_nonce_field('mpa_remove_submenu'); ?>
                                            <input type="hidden" name="mpa_role" value="<?php echo esc_attr($role_current); ?>">
                                            <input type="hidden" name="mpa_menu_pai_remove" value="<?php echo esc_attr($menu_pai); ?>">
                                            <input type="hidden" name="mpa_submenu_slug_remove"
                                                value="<?php echo esc_attr($submenu_slug); ?>">
                                            <button type="submit" name="mpa_remove_submenu_submit"
                                                class="button button-danger">Remover</button>
                                        </form>

                                        <!-- Tornar Menu -->
                                        <form method="post">
                                            <?php wp_nonce_field('mpa_promote_submenu'); ?>
                                            <input type="hidden" name="mpa_role" value="<?php echo esc_attr($role_current); ?>">
                                            <input type="hidden" name="mpa_promote_menu_pai" value="<?php echo esc_attr($menu_pai); ?>">
                                            <input type="hidden" name="mpa_promote_submenu_slug"
                                                value="<?php echo esc_attr($submenu_slug); ?>">
                                            <input type="hidden" name="mpa_promote_submenu_nome"
                                                value="<?php echo esc_attr($submenu_nome); ?>">
                                            <button type="submit" name="mpa_promote_submenu_submit" class="button">Tornar Menu</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php
                endforeach;
            else:
                echo '<p>Nenhum submenu encontrado.</p>';
            endif;
            ?>

            <!-- Menus Removidos (role) -->
            <h2 style="margin-top:40px;">Menus Removidos (<?php echo esc_html($role_current); ?>)</h2>
            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th>Slug</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($cfg_role['remove'])):
                        foreach ($cfg_role['remove'] as $removed_slug): ?>
                            <tr>
                                <td><?php echo esc_html($removed_slug); ?></td>
                                <td>
                                    <form method="post">
                                        <?php wp_nonce_field('mpa_restore_menu'); ?>
                                        <input type="hidden" name="mpa_role" value="<?php echo esc_attr($role_current); ?>">
                                        <input type="hidden" name="mpa_restore_menu_slug"
                                            value="<?php echo esc_attr($removed_slug); ?>">
                                        <button type="submit" name="mpa_restore_menu_submit" class="button">Restaurar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; else: ?>
                        <tr>
                            <td colspan="2">Nenhum menu removido para este perfil.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Submenus Removidos (role) -->
            <h2 style="margin-top:40px;">Submenus Removidos (<?php echo esc_html($role_current); ?>)</h2>
            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th>Menu Pai</th>
                        <th>Slug</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($cfg_role['remove_submenu'])):
                        foreach ($cfg_role['remove_submenu'] as $menu_pai => $items):
                            foreach ($items as $removed_slug): ?>
                                <tr>
                                    <td><?php echo esc_html($menu_pai); ?></td>
                                    <td><?php echo esc_html($removed_slug); ?></td>
                                    <td>
                                        <form method="post">
                                            <?php wp_nonce_field('mpa_restore_submenu'); ?>
                                            <input type="hidden" name="mpa_role" value="<?php echo esc_attr($role_current); ?>">
                                            <input type="hidden" name="mpa_restore_menu_pai" value="<?php echo esc_attr($menu_pai); ?>">
                                            <input type="hidden" name="mpa_restore_submenu_slug"
                                                value="<?php echo esc_attr($removed_slug); ?>">
                                            <button type="submit" name="mpa_restore_submenu_submit" class="button">Restaurar</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; endforeach; else: ?>
                        <tr>
                            <td colspan="3">Nenhum submenu removido para este perfil.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Menus Rebaixados / Submenus Promovidos -->
            <h2 style="margin-top:40px;">Menus Rebaixados (<?php echo esc_html($role_current); ?>)</h2>
            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th>Slug</th>
                        <th>Menu Pai Atual</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($cfg_role['demote_menu'])):
                        foreach ($cfg_role['demote_menu'] as $index => $demoted): ?>
                            <tr>
                                <td><?php echo esc_html($demoted['slug']); ?></td>
                                <td><?php echo esc_html($demoted['parent']); ?></td>
                                <td>
                                    <form method="post">
                                        <?php wp_nonce_field('mpa_restore_demoted_menu'); ?>
                                        <input type="hidden" name="mpa_role" value="<?php echo esc_attr($role_current); ?>">
                                        <input type="hidden" name="mpa_restore_index" value="<?php echo esc_attr($index); ?>">
                                        <button type="submit" name="mpa_restore_demoted_menu_submit" class="button">Restaurar como Menu
                                            Principal</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; else: ?>
                        <tr>
                            <td colspan="3">Nenhum menu rebaixado para este perfil.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <h2 style="margin-top:40px;">Submenus Promovidos (<?php echo esc_html($role_current); ?>)</h2>
            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Slug</th>
                        <th>Menu Pai Original</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($cfg_role['promote_submenu'])):
                        foreach ($cfg_role['promote_submenu'] as $index => $promoted): ?>
                            <tr>
                                <td><?php echo esc_html($promoted['name']); ?></td>
                                <td><?php echo esc_html($promoted['slug']); ?></td>
                                <td><?php echo esc_html($promoted['parent']); ?></td>
                                <td>
                                    <form method="post">
                                        <?php wp_nonce_field('mpa_restore_promoted_submenu'); ?>
                                        <input type="hidden" name="mpa_role" value="<?php echo esc_attr($role_current); ?>">
                                        <input type="hidden" name="mpa_restore_index" value="<?php echo esc_attr($index); ?>">
                                        <button type="submit" name="mpa_restore_promoted_submenu_submit" class="button">Restaurar como
                                            Submenu</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; else: ?>
                        <tr>
                            <td colspan="4">Nenhum submenu promovido para este perfil.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Form oculto para ordem -->
            <form method="post" id="mpa_order_form" style="display:none;">
                <?php wp_nonce_field('mpa_save_order'); ?>
                <input type="hidden" name="mpa_role" value="<?php echo esc_attr($role_current); ?>">
                <input type="hidden" name="mpa_menu_order" id="mpa_menu_order">
                <!-- mpa_submenu_order[parent] será criado via JS -->
            </form>

            <!-- Seção de Export/Import -->
            <hr style="margin: 30px 0;">
            <h2>🔄 Backup e Migração de Configurações</h2>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 20px 0;">
                <!-- Exportar Configurações -->
                <div style="padding: 15px; background: #f0f8ff; border: 1px solid #b3d9ff; border-radius: 6px; border-left: 4px solid #0073aa;">
                    <h3 style="color: #0073aa; margin: 0 0 15px;"><strong>📤 Exportar Configurações</strong></h3>
                    <p style="margin: 5px 0 15px; font-size: 14px;">
                        Faça backup das suas configurações de menu em formato JSON para usar como backup ou migrar para outra instalação.
                    </p>

                    <form method="post" style="margin-bottom: 10px;">
                        <?php wp_nonce_field('mpa_export_config'); ?>
                        <input type="hidden" name="mpa_role" value="<?php echo esc_attr($role_current); ?>">

                        <div style="margin-bottom: 10px;">
                            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Tipo de Exportação:</label>
                            <label style="display: block; margin-bottom: 5px;">
                                <input type="radio" name="mpa_export_type" value="current" checked>
                                Apenas perfil atual (<?php echo esc_html($role_current === '_global' ? 'Global' : $roles[$role_current]['name']); ?>)
                            </label>
                            <label style="display: block; margin-bottom: 10px;">
                                <input type="radio" name="mpa_export_type" value="all">
                                Todas as configurações (todos os perfis)
                            </label>
                        </div>

                        <button type="submit" name="mpa_export_config_submit" class="button button-primary">
                            💾 Exportar Configurações
                        </button>
                    </form>
                </div>

                <!-- Importar Configurações -->
                <div style="padding: 15px; background: #f8fff0; border: 1px solid #c8e6c9; border-radius: 6px; border-left: 4px solid #4caf50;">
                    <h3 style="color: #4caf50; margin: 0 0 15px;"><strong>📥 Importar Configurações</strong></h3>
                    <p style="margin: 5px 0 15px; font-size: 14px;">
                        Restaure configurações de um arquivo de backup ou migre configurações de outra instalação.
                    </p>

                    <form method="post" enctype="multipart/form-data">
                        <?php wp_nonce_field('mpa_import_config'); ?>

                        <div style="margin-bottom: 10px;">
                            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Arquivo de Configuração (.json):</label>
                            <input type="file" name="mpa_import_file" accept=".json" required
                                   style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                        </div>

                        <div style="margin-bottom: 15px;">
                            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Modo de Importação:</label>
                            <label style="display: block; margin-bottom: 5px;">
                                <input type="radio" name="mpa_import_mode" value="replace" checked>
                                <strong>Substituir</strong> - Replace todas as configurações existentes
                            </label>
                            <label style="display: block;">
                                <input type="radio" name="mpa_import_mode" value="merge">
                                <strong>Mesclar</strong> - Combinar com configurações existentes
                            </label>
                        </div>

                        <button type="submit" name="mpa_import_config_submit" class="button button-secondary"
                                onclick="return confirm('⚠️ ATENÇÃO! Esta ação irá modificar suas configurações de menu. Tem certeza que deseja continuar?')">
                            📂 Importar Configurações
                        </button>
                    </form>
                </div>
            </div>

            <!-- Botão de Reset -->
            <hr style="margin: 30px 0;">
            <div style="margin: 20px 0; padding: 15px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 6px; border-left: 4px solid #dc3545;">
                <h3 style="color: #dc3545; margin: 0 0 10px;"><strong>⚠️ Zona de Perigo</strong></h3>
                <p style="margin: 5px 0 15px; font-size: 14px; color: #856404;">
                    Esta ação irá <strong>DELETAR PERMANENTEMENTE</strong> todas as configurações de menu para o perfil selecionado
                    <strong>"<?php echo esc_html($role_current === '_global' ? 'Global' : $roles[$role_current]['name'] . " ({$role_current})"); ?>"</strong>.
                    <br><br>
                    <strong>Isso inclui:</strong> Menus personalizados, renomeações, ordem personalizada, menus removidos e todas as demais configurações.
                </p>
                <button type="button" id="mpa-reset-config" class="button button-large"
                        data-role="<?php echo esc_attr($role_current); ?>"
                        style="background: #dc3545; color: white; border-color: #dc3545; font-weight: bold;">
                    🗑️ Resetar Todas as Configurações
                </button>
            </div>

            <!-- SortableJS -->
            <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
            <script>
                document.addEventListener("DOMContentLoaded", function () {
                    // MENUS principais (prévia)
                    const menusEl = document.querySelector("#mpa-menus");
                    if (menusEl) {
                        new Sortable(menusEl, {
                            animation: 150,
                            onEnd: function () {
                                const order = Array.from(menusEl.querySelectorAll("tr")).map(tr => tr.dataset
                                    .slug);
                                document.getElementById("mpa_menu_order").value = JSON.stringify(order);
                                document.getElementById("mpa_order_form").submit();
                            }
                        });
                    }

                    // SUBMENUS por PAI (prévia)
                    document.querySelectorAll('tbody[data-mpa-submenu-parent]').forEach(function (tbody) {
                        new Sortable(tbody, {
                            animation: 150,
                            onEnd: function () {
                                const parent = tbody.getAttribute('data-mpa-submenu-parent');
                                const slugs = Array.from(tbody.querySelectorAll('tr')).map(tr => tr
                                    .dataset.slug);

                                // remove input anterior (se existir) para este pai
                                const sel = '#mpa_order_form input[name="mpa_submenu_order[' + CSS
                                    .escape(parent) + ']"]';
                                const old = document.querySelector(sel);
                                if (old) old.remove();

                                // cria input hidden para este pai
                                const input = document.createElement('input');
                                input.type = 'hidden';
                                input.name = 'mpa_submenu_order[' + parent + ']';
                                input.value = JSON.stringify(slugs);
                                document.getElementById('mpa_order_form').appendChild(input);

                                // envia
                                document.getElementById('mpa_order_form').submit();
                            }
                        });
                    });
                });

                // Funcionalidade de reset com confirmação
                const resetButton = document.getElementById('mpa-reset-config');
                if (resetButton) {
                    resetButton.addEventListener('click', function() {
                        const role = this.getAttribute('data-role');
                        const roleName = role === '_global' ? 'Global' : role;

                        if (confirm(`⚠️ ATENÇÃO!\n\nEsta ação irá DELETAR PERMANENTEMENTE todas as configurações de menu para o perfil "${roleName}".\n\nIsso inclui:\n• Menus personalizados criados\n• Renomeações de menus\n• Menus removidos\n• Ordem personalizada dos menus\n• Todas as demais configurações\n\n❌ ESTA AÇÃO NÃO PODE SER DESFEITA!\n\nTem certeza que deseja continuar?`)) {
                            if (confirm(`🔥 ÚLTIMA CONFIRMAÇÃO!\n\nVocê está prestes a resetar TODAS as configurações para "${roleName}".\n\nClique OK para DELETAR PERMANENTEMENTE ou Cancelar para abortar.`)) {
                                // Redirecionar para o handler PHP com confirmação dupla
                                const form = document.createElement('form');
                                form.method = 'POST';
                                form.style.display = 'none';

                                // Nonce field
                                const nonceField = document.createElement('input');
                                nonceField.type = 'hidden';
                                nonceField.name = '_wpnonce';
                                nonceField.value = '<?php echo wp_create_nonce('mpa_reset_config'); ?>';
                                form.appendChild(nonceField);

                                // Role field
                                const roleField = document.createElement('input');
                                roleField.type = 'hidden';
                                roleField.name = 'mpa_role';
                                roleField.value = role;
                                form.appendChild(roleField);

                                // Submit field
                                const submitField = document.createElement('input');
                                submitField.type = 'hidden';
                                submitField.name = 'mpa_reset_config_submit';
                                submitField.value = '1';
                                form.appendChild(submitField);

                                document.body.appendChild(form);
                                form.submit();
                            }
                        }
                    });
                }
            </script>
        </div>
        <?php
    }
}