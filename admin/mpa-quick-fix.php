<?php
defined('ABSPATH') || exit;

/**
 * 🚀 SOLUÇÃO RÁPIDA PARA PROBLEMAS DE PRODUÇÃO
 *
 * Execute este código para resolver conflitos entre sistema antigo e novo
 */

/**
 * Limpa completamente dados antigos que podem estar causando conflito
 */
function mpa_quick_fix_production() {
    // Verificar permissões
    if (!current_user_can('manage_options')) {
        return ['error' => 'Sem permissões para executar esta operação'];
    }

    $results = [];
    $cleaned = [];
    $errors = [];

    // OPÇÕES ANTIGAS QUE DEVEM SER REMOVIDAS
    $old_options = [
        'mpa_menu_permissions',
        'mpa_menu_customizations',
        'mpa_menu_order',
        'mpa_custom_menus',
        'mpa_menu_settings', // Legacy

        // Restrições por role antigas
        'mpa_restricoes_menus_administrator',
        'mpa_restricoes_menus_editor',
        'mpa_restricoes_menus_author',
        'mpa_restricoes_menus_contributor',
        'mpa_restricoes_menus_subscriber',
        'mpa_restricoes_menus_gerente',
        'mpa_restricoes_menus_gerentes',
    ];

    // Remover todas as opções antigas
    foreach ($old_options as $option_name) {
        $existed = get_option($option_name, null) !== null;
        if ($existed) {
            $result = delete_option($option_name);
            if ($result) {
                $cleaned[] = $option_name;
            } else {
                $errors[] = "Falha ao deletar: $option_name";
            }
        }
    }

    // Garantir que o sistema novo existe com configuração mínima
    $current_new_settings = get_option('mpa_menu_settings_roles', []);
    if (empty($current_new_settings)) {
        $initial_settings = [
            '_global' => [
                'custom_menus' => [],
                'rename' => [],
                'remove' => [],
                'icons' => [],
                'order_menu' => [],
                'remove_submenu' => [],
                'rename_submenu' => []
            ]
        ];
        update_option('mpa_menu_settings_roles', $initial_settings);
        $results[] = "Sistema novo inicializado com configuração padrão";
    }

    return [
        'success' => empty($errors),
        'cleaned_options' => $cleaned,
        'errors' => $errors,
        'message' => empty($errors) ? 'Limpeza concluída com sucesso!' : 'Limpeza parcial realizada',
        'total_cleaned' => count($cleaned)
    ];
}

/**
 * Handler AJAX para execução via admin
 */
add_action('wp_ajax_mpa_quick_fix', function() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permissão negada');
    }

    $result = mpa_quick_fix_production();

    if ($result['success']) {
        wp_send_json_success($result);
    } else {
        wp_send_json_error($result);
    }
});

/**
 * Interface simples para executar o fix
 */
function mpa_render_quick_fix_page() {
    ?>
    <div class="wrap">
        <h1>🚀 Correção Rápida - Sistema de Menus</h1>

        <div class="notice notice-warning">
            <p><strong>⚠️ ATENÇÃO:</strong> Esta função remove completamente dados antigos que podem estar causando conflitos.</p>
            <p>Use apenas se estiver enfrentando problemas com o gerenciamento de menus em produção.</p>
        </div>

        <div class="card">
            <h2>O que esta correção faz:</h2>
            <ul>
                <li>✅ Remove todas as opções antigas do sistema de menus</li>
                <li>✅ Limpa dados conflitantes do banco de dados</li>
                <li>✅ Inicializa o sistema novo se necessário</li>
                <li>✅ Mantém configurações atuais do sistema novo</li>
            </ul>

            <h3>Opções que serão removidas:</h3>
            <ul style="font-family: monospace; font-size: 12px;">
                <li>mpa_menu_permissions</li>
                <li>mpa_menu_customizations</li>
                <li>mpa_menu_order</li>
                <li>mpa_custom_menus</li>
                <li>mpa_menu_settings</li>
                <li>mpa_restricoes_menus_* (todas as roles)</li>
            </ul>

            <p style="margin-top: 20px;">
                <button id="mpa-quick-fix-btn" class="button button-primary" onclick="mpaExecuteQuickFix()">
                    🚀 Executar Correção Rápida
                </button>
            </p>
        </div>

        <div id="mpa-quick-fix-result" style="margin-top: 20px;"></div>
    </div>

    <script>
    function mpaExecuteQuickFix() {
        const button = document.getElementById('mpa-quick-fix-btn');
        const resultDiv = document.getElementById('mpa-quick-fix-result');

        button.disabled = true;
        button.textContent = '⏳ Executando...';

        resultDiv.innerHTML = '<div class="notice notice-info"><p>Processando correção...</p></div>';

        fetch(ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'mpa_quick_fix',
                _ajax_nonce: '<?php echo wp_create_nonce('wp_ajax'); ?>'
            })
        })
        .then(response => response.json())
        .then(data => {
            button.disabled = false;
            button.textContent = '🚀 Executar Correção Rápida';

            if (data.success) {
                resultDiv.innerHTML = `
                    <div class="notice notice-success">
                        <h3>✅ Correção Executada com Sucesso!</h3>
                        <p><strong>Opções removidas:</strong> ${data.data.total_cleaned}</p>
                        <p><strong>Detalhes:</strong> ${data.data.message}</p>
                        ${data.data.cleaned_options.length > 0 ?
                            '<p><strong>Removidas:</strong> ' + data.data.cleaned_options.join(', ') + '</p>' :
                            ''}
                        <p style="margin-top: 15px; padding: 10px; background: #f0f8ff; border-left: 4px solid #0073aa;">
                            <strong>📋 Próximos passos:</strong><br>
                            1. Vá para <a href="<?php echo admin_url('admin.php?page=mpa-menu-roles'); ?>">Menus por Role</a><br>
                            2. Configure os menus conforme necessário<br>
                            3. Teste o funcionamento do sistema
                        </p>
                    </div>
                `;
            } else {
                resultDiv.innerHTML = `
                    <div class="notice notice-error">
                        <h3>❌ Erro na Execução</h3>
                        <p>${data.data ? data.data.message : 'Erro desconhecido'}</p>
                        ${data.data && data.data.errors ?
                            '<p><strong>Erros:</strong><br>' + data.data.errors.join('<br>') + '</p>' :
                            ''}
                    </div>
                `;
            }
        })
        .catch(error => {
            button.disabled = false;
            button.textContent = '🚀 Executar Correção Rápida';
            resultDiv.innerHTML = `
                <div class="notice notice-error">
                    <p><strong>Erro:</strong> ${error.message}</p>
                </div>
            `;
        });
    }
    </script>

    <style>
    .card {
        background: white;
        border: 1px solid #ccd0d4;
        border-radius: 4px;
        padding: 20px;
        margin: 20px 0;
    }
    .card h2 {
        margin-top: 0;
        color: #23282d;
    }
    .card ul {
        margin-left: 20px;
    }
    .card li {
        margin-bottom: 5px;
    }
    #mpa-quick-fix-btn {
        font-size: 16px;
        padding: 10px 20px;
        height: auto;
    }
    </style>
    <?php
}
?>