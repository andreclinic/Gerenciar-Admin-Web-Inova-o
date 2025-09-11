<?php
/**
 * MPA Analytics Client Class
 * 
 * Responsável por autenticação OAuth, salvar tokens em options seguras,
 * disponibilizar endpoints REST e implementar filtros de intervalo de datas
 * 
 * @package Gerenciar_Admin
 * @since 1.0.0
 */

// Evitar acesso direto
if (!defined('ABSPATH')) {
    exit;
}

class MPA_Analytics_Client {
    
    /**
     * Google OAuth URLs
     */
    const OAUTH_ENDPOINT = 'https://accounts.google.com/o/oauth2/v2/auth';
    const TOKEN_ENDPOINT = 'https://oauth2.googleapis.com/token';
    const GA4_API_ENDPOINT = 'https://analyticsdata.googleapis.com/v1beta';
    
    /**
     * OAuth Scopes necessários
     */
    const REQUIRED_SCOPES = array(
        'https://www.googleapis.com/auth/analytics.readonly'
    );
    
    /**
     * Constructor - Registrar hooks necessários
     */
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_rest_routes'));
        add_action('wp_ajax_mpa_disconnect_ga4', array($this, 'disconnect_ga4'));
    }
    
    /**
     * Registrar rotas REST API
     */
    public function register_rest_routes() {
        // Namespace principal
        $namespace = 'mpa/v1/analytics';
        
        // Métricas principais
        register_rest_route($namespace, '/metrics', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_metrics'),
            'permission_callback' => array($this, 'check_permissions'),
            'args' => $this->get_date_range_args()
        ));
        
        // Dados de visitantes por dia
        register_rest_route($namespace, '/visitors', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_visitors_data'),
            'permission_callback' => array($this, 'check_permissions'),
            'args' => $this->get_date_range_args()
        ));
        
        // Dados de dispositivos
        register_rest_route($namespace, '/devices', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_device_data'),
            'permission_callback' => array($this, 'check_permissions'),
            'args' => $this->get_date_range_args()
        ));
        
        // Fontes de tráfego
        register_rest_route($namespace, '/traffic-sources', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_traffic_sources'),
            'permission_callback' => array($this, 'check_permissions'),
            'args' => $this->get_date_range_args()
        ));
        
        // Principais cidades
        register_rest_route($namespace, '/cities', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_top_cities'),
            'permission_callback' => array($this, 'check_permissions'),
            'args' => $this->get_date_range_args()
        ));
        
        // Principais páginas
        register_rest_route($namespace, '/pages', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_top_pages'),
            'permission_callback' => array($this, 'check_permissions'),
            'args' => $this->get_date_range_args()
        ));
        
        // Dados em tempo real
        register_rest_route($namespace, '/realtime', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_realtime_data'),
            'permission_callback' => array($this, 'check_permissions')
        ));
    }
    
    /**
     * Verificar permissões para endpoints
     */
    public function check_permissions() {
        return current_user_can('manage_options');
    }
    
    /**
     * Argumentos para filtro de data
     */
    private function get_date_range_args() {
        return array(
            'start_date' => array(
                'required' => false,
                'default' => date('Y-m-d', strtotime('-30 days')),
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => array($this, 'validate_date')
            ),
            'end_date' => array(
                'required' => false,
                'default' => date('Y-m-d'),
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => array($this, 'validate_date')
            )
        );
    }
    
    /**
     * Validar formato de data
     */
    public function validate_date($param, $request, $key) {
        return (bool) strtotime($param);
    }
    
    /**
     * Testar conexão com GA4
     */
    public function test_connection() {
        try {
            // Verificar se as credenciais estão configuradas
            $settings = MPA_Analytics_Page::get_ga4_settings();
            
            if (empty($settings['client_id']) || empty($settings['property_id'])) {
                throw new Exception('Credenciais do GA4 não configuradas');
            }
            
            // Tentar obter token de acesso
            $access_token = $this->get_access_token();
            
            if (!$access_token) {
                throw new Exception('Não foi possível obter token de acesso');
            }
            
            // Fazer uma requisição de teste simples
            $test_result = $this->make_ga4_request('reports:runReport', array(
                'property' => 'properties/' . $settings['property_id'],
                'dateRanges' => array(
                    array(
                        'startDate' => date('Y-m-d', strtotime('-7 days')),
                        'endDate' => date('Y-m-d')
                    )
                ),
                'metrics' => array(
                    array('name' => 'activeUsers')
                )
            ));
            
            if ($test_result === false) {
                throw new Exception('Falha na requisição para GA4 API');
            }
            
            $this->log_activity('Teste de conexão realizado com sucesso', 'success');
            
            return array(
                'success' => true,
                'message' => 'Conexão estabelecida com sucesso!',
                'data' => array(
                    'property_id' => $settings['property_id'],
                    'test_data' => $test_result
                )
            );
            
        } catch (Exception $e) {
            $this->log_activity('Erro no teste de conexão: ' . $e->getMessage(), 'error');
            
            return array(
                'success' => false,
                'message' => $e->getMessage()
            );
        }
    }
    
    /**
     * Obter métricas principais
     */
    public function get_metrics($request) {
        try {
            $start_date = $request->get_param('start_date');
            $end_date = $request->get_param('end_date');
            
            $current_period = $this->fetch_metrics($start_date, $end_date);
            $previous_period = $this->fetch_metrics(
                date('Y-m-d', strtotime($start_date . ' -' . $this->get_date_diff($start_date, $end_date) . ' days')),
                date('Y-m-d', strtotime($start_date . ' -1 day'))
            );
            
            return rest_ensure_response(array(
                'success' => true,
                'data' => array(
                    'current' => $current_period,
                    'previous' => $previous_period,
                    'changes' => $this->calculate_changes($current_period, $previous_period)
                )
            ));
            
        } catch (Exception $e) {
            return new WP_Error('ga4_error', $e->getMessage(), array('status' => 500));
        }
    }
    
    /**
     * Obter dados de visitantes por dia
     */
    public function get_visitors_data($request) {
        try {
            $start_date = $request->get_param('start_date');
            $end_date = $request->get_param('end_date');
            
            $data = $this->make_ga4_request('reports:runReport', array(
                'property' => 'properties/' . $this->get_property_id(),
                'dateRanges' => array(
                    array(
                        'startDate' => $start_date,
                        'endDate' => $end_date
                    )
                ),
                'dimensions' => array(
                    array('name' => 'date')
                ),
                'metrics' => array(
                    array('name' => 'activeUsers'),
                    array('name' => 'sessions')
                ),
                'orderBys' => array(
                    array(
                        'dimension' => array('dimensionName' => 'date'),
                        'desc' => false
                    )
                )
            ));
            
            $visitors_data = $this->format_visitors_data($data);
            
            return rest_ensure_response(array(
                'success' => true,
                'data' => $visitors_data
            ));
            
        } catch (Exception $e) {
            return new WP_Error('ga4_error', $e->getMessage(), array('status' => 500));
        }
    }
    
    /**
     * Obter dados de dispositivos
     */
    public function get_device_data($request) {
        try {
            $start_date = $request->get_param('start_date');
            $end_date = $request->get_param('end_date');
            
            $data = $this->make_ga4_request('reports:runReport', array(
                'property' => 'properties/' . $this->get_property_id(),
                'dateRanges' => array(
                    array(
                        'startDate' => $start_date,
                        'endDate' => $end_date
                    )
                ),
                'dimensions' => array(
                    array('name' => 'deviceCategory')
                ),
                'metrics' => array(
                    array('name' => 'activeUsers')
                )
            ));
            
            $device_data = $this->format_device_data($data);
            
            return rest_ensure_response(array(
                'success' => true,
                'data' => $device_data
            ));
            
        } catch (Exception $e) {
            return new WP_Error('ga4_error', $e->getMessage(), array('status' => 500));
        }
    }
    
    /**
     * Obter fontes de tráfego
     */
    public function get_traffic_sources($request) {
        try {
            $start_date = $request->get_param('start_date');
            $end_date = $request->get_param('end_date');
            
            $data = $this->make_ga4_request('reports:runReport', array(
                'property' => 'properties/' . $this->get_property_id(),
                'dateRanges' => array(
                    array(
                        'startDate' => $start_date,
                        'endDate' => $end_date
                    )
                ),
                'dimensions' => array(
                    array('name' => 'sessionDefaultChannelGrouping')
                ),
                'metrics' => array(
                    array('name' => 'sessions'),
                    array('name' => 'activeUsers')
                )
            ));
            
            $traffic_data = $this->format_traffic_sources($data);
            
            return rest_ensure_response(array(
                'success' => true,
                'data' => $traffic_data
            ));
            
        } catch (Exception $e) {
            return new WP_Error('ga4_error', $e->getMessage(), array('status' => 500));
        }
    }
    
    /**
     * Obter principais cidades
     */
    public function get_top_cities($request) {
        try {
            $start_date = $request->get_param('start_date');
            $end_date = $request->get_param('end_date');
            
            $data = $this->make_ga4_request('reports:runReport', array(
                'property' => 'properties/' . $this->get_property_id(),
                'dateRanges' => array(
                    array(
                        'startDate' => $start_date,
                        'endDate' => $end_date
                    )
                ),
                'dimensions' => array(
                    array('name' => 'city')
                ),
                'metrics' => array(
                    array('name' => 'activeUsers')
                ),
                'orderBys' => array(
                    array(
                        'metric' => array('metricName' => 'activeUsers'),
                        'desc' => true
                    )
                ),
                'limit' => 10
            ));
            
            $cities_data = $this->format_cities_data($data);
            
            return rest_ensure_response(array(
                'success' => true,
                'data' => $cities_data
            ));
            
        } catch (Exception $e) {
            return new WP_Error('ga4_error', $e->getMessage(), array('status' => 500));
        }
    }
    
    /**
     * Obter principais páginas
     */
    public function get_top_pages($request) {
        try {
            $start_date = $request->get_param('start_date');
            $end_date = $request->get_param('end_date');
            
            $data = $this->make_ga4_request('reports:runReport', array(
                'property' => 'properties/' . $this->get_property_id(),
                'dateRanges' => array(
                    array(
                        'startDate' => $start_date,
                        'endDate' => $end_date
                    )
                ),
                'dimensions' => array(
                    array('name' => 'pagePath'),
                    array('name' => 'pageTitle')
                ),
                'metrics' => array(
                    array('name' => 'screenPageViews')
                ),
                'orderBys' => array(
                    array(
                        'metric' => array('metricName' => 'screenPageViews'),
                        'desc' => true
                    )
                ),
                'limit' => 10
            ));
            
            $pages_data = $this->format_pages_data($data);
            
            return rest_ensure_response(array(
                'success' => true,
                'data' => $pages_data
            ));
            
        } catch (Exception $e) {
            return new WP_Error('ga4_error', $e->getMessage(), array('status' => 500));
        }
    }
    
    /**
     * Obter dados em tempo real
     */
    public function get_realtime_data($request) {
        try {
            $data = $this->make_ga4_request('reports:runRealtimeReport', array(
                'property' => 'properties/' . $this->get_property_id(),
                'metrics' => array(
                    array('name' => 'activeUsers'),
                    array('name' => 'screenPageViews'),
                    array('name' => 'eventCount')
                )
            ), true); // true para realtime
            
            $realtime_data = $this->format_realtime_data($data);
            
            return rest_ensure_response(array(
                'success' => true,
                'data' => $realtime_data
            ));
            
        } catch (Exception $e) {
            return new WP_Error('ga4_error', $e->getMessage(), array('status' => 500));
        }
    }
    
    /**
     * Fazer requisição para GA4 API
     */
    private function make_ga4_request($endpoint, $body, $realtime = false) {
        $access_token = $this->get_access_token();
        
        if (!$access_token) {
            throw new Exception('Token de acesso não disponível');
        }
        
        $base_url = $realtime ? 
            'https://analyticsdata.googleapis.com/v1beta' : 
            'https://analyticsdata.googleapis.com/v1beta';
            
        $url = $base_url . '/properties/' . $this->get_property_id() . ':' . $endpoint;
        
        $response = wp_remote_post($url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($body),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            throw new Exception('Erro na requisição: ' . $response->get_error_message());
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        if ($response_code !== 200) {
            $error_data = json_decode($response_body, true);
            $error_message = isset($error_data['error']['message']) ? 
                $error_data['error']['message'] : 
                'Erro HTTP ' . $response_code;
            throw new Exception($error_message);
        }
        
        return json_decode($response_body, true);
    }
    
    /**
     * Obter token de acesso (com refresh se necessário)
     */
    private function get_access_token() {
        $settings = MPA_Analytics_Page::get_ga4_settings();
        
        // Verificar se token ainda é válido
        if (!empty($settings['access_token']) && time() < $settings['token_expires']) {
            return $settings['access_token'];
        }
        
        // Tentar renovar token se temos refresh_token
        if (!empty($settings['refresh_token'])) {
            return $this->refresh_access_token();
        }
        
        // Se não temos tokens, precisamos iniciar OAuth flow
        return false;
    }
    
    /**
     * Renovar token de acesso
     */
    private function refresh_access_token() {
        $settings = MPA_Analytics_Page::get_ga4_settings();
        
        $response = wp_remote_post(self::TOKEN_ENDPOINT, array(
            'body' => array(
                'client_id' => $settings['client_id'],
                'client_secret' => $settings['client_secret'],
                'refresh_token' => $settings['refresh_token'],
                'grant_type' => 'refresh_token'
            )
        ));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($data['access_token'])) {
            // Salvar novo token
            update_option('mpa_ga4_access_token', $data['access_token']);
            update_option('mpa_ga4_token_expires', time() + $data['expires_in']);
            
            return $data['access_token'];
        }
        
        return false;
    }
    
    /**
     * Obter Property ID
     */
    private function get_property_id() {
        return get_option('mpa_ga4_property_id', '');
    }
    
    /**
     * Buscar métricas básicas
     */
    private function fetch_metrics($start_date, $end_date) {
        $data = $this->make_ga4_request('reports:runReport', array(
            'property' => 'properties/' . $this->get_property_id(),
            'dateRanges' => array(
                array(
                    'startDate' => $start_date,
                    'endDate' => $end_date
                )
            ),
            'metrics' => array(
                array('name' => 'activeUsers'),
                array('name' => 'screenPageViews'),
                array('name' => 'engagementRate'),
                array('name' => 'averageSessionDuration')
            )
        ));
        
        if (!isset($data['rows'][0]['metricValues'])) {
            return array(
                'users' => 0,
                'pageviews' => 0,
                'engagement_rate' => 0,
                'avg_session_duration' => 0
            );
        }
        
        $metrics = $data['rows'][0]['metricValues'];
        
        return array(
            'users' => intval($metrics[0]['value']),
            'pageviews' => intval($metrics[1]['value']),
            'engagement_rate' => floatval($metrics[2]['value']) * 100,
            'avg_session_duration' => intval($metrics[3]['value'])
        );
    }
    
    /**
     * Calcular diferenças percentuais
     */
    private function calculate_changes($current, $previous) {
        $changes = array();
        
        foreach ($current as $key => $value) {
            $prev_value = isset($previous[$key]) ? $previous[$key] : 0;
            
            if ($prev_value > 0) {
                $change = (($value - $prev_value) / $prev_value) * 100;
                $changes[$key] = round($change, 1);
            } else {
                $changes[$key] = $value > 0 ? 100 : 0;
            }
        }
        
        return $changes;
    }
    
    /**
     * Formatar dados de visitantes
     */
    private function format_visitors_data($data) {
        $formatted = array(
            'labels' => array(),
            'visitors' => array(),
            'sessions' => array()
        );
        
        if (isset($data['rows'])) {
            foreach ($data['rows'] as $row) {
                $date = $row['dimensionValues'][0]['value'];
                $formatted['labels'][] = date('d/m', strtotime($date));
                $formatted['visitors'][] = intval($row['metricValues'][0]['value']);
                $formatted['sessions'][] = intval($row['metricValues'][1]['value']);
            }
        }
        
        return $formatted;
    }
    
    /**
     * Formatar dados de dispositivos
     */
    private function format_device_data($data) {
        $formatted = array(
            'labels' => array(),
            'data' => array(),
            'total' => 0
        );
        
        if (isset($data['rows'])) {
            foreach ($data['rows'] as $row) {
                $device = $row['dimensionValues'][0]['value'];
                $users = intval($row['metricValues'][0]['value']);
                
                $formatted['labels'][] = $device;
                $formatted['data'][] = $users;
                $formatted['total'] += $users;
            }
        }
        
        return $formatted;
    }
    
    /**
     * Formatar fontes de tráfego
     */
    private function format_traffic_sources($data) {
        $formatted = array();
        $total = 0;
        
        if (isset($data['rows'])) {
            foreach ($data['rows'] as $row) {
                $source = $row['dimensionValues'][0]['value'];
                $sessions = intval($row['metricValues'][0]['value']);
                
                $formatted[] = array(
                    'source' => $source,
                    'sessions' => $sessions
                );
                $total += $sessions;
            }
            
            // Calcular percentuais
            foreach ($formatted as &$item) {
                $item['percentage'] = $total > 0 ? round(($item['sessions'] / $total) * 100, 1) : 0;
            }
        }
        
        return $formatted;
    }
    
    /**
     * Formatar dados de cidades
     */
    private function format_cities_data($data) {
        $formatted = array();
        
        if (isset($data['rows'])) {
            foreach ($data['rows'] as $row) {
                $city = $row['dimensionValues'][0]['value'];
                $users = intval($row['metricValues'][0]['value']);
                
                $formatted[] = array(
                    'city' => $city,
                    'users' => $users
                );
            }
        }
        
        return $formatted;
    }
    
    /**
     * Formatar dados de páginas
     */
    private function format_pages_data($data) {
        $formatted = array();
        
        if (isset($data['rows'])) {
            foreach ($data['rows'] as $row) {
                $path = $row['dimensionValues'][0]['value'];
                $title = isset($row['dimensionValues'][1]) ? $row['dimensionValues'][1]['value'] : $path;
                $pageviews = intval($row['metricValues'][0]['value']);
                
                $formatted[] = array(
                    'path' => $path,
                    'title' => $title,
                    'pageviews' => $pageviews
                );
            }
        }
        
        return $formatted;
    }
    
    /**
     * Formatar dados em tempo real
     */
    private function format_realtime_data($data) {
        $formatted = array(
            'active_users' => 0,
            'active_pages' => 0,
            'recent_events' => 0
        );
        
        if (isset($data['rows'][0]['metricValues'])) {
            $metrics = $data['rows'][0]['metricValues'];
            $formatted['active_users'] = intval($metrics[0]['value']);
            $formatted['active_pages'] = intval($metrics[1]['value']);
            $formatted['recent_events'] = intval($metrics[2]['value']);
        }
        
        return $formatted;
    }
    
    /**
     * Calcular diferença em dias entre duas datas
     */
    private function get_date_diff($start_date, $end_date) {
        $start = new DateTime($start_date);
        $end = new DateTime($end_date);
        return $end->diff($start)->days;
    }
    
    /**
     * Desconectar GA4 (remover tokens)
     */
    public function disconnect_ga4() {
        // Verificar nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'mpa_analytics_nonce')) {
            wp_send_json_error('Nonce inválido');
        }
        
        // Verificar permissões
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Sem permissão');
        }
        
        // Remover tokens
        delete_option('mpa_ga4_access_token');
        delete_option('mpa_ga4_refresh_token');
        delete_option('mpa_ga4_token_expires');
        
        $this->log_activity('GA4 desconectado manualmente', 'info');
        
        wp_send_json_success('GA4 desconectado com sucesso');
    }
    
    /**
     * Log de atividades
     */
    private function log_activity($message, $type = 'info') {
        $log = get_option('mpa_ga4_activity_log', array());
        
        $log[] = array(
            'timestamp' => current_time('d/m/Y H:i:s'),
            'message' => $message,
            'type' => $type
        );
        
        // Manter apenas os últimos 50 registros
        if (count($log) > 50) {
            $log = array_slice($log, -50);
        }
        
        update_option('mpa_ga4_activity_log', $log);
    }
}