// Sistema de scroll restoration para manter posição após reload
console.log('JavaScript do plugin gerenciar-admin carregado');

// Sistema de scroll restoration simplificado
(function() {
    'use strict';

    // Aguardar DOM carregar
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM carregado - iniciando sistema de scroll');

        // Verificar se estamos na página correta
        if (window.location.href.indexOf('mpa-menu-roles') === -1) {
            console.log('Não é a página mpa-menu-roles');
            return;
        }

        console.log('Página mpa-menu-roles detectada');

        // Interceptar submits de formulário
        document.addEventListener('submit', function(e) {
            console.log('SUBMIT detectado:', e.target);

            // Capturar posição atual da tela
            const scrollY = window.scrollY || window.pageYOffset;
            console.log('Posição atual do scroll:', scrollY);

            // Salvar no localStorage
            localStorage.setItem('mpa_scroll_position', scrollY);
            console.log('Posição salva no localStorage');
        });

        // Restaurar posição quando a página carrega
        const savedPosition = localStorage.getItem('mpa_scroll_position');
        if (savedPosition) {
            console.log('Posição encontrada no localStorage:', savedPosition);

            setTimeout(function() {
                window.scrollTo(0, parseInt(savedPosition));
                console.log('Scroll restaurado para posição:', savedPosition);

                // Limpar o localStorage
                localStorage.removeItem('mpa_scroll_position');
            }, 100);
        }
    });
})();