# CLAUDE.md — Correção Drag-and-Drop e Estrutura de Menus

Este documento orienta a correção do sistema de gerenciamento de menus no plugin **Gerenciar Admin Web Inovação** (prefixo `mpa-`).  
Os arquivos principais são:

- `mpa-wpadminbar.php` → Backend / Admin theme
- `mpa-adminmenumain.js` → Frontend JS / Drag-and-drop

---

## Problemas identificados

### PHP (`mpa-wpadminbar.php`)

1. **Admin bar escondida**

   - CSS atual usa `#wpadminbar { display: none !important; }`.
   - Isso quebra a compatibilidade com WordPress e plugins de terceiros.

2. **Estrutura fora do padrão WP**

   - Menus personalizados não respeitam os arrays globais `$menu` e `$submenu`.
   - Isso gera conflitos entre menus nativos e customizados.

3. **Persistência incompleta**
   - O banco não guarda uma árvore consistente (apenas eventos pontuais).
   - Falta padronização em JSON para refletir hierarquia.

---

### JS (`mpa-adminmenumain.js`)

1. **Profundidade ilimitada**

   - Permite sub-submenus, mas o WordPress só suporta 2 níveis (menu + submenu).

2. **Persistência parcial**

   - Apenas duas ações AJAX (`mpa_transform_menu_to_submenu`, `mpa_transform_submenu_to_menu`).
   - Não salva a ordem global dos menus.

3. **Reload forçado**

   - Recarrega a página após drop sem garantir consistência da árvore salva.

4. **Feedback pobre**
   - Não há aviso ao criar submenus inválidos.
   - Falta destaque visual para áreas válidas de drop.

---

## Correções necessárias

1. **Estrutura compatível com WP**

   - Usar `add_menu_page` e `add_submenu_page` com `parent_slug`.
   - Persistir a árvore em JSON no banco.

2. **Salvar árvore completa**

   - Coletar hierarquia inteira via JS após drop.
   - Enviar em uma única chamada AJAX (`mpa_save_menu_tree`).
   - Backend substitui árvore salva e aplica no próximo load.

3. **Prevenir profundidade inválida**

   - Bloquear criação de sub-submenus no JS.
   - Aviso: “⚠️ O WordPress só suporta um nível de submenu”.

4. **Feedback melhorado**

   - Substituir reload por atualização DOM + toast de sucesso/erro.
   - Usar `wp.data.dispatch('core/notices')`.

5. **Roles e capabilities**
   - Manter permissões (`capability`) dos itens após mover.
   - Garantir consistência entre pai/filho.

---

## Exemplo de JSON salvo no banco

```json
[
  {"slug":"index.php","parent":null,"pos":0},
  {"slug":"upload.php","parent":null,"pos":1},
  {"slug":"edit.php?post_type=page","parent":"edit.php","pos":0}
]
Exemplo de conversão JSON → $menu/$submenu (PHP)
add_action('admin_menu', function() {
    $tree = get_option('mpa_menu_tree', []);
    if (!$tree) return;

    global $menu, $submenu;

    // Reordenação de top-levels
    $order = [];
    foreach ($tree as $node) {
        if ($node['parent'] === null) {
            $order[$node['pos']] = $node['slug'];
        }
    }
    ksort($order);
    $desired = array_values($order);

    add_filter('custom_menu_order', '__return_true');
    add_filter('menu_order', fn() => $desired);

    // Reparent itens
    foreach ($tree as $node) {
        if ($node['parent']) {
            add_submenu_page(
                $node['parent'],
                '', '', 'read',
                $node['slug']
            );
        }
    }
}, 999);

Exemplo de salvar árvore completa (JS)
function saveMenuTree() {
  const tree = [];

  document.querySelectorAll('.mpa-menu-item').forEach((el, index) => {
    const slug = el.dataset.slug;
    const parent = el.closest('.mpa-submenu')?.dataset.parent || null;
    const pos = index;
    tree.push({ slug, parent, pos });
  });

  fetch(ajaxurl, {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
    body: new URLSearchParams({
      action: 'mpa_save_menu_tree',
      tree: JSON.stringify(tree),
      _ajax_nonce: mpa_ajax.nonce
    })
  })
  .then(r => r.json())
  .then(resp => {
    if (resp.success) {
      wp.data.dispatch('core/notices').createNotice(
        'success',
        'Menu atualizado com sucesso',
        { type: 'snackbar' }
      );
    } else {
      throw new Error(resp.data?.message || 'Erro desconhecido');
    }
  })
  .catch(err => {
    wp.data.dispatch('core/notices').createNotice(
      'error',
      'Erro ao salvar menu: ' + err.message,
      { type: 'snackbar' }
    );
  });
}

Melhorias sugeridas

UI/UX drag-and-drop

Expandir menu automaticamente ao arrastar sobre ele.

Destacar áreas válidas de drop.

Persistência automática

Salvar imediatamente após drop.

Segurança

Validar current_user_can('manage_options') no PHP antes de salvar.

Sanitizar slugs ao gravar no banco.

Desempenho

Só reconstruir menus se houver alteração real.

Tarefa para Claude Code

Refatorar mpa-wpadminbar.php para alinhar menus customizados com $menu/$submenu via árvore JSON.

Corrigir mpa-adminmenumain.js para salvar hierarquia completa via AJAX, prevenir sub-submenus e dar feedback em tempo real.

Remover reload forçado, atualizar DOM dinamicamente.

Manter compatibilidade com roles e permissões do WordPress.

👉 Objetivo final: menus e submenus reorganizados via drag-and-drop devem sempre refletir a estrutura nativa do WordPress, com persistência confiável, limite de profundidade respeitado e experiência de usuário clara.


---

Vou preparar um esqueleto inicial já estruturado para o seu plugin com:

class-mpa-menu.php (backend PHP) → cuida de salvar/ler árvore JSON, aplicar no $menu/$submenu, expor endpoint AJAX.

mpa-menu.js (frontend JS) → cuida do drag-and-drop, monta JSON e envia via AJAX.

Assim você terá a base pronta, e depois o Claude só refina.

class-mpa-menu.php
<?php
if (!defined('ABSPATH')) exit;

class MPA_Menu_Manager {
    public function __construct() {
        add_action('admin_menu', [$this, 'apply_menu_structure'], 999);
        add_action('wp_ajax_mpa_save_menu_tree', [$this, 'save_menu_tree']);
    }

    /**
     * Aplica a árvore salva em $menu/$submenu
     */
    public function apply_menu_structure() {
        $tree = get_option('mpa_menu_tree', []);
        if (!$tree || !is_array($tree)) return;

        global $menu, $submenu;

        // Ordenar top-levels
        $order = [];
        foreach ($tree as $node) {
            if ($node['parent'] === null) {
                $order[$node['pos']] = $node['slug'];
            }
        }
        ksort($order);
        $desired = array_values($order);

        add_filter('custom_menu_order', '__return_true');
        add_filter('menu_order', fn() => $desired);

        // Criar submenus
        foreach ($tree as $node) {
            if ($node['parent']) {
                add_submenu_page(
                    $node['parent'],
                    '', '', 'read',
                    $node['slug']
                );
            }
        }
    }

    /**
     * AJAX para salvar árvore completa
     */
    public function save_menu_tree() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permissão negada']);
        }

        $tree_raw = $_POST['tree'] ?? '';
        $tree = json_decode(stripslashes($tree_raw), true);

        if (!is_array($tree)) {
            wp_send_json_error(['message' => 'Formato inválido']);
        }

        // Sanitizar
        $clean = [];
        foreach ($tree as $i => $node) {
            $clean[] = [
                'slug'   => sanitize_text_field($node['slug'] ?? ''),
                'parent' => $node['parent'] ? sanitize_text_field($node['parent']) : null,
                'pos'    => intval($node['pos'] ?? $i),
            ];
        }

        update_option('mpa_menu_tree', $clean, false);

        wp_send_json_success(['saved' => count($clean)]);
    }
}

new MPA_Menu_Manager();

mpa-menu.js
document.addEventListener('DOMContentLoaded', function () {
  if (typeof Sortable === 'undefined') return;

  // Configura drag and drop
  const lists = document.querySelectorAll('.mpa-menu-list, .mpa-submenu-list');

  lists.forEach(list => {
    new Sortable(list, {
      group: 'mpa-menus',
      animation: 150,
      fallbackOnBody: true,
      swapThreshold: 0.65,
      onEnd: () => {
        saveMenuTree();
      }
    });
  });

  function saveMenuTree() {
    const tree = [];

    document.querySelectorAll('.mpa-menu-item').forEach((el, index) => {
      const slug = el.dataset.slug;
      const parent = el.closest('.mpa-submenu-list')?.dataset.parent || null;
      const pos = index;
      tree.push({ slug, parent, pos });
    });

    fetch(ajaxurl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
      body: new URLSearchParams({
        action: 'mpa_save_menu_tree',
        tree: JSON.stringify(tree),
        _ajax_nonce: mpa_ajax.nonce
      })
    })
    .then(r => r.json())
    .then(resp => {
      if (resp.success) {
        wp.data.dispatch('core/notices').createNotice(
          'success',
          '✅ Menu atualizado com sucesso',
          { type: 'snackbar' }
        );
      } else {
        throw new Error(resp.data?.message || 'Erro desconhecido');
      }
    })
    .catch(err => {
      wp.data.dispatch('core/notices').createNotice(
        'error',
        '❌ Erro ao salvar menu: ' + err.message,
        { type: 'snackbar' }
      );
    });
  }
});

🚀 Como usar

Adicione SortableJS no admin:

wp_enqueue_script('sortablejs', 'https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js', [], null, true);
wp_enqueue_script('mpa-menu', plugin_dir_url(__FILE__) . 'assets/js/mpa-menu.js', ['jquery','sortablejs'], '1.0', true);
wp_localize_script('mpa-menu', 'mpa_ajax', [
  'nonce' => wp_create_nonce('mpa_menu_nonce')
]);


Estruture seu HTML com .mpa-menu-item e .mpa-submenu-list com data-slug e data-parent.

Claude pode agora refinar:

validação para não criar sub-submenus,

feedback visual no drag,

sincronização com roles.
```
