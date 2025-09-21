# AGENT.md – Instruções para o CODEX da OpenAI

Este documento define como o CODEX deve operar dentro do projeto **Gerenciar Admin Web Inovação**.

---

## 📌 Funcionalidades do Plugin

### 1. Dashboard com Google Analytics

- Página principal do admin: `/wp-admin/admin.php?page=mpa-analytics`.
- **Fonte de verdade**: todo o layout, CSS e JavaScript devem seguir exatamente o que está implementado nesta página.
- Exibe métricas do **Google Analytics** usando **Chart.js**:
  - Sessões
  - Usuários
  - Visualizações de página
  - Taxa de rejeição
  - Duração média da sessão
  - Fontes de tráfego (orgânico, social, direto, referência)
  - Dispositivos (desktop, mobile, tablet)
  - Conteúdo mais acessado
- Suporta **filtro por intervalo de datas**.
- Estilo responsivo com **dark mode**.

### 2. Gerenciamento de Menus por Role

- Customização granular do menu lateral do WordPress.
- Funcionalidades:
  - Criar links personalizados (internos/externos).
  - Renomear menus e submenus.
  - Alterar ícones (Dashicons ou SVG custom).
  - Remover menus/submenus.
  - Promover/demover menus ↔ submenus.
  - Reordenar menus e submenus via drag-and-drop.
- Suporte a **restrições por role**:
  - Administradores e `gerentes` sempre têm acesso completo.
  - Outras roles seguem as configurações definidas.
- Proteção para não quebrar menus de plugins críticos como **Rank Math SEO**.
- Estrutura dividida em:
  - `mpa-menu-functions.php` → lógica principal.
  - `mpa-adminmenumain.php` → renderização do sidebar.
  - `mpa-menu-settings.php / .js` → interface de gerenciamento.

---

## 🔎 Instruções para o CODEX

1. **Leitura Completa**

   - Sempre analisar todos os arquivos e pastas do repositório.
   - Manter um mapa atualizado da estrutura do plugin.

2. **Planos de Melhoria**

   - Quando solicitado, elaborar um **plano de execução detalhado**, incluindo:
     - Descrição da melhoria.
     - Impacto esperado.
     - Arquivos a serem modificados.
     - Ordem sugerida de implementação.
     - Testes necessários após a mudança.

3. **Histórico das Alterações**

   - Toda alteração ou criação deve ser registrada em `HISTORICO.md`.
   - O registro precisa conter:
     - Data e hora
     - Autor (CODEX ou usuário)
     - Descrição clara da mudança
     - Arquivos modificados
     - Justificativa
   - Esse arquivo será a memória do projeto para futuras iterações.

4. **Controle de Versão**
   - Toda modificação deve ser commitada no GitHub, branch **`codex`**.
   - Formato dos commits:
     ```
     ⏺ Update(<arquivo>)
       ⎿  Breve descrição da alteração
     ```

---

## ⚠️ Regras Especiais

- Nunca sobrescrever código core do WordPress ou de plugins externos.
- Sempre usar **hooks e filters** para integrar alterações.
- Manter compatibilidade com **WooCommerce** e **Rank Math SEO**.
- Aplicar escopo de estilização com `body.dwi-theme` para evitar conflitos.
- Testar todas as mudanças em múltiplas roles (`administrator`, `editor`, `gerentes` etc).
- Garantir usabilidade (ex: após reload manter posição na página).

---

## ✅ Fluxo de Trabalho do CODEX

1. Ler o repositório completo.
2. Entender contexto e dependências.
3. Gerar plano antes de qualquer alteração.
4. Implementar mudança de forma incremental.
5. Registrar no `HISTORICO.md`.
6. Commitar na branch `codex`.
7. Validar em ambiente admin.

---

👉 O objetivo é garantir que o CODEX trabalhe de forma organizada, persistente e consistente no desenvolvimento do plugin **Gerenciar Admin Web Inovação**.
