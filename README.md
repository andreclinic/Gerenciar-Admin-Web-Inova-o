# Gerenciar Admin Web Inovação

Plugin WordPress que substitui a experiência administrativa padrão por um tema completo, combinando um dashboard de Analytics integrado ao Google Analytics 4, gerenciamento granular de menus por role e uma camada visual moderna (header, sidebar, footer e tela de login customizados).

## Visão Geral
- Redireciona usuários autorizados diretamente para `admin.php?page=mpa-analytics`, transformando o painel inicial em um centro de métricas.
- Integração nativa com GA4 via REST API própria e Chart.js, incluindo filtros de período, modo escuro e fallbacks defensivos.
- Sistema avançado de menus por role com suporte a ícones, drag-and-drop, menus personalizados, promover/demover submenus e exportação/importação de configurações.
- Reestilização total do admin (header com notificações, sidebar dinâmica, preloader global, ajustes de layout e dark mode persistente) mais tela de login em estilo aplicativo SaaS.
- Ferramentas auxiliares para migração do sistema de menus legado, correções rápidas em produção e ocultação de notificações de atualização para roles não administrativas.

## Principais Funcionalidades

### Dashboard Analytics (GA4)
- Usa `admin/views/mpa-analytics.php`, `assets/js/mpa-analytics.js` e `assets/css/mpa-analytics.css` como fonte de verdade para layout e estilo.
- Coleta métricas de usuários, sessões, pageviews, engajamento, duração média, dispositivos, fontes de tráfego, páginas e eventos através da classe `includes/class-mpa-analytics-client.php`.
- Enfileira Chart.js (CDN) e aplica cache em `sessionStorage`, com verificações adicionais para garantir carregamento mesmo quando o enqueue falha.
- Disponibiliza modo dark/light sincronizado com o tema global e atualizações em tempo real (30s) para visitantes ativos.

### Gerenciamento de Menus por Role
- Núcleo em `admin/mpa-menu-functions.php`, UI em `admin/mpa-menu-settings.php` + `assets/js/mpa-menu-settings.js` + `assets/css/mpa-menu-settings.css`.
- Permite renomear menus/submenus, alterar ícones, remover/restaurar itens, promover/demover submenus, criar links personalizados (internos ou externos) e reordenar via drag-and-drop.
- Configurações persistidas na option `mpa_menu_settings_roles`, com herança `_global` + roles específicas e proteção para administradores/role `gerentes`.
- Exportação/importação de JSON, reset por role, pré-visualização com âncoras de rolagem e correções para manter posição após reload.

### Experiência Admin Customizada
- `admin/mpa-adminmenumain.php`, `admin/mpa-wpadminbar.php`, `admin/mpa-wpbody.php`, `admin/mpa-wpcontent.php` e `admin/mpa-wpfooter.php` reescrevem header, sidebar, corpo e rodapé.
- Sidebar dinâmica respeita permissões e restrições configuradas, com suporte mobile (overlay), estado persistido e descrições contextuais.
- Header inclui logo configurável (`option mpa_logo_url`), notificações AJAX, menu do usuário, modo escuro e botão de menu responsivo.
- Layout aplica tipografia Inter, normaliza botões/ tabelas em páginas de listagem, remove notices em excesso e oculta admin bar padrão para não administradores no front.
- Preloader global (`assets/js/mpa-preloader.js`, `assets/css/mpa-preloader.css`) melhora feedback de navegação.

### Tela de Login Customizada
- `admin/mpa-custom-login.php` + `assets/css/mpa-custom-login.css` redesenham a tela de login com gradiente, cartão centralizado, toggle de senha, suporte a notices e overrides para plugins como Wordfence/UIPress.
- Logo pode ser alterado via option `mpa_logo_url`; fallback exibe logotipo textual estilizado.

### Ferramentas e Salvaguardas
- `admin/mpa-hide-updates.php` oculta notices e badges de update para roles não administradoras sem alterar capabilities.
- `admin/mpa-migration-tools.php` diagnostica/migra dados das opções legadas (`mpa_menu_*`) para o novo formato.
- `admin/mpa-quick-fix.php` expõe interface de “Correção Rápida” (limpeza de dados antigos + inicialização segura) para uso emergencial.
- Debug da sidebar disponível via query `?debug_sidebar_restrictions=1` para usuários com `edit_posts`.

## Arquitetura do Plugin
- `gerenciar-admin.php` inicializa constantes de caminho/URL, inclui módulos e controla visibilidade da admin bar no front.
- Documentação complementar em `ARQUITETURA.md` (mapa técnico atualizado), `AGENTS.md` (guidelines operacionais) e `HITORICO.md` (registro cronológico de mudanças).
- Para detalhes linha a linha consulte os arquivos PHP/JS/CSS correspondentes; cada módulo carrega apenas seus assets específicos via `admin_enqueue_scripts`.

## Requisitos
- WordPress 5.8+ (recomendado) com PHP 7.4+.
- Permissões para registrar rotas REST (`rest_api_init`) e executar AJAX (`admin-ajax.php`).
- Chart.js 4.4.0 carregado via CDN (automático ao acessar o dashboard).
- Credenciais válidas do Google Analytics 4 (Client ID, Client Secret, Property ID e opcional Data Stream ID).

## Instalação
1. Faça upload do diretório `gerenciar-admin` para `wp-content/plugins/` ou instale via ferramentas de deploy.
2. Ative em **Plugins > Gerenciar Admin Web Inovação**.
3. Acesso inicial será redirecionado para `Gerenciar Admin > Analytics` quando o usuário tiver `edit_posts` ou `manage_options`.

## Configuração Inicial

### 1. Integração com Google Analytics 4
- Acesse **Gerenciar Admin > Analytics Config** (`admin.php?page=mpa-config-analytics`).
- Crie/seleciona projeto no Google Cloud, habilite a API Analytics Data e configure uma credencial OAuth2 (aplicação web).
- Cadastre a URL de redirecionamento exatamente como exibido na tela (observa http/https, www e caminho).
- Informe Client ID, Client Secret, Property ID e (opcional) Data Stream ID e salve.
- Clique em **Conectar com Google Analytics** para realizar o fluxo OAuth, depois teste conexão. Tokens são armazenados com tempo de expiração e podem ser revogados via botão **Desconectar**.

### 2. Configuração de Menus por Role
- Abra **Gerenciar Admin > Menus por Role** (`admin.php?page=mpa-menu-roles`).
- Selecione `_global` para regras padrão e aplique customizações específicas por role (administrator, editor, gerentes, etc.).
- Use formulários para renomear/remover/promover/demover, criar menus personalizados e drag-and-drop para ordenar.
- Exporte configurações (backup), importe pacotes `.json` quando migrar ambientes e utilize o reset por role quando necessário.

### 3. Personalização Visual
- Ajuste o logo usado no header/login via option `mpa_logo_url` (pode ser mantido em um plugin de configurações ou via `update_option`).
- Avalie o impacto do modo escuro nos plugins instalados; classes CSS principais usam prefixo `mpa-` para minimizar colisões.
- Para reverter ao layout padrão do WordPress em cenários de compatibilidade, implemente a função `mpa_should_disable_for_admin()` retornando `true` conforme sua lógica e carregue em um mu-plugin ou snippet.

## Fluxos Recomendados de Verificação
- Teste o dashboard Analytics após a autenticação com um usuário administrador e valide os gráficos (metrics, visitors, devices, fontes, páginas, eventos, realtime).
- Valide menus para roles críticas (administrator, editor, shop_manager/gerentes) garantindo que menus essenciais como WooCommerce e Rank Math permaneçam acessíveis.
- Exercite o sistema de import/export antes de mover configurações entre ambientes.
- Utilize ambientes com e sem modo escuro para confirmar contraste e usabilidade.

## Boas Práticas e Cuidados
- Siga o fluxo descrito em `AGENTS.md`: planejar alterações, registrar em `HITORICO.md` e manter branch de trabalho conforme diretrizes do projeto.
- Evite editar arquivos core do WordPress ou plugins externos; todas as customizações devem acontecer via hooks/filters existentes no plugin.
- A ocultação de updates é intencional para roles limitadas; comunique usuários sobre políticas de atualização para evitar perda de alertas críticos.
- Preserve a role `gerentes` caso sua instalação dependa da exceção de acesso total implementada no código.

## Diagnóstico e Manutenção
- **Migração de dados:** utilize a aba "Migração/Limpeza" (`admin.php?page=mpa-migration-tools`) para verificar opções legadas e migrar automaticamente para `mpa_menu_settings_roles`.
- **Correção rápida:** em casos de inconsistência grave, execute **🚀 Quick Fix** (`admin.php?page=mpa-quick-fix`) para limpar opções antigas e recriar a configuração básica.
- **Debug da sidebar:** adicione `?debug_sidebar_restrictions=1` à URL do admin para visualizar restrições aplicadas ao usuário atual.

## Limitações Conhecidas
- `admin/mpa-admin.php` referencia `assets/css/mpa-admin.css` e `assets/js/mpa-admin.js`, arquivos inexistentes que resultam em 404 a cada carregamento; avalie remover ou restaurar esses assets.
- Alguns estilos ainda atuam diretamente sobre `body.wp-admin`, `.wrap` e `#wpcontent` sem escopo `body.dwi-theme`, podendo conflitar com plugins visuais.
- A view `admin/views/mpa-analytics.php` contém scripts de fallback que duplicam lógica de `assets/js/mpa-analytics.js`; mantenha ambos sincronizados ao realizar ajustes.
- `mpa-hide-updates.php` pode ocultar avisos importantes para usuários não administradores; avalie a política de comunicação interna antes de habilitar em produção.

## Documentação Complementar
- `ARQUITETURA.md` — mapa técnico detalhado.
- `AGENTS.md` — instruções operacionais para agentes/automação.
- `HITORICO.md` — registro cronológico de alterações.

## Licença
Projeto distribuído sob GPL v2 ou posterior.
