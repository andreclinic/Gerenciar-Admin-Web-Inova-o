<?php
// Carregar os arquivos CSS e JS corretamente (verificar compatibilidade no hook)
add_action('admin_enqueue_scripts', 'mpa_adminmenumain_assets');

function mpa_adminmenumain_assets($hook)
{
    // Carregar apenas nas páginas do admin
    if (!is_admin())
        return;

    wp_enqueue_style(
        'mpa-adminmenumain-css',
        ADMIN_BAR_MENU_URL . 'assets/css/mpa-adminmenumain.css'
    );

    wp_enqueue_script(
        'mpa-adminmenumain-js',
        ADMIN_BAR_MENU_URL . 'assets/js/mpa-adminmenumain.js',
        ['jquery'],
        null,
        true
    );

    // Localizar variáveis AJAX para o script do sidebar
    wp_localize_script('mpa-adminmenumain-js', 'mpaDragDropVars', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('mpa_transform_submenu')
    ));
}

// Implementar sidebar dinâmico baseado nos menus do WordPress
// Renderizar após o processamento completo dos menus (prioridade 9999)
add_action('in_admin_header', 'mpa_render_dynamic_sidebar', 99999);

function mpa_render_dynamic_sidebar()
{
    global $menu, $submenu;

    // Obter restrições de menu para o usuário atual
    $user_restrictions = mpa_get_user_menu_restrictions();

    // Debug temporário
    if (isset($_GET['debug_sidebar_restrictions']) && current_user_can('edit_posts')) {
        echo '<div style="position: fixed; top: 10px; right: 10px; background: red; color: white; padding: 10px; z-index: 9999; max-width: 300px;">';
        echo '<h4>Debug Sidebar</h4>';
        $user = wp_get_current_user();
        echo '<p>User: ' . $user->user_login . '</p>';
        echo '<p>Roles: ' . implode(', ', $user->roles) . '</p>';
        echo '<p>Restrições: <pre>' . print_r($user_restrictions, true) . '</pre></p>';
        echo '<button onclick="this.parentElement.style.display=\'none\'">X</button>';
        echo '</div>';
    }

    ?>
<div id="mpa-sidebar-overlay" class="mpa-sidebar-overlay"></div>

<aside id="mpa-sidebar" class="mpa-sidebar">
    <div class="mpa-sidebar-section">
        <h3 class="mpa-sidebar-title">Menu Principal</h3>
    </div>

    <nav class="mpa-sidebar-nav">
        <?php
            $current_screen = get_current_screen();
            $current_page = isset($_GET['page']) ? $_GET['page'] : '';


            foreach ($menu as $menu_item) {
                if (empty($menu_item[0]) || $menu_item[0] == 'separator')
                    continue;

                $menu_file = $menu_item[2];


                // Pular se usuário não tem permissão
                if (!current_user_can($menu_item[1]))
                    continue;

                // NOVA VERIFICAÇÃO: Pular se menu está restrito por role
                if (mpa_is_menu_restricted($menu_file, $user_restrictions))
                    continue;
                $menu_title = wp_strip_all_tags($menu_item[0]);
                $menu_icon = mpa_get_menu_icon($menu_item[6]);

                // Verificar se é o item ativo
                $is_active = mpa_is_menu_active($menu_file, $current_screen, $current_page);

                // Verificar se tem submenus
                $has_submenu = isset($submenu[$menu_file]) && count($submenu[$menu_file]) > 0;

                ?>
        <div class="mpa-nav-item-container" data-menu="<?php echo esc_attr($menu_file); ?>">
            <a href="<?php
                    // Construir URL corretamente para o menu principal
                    if (strpos($menu_file, '.php') !== false) {
                        // Arquivo direto do WordPress como index.php, edit.php, etc.
                        echo esc_url(admin_url($menu_file));
                    } elseif (strpos($menu_file, 'http') === 0) {
                        // URL externa completa - não usar, forçar admin.php?page=
                        $page_name = basename(parse_url($menu_file, PHP_URL_QUERY));
                        if (strpos($page_name, 'page=') !== false) {
                            $page_name = str_replace('page=', '', $page_name);
                        }
                        $url = admin_url('admin.php?page=' . $page_name);
                        if (strpos($page_name, 'jet-smart-filters') !== false && strpos($url, '#/') === false) {
                            $url .= '#/';
                        }
                        echo esc_url($url);
                    } else {
                        // Página de plugin/tema - usar admin.php?page=
                        $url = admin_url('admin.php?page=' . $menu_file);

                        // Fix específico para jet-smart-filters
                        if (strpos($menu_file, 'jet-smart-filters') !== false && strpos($url, '#/') === false) {
                            $url .= '#/';
                        }

                        echo esc_url($url);
                    }
                    ?>" class="mpa-nav-item <?php echo $is_active ? 'active' : ''; ?>"
                data-page="<?php echo esc_attr($menu_file); ?>">
                <div class="mpa-nav-icon">
                    <?php echo $menu_icon; ?>
                </div>
                <div class="mpa-nav-content">
                    <h3><?php echo esc_html($menu_title); ?></h3>
                    <p><?php echo mpa_get_menu_description($menu_file); ?></p>
                </div>
                <?php if ($has_submenu): ?>
                <div class="mpa-nav-arrow">
                    <svg class="mpa-icon mpa-arrow-icon" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                            clip-rule="evenodd" />
                    </svg>
                </div>
                <?php endif; ?>
            </a>

            <?php
                    // Verificar se tem submenus (nativos ou personalizados)
                    $menu_customizations = get_option('mpa_menu_customizations', array());
                    $has_custom_submenus = false;

                    if (isset($menu_customizations['submenu_custom_data'])) {
                        foreach ($menu_customizations['submenu_custom_data'] as $submenu_key => $submenu_data) {
                            list($parent_slug, $child_slug) = explode('|', $submenu_key, 2);

                            // Comparação mais flexível para matching
                            $match = false;
                            if ($parent_slug === $menu_file) {
                                $match = true;
                            } elseif ($parent_slug === str_replace('.php', '', $menu_file)) {
                                $match = true;
                            } elseif ($parent_slug . '.php' === $menu_file) {
                                $match = true;
                            }

                            if ($match) {
                                $has_custom_submenus = true;
                                break;
                            }
                        }
                    }

                    if ($has_submenu || $has_custom_submenus): ?>
            <div class="mpa-submenu <?php echo $is_active ? 'active' : ''; ?>">
                <?php
                            // Renderizar submenus nativos
                            if ($has_submenu) {
                                foreach ($submenu[$menu_file] as $submenu_item) {
                                    if (!current_user_can($submenu_item[1]))
                                        continue;

                                    $submenu_title = wp_strip_all_tags($submenu_item[0]);
                                    $submenu_file = $submenu_item[2];

                                    // NOVA VERIFICAÇÃO: Pular se submenu está restrito por role
                                    if (mpa_is_submenu_restricted($menu_file, $submenu_file, $user_restrictions))
                                        continue;

                                    // Construir URL do submenu corretamente
                                    if (strpos($submenu_file, '.php') !== false) {
                                        // Arquivo direto como edit.php, users.php, etc.
                                        $submenu_url = admin_url($submenu_file);
                                    } elseif (strpos($submenu_file, 'http') === 0) {
                                        // URL externa completa
                                        $submenu_url = $submenu_file;
                                    } else {
                                        // Página de plugin/tema - sempre usar admin.php?page=
                                        $submenu_url = admin_url('admin.php?page=' . $submenu_file);
                                    }

                                    $is_submenu_active = ($current_page === $submenu_file) ||
                                        (strpos($submenu_file, '.php') !== false && $current_screen->id === str_replace('.php', '', $submenu_file));

                                    ?>
                <a href="<?php echo esc_url($submenu_url); ?>"
                    class="mpa-submenu-item <?php echo $is_submenu_active ? 'active' : ''; ?>"
                    data-menu-slug="<?php echo esc_attr($submenu_file); ?>"
                    data-parent-slug="<?php echo esc_attr($menu_file); ?>">
                    <span><?php echo esc_html($submenu_title); ?></span>
                </a>
                <?php
                                }
                            }

                            // Renderizar submenus personalizados
                            if (isset($menu_customizations['submenu_custom_data'])) {
                                foreach ($menu_customizations['submenu_custom_data'] as $submenu_key => $submenu_data) {
                                    list($parent_slug, $child_slug) = explode('|', $submenu_key, 2);

                                    // Usar a mesma lógica de matching flexível
                                    $match = false;
                                    if ($parent_slug === $menu_file) {
                                        $match = true;
                                    } elseif ($parent_slug === str_replace('.php', '', $menu_file)) {
                                        $match = true;
                                    } elseif ($parent_slug . '.php' === $menu_file) {
                                        $match = true;
                                    }

                                    if ($match) {
                                        $submenu_title = $submenu_data['title'];
                                        $submenu_url = $submenu_data['url'];

                                        $is_submenu_active = ($current_page === $child_slug);

                                        ?>
                <a href="<?php echo esc_url($submenu_url); ?>"
                    class="mpa-submenu-item mpa-custom-submenu <?php echo $is_submenu_active ? 'active' : ''; ?>"
                    data-menu-slug="<?php echo esc_attr($child_slug); ?>"
                    data-parent-slug="<?php echo esc_attr($parent_slug); ?>" target="_blank">
                    <span><?php echo esc_html($submenu_title); ?></span>
                    <span class="mpa-custom-indicator">🔗</span>
                </a>
                <?php
                                    }
                                }
                            }
                            ?>
            </div>
            <?php endif; ?>
        </div>
        <?php
            }
            ?>
    </nav>
</aside>
<?php
}

// Função para obter ícone do menu
function mpa_get_menu_icon($icon)
{
    if (strpos($icon, 'dashicons-') !== false) {
        return '<span class="dashicons ' . esc_attr($icon) . '"></span>';
    }

    // Ícones padrão baseados no tipo de menu
    $default_icons = [
        'index.php' => '<svg class="mpa-icon" viewBox="0 0 20 20" fill="currentColor"><path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"/></svg>',
        'edit.php' => '<svg class="mpa-icon" viewBox="0 0 20 20" fill="currentColor"><path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/></svg>',
        'users.php' => '<svg class="mpa-icon" viewBox="0 0 20 20" fill="currentColor"><path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3z"/></svg>',
        'themes.php' => '<svg class="mpa-icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4 2a2 2 0 00-2 2v11a3 3 0 106 0V4a2 2 0 00-2-2H4z" clip-rule="evenodd"/></svg>',
        'plugins.php' => '<svg class="mpa-icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.395 2.553a1 1 0 00-1.45-.385c-.345.23-.614.558-.822.88-.214.33-.403.713-.57 1.116-.334.804-.614 1.768-.84 2.734a31.365 31.365 0 00-.613 3.58 2.64 2.64 0 01-.945-1.067c-.328-.68-.398-1.534-.398-2.654A1 1 0 005.05 6.05 6.981 6.981 0 003 11a7 7 0 1011.95-4.95c-.592-.591-.98-.985-1.348-1.467-.363-.476-.724-1.063-1.207-2.03zM12.12 15.12A3 3 0 017 13s.879.5 2.5.5c0-1 .5-4 1.25-4.5.5 1 .786 1.293 1.371 1.879A2.99 2.99 0 0113 13a2.99 2.99 0 01-.879 2.121z" clip-rule="evenodd"/></svg>',
        'options-general.php' => '<svg class="mpa-icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"/></svg>'
    ];

    $menu_file = is_array($icon) ? $icon[0] : $icon;

    return isset($default_icons[$menu_file]) ? $default_icons[$menu_file] : '<svg class="mpa-icon" viewBox="0 0 20 20" fill="currentColor"><path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/><path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/></svg>';
}

// Função para verificar se o menu está ativo
function mpa_is_menu_active($menu_file, $current_screen, $current_page)
{
    if ($current_screen->parent_file === $menu_file)
        return true;
    if ($current_screen->id === str_replace('.php', '', $menu_file))
        return true;
    if ($current_page && strpos($menu_file, $current_page) !== false)
        return true;

    return false;
}

// Função para obter descrição do menu
function mpa_get_menu_description($menu_file)
{
    $descriptions = [
        'index.php' => 'Visão geral',
        'edit.php' => 'Gerenciar posts',
        'upload.php' => 'Biblioteca de mídia',
        'edit.php?post_type=page' => 'Gerenciar páginas',
        'edit-comments.php' => 'Moderar comentários',
        'themes.php' => 'Personalizar site',
        'plugins.php' => 'Gerenciar plugins',
        'users.php' => 'Gerenciar usuários',
        'tools.php' => 'Ferramentas úteis',
        'options-general.php' => 'Configurações gerais'
    ];

    return isset($descriptions[$menu_file]) ? $descriptions[$menu_file] : 'Acessar seção';
}

// Esconder o menu padrão do WordPress
add_action('admin_head', function () {
    echo '<style>
        #adminmenuback, #adminmenuwrap, #adminmenu { display: none !important; }
        #wpcontent { margin-left: 0 !important; }
    </style>';
});

// Dashboard será adicionado como submenu de "Gerenciar Admin" no mpa-menu-manager.php

// NOVAS FUNÇÕES PARA CONTROLE DE RESTRIÇÕES DE MENU

function mpa_get_user_menu_restrictions()
{
    // Administradores e gerentes não têm restrições
    $user = wp_get_current_user();
    if (in_array('administrator', (array) $user->roles, true) || in_array('gerentes', (array) $user->roles, true)) {
        return array();
    }

    // Usar o novo sistema de configurações
    if (function_exists('mpa_get_effective_settings_for_current_user')) {
        $settings = mpa_get_effective_settings_for_current_user();

        $restricted_menus = $settings['remove'] ?? array();
        $restricted_submenus = array();

        // Converter submenu removidos para o formato esperado
        if (!empty($settings['remove_submenu']) && is_array($settings['remove_submenu'])) {
            foreach ($settings['remove_submenu'] as $parent_slug => $submenu_slugs) {
                foreach ($submenu_slugs as $submenu_slug) {
                    $restricted_submenus[] = $parent_slug . '|' . $submenu_slug;
                }
            }
        }

        return array(
            'menus' => array_unique($restricted_menus),
            'submenus' => array_unique($restricted_submenus)
        );
    }

    // Fallback para sistema antigo (se função não existir)
    $user_roles = $user->roles;
    if (empty($user_roles))
        return array();

    $menu_permissions = get_option('mpa_menu_permissions', array());
    $restricted_menus = array();
    $restricted_submenus = array();

    foreach ($user_roles as $role) {
        if (!isset($menu_permissions[$role]))
            continue;
        $role_permissions = $menu_permissions[$role];

        foreach ($role_permissions as $menu_slug => $permission) {
            if ($menu_slug === 'submenus')
                continue;
            if ($permission === false) {
                $restricted_menus[] = $menu_slug;
            }
        }

        if (isset($role_permissions['submenus']) && is_array($role_permissions['submenus'])) {
            foreach ($role_permissions['submenus'] as $submenu_key => $permission) {
                if ($permission === false) {
                    $restricted_submenus[] = $submenu_key;
                }
            }
        }
    }

    return array(
        'menus' => array_unique($restricted_menus),
        'submenus' => array_unique($restricted_submenus)
    );
}

function mpa_is_menu_restricted($menu_slug, $user_restrictions)
{
    if (empty($user_restrictions['menus']))
        return false;

    return in_array($menu_slug, $user_restrictions['menus']);
}

function mpa_is_submenu_restricted($parent_slug, $submenu_slug, $user_restrictions)
{
    if (empty($user_restrictions['submenus']))
        return false;

    $submenu_key = $parent_slug . '|' . $submenu_slug;
    return in_array($submenu_key, $user_restrictions['submenus']);
}

// Função para filtrar o menu principal do WordPress baseado nas permissões de role
function mpa_filtrar_menu_principal_por_role()
{
    global $menu;

    $user = wp_get_current_user();
    if (in_array('administrator', (array) $user->roles, true) || in_array('gerentes', (array) $user->roles, true)) {
        return; // Administradores e gerentes não têm restrições
    }

    $user = wp_get_current_user();
    $roles = (array) $user->roles;
    $permissoes = get_option('mpa_menu_permissions', []);

    // Nada a fazer se não houver roles ou permissões salvas
    if (empty($roles) || empty($permissoes))
        return;

    foreach ($menu as $key => $item) {
        $slug_raw = $item[2] ?? '';
        $slug_normalizado = mpa_normalize_slug($slug_raw);

        // Usa a função que já verifica permissões corretamente
        if (!mpa_user_can_see_menu($slug_normalizado, $roles, $permissoes)) {
            unset($menu[$key]);
        }
    }
}
// ❌ DESATIVADO: Conflita com novo sistema avançado de menus por role
// A função mpa_filtrar_menu_principal_por_role estava sendo executada com prioridade 999
// e conflitava com o novo sistema de gerenciamento de menus que usa prioridade 9999
// add_action('admin_menu', 'mpa_filtrar_menu_principal_por_role', 999);

add_action('admin_head', function () {
    if (!current_user_can('administrator')) {
        echo '<style>
            a.mpa-submenu-item[data-menu-slug="wc-admin"] {
                display: none !important;
            }
        </style>';
    }
});