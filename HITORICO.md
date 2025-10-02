# HISTORICO.md – Histórico de Alterações do Projeto

Este arquivo registra todas as alterações feitas no plugin **Gerenciar Admin Web Inovação**.  
Serve como memória do projeto para que o CODEX e os desenvolvedores humanos entendam o que já foi implementado.

---

## 📅 Histórico

### ⏺ Update(plugin version 1.5)

- **Data:** 2025-10-02 10:02:29
- **Branch:** codex
- **Autor:** CODEX / OpenAI
- **Descrição:**  
  Incremento da versão do plugin para 1.5 refletindo o ajuste fullscreen do Modo Garçom e demais melhorias recentes.
- **Arquivos afetados:**
  - `gerenciar-admin.php`
- **Problema:** A versão ainda permanecia em 1.4 após aplicar o layout fullscreen dedicado na tela de garçom.
- **Solução:** Atualizado o cabeçalho principal do plugin para 1.5.
- **Justificativa:** Manter rastreio de releases alinhado às mudanças entregues.

### ⏺ Update(garcom fullscreen layout)

- **Data:** 2025-10-02 09:47:45
- **Branch:** codex
- **Autor:** CODEX / OpenAI
- **Descrição:**  
  Aplicado modo fullscreen na tela Modo Garçom, removendo a moldura do admin (topo, menu lateral e rodapé) para que a página ocupe toda a viewport.
- **Arquivos afetados:**
  - `admin/mpa-admin.php`
- **Problema:** A página `modo-garcom-wc` ainda exibia header, sidebar e footer do WordPress, reduzindo a área útil e destoando do fluxo dedicado para atendimento.
- **Solução:** Identificada a tela via slug e adicionadas classes no `body` com estilos específicos para esconder os elementos do admin e zerar margens, mantendo o conteúdo no tamanho máximo.
- **Justificativa:** Atender à necessidade operacional de exibir o modo garçom em tela cheia, sem interferências da interface administrativa.

### ⏺ Update(custom menu capability)

- **Data:** 2025-09-30 16:56:53
- **Branch:** codex
- **Autor:** CODEX / OpenAI
- **Descrição:**  
  Ajustada a capability dos menus personalizados para `read` e normalizado o redirecionamento interno, permitindo que roles sem `manage_options` (como `gerente`) sejam levadas corretamente à URL configurada em vez de travar na slug intermediária `mpa_custom_*`.
- **Arquivos afetados:**
  - `admin/mpa-menu-functions.php`
  - `admin/mpa-menu-manager.php`
  - `admin/mpa-adminmenumain.php`
- **Problema:** Os menus customizados eram registrados com `manage_options`, bloqueando o router `mpa_custom_menu_router` para usuários sem essa permissão e deixando o link preso em `admin.php?page=mpa_custom_*`.
- **Solução:** Alinhado o `add_menu_page()` para usar `read`, normalizado o destino antes do redirect e atualizado a sidebar para renderizar o link final diretamente quando o slug for `mpa_custom_*`, mantendo o controle de acesso pelas regras do plugin e garantindo o destino correto.
- **Justificativa:** Restaurar o comportamento esperado dos links personalizados para gestores não administradores.

### ⏺ Update(menu danger zone modal)

- **Data:** 2025-09-30 18:47:44
- **Branch:** codex
- **Autor:** CODEX / OpenAI
- **Descrição:**  
  Transformada a "Zona de Perigo" da tela de menus por role em um modal com overlay, evitando que o alerta destrutivo permaneça exposto constantemente e reforçando a confirmação antes do reset.
- **Arquivos afetados:**
  - `admin/mpa-menu-settings.php`
- **Problema:** O aviso de reset ficava sempre visível, poluindo a interface e gerando apreensão constante mesmo quando o usuário não pretendia realizar a ação.
- **Solução:** Substituído o bloco aberto por um botão que abre modal com aviso, lista do que será apagado, overlay e suporte a ESC/click fora para fechar, mantendo o fluxo de reset existente.
- **Justificativa:** Melhorar usabilidade e reduzir risco de cliques acidentais sem retirar o alerta necessário para operações destrutivas.

### ⏺ Update(plugin version 1.4)

- **Data:** 2025-09-30 18:55:00
- **Branch:** codex
- **Autor:** CODEX / OpenAI
- **Descrição:**  
  Incremento da versão do plugin para 1.4 refletindo as correções recentes no sistema de menus personalizados e na interface de gerenciamento.
- **Arquivos afetados:**
  - `gerenciar-admin.php`
- **Problema:** A versão registrada permanecia em 1.3 mesmo após novas melhorias entregues.
- **Solução:** Atualização do cabeçalho principal para 1.4.
- **Justificativa:** Manter versionamento alinhado ao estado atual do plugin, facilitando deploy e controle.

### ⏺ Update(remove debug console logs)

- **Data:** 2025-09-30 12:42:58
- **Branch:** codex
- **Autor:** CODEX / OpenAI
- **Descrição:**  
  Removidos rastros de logs informativos e mensagens de debug do console dos módulos de analytics, menu e login para entregar build final de produção sem ruído em navegadores.
- **Arquivos afetados:**
  - `admin/views/mpa-analytics.php`
  - `assets/js/mpa-analytics.js`
  - `assets/js/mpa-adminmenumain.js`
  - `assets/js/mpa-menu-settings.js`
  - `assets/js/mpa-wpbody.js`
  - `assets/js/mpa-wpfooter.js`
  - `assets/js/mpa-custom-login.js`
- **Problema:** Durante o uso em produção, o console do navegador era preenchido com mensagens de depuração que deveriam ter sido desativadas após a finalização do projeto.
- **Solução:** Eliminadas as chamadas `console.log` e ajustes residuais, mantendo apenas avisos de erro necessários e limpando estruturas condicionais que dependiam desses logs.
- **Justificativa:** Evitar ruídos no console dos administradores, reduzir custos de suporte e alinhar o comportamento aos padrões de release.

### ⏺ Update(menu export full schema)

- **Data:** 2025-09-29 10:47:03
- **Branch:** codex
- **Autor:** CODEX / OpenAI
- **Descrição:**  
  Ampliada a exportação/importação dos menus para incluir permissões, ordenação, submenus e itens personalizados em um pacote único versionado.
- **Arquivos afetados:**
  - `admin/mpa-menu-manager.php`
  - `admin/mpa-menu-settings.php`
- **Problema:** A exportação atual levava apenas parte das configurações, impedindo replicar a mesma estrutura de menus e itens customizados em outro ambiente.
- **Solução:** Centralizado o coletor de dados, atualizado o JSON para schema 2.0 com opções globais e roles, e refeito o import para mesclar/substituir mantendo compatibilidade com arquivos antigos.
- **Justificativa:** Garantir que a migração entre projetos traga 100% da navegação customizada, evitando retrabalho manual.

### ⏺ Update(analytics oauth quick connect)

- **Data:** 2025-09-29 08:38:21
- **Branch:** codex
- **Autor:** CODEX / OpenAI
- **Descrição:**  
  Adicionado botão de reconexão rápida ao dashboard do Analytics para iniciar o fluxo OAuth diretamente, evitando ida obrigatória às configurações.
- **Arquivos afetados:**
  - `admin/views/mpa-analytics.php`
- **Problema:** Usuários sem token válido não tinham acesso imediato para reconectar via dashboard, impactando a coleta de métricas.
- **Solução:** Incluído botão primário ao lado do exportador e script que reutiliza a ação `mpa_start_oauth`, exibindo estado de carregamento e tratando erros.
- **Justificativa:** Reduzir atrito na retomada da integração GA4 em cenários de expiração ou desconexão.

### ⏺ Update(analytics oauth redirect dashboard)

- **Data:** 2025-09-29 08:44:03
- **Branch:** codex
- **Autor:** CODEX / OpenAI
- **Descrição:**  
  Ajustado o fluxo OAuth para retornar ao dashboard após conexão iniciada pelo botão rápido, mantendo a experiência no contexto correto.
- **Arquivos afetados:**
  - `admin/views/mpa-analytics.php`
  - `includes/class-mpa-analytics-client.php`
- **Problema:** Depois da autenticação pelo botão do dashboard o usuário era enviado às configurações, quebrando o fluxo desejado.
- **Solução:** Marcado a origem do OAuth com `origin=dashboard` e utilizado o state para redirecionar o callback diretamente a `admin.php?page=mpa-analytics` quando apropriado.
- **Justificativa:** Garantir que a reconexão rápida mantenha o usuário no relatório principal, evitando navegação extra após renovar tokens.

### ⏺ Update(custom login native submit failsafe)

- **Data:** 2025-09-29 10:27:10
- **Branch:** codex
- **Autor:** CODEX / OpenAI
- **Descrição:**  
  Reestruturada a tela de login para preservar campos ocultos/integrações de segurança e adicionar failsafe no submit, evitando travamentos após exibir “Entrando...”.
- **Arquivos afetados:**
  - `admin/mpa-custom-login.php`
- **Problema:** O botão alternava para o estado de carregamento, porém o envio nativo podia ser bloqueado por scripts ou campos removidos, impedindo o redirecionamento ao dashboard.
- **Solução:** Clonado os elementos originais (mensagens, campos ocultos, extras de plugins) antes de reconstruir o layout, reinserindo-os no novo form, e adicionado um fallback que garante o disparo do submit mesmo se o primeiro ciclo falhar.
- **Justificativa:** Manter compatibilidade com plugins como Wordfence/2FA e assegurar que o fluxo de login finalize em todos os cenários.

### ⏺ Update(admin header fullscreen ios fallback)

- **Data:** 2025-09-28 20:48:51
- **Branch:** codex
- **Autor:** CODEX / OpenAI
- **Descrição:**  
  Forçado o modo fullscreen simulado no iOS, garantindo que o botão permaneça visível e que o layout ocupe toda a viewport em iPhone/iPad.
- **Arquivos afetados:**
  - `assets/js/mpa-wpadminbar.js`
  - `assets/css/mpa-wpadminbar.css`
- **Problema:** O Safari móvel reportava suporte parcial à Fullscreen API, levando o script a tentar o fluxo nativo e impedindo o fallback visual, fazendo o botão sumir ou não responder.
- **Solução:** Considerado o iOS como plataforma sem suporte nativo, ativando sempre o fallback CSS, ajustando eventos de toque/clique e refinando o estilo com `100dvh`, header fixo e rolagem suave.
- **Justificativa:** Viabilizar a experiência de tela cheia no mobile Apple sem afetar o comportamento em desktop ou Android.

### ⏺ Update(admin sidebar preloader touch guard)

- **Data:** 2025-09-29 08:17:52
- **Branch:** codex
- **Autor:** CODEX / OpenAI
- **Descrição:**  
  Ajustado o disparo do preloader do menu lateral para ignorar gestos de arraste e expansões de submenu no mobile, preservando a fluidez da navegação.
- **Arquivos afetados:**
  - `assets/js/mpa-adminmenumain.js`
- **Problema:** Qualquer toque no menu móvel ativava o preloader mesmo quando o usuário apenas arrastava ou apenas abria submenus, prejudicando a experiência.
- **Solução:** Adicionada triagem de links navegáveis, detecção de movimento em gestos touch e remoção do gatilho imediato em `pointerdown`, garantindo que o preloader apareça apenas em cliques que realmente navegam.
- **Justificativa:** Evitar feedback indevido durante interações não-navegacionais, mantendo o preloader reservado para carregamentos reais de página.

### ⏺ Update(admin header fullscreen mobile ux)

- **Data:** 2025-09-28 20:24:58
- **Branch:** codex
- **Autor:** CODEX / OpenAI
- **Descrição:**  
  Reforçado o controle de tela cheia para manter o botão visível no mobile, garantir toque funcional no Android e fornecer fallback visual em iOS quando a API nativa estiver ausente.
- **Arquivos afetados:**
  - `assets/js/mpa-wpadminbar.js`
  - `assets/css/mpa-wpadminbar.css`
- **Problema:** Dispositivos móveis não exibiam ou não respondiam ao botão de fullscreen devido à detecção rígida da API, aos eventos `touchstart` e à falta de alternativa no Safari iOS.
- **Solução:** Adicionadas verificações em protótipos para manter o botão ativo, troca para `touchend` com supressão de clique duplicado, fallback CSS simulando fullscreen em iOS e classe de bloqueio apenas para navegadores realmente incompatíveis.
- **Justificativa:** Entregar a experiência solicitada com comportamento consistente entre desktop e mobile, sem depender exclusivamente da API nativa.

### ⏺ Update(admin header fullscreen mobile detection)

- **Data:** 2025-09-28 20:10:41
- **Branch:** codex
- **Autor:** CODEX / OpenAI
- **Descrição:**  
  Ajustada a detecção de suporte à tela cheia para considerar protótipos dos elementos e evitar esconder o botão em navegadores móveis compatíveis.
- **Arquivos afetados:**
  - `assets/js/mpa-wpadminbar.js`
- **Problema:** O botão de fullscreen deixou de aparecer no mobile porque o script avaliava apenas elementos concretos (`document`/`body`) e concluía que não havia suporte.
- **Solução:** Incluídos os protótipos (`Element/HTMLElement/Document`) no processo de detecção, mantendo o botão visível sempre que a API estiver definida e preservando o fallback existente para prefixos antigos.
- **Justificativa:** Garantir que navegadores modernos para dispositivos móveis reconheçam o recurso e exibam o controle conforme solicitado pelo usuário.

### ⏺ Update(admin header fullscreen sequential fallback)

- **Data:** 2025-09-28 19:51:01
- **Branch:** codex
- **Autor:** CODEX / OpenAI
- **Descrição:**  
  Reajustada a detecção da API de tela cheia para tentar múltiplos elementos compatíveis, reaproveitando prefixos antigos e prevenindo falhas assíncronas em navegadores mobile.
- **Arquivos afetados:**
  - `assets/js/mpa-wpadminbar.js`
- **Problema:** Mesmo com o botão exibido, Chrome e Safari mobile não alternavam para tela cheia porque o primeiro elemento testado rejeitava a solicitação e impedia tentativas subsequentes.
- **Solução:** Mapeados os métodos vendor (`webkit`, `moz`, `ms`) e implementado fallback sequencial com tratamento de promessas/rejeições, além de suprimir toques duplicados e registrar avisos para depuração.
- **Justificativa:** Garantir que o recurso funcione nos navegadores móveis suportados, mantendo consistência com o comportamento desktop.

### ⏺ Update(admin header fullscreen mobile support)

- **Data:** 2025-09-28 19:35:36
- **Branch:** codex
- **Autor:** CODEX / OpenAI
- **Descrição:**  
  Unificado o controle de tela cheia para reconhecer suporte móvel, evitar duplicação de ícones no Safari e manter o botão oculto em navegadores sem API.
- **Arquivos afetados:**
  - `assets/js/mpa-wpadminbar.js`
  - `assets/css/mpa-wpadminbar.css`
- **Problema:** Em dispositivos móveis o Chrome não exibia o botão e no Safari surgiam dois ícones inoperantes por conta da detecção genérica da API fullscreen.
- **Solução:** Implementado adaptador único para os vendors (standard, webkit, ms), tentativa sequencial de elementos permitidos e atualização de estado resiliente, além de CSS com classe dedicada para alternar os ícones.
- **Justificativa:** Garantir uma experiência consistente em todo o plugin, respeitando limitações reais da API no mobile e prevenindo comportamentos divergentes entre navegadores.

### ⏺ Update(admin header fullscreen toggle)

- **Data:** 2025-09-28 09:56:26
- **Branch:** codex
- **Autor:** CODEX / OpenAI
- **Descrição:**  
  Adicionado botão de tela cheia no header personalizado alinhado aos controles de modo escuro para ampliar a imersão do administrador.
- **Arquivos afetados:**
  - `admin/mpa-wpadminbar.php`
  - `assets/js/mpa-wpadminbar.js`
  - `assets/css/mpa-wpadminbar.css`
- **Problema:** A interface personalizada não oferecia forma consistente de ativar tela cheia, obrigando o uso de atalhos do navegador e quebrando a expectativa de ergonomia.
- **Solução:** Inserido controle com ícones dedicados no header, sincronizado via JavaScript com os eventos de fullscreen, incluindo fallback para navegadores legados e feedback visual no tema.
- **Justificativa:** Garantir que o recurso solicitado esteja disponível em todo o plugin, com experiência coerente ao dark mode e adesão aos padrões de acessibilidade do layout.

### ⏺ Update(custom login inline touch submit)

- **Data:** 2025-09-27 20:14:52
- **Branch:** codex
- **Autor:** CODEX / OpenAI
- **Descrição:**  
  Ajustada a lógica inline da tela de login para garantir que o estado "Entrando..." seja exibido antes do envio ao tocar no Chrome mobile.
- **Arquivos afetados:**
  - `admin/mpa-custom-login.php`
- **Problema:** As alterações no arquivo JS não tinham efeito porque a tela usa script inline; o botão permanecia estático quando pressionado por toque.
- **Solução:** Introduzidos handlers de `pointerdown/touchstart/click` diretamente no script inline, com checagem de submissão, reflow forçado e dupla chamada a `requestAnimationFrame` antes de submeter o formulário nativo.
- **Justificativa:** Aplicar o feedback visual real na implementação utilizada atualmente, evitando regressões com caches de assets.

### ⏺ Update(custom login button pointer)

- **Data:** 2025-09-27 20:20:56
- **Branch:** codex
- **Autor:** CODEX / OpenAI
- **Descrição:**  
  Corrigido o bloqueio do submit mobile removendo o `pointer-events: none` do estado `mpa-loading` para permitir que o clique conclua antes de aplicar o atraso controlado.
- **Arquivos afetados:**
  - `admin/mpa-custom-login.php`
  - `assets/css/mpa-custom-login.css`
- **Problema:** Ao tocar no botão “Entrar” o carregamento era exibido, porém o clique era cancelado porque o CSS desativava o ponteiro imediatamente, impedindo o submit nativo.
- **Solução:** Tornado o estilo `mpa-loading` apenas visual (opacidade) e mantido o controle contra múltiplos envios via flag JavaScript, preservando o redirecionamento.
- **Justificativa:** Garantir que o fluxo de login complete normalmente após exibir o feedback visual no Chrome mobile.

### ⏺ Update(custom login touch overlay)

- **Data:** 2025-09-27 20:06:29
- **Branch:** codex
- **Autor:** CODEX / OpenAI
- **Descrição:**  
  Aprofundado o tratamento do botão de login para acionar overlay e loading ainda no toque, prolongando levemente o submit para registro visual confiável no Chrome mobile.
- **Arquivos afetados:**
  - `assets/js/mpa-custom-login.js`
- **Problema:** O label “Entrando...” continuava invisível em toques rápidos porque o formulário era enviado antes do navegador pintar as mudanças.
- **Solução:** Aplicado estado de loading apenas quando o formulário não está em submissão, invocado `ensureOverlay()` nos eventos de toque/clique e ampliado a janela de `requestAnimationFrame` + `setTimeout` para ~220 ms antes do submit nativo.
- **Justificativa:** Aumentar a chance de repaint mesmo em dispositivos com animações rápidas, entregando feedback imediato ao usuário mobile.

### ⏺ Update(custom login mobile paint)

- **Data:** 2025-09-27 20:00:20
- **Branch:** codex
- **Autor:** CODEX / OpenAI
- **Descrição:**  
  Refinada a rotina de loading do botão “Entrar” para garantir repintura perceptível antes do envio no Chrome mobile.
- **Arquivos afetados:**
  - `assets/js/mpa-custom-login.js`
- **Problema:** Mesmo após ativar o estado de loading via `touchstart`, o Chrome mobile navegava antes de exibir o label “Entrando...” no botão.
- **Solução:** Forçado reflow ao alterar o label, complementado com gatilhos em `click` e dupla chamada a `requestAnimationFrame` antes do submit nativo, criando uma janela de ~90 ms para o frame pintar a animação.
- **Justificativa:** Assegurar feedback visual imediato para usuários mobile sem comprometer a submissão do formulário.

### ⏺ Update(custom login defer submit)

- **Data:** 2025-09-27 19:51:16
- **Branch:** codex
- **Autor:** CODEX / OpenAI
- **Descrição:**  
  Sincronizado o feedback do botão de login com toques no Chrome mobile, adiando ligeiramente o submit nativo para garantir repintura do estado de carregamento.
- **Arquivos afetados:**
  - `assets/js/mpa-custom-login.js`
- **Problema:** Ao tocar no botão “Entrar” no Chrome mobile a animação não aparecia porque a navegação ocorria antes do repaint do label e da classe de loading.
- **Solução:** Guardado o estado de submissão, disparado `showProgress` e usado `requestAnimationFrame` + `setTimeout` para submeter o formulário após ~75ms, preservando validação e evitando submits duplicados.
- **Justificativa:** Permitir que a alteração visual seja perceptível em dispositivos touch sem impactar o fluxo normal de login.

### ⏺ Update(custom login mobile loading)

- **Data:** 2025-09-27 19:39:46
- **Branch:** codex
- **Autor:** CODEX / OpenAI
- **Descrição:**  
  Ajustado o estado de loading do botão de login para acionar imediatamente em toques no Chrome mobile e garantir reset consistente quando o envio é inválido.
- **Arquivos afetados:**
  - `assets/js/mpa-custom-login.js`
- **Problema:** No Chrome para mobile o botão “Entrar” permanecia estático, pois a alteração de texto só ocorria após o submit e era ofuscada pelo carregamento imediato.
- **Solução:** Criados helpers para aplicar/remover o estado de loading, disparando-os em `pointerdown/touchstart`, além de evitar overlays duplicados e restaurar o label quando a validação falha.
- **Justificativa:** Oferecer feedback visual imediato em dispositivos touch, mantendo paridade com a experiência em desktop.

### ⏺ Update(admin preloader mobile pointer)

- **Data:** 2025-09-27 19:23:18
- **Branch:** codex
- **Autor:** CODEX / OpenAI
- **Descrição:**  
  Alinhado o preloader ao fluxo touch adicionando disparo no `pointerdown/touchstart` do menu lateral e tornando o overlay não bloqueante.
- **Arquivos afetados:**
  - `assets/css/mpa-preloader.css`
  - `assets/js/mpa-adminmenumain.js`
- **Problema:** Mesmo com a API global, o preloader não aparecia no menu toggle mobile porque o clique só dispara após o toque e o overlay bloqueava eventos antes da navegação.
- **Solução:** Mantido `pointer-events: none` mesmo visível para não interferir com os toques e acionado o preloader na fase inicial do toque, preservando filtros para ignorar expansões de submenu.
- **Justificativa:** Garantir feedback imediato no mobile sem impedir que o clique seja convertido em navegação.

### ⏺ Update(admin preloader sidebar touch integration)

- **Data:** 2025-09-27 19:13:41
- **Branch:** codex
- **Autor:** CODEX / OpenAI
- **Descrição:**  
  Integrada a barra lateral ao preloader criando uma API global e disparando manualmente nos cliques de navegação, respeitando o fluxo de toques em dispositivos móveis.
- **Arquivos afetados:**
  - `assets/js/mpa-preloader.js`
  - `assets/js/mpa-adminmenumain.js`
- **Problema:** O menu lateral mobile não exibia o preloader e interações por toque bloqueavam o clique, enquanto o desbloqueio de tela gerava loops esporádicos.
- **Solução:** Exposta a API `MPA_PRELOADER`, adicionada lógica para ignorar toques que apenas expandem submenus e acionado o overlay diretamente dos handlers da sidebar quando ocorre navegação legítima; removida a dependência de `visibilitychange` para evitar loops ao desbloquear a tela.
- **Justificativa:** Garantir feedback consistente nos fluxos de navegação originados pela barra lateral sem interferir nas interações por toque ou em estados de pausa do navegador.

### ⏺ Update(admin preloader sidebar navigation)

- **Data:** 2025-09-27 18:57:53
- **Branch:** codex
- **Autor:** CODEX / OpenAI
- **Descrição:**  
  Ajustada a exibição do preloader para disparar imediatamente ao clicar em links e removido o gatilho de `visibilitychange` que causava loop ao reativar a tela.
- **Arquivos afetados:**
  - `assets/js/mpa-preloader.js`
- **Problema:** O menu lateral mobile continuava sem feedback visual porque o overlay era agendado via `setTimeout`, não chegando a renderizar antes do redirecionamento, e o desbloqueio da tela acionava o preloader indefinidamente.
- **Solução:** Exibição síncrona do overlay no handler de `click` e eliminação do listener de `visibilitychange`, mantendo apenas `beforeunload`/`pagehide` e `pageshow` para gerenciar o estado.
- **Justificativa:** Oferecer feedback consistente tanto no menu lateral quanto em outros fluxos mobile sem interferir na navegação nem ativar loops inesperados.

### ⏺ Update(admin preloader mobile navigation)

- **Data:** 2025-09-27 18:42:06
- **Branch:** codex
- **Autor:** CODEX / OpenAI
- **Descrição:**  
  Simplificada a lógica do preloader para não bloquear o clique e garantir renderização uniforme em links do menu lateral (mobile e desktop).
- **Arquivos afetados:**
  - `assets/js/mpa-preloader.js`
- **Problema:** A tentativa anterior de segurar o toque com timers fazia o overlay aparecer antes do `click`, impedindo o redirecionamento em vários cenários.
- **Solução:** Reescrita a rotina para atuar apenas após o clique, removendo temporizadores de toque e tratadores redundantes de pointer/touch, mantendo gatilhos de `beforeunload`, formulários e `visibilitychange`.
- **Justificativa:** Evitar interferência nos eventos padrão dos links enquanto preserva o feedback visual durante a navegação no painel.

### ⏺ Update(admin preloader mobile menu display)

- **Data:** 2025-09-27 18:34:01
- **Branch:** codex
- **Autor:** CODEX / OpenAI
- **Descrição:**  
  Restaurada a exibição do preloader após cliques no menu lateral mobile sem afetar o comportamento desktop.
- **Arquivos afetados:**
  - `assets/js/mpa-preloader.js`
- **Problema:** O timer introduzido para liberar o toque limpava a exibição do preloader antes do `click`, fazendo o overlay não aparecer em navegações do menu mobile.
- **Solução:** Forçado `showPreloader` dentro do handler de `click` após a limpeza do timer, mantendo a janela de 120 ms para permitir o toque e preservando a lógica de cancelamento quando o evento é prevenido.
- **Justificativa:** Garantir que o usuário tenha feedback visual ao sair do menu lateral em dispositivos touch sem bloquear a navegação.

### ⏺ Update(admin preloader mobile menu fix)

- **Data:** 2025-09-27 18:25:17
- **Branch:** codex
- **Autor:** CODEX / OpenAI
- **Descrição:**  
  Corrigida a interação do preloader com o menu lateral mobile para não bloquear a navegação após o toque inicial.
- **Arquivos afetados:**
  - `assets/js/mpa-preloader.js`
- **Problema:** Ao tocar nos itens do menu mobile o preloader surgia, porém a página não navegava porque o overlay cobria o link antes do `click` ser disparado.
- **Solução:** Introduzido timer curto e cancelamento para `touchstart/pointerdown`, liberando o clique antes de exibir o overlay, adicionando limpeza em `touchend`/`pointerup` e centralizando a remoção de timers ao ocultar o preloader.
- **Justificativa:** Garantir feedback visual sem impedir o fluxo de navegação em dispositivos touch, mantendo a experiência consistente com desktop.

### ⏺ Update(admin preloader mobile menu)

- **Data:** 2025-09-27 18:14:07
- **Branch:** codex
- **Autor:** CODEX / OpenAI
- **Descrição:**  
  Refinada a integração do preloader com o menu lateral em dispositivos mobile, ajustando eventos de toque e garantindo sobreposição máxima.
- **Arquivos afetados:**
  - `assets/js/mpa-preloader.js`
  - `assets/css/mpa-preloader.css`
- **Problema:** Ao navegar pelo menu lateral no mobile o preloader não era exibido, deixando o usuário sem feedback visual enquanto aguardava o carregamento da nova página.
- **Solução:** Reposicionado o overlay diretamente sob `<body>`, removendo o uso de `requestAnimationFrame`, adicionando fallback para `beforeunload/pagehide/visibilitychange`, tratando interações `touchstart/pointerdown` sem cancelar cliques válidos e elevando o `z-index` para ficar acima do off-canvas.
- **Justificativa:** Garantir feedback consistente independentemente do tipo de interação (toque, clique ou submissão) e manter acessibilidade em cenários onde o menu mobile se sobrepõe ao conteúdo.

### ⏺ Update(admin preloader mobile)

- **Data:** 2025-09-27 17:54:06
- **Branch:** codex
- **Autor:** CODEX / OpenAI
- **Descrição:**  
  Ajustada a lógica de exibição do preloader para garantir feedback consistente em dispositivos móveis e submissões realizadas no admin.
- **Arquivos afetados:**
  - `assets/js/mpa-preloader.js`
- **Problema:** Em navegadores mobile o overlay raramente aparecia antes da navegação, deixando o usuário sem indicação de carregamento.
- **Solução:** Removida a dependência de `requestAnimationFrame` e do atraso com `setTimeout`, adicionados gatilhos imediatos para links, formulários, `beforeunload` e `pagehide`, além de normalizar o reset via `pageshow` e o atributo `aria-hidden`.
- **Justificativa:** Garantir que o preloader cumpra seu papel de feedback visual independente do dispositivo ou do tipo de interação, preservando acessibilidade e evitando travamento aparente.

### ⏺ Update(version 1.3)

- **Data:** 2025-09-25 13:33:44
- **Branch:** codex
- **Autor:** CODEX / OpenAI
- **Descrição:**  
  Incrementada a versão do plugin para 1.3 após a inclusão do preloader e ajustes de layout.
- **Arquivos afetados:**
  - `gerenciar-admin.php`
- **Problema:** O cabeçalho do plugin ainda marcava a versão 1.2 mesmo com as novas funcionalidades implantadas.
- **Solução:** Atualizado o metadado `Version` no arquivo principal para refletir a entrega atual.
- **Justificativa:** Manter o versionamento alinhado aos recursos publicados, facilitando controle e deploys.

### ⏺ Update(admin preloader layout)

- **Data:** 2025-09-25 13:31:02
- **Branch:** codex
- **Autor:** CODEX / OpenAI
- **Descrição:**  
  Corrigida a centralização do preloader garantindo que o spinner fique alinhado ao centro da tela.
- **Arquivos afetados:**
  - `admin/mpa-admin.php`
  - `assets/css/mpa-preloader.css`
  - `assets/js/mpa-preloader.js`
- **Problema:** O overlay era renderizado com `display: block`, fazendo o spinner aparecer no topo da página em vez de centralizado.
- **Solução:** Ajustados CSS e JS para manter o container em `display: flex`, oculto por classe, e exibir/esconder usando transição de opacidade.
- **Justificativa:** Garantir feedback visual consistente e alinhado com o layout do painel, evitando distrações para o usuário.

### ⏺ Update(admin preloader)

- **Data:** 2025-09-25 13:24:34
- **Branch:** codex
- **Autor:** CODEX / OpenAI
- **Descrição:**  
  Inserido preloader global no admin para mostrar feedback de carregamento em navegações internas.
- **Arquivos afetados:**
  - `admin/mpa-admin.php`
  - `assets/css/mpa-preloader.css`
  - `assets/js/mpa-preloader.js`
- **Problema:** A navegação entre páginas do admin não indicava progresso, gerando sensação de travamento em carregamentos mais lentos.
- **Solução:** Adicionados estilo e script dedicados, além do markup no rodapé do admin, para exibir um spinner enquanto novas páginas são solicitadas.
- **Justificativa:** Melhorar a experiência do usuário com feedback visual imediato após interações no painel.

### ⏺ Update(custom login autofill)

- **Data:** 2025-09-24 10:44:52
- **Branch:** codex
- **Autor:** CODEX / OpenAI
- **Descrição:**  
  Reestruturada a montagem do formulário de login para reaproveitar os campos nativos do WordPress, preservando atributos importantes para preenchimento automático.
- **Arquivos afetados:**
  - `admin/mpa-custom-login.php`
- **Problema:** Navegadores não conseguiam preencher automaticamente usuário e senha após selecionar credenciais salvas na tela de login customizada.
- **Solução:** Inseridos os campos originais dentro do layout customizado, mantendo placeholders e classes modernas sem perder `autocomplete` e demais atributos esperados pelos gerenciadores de senha.
- **Justificativa:** Garantir compatibilidade com o autofill padrão dos navegadores e melhorar a usabilidade do login.

### ⏺ Update(login redirect default)

- **Data:** 2025-09-24 13:59:06
- **Branch:** codex
- **Autor:** CODEX / OpenAI
- **Descrição:**  
  Ajustado o redirecionamento pós-login para respeitar o destino solicitado e manter como fallback o dashboard padrão do WordPress.
- **Arquivos afetados:**
  - `admin/mpa-custom-login.php`
- **Problema:** Após autenticação pela tela customizada, todos os usuários eram enviados para `admin.php?page=mpa-dashboard`, ignorando a navegação original.
- **Solução:** Atualizada a lógica do filtro `login_redirect` para validar o `redirect_to` fornecido e usar `admin_url()` como padrão, além de alinhar o valor enviado pelo formulário.
- **Justificativa:** Restaurar o comportamento esperado do WordPress, evitando redirecionamentos forçados que quebravam fluxos existentes.

### ⏺ Update(analytics config protection)

- **Data:** 2025-09-21 12:58:39
- **Branch:** codex
- **Autor:** CODEX / OpenAI
- **Descrição:**  
  Protegidas as rotinas de remoção de menus e submenus para preservar o acesso às configurações do GA4.
- **Arquivos afetados:**
  - `admin/mpa-menu-manager.php`
  - `admin/mpa-menu-functions.php`
- **Problema:** Página `mpa-config-analytics` bloqueada por restrições do pipeline de menus.
- **Solução:** Protegido o slug `mpa-config-analytics` nas rotinas de remoção para garantir acesso total a administradores e gestores autorizados.
- **Justificativa:** Corrigir o erro de permissão ao gerar o token do Google Analytics e evitar remoções indevidas do menu.

### ⏺ Update(arquitetura do dashboard)

- **Data:** 2025-09-21
- **Autor:** CODEX / OpenAI
- **Descrição:**  
  Substituído o uso do antigo protótipo `modelo_dashboard2.html` pelo dashboard real do WordPress Admin.  
  Agora, a página `/wp-admin/admin.php?page=mpa-analytics` é a fonte de verdade para layout, CSS e JavaScript.
- **Arquivos afetados:**
  - `agent.md` (atualizado para referenciar apenas `mpa-analytics`)
- **Justificativa:**  
  O arquivo `modelo_dashboard2.html` era apenas um mockup inicial e já não existe mais.  
  Todas as futuras melhorias e integrações devem respeitar a estrutura, estilos e scripts da página `mpa-analytics`.

---

## ✅ Regras de Registro

- Cada alteração deve ser registrada em formato semelhante ao acima.
- Sempre incluir **data, autor, descrição, arquivos afetados e justificativa**.
- As entradas devem ser organizadas em ordem cronológica.
