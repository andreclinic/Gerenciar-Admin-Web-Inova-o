<?php
defined('ABSPATH') || exit;

/**
 * [ROLE] Retorna as configurações efetivas para o usuário atual:
 * _global + sobreposição de CADA role do usuário logado.
 */
if (!function_exists('mpa_get_effective_settings_for_current_user')) {
    function mpa_get_effective_settings_for_current_user(): array
    {
        $by_role = get_option('mpa_menu_settings_roles', []);
        $global = $by_role['_global'] ?? [];

        // legado (versões antigas)
        $legacy = get_option('mpa_menu_settings', []);
        if (is_array($legacy) && $legacy) {
            $global = array_replace_recursive($global, $legacy);
        }

        $effective = $global;

        if (function_exists('wp_get_current_user')) {
            $user = wp_get_current_user();
            $roles = (array) ($user->roles ?? []);
            foreach ($roles as $r) {
                if (!empty($by_role[$r]) && is_array($by_role[$r])) {
                    $effective = array_replace_recursive($effective, $by_role[$r]);
                }
            }
        }

        return $effective;
    }
}

/**
 * [ROLE-PREVIEW] Retorna as configurações efetivas para UMA role específica
 * (global + a role pedida), para gerar a PRÉVIA da UI.
 */
if (!function_exists('mpa_get_effective_settings_for_role')) {
    function mpa_get_effective_settings_for_role(string $role): array
    {
        $by_role = get_option('mpa_menu_settings_roles', []);
        $global = $by_role['_global'] ?? [];

        $legacy = get_option('mpa_menu_settings', []);
        if (is_array($legacy) && $legacy) {
            $global = array_replace_recursive($global, $legacy);
        }

        $roleSet = $by_role[$role] ?? [];
        return array_replace_recursive($global, $roleSet);
    }
}

/**
 * [ROLE-PREVIEW] Captura baseline $menu/$submenu antes das alterações do plugin.
 */
if (!function_exists('mpa_capture_menu_baseline')) {
    function mpa_capture_menu_baseline()
    {
        global $menu, $submenu;
        if (empty($GLOBALS['mpa_menu_baseline'])) {
            $GLOBALS['mpa_menu_baseline'] = $menu;
            $GLOBALS['mpa_submenu_baseline'] = $submenu;
        }
    }
}
add_action('admin_menu', 'mpa_capture_menu_baseline', 9980);

/* ===========================
 * [CUSTOM] Helpers de URL
 * =========================== */
if (!function_exists('mpa_is_external_url')) {
    function mpa_is_external_url(string $url): bool
    {
        $url = trim($url);
        if (!preg_match('#^https?://#i', $url)) {
            return false;
        }
        $host = parse_url($url, PHP_URL_HOST);
        $siteHost = parse_url(get_site_url(), PHP_URL_HOST);
        if (!$host || !$siteHost)
            return true;
        return strcasecmp($host, $siteHost) !== 0;
    }
}

if (!function_exists('mpa_normalize_local_admin_url')) {
    function mpa_normalize_local_admin_url(string $url): string
    {
        $url = trim($url);

        // URL absoluta interna? deixa como está
        if (preg_match('#^https?://#i', $url) && !mpa_is_external_url($url)) {
            return $url;
        }

        // começa com /wp-admin/ -> vira admin_url(relative)
        if (strpos($url, '/wp-admin/') === 0) {
            $relative = ltrim(substr($url, strlen('/wp-admin/')), '/');
            return admin_url($relative);
        }

        // começa com arquivo do admin ou rota conhecida
        if (preg_match('#^(admin\.php|index\.php|options-.*\.php|edit\.php|upload\.php|tools\.php|users\.php|themes\.php|plugins\.php|.*\.php)(\?.*)?$#i', $url)) {
            return admin_url($url);
        }

        // começa com ?page=...
        if (strpos($url, '?') === 0) {
            return admin_url('admin.php' . $url);
        }

        // fallback: trata como relativo ao admin
        return admin_url($url);
    }
}

/**
 * [ROLE-PREVIEW] Aplica settings em arrays (cópias) para pré-visualização.
 * Agora inclui injeção de MENUS PERSONALIZADOS.
 */
if (!function_exists('mpa_apply_settings_to_arrays')) {
    function mpa_apply_settings_to_arrays(array &$menu, array &$submenu, array $settings): void
    {

        // 0) [CUSTOM] Injetar MENUS PERSONALIZADOS como menus principais
        if (!empty($settings['custom_menus']) && is_array($settings['custom_menus'])) {
            foreach ($settings['custom_menus'] as $cm) {
                $id = $cm['id'] ?? '';
                $title = $cm['title'] ?? '';
                $url = $cm['url'] ?? '';
                if (!$id || !$title || !$url)
                    continue;

                $icon = $cm['icon'] ?? 'dashicons-admin-links';
                $pos = $cm['pos'] ?? 82;
                $slug = 'mpa_custom_' . sanitize_key($id);

                // Estrutura mínima do $menu (preview)
                $menu[] = [
                    $title,              // 0: Menu title
                    'manage_options',    // 1: Capability
                    $slug,               // 2: Slug (usado para reorder/demove etc.)
                    $title,              // 3: Page title (preview)
                    '',                  // 4
                    '',                  // 5
                    $icon,               // 6: Ícone (dashicons-*)
                    $pos                 // 7: posição (apenas referência)
                ];
            }
        }

        // 1) Renomear MENUS
        if (!empty($settings['rename'])) {
            foreach ($menu as $key => $item) {
                if (!empty($item[2]) && isset($settings['rename'][$item[2]])) {
                    $menu[$key][0] = $settings['rename'][$item[2]];
                }
            }
        }

        // 2) Renomear SUBMENUS
        if (!empty($settings['rename_submenu']) && is_array($settings['rename_submenu'])) {
            foreach ($settings['rename_submenu'] as $menu_pai => $itens) {
                if (!empty($submenu[$menu_pai])) {
                    foreach ($itens as $submenu_slug => $novo_nome) {
                        foreach ($submenu[$menu_pai] as $index => $subitem) {
                            if (!empty($subitem[2]) && $subitem[2] === $submenu_slug) {
                                $submenu[$menu_pai][$index][0] = $novo_nome;
                            }
                        }
                    }
                }
            }
        }

        // 3) Remover MENUS
        if (!empty($settings['remove'])) {
            foreach ($menu as $idx => $item) {
                if (!empty($item[2]) && in_array($item[2], $settings['remove'], true)) {
                    unset($menu[$idx]);
                }
            }
            $menu = array_values($menu);
        }

        // 4) Remover SUBMENUS
        if (!empty($settings['remove_submenu']) && is_array($settings['remove_submenu'])) {
            foreach ($settings['remove_submenu'] as $menu_pai => $itens) {
                if (!empty($submenu[$menu_pai])) {
                    foreach ($submenu[$menu_pai] as $idx => $subitem) {
                        if (!empty($subitem[2]) && in_array($subitem[2], $itens, true)) {
                            unset($submenu[$menu_pai][$idx]);
                        }
                    }
                    $submenu[$menu_pai] = array_values($submenu[$menu_pai]);
                }
            }
        }

        // 5) Promover SUBMENU -> MENU
        if (!empty($settings['promote_submenu'])) {
            foreach ($settings['promote_submenu'] as $promoted) {
                $parent = $promoted['parent'] ?? '';
                $slug = $promoted['slug'] ?? '';
                $name = $promoted['name'] ?? '';
                $icon = $promoted['icon'] ?? 'dashicons-admin-generic';
                $pos = $promoted['pos'] ?? 80;

                // Verificar se há um ícone atualizado na configuração 'icons'
                if (!empty($settings['icons'][$slug])) {
                    $icon = $settings['icons'][$slug];
                }

                // Verificar se há um nome atualizado na configuração 'rename'
                if (!empty($settings['rename'][$slug])) {
                    $name = $settings['rename'][$slug];
                }

                if ($parent && $slug) {
                    if (!empty($submenu[$parent])) {
                        foreach ($submenu[$parent] as $i => $sub) {
                            if (!empty($sub[2]) && $sub[2] === $slug) {
                                unset($submenu[$parent][$i]);
                            }
                        }
                        $submenu[$parent] = array_values($submenu[$parent]);
                    }
                    $menu[] = [$name, 'manage_options', $slug, $name, '', '', $icon, $pos];
                }
            }
            // preview: ordena por "pos" se existir
            usort($menu, function ($a, $b) {
                $pa = $a[7] ?? 9999;
                $pb = $b[7] ?? 9999;
                return $pa <=> $pb;
            });
        }

        // 6) Demover MENU -> SUBMENU
        if (!empty($settings['demote_menu'])) {
            foreach ($settings['demote_menu'] as $demoted) {
                $slug = $demoted['slug'] ?? '';
                $parent = $demoted['parent'] ?? '';
                if ($slug && $parent) {
                    foreach ($menu as $idx => $item) {
                        if (!empty($item[2]) && $item[2] === $slug) {
                            $menu_name = $item[0];
                            $capability = $item[1] ?? 'manage_options';
                            unset($menu[$idx]);
                            $menu = array_values($menu);
                            $submenu[$parent][] = [$menu_name, $capability, $slug];
                            break;
                        }
                    }
                }
            }
        }

        // 7) Aplicar ÍCONES personalizados
        if (!empty($settings['icons']) && is_array($settings['icons'])) {
            foreach ($menu as &$item) {
                $slug = $item[2] ?? '';
                if ($slug && isset($settings['icons'][$slug])) {
                    $item[6] = $settings['icons'][$slug];
                }
            }
            unset($item); // Limpar referência
        }

        // 8) Reordenar MENUS
        if (!empty($settings['order_menu']) && is_array($settings['order_menu'])) {
            $ordered = [];
            foreach ($settings['order_menu'] as $slugWanted) {
                foreach ($menu as $item) {
                    if (!empty($item[2]) && $item[2] === $slugWanted) {
                        $ordered[] = $item;
                        break;
                    }
                }
            }
            foreach ($menu as $item) {
                if (!in_array($item[2] ?? null, $settings['order_menu'], true)) {
                    $ordered[] = $item;
                }
            }
            $menu = $ordered;
        }

        // 9) Reordenar SUBMENUS por PAI
        if (!empty($settings['order_submenu']) && is_array($settings['order_submenu'])) {
            foreach ($settings['order_submenu'] as $parent => $slugs) {
                if (!empty($submenu[$parent]) && is_array($submenu[$parent])) {
                    $ordered = [];
                    foreach ($slugs as $slug) {
                        foreach ($submenu[$parent] as $subitem) {
                            if (!empty($subitem[2]) && $subitem[2] === $slug) {
                                $ordered[] = $subitem;
                                break;
                            }
                        }
                    }
                    foreach ($submenu[$parent] as $subitem) {
                        if (!in_array($subitem[2], $slugs, true)) {
                            $ordered[] = $subitem;
                        }
                    }
                    $submenu[$parent] = $ordered;
                }
            }
        }
    }
}

/**
 * [CUSTOM] Callback genérico para páginas de menus personalizados:
 * - Se for URL interna: redireciona.
 * - Se for externa: mostra um botão para abrir em nova aba (fallback).
 */
// Verificação de capabilities do Rank Math removida - gerenciada por plugin específico

if (!function_exists('mpa_custom_menu_router')) {
    function mpa_custom_menu_router()
    {
        $slug = isset($_GET['page']) ? sanitize_key($_GET['page']) : '';

        // PROTEÇÃO: Nunca processar páginas do Rank Math como menus personalizados
        if (strpos($slug, 'rank-math') !== false) {
            return; // Deixar o WordPress/Rank Math processar normalmente
        }

        $map = $GLOBALS['mpa_custom_links_map'] ?? [];
        $item = $map[$slug] ?? null;

        if (!$item) {
            echo '<div class="wrap"><h1>Menu personalizado</h1><p>Link não configurado.</p></div>';
            return;
        }

        $url = $item['url'];
        if (!empty($item['external'])) {
            // Para links externos: redireciona diretamente
            wp_safe_redirect($url);
            exit;
        }

        // Interno: redireciona
        wp_safe_redirect($url);
        exit;
    }
}

/**
 * SISTEMA ATIVO DE GERENCIAMENTO DE MENUS POR ROLE
 * Aplica todas as configurações: custom menus, rename, remove, promote, demote, order
 * Com proteções para administradores e Rank Math SEO
 */


add_action('admin_menu', function () {
    global $menu, $submenu;

    // EXCEÇÕES: Administradores têm acesso completo
    $current_user = wp_get_current_user();
    $user_roles = (array) $current_user->roles;

    if (in_array('administrator', $user_roles)) {
        return; // Administradores têm acesso completo
    }

    $settings = mpa_get_effective_settings_for_current_user();

    /* [CUSTOM] Injetar MENUS PERSONALIZADOS reais via add_menu_page */
    $GLOBALS['mpa_custom_links_map'] = $GLOBALS['mpa_custom_links_map'] ?? [];
    if (!empty($settings['custom_menus']) && is_array($settings['custom_menus'])) {
        foreach ($settings['custom_menus'] as $cm) {
            $id = $cm['id'] ?? '';
            $title = $cm['title'] ?? '';
            $url = $cm['url'] ?? '';
            if (!$id || !$title || !$url)
                continue;

            // PROTEÇÃO: Nunca criar menus personalizados que conflitem com Rank Math
            if (strpos($id, 'rank-math') !== false || strpos($title, 'rank-math') !== false) {
                continue;
            }

            $icon = $cm['icon'] ?? 'dashicons-admin-links';
            $pos = $cm['pos'] ?? 82;
            $slug = 'mpa_custom_' . sanitize_key($id);

            $is_external = mpa_is_external_url($url);
            $finalUrl = $is_external ? $url : mpa_normalize_local_admin_url($url);

            $GLOBALS['mpa_custom_links_map'][$slug] = [
                'url' => $finalUrl,
                'external' => $is_external,
                'title' => $title,
            ];

            add_menu_page(
                $title,
                $title,
                'manage_options',
                $slug,
                'mpa_custom_menu_router',
                $icon,
                $pos
            );
        }
    }

    /* === PIPELINE COMPLETO (rename/remove/promote/demote/order) === */

    // 1) Renomear MENUS
    if (!empty($settings['rename'])) {
        foreach ($menu as $key => $item) {
            if (!empty($item[2]) && isset($settings['rename'][$item[2]])) {
                $menu[$key][0] = $settings['rename'][$item[2]];
            }
        }
    }

    // 2) Renomear SUBMENUS
    if (!empty($settings['rename_submenu']) && is_array($settings['rename_submenu'])) {
        foreach ($settings['rename_submenu'] as $menu_pai => $itens) {
            if (!empty($submenu[$menu_pai])) {
                foreach ($itens as $submenu_slug => $novo_nome) {
                    foreach ($submenu[$menu_pai] as $index => $subitem) {
                        if (!empty($subitem[2]) && $subitem[2] === $submenu_slug) {
                            $submenu[$menu_pai][$index][0] = $novo_nome;
                        }
                    }
                }
            }
        }
    }

    // 2.5) Aplicar ÍCONES personalizados
    if (!empty($settings['icons']) && is_array($settings['icons'])) {
        foreach ($menu as $key => $item) {
            if (!empty($item[2]) && isset($settings['icons'][$item[2]])) {
                $menu[$key][6] = $settings['icons'][$item[2]];
            }
        }
    }

    // 3) Remover MENUS
    if (!empty($settings['remove'])) {
        foreach ($settings['remove'] as $slug) {
            if ($slug === 'mpa-config-analytics') {
                continue;
            }
            remove_menu_page($slug);
        }
    }

    // 4) Remover SUBMENUS
    if (!empty($settings['remove_submenu']) && is_array($settings['remove_submenu'])) {
        foreach ($settings['remove_submenu'] as $menu_pai => $itens) {
            foreach ($itens as $submenu_slug) {
                if ($submenu_slug === 'mpa-config-analytics') {
                    continue;
                }
                remove_submenu_page($menu_pai, $submenu_slug);
            }
        }
    }

    // 5) Promover SUBMENU -> MENU
    if (!empty($settings['promote_submenu'])) {
        foreach ($settings['promote_submenu'] as $promoted) {
            $parent = $promoted['parent'] ?? '';
            $slug = $promoted['slug'] ?? '';
            $name = $promoted['name'] ?? '';
            $icon = $promoted['icon'] ?? 'dashicons-admin-generic';
            $pos = $promoted['pos'] ?? 80;

            // Verificar se há um ícone atualizado na configuração 'icons'
            if (!empty($settings['icons'][$slug])) {
                $icon = $settings['icons'][$slug];
            }

            // Verificar se há um nome atualizado na configuração 'rename'
            if (!empty($settings['rename'][$slug])) {
                $name = $settings['rename'][$slug];
            }

            if ($parent && $slug) {
                if (!empty($submenu[$parent])) {
                    foreach ($submenu[$parent] as $i => $sub) {
                        if (!empty($sub[2]) && $sub[2] === $slug) {
                            unset($submenu[$parent][$i]);
                        }
                    }
                    $submenu[$parent] = array_values($submenu[$parent]); // reindexa
                }

                add_menu_page($name, $name, 'manage_options', $slug, '', $icon, $pos);
            }
        }
    }

    // 6) Demover MENU -> SUBMENU
    if (!empty($settings['demote_menu'])) {
        foreach ($settings['demote_menu'] as $demoted) {
            $slug = $demoted['slug'] ?? '';
            $parent = $demoted['parent'] ?? '';
            if ($slug && $parent) {
                foreach ($menu as $idx => $item) {
                    if (!empty($item[2]) && $item[2] === $slug) {
                        $menu_name = $item[0];
                        $capability = $item[1] ?? 'manage_options';
                        unset($menu[$idx]);
                        $menu = array_values($menu);

                        $submenu[$parent][] = [$menu_name, $capability, $slug];
                        break;
                    }
                }
            }
        }
    }

    // 7) Reordenar MENUS
    if (!empty($settings['order_menu']) && is_array($settings['order_menu'])) {
        $ordered = [];
        foreach ($settings['order_menu'] as $slugWanted) {
            foreach ($menu as $item) {
                if (!empty($item[2]) && $item[2] === $slugWanted) {
                    $ordered[] = $item;
                    break;
                }
            }
        }
        foreach ($menu as $item) {
            if (!in_array($item[2] ?? null, $settings['order_menu'], true)) {
                $ordered[] = $item;
            }
        }
        $menu = $ordered;
    }

    // 8) Reordenar SUBMENUS por PAI
    if (!empty($settings['order_submenu']) && is_array($settings['order_submenu'])) {
        foreach ($settings['order_submenu'] as $parent => $slugs) {
            if (!empty($submenu[$parent]) && is_array($submenu[$parent])) {
                $ordered = [];
                foreach ($slugs as $slug) {
                    foreach ($submenu[$parent] as $subitem) {
                        if (!empty($subitem[2]) && $subitem[2] === $slug) {
                            $ordered[] = $subitem;
                            break;
                        }
                    }
                }
                foreach ($submenu[$parent] as $subitem) {
                    if (!in_array($subitem[2], $slugs, true)) {
                        $ordered[] = $subitem;
                    }
                }
                $submenu[$parent] = $ordered;
            }
        }
    }

    /* SISTEMA ATUALIZADO - removemos restrições antigas que entravam em conflito */
}, 9999);