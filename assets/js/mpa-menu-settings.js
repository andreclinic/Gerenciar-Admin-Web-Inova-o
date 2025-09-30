// Sistema de scroll restoration para manter posição após reload

// Sistema de scroll restoration simplificado
(function() {
    'use strict';

    // Aguardar DOM carregar
    document.addEventListener('DOMContentLoaded', function() {

        // Verificar se estamos na página correta
        if (window.location.href.indexOf('mpa-menu-roles') === -1) {
            return;
        }


        // Interceptar submits de formulário
        document.addEventListener('submit', function(e) {

            // Capturar posição atual da tela
            const scrollY = window.scrollY || window.pageYOffset;

            // Salvar no localStorage
            localStorage.setItem('mpa_scroll_position', scrollY);
        });

        // Restaurar posição quando a página carrega
        const savedPosition = localStorage.getItem('mpa_scroll_position');
        if (savedPosition) {

            setTimeout(function() {
                window.scrollTo(0, parseInt(savedPosition));

                // Limpar o localStorage
                localStorage.removeItem('mpa_scroll_position');
            }, 100);
        }

        // Sistema de upload de arquivo
        const fileUploadContainer = document.querySelector('.mpa-file-upload');
        const fileInput = document.getElementById('mpa_import_file');
        const fileUploadText = document.querySelector('.mpa-file-upload-text');

        if (fileUploadContainer && fileInput && fileUploadText) {

            // Clique no container ativa o input
            fileUploadContainer.addEventListener('click', function(e) {
                e.preventDefault();
                fileInput.click();
            });

            // Prevenir propagação do clique no input para evitar duplicação
            fileInput.addEventListener('click', function(e) {
                e.stopPropagation();
            });

            // Atualizar texto quando arquivo é selecionado
            fileInput.addEventListener('change', function(e) {
                const file = e.target.files[0];

                if (file) {
                    fileUploadText.innerHTML = `
                        <span class="dashicons dashicons-yes-alt" style="color: #46d369;"></span>
                        ${file.name}
                    `;
                    fileUploadContainer.classList.add('file-selected');
                } else {
                    fileUploadText.innerHTML = `
                        <span class="dashicons dashicons-upload"></span>
                        Clique para selecionar arquivo
                    `;
                    fileUploadContainer.classList.remove('file-selected');
                }
            });

            // Drag and drop support
            fileUploadContainer.addEventListener('dragover', function(e) {
                e.preventDefault();
                fileUploadContainer.classList.add('dragover');
            });

            fileUploadContainer.addEventListener('dragleave', function(e) {
                e.preventDefault();
                fileUploadContainer.classList.remove('dragover');
            });

            fileUploadContainer.addEventListener('drop', function(e) {
                e.preventDefault();
                fileUploadContainer.classList.remove('dragover');

                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    fileInput.files = files;

                    // Disparar evento change manualmente
                    const changeEvent = new Event('change', { bubbles: true });
                    fileInput.dispatchEvent(changeEvent);
                }
            });
        }
    });
})();
