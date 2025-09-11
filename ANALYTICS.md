# 📊 Analytics Dashboard - Gerenciar Admin

## Visão Geral

A nova funcionalidade de Analytics integra o plugin Gerenciar Admin Web Inovação com o Google Analytics GA4, oferecendo um dashboard completo de métricas em tempo real diretamente no painel administrativo do WordPress.

## 🚀 Características Principais

### Dashboard de Métricas
- **Métricas em tempo real**: Usuários ativos, páginas visualizadas, eventos
- **Análise de visitantes**: Dados históricos com gráficos interativos
- **Segmentação por dispositivo**: Desktop, mobile, tablet
- **Fontes de tráfego**: Orgânico, direto, social, referral
- **Principais cidades**: Localização geográfica dos visitantes
- **Páginas mais visitadas**: Rankings de conteúdo
- **Integração WordPress**: Posts e comentários recentes

### Filtros de Data
- Últimos 7 dias
- Últimos 30 dias (padrão)
- Últimos 90 dias
- Período personalizado

### Design Responsivo
- Layout baseado no `modelo_dashboard.html`
- Compatível com dark mode automático
- Otimizado para dispositivos móveis
- Cards interativos com hover effects

## 📁 Estrutura de Arquivos

```
gerenciar-admin/
├── admin/
│   ├── class-mpa-analytics-page.php     # Classe principal da página
│   └── views/
│       ├── mpa-analytics.php            # View principal do dashboard
│       └── mpa-analytics-settings.php   # View de configurações GA4
├── includes/
│   └── class-mpa-analytics-client.php   # Cliente GA4 e REST API
└── assets/
    ├── css/
    │   └── mpa-analytics.css             # Estilos do dashboard
    └── js/
        └── mpa-analytics.js              # JavaScript e Chart.js
```

## ⚙️ Configuração do Google Analytics GA4

### 1. Google Cloud Console
1. Acesse: https://console.cloud.google.com/
2. Crie um novo projeto ou selecione existente
3. Habilite a **Google Analytics Reporting API**
4. Vá em **Credenciais** > **Criar credenciais** > **ID do cliente OAuth 2.0**
5. Configure como "Aplicativo da Web"
6. Adicione a URL de redirecionamento:
   ```
   https://seusite.com/wp-admin/admin.php?page=mpa-analytics-settings
   ```

### 2. Google Analytics
1. Acesse: https://analytics.google.com/
2. Vá em **Admin** > **Informações da propriedade**
3. Copie o **ID da propriedade** (ex: 123456789)
4. (Opcional) Vá em **Fluxos de dados** e copie o **ID do fluxo de dados**

### 3. WordPress
1. Acesse **Gerenciar Admin** > **Analytics Config**
2. Preencha:
   - **Client ID**: Do Google Cloud Console
   - **Client Secret**: Do Google Cloud Console  
   - **Property ID**: Do Google Analytics
   - **Data Stream ID**: (Opcional) Do Google Analytics
3. Clique em **Salvar Configurações**
4. Teste a conexão

## 🔌 API REST Endpoints

A integração expõe endpoints REST para acessar dados do GA4:

### Métricas Principais
```
GET /wp-json/mpa/v1/analytics/metrics
?start_date=2024-01-01&end_date=2024-01-31
```

### Dados de Visitantes
```
GET /wp-json/mpa/v1/analytics/visitors
?start_date=2024-01-01&end_date=2024-01-31
```

### Dispositivos
```
GET /wp-json/mpa/v1/analytics/devices
?start_date=2024-01-01&end_date=2024-01-31
```

### Fontes de Tráfego
```
GET /wp-json/mpa/v1/analytics/traffic-sources
?start_date=2024-01-01&end_date=2024-01-31
```

### Principais Cidades
```
GET /wp-json/mpa/v1/analytics/cities
?start_date=2024-01-01&end_date=2024-01-31
```

### Principais Páginas
```
GET /wp-json/mpa/v1/analytics/pages
?start_date=2024-01-01&end_date=2024-01-31
```

### Dados em Tempo Real
```
GET /wp-json/mpa/v1/analytics/realtime
```

## 🎨 Personalização CSS

O CSS usa variáveis CSS para facilitar customização:

```css
.mpa-dashboard-analytics {
    --mpa-accent-primary: #2563eb;     /* Cor principal */
    --mpa-success: #10b981;            /* Cor de sucesso */
    --mpa-warning: #f59e0b;            /* Cor de aviso */
    --mpa-error: #ef4444;              /* Cor de erro */
}
```

### Dark Mode
O tema escuro é aplicado automaticamente quando:
- `body.dark-mode` está presente
- Ou `.dark-mode` está presente no container

## 📊 Gráficos e Chart.js

### Gráfico de Visitantes (Barras)
```javascript
// Implementado em mpa-analytics.js
this.charts.visitors = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: data.labels,
        datasets: [{
            label: 'Visitantes',
            data: data.visitors,
            backgroundColor: '#2563eb'
        }]
    }
});
```

### Gráfico de Dispositivos (Donut)
```javascript
this.charts.device = new Chart(ctx, {
    type: 'doughnut',
    data: {
        labels: data.labels,
        datasets: [{
            data: data.data,
            backgroundColor: ['#2563eb', '#9333ea', '#10b981']
        }]
    }
});
```

## 🔄 Atualização Automática

- **Dados completos**: A cada 5 minutos
- **Dados em tempo real**: A cada 30 segundos
- **Manual**: Botão "Atualizar Dados"

## 🛠️ Desenvolvimento

### Adicionar Nova Métrica

1. **Endpoint REST**:
```php
// Em class-mpa-analytics-client.php
register_rest_route($namespace, '/nova-metrica', array(
    'methods' => 'GET',
    'callback' => array($this, 'get_nova_metrica'),
    'permission_callback' => array($this, 'check_permissions')
));
```

2. **Método GA4**:
```php
public function get_nova_metrica($request) {
    $data = $this->make_ga4_request('reports:runReport', array(
        'property' => 'properties/' . $this->get_property_id(),
        'metrics' => array(array('name' => 'novaMetrica'))
    ));
    
    return rest_ensure_response($data);
}
```

3. **JavaScript**:
```javascript
async loadNovaMetrica() {
    const response = await this.makeRequest('nova-metrica');
    this.updateNovaMetricaDisplay(response.data);
}
```

4. **HTML/View**:
```php
<!-- Em mpa-analytics.php -->
<div class="mpa-card">
    <h3 class="mpa-metric-title">Nova Métrica</h3>
    <div class="mpa-metric-value" id="novaMetrica">0</div>
</div>
```

## 🔐 Segurança

### Tokens OAuth
- Armazenados como WordPress options
- Refresh automático quando expira
- Logs de atividade para auditoria

### Permissões
- Requer `manage_options` capability
- Nonce verification em todas as requisições AJAX
- Sanitização de dados de entrada

### API Rate Limits
- Implementar cache quando necessário
- Respeitar limites do Google Analytics
- Error handling robusto

## 🐛 Debugging

### Logs de Atividade
Acesse **Analytics Config** para ver logs de:
- Conexões estabelecidas
- Erros de autenticação
- Requisições falhadas

### Console do Browser
```javascript
// Verificar objeto principal
console.log(window.mpaAnalyticsDashboard);

// Debug de requisições
console.log('Analytics data:', response);
```

### WordPress Debug
```php
// wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);

// Logs em /wp-content/debug.log
error_log('[MPA Analytics] Debug message');
```

## 🚨 Solução de Problemas

### "Google Analytics não configurado"
1. Verificar credenciais em **Analytics Config**
2. Confirmar Property ID correto
3. Testar conexão

### "Erro ao carregar dados"
1. Verificar logs de atividade
2. Confirmar permissões do usuário
3. Verificar se tokens não expiraram

### Gráficos não aparecem
1. Verificar se Chart.js carregou
2. Confirmar dados retornados pela API
3. Verificar console do browser

### CSS não aplicado
1. Limpar cache do WordPress
2. Verificar se arquivo CSS existe
3. Confirmar enqueue correto

## 📈 Roadmap

### Próximas Versões
- [ ] Relatórios em PDF
- [ ] Alertas personalizados
- [ ] Integração com WooCommerce
- [ ] Metas e objetivos
- [ ] Análise de funil
- [ ] Comparação de períodos
- [ ] Widget WordPress
- [ ] Exportação Excel

### Melhorias Planejadas
- [ ] Cache inteligente
- [ ] Otimização de performance
- [ ] Mais tipos de gráficos
- [ ] Filtros avançados
- [ ] Drill-down de dados
- [ ] Integração com outras APIs

## 📚 Recursos Adicionais

- [Google Analytics Reporting API](https://developers.google.com/analytics/devguides/reporting/data/v1)
- [Chart.js Documentation](https://www.chartjs.org/docs/)
- [WordPress REST API](https://developer.wordpress.org/rest-api/)
- [OAuth 2.0 Google](https://developers.google.com/identity/protocols/oauth2)

---

**Desenvolvido por**: Web Inovação  
**Versão**: 1.0.0  
**Compatibilidade**: WordPress 5.0+, PHP 7.4+