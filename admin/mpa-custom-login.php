<?php
/**
 * Custom Login Page
 * 
 * Substitui a tela de login padrão do WordPress por uma interface moderna
 * que segue o design do plugin Gerenciar Admin
 * 
 * @package Gerenciar_Admin
 * @since 1.0.0
 */

// Evitar acesso direto
if (!defined('ABSPATH')) {
    exit;
}

// Personalizar a página de login
add_action('login_enqueue_scripts', 'mpa_custom_login_styles');
add_filter('login_headerurl', 'mpa_custom_login_logo_url');
add_filter('login_headertext', 'mpa_custom_login_logo_title');
add_action('login_head', 'mpa_custom_login_head');
add_action('login_footer', 'mpa_custom_login_footer');

/**
 * Carrega estilos personalizados para a página de login
 */
function mpa_custom_login_styles() {
    wp_enqueue_style(
        'mpa-custom-login',
        ADMIN_BAR_MENU_URL . 'assets/css/mpa-custom-login.css',
        [],
        '1.0.0'
    );
    
    wp_enqueue_script(
        'mpa-custom-login-js',
        ADMIN_BAR_MENU_URL . 'assets/js/mpa-custom-login.js',
        ['jquery'],
        '1.0.0',
        true
    );
}

/**
 * Personaliza a URL do logo
 */
function mpa_custom_login_logo_url() {
    return home_url();
}

/**
 * Personaliza o título do logo
 */
function mpa_custom_login_logo_title() {
    return get_bloginfo('name');
}

/**
 * Adiciona elementos customizados ao head da página de login
 */
function mpa_custom_login_head() {
    // Obter logo das configurações
    $logo_url = get_option('mpa_logo_url', 'https://www.webinovacao.com.br/wp-content/uploads/2024/07/logo-web-inovacao-horizontal-escura.png');
    ?>
    <!-- Font Inter para seguir o modelo -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        /* Logo dinâmico baseado no modelo */
        .mpa-logo-display {
            font-size: 2rem;
            font-weight: 800;
            color: #111827;
        }
        .mpa-logo-accent {
            color: #2563eb;
        }
        
        <?php if ($logo_url): ?>
        /* Se tem logo customizada, usar imagem */
        #login h1 a {
            background-image: url('<?php echo esc_url($logo_url); ?>') !important;
            background-size: contain !important;
            background-repeat: no-repeat !important;
            background-position: center !important;
            width: 200px !important;
            height: 60px !important;
            text-indent: -9999px;
        }
        <?php else: ?>
        /* Senão, usar texto estilizado */
        #login h1 a {
            background: none !important;
            text-indent: 0 !important;
            width: auto !important;
            height: auto !important;
        }
        <?php endif; ?>
    </style>
    
    <!-- Meta tags para responsividade -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
}

/**
 * Adiciona elementos customizados ao footer da página de login
 */
function mpa_custom_login_footer() {
    $logo_url = get_option('mpa_logo_url', 'https://www.webinovacao.com.br/wp-content/uploads/2024/07/logo-web-inovacao-horizontal-escura.png');
    ?>
    <script>
        jQuery(document).ready(function($) {
            // Aguardar um pouco para garantir que os elementos WordPress foram criados
            setTimeout(function() {
                // Detectar se estamos na tela de login principal (não recuperação de senha)
                const isMainLogin = $('#loginform').length > 0 && $('#lostpasswordform').length === 0 && $('#resetpassform').length === 0;
                
                // Se não for tela de login principal, não fazer transformação
                if (!isMainLogin) {
                    // Apenas remover elementos nativos e aplicar animação
                    $('#nav, #backtoblog').remove();
                    
                    // Adicionar título personalizado baseado na tela
                    let customTitle = '';
                    if ($('#lostpasswordform').length > 0) {
                        customTitle = '<h2 class="mpa-welcome-title">Recuperar Senha</h2>';
                    } else if ($('#resetpassform').length > 0) {
                        customTitle = '<h2 class="mpa-welcome-title">Nova Senha</h2>';
                    }
                    
                    if (customTitle) {
                        // Remover h1 nativo do WordPress e adicionar título customizado
                        $('#login h1').after(customTitle).hide();
                    }
                    
                    $('#login').addClass('mpa-animate-in');
                    return;
                }
                
                // Pegar referências dos elementos existentes
                const $loginDiv = $('#login');
                const $loginForm = $('#loginform');
                
                // Se não existir, não fazer nada
                if (!$loginDiv.length || !$loginForm.length) return;
                
                // Reorganizar completamente a estrutura
                const originalUserInput = $('#user_login').clone();
                const originalPassInput = $('#user_pass').clone();
                const originalRememberMe = $('.forgetmenot').clone();
                const originalSubmit = $('#wp-submit').clone();
                
                // Limpar o container #login
                $loginDiv.empty();
                
                // Reconstruir HTML seguindo exatamente o modelo
                const newHTML = `
                    <!-- Logo Section -->
                    <div class="logo-section">
                        <?php if ($logo_url): ?>
                            <div class="logo">
                                <img src="<?php echo esc_url($logo_url); ?>" alt="Logo" style="max-height: 48px; width: auto;" />
                            </div>
                        <?php else: ?>
                            <div class="logo">
                                Analytics <span class="logo-accent">Pro</span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Login Header -->
                    <div class="login-header">
                        <h1 class="login-title">Bem-vindo de volta</h1>
                        <p class="login-subtitle">Faça login na sua conta para continuar</p>
                    </div>

                    <!-- Login Form -->
                    <form id="loginform" name="loginform" method="post" action="<?php echo wp_login_url(); ?>">
                        <div class="form-group">
                            <label for="user_login" class="form-label">Usuário ou E-mail</label>
                            <input type="text" name="log" id="user_login" class="form-input" placeholder="Digite seu usuário ou e-mail" required>
                        </div>

                        <div class="form-group">
                            <label for="user_pass" class="form-label">Senha</label>
                            <div class="password-container">
                                <input type="password" name="pwd" id="user_pass" class="form-input" placeholder="Digite sua senha" required>
                                <button type="button" class="password-toggle" id="passwordToggle">
                                    <svg class="icon" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                                        <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div class="form-options">
                            <label class="remember-me">
                                <input type="checkbox" name="rememberme" class="checkbox">
                                <span class="remember-label">Lembrar de mim</span>
                            </label>
                            <a href="<?php echo wp_lostpassword_url(); ?>" class="forgot-password">Esqueci a senha</a>
                        </div>

                        <button type="submit" name="wp-submit" id="wp-submit" class="login-btn">
                            <span id="loginBtnText">Entrar</span>
                        </button>
                        
                        <input type="hidden" name="redirect_to" value="<?php echo admin_url('admin.php?page=mpa-dashboard'); ?>">
                        <input type="hidden" name="mpa_custom_login" value="1">
                    </form>

                    <!-- Language Selector -->
                    <div class="language-selector">
                        <div class="language-dropdown">
                            <button type="button" class="language-btn" id="languageBtn">
                                <span class="flag flag-br"></span>
                                <span id="currentLang">PT-BR</span>
                                <svg class="icon" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                </svg>
                            </button>
                            <div class="language-options" id="languageOptions">
                                <div class="language-option" data-lang="pt">
                                    <span class="flag flag-br"></span>
                                    <span>Português (Brasil)</span>
                                </div>
                                <div class="language-option" data-lang="en">
                                    <span class="flag flag-us"></span>
                                    <span>English</span>
                                </div>
                                <div class="language-option" data-lang="es">
                                    <span class="flag flag-es"></span>
                                    <span>Español</span>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
                // Inserir novo HTML
                $loginDiv.html(newHTML);
                
                // Adicionar eventos
                $('#loginform').on('submit', function() {
                    $('#wp-submit').addClass('mpa-loading');
                    $('#loginBtnText').text('Entrando...');
                });
                
                // Funcionalidade do botão de mostrar/ocultar senha
                $(document).on('click', '#passwordToggle', function() {
                    const passwordInput = $('#user_pass');
                    const icon = $(this).find('svg');
                    
                    if (passwordInput.attr('type') === 'password') {
                        passwordInput.attr('type', 'text');
                        icon.html('<path d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 11-4.243-4.243m4.242 4.242L9.88 9.88"/>');
                    } else {
                        passwordInput.attr('type', 'password');
                        icon.html('<path d="M10 12a2 2 0 100-4 2 2 0 000 4z" /><path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />');
                    }
                });
                
                // Funcionalidade do seletor de idioma
                $(document).on('click', '#languageBtn', function(e) {
                    e.stopPropagation();
                    $('#languageOptions').toggleClass('show');
                });
                
                // Fechar dropdown quando clicar fora
                $(document).on('click', function() {
                    $('#languageOptions').removeClass('show');
                });
                
                // Trocar idioma
                $(document).on('click', '.language-option', function(e) {
                    const lang = $(this).data('lang');
                    const flagClass = $(this).find('.flag').attr('class');
                    const langText = $(this).find('span:last').text();
                    
                    // Atualizar botão
                    $('#languageBtn .flag').attr('class', flagClass);
                    if (lang === 'pt') {
                        $('#currentLang').text('PT-BR');
                    } else {
                        $('#currentLang').text(lang.toUpperCase());
                    }
                    
                    // Atualizar textos da interface (exemplo básico)
                    if (lang === 'en') {
                        $('.login-title').text('Welcome back');
                        $('.login-subtitle').text('Sign in to your account to continue');
                        $('label[for="user_login"]').text('Username or Email');
                        $('label[for="user_pass"]').text('Password');
                        $('#user_login').attr('placeholder', 'Enter your username or email');
                        $('#user_pass').attr('placeholder', 'Enter your password');
                        $('.remember-label').text('Remember me');
                        $('.forgot-password').text('Forgot password');
                        $('#loginBtnText').text('Sign In');
                    } else if (lang === 'es') {
                        $('.login-title').text('Bienvenido de vuelta');
                        $('.login-subtitle').text('Inicia sesión en tu cuenta para continuar');
                        $('label[for="user_login"]').text('Usuario o Email');
                        $('label[for="user_pass"]').text('Contraseña');
                        $('#user_login').attr('placeholder', 'Ingresa tu usuario o email');
                        $('#user_pass').attr('placeholder', 'Ingresa tu contraseña');
                        $('.remember-label').text('Recordarme');
                        $('.forgot-password').text('Olvidé mi contraseña');
                        $('#loginBtnText').text('Iniciar Sesión');
                    } else {
                        $('.login-title').text('Bem-vindo de volta');
                        $('.login-subtitle').text('Faça login na sua conta para continuar');
                        $('label[for="user_login"]').text('Usuário ou E-mail');
                        $('label[for="user_pass"]').text('Senha');
                        $('#user_login').attr('placeholder', 'Digite seu usuário ou e-mail');
                        $('#user_pass').attr('placeholder', 'Digite sua senha');
                        $('.remember-label').text('Lembrar de mim');
                        $('.forgot-password').text('Esqueci a senha');
                        $('#loginBtnText').text('Entrar');
                    }
                    
                    $('#languageOptions').removeClass('show');
                });
                
                // Animação de entrada
                setTimeout(() => {
                    $loginDiv.addClass('mpa-animate-in');
                }, 100);
                
            }, 500);
        });
    </script>
    
    <!-- Estilos para corrigir problemas -->
    <style>
        /* Esconder elementos WordPress que podem aparecer */
        #nav, #backtoblog { display: none !important; }
        
        /* Classe para animação */
        .mpa-animate-in {
            opacity: 1;
            transform: translateY(0);
        }
        
        /* Loading state */
        .mpa-loading {
            opacity: 0.7;
            pointer-events: none;
        }
    </style>
    <?php
}

/**
 * Personaliza a mensagem de erro de login
 */
add_filter('wp_login_errors', 'mpa_custom_login_errors', 10, 2);
function mpa_custom_login_errors($errors, $redirect_to) {
    // Personalizar mensagens de erro
    if (isset($errors->errors['invalid_username'])) {
        $errors->errors['invalid_username'] = ['<strong>Erro:</strong> Nome de usuário não encontrado.'];
    }
    if (isset($errors->errors['incorrect_password'])) {
        $errors->errors['incorrect_password'] = ['<strong>Erro:</strong> Senha incorreta.'];
    }
    return $errors;
}

/**
 * Personaliza o texto do botão de login
 */
add_filter('gettext', 'mpa_change_login_button_text', 20, 3);
function mpa_change_login_button_text($translated_text, $text, $domain) {
    if ($text === 'Log In' && is_login()) {
        $translated_text = 'Entrar';
    }
    if ($text === 'Username or Email Address' && is_login()) {
        $translated_text = 'Usuário ou Email';
    }
    if ($text === 'Password' && is_login()) {
        $translated_text = 'Senha';
    }
    if ($text === 'Remember Me' && is_login()) {
        $translated_text = 'Lembrar de mim';
    }
    return $translated_text;
}

/**
 * Adiciona campos extras personalizados (se necessário)
 */
add_action('login_form', 'mpa_add_login_fields');
function mpa_add_login_fields() {
    ?>
    <!-- Campo oculto para identificar login personalizado -->
    <input type="hidden" name="mpa_custom_login" value="1">
    <?php
}

/**
 * Redirecionar após login baseado no role do usuário
 */
add_filter('login_redirect', 'mpa_custom_login_redirect', 10, 3);
function mpa_custom_login_redirect($redirect_to, $request, $user) {
    // Se não é um usuário válido, retorna o redirect padrão
    if (!isset($user->roles) || !is_array($user->roles)) {
        return $redirect_to;
    }
    
    // Verificar se veio do login personalizado
    if (isset($_POST['mpa_custom_login'])) {
        // Sempre redirecionar para o dashboard personalizado
        return admin_url('admin.php?page=mpa-dashboard');
    }
    
    return $redirect_to;
}

/**
 * Adicionar informações de segurança
 */
add_action('wp_login', 'mpa_log_successful_login', 10, 2);
function mpa_log_successful_login($user_login, $user) {
    // Log básico de acesso (opcional)
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('MPA Custom Login: User ' . $user_login . ' logged in at ' . current_time('mysql'));
    }
}

/**
 * Customizar o título da página de login
 */
add_filter('login_title', 'mpa_custom_login_title');
function mpa_custom_login_title($login_title) {
    return get_bloginfo('name') . ' - Painel Administrativo';
}