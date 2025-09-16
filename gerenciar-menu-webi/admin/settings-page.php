<?php
defined('ABSPATH') || exit;

/**
 * Página do plugin no admin.
 */
add_action('admin_menu', function () {
    if (!current_user_can('manage_options'))
        return;

    add_menu_page(
        'Gerenciar Menu Webi',
        'Gerenciar Menu',
        'manage_options',
        'gerenciar-menu-webi',
        'gmw_render_settings_page',
        'dashicons-menu',
        80
    );
});

/** [ROLE] Helpers UI */
function gmw_get_ui_role(): string
{
    $r = isset($_GET['role']) ? sanitize_key($_GET['role']) : '_global';
    return $r ?: '_global';
}
function gmw_get_role_settings(string $role): array
{
    $all = get_option('gmw_menu_settings_roles', []);
    return $all[$role] ?? [];
}
function gmw_update_role_settings(string $role, array $settings): void
{
    $all = get_option('gmw_menu_settings_roles', []);
    $all[$role] = $settings;
    update_option('gmw_menu_settings_roles', $all);
}

/**
 * Handlers (POST) — por role
 */
add_action('admin_init', function () {

    $role_key = isset($_POST['gmw_role']) ? sanitize_key($_POST['gmw_role']) : '_global';
    $role_key = $role_key ?: '_global';

    // Renomear SUBMENU
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['gmw_rename_submenu_submit'])) {
        if (!current_user_can('manage_options') || !check_admin_referer('gmw_rename_submenu'))
            wp_die('Permissão negada ou nonce inválido');

        $menu_pai = sanitize_text_field($_POST['gmw_menu_pai'] ?? '');
        $submenu_slug = sanitize_text_field($_POST['gmw_submenu_slug'] ?? '');
        $novo_nome = sanitize_text_field($_POST['gmw_submenu_new_name'] ?? '');

        if ($menu_pai && $submenu_slug && $novo_nome) {
            $cfg = gmw_get_role_settings($role_key);
            $cfg['rename_submenu'][$menu_pai][$submenu_slug] = $novo_nome;
            gmw_update_role_settings($role_key, $cfg);
            wp_redirect(admin_url('admin.php?page=gerenciar-menu-webi&role=' . $role_key . '&renomeado=1'));
            exit;
        }
    }

    // Remover MENU
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['gmw_remove_menu_submit'])) {
        if (!current_user_can('manage_options') || !check_admin_referer('gmw_remove_menu'))
            wp_die('Permissão negada ou nonce inválido');

        $slug = sanitize_text_field($_POST['gmw_menu_slug_remove'] ?? '');
        if ($slug) {
            $cfg = gmw_get_role_settings($role_key);
            $cfg['remove'] = $cfg['remove'] ?? [];
            if (!in_array($slug, $cfg['remove'], true))
                $cfg['remove'][] = $slug;
            gmw_update_role_settings($role_key, $cfg);
            wp_redirect(admin_url('admin.php?page=gerenciar-menu-webi&role=' . $role_key . '&removido=1'));
            exit;
        }
    }

    // Remover SUBMENU
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['gmw_remove_submenu_submit'])) {
        if (!current_user_can('manage_options') || !check_admin_referer('gmw_remove_submenu'))
            wp_die('Permissão negada ou nonce inválido');

        $menu_pai = sanitize_text_field($_POST['gmw_menu_pai_remove'] ?? '');
        $submenu_slug = sanitize_text_field($_POST['gmw_submenu_slug_remove'] ?? '');

        if ($menu_pai && $submenu_slug) {
            $cfg = gmw_get_role_settings($role_key);
            $cfg['remove_submenu'][$menu_pai] = $cfg['remove_submenu'][$menu_pai] ?? [];
            if (!in_array($submenu_slug, $cfg['remove_submenu'][$menu_pai], true))
                $cfg['remove_submenu'][$menu_pai][] = $submenu_slug;
            gmw_update_role_settings($role_key, $cfg);
            wp_redirect(admin_url('admin.php?page=gerenciar-menu-webi&role=' . $role_key . '&subremovido=1'));
            exit;
        }
    }

    // Restaurar MENU removido
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['gmw_restore_menu_submit'])) {
        if (!current_user_can('manage_options') || !check_admin_referer('gmw_restore_menu'))
            wp_die('Permissão negada ou nonce inválido');

        $slug = sanitize_text_field($_POST['gmw_restore_menu_slug'] ?? '');
        $cfg = gmw_get_role_settings($role_key);
        if ($slug && !empty($cfg['remove'])) {
            $cfg['remove'] = array_values(array_diff($cfg['remove'], [$slug]));
            gmw_update_role_settings($role_key, $cfg);
        }
        wp_redirect(admin_url('admin.php?page=gerenciar-menu-webi&role=' . $role_key . '&restaurado=1'));
        exit;
    }

    // Restaurar SUBMENU removido
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['gmw_restore_submenu_submit'])) {
        if (!current_user_can('manage_options') || !check_admin_referer('gmw_restore_submenu'))
            wp_die('Permissão negada ou nonce inválido');

        $menu_pai = sanitize_text_field($_POST['gmw_restore_menu_pai'] ?? '');
        $submenu_slug = sanitize_text_field($_POST['gmw_restore_submenu_slug'] ?? '');
        $cfg = gmw_get_role_settings($role_key);
        if ($menu_pai && $submenu_slug && !empty($cfg['remove_submenu'][$menu_pai])) {
            $cfg['remove_submenu'][$menu_pai] = array_values(array_diff($cfg['remove_submenu'][$menu_pai], [$submenu_slug]));
            if (empty($cfg['remove_submenu'][$menu_pai]))
                unset($cfg['remove_submenu'][$menu_pai]);
            gmw_update_role_settings($role_key, $cfg);
        }
        wp_redirect(admin_url('admin.php?page=gerenciar-menu-webi&role=' . $role_key . '&subrestaurado=1'));
        exit;
    }

    // Promover SUBMENU -> MENU
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['gmw_promote_submenu_submit'])) {
        if (!current_user_can('manage_options') || !check_admin_referer('gmw_promote_submenu'))
            wp_die('Permissão negada ou nonce inválido');

        $menu_pai = sanitize_text_field($_POST['gmw_promote_menu_pai'] ?? '');
        $submenu_slug = sanitize_text_field($_POST['gmw_promote_submenu_slug'] ?? '');
        $submenu_nome = sanitize_text_field($_POST['gmw_promote_submenu_nome'] ?? '');

        if ($menu_pai && $submenu_slug && $submenu_nome) {
            $cfg = gmw_get_role_settings($role_key);
            $cfg['promote_submenu'] = $cfg['promote_submenu'] ?? [];
            $cfg['promote_submenu'][] = ['parent' => $menu_pai, 'slug' => $submenu_slug, 'name' => $submenu_nome, 'icon' => 'dashicons-admin-generic', 'pos' => 80];
            gmw_update_role_settings($role_key, $cfg);
            wp_redirect(admin_url('admin.php?page=gerenciar-menu-webi&role=' . $role_key . '&promovido=1'));
            exit;
        }
    }

    // Demover MENU -> SUBMENU
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['gmw_demote_menu_submit'])) {
        if (!current_user_can('manage_options') || !check_admin_referer('gmw_demote_menu'))
            wp_die('Permissão negada ou nonce inválido');

        $menu_slug = sanitize_text_field($_POST['gmw_demote_menu_slug'] ?? '');
        $new_parent = sanitize_text_field($_POST['gmw_new_parent'] ?? '');

        if ($menu_slug && $new_parent) {
            $cfg = gmw_get_role_settings($role_key);
            $cfg['demote_menu'] = $cfg['demote_menu'] ?? [];
            $cfg['demote_menu'][] = ['slug' => $menu_slug, 'parent' => $new_parent];
            gmw_update_role_settings($role_key, $cfg);
            wp_redirect(admin_url('admin.php?page=gerenciar-menu-webi&role=' . $role_key . '&rebaixado=1'));
            exit;
        }
    }

    // Restaurar MENU rebaixado
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['gmw_restore_demoted_menu_submit'])) {
        if (!current_user_can('manage_options') || !check_admin_referer('gmw_restore_demoted_menu'))
            wp_die('Permissão negada ou nonce inválido');

        $index = intval($_POST['gmw_restore_index'] ?? -1);
        $cfg = gmw_get_role_settings($role_key);
        if ($index >= 0 && !empty($cfg['demote_menu'][$index])) {
            unset($cfg['demote_menu'][$index]);
            $cfg['demote_menu'] = array_values($cfg['demote_menu']);
            gmw_update_role_settings($role_key, $cfg);
        }
        wp_redirect(admin_url('admin.php?page=gerenciar-menu-webi&role=' . $role_key . '&demoterestaurado=1'));
        exit;
    }

    // Restaurar SUBMENU promovido
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['gmw_restore_promoted_submenu_submit'])) {
        if (!current_user_can('manage_options') || !check_admin_referer('gmw_restore_promoted_submenu'))
            wp_die('Permissão negada ou nonce inválido');

        $index = intval($_POST['gmw_restore_index'] ?? -1);
        $cfg = gmw_get_role_settings($role_key);
        if ($index >= 0 && !empty($cfg['promote_submenu'][$index])) {
            unset($cfg['promote_submenu'][$index]);
            $cfg['promote_submenu'] = array_values($cfg['promote_submenu']);
            gmw_update_role_settings($role_key, $cfg);
        }
        wp_redirect(admin_url('admin.php?page=gerenciar-menu-webi&role=' . $role_key . '&promoterestaurado=1'));
        exit;
    }

    // [CUSTOM] Adicionar MENU personalizado
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['gmw_add_custom_menu_submit'])) {
        if (!current_user_can('manage_options') || !check_admin_referer('gmw_custom_menu'))
            wp_die('Permissão negada ou nonce inválido');

        $title = sanitize_text_field($_POST['gmw_custom_title'] ?? '');
        $url = esc_url_raw($_POST['gmw_custom_url'] ?? '');
        $icon = sanitize_text_field($_POST['gmw_custom_icon'] ?? '');

        if ($title && $url) {
            $cfg = gmw_get_role_settings($role_key);
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
            gmw_update_role_settings($role_key, $cfg);
            wp_redirect(admin_url('admin.php?page=gerenciar-menu-webi&role=' . $role_key . '&customadd=1'));
            exit;
        }
    }

    // [CUSTOM] Editar MENU personalizado
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['gmw_edit_custom_menu_submit'])) {
        if (!current_user_can('manage_options') || !check_admin_referer('gmw_custom_menu'))
            wp_die('Permissão negada ou nonce inválido');

        $id = sanitize_text_field($_POST['gmw_custom_id'] ?? '');
        $title = sanitize_text_field($_POST['gmw_custom_title'] ?? '');
        $url = esc_url_raw($_POST['gmw_custom_url'] ?? '');
        $icon = sanitize_text_field($_POST['gmw_custom_icon'] ?? '');

        if ($id && $title && $url) {
            $cfg = gmw_get_role_settings($role_key);
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
                gmw_update_role_settings($role_key, $cfg);
            }
            wp_redirect(admin_url('admin.php?page=gerenciar-menu-webi&role=' . $role_key . '&customedit=1'));
            exit;
        }
    }

    // [CUSTOM] Excluir MENU personalizado
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['gmw_delete_custom_menu_submit'])) {
        if (!current_user_can('manage_options') || !check_admin_referer('gmw_custom_menu'))
            wp_die('Permissão negada ou nonce inválido');

        $id = sanitize_text_field($_POST['gmw_custom_id'] ?? '');
        if ($id) {
            $cfg = gmw_get_role_settings($role_key);
            if (!empty($cfg['custom_menus'])) {
                $cfg['custom_menus'] = array_values(array_filter($cfg['custom_menus'], function ($cm) use ($id) {
                    return !empty($cm['id']) && $cm['id'] !== $id;
                }));
                gmw_update_role_settings($role_key, $cfg);
            }
            wp_redirect(admin_url('admin.php?page=gerenciar-menu-webi&role=' . $role_key . '&customdel=1'));
            exit;
        }
    }

    // Salvar ORDEM (menus e submenus por pai)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['gmw_menu_order']) || isset($_POST['gmw_submenu_order']))) {
        if (!current_user_can('manage_options') || !check_admin_referer('gmw_save_order'))
            wp_die('Permissão negada ou nonce inválido');

        $cfg = gmw_get_role_settings($role_key);

        if (!empty($_POST['gmw_menu_order'])) {
            $order = json_decode(stripslashes($_POST['gmw_menu_order']), true);
            if (is_array($order))
                $cfg['order_menu'] = $order;
        }
        if (!empty($_POST['gmw_submenu_order']) && is_array($_POST['gmw_submenu_order'])) {
            foreach ($_POST['gmw_submenu_order'] as $parent => $order_json) {
                $order = json_decode(stripslashes($order_json), true);
                if (is_array($order))
                    $cfg['order_submenu'][$parent] = $order;
            }
        }

        gmw_update_role_settings($role_key, $cfg);
        wp_redirect(admin_url('admin.php?page=gerenciar-menu-webi&role=' . $role_key . '&ordenado=1'));
        exit;
    }


    // Renomear MENU principal (por role)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['gmw_rename_menu_submit'])) {
        if (!current_user_can('manage_options') || !check_admin_referer('gmw_rename_menu')) {
            wp_die('Permissão negada ou nonce inválido');
        }

        $role_key = isset($_POST['gmw_role']) ? sanitize_key($_POST['gmw_role']) : '_global';
        $role_key = $role_key ?: '_global';

        $slug = sanitize_text_field($_POST['gmw_menu_slug'] ?? '');
        $novo_nome = sanitize_text_field($_POST['gmw_new_name'] ?? '');

        if ($slug && $novo_nome) {
            $cfg = gmw_get_role_settings($role_key);

            // mapeamento de renome por slug
            $cfg['rename'][$slug] = $novo_nome;

            // (opcional) se for um menu personalizado, já atualiza o título na lista de custom
            if (strpos($slug, 'gmw_custom_') === 0 && !empty($cfg['custom_menus'])) {
                foreach ($cfg['custom_menus'] as &$cm) {
                    $cm_slug = 'gmw_custom_' . sanitize_key($cm['id'] ?? '');
                    if ($cm_slug === $slug) {
                        $cm['title'] = $novo_nome;
                        break;
                    }
                }
                unset($cm);
            }

            gmw_update_role_settings($role_key, $cfg);
            wp_redirect(admin_url('admin.php?page=gerenciar-menu-webi&role=' . $role_key . '&renomeado=1'));
            exit;
        }
    }
});

/**
 * Interface — agora usando PRÉVIA por role + Menus Personalizados
 */
if (!function_exists('gmw_render_settings_page')) {
    function gmw_render_settings_page()
    {
        require_once ABSPATH . 'wp-admin/includes/user.php';
        $role_current = gmw_get_ui_role();

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
            'customdel' => 'Menu personalizado excluído!'
        ] as $param => $msg) {
            if (isset($_GET[$param]) && $_GET[$param] == '1') {
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($msg) . '</p></div>';
            }
        }

        // PRÉVIA por role (baseline + settings da role)
        $base_menu = $GLOBALS['gmw_menu_baseline'] ?? $GLOBALS['menu'];
        $base_submenu = $GLOBALS['gmw_submenu_baseline'] ?? $GLOBALS['submenu'];
        $preview_menu = $base_menu;
        $preview_submenu = $base_submenu;

        $settings_preview = gmw_get_effective_settings_for_role($role_current);
        gmw_apply_settings_to_arrays($preview_menu, $preview_submenu, $settings_preview);

        $roles = get_editable_roles();
        $cfg_role = gmw_get_role_settings($role_current);

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
                <input type="hidden" name="page" value="gerenciar-menu-webi">
                <label for="gmw_role_sel"><strong>Perfil (role):</strong></label>
                <select id="gmw_role_sel" name="role">
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
                <tbody id="gmw-menus">
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
                                    <?php wp_nonce_field('gmw_rename_menu'); ?>
                                    <input type="hidden" name="gmw_role" value="<?php echo esc_attr($role_current); ?>">
                                    <input type="hidden" name="gmw_menu_slug" value="<?php echo esc_attr($slug); ?>">
                                    <input type="text" name="gmw_new_name" placeholder="Novo nome" class="regular-text">
                                    <button type="submit" name="gmw_rename_menu_submit" class="button">Renomear</button>
                                </form>

                                <!-- Remover MENU -->
                                <form method="post" onsubmit="return confirm('Tem certeza que deseja remover este menu?');">
                                    <?php wp_nonce_field('gmw_remove_menu'); ?>
                                    <input type="hidden" name="gmw_role" value="<?php echo esc_attr($role_current); ?>">
                                    <input type="hidden" name="gmw_menu_slug_remove" value="<?php echo esc_attr($slug); ?>">
                                    <button type="submit" name="gmw_remove_menu_submit"
                                        class="button button-danger">Remover</button>
                                </form>

                                <!-- Tornar Submenu -->
                                <form method="post" style="display:inline-flex; gap:6px;">
                                    <?php wp_nonce_field('gmw_demote_menu'); ?>
                                    <input type="hidden" name="gmw_role" value="<?php echo esc_attr($role_current); ?>">
                                    <input type="hidden" name="gmw_demote_menu_slug" value="<?php echo esc_attr($slug); ?>">
                                    <select name="gmw_new_parent" required>
                                        <option value="">-- Escolher Menu Pai --</option>
                                        <?php foreach ($preview_menu as $pai): ?>
                                            <option value="<?php echo esc_attr($pai[2]); ?>"><?php echo esc_html($pai[0]); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" name="gmw_demote_menu_submit" class="button">Tornar Submenu</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- [CUSTOM] Adicionar Menu Personalizado -->
            <h2 style="margin-top:40px;">Adicionar Menu Personalizado</h2>
            <form method="post" style="display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end;">
                <?php wp_nonce_field('gmw_custom_menu'); ?>
                <input type="hidden" name="gmw_role" value="<?php echo esc_attr($role_current); ?>">
                <p>
                    <label><strong>Nome</strong><br>
                        <input type="text" name="gmw_custom_title" class="regular-text" required
                            placeholder="Ex.: Suporte Webi">
                    </label>
                </p>
                <p style="min-width:380px;">
                    <label><strong>URL</strong><br>
                        <input type="text" name="gmw_custom_url" class="regular-text" required
                            placeholder="/wp-admin/admin.php?page=...">
                        <br><small>Interna: <code>/wp-admin/...</code> ou <code>admin.php?page=...</code> — Externa:
                            <code>https://seusite.com</code> (abrirá em nova aba)</small>
                    </label>
                </p>
                <p>
                    <label><strong>Ícone (Dashicons)</strong><br>
                        <input type="text" name="gmw_custom_icon" class="regular-text" placeholder="Ex.: dashicons-admin-links">
                    </label>
                </p>
                <p>
                    <button type="submit" name="gmw_add_custom_menu_submit" class="button button-primary">Adicionar</button>
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
                            $slug = 'gmw_custom_' . sanitize_key($id);
                            ?>
                            <tr>
                                <td>
                                    <form method="post" style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
                                        <?php wp_nonce_field('gmw_custom_menu'); ?>
                                        <input type="hidden" name="gmw_role" value="<?php echo esc_attr($role_current); ?>">
                                        <input type="hidden" name="gmw_custom_id" value="<?php echo esc_attr($id); ?>">
                                        <input type="text" name="gmw_custom_title" value="<?php echo esc_attr($name); ?>"
                                            class="regular-text">
                                </td>
                                <td>
                                    <input type="text" name="gmw_custom_url" value="<?php echo esc_attr($url); ?>" class="regular-text"
                                        style="min-width:340px;">
                                </td>
                                <td>
                                    <input type="text" name="gmw_custom_icon" value="<?php echo esc_attr($icon); ?>"
                                        class="regular-text" placeholder="dashicons-...">
                                </td>
                                <td><code><?php echo esc_html($slug); ?></code></td>
                                <td style="display:flex; gap:8px;">
                                    <button type="submit" name="gmw_edit_custom_menu_submit" class="button">Salvar</button>
                                    </form>

                                    <form method="post" onsubmit="return confirm('Excluir este menu personalizado?');"
                                        style="display:inline;">
                                        <?php wp_nonce_field('gmw_custom_menu'); ?>
                                        <input type="hidden" name="gmw_role" value="<?php echo esc_attr($role_current); ?>">
                                        <input type="hidden" name="gmw_custom_id" value="<?php echo esc_attr($id); ?>">
                                        <button type="submit" name="gmw_delete_custom_menu_submit"
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
                        <tbody id="gmw-submenu-<?php echo esc_attr($parent_id); ?>"
                            data-gmw-submenu-parent="<?php echo esc_attr($menu_pai); ?>">
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
                                            <?php wp_nonce_field('gmw_rename_submenu'); ?>
                                            <input type="hidden" name="gmw_role" value="<?php echo esc_attr($role_current); ?>">
                                            <input type="hidden" name="gmw_menu_pai" value="<?php echo esc_attr($menu_pai); ?>">
                                            <input type="hidden" name="gmw_submenu_slug" value="<?php echo esc_attr($submenu_slug); ?>">
                                            <input type="text" name="gmw_submenu_new_name" placeholder="Novo nome" class="regular-text">
                                            <button type="submit" name="gmw_rename_submenu_submit" class="button">Renomear</button>
                                        </form>

                                        <!-- Remover -->
                                        <form method="post" onsubmit="return confirm('Tem certeza que deseja remover este submenu?');">
                                            <?php wp_nonce_field('gmw_remove_submenu'); ?>
                                            <input type="hidden" name="gmw_role" value="<?php echo esc_attr($role_current); ?>">
                                            <input type="hidden" name="gmw_menu_pai_remove" value="<?php echo esc_attr($menu_pai); ?>">
                                            <input type="hidden" name="gmw_submenu_slug_remove"
                                                value="<?php echo esc_attr($submenu_slug); ?>">
                                            <button type="submit" name="gmw_remove_submenu_submit"
                                                class="button button-danger">Remover</button>
                                        </form>

                                        <!-- Tornar Menu -->
                                        <form method="post">
                                            <?php wp_nonce_field('gmw_promote_submenu'); ?>
                                            <input type="hidden" name="gmw_role" value="<?php echo esc_attr($role_current); ?>">
                                            <input type="hidden" name="gmw_promote_menu_pai" value="<?php echo esc_attr($menu_pai); ?>">
                                            <input type="hidden" name="gmw_promote_submenu_slug"
                                                value="<?php echo esc_attr($submenu_slug); ?>">
                                            <input type="hidden" name="gmw_promote_submenu_nome"
                                                value="<?php echo esc_attr($submenu_nome); ?>">
                                            <button type="submit" name="gmw_promote_submenu_submit" class="button">Tornar Menu</button>
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
                                        <?php wp_nonce_field('gmw_restore_menu'); ?>
                                        <input type="hidden" name="gmw_role" value="<?php echo esc_attr($role_current); ?>">
                                        <input type="hidden" name="gmw_restore_menu_slug"
                                            value="<?php echo esc_attr($removed_slug); ?>">
                                        <button type="submit" name="gmw_restore_menu_submit" class="button">Restaurar</button>
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
                                            <?php wp_nonce_field('gmw_restore_submenu'); ?>
                                            <input type="hidden" name="gmw_role" value="<?php echo esc_attr($role_current); ?>">
                                            <input type="hidden" name="gmw_restore_menu_pai" value="<?php echo esc_attr($menu_pai); ?>">
                                            <input type="hidden" name="gmw_restore_submenu_slug"
                                                value="<?php echo esc_attr($removed_slug); ?>">
                                            <button type="submit" name="gmw_restore_submenu_submit" class="button">Restaurar</button>
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
                                        <?php wp_nonce_field('gmw_restore_demoted_menu'); ?>
                                        <input type="hidden" name="gmw_role" value="<?php echo esc_attr($role_current); ?>">
                                        <input type="hidden" name="gmw_restore_index" value="<?php echo esc_attr($index); ?>">
                                        <button type="submit" name="gmw_restore_demoted_menu_submit" class="button">Restaurar como Menu
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
                                        <?php wp_nonce_field('gmw_restore_promoted_submenu'); ?>
                                        <input type="hidden" name="gmw_role" value="<?php echo esc_attr($role_current); ?>">
                                        <input type="hidden" name="gmw_restore_index" value="<?php echo esc_attr($index); ?>">
                                        <button type="submit" name="gmw_restore_promoted_submenu_submit" class="button">Restaurar como
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
            <form method="post" id="gmw_order_form" style="display:none;">
                <?php wp_nonce_field('gmw_save_order'); ?>
                <input type="hidden" name="gmw_role" value="<?php echo esc_attr($role_current); ?>">
                <input type="hidden" name="gmw_menu_order" id="gmw_menu_order">
                <!-- gmw_submenu_order[parent] será criado via JS -->
            </form>

            <!-- SortableJS -->
            <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
            <script>
                document.addEventListener("DOMContentLoaded", function () {
                    // MENUS principais (prévia)
                    const menusEl = document.querySelector("#gmw-menus");
                    if (menusEl) {
                        new Sortable(menusEl, {
                            animation: 150,
                            onEnd: function () {
                                const order = Array.from(menusEl.querySelectorAll("tr")).map(tr => tr.dataset
                                    .slug);
                                document.getElementById("gmw_menu_order").value = JSON.stringify(order);
                                document.getElementById("gmw_order_form").submit();
                            }
                        });
                    }

                    // SUBMENUS por PAI (prévia)
                    document.querySelectorAll('tbody[data-gmw-submenu-parent]').forEach(function (tbody) {
                        new Sortable(tbody, {
                            animation: 150,
                            onEnd: function () {
                                const parent = tbody.getAttribute('data-gmw-submenu-parent');
                                const slugs = Array.from(tbody.querySelectorAll('tr')).map(tr => tr
                                    .dataset.slug);

                                // remove input anterior (se existir) para este pai
                                const sel = '#gmw_order_form input[name="gmw_submenu_order[' + CSS
                                    .escape(parent) + ']"]';
                                const old = document.querySelector(sel);
                                if (old) old.remove();

                                // cria input hidden para este pai
                                const input = document.createElement('input');
                                input.type = 'hidden';
                                input.name = 'gmw_submenu_order[' + parent + ']';
                                input.value = JSON.stringify(slugs);
                                document.getElementById('gmw_order_form').appendChild(input);

                                // envia
                                document.getElementById('gmw_order_form').submit();
                            }
                        });
                    });
                });
            </script>
        </div>
        <?php
    }
}