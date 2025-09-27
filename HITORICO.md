# HISTORICO.md – Histórico de Alterações do Projeto

Este arquivo registra todas as alterações feitas no plugin **Gerenciar Admin Web Inovação**.  
Serve como memória do projeto para que o CODEX e os desenvolvedores humanos entendam o que já foi implementado.

---

## 📅 Histórico

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
