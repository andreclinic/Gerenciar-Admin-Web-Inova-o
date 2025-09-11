<?php
/**
 * Analytics Dashboard View
 * 
 * Renderiza o layout das métricas baseado em modelo_dashboard.html
 * e na imagem de referência fornecida pelo usuário
 * 
 * @package Gerenciar_Admin
 * @since 1.0.0
 */

// Evitar acesso direto
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="mpa-dashboard-analytics">
    <!-- Header com filtros de data -->
    <div class="mpa-analytics-header">
        <div class="mpa-analytics-header-left">
            <h1 class="mpa-analytics-title">📊 Analytics Dashboard</h1>
            <p class="mpa-analytics-subtitle">Métricas em tempo real do seu site WordPress</p>
        </div>
        <div class="mpa-analytics-header-right">
            <div class="mpa-date-filter-container">
                <label for="dateRange">📅 Período:</label>
                <select id="dateRange" class="mpa-date-filter">
                    <option value="7d">Últimos 7 dias</option>
                    <option value="30d" selected>Últimos 30 dias</option>
                    <option value="90d">Últimos 90 dias</option>
                    <option value="custom">Personalizado</option>
                </select>
            </div>
            <div id="customDateRange" class="mpa-custom-date-range" style="display: none;">
                <input type="date" id="startDate" class="mpa-date-input">
                <span>até</span>
                <input type="date" id="endDate" class="mpa-date-input">
                <button id="applyDateRange" class="mpa-btn mpa-btn-primary">Aplicar</button>
            </div>
        </div>
    </div>

    <?php if (!$ga4_configured): ?>
        <!-- Aviso de configuração necessária -->
        <div class="mpa-config-notice">
            <div class="mpa-notice-icon">⚠️</div>
            <div class="mpa-notice-content">
                <h3>Google Analytics não configurado</h3>
                <p>Para visualizar as métricas, você precisa configurar a integração com o Google Analytics GA4.</p>
                <a href="<?php echo admin_url('admin.php?page=' . MPA_Analytics_Page::SETTINGS_SLUG); ?>" class="mpa-btn mpa-btn-primary">
                    🔧 Configurar GA4
                </a>
            </div>
        </div>
    <?php else: ?>
        <!-- Loading indicator -->
        <div id="mpaAnalyticsLoading" class="mpa-loading-container">
            <div class="mpa-loading-spinner"></div>
            <p>Carregando métricas do Google Analytics...</p>
        </div>

        <!-- Visitor Analytics Section -->
        <section class="mpa-analytics-section">
            <h2 class="mpa-section-title">📈 Visitor Analytics</h2>
            <div class="mpa-card-grid mpa-card-grid-4">
                <!-- Usuários do Site -->
                <div class="mpa-card">
                    <div class="mpa-metric-header">
                        <h3 class="mpa-metric-title">Usuários do Site</h3>
                        <span class="mpa-metric-change mpa-metric-change-positive" id="usersChange">+0%</span>
                    </div>
                    <div class="mpa-metric-value" id="usersCount">0</div>
                    <p class="mpa-metric-subtitle">vs período anterior</p>
                </div>

                <!-- Visualizações de Página -->
                <div class="mpa-card">
                    <div class="mpa-metric-header">
                        <h3 class="mpa-metric-title">Visualizações</h3>
                        <span class="mpa-metric-change mpa-metric-change-negative" id="pageViewsChange">+0%</span>
                    </div>
                    <div class="mpa-metric-value" id="pageViewsCount">0</div>
                    <p class="mpa-metric-subtitle">vs período anterior</p>
                </div>

                <!-- Taxa de Engajamento -->
                <div class="mpa-card">
                    <div class="mpa-metric-header">
                        <h3 class="mpa-metric-title">Taxa de Engajamento</h3>
                        <span class="mpa-metric-change mpa-metric-change-positive" id="engagementChange">+0%</span>
                    </div>
                    <div class="mpa-metric-value" id="engagementRate">0%</div>
                    <p class="mpa-metric-subtitle">vs período anterior</p>
                </div>

                <!-- Duração Média da Sessão -->
                <div class="mpa-card">
                    <div class="mpa-metric-header">
                        <h3 class="mpa-metric-title">Duração Média</h3>
                        <span class="mpa-metric-change mpa-metric-change-positive" id="sessionDurationChange">+0%</span>
                    </div>
                    <div class="mpa-metric-value" id="sessionDuration">0:00</div>
                    <p class="mpa-metric-subtitle">minutos por sessão</p>
                </div>
            </div>

            <div class="mpa-card-grid mpa-card-grid-3">
                <!-- Gráfico de Visitantes por Dia -->
                <div class="mpa-card">
                    <h3 class="mpa-section-title">Visitantes por Dia</h3>
                    <div class="mpa-chart-container">
                        <canvas id="visitorsChart"></canvas>
                    </div>
                </div>

                <!-- Visitas por Dispositivo -->
                <div class="mpa-card">
                    <h3 class="mpa-section-title">Visitas por Dispositivo</h3>
                    <div class="mpa-donut-chart">
                        <canvas id="deviceChart"></canvas>
                    </div>
                    <div class="mpa-device-stats" id="deviceStats">
                        <!-- Stats serão inseridos via JS -->
                    </div>
                </div>
            </div>
        </section>

        <!-- User Acquisition & Engagement -->
        <div class="mpa-card-grid mpa-card-grid-2">
            <!-- User Acquisition -->
            <div class="mpa-card">
                <h3 class="mpa-section-title">🎯 User Acquisition</h3>
                <div id="acquisitionSources">
                    <!-- Fontes de tráfego serão inseridas via JS -->
                </div>

                <!-- Principais Cidades -->
                <div class="mpa-city-stats">
                    <h4>🌍 Principais Cidades</h4>
                    <div id="topCities">
                        <!-- Cidades serão inseridas via JS -->
                    </div>
                </div>
            </div>

            <!-- Engagement & Content -->
            <div class="mpa-card">
                <h3 class="mpa-section-title">🔥 Site Overview</h3>
                
                <!-- WordPress Content -->
                <div class="mpa-wp-content">
                    <h4>📝 Conteúdo Recente</h4>
                    <div class="mpa-recent-posts">
                        <?php
                        // Buscar posts recentes
                        $recent_posts = get_posts(array(
                            'numberposts' => 5,
                            'post_status' => 'publish',
                            'orderby' => 'date',
                            'order' => 'DESC'
                        ));

                        if ($recent_posts): ?>
                            <?php foreach ($recent_posts as $post): ?>
                                <div class="mpa-recent-item">
                                    <span class="mpa-recent-title"><?php echo esc_html($post->post_title); ?></span>
                                    <span class="mpa-recent-date"><?php echo get_the_date('d/m/Y', $post); ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="mpa-no-data">Nenhum post encontrado</p>
                        <?php endif; ?>
                    </div>

                    <h4>💬 Comentários Recentes</h4>
                    <div class="mpa-recent-comments">
                        <?php
                        // Buscar comentários recentes
                        $recent_comments = get_comments(array(
                            'number' => 5,
                            'status' => 'approve',
                            'orderby' => 'comment_date',
                            'order' => 'DESC'
                        ));

                        if ($recent_comments): ?>
                            <?php foreach ($recent_comments as $comment): ?>
                                <div class="mpa-recent-item">
                                    <span class="mpa-recent-title"><?php echo esc_html($comment->comment_author); ?></span>
                                    <span class="mpa-recent-excerpt"><?php echo esc_html(wp_trim_words($comment->comment_content, 8)); ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="mpa-no-data">Nenhum comentário encontrado</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Páginas Mais Visitadas -->
                <div class="mpa-top-pages">
                    <h4>📄 Páginas Mais Visitadas</h4>
                    <div id="topPages">
                        <!-- Páginas serão inseridas via JS -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Real Time Section -->
        <section class="mpa-analytics-section">
            <h2 class="mpa-section-title">⚡ Real Time Analytics</h2>
            <div class="mpa-card-grid mpa-card-grid-real-time">
                <div class="mpa-card">
                    <h3 class="mpa-metric-title">Usuários Ativos</h3>
                    <div class="mpa-metric-value mpa-realtime-value" id="activeUsers">0</div>
                    <p class="mpa-metric-subtitle">agora mesmo</p>
                </div>

                <div class="mpa-card">
                    <h3 class="mpa-metric-title">Páginas Ativas</h3>
                    <div class="mpa-metric-value mpa-realtime-value" id="activePages">0</div>
                    <p class="mpa-metric-subtitle">sendo visualizadas</p>
                </div>

                <div class="mpa-card">
                    <h3 class="mpa-metric-title">Eventos (30min)</h3>
                    <div class="mpa-metric-value mpa-realtime-value" id="recentEvents">0</div>
                    <p class="mpa-metric-subtitle">últimos 30 minutos</p>
                </div>

                <div class="mpa-card">
                    <h3 class="mpa-metric-title">Taxa de Conversão</h3>
                    <div class="mpa-metric-value mpa-realtime-value" id="conversionRate">0%</div>
                    <p class="mpa-metric-subtitle">hoje</p>
                </div>
            </div>
        </section>

        <!-- Botão de atualização manual -->
        <div class="mpa-analytics-actions">
            <button id="refreshAnalytics" class="mpa-btn mpa-btn-primary">
                🔄 Atualizar Dados
            </button>
            <button id="exportData" class="mpa-btn mpa-btn-secondary">
                📊 Exportar Relatório
            </button>
            <a href="<?php echo admin_url('admin.php?page=' . MPA_Analytics_Page::SETTINGS_SLUG); ?>" class="mpa-btn mpa-btn-secondary">
                ⚙️ Configurações
            </a>
        </div>
    <?php endif; ?>
</div>

<!-- Modal para períodos personalizados -->
<div id="customDateModal" class="mpa-modal" style="display: none;">
    <div class="mpa-modal-content">
        <div class="mpa-modal-header">
            <h3>Selecionar Período Personalizado</h3>
            <button class="mpa-modal-close">&times;</button>
        </div>
        <div class="mpa-modal-body">
            <div class="mpa-date-inputs">
                <div class="mpa-date-input-group">
                    <label for="modalStartDate">Data de Início:</label>
                    <input type="date" id="modalStartDate" class="mpa-date-input">
                </div>
                <div class="mpa-date-input-group">
                    <label for="modalEndDate">Data de Fim:</label>
                    <input type="date" id="modalEndDate" class="mpa-date-input">
                </div>
            </div>
        </div>
        <div class="mpa-modal-footer">
            <button id="applyCustomDate" class="mpa-btn mpa-btn-primary">Aplicar</button>
            <button class="mpa-modal-close mpa-btn mpa-btn-secondary">Cancelar</button>
        </div>
    </div>
</div>