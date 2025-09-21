# 📐 ARQUITETURA DO PLUGIN — Gerenciar Admin Web Inovação

Este documento descreve a estrutura atual do código-fonte, responsabilidades de cada arquivo e pontos críticos identificados.  
Ele serve como **referência técnica** para evolução do projeto e deve ser atualizado sempre que a base mudar.

---

## 🗂️ Estrutura de Pastas e Arquivos
```
./
├── gerenciar-admin.php
├── README.md
├── AGENTS.md
├── HITORICO.md
├── ARQUITETURA.md
├── admin/
│   ├── mpa-admin.php
│   ├── mpa-adminmenumain.php
│   ├── mpa-wpadminbar.php
│   ├── mpa-wpbody.php
│   ├── mpa-wpcontent.php
│   ├── mpa-wpfooter.php
│   ├── mpa-menu-manager.php
│   ├── mpa-menu-functions.php
│   ├── mpa-menu-settings.php
│   ├── mpa-migration-tools.php
│   ├── mpa-quick-fix.php
│   ├── mpa-custom-login.php
│   ├── mpa-hide-updates.php
│   ├── class-mpa-analytics-page.php
│   └── views/
│       ├── mpa-analytics.php
│       └── mpa-analytics-settings.php
├── includes/
│   └── class-mpa-analytics-client.php
├── assets/
│   ├── css/
│   │   ├── mpa-adminmenumain.css
│   │   ├── mpa-analytics.css
│   │   ├── mpa-custom-login.css
│   │   ├── mpa-menu-settings.css
│   │   ├── mpa-wpadminbar.css
│   │   ├── mpa-wpbody.css
│   │   ├── mpa-wpcontent.css
│   │   └── mpa-wpfooter.css
│   └── js/
│       ├── mpa-adminmenumain.js
│       ├── mpa-analytics.js
│       ├── mpa-custom-login.js
│       ├── mpa-menu-settings.js
│       ├── mpa-wpadminbar.js
│       ├── mpa-wpbody.js
│       ├── mpa-wpcontent.js
│       └── mpa-wpfooter.js
└── CLAUDE.md
```

---

## 🧩 Responsabilidades Principais
- `gerenciar-admin.php` — boot do plugin. Define constantes de caminho/URL, inclui todos os módulos, controla visibilidade da admin-bar no front para não administradores e redireciona usuários autorizados para `admin.php?page=mpa-analytics` ao acessar `index.php`.
- `admin/mpa-admin.php` — enfileira CSS/JS globais do plugin para o painel, com destaque para dependências específicas da tela `mpa-menu-roles` (menu settings, nonce/scroll). Também injeta CSS para ajustar âncoras.
- `admin/mpa-adminmenumain.php` — substitui o menu lateral nativo por um sidebar customizado. Renderiza menus/submenus usando `$menu/$submenu`, aplica ícones customizados, integra menus personalizados e respeita restrições de role via `mpa_get_user_menu_restrictions()`. Usa `mpa-adminmenumain.js` para drag-and-drop e interações.
- `admin/mpa-menu-manager.php` — registra o menu “Gerenciar Admin” e seus submenus (Dashboard, Analytics, Configurações, Migração, Quick Fix). Orquestra import/export/reset de configurações, ordem de menus, custom menus (handlers AJAX), debug visual e proteção a plugins críticos (Rank Math). Consome funções de `mpa-menu-functions.php` e `mpa-menu-settings.php`.
- `admin/mpa-menu-functions.php` — núcleo das regras de visibilidade por role. Calcula configurações efetivas (`mpa_menu_settings_roles` + legado), injeta menus customizados, renomeia/promove/demove/remove itens e reordena menus/submenus em `admin_menu` (prioridade 9999). Fornece utilities para pré-visualização, normalização de URLs e roteamento de menus custom (`mpa_custom_menu_router`).
- `admin/mpa-menu-settings.php` — UI e processamento de formulários na tela “Menus por Role”. Cada ação (rename, remove, promote, demote, restore, update icon) respeita nonce e `manage_options`, atualizando `mpa_menu_settings_roles`. Implementa redirecionamento com âncora para preservar a posição da página.
- `admin/class-mpa-analytics-page.php` — instância Singleton-like que enfileira assets do dashboard Analytics (Chart.js, CSS/JS), localiza strings/URLs, valida permissões e renderiza views `mpa-analytics.php` e `mpa-analytics-settings.php`. Expõe wrappers `mpa_render_analytics_page()` e `mpa_render_analytics_settings_page()`.
- `includes/class-mpa-analytics-client.php` — integração GA4: fluxo OAuth (start/callback), persistência de tokens/credenciais, endpoints REST (`/mpa/v1/analytics/*`), rate limiting por IP/usuário, agregação de métricas, eventos, realtime e ações AJAX (disconnect/test connection).
- `admin/views/mpa-analytics.php` — layout completo do dashboard (cards de métricas, gráficos, tabelas, filtros). Inclui blocos de fallback/console para garantir carregamento do Chart.js e dados via REST.
- `assets/js/mpa-analytics.js` — camada JS principal do dashboard. Gerencia filtros, cache em sessionStorage, renderização com Chart.js, atualização periódica, fallback para UI ativa, exibição de notificações.
- `admin/mpa-wpadminbar.php`, `mpa-wpbody.php`, `mpa-wpcontent.php`, `mpa-wpfooter.php` — reconstroem header, layout do corpo, reset de conteúdo padrão e rodapé do admin, escondendo elementos do core e aplicando tema custom (dark mode, notificações persistentes, menu mobile). Cada arquivo enfileira os CSS/JS correspondentes.
- `admin/mpa-custom-login.php` — substitui a tela de login por layout custom (Inter, gradient, logotipo dinâmico, overrides inline para plugins conflitantes, manipulação de avisos).
- `admin/mpa-hide-updates.php` — esconde notificações de update para roles não administradoras removendo hooks e usando CSS agressivo; mantém administradores sem restrição.
- `admin/mpa-migration-tools.php`/`admin/mpa-quick-fix.php` — suplementos de manutenção (migração/limpeza, correções rápidas) disponíveis via menu principal.
- `assets/css` e `assets/js` — pares de estilo/script que dão suporte às telas acima. Cada arquivo PHP enfileira seu CSS/JS correspondente.
- Documentação: `AGENTS.md` traz diretrizes operacionais; `HITORICO.md` registra mudanças; `ARQUITETURA.md` (este arquivo) mantém o mapa técnico; `README.md` introduz o plugin.

---

## 🔄 Fluxo de Integração
- Bootstrap (`gerenciar-admin.php`) inclui todos os módulos e define hooks iniciais para redirecionamentos e admin bar.
- Sistema de menus: `mpa-menu-manager.php` injeta o menu principal e configurações; `mpa-menu-functions.php` manipula `$menu/$submenu` segundo as opções (`mpa_menu_settings_roles`, `mpa_custom_menus`, `mpa_menu_customizations`, `mpa_menu_order`); `mpa-adminmenumain.php` lê o resultado e renderiza a sidebar custom com suporte a ícones, descrições e menus personalizados.
- UI da tela “Menus por Role” (`mpa-menu-settings.php`, `mpa-menu-settings.js`) consome as opções e persiste mudanças via POST/AJAX; export/import/reset trafegam JSON com todas as opções relevantes.
- Dashboard Analytics: `class-mpa-analytics-page.php` e `class-mpa-analytics-client.php` trabalham em conjunto. As views e JS consomem a REST API própria para montar gráficos e cartões. Localização de scripts disponibiliza URLs/nonce, e a view possui fallback para garantir execução mesmo se Chart.js não carregar via enqueue.
- Customizações visuais (header/sidebar/body/footer/login) dependem de CSS/JS dedicados para impor o layout `dwi-theme`. Hooks em `admin_head`, `in_admin_header` e `admin_enqueue_scripts` garantem que a substituição ocorra antes da renderização do core.
- Restrições por role se aplicam tanto no sidebar custom como no menu real do WordPress, garantindo consistência mesmo se o usuário acessar URLs diretas. `mpa-hide-updates.php` e demais módulos auxiliam no controle de UX para roles com acesso limitado.

---

## ⚠️ Pontos Críticos e Riscos
- **Assets ausentes** — `admin/mpa-admin.php` referencia `assets/css/mpa-admin.css` e `assets/js/mpa-admin.js`, arquivos inexistentes. Gera 404 em cada load e indica dependências faltantes.
- **Escopo CSS** — Diversos estilos custom (ex.: `mpa-wpbody.php`, `mpa-wpadminbar.php`, `mpa-wpcontent.php`, `mpa-wpfooter.php`) atuam diretamente sobre `body.wp-admin`, `.wrap`, `#wpcontent` sem usar o escopo `body.dwi-theme` exigido no AGENTS.md. Aumenta risco de colisão com core/plugins e dificulta ativar/desativar o tema custom.
- **Sistema de permissões duplicado** — Código ainda lê opções legadas (`mpa_menu_permissions`) e as novas (`mpa_menu_settings_roles`). A manutenção simultânea pode gerar divergências, especialmente em funções como `mpa_user_can_see_menu()` e `mpa_get_user_menu_restrictions()`.
- **Ocultação agressiva de updates** — `mpa-hide-updates.php` remove notices e badges via CSS amplo e remoção de hooks. Pode ocultar alertas críticos (segurança, sincronização WooCommerce) para roles não admins. Necessário balancear UX x visibilidade de alertas importantes.
- **Sidebar custom** — Reimplementar o menu WP implica acompanhar mudanças do core (estrutura de `$menu`, highlight, capabilities) e de plugins de terceiros. Proteções específicas (Rank Math, Jet Smart Filters) estão hardcoded; novos plugins podem demandar regras adicionais.
- **Fallbacks redundantes no Analytics** — `views/mpa-analytics.php` inclui blocos inline que replicam lógica do JS (`mpa-analytics.js`), tornando manutenção mais difícil e abrindo chance de comportamento divergente.
- **Controle da admin bar frontend** — `gerenciar-admin.php` força `show_admin_bar(false)` com CSS inline. Verificar se roles custom que precisam de admin bar (ex.: suporte) conseguem habilitar.
- **Dependência de roles custom ("gerentes")** — Regras assumem existência das roles `gerentes` e variantes. Ausência ou renome pode quebrar acesso padrão.

---

> Atualize este documento sempre que a estrutura, responsabilidades ou riscos mudarem para manter o mapa do plugin confiável.
