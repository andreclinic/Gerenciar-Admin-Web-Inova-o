<?php
/**
 * Analytics Settings Page View
 * 
 * Página de configurações para autenticação e parâmetros do GA4
 * 
 * @package Gerenciar_Admin
 * @since 1.0.0
 */

// Evitar acesso direto
if (!defined('ABSPATH')) {
    exit;
}

// Obter configurações salvas
$ga4_settings = MPA_Analytics_Page::get_ga4_settings();
$is_connected = !empty($ga4_settings['access_token']) && time() < $ga4_settings['token_expires'];
?>

<div class="mpa-analytics-settings">
    <div class="mpa-settings-header">
        <h1>⚙️ Configurações do Google Analytics GA4</h1>
        <p class="mpa-settings-description">
            Configure a integração com o Google Analytics GA4 para visualizar as métricas do seu site.
        </p>
    </div>

    <?php if ($is_connected): ?>
        <div class="mpa-connection-status mpa-status-connected">
            <div class="mpa-status-icon">✅</div>
            <div class="mpa-status-content">
                <h3>Conectado ao Google Analytics</h3>
                <p>A integração está ativa e funcionando corretamente.</p>
                <p><strong>Property ID:</strong> <?php echo esc_html($ga4_settings['property_id']); ?></p>
                <p><strong>Token expira em:</strong> <?php echo date('d/m/Y H:i', $ga4_settings['token_expires']); ?></p>
            </div>
        </div>
    <?php else: ?>
        <div class="mpa-connection-status mpa-status-disconnected">
            <div class="mpa-status-icon">⚠️</div>
            <div class="mpa-status-content">
                <h3>Não conectado ao Google Analytics</h3>
                <p>Configure as credenciais abaixo para começar a usar as métricas.</p>
            </div>
        </div>
    <?php endif; ?>

    <!-- Instruções -->
    <div class="mpa-instructions-card">
        <h3>📋 Como configurar a integração</h3>
        <ol class="mpa-instructions-list">
            <li>
                <strong>Acesse o Google Cloud Console:</strong>
                <a href="https://console.cloud.google.com/" target="_blank" class="mpa-external-link">
                    https://console.cloud.google.com/ 🔗
                </a>
            </li>
            <li><strong>Crie um novo projeto</strong> ou selecione um existente</li>
            <li><strong>Habilite a API do Google Analytics</strong> no projeto</li>
            <li><strong>Crie credenciais OAuth 2.0</strong> para aplicação web</li>
            <li><strong>Configure as URLs de redirecionamento:</strong>
                <code><?php echo admin_url('admin.php?page=' . MPA_Analytics_Page::SETTINGS_SLUG); ?></code>
            </li>
            <li><strong>Copie o Client ID e Client Secret</strong> gerados</li>
            <li><strong>No Google Analytics,</strong> obtenha o Property ID da sua propriedade GA4</li>
        </ol>
    </div>

    <!-- Formulário de configuração -->
    <form method="post" action="" class="mpa-settings-form">
        <?php wp_nonce_field('mpa_save_ga4_settings', 'mpa_ga4_nonce'); ?>
        
        <div class="mpa-form-section">
            <h3>🔑 Credenciais OAuth</h3>
            
            <div class="mpa-form-group">
                <label for="ga4_client_id">Client ID *</label>
                <input 
                    type="text" 
                    id="ga4_client_id" 
                    name="ga4_client_id" 
                    value="<?php echo esc_attr($ga4_settings['client_id']); ?>"
                    placeholder="1234567890-abcdefghijklmnop.apps.googleusercontent.com"
                    required
                    class="mpa-form-input"
                />
                <p class="mpa-form-help">Client ID obtido no Google Cloud Console</p>
            </div>

            <div class="mpa-form-group">
                <label for="ga4_client_secret">Client Secret *</label>
                <input
                    type="password"
                    id="ga4_client_secret"
                    name="ga4_client_secret"
                    value="<?php echo !empty($ga4_settings['client_secret']) ? '••••••••••••••••••••••••••••••••' : ''; ?>"
                    placeholder="GOCSPX-abcdefghijklmnopqrstuvwxyz"
                    <?php echo !empty($ga4_settings['client_secret']) ? '' : 'required'; ?>
                    class="mpa-form-input"
                />
                <?php if (!empty($ga4_settings['client_secret'])): ?>
                    <p class="mpa-form-help">✅ <strong>Client Secret configurado</strong> - deixe vazio para manter o atual</p>
                <?php else: ?>
                    <p class="mpa-form-help">Client Secret obtido no Google Cloud Console</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="mpa-form-section">
            <h3>📊 Propriedade do Google Analytics</h3>
            
            <div class="mpa-form-group">
                <label for="ga4_property_id">Property ID *</label>
                <input 
                    type="text" 
                    id="ga4_property_id" 
                    name="ga4_property_id" 
                    value="<?php echo esc_attr($ga4_settings['property_id']); ?>"
                    placeholder="123456789"
                    required
                    class="mpa-form-input"
                />
                <p class="mpa-form-help">ID da propriedade GA4 (encontrado em Admin > Informações da propriedade)</p>
            </div>

            <div class="mpa-form-group">
                <label for="ga4_data_stream_id">Data Stream ID</label>
                <input 
                    type="text" 
                    id="ga4_data_stream_id" 
                    name="ga4_data_stream_id" 
                    value="<?php echo esc_attr($ga4_settings['data_stream_id']); ?>"
                    placeholder="987654321"
                    class="mpa-form-input"
                />
                <p class="mpa-form-help">ID do fluxo de dados (opcional, para métricas específicas)</p>
            </div>
        </div>

        <div class="mpa-form-actions">
            <button type="submit" name="submit" class="mpa-btn mpa-btn-primary">
                💾 Salvar Configurações
            </button>
            
            <?php if (!empty($ga4_settings['client_id']) && !empty($ga4_settings['property_id'])): ?>
                <?php if (!$is_connected): ?>
                    <button type="button" id="startOAuth" class="mpa-btn mpa-btn-primary">
                        🔗 Conectar com Google Analytics
                    </button>
                <?php endif; ?>
                
                <button type="button" id="testConnection" class="mpa-btn mpa-btn-secondary">
                    🔍 Testar Conexão
                </button>
            <?php endif; ?>
            
            <?php if ($is_connected): ?>
                <button type="button" id="disconnectGA4" class="mpa-btn mpa-btn-danger">
                    🔌 Desconectar
                </button>
            <?php endif; ?>
        </div>
    </form>

    <!-- Seção de diagnóstico -->
    <div class="mpa-diagnostic-section">
        <h3>🔧 Diagnóstico da Conexão</h3>
        <div id="diagnosticResults" class="mpa-diagnostic-results">
            <p>Clique em "Testar Conexão" para verificar a configuração.</p>
        </div>
    </div>

    <!-- Log de atividades -->
    <div class="mpa-activity-log">
        <h3>📝 Log de Atividades</h3>
        <div class="mpa-log-container">
            <?php
            $activity_log = get_option('mpa_ga4_activity_log', array());
            if (!empty($activity_log)):
                $activity_log = array_reverse(array_slice($activity_log, -10)); // Últimas 10 atividades
                foreach ($activity_log as $entry):
            ?>
                <div class="mpa-log-entry">
                    <span class="mpa-log-timestamp"><?php echo esc_html($entry['timestamp']); ?></span>
                    <span class="mpa-log-message <?php echo esc_attr($entry['type']); ?>">
                        <?php echo esc_html($entry['message']); ?>
                    </span>
                </div>
            <?php 
                endforeach;
            else:
            ?>
                <p class="mpa-no-log">Nenhuma atividade registrada ainda.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Links úteis -->
    <div class="mpa-useful-links">
        <h3>🔗 Links Úteis</h3>
        <ul>
            <li>
                <a href="https://console.cloud.google.com/" target="_blank">
                    Google Cloud Console 🔗
                </a>
            </li>
            <li>
                <a href="https://analytics.google.com/" target="_blank">
                    Google Analytics 🔗
                </a>
            </li>
            <li>
                <a href="https://developers.google.com/analytics/devguides/reporting/data/v1" target="_blank">
                    GA4 Reporting API Documentation 🔗
                </a>
            </li>
            <li>
                <a href="<?php echo admin_url('admin.php?page=' . MPA_Analytics_Page::PAGE_SLUG); ?>">
                    Voltar ao Dashboard Analytics
                </a>
            </li>
        </ul>
    </div>
</div>

<!-- Modal de confirmação para desconectar -->
<div id="disconnectModal" class="mpa-modal" style="display: none;">
    <div class="mpa-modal-content">
        <div class="mpa-modal-header">
            <h3>Confirmar Desconexão</h3>
            <button class="mpa-modal-close">&times;</button>
        </div>
        <div class="mpa-modal-body">
            <p>Tem certeza de que deseja desconectar o Google Analytics?</p>
            <p>Isso irá remover todos os tokens de acesso salvos e você precisará reconectar para ver as métricas novamente.</p>
        </div>
        <div class="mpa-modal-footer">
            <button id="confirmDisconnect" class="mpa-btn mpa-btn-danger">Sim, Desconectar</button>
            <button class="mpa-modal-close mpa-btn mpa-btn-secondary">Cancelar</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Iniciar OAuth
    const oauthBtn = document.getElementById('startOAuth');
    if (oauthBtn) {
        oauthBtn.addEventListener('click', function() {
            oauthBtn.disabled = true;
            oauthBtn.innerHTML = '🔄 Conectando...';
            
            // Fazer requisição AJAX para iniciar OAuth
            fetch(ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'mpa_start_oauth',
                    nonce: '<?php echo wp_create_nonce('mpa_analytics_nonce'); ?>'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Redirecionar para autorização do Google
                    window.location.href = data.data.auth_url;
                } else {
                    alert('Erro ao iniciar autorização: ' + (data.data || 'Erro desconhecido'));
                    oauthBtn.disabled = false;
                    oauthBtn.innerHTML = '🔗 Conectar com Google Analytics';
                }
            })
            .catch(error => {
                alert('Erro na requisição: ' + error.message);
                oauthBtn.disabled = false;
                oauthBtn.innerHTML = '🔗 Conectar com Google Analytics';
            });
        });
    }
    
    // Testar conexão
    const testBtn = document.getElementById('testConnection');
    if (testBtn) {
        testBtn.addEventListener('click', function() {
            const diagnosticResults = document.getElementById('diagnosticResults');
            
            // Mostrar loading
            diagnosticResults.innerHTML = '<div class="mpa-loading-inline">🔄 Testando conexão...</div>';
            testBtn.disabled = true;
            
            // Fazer requisição AJAX
            fetch(ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'mpa_test_ga4_connection',
                    nonce: '<?php echo wp_create_nonce('mpa_analytics_nonce'); ?>'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    diagnosticResults.innerHTML = `
                        <div class="mpa-diagnostic-success">
                            ✅ <strong>Conexão estabelecida com sucesso!</strong><br>
                            ${data.data.message || 'Integração funcionando corretamente.'}
                        </div>
                    `;
                } else {
                    diagnosticResults.innerHTML = `
                        <div class="mpa-diagnostic-error">
                            ❌ <strong>Erro na conexão:</strong><br>
                            ${data.data || 'Erro desconhecido'}
                        </div>
                    `;
                }
            })
            .catch(error => {
                diagnosticResults.innerHTML = `
                    <div class="mpa-diagnostic-error">
                        ❌ <strong>Erro na requisição:</strong><br>
                        ${error.message}
                    </div>
                `;
            })
            .finally(() => {
                testBtn.disabled = false;
            });
        });
    }
    
    // Modal de desconectar
    const disconnectBtn = document.getElementById('disconnectGA4');
    const disconnectModal = document.getElementById('disconnectModal');
    const confirmDisconnectBtn = document.getElementById('confirmDisconnect');
    
    if (disconnectBtn && disconnectModal) {
        disconnectBtn.addEventListener('click', function() {
            disconnectModal.style.display = 'flex';
        });
        
        // Fechar modal
        document.querySelectorAll('.mpa-modal-close').forEach(btn => {
            btn.addEventListener('click', function() {
                disconnectModal.style.display = 'none';
            });
        });
        
        // Confirmar desconexão
        if (confirmDisconnectBtn) {
            confirmDisconnectBtn.addEventListener('click', function() {
                // Fazer requisição para desconectar
                fetch(ajaxurl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'mpa_disconnect_ga4',
                        nonce: '<?php echo wp_create_nonce('mpa_analytics_nonce'); ?>'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Erro ao desconectar: ' + (data.data || 'Erro desconhecido'));
                    }
                });
            });
        }
    }
});
</script>

<style>
/* Estilos específicos para botões da página Analytics Config */
.mpa-btn {
    display: inline-flex;
    align-items: center;
    padding: 8px 16px;
    font-size: 14px;
    font-weight: 500;
    line-height: 20px;
    border: 1px solid;
    border-radius: 6px;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.2s ease;
    margin-right: 8px;
    margin-bottom: 8px;
}

.mpa-btn-primary {
    background-color: #2563eb !important;
    border-color: #2563eb !important;
    color: #ffffff !important;
}

.mpa-btn-primary:hover {
    background-color: #1d4ed8 !important;
    border-color: #1d4ed8 !important;
    color: #ffffff !important;
}

.mpa-btn-secondary {
    background-color: #f3f4f6 !important;
    border-color: #d1d5db !important;
    color: #374151 !important;
}

.mpa-btn-secondary:hover {
    background-color: #e5e7eb !important;
    border-color: #9ca3af !important;
    color: #111827 !important;
}

.mpa-btn-danger {
    background-color: #ef4444 !important;
    border-color: #ef4444 !important;
    color: #ffffff !important;
}

.mpa-btn-danger:hover {
    background-color: #dc2626 !important;
    border-color: #dc2626 !important;
    color: #ffffff !important;
}

.mpa-btn:disabled {
    opacity: 0.6 !important;
    cursor: not-allowed !important;
}

/* Dark mode support */
body.dark-mode .mpa-btn-secondary,
.dark-mode .mpa-btn-secondary {
    background-color: #374151 !important;
    border-color: #4b5563 !important;
    color: #d1d5db !important;
}

body.dark-mode .mpa-btn-secondary:hover,
.dark-mode .mpa-btn-secondary:hover {
    background-color: #4b5563 !important;
    border-color: #6b7280 !important;
    color: #f9fafb !important;
}
</style>