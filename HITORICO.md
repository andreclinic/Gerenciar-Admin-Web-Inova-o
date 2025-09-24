# HISTORICO.md – Histórico de Alterações do Projeto

Este arquivo registra todas as alterações feitas no plugin **Gerenciar Admin Web Inovação**.  
Serve como memória do projeto para que o CODEX e os desenvolvedores humanos entendam o que já foi implementado.

---

## 📅 Histórico

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
