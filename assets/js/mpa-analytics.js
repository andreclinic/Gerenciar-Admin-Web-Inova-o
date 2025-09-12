/**
 * MPA Analytics Dashboard JavaScript
 * 
 * Responsável por carregar dados via REST API, renderizar gráficos com Chart.js
 * e gerenciar interações do dashboard
 * 
 * @package Gerenciar_Admin
 * @since 1.0.0
 */

(function($) {
    'use strict';

    // Classe principal do Analytics Dashboard
    class MPAAnalyticsDashboard {
        constructor() {
            this.charts = {};
            this.currentDateRange = {
                start_date: this.getDateString(-30),
                end_date: this.getDateString(0)
            };
            this.init();
        }

        init() {
            console.log('🔍 [MPA DEBUG] Inicializando MPAAnalyticsDashboard');
            
            // Verificar se objeto mpaAnalytics existe
            if (typeof mpaAnalytics === 'undefined') {
                console.error('❌ [MPA DEBUG] Objeto mpaAnalytics não existe!');
                this.showNotification('Erro: Configurações JavaScript não carregadas', 'error');
                return;
            }
            
            console.log('🔍 [MPA DEBUG] Objeto mpaAnalytics encontrado:', mpaAnalytics);
            console.log('🔍 [MPA DEBUG] Chart.js disponível:', typeof Chart !== 'undefined' ? 'SIM' : 'NÃO');
            
            // Se Chart.js não está disponível, aguardar um pouco
            if (typeof Chart === 'undefined') {
                console.log('⏳ [MPA DEBUG] Aguardando Chart.js carregar...');
                setTimeout(() => {
                    console.log('🔍 [MPA DEBUG] Verificação Chart.js após delay:', typeof Chart !== 'undefined' ? 'SIM' : 'NÃO');
                    this.continueInit();
                }, 2000);
                return;
            }
            
            this.continueInit();
        }
        
        continueInit() {
            this.bindEvents();
            this.setupDateFilters();
            this.loadAllData();
            this.setupAutoRefresh();
        }

        // ===================================
        // EVENT BINDING
        // ===================================
        bindEvents() {
            // Filtro de período
            $('#dateRange').on('change', this.handleDateRangeChange.bind(this));
            $('#applyDateRange, #applyCustomDate').on('click', this.applyCustomDateRange.bind(this));
            
            // Botões de ação
            $('#refreshAnalytics').on('click', this.refreshData.bind(this));
            $('#exportData').on('click', this.exportData.bind(this));
            
            // Modal de data personalizada
            this.setupCustomDateModal();
            
            // Atualização automática para dados em tempo real
            setInterval(() => {
                this.loadRealtimeData();
            }, 30000); // A cada 30 segundos
        }

        setupDateFilters() {
            const dateRangeSelect = $('#dateRange');
            const today = new Date();
            
            // Configurar valores padrão das datas
            $('#startDate, #modalStartDate').val(this.getDateString(-30));
            $('#endDate, #modalEndDate').val(this.getDateString(0));
            
            // Mostrar/esconder campos de data personalizada
            dateRangeSelect.on('change', function() {
                const customRange = $('#customDateRange');
                if ($(this).val() === 'custom') {
                    customRange.show();
                } else {
                    customRange.hide();
                }
            });
        }

        setupCustomDateModal() {
            // Abrir modal para datas personalizadas (se implementado)
            $('.mpa-modal-close').on('click', function() {
                $(this).closest('.mpa-modal').hide();
            });
            
            // Fechar modal clicando fora
            $('.mpa-modal').on('click', function(e) {
                if (e.target === this) {
                    $(this).hide();
                }
            });
        }

        // ===================================
        // DATE HANDLING
        // ===================================
        handleDateRangeChange() {
            const selectedRange = $('#dateRange').val();
            let startDate, endDate;

            switch (selectedRange) {
                case '7d':
                    startDate = this.getDateString(-7);
                    endDate = this.getDateString(0);
                    break;
                case '30d':
                    startDate = this.getDateString(-30);
                    endDate = this.getDateString(0);
                    break;
                case '90d':
                    startDate = this.getDateString(-90);
                    endDate = this.getDateString(0);
                    break;
                case 'custom':
                    return; // Será tratado pelo applyCustomDateRange
                default:
                    return;
            }

            this.updateDateRange(startDate, endDate);
        }

        applyCustomDateRange() {
            const startDate = $('#startDate').val() || $('#modalStartDate').val();
            const endDate = $('#endDate').val() || $('#modalEndDate').val();

            if (!startDate || !endDate) {
                this.showNotification('Por favor, selecione as datas de início e fim.', 'error');
                return;
            }

            if (new Date(startDate) > new Date(endDate)) {
                this.showNotification('A data de início deve ser anterior à data de fim.', 'error');
                return;
            }

            this.updateDateRange(startDate, endDate);
            $('#customDateModal').hide();
        }

        updateDateRange(startDate, endDate) {
            this.currentDateRange = {
                start_date: startDate,
                end_date: endDate
            };

            this.showLoading();
            this.loadAllData();
        }

        getDateString(daysOffset) {
            const date = new Date();
            date.setDate(date.getDate() + daysOffset);
            return date.toISOString().split('T')[0];
        }

        // ===================================
        // DATA LOADING
        // ===================================
        loadAllData() {
            console.log('🔍 [MPA DEBUG] loadAllData() iniciado');
            console.log('🔍 [MPA DEBUG] currentDateRange:', this.currentDateRange);
            console.log('🔍 [MPA DEBUG] mpaAnalytics object:', mpaAnalytics);
            
            this.showLoading();
            
            Promise.all([
                this.loadMetrics(),
                this.loadVisitorsData(),
                this.loadDeviceData(),
                this.loadTrafficSources(),
                this.loadTopCities(),
                this.loadTopPages(),
                this.loadEventsData(),
                this.loadRealtimeData()
            ]).then(() => {
                console.log('✅ [MPA DEBUG] Todos os dados carregados com sucesso');
                this.hideLoading();
                this.showNotification('Dados atualizados com sucesso!', 'success');
            }).catch((error) => {
                console.error('❌ [MPA DEBUG] Erro ao carregar dados:', error);
                this.hideLoading();
                this.showNotification('Erro ao carregar dados: ' + error.message, 'error');
                console.error('Erro ao carregar dados do analytics:', error);
            });
        }

        async loadMetrics() {
            try {
                const response = await this.makeRequest('metrics', this.currentDateRange);
                this.updateMetricsDisplay(response.data);
            } catch (error) {
                console.error('Erro ao carregar métricas:', error);
                throw error;
            }
        }

        async loadVisitorsData() {
            try {
                const response = await this.makeRequest('visitors', this.currentDateRange);
                this.updateVisitorsChart(response.data);
            } catch (error) {
                console.error('Erro ao carregar dados de visitantes:', error);
                throw error;
            }
        }

        async loadDeviceData() {
            try {
                const response = await this.makeRequest('devices', this.currentDateRange);
                this.updateDeviceChart(response.data);
                this.updateDeviceStats(response.data);
            } catch (error) {
                console.error('Erro ao carregar dados de dispositivos:', error);
                throw error;
            }
        }

        async loadTrafficSources() {
            try {
                const response = await this.makeRequest('traffic-sources', this.currentDateRange);
                this.updateTrafficSources(response.data);
            } catch (error) {
                console.error('Erro ao carregar fontes de tráfego:', error);
                throw error;
            }
        }

        async loadTopCities() {
            try {
                const response = await this.makeRequest('cities', this.currentDateRange);
                this.updateTopCities(response.data);
            } catch (error) {
                console.error('Erro ao carregar principais cidades:', error);
                throw error;
            }
        }

        async loadTopPages() {
            try {
                const response = await this.makeRequest('pages', this.currentDateRange);
                this.updateTopPages(response.data);
            } catch (error) {
                console.error('Erro ao carregar páginas principais:', error);
                throw error;
            }
        }

        async loadEventsData() {
            try {
                const response = await this.makeRequest('events', this.currentDateRange);
                this.updateEventsChart(response.data);
                this.updateTopEvents(response.data);
            } catch (error) {
                console.error('Erro ao carregar dados de eventos:', error);
                throw error;
            }
        }

        async loadRealtimeData() {
            try {
                const response = await this.makeRequest('realtime');
                this.updateRealtimeDisplay(response.data);
            } catch (error) {
                console.error('Erro ao carregar dados em tempo real:', error);
                // Não lançar erro para dados em tempo real para não interromper outras operações
            }
        }

        // ===================================
        // API REQUESTS
        // ===================================
        async makeRequest(endpoint, params = {}) {
            const url = new URL(mpaAnalytics.restUrl + endpoint);
            
            // Adicionar parâmetros de data se fornecidos
            if (params.start_date) url.searchParams.append('start_date', params.start_date);
            if (params.end_date) url.searchParams.append('end_date', params.end_date);

            console.log(`🌐 [MPA DEBUG] Fazendo requisição para: ${url.toString()}`);
            console.log('🌐 [MPA DEBUG] Headers enviados:', {
                'X-WP-Nonce': mpaAnalytics.nonce,
                'Content-Type': 'application/json'
            });

            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'X-WP-Nonce': mpaAnalytics.nonce,
                    'Content-Type': 'application/json'
                }
            });

            console.log(`🌐 [MPA DEBUG] Response status: ${response.status} ${response.statusText}`);

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                console.error(`❌ [MPA DEBUG] Erro na requisição:`, errorData);
                throw new Error(errorData.message || `HTTP ${response.status}: ${response.statusText}`);
            }

            const result = await response.json();
            console.log(`✅ [MPA DEBUG] Response recebida para ${endpoint}:`, result);
            return result;
        }

        // ===================================
        // UI UPDATES
        // ===================================
        updateMetricsDisplay(data) {
            if (!data.current) return;

            const current = data.current;
            const changes = data.changes || {};

            // Atualizar valores principais
            $('#usersCount').text(this.formatNumber(current.users));
            $('#pageViewsCount').text(this.formatNumber(current.pageviews));
            $('#engagementRate').text(current.engagement_rate.toFixed(1) + '%');
            $('#sessionDuration').text(this.formatDuration(current.avg_session_duration));

            // Atualizar indicadores de mudança
            this.updateChangeIndicator('#usersChange', changes.users);
            this.updateChangeIndicator('#pageViewsChange', changes.pageviews);
            this.updateChangeIndicator('#engagementChange', changes.engagement_rate);
            this.updateChangeIndicator('#sessionDurationChange', changes.avg_session_duration);
        }

        updateChangeIndicator(selector, change) {
            const element = $(selector);
            const isPositive = change >= 0;
            const formattedChange = (change >= 0 ? '+' : '') + change.toFixed(1) + '%';

            element.text(formattedChange);
            element.removeClass('mpa-metric-change-positive mpa-metric-change-negative');
            element.addClass(isPositive ? 'mpa-metric-change-positive' : 'mpa-metric-change-negative');
        }

        updateVisitorsChart(data) {
            const ctx = document.getElementById('visitorsChart');
            if (!ctx) return;

            // Verificar se Chart.js está disponível
            if (typeof Chart === 'undefined') {
                console.log('⚠️ [MPA DEBUG] Chart.js não disponível, exibindo dados sem gráfico');
                ctx.outerHTML = '<div style="padding: 20px; text-align: center; color: #666;">📊 Dados carregados com sucesso<br>Gráfico indisponível (Chart.js não carregado)</div>';
                return;
            }

            // Destruir gráfico existente se houver
            if (this.charts.visitors) {
                this.charts.visitors.destroy();
            }

            this.charts.visitors = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.labels || [],
                    datasets: [{
                        label: 'Visitantes',
                        data: data.visitors || [],
                        backgroundColor: '#2563eb',
                        borderRadius: 4,
                        borderSkipped: false,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false,
                        },
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: this.getGridColor(),
                            },
                            ticks: {
                                color: this.getTextColor(),
                            },
                        },
                        x: {
                            grid: {
                                display: false,
                            },
                            ticks: {
                                color: this.getTextColor(),
                            },
                        },
                    },
                }
            });
        }

        updateDeviceChart(data) {
            const ctx = document.getElementById('deviceChart');
            if (!ctx) return;

            // Verificar se Chart.js está disponível
            if (typeof Chart === 'undefined') {
                console.log('⚠️ [MPA DEBUG] Chart.js não disponível para device chart');
                ctx.outerHTML = '<div style="padding: 20px; text-align: center; color: #666;">📱 Dados de dispositivos carregados<br>Gráfico indisponível</div>';
                return;
            }

            // Destruir gráfico existente se houver
            if (this.charts.device) {
                this.charts.device.destroy();
            }

            const colors = ['#2563eb', '#9333ea', '#10b981', '#f59e0b'];
            
            // Traduções dos dispositivos
            const deviceTranslations = {
                'mobile': 'Celular',
                'desktop': 'Desktop',
                'tablet': 'Tablet'
            };
            
            // Traduzir labels
            const translatedLabels = (data.labels || []).map(label => 
                deviceTranslations[label.toLowerCase()] || label
            );

            this.charts.device = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: translatedLabels,
                    datasets: [{
                        data: data.data || [],
                        backgroundColor: colors.slice(0, data.labels?.length || 0),
                        borderWidth: 0,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false,
                        },
                    },
                    cutout: '70%',
                }
            });
        }

        updateDeviceStats(data) {
            const container = $('#deviceStats');
            if (!container.length) return;

            let html = '';
            const total = data.total || 1;
            
            // Traduções dos dispositivos
            const deviceTranslations = {
                'mobile': 'Celular',
                'desktop': 'Desktop',
                'tablet': 'Tablet'
            };

            (data.labels || []).forEach((label, index) => {
                const value = data.data[index] || 0;
                const percentage = ((value / total) * 100).toFixed(1);
                const dotClass = label.toLowerCase();
                const translatedLabel = deviceTranslations[label.toLowerCase()] || label;

                html += `
                    <div class="mpa-device-stat">
                        <div class="mpa-device-stat-left">
                            <div class="mpa-device-dot ${dotClass}"></div>
                            <span class="mpa-device-label">${translatedLabel}</span>
                        </div>
                        <span class="mpa-device-percentage">${percentage}%</span>
                    </div>
                `;
            });

            container.html(html);
        }

        updateTrafficSources(data) {
            const container = $('#acquisitionSources');
            if (!container.length) return;

            let html = '';
            
            // Traduções das fontes de tráfego
            const trafficTranslations = {
                'Paid Search': 'Busca Paga',
                'Referral': 'Referência',
                'Direct': 'Direto',
                'Organic Search': 'Busca Orgânica',
                'Organic Social': 'Social Orgânico',
                'Cross-network': 'Cross-network',
                'Unassigned': 'Não Atribuído',
                'Social': 'Social',
                'Email': 'E-mail',
                'Display': 'Display'
            };

            (data || []).forEach(source => {
                const barWidth = Math.max(source.percentage, 5); // Mínimo 5% para visibilidade
                const fillClass = this.getTrafficSourceClass(source.source);
                const translatedSource = trafficTranslations[source.source] || source.source;

                html += `
                    <div class="mpa-progress-item">
                        <span class="mpa-progress-label">${translatedSource}</span>
                        <div class="mpa-progress-right">
                            <div class="mpa-progress-bar">
                                <div class="mpa-progress-fill ${fillClass}" style="width: ${barWidth}%"></div>
                            </div>
                            <span class="mpa-progress-percentage">${source.percentage}%</span>
                        </div>
                    </div>
                `;
            });

            container.html(html);
        }

        updateTopCities(data) {
            const container = $('#topCities');
            if (!container.length) return;

            let html = '';

            (data || []).forEach(city => {
                html += `
                    <div class="mpa-city-item">
                        <span class="mpa-city-name">${city.city}</span>
                        <span class="mpa-city-value">${this.formatNumber(city.users)}</span>
                    </div>
                `;
            });

            container.html(html || '<p class="mpa-no-data">Nenhum dado disponível</p>');
        }

        updateTopPages(data) {
            const container = $('#topPages');
            if (!container.length) return;

            let html = '';

            (data || []).forEach(page => {
                html += `
                    <div class="mpa-page-item">
                        <div class="mpa-page-info">
                            <div class="mpa-page-title">${page.title}</div>
                            <div class="mpa-page-path">${page.path}</div>
                        </div>
                        <div class="mpa-page-views">${this.formatNumber(page.pageviews)}</div>
                    </div>
                `;
            });

            container.html(html || '<p class="mpa-no-data">Nenhum dado disponível</p>');
        }

        updateEventsChart(data) {
            const canvas = document.getElementById('eventsChart');
            if (!canvas) return;

            // Destruir gráfico existente se houver
            if (this.eventsChart) {
                this.eventsChart.destroy();
            }

            // Preparar dados para o gráfico
            const events = data?.events || [];
            const labels = events.map(event => event.event_name || 'Evento');
            const values = events.map(event => event.event_count || 0);
            const colors = [
                '#FF6B6B', '#4ECDC4', '#45B7D1', '#96CEB4', '#FFEAA7',
                '#DDA0DD', '#98D8C8', '#F7DC6F', '#BB8FCE', '#85C1E9'
            ];

            try {
                this.eventsChart = new Chart(canvas, {
                    type: 'doughnut',
                    data: {
                        labels: labels,
                        datasets: [{
                            data: values,
                            backgroundColor: colors.slice(0, values.length),
                            borderWidth: 2,
                            borderColor: '#ffffff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    boxWidth: 12,
                                    padding: 15,
                                    font: {
                                        size: 11
                                    }
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const label = context.label || '';
                                        const value = context.parsed || 0;
                                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                        const percentage = ((value / total) * 100).toFixed(1);
                                        return `${label}: ${value} (${percentage}%)`;
                                    }
                                }
                            }
                        },
                        layout: {
                            padding: 10
                        }
                    }
                });
            } catch (error) {
                console.error('Erro ao criar gráfico de eventos:', error);
                canvas.parentElement.innerHTML = '<p style="text-align: center; color: #666;">Erro ao carregar gráfico</p>';
            }
        }

        updateTopEvents(data) {
            const container = $('#topEvents');
            if (!container.length) return;

            let html = '';
            const events = data?.events || [];

            events.forEach((event, index) => {
                const eventName = event.event_name || 'Evento';
                const eventCount = event.event_count || 0;
                
                // Traduzir nomes de eventos comuns do GA4
                const eventTranslations = {
                    'page_view': 'Visualização de Página',
                    'click': 'Clique',
                    'scroll': 'Rolagem',
                    'file_download': 'Download de Arquivo',
                    'form_submit': 'Envio de Formulário',
                    'video_start': 'Início de Vídeo',
                    'video_complete': 'Vídeo Completo',
                    'search': 'Pesquisa',
                    'login': 'Login',
                    'sign_up': 'Cadastro'
                };
                
                const translatedName = eventTranslations[eventName] || eventName;
                
                html += `
                    <div class="mpa-event-item">
                        <div class="mpa-event-rank">${index + 1}</div>
                        <div class="mpa-event-info">
                            <div class="mpa-event-name">${translatedName}</div>
                        </div>
                        <div class="mpa-event-count">${this.formatNumber(eventCount)}</div>
                    </div>
                `;
            });

            container.html(html || '<p class="mpa-no-data">Nenhum evento disponível</p>');
        }

        updateRealtimeDisplay(data) {
            if (!data) return;

            $('#activeUsers').text(this.formatNumber(data.active_users || 0));
            $('#activePages').text(this.formatNumber(data.active_pages || 0));
            $('#recentEvents').text(this.formatNumber(data.recent_events || 0));
            
            // Calcular taxa de conversão fictícia (seria baseada em eventos reais)
            const conversionRate = data.active_users > 0 ? 
                ((data.recent_events / data.active_users) * 100).toFixed(1) : 
                '0.0';
            $('#conversionRate').text(conversionRate + '%');
        }

        // ===================================
        // UTILITY FUNCTIONS
        // ===================================
        formatNumber(num) {
            if (num >= 1000000) {
                return (num / 1000000).toFixed(1) + 'M';
            }
            if (num >= 1000) {
                return (num / 1000).toFixed(1) + 'K';
            }
            return num.toString();
        }

        formatDuration(seconds) {
            const minutes = Math.floor(seconds / 60);
            const remainingSeconds = seconds % 60;
            return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;
        }

        getTrafficSourceClass(source) {
            const sourceMap = {
                'Organic Search': 'organic',
                'Direct': 'direct',
                'Social': 'social',
                'Referral': 'referral',
                'Email': 'organic',
                'Paid Search': 'direct',
                'Display': 'social'
            };
            return sourceMap[source] || 'organic';
        }

        getGridColor() {
            return document.body.classList.contains('dark-mode') ? '#374151' : '#f3f4f6';
        }

        getTextColor() {
            return document.body.classList.contains('dark-mode') ? '#9ca3af' : '#6b7280';
        }

        // ===================================
        // UI STATES
        // ===================================
        showLoading() {
            $('#mpaAnalyticsLoading').show();
            $('.mpa-analytics-section, .mpa-card-grid').css('opacity', '0.6');
        }

        hideLoading() {
            $('#mpaAnalyticsLoading').hide();
            $('.mpa-analytics-section, .mpa-card-grid').css('opacity', '1');
        }

        showNotification(message, type = 'info') {
            // Criar elemento de notificação
            const notificationClass = type === 'error' ? 'notice-error' : 'notice-success';
            const notification = $(`
                <div class="notice ${notificationClass} is-dismissible" style="margin: 1rem 0;">
                    <p>${message}</p>
                    <button type="button" class="notice-dismiss">
                        <span class="screen-reader-text">Dismiss this notice.</span>
                    </button>
                </div>
            `);

            // Adicionar ao topo da página
            $('.mpa-dashboard-analytics').prepend(notification);

            // Auto-remover após 5 segundos
            setTimeout(() => {
                notification.fadeOut(() => notification.remove());
            }, 5000);

            // Remover ao clicar no X
            notification.find('.notice-dismiss').on('click', () => {
                notification.fadeOut(() => notification.remove());
            });
        }

        // ===================================
        // ACTION HANDLERS
        // ===================================
        refreshData() {
            this.showNotification('Atualizando dados...', 'info');
            this.loadAllData();
        }

        async exportData() {
            try {
                const exportBtn = $('#exportData');
                exportBtn.prop('disabled', true).text('📊 Exportando...');

                // Simular export (implementar conforme necessário)
                await new Promise(resolve => setTimeout(resolve, 2000));

                // Criar um relatório simples em CSV (exemplo)
                const csvContent = this.generateCSVReport();
                this.downloadCSV(csvContent, `analytics-report-${new Date().toISOString().split('T')[0]}.csv`);

                this.showNotification('Relatório exportado com sucesso!', 'success');

            } catch (error) {
                this.showNotification('Erro ao exportar relatório: ' + error.message, 'error');
            } finally {
                const exportBtn = $('#exportData');
                exportBtn.prop('disabled', false).text('📊 Exportar Relatório');
            }
        }

        generateCSVReport() {
            // Gerar relatório CSV simples baseado nos dados atuais
            const current = this.getCurrentMetrics();
            
            return `Relatório Analytics - ${this.currentDateRange.start_date} até ${this.currentDateRange.end_date}
            
Métrica,Valor
Usuários,${current.users || 0}
Visualizações,${current.pageviews || 0}
Taxa de Engajamento,${current.engagement_rate || 0}%
Duração Média da Sessão,${this.formatDuration(current.avg_session_duration || 0)}

Gerado em: ${new Date().toLocaleString('pt-BR')}`;
        }

        downloadCSV(content, filename) {
            const blob = new Blob([content], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            
            link.setAttribute('href', url);
            link.setAttribute('download', filename);
            link.style.visibility = 'hidden';
            
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        getCurrentMetrics() {
            return {
                users: parseInt($('#usersCount').text().replace(/[K,M]/g, '')) || 0,
                pageviews: parseInt($('#pageViewsCount').text().replace(/[K,M]/g, '')) || 0,
                engagement_rate: parseFloat($('#engagementRate').text()) || 0,
                avg_session_duration: 0 // Seria extraído dos dados reais
            };
        }

        // ===================================
        // AUTO REFRESH
        // ===================================
        setupAutoRefresh() {
            // Atualizar dados completos a cada 5 minutos
            setInterval(() => {
                this.loadAllData();
            }, 5 * 60 * 1000);

            // Atualizar dados em tempo real a cada 30 segundos
            setInterval(() => {
                this.loadRealtimeData();
            }, 30 * 1000);
        }
    }

    // ===================================
    // INITIALIZATION
    // ===================================
    $(document).ready(function() {
        console.log('🔍 [MPA DEBUG] DOM ready, verificando se estamos na página Analytics');
        console.log('🔍 [MPA DEBUG] Elemento .mpa-dashboard-analytics encontrado:', $('.mpa-dashboard-analytics').length > 0);
        console.log('🔍 [MPA DEBUG] Elemento #mpaAnalyticsLoading encontrado:', $('#mpaAnalyticsLoading').length > 0);
        console.log('🔍 [MPA DEBUG] mpaAnalytics object disponível:', typeof mpaAnalytics !== 'undefined');
        
        // Verificar se estamos na página de analytics
        if ($('.mpa-dashboard-analytics').length > 0) {
            console.log('✅ [MPA DEBUG] Estamos na página Analytics');
            
            // Verificar se o GA4 está configurado
            if ($('#mpaAnalyticsLoading').length > 0) {
                console.log('✅ [MPA DEBUG] GA4 configurado, inicializando dashboard');
                window.mpaAnalyticsDashboard = new MPAAnalyticsDashboard();
            } else {
                console.log('⚠️ [MPA DEBUG] GA4 não configurado');
            }
        } else {
            console.log('⚠️ [MPA DEBUG] Não estamos na página Analytics');
        }
    });

    // ===================================
    // THEME COMPATIBILITY
    // ===================================
    
    // Atualizar cores dos gráficos quando o tema mudar
    $(document).on('themeChanged darkModeToggled', function() {
        if (window.mpaAnalyticsDashboard) {
            // Re-renderizar gráficos com novas cores
            setTimeout(() => {
                window.mpaAnalyticsDashboard.loadVisitorsData();
                window.mpaAnalyticsDashboard.loadDeviceData();
            }, 100);
        }
    });

})(jQuery);