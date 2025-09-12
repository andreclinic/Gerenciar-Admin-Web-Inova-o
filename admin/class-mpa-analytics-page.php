<?php
/**
 * MPA Analytics Page Class
 * 
 * Responsável por registrar o menu/submenu, renderizar a view 
 * e enfileirar os assets necessários (chart.js, CSS, JS)
 * 
 * @package Gerenciar_Admin
 * @since 1.0.0
 */

// Evitar acesso direto
if (!defined('ABSPATH')) {
    exit;
}

class MPA_Analytics_Page {
    
    /**
     * Hook prefix para ações
     */
    const HOOK_PREFIX = 'mpa_analytics_';
    
    /**
     * Slug da página principal
     */
    const PAGE_SLUG = 'mpa-analytics';
    
    /**
     * Slug da página de configurações
     */
    const SETTINGS_SLUG = 'mpa-config-analytics';
    
    /**
     * Constructor
     */
    public function __construct() {
        // Usar prioridade 15 para garantir que execute DEPOIS do menu principal (prioridade 10)
        add_action('admin_menu', array($this, 'register_menu_pages'), 15);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('wp_ajax_mpa_test_ga4_connection', array($this, 'test_ga4_connection'));
        
        // Carregar Analytics Client
        $this->load_analytics_client();
    }
    
    /**
     * Carregar a classe Analytics Client
     */
    private function load_analytics_client() {
        if (!class_exists('MPA_Analytics_Client')) {
            require_once plugin_dir_path(__FILE__) . '../includes/class-mpa-analytics-client.php';
            new MPA_Analytics_Client();
        }
    }
    
    /**
     * Registrar páginas do menu/submenu
     * NOTA: Os menus agora são registrados em mpa-menu-manager.php como submenus de "Gerenciar Admin"
     */
    public function register_menu_pages() {
        // Menus movidos para mpa-menu-manager.php - manter este método por compatibilidade
        // mas não registrar os menus aqui para evitar duplicação
        return;
    }
    
    /**
     * Enfileirar assets CSS e JS
     */
    public function enqueue_assets($hook_suffix) {
        // Debug: Log do hook suffix
        error_log('[MPA DEBUG] Hook suffix atual: ' . $hook_suffix);
        
        // Verificar se estamos na página do analytics (método mais abrangente)
        $current_page = isset($_GET['page']) ? $_GET['page'] : '';
        $is_analytics_page = ($current_page === self::PAGE_SLUG || $current_page === self::SETTINGS_SLUG);
        
        error_log('[MPA DEBUG] Current page: ' . $current_page);
        error_log('[MPA DEBUG] Is analytics page: ' . ($is_analytics_page ? 'YES' : 'NO'));
        
        if (!$is_analytics_page) {
            return;
        }
        
        error_log('[MPA DEBUG] Carregando assets para página Analytics');
        
        // Enfileirar Chart.js - mover para header para garantir carregamento
        wp_enqueue_script(
            'chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js',
            array(),
            '4.4.0',
            false  // Carregar no header ao invés do footer
        );
        
        error_log('[MPA DEBUG] Chart.js enfileirado');
        
        // Enfileirar CSS do Analytics
        wp_enqueue_style(
            'mpa-analytics-css',
            plugin_dir_url(__FILE__) . '../assets/css/mpa-analytics.css',
            array(),
            filemtime(plugin_dir_path(__FILE__) . '../assets/css/mpa-analytics.css')
        );
        
        // Enfileirar JS do Analytics
        wp_enqueue_script(
            'mpa-analytics-js',
            plugin_dir_url(__FILE__) . '../assets/js/mpa-analytics.js',
            array('jquery', 'chartjs'),
            filemtime(plugin_dir_path(__FILE__) . '../assets/js/mpa-analytics.js'),
            true
        );
        
        // Localizar script para AJAX
        wp_localize_script('mpa-analytics-js', 'mpaAnalytics', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'restUrl' => rest_url('mpa/v1/analytics/'),
            'nonce' => wp_create_nonce('wp_rest'), // Usar nonce correto para REST API
            'ajaxNonce' => wp_create_nonce('mpa_analytics_nonce'), // Manter nonce separado para AJAX
            'strings' => array(
                'loading' => __('Carregando...', 'gerenciar-admin'),
                'error' => __('Erro ao carregar dados', 'gerenciar-admin'),
                'noData' => __('Nenhum dado disponível', 'gerenciar-admin'),
                'connectionSuccess' => __('Conexão com GA4 estabelecida com sucesso!', 'gerenciar-admin'),
                'connectionError' => __('Erro ao conectar com GA4. Verifique as credenciais.', 'gerenciar-admin')
            )
        ));
    }
    
    /**
     * Verificar se estamos na página do analytics
     */
    private function is_analytics_page($hook_suffix) {
        $analytics_pages = array(
            'gerenciar-admin-web-inovacao_page_' . self::PAGE_SLUG,
            'gerenciar-admin-web-inovacao_page_' . self::SETTINGS_SLUG
        );
        
        return in_array($hook_suffix, $analytics_pages);
    }
    
    /**
     * Renderizar página principal do Analytics
     */
    public function render_analytics_page() {
        // Verificar permissões
        if (!current_user_can('manage_options')) {
            wp_die(__('Você não tem permissão para acessar esta página.'));
        }
        
        // Verificar se GA4 está configurado
        $ga4_configured = $this->is_ga4_configured();
        
        // Incluir a view
        include_once plugin_dir_path(__FILE__) . 'views/mpa-analytics.php';
    }
    
    /**
     * Renderizar página de configurações
     */
    public function render_settings_page() {
        // Verificar permissões
        if (!current_user_can('manage_options')) {
            wp_die(__('Você não tem permissão para acessar esta página.'));
        }
        
        // Processar formulário se enviado
        if (isset($_POST['submit']) && wp_verify_nonce($_POST['mpa_ga4_nonce'], 'mpa_save_ga4_settings')) {
            $this->save_ga4_settings();
        }
        
        // Incluir a view
        include_once plugin_dir_path(__FILE__) . 'views/mpa-analytics-settings.php';
    }
    
    /**
     * Verificar se GA4 está configurado
     */
    private function is_ga4_configured() {
        $client_id = get_option('mpa_ga4_client_id', '');
        $client_secret = get_option('mpa_ga4_client_secret', '');
        $property_id = get_option('mpa_ga4_property_id', '');
        
        return !empty($client_id) && !empty($client_secret) && !empty($property_id);
    }
    
    /**
     * Salvar configurações do GA4
     */
    private function save_ga4_settings() {
        try {
            // Sanitizar e salvar dados
            $client_id = sanitize_text_field($_POST['ga4_client_id'] ?? '');
            $client_secret = sanitize_text_field($_POST['ga4_client_secret'] ?? '');
            $property_id = sanitize_text_field($_POST['ga4_property_id'] ?? '');
            $data_stream_id = sanitize_text_field($_POST['ga4_data_stream_id'] ?? '');
            
            // Validar campos obrigatórios
            if (empty($client_id) || empty($client_secret) || empty($property_id)) {
                throw new Exception('Todos os campos obrigatórios devem ser preenchidos.');
            }
            
            // Salvar no banco
            update_option('mpa_ga4_client_id', $client_id);
            update_option('mpa_ga4_client_secret', $client_secret);
            update_option('mpa_ga4_property_id', $property_id);
            update_option('mpa_ga4_data_stream_id', $data_stream_id);
            
            // Limpar tokens existentes para forçar nova autenticação
            delete_option('mpa_ga4_access_token');
            delete_option('mpa_ga4_refresh_token');
            delete_option('mpa_ga4_token_expires');
            
            $this->add_admin_notice('Configurações do GA4 salvas com sucesso!', 'success');
            
        } catch (Exception $e) {
            $this->add_admin_notice('Erro: ' . $e->getMessage(), 'error');
        }
    }
    
    /**
     * Testar conexão com GA4 via AJAX
     */
    public function test_ga4_connection() {
        // Verificar nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'mpa_analytics_nonce')) {
            wp_send_json_error('Nonce inválido');
        }
        
        // Verificar permissões
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Sem permissão');
        }
        
        try {
            // Tentar carregar a classe de integração GA4
            if (!class_exists('MPA_Analytics_Client')) {
                require_once plugin_dir_path(__FILE__) . '../includes/class-mpa-analytics-client.php';
            }
            
            $ga4_client = new MPA_Analytics_Client();
            $test_result = $ga4_client->test_connection();
            
            if ($test_result['success']) {
                wp_send_json_success(array(
                    'message' => 'Conexão com GA4 estabelecida com sucesso!',
                    'data' => $test_result['data']
                ));
            } else {
                wp_send_json_error($test_result['message']);
            }
            
        } catch (Exception $e) {
            wp_send_json_error('Erro ao testar conexão: ' . $e->getMessage());
        }
    }
    
    /**
     * Adicionar notice de admin
     */
    private function add_admin_notice($message, $type = 'success') {
        add_action('admin_notices', function() use ($message, $type) {
            $class = $type === 'error' ? 'notice-error' : 'notice-success';
            echo '<div class="notice ' . $class . ' is-dismissible">';
            echo '<p>' . esc_html($message) . '</p>';
            echo '</div>';
        });
    }
    
    /**
     * Obter configurações salvas do GA4
     */
    public static function get_ga4_settings() {
        return array(
            'client_id' => get_option('mpa_ga4_client_id', ''),
            'client_secret' => get_option('mpa_ga4_client_secret', ''),
            'property_id' => get_option('mpa_ga4_property_id', ''),
            'data_stream_id' => get_option('mpa_ga4_data_stream_id', ''),
            'access_token' => get_option('mpa_ga4_access_token', ''),
            'refresh_token' => get_option('mpa_ga4_refresh_token', ''),
            'token_expires' => get_option('mpa_ga4_token_expires', 0)
        );
    }
}

// Inicializar a classe
$mpa_analytics_instance = new MPA_Analytics_Page();

// Funções wrapper para compatibilidade com menu callbacks
function mpa_render_analytics_page() {
    global $mpa_analytics_instance;
    if ($mpa_analytics_instance) {
        $mpa_analytics_instance->render_analytics_page();
    }
}

function mpa_render_analytics_settings_page() {
    global $mpa_analytics_instance;
    if ($mpa_analytics_instance) {
        $mpa_analytics_instance->render_settings_page();
    }
}