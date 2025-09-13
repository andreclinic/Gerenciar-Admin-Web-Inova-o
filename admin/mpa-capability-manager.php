<?php
// Gerenciador de Capabilities por Role

// Adicionar submenu para gerenciar capabilities
add_action('admin_menu', 'mpa_add_capability_menu');

function mpa_add_capability_menu() {
    // Verificar se o usuário é administrador
    if (!current_user_can('manage_options')) {
        return;
    }

    // Submenu para gerenciar capabilities por role
    add_submenu_page(
        'mpa-main',                     // Parent slug
        'Gerenciar Capabilities',       // Page title
        'Capabilities',                 // Menu title
        'manage_options',               // Capability
        'mpa-capabilities',             // Menu slug
        'mpa_capabilities_page'         // Function
    );
}

// Função para obter todas as capabilities possíveis
function mpa_get_all_capabilities() {
    global $wp_roles;

    // Capabilities conhecidas por plugin
    $plugin_capabilities = [
        'WordPress Core' => [
            'read', 'edit_posts', 'edit_others_posts', 'edit_published_posts', 'publish_posts',
            'delete_posts', 'delete_others_posts', 'delete_published_posts', 'delete_private_posts',
            'edit_private_posts', 'read_private_posts', 'manage_categories', 'manage_links',
            'upload_files', 'import', 'unfiltered_html', 'edit_comments', 'moderate_comments',
            'manage_options', 'activate_plugins', 'edit_plugins', 'install_plugins', 'delete_plugins',
            'update_plugins', 'edit_themes', 'install_themes', 'update_themes', 'delete_themes',
            'switch_themes', 'edit_theme_options', 'edit_users', 'list_users', 'create_users',
            'delete_users', 'promote_users', 'remove_users', 'add_users', 'edit_dashboard',
            'update_core', 'manage_network', 'manage_sites', 'manage_network_users',
            'manage_network_themes', 'manage_network_plugins', 'manage_network_options'
        ],
        'WooCommerce' => [
            'manage_woocommerce', 'view_woocommerce_reports', 'edit_product', 'read_product',
            'delete_product', 'edit_products', 'edit_others_products', 'publish_products',
            'read_private_products', 'delete_products', 'delete_private_products',
            'delete_published_products', 'delete_others_products', 'edit_private_products',
            'edit_published_products', 'create_products', 'assign_product_terms',
            'edit_product_terms', 'delete_product_terms', 'manage_product_terms',
            'edit_shop_order', 'read_shop_order', 'delete_shop_order', 'edit_shop_orders',
            'edit_others_shop_orders', 'publish_shop_orders', 'read_private_shop_orders',
            'delete_shop_orders', 'delete_private_shop_orders', 'delete_published_shop_orders',
            'delete_others_shop_orders', 'edit_private_shop_orders', 'edit_published_shop_orders',
            'create_shop_orders', 'edit_shop_coupon', 'read_shop_coupon', 'delete_shop_coupon',
            'edit_shop_coupons', 'edit_others_shop_coupons', 'publish_shop_coupons',
            'read_private_shop_coupons', 'delete_shop_coupons', 'delete_private_shop_coupons',
            'delete_published_shop_coupons', 'delete_others_shop_coupons',
            'edit_private_shop_coupons', 'edit_published_shop_coupons', 'create_shop_coupons',
            'manage_woocommerce_orders', 'view_admin_dashboard', 'wc_admin_dashboard_access',
            'manage_woocommerce_customers', 'view_woocommerce_customers', 'edit_woocommerce_customers',
            'woocommerce_view_customers', 'woocommerce_manage_customers', 'woocommerce_analytics_access',
            'woocommerce_analytics_customers', 'wc_admin_read', 'wc_admin_edit', 'woocommerce_admin'
        ],
        'JetEngine' => [
            'jet_engine_dashboard_access', 'jet_engine_manage_options', 'jet_engine_edit_listings',
            'jet_engine_edit_meta_boxes', 'jet_engine_edit_custom_content_types',
            'jet_engine_edit_profile_builder', 'jet_engine_edit_relations',
            'jet_engine_edit_glossaries', 'jet_engine_edit_taxonomies'
        ],
        'JetWooBuilder' => [
            'edit_jet_woo_builder', 'jetwoobulider_manager'
        ],
        'JetSmartFilters' => [
            'edit_jet_smart_filters', 'jetsmartfilters_manager'
        ],
        'JetSearch' => [
            'edit_jet_search', 'jetsearch_manager'
        ],
        'JetCompare&Wishlist' => [
            'edit_jet_compare_wishlist', 'jetcw_manager'
        ],
        'JetBlocks' => [
            'edit_jet_blocks', 'jetblocks_manager'
        ],
        'Rank Math SEO' => [
            'rank_math_options_general', 'rank_math_analytics', 'rank_math_redirections',
            'rank_math_sitemap', 'rank_math_link_builder', 'rank_math_404_monitor',
            'rank_math_edit_htaccess', 'rank_math_admin_bar', 'rank_math_onpage_general',
            'rank_math_onpage_advanced', 'rank_math_onpage_snippet', 'rank_math_onpage_social'
        ],
        'Elementor' => [
            'edit_elementor', 'manage_elementor', 'elementor_edit_content',
            'elementor_edit_posts', 'elementor_edit_pages'
        ],
        'WP Mail SMTP' => [
            'wp_mail_smtp_manage_options', 'wp_mail_smtp_admin'
        ],
        'Loco Translate' => [
            'loco_admin', 'loco_translate', 'loco_manage'
        ],
        'WPvivid Backup' => [
            'wpvivid_manage_options'
        ],
        'Wordfence Security' => [
            'wordfence_menu', 'wordfence_scan', 'wordfence_options',
            'wordfence_live_traffic', 'wordfence_blocking'
        ],
        'CookieYes' => [
            'cookieyes_manage_options', 'cookie_law_info_manage'
        ]
    ];

    $all_caps = [];

    // Capabilities dos plugins conhecidos
    foreach ($plugin_capabilities as $plugin => $caps) {
        foreach ($caps as $cap) {
            $all_caps[$cap] = [
                'plugin' => $plugin,
                'type' => mpa_classify_capability($cap),
                'assigned' => false,
                'roles' => []
            ];
        }
    }

    // Capabilities atualmente atribuídas às roles
    $roles = $wp_roles->roles;
    foreach ($roles as $role_key => $role) {
        if (!empty($role['capabilities'])) {
            foreach ($role['capabilities'] as $cap => $granted) {
                if ($granted) {
                    if (!isset($all_caps[$cap])) {
                        $all_caps[$cap] = [
                            'plugin' => 'Desconhecido',
                            'type' => mpa_classify_capability($cap),
                            'assigned' => false,
                            'roles' => []
                        ];
                    }
                    $all_caps[$cap]['assigned'] = true;
                    $all_caps[$cap]['roles'][] = $role_key;
                }
            }
        }
    }

    // Capabilities dos usuários individuais
    $users = get_users(['fields' => ['ID', 'user_login']]);
    foreach ($users as $user) {
        $user_obj = get_userdata($user->ID);
        if ($user_obj && !empty($user_obj->allcaps)) {
            foreach ($user_obj->allcaps as $cap => $granted) {
                if ($granted && !isset($all_caps[$cap])) {
                    $all_caps[$cap] = [
                        'plugin' => 'Desconhecido',
                        'type' => mpa_classify_capability($cap),
                        'assigned' => true,
                        'roles' => ['user_specific']
                    ];
                }
            }
        }
    }

    return $all_caps;
}

// Função para classificar capability por tipo de ação
function mpa_classify_capability($cap) {
    if (strpos($cap, 'edit_') === 0) return 'Editar';
    if (strpos($cap, 'delete_') === 0) return 'Excluir';
    if (strpos($cap, 'publish_') === 0) return 'Publicar';
    if (strpos($cap, 'read_') === 0) return 'Ler';
    if (strpos($cap, 'manage_') === 0) return 'Gerenciar';
    if (strpos($cap, 'view_') === 0) return 'Visualizar';
    if (strpos($cap, 'create_') === 0) return 'Criar';
    if (strpos($cap, 'update_') === 0) return 'Atualizar';
    if (strpos($cap, 'install_') === 0) return 'Instalar';
    if (strpos($cap, 'activate_') === 0) return 'Ativar';
    return 'Outro';
}

// Função para debugar acesso ao WooCommerce Admin
function mpa_debug_wc_admin_access() {
    $current_user = wp_get_current_user();
    $user_roles = $current_user->roles;

    $output = "<div class='mpa-debug-wc'>";

    // Informações básicas do usuário
    $output .= "<div style='background: #e3f2fd; padding: 15px; border-radius: 5px; margin-bottom: 20px;'>";
    $output .= "<h3>👤 Usuário Atual: {$current_user->user_login}</h3>";
    $output .= "<p><strong>Roles:</strong> " . implode(', ', $user_roles) . "</p>";
    $output .= "</div>";

    // URLs do WooCommerce Admin para testar
    $wc_admin_urls = [
        'Dashboard WooCommerce' => 'admin.php?page=wc-admin',
        'Analytics' => 'admin.php?page=wc-admin&path=/analytics',
        'Customers' => 'admin.php?page=wc-admin&path=/customers',
        'Orders' => 'admin.php?page=wc-admin&path=/analytics/orders',
        'Products' => 'admin.php?page=wc-admin&path=/analytics/products',
        'Revenue' => 'admin.php?page=wc-admin&path=/analytics/revenue',
        'Settings' => 'admin.php?page=wc-settings'
    ];

    $output .= "<h3>🔍 Teste de Acesso às URLs do WooCommerce Admin</h3>";
    $output .= "<table class='widefat striped' style='margin-bottom: 20px;'>";
    $output .= "<thead><tr><th>Página</th><th>URL</th><th>Status</th><th>Testar</th></tr></thead><tbody>";

    foreach ($wc_admin_urls as $name => $url) {
        $full_url = admin_url($url);
        $output .= "<tr>";
        $output .= "<td><strong>$name</strong></td>";
        $output .= "<td><code>$url</code></td>";
        $output .= "<td><span id='status-" . md5($url) . "'>⏳ Testando...</span></td>";
        $output .= "<td><a href='$full_url' target='_blank' class='button button-small'>🔗 Abrir</a></td>";
        $output .= "</tr>";
    }

    $output .= "</tbody></table>";

    // Capabilities críticas para WooCommerce Admin
    $critical_caps = [
        'manage_woocommerce' => 'Capability base do WooCommerce',
        'view_woocommerce_reports' => 'Visualizar relatórios',
        'view_admin_dashboard' => 'Acessar dashboard administrativo',
        'manage_options' => 'Gerenciar opções (Admin total)',
        'read' => 'Capability básica de leitura',
        'edit_posts' => 'Capability mínima para admin',
        'wc_admin_read' => 'Leitura no WC Admin (se existir)',
        'woocommerce_admin' => 'Admin do WooCommerce (se existir)'
    ];

    $output .= "<h3>🔑 Capabilities Críticas para WooCommerce Admin</h3>";
    $output .= "<table class='widefat striped' style='margin-bottom: 20px;'>";
    $output .= "<thead><tr><th>Capability</th><th>Descrição</th><th>Status</th></tr></thead><tbody>";

    foreach ($critical_caps as $cap => $desc) {
        $has_cap = current_user_can($cap);
        $status_color = $has_cap ? '#4caf50' : '#f44336';
        $status_icon = $has_cap ? '✅' : '❌';
        $status_text = $has_cap ? 'TEM' : 'NÃO TEM';

        $output .= "<tr>";
        $output .= "<td><code>$cap</code></td>";
        $output .= "<td>$desc</td>";
        $output .= "<td style='color: $status_color; font-weight: bold;'>$status_icon $status_text</td>";
        $output .= "</tr>";
    }

    $output .= "</tbody></table>";

    // Informações sobre filtros e hooks do WooCommerce
    $output .= "<h3>🔧 Possíveis Soluções</h3>";
    $output .= "<div style='background: #fff3e0; padding: 15px; border-radius: 5px; margin-bottom: 20px;'>";
    $output .= "<h4>Se ainda não tiver acesso, tente:</h4>";
    $output .= "<ol>";
    $output .= "<li><strong>Verificar filtros do WooCommerce:</strong> O WC pode ter filtros específicos que bloqueiam acesso</li>";
    $output .= "<li><strong>Capability específica:</strong> Pode precisar de uma capability não listada</li>";
    $output .= "<li><strong>Versão do WooCommerce:</strong> Versões diferentes podem ter requisitos diferentes</li>";
    $output .= "<li><strong>Plugin de terceiros:</strong> Outros plugins podem estar interferindo</li>";
    $output .= "</ol>";
    $output .= "</div>";

    // Botão para testar capabilities específicas
    $output .= "<div style='background: #f9f9f9; padding: 15px; border-radius: 5px; margin-bottom: 20px;'>";
    $output .= "<h4>🧪 Teste Manual de Capabilities</h4>";
    $output .= "<p>Digite uma capability para testar:</p>";
    $output .= "<input type='text' id='test-capability' placeholder='Ex: wc_admin_access' style='width: 300px; padding: 5px;'>";
    $output .= "<button class='button button-primary' id='test-cap-btn' style='margin-left: 10px;'>Testar</button>";
    $output .= "<div id='test-result' style='margin-top: 10px;'></div>";
    $output .= "</div>";

    // Controle do forçamento do WC Admin
    $force_wc_admin = get_option('mpa_force_wc_admin', true);
    $status_text = $force_wc_admin ? 'ATIVO' : 'INATIVO';
    $status_color = $force_wc_admin ? '#4caf50' : '#f44336';
    $button_text = $force_wc_admin ? 'Desativar Forçamento' : 'Ativar Forçamento';
    $button_action = $force_wc_admin ? '0' : '1';

    $output .= "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; border-left: 4px solid #4caf50;'>";
    $output .= "<h4>🔧 Forçar WooCommerce Admin</h4>";
    $output .= "<p>Status atual: <strong style='color: $status_color;'>$status_text</strong></p>";
    $output .= "<p>Esta funcionalidade força o WooCommerce Admin a ficar sempre ativo, mesmo que outros plugins tentem desativá-lo.</p>";
    $output .= "<button class='button button-secondary' id='toggle-wc-admin' data-enable='$button_action' style='margin-right: 10px;'>$button_text</button>";
    $output .= "<button class='button button-primary' id='copy-shop-manager-caps' style='margin-right: 10px;'>🚀 Copiar TODAS capabilities de Shop_Manager para Gerentes</button>";
    $output .= "<div id='wc-admin-result' style='margin-top: 10px;'></div>";
    $output .= "</div>";

    $output .= "</div>";

    // JavaScript para testes
    $output .= "<script>
    jQuery(document).ready(function($) {
        // Teste manual de capability
        $('#test-cap-btn').click(function() {
            const capability = $('#test-capability').val();
            if (!capability) {
                $('#test-result').html('<p style=\"color: red;\">Digite uma capability!</p>');
                return;
            }

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'mpa_test_capability',
                    capability: capability,
                    nonce: '" . wp_create_nonce('mpa_capability_nonce') . "'
                },
                success: function(response) {
                    const color = response.data ? '#4caf50' : '#f44336';
                    const icon = response.data ? '✅' : '❌';
                    const text = response.data ? 'TEM' : 'NÃO TEM';
                    $('#test-result').html('<p style=\"color: ' + color + '; font-weight: bold;\">' + icon + ' ' + text + ' a capability \"' + capability + '\"</p>');
                },
                error: function() {
                    $('#test-result').html('<p style=\"color: red;\">Erro no teste</p>');
                }
            });
        });

        // Controlar forçamento do WC Admin
        $('#toggle-wc-admin').click(function() {
            const enable = $(this).data('enable');
            const button = $(this);

            button.prop('disabled', true).text('Processando...');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'mpa_toggle_wc_admin',
                    enable: enable,
                    nonce: '" . wp_create_nonce('mpa_capability_nonce') . "'
                },
                success: function(response) {
                    if (response.success) {
                        $('#wc-admin-result').html('<p style=\"color: #4caf50; font-weight: bold;\">✅ ' + response.data + '</p>');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        $('#wc-admin-result').html('<p style=\"color: #f44336; font-weight: bold;\">❌ ' + response.data + '</p>');
                    }
                    button.prop('disabled', false);
                },
                error: function() {
                    $('#wc-admin-result').html('<p style=\"color: #f44336;\">❌ Erro na requisição</p>');
                    button.prop('disabled', false);
                }
            });
        });
    });
    </script>";

    return $output;
}

// Função para debugar capabilities de uma role específica
function mpa_debug_role_capabilities($role_name = 'shop_manager') {
    $role = get_role($role_name);
    if (!$role) {
        return "Role '$role_name' não encontrada.";
    }

    $capabilities = $role->capabilities;
    ksort($capabilities);

    $output = "<h3>Capabilities da role '$role_name':</h3>";
    $output .= "<ul style='columns: 3; list-style: none; padding: 0;'>";

    foreach ($capabilities as $cap => $granted) {
        $status = $granted ? '✅' : '❌';
        $output .= "<li style='padding: 2px 0;'>$status <code>$cap</code></li>";
    }

    $output .= "</ul>";
    $output .= "<p><strong>Total:</strong> " . count($capabilities) . " capabilities</p>";

    return $output;
}

// Função para comparar capabilities entre duas roles em formato de tabela
function mpa_compare_roles($role1_name = 'shop_manager', $role2_name = 'gerentes') {
    $role1 = get_role($role1_name);
    $role2 = get_role($role2_name);

    if (!$role1 || !$role2) {
        return "Uma das roles não foi encontrada.";
    }

    $caps1 = $role1->capabilities;
    $caps2 = $role2->capabilities;

    // Obter todas as capabilities únicas entre as duas roles
    $all_caps = array_unique(array_merge(array_keys($caps1), array_keys($caps2)));
    sort($all_caps);

    $output = "<div class='wrap'>";

    // Estatísticas no topo
    $caps1_count = count($caps1);
    $caps2_count = count($caps2);
    $common_caps = count(array_intersect(array_keys($caps1), array_keys($caps2)));
    $missing_in_role2 = count(array_diff(array_keys($caps1), array_keys($caps2)));
    $extra_in_role2 = count(array_diff(array_keys($caps2), array_keys($caps1)));

    $output .= "<div style='display: flex; gap: 15px; margin-bottom: 20px;'>";
    $output .= "<div class='mpa-stat-card' style='background: #e3f2fd; padding: 15px; border-left: 4px solid #2196f3; flex: 1;'>";
    $output .= "<h4 style='margin: 0 0 5px 0;'>$role1_name</h4>";
    $output .= "<p style='margin: 0; font-size: 18px; font-weight: bold;'>$caps1_count capabilities</p>";
    $output .= "</div>";

    $output .= "<div class='mpa-stat-card' style='background: #f3e5f5; padding: 15px; border-left: 4px solid #9c27b0; flex: 1;'>";
    $output .= "<h4 style='margin: 0 0 5px 0;'>$role2_name</h4>";
    $output .= "<p style='margin: 0; font-size: 18px; font-weight: bold;'>$caps2_count capabilities</p>";
    $output .= "</div>";

    $output .= "<div class='mpa-stat-card' style='background: #e8f5e8; padding: 15px; border-left: 4px solid #4caf50; flex: 1;'>";
    $output .= "<h4 style='margin: 0 0 5px 0;'>Em Comum</h4>";
    $output .= "<p style='margin: 0; font-size: 18px; font-weight: bold;'>$common_caps capabilities</p>";
    $output .= "</div>";

    $output .= "<div class='mpa-stat-card' style='background: #fff3e0; padding: 15px; border-left: 4px solid #ff9800; flex: 1;'>";
    $output .= "<h4 style='margin: 0 0 5px 0;'>Faltam em $role2_name</h4>";
    $output .= "<p style='margin: 0; font-size: 18px; font-weight: bold; color: #ff9800;'>$missing_in_role2</p>";
    $output .= "</div>";
    $output .= "</div>";

    // Tabela de comparação
    $output .= "<table class='widefat fixed striped' style='margin-top: 20px;'>";
    $output .= "<thead>";
    $output .= "<tr style='background: #f1f1f1;'>";
    $output .= "<th style='width: 40%;'>Capability</th>";
    $output .= "<th style='width: 20%; text-align: center;'>$role1_name</th>";
    $output .= "<th style='width: 20%; text-align: center;'>$role2_name</th>";
    $output .= "<th style='width: 20%; text-align: center;'>Ações</th>";
    $output .= "</tr>";
    $output .= "</thead>";
    $output .= "<tbody>";

    foreach ($all_caps as $cap) {
        $has_role1 = isset($caps1[$cap]) && $caps1[$cap];
        $has_role2 = isset($caps2[$cap]) && $caps2[$cap];

        // Determinar cor da linha
        $row_class = '';
        $row_style = '';
        if ($has_role1 && !$has_role2) {
            $row_style = 'background-color: #fff3e0;'; // Laranja claro - falta em role2
        } elseif (!$has_role1 && $has_role2) {
            $row_style = 'background-color: #e3f2fd;'; // Azul claro - extra em role2
        } elseif ($has_role1 && $has_role2) {
            $row_style = 'background-color: #e8f5e8;'; // Verde claro - em comum
        }

        $output .= "<tr style='$row_style'>";

        // Nome da capability
        $output .= "<td><code style='font-weight: bold;'>$cap</code></td>";

        // Status no role1 (shop_manager)
        $output .= "<td style='text-align: center;'>";
        if ($has_role1) {
            $output .= "<span style='color: #4caf50; font-size: 18px;'>✅</span>";
        } else {
            $output .= "<span style='color: #f44336; font-size: 18px;'>❌</span>";
        }
        $output .= "</td>";

        // Status no role2 (gerentes)
        $output .= "<td style='text-align: center;'>";
        if ($has_role2) {
            $output .= "<span style='color: #4caf50; font-size: 18px;'>✅</span>";
        } else {
            $output .= "<span style='color: #f44336; font-size: 18px;'>❌</span>";
        }
        $output .= "</td>";

        // Ações
        $output .= "<td style='text-align: center;'>";
        if ($has_role1 && !$has_role2) {
            $output .= "<button class='button button-small mpa-assign-cap' data-capability='$cap' style='background: #ff9800; color: white;'>";
            $output .= "Adicionar a $role2_name";
            $output .= "</button>";
        } elseif (!$has_role1 && $has_role2) {
            $output .= "<button class='button button-small mpa-remove-cap' data-capability='$cap' style='background: #f44336; color: white;'>";
            $output .= "Remover de $role2_name";
            $output .= "</button>";
        } elseif ($has_role1 && $has_role2) {
            $output .= "<span style='color: #4caf50;'>✅ Sincronizado</span>";
        } else {
            $output .= "<span style='color: #999;'>➖ Ausente em ambas</span>";
        }
        $output .= "</td>";

        $output .= "</tr>";
    }

    $output .= "</tbody>";
    $output .= "</table>";

    // Botões de ação em lote
    $output .= "<div style='margin-top: 20px; padding: 20px; background: #f9f9f9; border-radius: 5px;'>";
    $output .= "<h3>Ações em Lote</h3>";
    $output .= "<div style='display: flex; gap: 15px; flex-wrap: wrap;'>";
    $output .= "<button class='button button-primary' id='mpa-sync-all-missing'>";
    $output .= "🔄 Copiar TODAS as capabilities de $role1_name para $role2_name";
    $output .= "</button>";
    $output .= "<button class='button button-secondary' id='mpa-sync-woocommerce-only'>";
    $output .= "🛒 Copiar apenas capabilities do WooCommerce";
    $output .= "</button>";
    $output .= "</div>";
    $output .= "</div>";

    $output .= "</div>";

    return $output;
}

// Nova página de capabilities melhorada
function mpa_capabilities_page()
{
    if (!current_user_can('manage_options')) {
        wp_die('Sem permissão.');
    }

    // Debug: Mostrar capabilities da shop_manager se solicitado
    if (isset($_GET['debug_role']) && $_GET['debug_role'] === 'shop_manager') {
        echo '<div class="wrap">';
        echo '<h1>Debug: Capabilities da Role Shop Manager</h1>';
        echo mpa_debug_role_capabilities('shop_manager');
        echo '<p><a href="' . admin_url('admin.php?page=mpa-capabilities') . '" class="button">Voltar</a></p>';
        echo '</div>';
        return;
    }

    // Comparação entre roles se solicitado
    if (isset($_GET['compare_roles']) && $_GET['compare_roles'] === '1') {
        echo '<div class="wrap">';
        echo '<h1>Comparação: Shop Manager vs Gerentes</h1>';
        echo mpa_compare_roles('shop_manager', 'gerentes');
        echo '<p><a href="' . admin_url('admin.php?page=mpa-capabilities') . '" class="button">Voltar</a></p>';
        echo '</div>';
        return;
    }

    // Debug específico para WooCommerce Admin
    if (isset($_GET['debug_wc_admin']) && $_GET['debug_wc_admin'] === '1') {
        echo '<div class="wrap">';
        echo '<h1>Debug: Acesso ao WooCommerce Admin</h1>';
        echo mpa_debug_wc_admin_access();
        echo '<p><a href="' . admin_url('admin.php?page=mpa-capabilities') . '" class="button">Voltar</a></p>';
        echo '</div>';
        return;
    }

    // Obter todas as capabilities
    $all_caps = mpa_get_all_capabilities();

    // Filtros
    $filter_plugin = sanitize_text_field($_GET['filter_plugin'] ?? '');
    $filter_type = sanitize_text_field($_GET['filter_type'] ?? '');
    $filter_assigned = $_GET['filter_assigned'] ?? '';

    // Aplicar filtros
    if ($filter_plugin) {
        $all_caps = array_filter($all_caps, function($cap) use ($filter_plugin) {
            return $cap['plugin'] === $filter_plugin;
        });
    }

    if ($filter_type) {
        $all_caps = array_filter($all_caps, function($cap) use ($filter_type) {
            return $cap['type'] === $filter_type;
        });
    }

    if ($filter_assigned !== '') {
        $assigned_filter = $filter_assigned === '1';
        $all_caps = array_filter($all_caps, function($cap) use ($assigned_filter) {
            return $cap['assigned'] === $assigned_filter;
        });
    }

    // Obter listas únicas para filtros
    $all_caps_full = mpa_get_all_capabilities();
    $plugins = array_unique(array_column($all_caps_full, 'plugin'));
    $types = array_unique(array_column($all_caps_full, 'type'));
    sort($plugins);
    sort($types);

    ?>
    <div class="wrap">
        <h1>Gerenciador de Capabilities Avançado</h1>
        <p>Lista completa de todas as capabilities conhecidas, incluindo as não atribuídas.</p>

        <!-- Filtros -->
        <div class="mpa-filters-section" style="background: #f9f9f9; padding: 15px; margin-bottom: 20px; border-radius: 5px;">
            <form method="get" style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
                <input type="hidden" name="page" value="mpa-capabilities">

                <div>
                    <label for="filter_plugin"><strong>Plugin:</strong></label>
                    <select name="filter_plugin" id="filter_plugin">
                        <option value="">Todos os plugins</option>
                        <?php foreach ($plugins as $plugin): ?>
                            <option value="<?php echo esc_attr($plugin); ?>" <?php selected($filter_plugin, $plugin); ?>>
                                <?php echo esc_html($plugin); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="filter_type"><strong>Tipo:</strong></label>
                    <select name="filter_type" id="filter_type">
                        <option value="">Todos os tipos</option>
                        <?php foreach ($types as $type): ?>
                            <option value="<?php echo esc_attr($type); ?>" <?php selected($filter_type, $type); ?>>
                                <?php echo esc_html($type); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="filter_assigned"><strong>Status:</strong></label>
                    <select name="filter_assigned" id="filter_assigned">
                        <option value="">Todos</option>
                        <option value="1" <?php selected($filter_assigned, '1'); ?>>Atribuídas</option>
                        <option value="0" <?php selected($filter_assigned, '0'); ?>>Não atribuídas</option>
                    </select>
                </div>

                <div>
                    <?php submit_button('Filtrar', 'secondary', 'filter', false); ?>
                    <a href="<?php echo admin_url('admin.php?page=mpa-capabilities'); ?>" class="button">Limpar</a>
                </div>
            </form>
        </div>

        <!-- Estatísticas -->
        <div class="mpa-stats" style="display: flex; gap: 20px; margin-bottom: 20px;">
            <div class="mpa-stat-card" style="background: #fff; padding: 15px; border-left: 4px solid #0073aa; flex: 1;">
                <h3 style="margin: 0 0 5px 0;">Total de Capabilities</h3>
                <p style="margin: 0; font-size: 24px; font-weight: bold;"><?php echo count($all_caps_full); ?></p>
            </div>
            <div class="mpa-stat-card" style="background: #fff; padding: 15px; border-left: 4px solid #00a32a; flex: 1;">
                <h3 style="margin: 0 0 5px 0;">Atribuídas</h3>
                <p style="margin: 0; font-size: 24px; font-weight: bold; color: #00a32a;">
                    <?php echo count(array_filter($all_caps_full, function($cap) { return $cap['assigned']; })); ?>
                </p>
            </div>
            <div class="mpa-stat-card" style="background: #fff; padding: 15px; border-left: 4px solid #d63638; flex: 1;">
                <h3 style="margin: 0 0 5px 0;">Não Atribuídas</h3>
                <p style="margin: 0; font-size: 24px; font-weight: bold; color: #d63638;">
                    <?php echo count(array_filter($all_caps_full, function($cap) { return !$cap['assigned']; })); ?>
                </p>
            </div>
        </div>

        <!-- Tabela de capabilities -->
        <div class="mpa-capabilities-table">
            <table class="widefat fixed striped" style="margin-top: 20px;">
                <thead>
                    <tr>
                        <th style="width: 300px;">Capability</th>
                        <th style="width: 120px;">Tipo</th>
                        <th style="width: 150px;">Plugin</th>
                        <th style="width: 100px;">Status</th>
                        <th>Roles Atribuídas</th>
                        <th style="width: 200px;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($all_caps)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px;">
                                Nenhuma capability encontrada com os filtros aplicados.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($all_caps as $cap_name => $cap_data): ?>
                            <tr>
                                <td>
                                    <code style="font-weight: bold; color: #0073aa;"><?php echo esc_html($cap_name); ?></code>
                                </td>
                                <td>
                                    <span class="mpa-type-badge" style="background: #f0f0f0; padding: 2px 8px; border-radius: 3px; font-size: 12px;">
                                        <?php echo esc_html($cap_data['type']); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html($cap_data['plugin']); ?></td>
                                <td>
                                    <?php if ($cap_data['assigned']): ?>
                                        <span style="color: #00a32a; font-weight: bold;">✓ Atribuída</span>
                                    <?php else: ?>
                                        <span style="color: #d63638;">✗ Não atribuída</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    if (!empty($cap_data['roles'])):
                                        echo esc_html(implode(', ', $cap_data['roles']));
                                    else:
                                        echo '<span style="color: #666;">Nenhuma</span>';
                                    endif;
                                    ?>
                                </td>
                                <td>
                                    <button class="button button-small mpa-assign-cap"
                                            data-capability="<?php echo esc_attr($cap_name); ?>">
                                        Atribuir a Role
                                    </button>
                                    <?php if ($cap_data['assigned']): ?>
                                        <button class="button button-small mpa-remove-cap"
                                                data-capability="<?php echo esc_attr($cap_name); ?>"
                                                style="margin-left: 5px;">
                                            Remover
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Modal para atribuir capability -->
        <div id="mpa-assign-modal" style="display: none; position: fixed; z-index: 999999; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
            <div style="background-color: #fff; margin: 5% auto; padding: 20px; border-radius: 5px; width: 500px; max-width: 90%;">
                <h3>Atribuir Capability</h3>
                <form id="mpa-assign-form">
                    <input type="hidden" id="mpa-capability-name" name="capability" value="">
                    <div style="margin-bottom: 15px;">
                        <label for="mpa-role-select"><strong>Selecionar Role:</strong></label>
                        <select id="mpa-role-select" name="role" style="width: 100%; padding: 5px;">
                            <?php foreach (get_editable_roles() as $role_key => $role_info): ?>
                                <option value="<?php echo esc_attr($role_key); ?>">
                                    <?php echo esc_html($role_info['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="text-align: right;">
                        <button type="button" id="mpa-modal-cancel" class="button">Cancelar</button>
                        <button type="submit" class="button button-primary" style="margin-left: 10px;">Atribuir</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Ações em lote -->
        <div class="mpa-bulk-actions" style="margin-top: 30px; padding: 20px; background: #f9f9f9; border-radius: 5px;">
            <h3>Ações Avançadas</h3>
            <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                <button class="button button-secondary" id="mpa-export-json">
                    Exportar Lista (JSON)
                </button>
                <button class="button button-secondary" id="mpa-create-capability">
                    Criar Nova Capability
                </button>
                <button class="button button-secondary" id="mpa-refresh-scan">
                    Atualizar Scan
                </button>
                <a href="<?php echo admin_url('admin.php?page=mpa-capabilities&debug_role=shop_manager'); ?>"
                   class="button button-secondary">
                    🔍 Debug Role Shop_Manager
                </a>
                <!-- Comparação removida - usar role shop_manager nativa -->
                <!-- Debug WooCommerce removido - usar role shop_manager nativa -->
            </div>
        </div>
    </div>

    <!-- JavaScript para funcionalidades AJAX -->
    <script>
    jQuery(document).ready(function($) {
        // Nonce para segurança AJAX
        const nonce = '<?php echo wp_create_nonce('mpa_capability_nonce'); ?>';

        // Função para mostrar notificações
        function showNotice(message, type = 'success') {
            const notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
            $('.wrap h1').after(notice);
            setTimeout(() => notice.fadeOut(), 3000);
        }

        // Atribuir capability
        $(document).on('click', '.mpa-assign-cap', function() {
            const capability = $(this).data('capability');
            $('#mpa-capability-name').val(capability);
            $('#mpa-assign-modal').show();
        });

        // Fechar modal
        $('#mpa-modal-cancel, #mpa-assign-modal').click(function(e) {
            if (e.target === this) {
                $('#mpa-assign-modal').hide();
            }
        });

        // Submeter formulário de atribuição
        $('#mpa-assign-form').submit(function(e) {
            e.preventDefault();

            const capability = $('#mpa-capability-name').val();
            const role = $('#mpa-role-select').val();

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'mpa_assign_capability',
                    capability: capability,
                    role: role,
                    nonce: nonce
                },
                success: function(response) {
                    if (response.success) {
                        showNotice(response.data, 'success');
                        location.reload();
                    } else {
                        showNotice(response.data, 'error');
                    }
                },
                error: function() {
                    showNotice('Erro na requisição AJAX', 'error');
                }
            });

            $('#mpa-assign-modal').hide();
        });

        // Remover capability
        $(document).on('click', '.mpa-remove-cap', function() {
            const capability = $(this).data('capability');

            if (!confirm('Tem certeza que deseja remover a capability "' + capability + '" de todas as roles?')) {
                return;
            }

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'mpa_remove_capability',
                    capability: capability,
                    nonce: nonce
                },
                success: function(response) {
                    if (response.success) {
                        showNotice(response.data, 'success');
                        location.reload();
                    } else {
                        showNotice(response.data, 'error');
                    }
                },
                error: function() {
                    showNotice('Erro na requisição AJAX', 'error');
                }
            });
        });

        // Exportar JSON
        $('#mpa-export-json').click(function() {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'mpa_export_capabilities',
                    nonce: nonce
                },
                success: function(response) {
                    if (response.success) {
                        const dataStr = JSON.stringify(response.data, null, 2);
                        const dataBlob = new Blob([dataStr], {type: 'application/json'});
                        const url = URL.createObjectURL(dataBlob);
                        const link = document.createElement('a');
                        link.href = url;
                        link.download = 'capabilities-export.json';
                        link.click();
                        showNotice('Arquivo JSON exportado com sucesso!', 'success');
                    } else {
                        showNotice(response.data, 'error');
                    }
                },
                error: function() {
                    showNotice('Erro na exportação', 'error');
                }
            });
        });

        // Criar nova capability
        $('#mpa-create-capability').click(function() {
            const capability = prompt('Digite o nome da nova capability:');
            if (!capability) return;

            const role = prompt('Digite a role para atribuir (deixe vazio para administrator):') || 'administrator';

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'mpa_create_capability',
                    capability: capability,
                    role: role,
                    nonce: nonce
                },
                success: function(response) {
                    if (response.success) {
                        showNotice(response.data, 'success');
                        location.reload();
                    } else {
                        showNotice(response.data, 'error');
                    }
                },
                error: function() {
                    showNotice('Erro ao criar capability', 'error');
                }
            });
        });

        // Atualizar scan
        $('#mpa-refresh-scan').click(function() {
            location.reload();
        });

        // Sincronizar todas as capabilities faltantes
        $(document).on('click', '#mpa-sync-all-missing', function() {
            if (!confirm('Tem certeza que deseja copiar TODAS as capabilities de shop_manager para gerentes?\\n\\nIsso pode levar alguns segundos...')) {
                return;
            }

            const buttons = $('.mpa-assign-cap');
            let completed = 0;
            let total = buttons.length;

            if (total === 0) {
                showNotice('Nenhuma capability para sincronizar!', 'info');
                return;
            }

            showNotice('Sincronizando ' + total + ' capabilities...', 'info');

            buttons.each(function() {
                const capability = $(this).data('capability');

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'mpa_assign_capability',
                        capability: capability,
                        role: 'gerentes',
                        nonce: nonce
                    },
                    success: function(response) {
                        completed++;
                        if (completed === total) {
                            showNotice('✅ Sincronização completa! ' + total + ' capabilities copiadas.', 'success');
                            setTimeout(() => location.reload(), 2000);
                        }
                    },
                    error: function() {
                        completed++;
                        if (completed === total) {
                            showNotice('⚠️ Sincronização completa com alguns erros.', 'warning');
                            setTimeout(() => location.reload(), 2000);
                        }
                    }
                });
            });
        });

        // Sincronizar apenas capabilities do WooCommerce
        $(document).on('click', '#mpa-sync-woocommerce-only', function() {
            if (!confirm('Copiar apenas as capabilities relacionadas ao WooCommerce?')) {
                return;
            }

            const woocommerce_patterns = ['woocommerce', 'shop_', 'product', 'order', 'coupon', 'customer', 'manage_woocommerce', 'view_woocommerce', 'export_shop'];
            const buttons = $('.mpa-assign-cap').filter(function() {
                const capability = $(this).data('capability');
                return woocommerce_patterns.some(pattern => capability.includes(pattern));
            });

            let completed = 0;
            let total = buttons.length;

            if (total === 0) {
                showNotice('Nenhuma capability do WooCommerce para sincronizar!', 'info');
                return;
            }

            showNotice('Sincronizando ' + total + ' capabilities do WooCommerce...', 'info');

            buttons.each(function() {
                const capability = $(this).data('capability');

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'mpa_assign_capability',
                        capability: capability,
                        role: 'gerentes',
                        nonce: nonce
                    },
                    success: function(response) {
                        completed++;
                        if (completed === total) {
                            showNotice('✅ Capabilities do WooCommerce sincronizadas!', 'success');
                            setTimeout(() => location.reload(), 2000);
                        }
                    },
                    error: function() {
                        completed++;
                        if (completed === total) {
                            showNotice('⚠️ Sincronização do WooCommerce completa com alguns erros.', 'warning');
                            setTimeout(() => location.reload(), 2000);
                        }
                    }
                });
            });
        });
    });
    </script>

    <!-- CSS adicional -->
    <style>
        .mpa-type-badge {
            display: inline-block;
            font-size: 11px;
            padding: 3px 8px;
            border-radius: 3px;
            background: #0073aa;
            color: white;
            font-weight: bold;
        }
        .mpa-stat-card {
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .mpa-capabilities-table .widefat th,
        .mpa-capabilities-table .widefat td {
            padding: 12px 8px;
        }
        .mpa-assign-cap, .mpa-remove-cap {
            font-size: 12px;
        }
    </style>

    <?php
}

// Funções AJAX para gerenciar capabilities
add_action('wp_ajax_mpa_assign_capability', 'mpa_assign_capability_ajax');
add_action('wp_ajax_mpa_remove_capability', 'mpa_remove_capability_ajax');
add_action('wp_ajax_mpa_create_capability', 'mpa_create_capability_ajax');
add_action('wp_ajax_mpa_export_capabilities', 'mpa_export_capabilities_ajax');
add_action('wp_ajax_mpa_test_capability', 'mpa_test_capability_ajax');
// Função removida - usar role shop_manager nativa ao invés de copiar capabilities

function mpa_assign_capability_ajax() {
    // Verificar se usuário está logado
    if (!is_user_logged_in()) {
        wp_send_json_error('Acesso negado: usuário não autenticado');
    }

    check_ajax_referer('mpa_capability_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Sem permissão.');
    }

    $capability = sanitize_text_field($_POST['capability']);
    $role_name = sanitize_text_field($_POST['role']);

    $role = get_role($role_name);
    if ($role) {
        $role->add_cap($capability);
        wp_send_json_success('Capability atribuída com sucesso!');
    } else {
        wp_send_json_error('Role não encontrada.');
    }
}

function mpa_remove_capability_ajax() {
    check_ajax_referer('mpa_capability_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Sem permissão.');
    }

    $capability = sanitize_text_field($_POST['capability']);

    // Remove de todas as roles
    global $wp_roles;
    $roles = $wp_roles->roles;

    foreach ($roles as $role_key => $role_data) {
        $role = get_role($role_key);
        if ($role && isset($role->capabilities[$capability])) {
            $role->remove_cap($capability);
        }
    }

    wp_send_json_success('Capability removida de todas as roles!');
}

function mpa_create_capability_ajax() {
    check_ajax_referer('mpa_capability_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Sem permissão.');
    }

    $capability = sanitize_text_field($_POST['capability']);
    $role_name = sanitize_text_field($_POST['role']);

    if (empty($capability)) {
        wp_send_json_error('Nome da capability é obrigatório.');
    }

    $role = get_role($role_name);
    if ($role) {
        $role->add_cap($capability);
        wp_send_json_success('Nova capability criada e atribuída!');
    } else {
        wp_send_json_error('Role não encontrada.');
    }
}

function mpa_export_capabilities_ajax() {
    check_ajax_referer('mpa_capability_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Sem permissão.');
    }

    $all_caps = mpa_get_all_capabilities();
    $export_data = [
        'timestamp' => current_time('mysql'),
        'site_url' => get_site_url(),
        'capabilities' => $all_caps
    ];

    wp_send_json_success($export_data);
}

function mpa_test_capability_ajax() {
    check_ajax_referer('mpa_capability_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Sem permissão.');
    }

    $capability = sanitize_text_field($_POST['capability']);
    $has_capability = current_user_can($capability);

    wp_send_json_success($has_capability);
}

// Sistema básico de capabilities para WooCommerce
// (Bypass removido - usar role shop_manager nativa)

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
                'page' => 'mpa-capabilities',
                'role' => $role_key,
                'updated' => 'true'
            ], admin_url('admin.php')));
            exit;
        }
    }

    wp_redirect(admin_url('admin.php?page=mpa-capabilities&error=1'));
    exit;
});

// Notificações
add_action('admin_notices', function () {
    if (!isset($_GET['page']) || $_GET['page'] !== 'mpa-capabilities')
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
    if (!isset($_GET['page']) || $_GET['page'] !== 'mpa-capabilities')
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
    if (!isset($_GET['page']) || $_GET['page'] !== 'mpa-capabilities')
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