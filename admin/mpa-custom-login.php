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

// Personalizar a página de login - prioridade alta para sobrepor outros plugins
add_action('login_enqueue_scripts', 'mpa_custom_login_styles', 99);
add_filter('login_headerurl', 'mpa_custom_login_logo_url', 99);
add_filter('login_headertext', 'mpa_custom_login_logo_title', 99);
add_action('login_head', 'mpa_custom_login_head', 99);
add_action('login_footer', 'mpa_custom_login_footer', 99);

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

    // CSS inline para sobrepor outros plugins (Wordfence, UIPress, etc.)
    wp_add_inline_style('mpa-custom-login', '
        /* Centralização correta - Grid Layout */
        body.login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
            font-family: "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
            min-height: 100vh !important;
            display: grid !important;
            place-items: center !important;
            padding: 1rem !important;
            margin: 0 !important;
            overflow-x: hidden !important;
        }

        #login {
            background: white !important;
            border-radius: 1rem !important;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04) !important;
            width: 100% !important;
            max-width: 400px !important;
            padding: 2rem !important;
            position: relative !important;
            border: none !important;
            margin: 0 !important;
            left: auto !important;
            right: auto !important;
            top: auto !important;
            transform: none !important;
        }

        /* Responsivo Mobile */
        @media (max-width: 768px) {
            body.login {
                place-items: start center !important;
                padding: 2rem 1rem !important;
            }
            #login {
                width: calc(100% - 2rem) !important;
                max-width: 380px !important;
            }
        }

        /* Tratamento de notices */
        .login .message,
        .login #login_error {
            margin: 0 0 1.5rem 0 !important;
            border-radius: 0.75rem !important;
            text-align: center !important;
        }

        /* Ajustar quando há mensagens */
        body.login:has(.message),
        body.login:has(#login_error) {
            place-items: start center !important;
            padding-top: 2rem !important;
        }

        /* Desativar completamente UIPress na tela de login */
        body.uip-login,
        body.uip-login::before,
        body.uip-login::after {
            background: none !important;
            content: none !important;
            width: auto !important;
            height: auto !important;
            position: static !important;
            top: auto !important;
            left: auto !important;
            right: auto !important;
            bottom: auto !important;
        }

        /* Remover classe UIPress e forçar nossa */
        body.login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        }

        body.login::before,
        body.login::after {
            display: none !important;
            content: none !important;
        }

        /* Esconder elementos de outros plugins */
        .uip-login-form,
        .uip-login-panel,
        .uip-login-content {
            display: none !important;
        }
    ');
    
    // JavaScript inline será usado ao invés de arquivo separado
    wp_enqueue_script('jquery');
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
    <?php
    // Preparar variáveis PHP para o JavaScript
    $js_logo_url = esc_url($logo_url);
    $js_login_url = wp_login_url();
    $js_lost_password_url = wp_lostpassword_url();
    $js_admin_url = admin_url();
    ?>
    <script>
        jQuery(document).ready(function($) {
            // Variáveis definidas no PHP
            const logoUrl = '<?php echo $js_logo_url; ?>';
            const loginUrl = '<?php echo $js_login_url; ?>';
            const lostPasswordUrl = '<?php echo $js_lost_password_url; ?>';
            const adminUrl = '<?php echo $js_admin_url; ?>';

            // Remover completamente UIPress e outros plugins conflitantes
            $('body').removeClass('uip-login uip-dark-mode uip-light-mode');
            $('body').addClass('mpa-login-override');

            // Remover wrappers conflitantes do UIPress, preservando integrações de segurança
            $('.uip-login-form, .uip-login-panel, .uip-login-content').remove();

            // Forçar remoção de estilos inline do UIPress
            $('style').each(function() {
                const styleContent = $(this).html();
                if (styleContent.includes('uip-login') || styleContent.includes('uip-background')) {
                    $(this).remove();
                }
            });

            // Detectar e ajustar layout para notices/mensagens
            if ($('.message, #login_error').length > 0) {
                $('body').addClass('login-with-message');
            }

            // Monitoramento contínuo para desativar UIPress
            function disableUIPress() {
                $('body').removeClass('uip-login uip-dark-mode uip-light-mode');
                $('.uip-login-panel, .uip-login-content, .uip-login-form').remove();

                // Remover estilos inline do UIPress
                $('style').each(function() {
                    const content = $(this).html();
                    if (content && (content.includes('uip-login') || content.includes('--uip-background'))) {
                        $(this).remove();
                    }
                });
            }

            // Executar imediatamente e repetir
            disableUIPress();
            setInterval(disableUIPress, 100); // Verificar a cada 100ms

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

                const originalMessages = $loginDiv.find('.message, #login_error').clone(true, true);

                const formAction = $loginForm.attr('action') || loginUrl;
                const formMethod = $loginForm.attr('method') || 'post';

                // Reorganizar completamente a estrutura preservando campos originais
                const originalUserInput = $('#user_login').clone();
                const originalPassInput = $('#user_pass').clone();
                const originalRememberMe = $loginForm.find('.forgetmenot').clone(true, true);
                const originalHiddenInputs = $loginForm.find('input[type="hidden"]').clone(true, true);

                const additionalFormNodes = [];
                $loginForm.children().each(function () {
                    const $child = $(this);

                    if ($child.find('#user_login').length || $child.find('#user_pass').length) {
                        return;
                    }

                    if ($child.is('.forgetmenot') || $child.find('#rememberme').length) {
                        return;
                    }

                    if ($child.find('#wp-submit').length) {
                        return;
                    }

                    if ($child.is('input[type="hidden"]') || $child.find('input[type="hidden"]').length) {
                        return;
                    }

                    additionalFormNodes.push($child.clone(true, true));
                });

                const originalRememberInput = originalRememberMe.find('input[name="rememberme"]').first();
                const rememberValueRaw = originalRememberInput.length ? originalRememberInput.val() || 'forever' : 'forever';
                const sanitizedRememberValue = String(rememberValueRaw).replace(/"/g, '&quot;');
                const rememberChecked = originalRememberInput.length ? originalRememberInput.prop('checked') : false;
                const rememberCheckboxAttributes = [
                    'name="rememberme"',
                    'id="rememberme"',
                    'class="checkbox"',
                    'value="' + sanitizedRememberValue + '"'
                ];

                if (rememberChecked) {
                    rememberCheckboxAttributes.push('checked');
                }

                // Garantir atributos necessários para preenchimento automático
                originalUserInput
                    .removeClass('input')
                    .addClass('form-input')
                    .attr('placeholder', 'Digite seu usuário ou e-mail')
                    .attr('required', true)
                    .attr('autocomplete', originalUserInput.attr('autocomplete') || 'username');

                originalPassInput
                    .removeClass('input')
                    .addClass('form-input')
                    .attr('placeholder', 'Digite sua senha')
                    .attr('required', true)
                    .attr('autocomplete', originalPassInput.attr('autocomplete') || 'current-password');

                // Limpar o container #login
                $loginDiv.empty();

                // Reconstruir HTML seguindo exatamente o modelo
                const newHTML = `
                    <!-- Logo Section -->
                    <div class="logo-section">
                        ${logoUrl ? `
                            <div class="logo">
                                <img src="${logoUrl}" alt="Logo" style="max-height: 48px; width: auto;" />
                            </div>
                        ` : `
                            <div class="logo">
                                Analytics <span class="logo-accent">Pro</span>
                            </div>
                        `}
                    </div>

                    <!-- Login Header -->
                    <div class="login-header">
                        <h1 class="login-title">Bem-vindo de volta</h1>
                        <p class="login-subtitle">Faça login na sua conta para continuar</p>
                    </div>

                    <!-- Login Form -->
                    <form id="loginform" name="loginform" method="${formMethod}" action="${formAction}">
                        <div class="form-group">
                            <label for="user_login" class="form-label">Usuário ou E-mail</label>
                            <div class="input-wrapper" data-original-input="user_login"></div>
                        </div>

                        <div class="form-group">
                            <label for="user_pass" class="form-label">Senha</label>
                            <div class="password-container">
                                <div class="input-wrapper" data-original-input="user_pass"></div>
                                <button type="button" class="password-toggle" id="passwordToggle">
                                    <svg class="icon" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                                        <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div class="mpa-extra-fields" id="mpa-extra-fields"></div>

                        <div class="form-options">
                            <label class="remember-me">
                                <input type="checkbox" ${rememberCheckboxAttributes.join(' ')}>
                                <span class="remember-label">Lembrar de mim</span>
                            </label>
                            <a href="${lostPasswordUrl}" class="forgot-password">Esqueci a senha</a>
                        </div>

                        <button type="submit" name="wp-submit" id="wp-submit" class="login-btn">
                            <span id="loginBtnText">Entrar</span>
                        </button>
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

                if (originalMessages.length) {
                    const $messageWrapper = $('<div class="mpa-login-messages"></div>');
                    $messageWrapper.append(originalMessages);
                    $loginDiv.prepend($messageWrapper);
                }

                // Recolocar inputs originais para preservar atributos de autofill do navegador
                const $usernameWrapper = $loginDiv.find('[data-original-input="user_login"]');
                const $passwordWrapper = $loginDiv.find('[data-original-input="user_pass"]');

                if ($usernameWrapper.length) {
                    $usernameWrapper.replaceWith(originalUserInput);
                }

                if ($passwordWrapper.length) {
                    $passwordWrapper.replaceWith(originalPassInput);
                }
                
                // Adicionar eventos
                const $form = $('#loginform');
                const $submitBtn = $form.find('#wp-submit');
                const $submitLabel = $('#loginBtnText');

                const appendedHiddenNames = {};

                if (originalHiddenInputs.length) {
                    originalHiddenInputs.each(function () {
                        const $input = $(this);
                        const name = $input.attr('name');

                        if (name === 'redirect_to') {
                            $input.val(adminUrl);
                        }

                        if (!name) {
                            $form.append($input);
                            return;
                        }

                        if (!appendedHiddenNames[name]) {
                            $form.append($input);
                            appendedHiddenNames[name] = true;
                        }
                    });
                }

                if (!appendedHiddenNames.redirect_to) {
                    $form.append('<input type="hidden" name="redirect_to" value="' + adminUrl.replace(/"/g, '&quot;') + '">');
                    appendedHiddenNames.redirect_to = true;
                }

                if (!appendedHiddenNames.testcookie) {
                    $form.append('<input type="hidden" name="testcookie" value="1">');
                    appendedHiddenNames.testcookie = true;
                }

                if (!appendedHiddenNames.mpa_custom_login) {
                    $form.append('<input type="hidden" name="mpa_custom_login" value="1">');
                    appendedHiddenNames.mpa_custom_login = true;
                }

                if (additionalFormNodes.length) {
                    const $extraFieldsContainer = $form.find('#mpa-extra-fields');
                    additionalFormNodes.forEach(function ($node) {
                        if ($extraFieldsContainer.length) {
                            $extraFieldsContainer.append($node);
                        } else {
                            $form.append($node);
                        }
                    });
                }

                const setLoadingState = function () {
                    if (!$submitBtn.length) {
                        return;
                    }

                    if ($form.data('mpa-submitting') === true) {
                        return;
                    }

                    if (!$submitBtn.hasClass('mpa-loading')) {
                        $submitBtn.addClass('mpa-loading');
                    }

                    if ($submitLabel.length) {
                        $submitLabel.text('Entrando...');
                    } else {
                        $submitBtn.text('Entrando...');
                    }

                    if ($submitBtn[0]) {
                        void $submitBtn[0].offsetWidth;
                    }
                };

                const resetLoadingState = function () {
                    if (!$submitBtn.length) {
                        return;
                    }

                    $submitBtn.removeClass('mpa-loading');
                    $form.data('mpa-submitting', false);
                    $form.data('mpa-native-fired', false);

                    if ($submitLabel.length) {
                        $submitLabel.text('Entrar');
                    } else {
                        $submitBtn.text('Entrar');
                    }
                };

                const deferNativeSubmit = function () {
                    const nativeForm = $form.get(0);

                    if (!nativeForm) {
                        return;
                    }

                    const fireNativeSubmit = function () {
                        if ($form.data('mpa-native-fired') === true) {
                            return;
                        }

                        $form.data('mpa-native-fired', true);

                        try {
                            nativeForm.submit();
                        } catch (error) {
                            console.error('[MPA Login] Erro ao acionar submit nativo:', error);
                            nativeForm.dispatchEvent(new Event('submit', { cancelable: true }));
                        }
                    };

                    if (typeof window.requestAnimationFrame === 'function') {
                        window.requestAnimationFrame(function () {
                            window.requestAnimationFrame(function () {
                                window.setTimeout(function () {
                                    fireNativeSubmit();
                                }, 180);
                            });
                        });
                    } else {
                        window.setTimeout(fireNativeSubmit, 180);
                    }

                    // Fail-safe: se o navegador bloquear o submit, tentar novamente após 1.5s
                    window.setTimeout(function () {
                        if ($form.data('mpa-submitting') === true && $form.data('mpa-native-fired') !== true) {
                            fireNativeSubmit();
                        }
                    }, 1500);
                };

                const isFormFilled = function () {
                    const username = $.trim($('#user_login').val() || '');
                    const password = $.trim($('#user_pass').val() || '');

                    return username.length > 0 && password.length > 0;
                };

                $submitBtn.on('pointerdown touchstart click', function () {
                    if ($form.data('mpa-submitting') === true) {
                        return;
                    }

                    setLoadingState();
                });

                $form.on('submit', function (event) {
                    if ($form.data('mpa-submitting') === true) {
                        return true;
                    }

                    setLoadingState();

                    if (!isFormFilled()) {
                        resetLoadingState();
                        return true; // permitir validação padrão do WP
                    }

                    event.preventDefault();
                    $form.data('mpa-submitting', true);
                    $form.data('mpa-native-fired', false);

                    deferNativeSubmit();

                    return false;
                });

                $(document).on('mpaLogin:resetLoading', resetLoadingState);

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
        }

        /* Otimizações para mobile - reduzir espaçamento */
        @media (max-width: 480px) {
            body.login {
                padding: 1rem 0.5rem !important;
                min-height: 100vh !important;
                height: auto !important;
                display: flex !important;
                align-items: flex-start !important;
                justify-content: center !important;
                padding-top: 3rem !important;
                overflow-y: auto !important;
            }

            .login-container {
                margin: 0 !important;
                padding: 2rem !important;
                min-height: auto !important;
                width: 100% !important;
                max-width: 380px !important;
            }

            #login {
                padding: 25px !important;
                margin: 0 !important;
                width: 100% !important;
                position: relative !important;
            }

            /* Reduzir espaçamento interno do formulário */
            .logo {
                font-size: 1.75rem !important;
                margin-bottom: 1rem !important;
            }

            .login-header {
                margin-bottom: 1.5rem !important;
            }

            .login-title {
                font-size: 1.25rem !important;
                margin-bottom: 0.75rem !important;
            }

            .login-subtitle {
                font-size: 0.9rem !important;
                margin-bottom: 1.5rem !important;
            }

            .form-group {
                margin-bottom: 1.25rem !important;
            }

            .form-options {
                flex-direction: column !important;
                gap: 1rem !important;
                align-items: flex-start !important;
            }

            .login-btn {
                margin-top: 1rem !important;
                margin-bottom: 1.5rem !important;
            }

            .language-selector {
                margin-top: 1rem !important;
            }
        }

        /* Para telas muito pequenas em altura */
        @media (max-width: 480px) and (max-height: 700px) {
            body.login {
                padding-top: 2rem !important;
            }

            .login-container {
                padding: 1.75rem !important;
            }

            .logo {
                font-size: 1.5rem !important;
                margin-bottom: 0.75rem !important;
            }

            .login-header {
                margin-bottom: 1rem !important;
            }
        }

        /* Para iPhone SE e similares */
        @media (max-width: 375px) {
            body.login {
                padding: 0.5rem 0.25rem !important;
                padding-top: 1.5rem !important;
            }

            .login-container {
                padding: 1.5rem !important;
                border-radius: 0.75rem !important;
            }
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
    
    $roles = (array) $user->roles;
    $is_customer = in_array('customer', $roles, true) || in_array('cliente', $roles, true);

    if ($is_customer) {
        if (function_exists('wc_get_page_permalink')) {
            $my_account = wc_get_page_permalink('myaccount');
            if ($my_account) {
                return $my_account;
            }
        }

        return home_url('/minha-conta/');
    }

    // Verificar se veio do login personalizado
    if (isset($_POST['mpa_custom_login'])) {
        $target = '';

        if (isset($_REQUEST['redirect_to']) && $_REQUEST['redirect_to'] !== '') {
            $target = wp_unslash($_REQUEST['redirect_to']);
        } elseif (!empty($redirect_to)) {
            $target = $redirect_to;
        }

        // Fallback para o dashboard padrão do WordPress
        $target = wp_validate_redirect($target, admin_url());

        return $target;
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
    }
}

/**
 * Customizar o título da página de login
 */
add_filter('login_title', 'mpa_custom_login_title');
function mpa_custom_login_title($login_title) {
    return get_bloginfo('name') . ' - Painel Administrativo';
}
