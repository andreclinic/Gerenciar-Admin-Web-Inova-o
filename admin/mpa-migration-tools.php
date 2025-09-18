<?php
defined('ABSPATH') || exit;

/**
 * FERRAMENTAS DE MIGRAÇÃO E LIMPEZA DO SISTEMA DE MENUS
 *
 * Este arquivo contém funções para limpar dados antigos e migrar para o novo sistema
 * baseado em mpa_menu_settings_roles.
 */

/**
 * Lista todas as opções do sistema de menus (antigas e novas)
 */
function mpa_get_all_menu_options() {
    return [
        // SISTEMA NOVO (manter)
        'mpa_menu_settings_roles' => 'Sistema atual - configurações por role',

        // SISTEMA ANTIGO (remover)
        'mpa_menu_permissions' => 'ANTIGO - Permissões de menu por role',
        'mpa_menu_customizations' => 'ANTIGO - Customizações de títulos/ícones',
        'mpa_menu_order' => 'ANTIGO - Ordem dos menus',
        'mpa_custom_menus' => 'ANTIGO - Menus personalizados',
        'mpa_menu_settings' => 'ANTIGO - Configurações legacy',

        // OPÇÕES RELACIONADAS (verificar)
        'mpa_restricoes_menus_administrator' => 'ANTIGO - Restrições para administrator',
        'mpa_restricoes_menus_editor' => 'ANTIGO - Restrições para editor',
        'mpa_restricoes_menus_author' => 'ANTIGO - Restrições para author',
        'mpa_restricoes_menus_contributor' => 'ANTIGO - Restrições para contributor',
        'mpa_restricoes_menus_subscriber' => 'ANTIGO - Restrições para subscriber',
        'mpa_restricoes_menus_gerente' => 'ANTIGO - Restrições para gerente',
        'mpa_restricoes_menus_gerentes' => 'ANTIGO - Restrições para gerentes',
    ];
}

/**
 * Diagnostica o estado atual do banco de dados
 */
function mpa_diagnose_menu_system() {
    $options = mpa_get_all_menu_options();
    $found_options = [];
    $conflicting_data = false;

    foreach ($options as $option_name => $description) {
        $value = get_option($option_name, null);
        if ($value !== null && $value !== false && $value !== '') {
            $found_options[$option_name] = [
                'description' => $description,
                'size' => is_array($value) ? count($value) : strlen(serialize($value)),
                'type' => gettype($value),
                'has_data' => !empty($value)
            ];

            // Marcar como conflitante se for sistema antigo com dados
            if (strpos($description, 'ANTIGO') !== false && !empty($value)) {
                $conflicting_data = true;
            }
        }
    }

    return [
        'found_options' => $found_options,
        'has_conflicts' => $conflicting_data,
        'new_system_exists' => array_key_exists('mpa_menu_settings_roles', $found_options),
        'old_system_exists' => count(array_filter($found_options, function($opt) {
            return strpos($opt['description'], 'ANTIGO') !== false;
        })) > 0
    ];
}

/**
 * Migra dados do sistema antigo para o novo (se necessário)
 */
function mpa_migrate_old_to_new_system() {
    $migrated = [];
    $errors = [];

    try {
        // Verificar se já existe configuração nova
        $new_settings = get_option('mpa_menu_settings_roles', []);

        // Migrar permissões antigas
        $old_permissions = get_option('mpa_menu_permissions', []);
        if (!empty($old_permissions)) {
            foreach ($old_permissions as $role => $permissions) {
                if (!isset($new_settings[$role])) {
                    $new_settings[$role] = [];
                }

                // Converter permissões antigas para novo formato
                if (isset($permissions['menus'])) {
                    $remove_list = [];
                    foreach ($permissions['menus'] as $menu_slug => $allowed) {
                        if (!$allowed) {
                            $remove_list[] = $menu_slug;
                        }
                    }
                    if (!empty($remove_list)) {
                        $new_settings[$role]['remove'] = array_merge(
                            $new_settings[$role]['remove'] ?? [],
                            $remove_list
                        );
                    }
                }

                if (isset($permissions['submenus'])) {
                    $remove_submenu = [];
                    foreach ($permissions['submenus'] as $submenu_key => $allowed) {
                        if (!$allowed && strpos($submenu_key, '|') !== false) {
                            list($parent, $child) = explode('|', $submenu_key, 2);
                            if (!isset($remove_submenu[$parent])) {
                                $remove_submenu[$parent] = [];
                            }
                            $remove_submenu[$parent][] = $child;
                        }
                    }
                    if (!empty($remove_submenu)) {
                        $new_settings[$role]['remove_submenu'] = array_merge_recursive(
                            $new_settings[$role]['remove_submenu'] ?? [],
                            $remove_submenu
                        );
                    }
                }
            }
            $migrated[] = "Permissões de menu migradas";
        }

        // Migrar customizações antigas (renomes e ícones)
        $old_customizations = get_option('mpa_menu_customizations', []);
        if (!empty($old_customizations)) {
            foreach ($old_customizations as $menu_slug => $customization) {
                // Assumir que customizações se aplicam globalmente se não especificado
                if (!isset($new_settings['_global'])) {
                    $new_settings['_global'] = [];
                }

                if (isset($customization['title']) && !empty($customization['title'])) {
                    $new_settings['_global']['rename'][$menu_slug] = $customization['title'];
                }

                if (isset($customization['icon']) && !empty($customization['icon'])) {
                    $new_settings['_global']['icons'][$menu_slug] = $customization['icon'];
                }
            }
            $migrated[] = "Customizações de menus migradas";
        }

        // Migrar menus personalizados antigos
        $old_custom_menus = get_option('mpa_custom_menus', []);
        if (!empty($old_custom_menus)) {
            foreach ($old_custom_menus as $role => $menus) {
                if (!isset($new_settings[$role])) {
                    $new_settings[$role] = [];
                }
                if (!isset($new_settings[$role]['custom_menus'])) {
                    $new_settings[$role]['custom_menus'] = [];
                }

                foreach ($menus as $menu_id => $menu_data) {
                    $new_settings[$role]['custom_menus'][] = [
                        'id' => $menu_id,
                        'title' => $menu_data['title'] ?? 'Menu Personalizado',
                        'url' => $menu_data['url'] ?? '#',
                        'icon' => $menu_data['icon'] ?? 'dashicons-admin-generic',
                        'pos' => 80
                    ];
                }
            }
            $migrated[] = "Menus personalizados migrados";
        }

        // Migrar ordem de menus antiga
        $old_order = get_option('mpa_menu_order', []);
        if (!empty($old_order) && is_array($old_order)) {
            if (!isset($new_settings['_global'])) {
                $new_settings['_global'] = [];
            }
            $new_settings['_global']['order_menu'] = $old_order;
            $migrated[] = "Ordem de menus migrada";
        }

        // Salvar configurações migradas
        if (!empty($migrated)) {
            update_option('mpa_menu_settings_roles', $new_settings);
        }

    } catch (Exception $e) {
        $errors[] = "Erro durante migração: " . $e->getMessage();
    }

    return [
        'migrated' => $migrated,
        'errors' => $errors,
        'success' => empty($errors)
    ];
}

/**
 * Remove TODAS as opções antigas do sistema de menus
 */
function mpa_clean_old_menu_system() {
    $deleted = [];
    $errors = [];

    $old_options = [
        'mpa_menu_permissions',
        'mpa_menu_customizations',
        'mpa_menu_order',
        'mpa_custom_menus',
        'mpa_menu_settings',
        'mpa_restricoes_menus_administrator',
        'mpa_restricoes_menus_editor',
        'mpa_restricoes_menus_author',
        'mpa_restricoes_menus_contributor',
        'mpa_restricoes_menus_subscriber',
        'mpa_restricoes_menus_gerente',
        'mpa_restricoes_menus_gerentes',
    ];

    foreach ($old_options as $option_name) {
        $existed = get_option($option_name, null) !== null;
        if ($existed) {
            $result = delete_option($option_name);
            if ($result) {
                $deleted[] = $option_name;
            } else {
                $errors[] = "Falha ao deletar: $option_name";
            }
        }
    }

    return [
        'deleted' => $deleted,
        'errors' => $errors,
        'success' => empty($errors)
    ];
}

/**
 * Reset completo: limpa tudo e recomeça
 */
function mpa_complete_reset_menu_system() {
    $results = [];

    // 1. Limpar sistema antigo
    $clean_result = mpa_clean_old_menu_system();
    $results['cleanup'] = $clean_result;

    // 2. Limpar sistema novo também (reset completo)
    $new_deleted = delete_option('mpa_menu_settings_roles');
    $results['new_system_reset'] = $new_deleted;

    // 3. Inicializar sistema novo vazio
    $initial_settings = [
        '_global' => [
            'custom_menus' => [],
            'rename' => [],
            'remove' => [],
            'icons' => [],
            'order_menu' => []
        ]
    ];

    update_option('mpa_menu_settings_roles', $initial_settings);
    $results['initialized'] = true;

    return $results;
}

/**
 * ADMIN HANDLERS para executar via interface
 */

// Handler para diagnóstico
add_action('wp_ajax_mpa_diagnose_system', function() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permissão negada');
    }

    $diagnosis = mpa_diagnose_menu_system();
    wp_send_json_success($diagnosis);
});

// Handler para migração
add_action('wp_ajax_mpa_migrate_system', function() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permissão negada');
    }

    $migration = mpa_migrate_old_to_new_system();
    wp_send_json_success($migration);
});

// Handler para limpeza
add_action('wp_ajax_mpa_clean_old_system', function() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permissão negada');
    }

    $cleanup = mpa_clean_old_menu_system();
    wp_send_json_success($cleanup);
});

// Handler para reset completo
add_action('wp_ajax_mpa_complete_reset', function() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permissão negada');
    }

    $reset = mpa_complete_reset_menu_system();
    wp_send_json_success($reset);
});

/**
 * Interface administrativa
 */
function mpa_render_migration_tools_page() {
    $diagnosis = mpa_diagnose_menu_system();
    ?>
    <div class="wrap">
        <h1>🔧 Ferramentas de Migração - Sistema de Menus</h1>

        <div class="notice notice-info">
            <p><strong>Atenção:</strong> Use essas ferramentas para resolver conflitos entre versões antigas e novas do sistema de menus.</p>
        </div>

        <h2>📊 Diagnóstico Atual</h2>
        <table class="widefat">
            <thead>
                <tr>
                    <th>Status</th>
                    <th>Descrição</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?php echo $diagnosis['new_system_exists'] ? '✅' : '❌'; ?></td>
                    <td>Sistema novo (mpa_menu_settings_roles) existe</td>
                </tr>
                <tr>
                    <td><?php echo $diagnosis['old_system_exists'] ? '⚠️' : '✅'; ?></td>
                    <td>Sistema antigo <?php echo $diagnosis['old_system_exists'] ? 'encontrado (pode causar conflitos)' : 'não encontrado'; ?></td>
                </tr>
                <tr>
                    <td><?php echo $diagnosis['has_conflicts'] ? '🔴' : '🟢'; ?></td>
                    <td>Conflitos detectados: <?php echo $diagnosis['has_conflicts'] ? 'SIM' : 'NÃO'; ?></td>
                </tr>
            </tbody>
        </table>

        <h3>Opções Encontradas no Banco</h3>
        <table class="widefat">
            <thead>
                <tr>
                    <th>Opção</th>
                    <th>Descrição</th>
                    <th>Tipo</th>
                    <th>Tamanho</th>
                    <th>Tem Dados</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($diagnosis['found_options'] as $option_name => $info): ?>
                <tr>
                    <td><code><?php echo esc_html($option_name); ?></code></td>
                    <td><?php echo esc_html($info['description']); ?></td>
                    <td><?php echo esc_html($info['type']); ?></td>
                    <td><?php echo esc_html($info['size']); ?></td>
                    <td><?php echo $info['has_data'] ? '✅' : '❌'; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h2>🛠️ Ações Disponíveis</h2>

        <div class="card">
            <h3>1. Migração Suave (Recomendado)</h3>
            <p>Migra dados do sistema antigo para o novo e mantém ambos temporariamente.</p>
            <button class="button button-secondary" onclick="mpaMigrationAction('migrate')">
                Migrar Dados Antigos
            </button>
        </div>

        <div class="card">
            <h3>2. Limpeza do Sistema Antigo</h3>
            <p><strong>Cuidado:</strong> Remove permanentemente todas as opções antigas. Use após confirmar que a migração funcionou.</p>
            <button class="button button-secondary" onclick="mpaMigrationAction('clean')">
                Limpar Sistema Antigo
            </button>
        </div>

        <div class="card">
            <h3>3. Reset Completo (PERIGOSO)</h3>
            <p><strong>⚠️ ATENÇÃO:</strong> Remove TODOS os dados (antigos e novos) e reinicia o sistema do zero.</p>
            <button class="button button-secondary" onclick="mpaMigrationAction('reset')"
                    style="background-color: #dc3545; color: white;">
                Reset Completo
            </button>
        </div>

        <div id="mpa-migration-result" style="margin-top: 20px;"></div>
    </div>

    <script>
    function mpaMigrationAction(action) {
        const resultDiv = document.getElementById('mpa-migration-result');
        resultDiv.innerHTML = '<div class="notice notice-info"><p>Processando...</p></div>';

        let actionMap = {
            'migrate': 'mpa_migrate_system',
            'clean': 'mpa_clean_old_system',
            'reset': 'mpa_complete_reset'
        };

        if (action === 'reset' && !confirm('⚠️ CUIDADO: Esta ação irá remover TODAS as configurações de menu e não pode ser desfeita. Continuar?')) {
            resultDiv.innerHTML = '<div class="notice notice-warning"><p>Ação cancelada.</p></div>';
            return;
        }

        fetch(ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: actionMap[action],
                _ajax_nonce: '<?php echo wp_create_nonce('wp_ajax'); ?>'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                resultDiv.innerHTML = '<div class="notice notice-success"><p><strong>Sucesso!</strong></p><pre>' + JSON.stringify(data.data, null, 2) + '</pre></div>';
                // Recarregar página após 3 segundos para mostrar novos dados
                setTimeout(() => window.location.reload(), 3000);
            } else {
                resultDiv.innerHTML = '<div class="notice notice-error"><p><strong>Erro:</strong> ' + data.data + '</p></div>';
            }
        })
        .catch(error => {
            resultDiv.innerHTML = '<div class="notice notice-error"><p><strong>Erro:</strong> ' + error.message + '</p></div>';
        });
    }
    </script>

    <style>
    .card {
        background: white;
        border: 1px solid #ccd0d4;
        border-radius: 4px;
        padding: 20px;
        margin: 10px 0;
    }
    .card h3 {
        margin-top: 0;
    }
    pre {
        background: #f1f1f1;
        padding: 10px;
        border-radius: 3px;
        max-height: 300px;
        overflow-y: auto;
    }
    </style>
    <?php
}