# HISTORICO.md – Histórico de Alterações do Projeto

Este arquivo registra todas as alterações feitas no plugin **Gerenciar Admin Web Inovação**.  
Serve como memória do projeto para que o CODEX e os desenvolvedores humanos entendam o que já foi implementado.

---

## 📅 Histórico

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
