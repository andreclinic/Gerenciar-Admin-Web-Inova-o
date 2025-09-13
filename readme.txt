=== Gerenciar Admin Web Inovação ===
Contributors: webinovacao
Tags: admin, dashboard, customization, interface
Requires at least: 5.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Transform seu painel administrativo do WordPress com uma interface moderna e customizável.

== Description ==

O plugin Gerenciar Admin Web Inovação transforma completamente o painel administrativo do WordPress, implementando um layout moderno e profissional baseado em um dashboard analítico.

= Recursos Principais =

* **Interface Moderna**: Design limpo e profissional inspirado em dashboards analíticos
* **Sistema de Menus**: Gerenciamento completo de menus por roles (papéis de usuário)
* **Modo Escuro/Claro**: Sistema completo de dark/light mode
* **Responsivo**: Layout totalmente responsivo para mobile e desktop
* **Analytics Integration**: Integração com Google Analytics 4
* **Customização de Login**: Página de login personalizada
* **Gestão de Capabilities**: Sistema avançado de gerenciamento de permissões
* **Compatibilidade WooCommerce**: Exceções especiais para URLs do WooCommerce Admin

= Funcionalidades Detalhadas =

**Gerenciamento de Menus:**
- Criação, edição e exclusão de menus personalizados
- Ordenação por drag-and-drop
- Configuração por roles (administrador, editor, gerente, etc.)
- Integração com menu global do WordPress

**Interface Customizada:**
- Header customizado com informações do usuário
- Sidebar com navegação moderna
- Cards de métricas responsivos
- Limpeza da interface padrão do WordPress

**Segurança:**
- Verificação de nonces em todas as operações
- Sanitização de todas as entradas
- Verificação de permissões adequadas
- Exceções especiais para WooCommerce Admin

== Installation ==

1. Faça upload do plugin para o diretório `/wp-content/plugins/gerenciar-admin`
2. Ative o plugin através do menu 'Plugins' no WordPress
3. Configure as opções através do menu 'Gerenciar Admin' no painel administrativo

== Frequently Asked Questions ==

= O plugin é compatível com WooCommerce? =

Sim, o plugin possui exceções especiais para URLs do WooCommerce Admin, garantindo que usuários com roles apropriadas (shop_manager, gerentes, administrator) sempre tenham acesso às funcionalidades do WooCommerce.

= Como funciona o sistema de menus por roles? =

O plugin permite configurar quais menus cada role pode visualizar, além de permitir a criação de menus personalizados específicos para cada role.

= É possível personalizar o visual? =

Sim, o plugin oferece sistema de dark/light mode e permite customizações através do arquivo modelo_dashboard.html incluído.

== Screenshots ==

1. Dashboard principal com interface moderna
2. Sistema de gerenciamento de menus
3. Configuração de roles e permissões
4. Modo escuro ativado
5. Interface mobile responsiva

== Changelog ==

= 1.1 =
* Adicionada exceção especial para WooCommerce Admin URLs
* Removido código de debug para produção
* Melhorias na segurança e sanitização
* Correções de compatibilidade
* Limpeza de arquivos temporários

= 1.0 =
* Versão inicial
* Sistema completo de gerenciamento de menus
* Interface moderna baseada em dashboard analítico
* Integração com Google Analytics 4
* Sistema de dark/light mode
* Layout responsivo

== Upgrade Notice ==

= 1.1 =
Esta versão inclui correções importantes de segurança e compatibilidade com WooCommerce. Recomendamos a atualização.

== Suporte ==

Para suporte técnico, entre em contato com Web Inovação.

== Requisitos do Sistema ==

* WordPress 5.0 ou superior
* PHP 7.4 ou superior
* Navegadores modernos com suporte a CSS Grid e Flexbox