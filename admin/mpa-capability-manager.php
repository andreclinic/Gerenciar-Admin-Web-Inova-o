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

// Enqueue CSS específico para capabilities
add_action('admin_enqueue_scripts', 'mpa_capability_enqueue_assets');

function mpa_capability_enqueue_assets($hook) {
    // Carregar apenas na página de capabilities
    if (strpos($hook, 'mpa-capabilities') !== false) {
        wp_enqueue_style(
            'mpa-capability-manager',
            plugin_dir_url(__FILE__) . '../assets/css/mpa-capability-manager.css',
            [],
            '1.0.1'
        );
    }
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
            'woocommerce_analytics_customers', 'wc_admin_read', 'wc_admin_edit', 'woocommerce_admin',
            // Capabilities adicionais baseadas na lista fornecida
            'assign_shop_coupon_terms', 'assign_shop_order_terms', 'create_customers',
            'delete_shop_coupon_terms', 'delete_shop_order_terms', 'edit_shop_coupon_terms',
            'edit_shop_order_terms', 'manage_shop_coupon_terms', 'manage_shop_order_terms',
            // Capabilities de Shop Discounts e Payments
            'assign_shop_discount_terms', 'assign_shop_payment_terms', 'create_shop_orders',
            'delete_acf-field-groups', 'delete_custom_css', 'delete_custom_csss',
            'delete_custom_order_status', 'delete_others_acf-field-groups', 'delete_others_custom_csss',
            'delete_others_shop_discounts', 'delete_others_shop_payments', 'delete_others_whatsapp-accounts',
            'delete_private_acf-field-groups', 'delete_private_shop_discounts', 'delete_private_shop_payments',
            'delete_private_whatsapp-accounts', 'delete_published_acf-field-groups', 'delete_published_custom_csss',
            'delete_published_shop_discounts', 'delete_published_shop_payments', 'delete_published_whatsapp-accounts',
            'delete_shop_discounts', 'delete_shop_discount_terms', 'delete_shop_payments',
            'delete_shop_payment_terms', 'delete_whatsapp-accounts', 'edit_acf-field-groups',
            'edit_custom_css', 'edit_custom_csss', 'edit_custom_order_status', 'edit_others_acf-field-groups',
            'edit_others_custom_csss', 'edit_others_shop_discounts', 'edit_others_shop_payments',
            'edit_others_whatsapp-accounts', 'edit_private_acf-field-groups', 'edit_private_shop_discounts',
            'edit_private_shop_payments', 'edit_private_whatsapp-accounts', 'edit_published_acf-field-groups',
            'edit_published_custom_csss', 'edit_published_shop_discounts', 'edit_published_shop_payments',
            'edit_published_whatsapp-accounts', 'edit_shop_discounts', 'edit_shop_discount_terms',
            'edit_shop_payments', 'edit_shop_payment_terms', 'edit_whatsapp-accounts',
            'export_shop_payments', 'export_shop_reports', 'import_shop_discounts', 'import_shop_payments',
            'manage_custom_order_statuses', 'manage_shop_discounts', 'manage_shop_discount_terms',
            'manage_shop_payment_terms', 'manage_shop_settings', 'mgwc_delete_orders',
            'mgwc_manage_all_orders', 'mgwc_manage_orders', 'mgwc_view_orders',
            'publish_acf-field-groups', 'publish_custom_csss', 'publish_shop_discounts',
            'publish_shop_payments', 'publish_whatsapp-accounts', 'read_custom_css',
            'read_custom_order_status', 'read_private_acf-field-groups', 'read_private_shop_discounts',
            'read_private_shop_payments', 'read_private_whatsapp-accounts', 'view_dwi_dashboard',
            'view_shop_discount_stats', 'view_shop_payment_stats', 'view_shop_sensitive_data'
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
            'rank_math_onpage_advanced', 'rank_math_onpage_snippet', 'rank_math_onpage_social',
            'rank_math_content_ai', 'rank_math_general', 'rank_math_onpage_analysis',
            'rank_math_role_manager', 'rank_math_site_analysis', 'rank_math_titles'
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
            'wordfence_live_traffic', 'wordfence_blocking',
            'wf2fa_activate_2fa_others', 'wf2fa_activate_2fa_self', 'wf2fa_manage_settings'
        ],
        'CookieYes' => [
            'cookieyes_manage_options', 'cookie_law_info_manage'
        ],
        'PublishPress Capabilities' => [
            'manage_capabilities_dashboard', 'manage_capabilities_roles', 'manage_capabilities',
            'manage_capabilities_editor_features', 'manage_capabilities_admin_features',
            'manage_capabilities_admin_menus', 'manage_capabilities_frontend_features',
            'manage_capabilities_profile_features', 'manage_capabilities_redirects',
            'manage_capabilities_nav_menus', 'manage_capabilities_user_testing',
            'manage_capabilities_backup', 'manage_capabilities_settings'
        ],
        'Outras Permissões' => [
            'manage_pys', 'copy_posts', 'delete_categories', 'delete_tags',
            'edit_categories', 'edit_tags', 'manage_tags', 'delete_files'
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

// Função para obter categorias organizadas de capabilities baseada na referência completa
function mpa_get_capability_categories() {
    return [
        'Edição' => [
            'Permissões de Edição' => [
                'Posts' => ['edit_posts', 'edit_others_posts', 'edit_published_posts', 'edit_private_posts'],
                'Páginas' => ['edit_pages', 'edit_others_pages', 'edit_published_pages', 'edit_private_pages'],
                'Mídia' => ['upload_files', 'edit_files'],
                'Menus de navegação (bloco)' => [],
                'Código personalizado' => ['edit_themes'],
                'Produtos' => ['edit_products', 'edit_others_products', 'edit_published_products', 'edit_private_products', 'edit_product'],
                'Pedidos' => ['edit_shop_orders', 'edit_others_shop_orders', 'edit_published_shop_orders', 'edit_private_shop_orders', 'edit_shop_order', 'create_shop_orders'],
                'Cupons' => ['edit_shop_coupons', 'edit_others_shop_coupons', 'edit_published_shop_coupons', 'edit_private_shop_coupons', 'edit_shop_coupon'],
                'GDPR Cookie Consent' => [],
                'Order Statuses' => ['manage_woocommerce', 'edit_custom_order_status', 'manage_custom_order_statuses'],
                'Dados estruturados (schemas)' => [],
                'Forms' => [],
                'ACF Field Groups' => ['edit_acf-field-groups', 'edit_others_acf-field-groups', 'edit_published_acf-field-groups', 'edit_private_acf-field-groups'],
                'Custom CSS' => ['edit_custom_css', 'edit_custom_csss', 'edit_others_custom_csss', 'edit_published_custom_csss'],
                'Shop Discounts' => ['edit_shop_discounts', 'edit_others_shop_discounts', 'edit_published_shop_discounts', 'edit_private_shop_discounts'],
                'Shop Payments' => ['edit_shop_payments', 'edit_others_shop_payments', 'edit_published_shop_payments', 'edit_private_shop_payments'],
                'WhatsApp Accounts' => ['edit_whatsapp-accounts', 'edit_others_whatsapp-accounts', 'edit_published_whatsapp-accounts', 'edit_private_whatsapp-accounts'],
                'Categorias' => ['edit_categories'],
                'Tags' => ['edit_tags']
            ]
        ],
        'Exclusão' => [
            'Permissões de Exclusão' => [
                'Posts' => ['delete_posts', 'delete_others_posts', 'delete_published_posts', 'delete_private_posts'],
                'Páginas' => ['delete_pages', 'delete_others_pages', 'delete_published_pages', 'delete_private_pages'],
                'Mídia' => ['delete_files'],
                'Menus de navegação (bloco)' => [],
                'Produtos' => ['delete_products', 'delete_others_products', 'delete_published_products', 'delete_private_products', 'delete_product'],
                'Pedidos' => ['delete_shop_orders', 'delete_others_shop_orders', 'delete_published_shop_orders', 'delete_private_shop_orders', 'delete_shop_order'],
                'Cupons' => ['delete_shop_coupons', 'delete_others_shop_coupons', 'delete_published_shop_coupons', 'delete_private_shop_coupons', 'delete_shop_coupon'],
                'GDPR Cookie Consent' => [],
                'Order Statuses' => ['delete_custom_order_status'],
                'Forms' => ['delete_posts'],
                'ACF Field Groups' => ['delete_acf-field-groups', 'delete_others_acf-field-groups', 'delete_published_acf-field-groups', 'delete_private_acf-field-groups'],
                'Custom CSS' => ['delete_custom_css', 'delete_custom_csss', 'delete_others_custom_csss', 'delete_published_custom_csss'],
                'Shop Discounts' => ['delete_shop_discounts', 'delete_others_shop_discounts', 'delete_published_shop_discounts', 'delete_private_shop_discounts'],
                'Shop Payments' => ['delete_shop_payments', 'delete_others_shop_payments', 'delete_published_shop_payments', 'delete_private_shop_payments'],
                'WhatsApp Accounts' => ['delete_whatsapp-accounts', 'delete_others_whatsapp-accounts', 'delete_published_whatsapp-accounts', 'delete_private_whatsapp-accounts'],
                'Categorias' => ['delete_categories'],
                'Tags' => ['delete_tags']
            ]
        ],
        'Visibilidade' => [
            'Permissões de Visibilidade' => [
                'Posts' => ['read_posts', 'read_private_posts'],
                'Páginas' => ['read_pages', 'read_private_pages'],
                'Menus de navegação (bloco)' => [],
                'Código personalizado' => ['edit_themes'],
                'Produtos' => ['read_products', 'read_private_products', 'read_product'],
                'Pedidos' => ['read_shop_orders', 'read_private_shop_orders', 'read_shop_order'],
                'Cupons' => ['read_shop_coupons', 'read_private_shop_coupons', 'read_shop_coupon'],
                'GDPR Cookie Consent' => [],
                'Order Statuses' => ['read_custom_order_status'],
                'Dados estruturados (schemas)' => [],
                'Forms' => [],
                'ACF Field Groups' => ['read_private_acf-field-groups'],
                'Custom CSS' => ['read_custom_css'],
                'Shop Discounts' => ['read_private_shop_discounts'],
                'Shop Payments' => ['read_private_shop_payments'],
                'WhatsApp Accounts' => ['read_private_whatsapp-accounts'],
                'Dashboards' => ['view_admin_dashboard', 'view_dwi_dashboard', 'view_shop_discount_stats', 'view_shop_payment_stats', 'view_shop_reports', 'view_shop_sensitive_data']
            ]
        ],
        'Taxonomias' => [
            'Permissões de Taxonomias' => [
                'Taxonomias de Produto' => ['manage_product_terms', 'edit_product_terms', 'delete_product_terms', 'assign_product_terms'],
                'Menus de navegação (legado)' => ['manage_links'],
                'Shop Coupons Terms' => ['assign_shop_coupon_terms', 'manage_shop_coupon_terms', 'edit_shop_coupon_terms', 'delete_shop_coupon_terms'],
                'Shop Orders Terms' => ['assign_shop_order_terms', 'manage_shop_order_terms', 'edit_shop_order_terms', 'delete_shop_order_terms'],
                'Shop Discounts Terms' => ['assign_shop_discount_terms', 'manage_shop_discount_terms', 'edit_shop_discount_terms', 'delete_shop_discount_terms'],
                'Shop Payments Terms' => ['assign_shop_payment_terms', 'manage_shop_payment_terms', 'edit_shop_payment_terms', 'delete_shop_payment_terms'],
                'Categorias Gerais' => ['manage_categories'],
                'Tags Gerais' => ['manage_tags']
            ]
        ],
        'Mídia' => [
            'Permissões de Mídia' => [
                'Biblioteca de mídia' => ['edit_files', 'upload_files', 'unfiltered_upload', 'delete_files']
            ]
        ],
        'Comentários' => [
            'Permissões de Comentários' => [
                'Comentários' => ['moderate_comments', 'edit_comments']
            ]
        ],
        'Usuários' => [
            'Permissões de Usuários' => [
                'Usuários' => ['create_users', 'delete_users', 'edit_users', 'list_users', 'promote_users', 'remove_users'],
                'Clientes' => ['create_customers']
            ]
        ],
        'Administrador' => [
            'Permissões Administrativas' => [
                'Sistema' => ['manage_options', 'edit_dashboard', 'export', 'import', 'read', 'update_core', 'unfiltered_html'],
                'Copy Posts' => ['copy_posts']
            ]
        ],
        'Temas' => [
            'Permissões de Temas' => [
                'Temas' => ['delete_themes', 'edit_themes', 'install_themes', 'switch_themes', 'update_themes', 'edit_theme_options', 'manage_links']
            ]
        ],
        'Plugins' => [
            'Permissões de Plugins' => [
                'Plugins' => ['activate_plugins', 'delete_plugins', 'edit_plugins', 'install_plugins', 'update_plugins']
            ]
        ],
        'PublishPress Capabilities' => [
            'Permissões do PublishPress' => [
                'Capabilities' => [
                    'manage_capabilities_dashboard',
                    'manage_capabilities_roles',
                    'manage_capabilities',
                    'manage_capabilities_editor_features',
                    'manage_capabilities_admin_features',
                    'manage_capabilities_admin_menus',
                    'manage_capabilities_frontend_features',
                    'manage_capabilities_profile_features',
                    'manage_capabilities_redirects',
                    'manage_capabilities_nav_menus',
                    'manage_capabilities_user_testing',
                    'manage_capabilities_backup',
                    'manage_capabilities_settings'
                ]
            ]
        ],
        'WooCommerce' => [
            'Permissões do WooCommerce' => [
                'WooCommerce Core' => ['manage_woocommerce', 'view_woocommerce_reports'],
                'Orders Management' => ['mgwc_delete_orders', 'mgwc_manage_all_orders', 'mgwc_manage_orders', 'mgwc_view_orders'],
                'Shop Management' => ['manage_shop_settings', 'manage_shop_discounts', 'manage_shop_discount_terms', 'manage_shop_payment_terms'],
                'Import/Export' => ['export_shop_payments', 'export_shop_reports', 'import_shop_discounts', 'import_shop_payments'],
                'Publishing' => ['publish_acf-field-groups', 'publish_custom_csss', 'publish_shop_discounts', 'publish_shop_payments', 'publish_whatsapp-accounts']
            ]
        ],
        'Loco Translate' => [
            'Permissões do Loco Translate' => [
                'Loco Translate' => ['loco_admin']
            ]
        ],
        'Rank Math SEO' => [
            'Permissões do Rank Math' => [
                'Rank Math' => [
                    'rank_math_404_monitor',
                    'rank_math_admin_bar',
                    'rank_math_analytics',
                    'rank_math_content_ai',
                    'rank_math_edit_htaccess',
                    'rank_math_general',
                    'rank_math_link_builder',
                    'rank_math_onpage_advanced',
                    'rank_math_onpage_analysis',
                    'rank_math_onpage_general',
                    'rank_math_onpage_social',
                    'rank_math_redirections',
                    'rank_math_role_manager',
                    'rank_math_site_analysis',
                    'rank_math_sitemap',
                    'rank_math_titles'
                ]
            ]
        ],
        'Wordfence Security' => [
            'Permissões do Wordfence' => [
                'Wordfence' => ['wf2fa_activate_2fa_others', 'wf2fa_activate_2fa_self', 'wf2fa_manage_settings']
            ]
        ],
        'Outras Permissões' => [
            'Permissões Adicionais' => [
                'PYS' => ['manage_pys'],
                'Outras' => ['unfiltered_upload', 'unfiltered_html']
            ]
        ]
    ];
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

// Nova página de capabilities organizada por role
function mpa_capabilities_page()
{
    if (!current_user_can('manage_options')) {
        wp_die('Sem permissão.');
    }

    // Obter role selecionada
    $selected_role = sanitize_text_field($_GET['role'] ?? 'administrator');

    // Obter todas as roles disponíveis
    $available_roles = get_editable_roles();

    // Obter capabilities da role selecionada
    $role = get_role($selected_role);
    $current_capabilities = $role ? $role->capabilities : [];

    // Definir categorias organizadas como na imagem de referência
    $capability_categories = mpa_get_capability_categories();


    ?>
    <div class="wrap">
        <h1>Permissões da função</h1>

        <!-- Dropdown para seleção de role -->
        <div class="mpa-role-selector" style="margin-bottom: 20px;">
            <form method="get" style="display: inline-block;">
                <input type="hidden" name="page" value="mpa-capabilities">
                <select name="role" id="mpa-role-select" onchange="this.form.submit()" style="padding: 8px; font-size: 14px; min-width: 200px;">
                    <?php foreach ($available_roles as $role_key => $role_info): ?>
                        <option value="<?php echo esc_attr($role_key); ?>" <?php selected($selected_role, $role_key); ?>>
                            <?php echo esc_html($role_info['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>

        <!-- Formulário para salvar capabilities -->
        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
            <input type="hidden" name="action" value="mpa_save_role_capabilities">
            <input type="hidden" name="role" value="<?php echo esc_attr($selected_role); ?>">
            <?php wp_nonce_field('mpa_capability_nonce'); ?>

            <!-- Filtro por tipo de post -->
            <div class="mpa-post-filter" style="margin-bottom: 20px;">
                <label for="mpa-post-filter-select"><strong>Filtrar por tipo de post:</strong></label>
                <select id="mpa-post-filter-select" style="padding: 5px; margin-left: 10px;">
                    <option value="">Mostrar todos</option>
                    <option value="posts">Posts</option>
                    <option value="pages">Páginas</option>
                    <option value="media">Mídia</option>
                    <option value="products">Produtos</option>
                    <option value="orders">Pedidos</option>
                    <option value="coupons">Cupons</option>
                </select>
                <button type="button" class="button" id="mpa-limpar-filtro" style="margin-left: 10px;">Limpar</button>
            </div>

            <!-- Tabelas de capabilities organizadas por categoria -->
            <?php foreach ($capability_categories as $category_name => $category_data): ?>
                <div class="mpa-capability-category" style="margin-bottom: 30px;">
                    <h2 style="background: #f0f0f0; padding: 10px; margin: 0 0 15px 0; border-left: 4px solid #0073aa;">
                        <?php echo esc_html($category_name); ?>
                    </h2>

                    <?php foreach ($category_data as $section_name => $section_items): ?>
                        <div class="mpa-capability-section" style="margin-bottom: 25px;">
                            <h3 style="margin: 0 0 10px 0; font-size: 16px; color: #333;">
                                <?php echo esc_html($section_name); ?>
                            </h3>

                            <table class="widefat fixed striped mpa-capabilities-table">
                                <thead>
                                    <tr style="background: #f9f9f9;">
                                        <th style="width: 30%;">Tipo de Conteúdo</th>
                                        <th style="width: 70%;">
                                            Capabilities Disponíveis
                                            <button type="button" class="mpa-toggle-section button-secondary"
                                                    style="float: right; font-size: 11px; padding: 2px 8px; margin-left: 10px;"
                                                    data-section="<?php echo esc_attr($section_name); ?>"
                                                    title="Marcar/Desmarcar todas as capabilities desta seção">
                                                <span class="toggle-text">Marcar Todos</span>
                                            </button>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($section_items as $item_name => $capabilities): ?>
                                        <tr data-post-type="<?php echo strtolower($item_name); ?>">
                                            <td style="font-weight: bold; vertical-align: top; padding-top: 15px;">
                                                <?php echo esc_html($item_name); ?>
                                            </td>
                                            <td>
                                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; padding: 10px 0;">
                                                    <?php foreach ($capabilities as $capability): ?>
                                                        <label style="display: flex; align-items: center; gap: 8px; padding: 5px; background: #f9f9f9; border-radius: 3px;">
                                                            <input type="checkbox"
                                                                   name="capabilities[]"
                                                                   value="<?php echo esc_attr($capability); ?>"
                                                                   <?php checked(isset($current_capabilities[$capability]) && $current_capabilities[$capability]); ?>>
                                                            <code style="font-size: 11px; color: #666;"><?php echo esc_html($capability); ?></code>
                                                        </label>
                                                    <?php endforeach; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>

            <!-- Botão de salvar -->
            <p class="submit">
                <input type="submit" name="submit" id="submit" class="button button-primary" value="Salvar alterações">
            </p>
        </form>
    </div>

    <!-- JavaScript para filtros e funcionalidades -->
    <script>

    jQuery(document).ready(function($) {

        // Interceptar envio do formulário para confirmação
        $('form').on('submit', function(e) {
            const form = $(this);
            let capabilities = [];

            // Coletar todas as capabilities marcadas
            $('input[name="capabilities[]"]:checked').each(function() {
                capabilities.push($(this).val());
            });

            // Mostrar confirmação antes de enviar se nenhuma capability estiver marcada
            if (capabilities.length === 0) {
                if (!confirm('⚠️ NENHUMA capability foi marcada! Isso removerá todas as capabilities da role. Continuar?')) {
                    e.preventDefault();
                    return false;
                }
            }

            // Permitir envio normal do formulário
            return true;
        });


        // Filtro por tipo de post
        $('#mpa-post-filter-select').on('change', function() {
            const filterValue = $(this).val().toLowerCase();

            if (filterValue === '') {
                $('.mpa-capability-category').show();
                $('.mpa-capabilities-table tbody tr').show();
            } else {
                $('.mpa-capability-category').hide();
                $('.mpa-capabilities-table tbody tr').hide();

                // Mostrar apenas linhas que correspondem ao filtro
                $('.mpa-capabilities-table tbody tr').each(function() {
                    const postType = $(this).data('post-type');
                    if (postType && postType.includes(filterValue)) {
                        $(this).show();
                        $(this).closest('.mpa-capability-category').show();
                    }
                });
            }
        });

        // Limpar filtro
        $('#mpa-limpar-filtro').on('click', function() {
            $('#mpa-post-filter-select').val('');
            $('.mpa-capability-category').show();
            $('.mpa-capabilities-table tbody tr').show();
        });

        // Função para selecionar/deselecionar todas as capabilities de uma categoria
        $('.mpa-capability-category h2').on('click', function() {
            const category = $(this).closest('.mpa-capability-category');
            const checkboxes = category.find('input[type="checkbox"]');
            const allChecked = checkboxes.length === checkboxes.filter(':checked').length;

            checkboxes.prop('checked', !allChecked);
        });

        // Melhorar usabilidade - indicar quando categoria está parcialmente selecionada
        $('input[type="checkbox"]').on('change', function() {
            const category = $(this).closest('.mpa-capability-category');
            const checkboxes = category.find('input[type="checkbox"]');
            const checkedBoxes = checkboxes.filter(':checked');
            const categoryTitle = category.find('h2');

            if (checkedBoxes.length === 0) {
                categoryTitle.css('color', '#666');
            } else if (checkedBoxes.length === checkboxes.length) {
                categoryTitle.css('color', '#00a32a');
            } else {
                categoryTitle.css('color', '#d63638');
            }
        });

        // Inicializar cores das categorias
        $('.mpa-capability-category').each(function() {
            const checkboxes = $(this).find('input[type="checkbox"]');
            const checkedBoxes = checkboxes.filter(':checked');
            const categoryTitle = $(this).find('h2');

            if (checkedBoxes.length === 0) {
                categoryTitle.css('color', '#666');
            } else if (checkedBoxes.length === checkboxes.length) {
                categoryTitle.css('color', '#00a32a');
            } else {
                categoryTitle.css('color', '#d63638');
            }
        });

        // 🚀 NOVA FUNCIONALIDADE: Botões Marcar/Desmarcar Todos por Seção
        $('.mpa-toggle-section').on('click', function() {
            const button = $(this);
            const section = button.closest('.mpa-capability-section');
            const checkboxes = section.find('input[type="checkbox"]');
            const toggleText = button.find('.toggle-text');

            // Verificar estado atual (se todos estão marcados)
            const allChecked = checkboxes.length === checkboxes.filter(':checked').length;

            if (allChecked) {
                // Desmarcar todos
                checkboxes.prop('checked', false);
                toggleText.text('Marcar Todos');
                button.removeClass('button-primary').addClass('button-secondary');

                // Feedback visual
                button.css({
                    'background': '#f0f0f1',
                    'border-color': '#c3c4c7',
                    'color': '#50575e'
                });
            } else {
                // Marcar todos
                checkboxes.prop('checked', true);
                toggleText.text('Desmarcar Todos');
                button.removeClass('button-secondary').addClass('button-primary');

                // Feedback visual
                button.css({
                    'background': '#2271b1',
                    'border-color': '#2271b1',
                    'color': '#fff'
                });
            }

            // Disparar evento change para atualizar cores das categorias
            checkboxes.trigger('change');

            // Feedback tátil visual
            button.addClass('mpa-button-flash');
            setTimeout(() => {
                button.removeClass('mpa-button-flash');
            }, 200);
        });

        // Atualizar texto dos botões baseado no estado inicial
        $('.mpa-toggle-section').each(function() {
            const button = $(this);
            const section = button.closest('.mpa-capability-section');
            const checkboxes = section.find('input[type="checkbox"]');
            const toggleText = button.find('.toggle-text');

            const allChecked = checkboxes.length === checkboxes.filter(':checked').length;

            if (allChecked && checkboxes.length > 0) {
                toggleText.text('Desmarcar Todos');
                button.removeClass('button-secondary').addClass('button-primary');
            } else {
                toggleText.text('Marcar Todos');
                button.removeClass('button-primary').addClass('button-secondary');
            }
        });
    });
    </script>

    <!-- CSS para botões de toggle e melhorias visuais -->
    <style>
        /* Estilos para botões Marcar/Desmarcar Todos */
        .mpa-toggle-section {
            transition: all 0.3s ease;
            font-size: 11px !important;
            padding: 3px 10px !important;
            height: auto !important;
            line-height: 1.2 !important;
            border-radius: 3px !important;
        }

        .mpa-toggle-section:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .mpa-toggle-section:active {
            transform: translateY(0);
        }

        /* Efeito flash ao clicar */
        .mpa-button-flash {
            animation: buttonFlash 0.2s ease;
        }

        @keyframes buttonFlash {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        /* Melhorar cabeçalho da tabela */
        .mpa-capabilities-table thead th {
            position: relative;
            vertical-align: middle !important;
        }

        /* CSS responsivo para mobile */
        @media (max-width: 768px) {
            .mpa-toggle-section {
                float: none !important;
                display: block;
                margin-top: 8px;
                width: 100%;
            }
        }

        /* Indicador visual para seções com capabilities marcadas */
        .mpa-capability-section:has(input[type="checkbox"]:checked) .mpa-toggle-section {
            border-left: 3px solid #00a32a;
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

    foreach ($roles as $role_key => $role_info) {
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
    // Verificar permissões
    if (!current_user_can('manage_options')) {
        wp_die('Sem permissão.');
    }

    // Verificar nonce
    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'mpa_capability_nonce')) {
        wp_die('Falha na verificação de segurança.');
    }


    // Processar salvamento
    if (isset($_POST['role'])) {
        $role_key = sanitize_text_field($_POST['role']);
        $selected_caps = isset($_POST['capabilities']) && is_array($_POST['capabilities'])
            ? array_map('sanitize_text_field', $_POST['capabilities'])
            : [];


        $role = get_role($role_key);
        if ($role) {
            // Obter todas as capabilities que estão sendo gerenciadas pelo nosso sistema
            $managed_capabilities = [];
            $capability_categories = mpa_get_capability_categories();
            foreach ($capability_categories as $category_data) {
                foreach ($category_data as $section_items) {
                    foreach ($section_items as $capabilities) {
                        $managed_capabilities = array_merge($managed_capabilities, $capabilities);
                    }
                }
            }
            $managed_capabilities = array_unique($managed_capabilities);

            // Remover apenas as capabilities gerenciadas pelo nosso sistema
            foreach ($managed_capabilities as $cap) {
                if (isset($role->capabilities[$cap])) {
                    $role->remove_cap($cap);
                }
            }

            // Adicionar as capabilities selecionadas
            foreach ($selected_caps as $cap) {
                if (!empty($cap)) {
                    $role->add_cap($cap);
                }
            }

            // Redirecionar com sucesso
            wp_redirect(add_query_arg([
                'page' => 'mpa-capabilities',
                'role' => $role_key,
                'updated' => 'true'
            ], admin_url('admin.php')));
            exit;
        }
    }

    // Redirecionar com erro
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