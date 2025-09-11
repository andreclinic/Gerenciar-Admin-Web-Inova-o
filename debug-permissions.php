<?php
// Arquivo temporário para debug das permissões

// Só executar se estiver testando (removido check de user capabilities no carregamento)
if (!isset($_GET['debug_permissions'])) {
    return;
}

add_action('admin_notices', function() {
    if (!isset($_GET['debug_permissions'])) return;
    
    $user = wp_get_current_user();
    $permissions = get_option('mpa_menu_permissions', []);
    
    echo '<div class="notice notice-info" style="max-height: 400px; overflow: auto;">';
    echo '<h3>🔍 Debug Permissões MPA</h3>';
    echo '<p><strong>Usuário:</strong> ' . $user->user_login . '</p>';
    echo '<p><strong>Roles:</strong> ' . implode(', ', $user->roles) . '</p>';
    
    echo '<h4>Permissões Configuradas:</h4>';
    if (empty($permissions)) {
        echo '<p style="color: red;"><strong>NENHUMA PERMISSÃO CONFIGURADA!</strong></p>';
        echo '<p>Isso significa que todos os menus estão permitidos por padrão.</p>';
    } else {
        echo '<pre style="font-size: 11px;">' . print_r($permissions, true) . '</pre>';
        
        // Testar taxonomias específicas
        echo '<h4>Teste de Taxonomias para suas Roles:</h4>';
        $test_taxonomies = ['category', 'post_tag', 'product_cat', 'product_tag'];
        
        foreach ($user->roles as $role) {
            echo '<p><strong>Role: ' . $role . '</strong></p>';
            foreach ($test_taxonomies as $tax) {
                $allowed = mpa_user_can_see_menu($tax, [$role], $permissions);
                $status = $allowed ? '✅ PERMITIDO' : '❌ BLOQUEADO';
                echo '<div>- ' . $tax . ': ' . $status . '</div>';
            }
        }
    }
    
    echo '<p><em>Para bloquear taxonomias, vá em: Admin → Gerenciar Admin → Menus por Role</em></p>';
    echo '<button onclick="this.parentElement.style.display=\'none\'">❌ Fechar</button>';
    echo '</div>';
});