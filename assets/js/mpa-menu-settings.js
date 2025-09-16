/**
 * JavaScript para funcionalidades da página de configurações de menus
 */
document.addEventListener('DOMContentLoaded', function() {

    // ===== FUNCIONALIDADES DE UX =====

    // Tooltip para botões com ícones
    initTooltips();

    // Tooltip para slugs truncados
    initSlugTooltips();

    // Animações suaves para formulários
    initFormAnimations();

    // Melhorar upload de arquivos
    initFileUpload();

    // Confirmações personalizadas
    initCustomConfirmations();

    // Estados de loading
    initLoadingStates();

    // ===== FUNÇÕES AUXILIARES =====

    /**
     * Inicializa tooltips para botões com ícones
     */
    function initTooltips() {
        const iconButtons = document.querySelectorAll('.mpa-icon-btn[title]');

        iconButtons.forEach(button => {
            button.addEventListener('mouseenter', function() {
                if (this.getAttribute('title')) {
                    this.classList.add('mpa-tooltip');
                    this.setAttribute('data-tooltip', this.getAttribute('title'));
                    this.removeAttribute('title');
                }
            });

            button.addEventListener('mouseleave', function() {
                this.classList.remove('mpa-tooltip');
            });
        });
    }

    /**
     * Inicializa tooltips para slugs truncados
     */
    function initSlugTooltips() {
        // Função para processar células de slug
        function processSlugCells() {
            const slugCells = document.querySelectorAll('.mpa-menu-table td:nth-child(3)');
            const maxLength = 16; // Máximo de caracteres visíveis para 15% da largura

            slugCells.forEach(cell => {
                const originalText = cell.textContent.trim();

                if (originalText.length > maxLength) {
                    // Trunca o texto e adiciona tooltip
                    const truncatedText = originalText.substring(0, maxLength) + '...';
                    cell.textContent = truncatedText;
                    cell.setAttribute('title', originalText);
                } else {
                    // Remove tooltip se não for necessário
                    cell.removeAttribute('title');
                }
            });
        }

        // Processa células existentes
        processSlugCells();

        // Observa mudanças na tabela para processar novas células
        const tableContainer = document.querySelector('.mpa-menu-table');
        if (tableContainer) {
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'childList' || mutation.type === 'subtree') {
                        processSlugCells();
                    }
                });
            });

            observer.observe(tableContainer, {
                childList: true,
                subtree: true,
                characterData: true
            });
        }
    }

    /**
     * Adiciona animações suaves para formulários
     */
    function initFormAnimations() {
        // Animação para campos de input ao focar
        const inputs = document.querySelectorAll('.mpa-inline-input, .mpa-form-input');

        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.style.transform = 'scale(1.02)';
                this.style.transition = 'all 0.2s ease';
            });

            input.addEventListener('blur', function() {
                this.style.transform = 'scale(1)';
            });
        });

        // Animação para cards ao hover
        const cards = document.querySelectorAll('.mpa-export-card, .mpa-import-card');

        cards.forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px)';
                this.style.boxShadow = '0 4px 12px rgba(0, 0, 0, 0.15)';
                this.style.transition = 'all 0.2s ease';
            });

            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = 'none';
            });
        });
    }

    /**
     * Melhora a UX do upload de arquivos
     */
    function initFileUpload() {
        const fileUpload = document.querySelector('.mpa-file-upload');
        const fileInput = document.querySelector('#mpa_import_file');
        const uploadText = document.querySelector('.mpa-file-upload-text');

        if (!fileUpload || !fileInput || !uploadText) return;

        // Drag and drop
        fileUpload.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('dragover');
            this.style.borderColor = '#3b82f6';
            this.style.background = '#eff6ff';
        });

        fileUpload.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
            this.style.borderColor = '#cbd5e1';
            this.style.background = '#f8fafc';
        });

        fileUpload.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
            this.style.borderColor = '#cbd5e1';
            this.style.background = '#f8fafc';

            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                updateFileUploadText(files[0].name);
            }
        });

        // Change event para mostrar nome do arquivo selecionado
        fileInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                updateFileUploadText(this.files[0].name);
            }
        });

        function updateFileUploadText(fileName) {
            uploadText.innerHTML = `
                <span class="dashicons dashicons-yes-alt" style="color: #10b981;"></span>
                Arquivo selecionado: ${fileName}
            `;
            fileUpload.style.borderColor = '#10b981';
            fileUpload.style.background = '#f0fdf4';
        }
    }

    /**
     * Personaliza confirmações para ações perigosas
     */
    function initCustomConfirmations() {
        // Botão de reset
        const resetButton = document.getElementById('mpa-reset-config');

        if (resetButton) {
            resetButton.addEventListener('click', function(e) {
                e.preventDefault();

                const role = this.getAttribute('data-role');
                const roleName = role === '_global' ? 'Global' : role;

                if (confirm(`⚠️ ATENÇÃO!\n\nVocê está prestes a DELETAR PERMANENTEMENTE todas as configurações de menu para o perfil "${roleName}".\n\nEsta ação NÃO PODE ser desfeita!\n\nTem certeza que deseja continuar?`)) {
                    if (confirm('💀 ÚLTIMA CONFIRMAÇÃO!\n\nEsta é sua última chance de cancelar. Todas as configurações serão perdidas permanentemente.\n\nConfirma a exclusão definitiva?')) {
                        // Adiciona estado de loading
                        this.innerHTML = '<span class="dashicons dashicons-update"></span> Resetando...';
                        this.disabled = true;
                        this.classList.add('mpa-loading');

                        // Envia requisição AJAX
                        const formData = new FormData();
                        formData.append('action', 'mpa_reset_config');
                        formData.append('role', role);
                        formData.append('_ajax_nonce', mpaNonce);

                        fetch(ajaxurl, {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                window.location.reload();
                            } else {
                                alert('Erro ao resetar configurações: ' + (data.data || 'Erro desconhecido'));
                                resetButton.innerHTML = '<span class="dashicons dashicons-trash"></span> Resetar Todas as Configurações';
                                resetButton.disabled = false;
                                resetButton.classList.remove('mpa-loading');
                            }
                        })
                        .catch(error => {
                            console.error('Erro:', error);
                            alert('Erro na comunicação com o servidor.');
                            resetButton.innerHTML = '<span class="dashicons dashicons-trash"></span> Resetar Todas as Configurações';
                            resetButton.disabled = false;
                            resetButton.classList.remove('mpa-loading');
                        });
                    }
                }
            });
        }
    }

    /**
     * Adiciona estados de loading para formulários
     */
    function initLoadingStates() {
        const forms = document.querySelectorAll('form');

        forms.forEach(form => {
            form.addEventListener('submit', function() {
                const submitButton = this.querySelector('button[type="submit"], input[type="submit"]');

                if (submitButton && !submitButton.hasAttribute('onclick')) {
                    const originalText = submitButton.innerHTML;
                    submitButton.innerHTML = '<span class="dashicons dashicons-update"></span> Processando...';
                    submitButton.disabled = true;
                    submitButton.classList.add('mpa-loading');

                    // Timeout para reverter se não houver redirecionamento
                    setTimeout(() => {
                        if (submitButton) {
                            submitButton.innerHTML = originalText;
                            submitButton.disabled = false;
                            submitButton.classList.remove('mpa-loading');
                        }
                    }, 10000);
                }
            });
        });
    }

    // ===== MELHORAMENTOS VISUAIS =====

    /**
     * Adiciona animação de entrada para seções
     */
    function initSectionAnimations() {
        const sections = document.querySelectorAll('.mpa-section');

        sections.forEach((section, index) => {
            section.style.opacity = '0';
            section.style.transform = 'translateY(20px)';
            section.style.transition = 'all 0.4s ease';

            setTimeout(() => {
                section.style.opacity = '1';
                section.style.transform = 'translateY(0)';
            }, index * 100);
        });
    }

    /**
     * Destaca campos com erro
     */
    function highlightErrors() {
        const urlParams = new URLSearchParams(window.location.search);

        if (urlParams.get('import_error')) {
            const importSection = document.querySelector('.mpa-import-card');
            if (importSection) {
                importSection.style.borderColor = '#ef4444';
                importSection.style.background = '#fef2f2';

                setTimeout(() => {
                    importSection.style.borderColor = '';
                    importSection.style.background = '';
                }, 3000);
            }
        }
    }

    // Executar melhoramentos visuais
    setTimeout(() => {
        initSectionAnimations();
        highlightErrors();
    }, 100);

    // ===== KEYBOARD SHORTCUTS =====

    /**
     * Atalhos de teclado
     */
    document.addEventListener('keydown', function(e) {
        // Ctrl+S para salvar (previne comportamento padrão)
        if (e.ctrlKey && e.key === 's') {
            e.preventDefault();

            const visibleForm = document.querySelector('form:not([style*="display: none"])');
            if (visibleForm) {
                const submitButton = visibleForm.querySelector('button[type="submit"], input[type="submit"]');
                if (submitButton) {
                    submitButton.click();
                }
            }
        }

        // Escape para cancelar ações
        if (e.key === 'Escape') {
            const modals = document.querySelectorAll('.mpa-modal, .mpa-loading');
            modals.forEach(modal => {
                modal.style.display = 'none';
            });
        }
    });

    // ===== VALIDAÇÕES EM TEMPO REAL =====

    /**
     * Valida URLs em tempo real
     */
    function initUrlValidation() {
        const urlInputs = document.querySelectorAll('input[name*="url"]');

        urlInputs.forEach(input => {
            input.addEventListener('blur', function() {
                const url = this.value.trim();

                if (url && !isValidUrl(url)) {
                    this.style.borderColor = '#ef4444';
                    this.style.background = '#fef2f2';

                    // Adiciona mensagem de erro
                    let errorMsg = this.parentNode.querySelector('.mpa-error-msg');
                    if (!errorMsg) {
                        errorMsg = document.createElement('div');
                        errorMsg.className = 'mpa-error-msg';
                        errorMsg.style.color = '#ef4444';
                        errorMsg.style.fontSize = '12px';
                        errorMsg.style.marginTop = '4px';
                        this.parentNode.appendChild(errorMsg);
                    }
                    errorMsg.textContent = 'URL inválida. Use formato: https://exemplo.com ou admin.php?page=exemplo';
                } else {
                    this.style.borderColor = '';
                    this.style.background = '';

                    const errorMsg = this.parentNode.querySelector('.mpa-error-msg');
                    if (errorMsg) {
                        errorMsg.remove();
                    }
                }
            });
        });
    }

    /**
     * Valida se URL é válida
     */
    function isValidUrl(url) {
        // URL externa
        if (url.startsWith('http://') || url.startsWith('https://')) {
            try {
                new URL(url);
                return true;
            } catch {
                return false;
            }
        }

        // URL interna
        if (url.startsWith('admin.php?') || url.startsWith('/wp-admin/')) {
            return true;
        }

        return false;
    }

    // Inicializar validações
    initUrlValidation();

    console.log('🎨 MPA Menu Settings UI carregado com sucesso!');
});

// Variáveis globais (definidas pelo WordPress)
const ajaxurl = window.ajaxurl || '/wp-admin/admin-ajax.php';
const mpaNonce = window.mpa_nonce || '';