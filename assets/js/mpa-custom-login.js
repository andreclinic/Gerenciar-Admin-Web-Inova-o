/**
 * Custom Login JavaScript
 * 
 * Adiciona interações e animações modernas à tela de login personalizada
 */

jQuery(document).ready(function($) {
    'use strict';
    
    console.log('🎨 MPA Custom Login: Inicializando interface personalizada');
    
    // Configurações
    const config = {
        animationDuration: 300,
        particleCount: 20,
        typingSpeed: 100
    };
    const selectors = {
        submitBtn: '#wp-submit',
        submitLabel: '#loginBtnText'
    };

    // Inicialização
    init();
    
    function init() {
        setupFormAnimations();
        setupInputEffects();
        setupSubmitHandler();
        setupParticleBackground();
        setupKeyboardShortcuts();
        setupAccessibility();
        setupWelcomeMessage();
    }
    
    /**
     * Configura animações do formulário
     */
    function setupFormAnimations() {
        // Animação de entrada com delay
        setTimeout(() => {
            $('#loginform').addClass('mpa-form-animate');
        }, 200);
        
        // Animação de entrada dos elementos
        $('#nav, #backtoblog').css({
            opacity: 0,
            transform: 'translateY(10px)'
        }).animate({
            opacity: 1
        }, config.animationDuration).css('transform', 'translateY(0)');
    }
    
    /**
     * Efeitos nos campos de input
     */
    function setupInputEffects() {
        const inputs = $('#loginform input[type="text"], #loginform input[type="password"]');
        
        inputs.each(function() {
            const $input = $(this);
            const $wrapper = $('<div class="mpa-input-wrapper"></div>');
            
            $input.wrap($wrapper);
            
            // Efeito de foco
            $input.on('focus', function() {
                $(this).closest('.mpa-input-wrapper').addClass('mpa-input-focused');
                addRippleEffect(this);
            });
            
            $input.on('blur', function() {
                $(this).closest('.mpa-input-wrapper').removeClass('mpa-input-focused');
            });
            
            // Validação em tempo real
            $input.on('input', function() {
                validateField(this);
            });
        });
        
        // Efeito de digitação no placeholder
        addTypingEffect();
    }
    
    /**
     * Adiciona efeito ripple nos inputs
     */
    function addRippleEffect(element) {
        const $element = $(element);
        const $ripple = $('<div class="mpa-ripple"></div>');
        
        $element.append($ripple);
        
        setTimeout(() => {
            $ripple.remove();
        }, 600);
    }
    
    /**
     * Validação de campos
     */
    function validateField(field) {
        const $field = $(field);
        const value = $field.val();
        
        // Remover classes de validação anteriores
        $field.removeClass('mpa-valid mpa-invalid');
        
        if (field.type === 'text' || field.name === 'log') {
            // Validação de usuário/email
            if (value.length > 0) {
                const isEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
                const isUsername = /^[a-zA-Z0-9_]{3,}$/.test(value);
                
                if (isEmail || isUsername) {
                    $field.addClass('mpa-valid');
                } else if (value.length > 2) {
                    $field.addClass('mpa-invalid');
                }
            }
        } else if (field.type === 'password') {
            // Validação básica de senha
            if (value.length >= 4) {
                $field.addClass('mpa-valid');
            } else if (value.length > 0) {
                $field.addClass('mpa-invalid');
            }
        }
    }
    
    /**
     * Handler do submit do formulário
     */
    function setupSubmitHandler() {
        const setLoadingState = function () {
            const $submit = $(selectors.submitBtn);
            if (!$submit.length) {
                return;
            }

            if (!$submit.hasClass('mpa-loading')) {
                $submit.addClass('mpa-loading');
            }

            const $label = $(selectors.submitLabel);
            if ($label.length) {
                $label.text('Entrando...');
            } else {
                $submit.text('Entrando...');
            }

            if ($submit[0]) {
                void $submit[0].offsetWidth;
            }
        };

        const resetLoadingState = function () {
            const $submit = $(selectors.submitBtn);
            if (!$submit.length) {
                return;
            }

            $submit.removeClass('mpa-loading');
            $('#loginform').data('mpa-submitting', false);

            if ($(selectors.submitLabel).length) {
                $(selectors.submitLabel).text('Entrar');
            } else {
                $submit.text('Entrar');
            }
        };

        const ensureOverlay = function () {
            let $overlay = $('.mpa-loading-overlay');
            if (!$overlay.length) {
                $overlay = $('<div class="mpa-loading-overlay"></div>');
                $('body').append($overlay);
            }

            return $overlay;
        };

        const removeOverlay = function () {
            $('.mpa-loading-overlay').remove();
        };

        $(selectors.submitBtn).on('pointerdown touchstart', function () {
            if ($('#loginform').data('mpa-submitting') === true) {
                return;
            }
            setLoadingState();
            ensureOverlay();
        });

        $(selectors.submitBtn).on('click', function () {
            if ($('#loginform').data('mpa-submitting') === true) {
                return;
            }
            setLoadingState();
            ensureOverlay();
        });

        $('#loginform').on('submit', function(e) {
            const $form = $(this);
            const alreadySubmitting = $form.data('mpa-submitting') === true;

            setLoadingState();
            const $overlay = ensureOverlay();

            const isValid = validateForm();

            if (!isValid) {
                e.preventDefault();
                resetLoadingState();
                $overlay.remove();
                showError('Por favor, verifique seus dados');
                return false;
            }

            if (alreadySubmitting) {
                return true;
            }

            e.preventDefault();
            $form.data('mpa-submitting', true);

            // Adicionar indicador de progresso
            showProgress();

            var flush = function (cb) {
                window.requestAnimationFrame(function () {
                    window.requestAnimationFrame(function () {
                        cb();
                    });
                });
            };

            flush(function () {
                window.setTimeout(function () {
                    $form.trigger('mpaLogin:beforeNativeSubmit');
                    $form.get(0).submit();
                }, 220);
            });

            return false;
        });

        $(document).on('mpaLogin:resetLoading', function () {
            resetLoadingState();
            removeOverlay();
        });
    }
    
    /**
     * Valida o formulário completo
     */
    function validateForm() {
        const username = $('#user_login').val();
        const password = $('#user_pass').val();
        
        return username.length > 0 && password.length > 0;
    }
    
    /**
     * Mostra indicador de progresso
     */
    function showProgress() {
        const $progress = $('<div class="mpa-progress-bar"><div class="mpa-progress-fill"></div></div>');
        $('#loginform').append($progress);
        
        // Animar barra de progresso
        setTimeout(() => {
            $progress.find('.mpa-progress-fill').css('width', '100%');
        }, 100);
    }
    
    /**
     * Mostra mensagem de erro
     */
    function showError(message) {
        // Remover erros anteriores
        $('.mpa-error-message').remove();
        
        const $error = $('<div class="mpa-error-message">' + message + '</div>');
        $('#loginform').prepend($error);
        
        // Animação de entrada
        $error.css({
            opacity: 0,
            transform: 'translateY(-10px)'
        }).animate({
            opacity: 1
        }, config.animationDuration).css('transform', 'translateY(0)');
        
        // Remover após alguns segundos
        setTimeout(() => {
            $error.fadeOut(config.animationDuration, function() {
                $(this).remove();
            });
        }, 3000);
    }
    
    /**
     * Cria fundo de partículas animadas
     */
    function setupParticleBackground() {
        const $container = $('<div class="mpa-particles-container"></div>');
        $('body').append($container);
        
        for (let i = 0; i < config.particleCount; i++) {
            createParticle($container, i);
        }
    }
    
    /**
     * Cria uma partícula individual
     */
    function createParticle($container, index) {
        const $particle = $('<div class="mpa-particle"></div>');
        
        // Propriedades aleatórias
        const size = Math.random() * 4 + 2;
        const left = Math.random() * 100;
        const duration = Math.random() * 20 + 10;
        const delay = Math.random() * 20;
        
        $particle.css({
            width: size + 'px',
            height: size + 'px',
            left: left + '%',
            animationDuration: duration + 's',
            animationDelay: delay + 's'
        });
        
        $container.append($particle);
    }
    
    /**
     * Atalhos de teclado
     */
    function setupKeyboardShortcuts() {
        $(document).on('keydown', function(e) {
            // Enter para focar no próximo campo
            if (e.key === 'Tab') {
                const $focused = $(':focus');
                if ($focused.is('#user_login')) {
                    setTimeout(() => $('#user_pass').focus(), 10);
                }
            }
            
            // Ctrl+Enter para submeter
            if (e.ctrlKey && e.key === 'Enter') {
                $('#loginform').submit();
            }
            
            // Escape para limpar campos
            if (e.key === 'Escape') {
                $('#user_login, #user_pass').val('').blur();
            }
        });
    }
    
    /**
     * Melhorias de acessibilidade
     */
    function setupAccessibility() {
        // ARIA labels
        $('#user_login').attr('aria-label', 'Nome de usuário ou endereço de email');
        $('#user_pass').attr('aria-label', 'Senha');
        $('#wp-submit').attr('aria-label', 'Entrar no painel administrativo');
        
        // Indicadores visuais de foco
        $('#loginform input').on('focus', function() {
            $(this).attr('aria-expanded', 'true');
        }).on('blur', function() {
            $(this).attr('aria-expanded', 'false');
        });
        
        // Anunciar mudanças de estado
        $('#wp-submit').on('click', function() {
            $(this).attr('aria-busy', 'true');
        });
    }
    
    /**
     * Efeito de digitação no placeholder
     */
    function addTypingEffect() {
        const messages = [
            'Bem-vindo de volta!',
            'Faça login para continuar',
            'Acesso seguro ao seu painel'
        ];
        
        let messageIndex = 0;
        let charIndex = 0;
        const $title = $('<h2 class="mpa-welcome-title"></h2>');
        
        $('#login h1').after($title);
        
        function typeMessage() {
            if (charIndex < messages[messageIndex].length) {
                $title.text($title.text() + messages[messageIndex].charAt(charIndex));
                charIndex++;
                setTimeout(typeMessage, config.typingSpeed);
            } else {
                setTimeout(() => {
                    $title.text('');
                    charIndex = 0;
                    messageIndex = (messageIndex + 1) % messages.length;
                    typeMessage();
                }, 3000);
            }
        }
        
        setTimeout(typeMessage, 1000);
    }
    
    /**
     * Mensagem de boas-vindas personalizada
     */
    function setupWelcomeMessage() {
        const hour = new Date().getHours();
        let greeting;
        
        if (hour < 12) {
            greeting = 'Bom dia!';
        } else if (hour < 18) {
            greeting = 'Boa tarde!';
        } else {
            greeting = 'Boa noite!';
        }
        
        // Adicionar saudação personalizada
        const $greeting = $('<div class="mpa-greeting">' + greeting + '</div>');
        $('#loginform').prepend($greeting);
        
        // Animação de entrada
        $greeting.css({
            opacity: 0,
            transform: 'translateY(-10px)'
        }).delay(500).animate({
            opacity: 1
        }, config.animationDuration).css('transform', 'translateY(0)');
    }
    
    /**
     * Detectar modo escuro do sistema
     */
    function setupDarkMode() {
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            $('body').addClass('mpa-dark-mode');
        }
        
        // Escutar mudanças
        window.matchMedia('(prefers-color-scheme: dark)').addListener(function(e) {
            $('body').toggleClass('mpa-dark-mode', e.matches);
        });
    }
    
    // Inicializar modo escuro
    setupDarkMode();
    
    // Log de inicialização completa
    setTimeout(() => {
        console.log('✅ MPA Custom Login: Interface inicializada com sucesso');
    }, 1000);
    
    // Interceptar erros de login para melhor UX
    $(window).on('load', function() {
        const $error = $('#login_error');
        if ($error.length) {
            $error.hide().fadeIn(config.animationDuration);
            
            // Foco automático no primeiro campo em caso de erro
            setTimeout(() => {
                $('#user_login').focus();
            }, 300);
        }
    });
});
