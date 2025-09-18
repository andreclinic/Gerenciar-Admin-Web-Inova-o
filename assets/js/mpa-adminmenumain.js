// Dynamic Sidebar Menu JavaScript
jQuery(function ($) {
    'use strict';

    // Global variable for drag and drop functionality
    let draggedItem = null;

    // Sidebar toggle functionality
    function initSidebarToggle() {
        const sidebar = $('#mpa-sidebar');
        const overlay = $('#mpa-sidebar-overlay');
        const mobileMenuBtn = $('.mpa-mobile-menu-btn');
        const sidebarToggle = $('#mpa-sidebar-toggle');

        // Desktop sidebar toggle
        sidebarToggle.on('click', function(e) {
            e.preventDefault();
            
            const isCollapsed = sidebar.hasClass('collapsed');
            
            if (isCollapsed) {
                // Expandir sidebar
                sidebar.removeClass('collapsed');
                $('body').removeClass('sidebar-collapsed');
                localStorage.setItem('mpa-sidebar-collapsed', 'false');
            } else {
                // Recolher sidebar
                sidebar.addClass('collapsed');
                $('body').addClass('sidebar-collapsed');
                localStorage.setItem('mpa-sidebar-collapsed', 'true');
            }
            
            // Visual feedback
            $(this).css('transform', 'scale(0.95)');
            setTimeout(() => {
                $(this).css('transform', 'scale(1)');
            }, 150);
        });

        // Mobile menu toggle
        mobileMenuBtn.on('click', function(e) {
            e.preventDefault();
            const isShowing = sidebar.hasClass('show');
            
            if (isShowing) {
                // Close mobile menu
                sidebar.removeClass('show');
                overlay.removeClass('show');
                $('body').removeClass('mobile-menu-open');
            } else {
                // Open mobile menu
                sidebar.addClass('show');
                overlay.addClass('show');
                $('body').addClass('mobile-menu-open');
            }
        });

        // Overlay click to close (mobile)
        overlay.on('click', function() {
            sidebar.removeClass('show');
            overlay.removeClass('show');
            $('body').removeClass('mobile-menu-open');
        });
        
        // Close mobile menu when clicking on menu items
        $('.mpa-nav-item, .mpa-submenu-item').on('click', function() {
            if ($(window).width() <= 768 && !$(this).closest('.mpa-nav-item-container').find('.mpa-submenu').length) {
                sidebar.removeClass('show');
                overlay.removeClass('show');
                $('body').removeClass('mobile-menu-open');
            }
        });
        
        // Restaurar estado da sidebar ao carregar
        const sidebarCollapsed = localStorage.getItem('mpa-sidebar-collapsed');
        if (sidebarCollapsed === 'true') {
            sidebar.addClass('collapsed');
            $('body').addClass('sidebar-collapsed');
        }
    }

    // Submenu toggle functionality
    function initSubmenuToggle() {
        $('.mpa-nav-item').on('click', function(e) {
            const $navItem = $(this);
            const $container = $navItem.closest('.mpa-nav-item-container');
            const $submenu = $container.find('.mpa-submenu');
            
            // Only handle items with submenus
            if ($submenu.length === 0) {
                return; // Allow normal navigation
            }

            e.preventDefault();
            
            // Toggle expanded state
            $container.toggleClass('expanded');
            $submenu.toggleClass('expanded');
            
            // Close other expanded submenus
            $('.mpa-nav-item-container').not($container).removeClass('expanded');
            $('.mpa-submenu').not($submenu).removeClass('expanded');
        });

        // Submenu item clicks should navigate normally
        $('.mpa-submenu-item').on('click', function() {
            // Allow normal navigation for submenu items
            return true;
        });
    }

    // Active menu state management
    function initActiveMenuState() {
        // Set active states based on current page
        const currentUrl = window.location.href;
        
        // Mark active menu items
        $('.mpa-nav-item').each(function() {
            const $navItem = $(this);
            const href = $navItem.attr('href');
            
            if (href && currentUrl.indexOf(href) !== -1) {
                $navItem.addClass('active');
                
                // If this item has a parent submenu, expand it
                const $container = $navItem.closest('.mpa-nav-item-container');
                if ($container.find('.mpa-submenu').length > 0) {
                    $container.addClass('expanded');
                    $container.find('.mpa-submenu').addClass('expanded');
                }
            }
        });

        // Mark active submenu items
        $('.mpa-submenu-item').each(function() {
            const $submenuItem = $(this);
            const href = $submenuItem.attr('href');
            
            if (href && currentUrl.indexOf(href) !== -1) {
                $submenuItem.addClass('active');
                
                // Expand parent submenu
                const $submenu = $submenuItem.closest('.mpa-submenu');
                const $container = $submenu.closest('.mpa-nav-item-container');
                $container.addClass('expanded');
                $submenu.addClass('expanded');
            }
        });
    }

    // Responsive behavior
    function initResponsiveBehavior() {
        $(window).on('resize', function() {
            if ($(window).width() > 768) {
                // Desktop: remove mobile classes but preserve collapsed state
                const sidebar = $('#mpa-sidebar');
                sidebar.removeClass('show');
                $('#mpa-sidebar-overlay').removeClass('show');
                
                // Show desktop toggle, hide mobile toggle
                $('#mpa-sidebar-toggle').show();
                $('#mpa-mobile-menu-btn').hide();
            } else {
                // Mobile: show mobile toggle, hide desktop toggle
                $('#mpa-sidebar-toggle').hide();
                $('#mpa-mobile-menu-btn').show();
                
                // On mobile, reset all desktop states
                const sidebar = $('#mpa-sidebar');
                sidebar.removeClass('collapsed show');
                $('#mpa-sidebar-overlay').removeClass('show');
                $('body').removeClass('sidebar-collapsed mobile-menu-open');
            }
        });
        
        // Initialize based on current screen size
        if ($(window).width() <= 768) {
            $('#mpa-sidebar-toggle').hide();
            $('#mpa-mobile-menu-btn').show();
        }
    }

    // Smooth scroll prevention for toggle links
    function preventToggleNavigation() {
        $('.mpa-nav-item').on('click', function(e) {
            const $container = $(this).closest('.mpa-nav-item-container');
            const hasSubmenu = $container.find('.mpa-submenu').length > 0;
            
            if (hasSubmenu) {
                // Don't navigate if it's a toggle action
                const isExpanding = !$container.hasClass('expanded');
                if (isExpanding) {
                    e.preventDefault();
                    return false;
                }
            }
        });
    }

    // Scroll preservation functionality
    function initScrollPreservation() {
        const sidebar = $('#mpa-sidebar');
        
        // Save scroll position before navigation
        $('.mpa-nav-item, .mpa-submenu-item').on('click', function(e) {
            // Don't save scroll for submenu toggle actions
            const isSubmenuToggle = $(this).hasClass('mpa-nav-item') && 
                                  $(this).closest('.mpa-nav-item-container').find('.mpa-submenu').length > 0;
            
            if (!isSubmenuToggle) {
                const scrollPosition = sidebar.scrollTop();
                localStorage.setItem('mpa-sidebar-scroll', scrollPosition);
            }
        });
        
        // Restore scroll position on page load
        const savedScroll = localStorage.getItem('mpa-sidebar-scroll');
        if (savedScroll && sidebar.length) {
            // Delay to ensure DOM is fully rendered
            setTimeout(function() {
                sidebar.scrollTop(parseInt(savedScroll));
            }, 100);
        }
        
        // Smooth scroll to active menu item if not manually scrolled
        const activeItem = sidebar.find('.mpa-nav-item.active, .mpa-submenu-item.active').first();
        if (activeItem.length && !savedScroll) {
            setTimeout(function() {
                const itemPosition = activeItem.position().top;
                const sidebarHeight = sidebar.height();
                const scrollTo = Math.max(0, itemPosition - (sidebarHeight / 2));
                
                sidebar.animate({
                    scrollTop: scrollTo
                }, 300);
            }, 200);
        }
    }

    // Fix for broken URLs in dynamic menu
    function fixBrokenUrls() {
        $('.mpa-nav-item, .mpa-submenu-item').each(function() {
            const $link = $(this);
            let href = $link.attr('href');
            
            if (href) {
                // Fix duplicated admin path patterns
                if (href.includes('/wp-admin/https:/')) {
                    // Extract the correct part after the duplication
                    const parts = href.split('/wp-admin/admin.php');
                    if (parts.length > 1) {
                        href = '/wp-admin/admin.php' + parts[parts.length - 1];
                    }
                }
                
                // Specific fix for jet-smart-filters
                if (href.includes('jet-smart-filters') && !href.includes('#/')) {
                    href = href + '#/';
                }
                
                // Update the link if it was changed
                if (href !== $link.attr('href')) {
                    $link.attr('href', href);
                    console.log('Fixed URL:', $link.attr('href'), '->', href);
                }
            }
        });
    }

    // Clean menu titles
    function cleanMenuTitles() {
        // Fix Comments menu title
        $('.mpa-nav-item[data-page="edit-comments.php"] .mpa-nav-content h3').each(function() {
            const $title = $(this);
            const text = $title.text();

            // Remove pending comments count from title
            if (text.includes('Comentários') && text.includes('comentário')) {
                $title.text('Comentários');
            }
        });

        // Fix other menu titles that might have counts or extra text
        $('.mpa-nav-content h3').each(function() {
            const $title = $(this);
            let text = $title.text().trim();

            // Remove any trailing numbers or counts in parentheses
            text = text.replace(/\s+\d+.*$/, '');
            text = text.replace(/\s*\([^)]*\)$/, '');

            if (text !== $title.text().trim()) {
                $title.text(text);
            }
        });
    }

    // Drag and drop functionality for sidebar menu
    function initSidebarDragDrop() {

        // Add draggable attribute to nav items
        $('.mpa-nav-item-container').each(function() {
            const $container = $(this);
            const $navItem = $container.find('.mpa-nav-item');

            // Make nav items draggable
            $navItem.attr('draggable', 'true');
            $container.addClass('mpa-draggable-item');

            // Add data attribute for easier identification
            const menuData = $navItem.attr('data-menu') || $navItem.attr('href');
            if (menuData) {
                $container.attr('data-menu-slug', menuData);
            }
        });

        // Drag event handlers for nav items
        $('.mpa-nav-item').on('dragstart', function(e) {
            draggedItem = $(this).closest('.mpa-nav-item-container')[0];
            $(draggedItem).addClass('dragging');

            e.originalEvent.effectAllowed = 'move';
            e.originalEvent.dataTransfer.setData('text/html', draggedItem.outerHTML);
        });

        $('.mpa-nav-item').on('dragend', function(e) {
            if (draggedItem) {
                $(draggedItem).removeClass('dragging');
                $('.mpa-nav-item-container').removeClass('drag-over submenu-drop-zone');
                $('.mpa-submenu').removeClass('submenu-drop-zone');
            }
            draggedItem = null;
        });

        // Drop zone handlers for nav items (for reordering)
        $('.mpa-nav-item-container').on('dragover', function(e) {
            e.preventDefault();
            e.originalEvent.dataTransfer.dropEffect = 'move';

            if (this !== draggedItem) {
                const rect = this.getBoundingClientRect();
                const mouseY = e.originalEvent.clientY;
                const centerY = rect.top + rect.height / 2;
                const isSubmenuZone = Math.abs(mouseY - centerY) < rect.height * 0.3;

                if (isSubmenuZone) {
                    $(this).addClass('submenu-drop-zone');
                    $(this).removeClass('drag-over');
                } else {
                    $(this).addClass('drag-over');
                    $(this).removeClass('submenu-drop-zone');
                }
            }
        });

        $('.mpa-nav-item-container').on('dragenter', function(e) {
            e.preventDefault();
        });

        $('.mpa-nav-item-container').on('dragleave', function(e) {
            $(this).removeClass('drag-over submenu-drop-zone');
        });

        $('.mpa-nav-item-container').on('drop', function(e) {
            e.preventDefault();
            $(this).removeClass('drag-over submenu-drop-zone');

            // Don't handle submenu items here - let the main drop zone handle them
            if (draggedItem && $(draggedItem).hasClass('mpa-submenu-item')) {
                return;
            }

            if (this !== draggedItem && draggedItem) {
                const rect = this.getBoundingClientRect();
                const mouseY = e.originalEvent.clientY;
                const centerY = rect.top + rect.height / 2;

                // Check if dropping in the center area (submenu zone)
                const isSubmenuZone = Math.abs(mouseY - centerY) < rect.height * 0.3;

                if (isSubmenuZone) {
                    // Transform dragged item into submenu of this item
                    e.stopPropagation();

                    const $draggedContainer = $(draggedItem);
                    const $parentContainer = $(this);

                    const draggedMenuSlug = $draggedContainer.attr('data-menu-slug');
                    const parentMenuSlug = $parentContainer.attr('data-menu-slug');
                    const draggedMenuTitle = $draggedContainer.find('.mpa-nav-title, h3').text().trim();

                    // Check if trying to make a menu submenu of itself
                    if (draggedMenuSlug === parentMenuSlug) {
                        showSidebarNotification('Um menu não pode ser submenu dele mesmo!', 'error');
                        return;
                    }

                    // Transform menu to submenu
                    transformSidebarMenuToSubmenu($draggedContainer, $parentContainer, draggedMenuSlug, parentMenuSlug, draggedMenuTitle);
                    return;
                }
                const itemMiddle = rect.top + rect.height / 2;

                if (mouseY < itemMiddle) {
                    // Insert before
                    $(draggedItem).insertBefore($(this));
                } else {
                    // Insert after
                    $(draggedItem).insertAfter($(this));
                }

                // Save new order (if needed)
                // saveSidebarMenuOrder();
            }
        });

        // Drop zone handlers for submenus (for transformation)
        $('.mpa-submenu').on('dragover', function(e) {
            // Only allow if submenu is expanded
            if (!$(this).hasClass('expanded')) return;

            e.preventDefault();
            e.stopPropagation();
            e.originalEvent.dataTransfer.dropEffect = 'move';

            $(this).addClass('submenu-drop-zone');
        });

        $('.mpa-submenu').on('dragenter', function(e) {
            if (!$(this).hasClass('expanded')) return;

            e.preventDefault();
            e.stopPropagation();
        });

        $('.mpa-submenu').on('dragleave', function(e) {
            const rect = this.getBoundingClientRect();
            const x = e.originalEvent.clientX;
            const y = e.originalEvent.clientY;

            if (x < rect.left || x > rect.right || y < rect.top || y > rect.bottom) {
                $(this).removeClass('submenu-drop-zone');
            }
        });

        $('.mpa-submenu').on('drop', function(e) {
            if (!$(this).hasClass('expanded')) return;

            e.preventDefault();
            e.stopPropagation();

            $(this).removeClass('submenu-drop-zone');

            if (draggedItem) {
                const $parentContainer = $(this).closest('.mpa-nav-item-container');
                const $draggedContainer = $(draggedItem);

                const draggedMenuSlug = $draggedContainer.attr('data-menu-slug');
                const parentMenuSlug = $parentContainer.attr('data-menu-slug');
                const draggedMenuTitle = $draggedContainer.find('.mpa-nav-content h3').text().trim();

                // Check if trying to make a menu submenu of itself
                if (draggedMenuSlug === parentMenuSlug) {
                    showSidebarNotification('Um menu não pode ser submenu dele mesmo!', 'error');
                    return;
                }

                // Transform menu to submenu in sidebar
                transformSidebarMenuToSubmenu($draggedContainer, $parentContainer, draggedMenuSlug, parentMenuSlug, draggedMenuTitle);
            }
        });

        // Drop zone for main menu area to accept submenu items (for reverting)
        $('.mpa-menu-list').on('dragover', function(e) {
            // Only allow if a submenu item is being dragged
            if (draggedItem && $(draggedItem).hasClass('mpa-submenu-item')) {
                e.preventDefault();
                e.stopPropagation();
                e.originalEvent.dataTransfer.dropEffect = 'move';
                $(this).addClass('main-menu-drop-zone');
                console.log('Dragover on main nav with submenu item');
            } else if (draggedItem) {
                console.log('Dragover on main nav but not submenu item:', draggedItem, 'has class:', $(draggedItem).hasClass('mpa-submenu-item'));
            }
        });

        $('.mpa-menu-list').on('dragenter', function(e) {
            if (draggedItem && $(draggedItem).hasClass('mpa-submenu-item')) {
                e.preventDefault();
                e.stopPropagation();
            }
        });

        $('.mpa-menu-list').on('dragleave', function(e) {
            // Check if really leaving the main nav area
            const rect = this.getBoundingClientRect();
            const x = e.originalEvent.clientX;
            const y = e.originalEvent.clientY;

            if (x < rect.left || x > rect.right || y < rect.top || y > rect.bottom) {
                $(this).removeClass('main-menu-drop-zone');
            }
        });

        $('.mpa-menu-list').on('drop', function(e) {
            if (draggedItem && $(draggedItem).hasClass('mpa-submenu-item')) {
                e.preventDefault();
                e.stopPropagation();
                $(this).removeClass('main-menu-drop-zone');

                console.log('Transforming submenu to menu:', $(draggedItem));

                // Transform submenu back to main menu
                transformSidebarSubmenuToMenu($(draggedItem));
            }
        });

        // Initialize drag events for existing submenu items
        $('.mpa-submenu-item').each(function() {
            addSubmenuDragEvents($(this));
        });

        // Add CSS for drag states
        if (!$('#mpa-sidebar-drag-styles').length) {
            $('head').append(`
                <style id="mpa-sidebar-drag-styles">
                    .mpa-nav-item-container.dragging {
                        opacity: 0.5;
                        transform: scale(1.02);
                        z-index: 1000;
                        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                    }

                    .mpa-nav-item-container.drag-over {
                        border-left: 3px solid #0073aa;
                        background: rgba(0, 115, 170, 0.05);
                    }

                    .mpa-nav-item-container.submenu-drop-zone {
                        background: linear-gradient(90deg, #fff3cd 0%, #ffeeba 100%);
                        border: 2px dashed #856404;
                        border-radius: 4px;
                        animation: submenuDropPulse 1.5s infinite;
                        position: relative;
                    }

                    .mpa-nav-item-container.submenu-drop-zone::after {
                        content: "↳ Solte aqui para criar submenu";
                        display: block;
                        position: absolute;
                        top: 50%;
                        left: 50%;
                        transform: translate(-50%, -50%);
                        background: rgba(133, 100, 4, 0.9);
                        color: white;
                        padding: 4px 8px;
                        border-radius: 4px;
                        font-size: 12px;
                        white-space: nowrap;
                        z-index: 1000;
                    }

                    @keyframes submenuDropPulse {
                        0%, 100% {
                            border-color: #856404;
                            background: linear-gradient(90deg, #fff3cd 0%, #ffeeba 100%);
                        }
                        50% {
                            border-color: #533f03;
                            background: linear-gradient(90deg, #ffeaa7 0%, #fdcb6e 100%);
                        }
                    }

                    .mpa-submenu.submenu-drop-zone {
                        background: linear-gradient(90deg, #e8f4fd 0%, #cfe8fc 100%);
                        border: 2px dashed #0073aa;
                        border-radius: 4px;
                        animation: sidebarSubmenuDropPulse 1.5s infinite;
                        position: relative;
                    }

                    @keyframes sidebarSubmenuDropPulse {
                        0%, 100% {
                            border-color: #0073aa;
                            background: linear-gradient(90deg, #e8f4fd 0%, #cfe8fc 100%);
                        }
                        50% {
                            border-color: #005a87;
                            background: linear-gradient(90deg, #d0e7fc 0%, #a8d0fa 100%);
                        }
                    }

                    .mpa-submenu.submenu-drop-zone::after {
                        content: "↳ Solte aqui para tornar submenu";
                        display: block;
                        text-align: center;
                        padding: 8px;
                        color: #0073aa;
                        font-weight: 600;
                        font-size: 12px;
                        background: rgba(255, 255, 255, 0.95);
                        border-radius: 3px;
                        margin: 4px;
                        border: 1px solid rgba(0, 115, 170, 0.3);
                        position: absolute;
                        top: 50%;
                        left: 50%;
                        transform: translate(-50%, -50%);
                        min-width: 200px;
                        z-index: 10;
                    }

                    .mpa-nav-main.main-menu-drop-zone {
                        background: linear-gradient(135deg, #f0f8ff 0%, #e6f3ff 100%);
                        border: 2px dashed #0073aa;
                        border-radius: 8px;
                        animation: mainMenuDropPulse 1.5s infinite;
                        padding: 10px;
                        position: relative;
                    }

                    @keyframes mainMenuDropPulse {
                        0%, 100% {
                            border-color: #0073aa;
                            background: linear-gradient(135deg, #f0f8ff 0%, #e6f3ff 100%);
                        }
                        50% {
                            border-color: #005a87;
                            background: linear-gradient(135deg, #e1f0ff 0%, #cce6ff 100%);
                        }
                    }

                    .mpa-nav-main.main-menu-drop-zone::before {
                        content: "⬆ Solte aqui para voltar ao menu principal";
                        display: block;
                        text-align: center;
                        padding: 15px;
                        color: #0073aa;
                        font-weight: 700;
                        font-size: 14px;
                        background: rgba(255, 255, 255, 0.95);
                        border-radius: 6px;
                        margin: 10px;
                        border: 1px solid rgba(0, 115, 170, 0.3);
                        position: absolute;
                        top: 50%;
                        left: 50%;
                        transform: translate(-50%, -50%);
                        min-width: 250px;
                        z-index: 100;
                        box-shadow: 0 2px 8px rgba(0, 115, 170, 0.1);
                    }

                    .mpa-submenu-item.dragging {
                        opacity: 0.7;
                        transform: scale(1.05);
                        z-index: 1000;
                    }
                </style>
            `);
        }
    }

    // Transform menu to submenu in sidebar
    function transformSidebarMenuToSubmenu($draggedContainer, $parentContainer, draggedMenuSlug, parentMenuSlug, draggedMenuTitle) {
        // Remove the dragged menu from sidebar
        $draggedContainer.fadeOut(300, function() {
            $(this).remove();
        });

        // Create new submenu item with drag functionality
        const $submenu = $parentContainer.find('.mpa-submenu');
        const newSubmenuItem = `
            <a href="${$draggedContainer.find('.mpa-nav-item').attr('href')}"
               class="mpa-submenu-item"
               draggable="true"
               data-menu-slug="${draggedMenuSlug}"
               data-parent-slug="${parentMenuSlug}">
                <span>↳ ${draggedMenuTitle}</span>
            </a>
        `;

        let $newSubmenuElement;
        if ($submenu.length > 0) {
            // Add to existing submenu
            $submenu.append(newSubmenuItem);
            $newSubmenuElement = $submenu.children().last();
        } else {
            // Create new submenu structure
            const submenuHtml = `<div class="mpa-submenu expanded">${newSubmenuItem}</div>`;
            $parentContainer.append(submenuHtml);
            $parentContainer.addClass('expanded');
            $newSubmenuElement = $parentContainer.find('.mpa-submenu-item').last();
        }

        // Add drag events to the new submenu item
        addSubmenuDragEvents($newSubmenuElement);

        // Send transformation to backend
        console.log('🚀 Enviando transformação para o backend:', {draggedMenuSlug, parentMenuSlug, draggedMenuTitle});
        saveMenuToSubmenuTransformation(draggedMenuSlug, parentMenuSlug, draggedMenuTitle);

        showSidebarNotification(`Menu "${draggedMenuTitle}" transformado em submenu com sucesso! Recarregando...`, 'success');
    }

    // Show notification in sidebar context
    function showSidebarNotification(message, type = 'info') {
        const notification = $(`
            <div class="mpa-sidebar-notification ${type}" style="
                position: fixed;
                top: 80px;
                right: 20px;
                background: ${type === 'success' ? '#00a32a' : type === 'error' ? '#d63638' : '#0073aa'};
                color: white;
                padding: 12px 20px;
                border-radius: 4px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.2);
                z-index: 9999;
                font-size: 14px;
                max-width: 300px;
                opacity: 0;
                transform: translateX(20px);
                transition: all 0.3s ease;
            ">
                ${message}
            </div>
        `);

        $('body').append(notification);

        // Animate in
        setTimeout(() => {
            notification.css({
                opacity: 1,
                transform: 'translateX(0)'
            });
        }, 100);

        // Remove after delay
        setTimeout(() => {
            notification.css({
                opacity: 0,
                transform: 'translateX(20px)'
            });
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }

    // Save menu to submenu transformation (reuse from main menu manager)
    function saveMenuToSubmenuTransformation(menuSlug, parentSlug, menuTitle) {
        console.log('saveMenuToSubmenuTransformation chamada:', {menuSlug, parentSlug, menuTitle});

        const formData = new FormData();
        formData.append('action', 'mpa_transform_menu_to_submenu');
        formData.append('menu_slug', menuSlug);
        formData.append('parent_slug', parentSlug);
        formData.append('menu_title', menuTitle);
        formData.append('nonce', mpaDragDropVars ? mpaDragDropVars.nonce : '');

        console.log('FormData preparado:', Array.from(formData.entries()));
        console.log('URL AJAX:', mpaDragDropVars ? mpaDragDropVars.ajax_url : '/wp-admin/admin-ajax.php');

        fetch(mpaDragDropVars ? mpaDragDropVars.ajax_url : '/wp-admin/admin-ajax.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('Resposta recebida:', response.status, response.statusText);
            return response.json();
        })
        .then(data => {
            console.log('Dados da resposta:', data);

            if (data.success) {
                console.log('✅ Transformação salva com sucesso!', data.data);

                // Recarregar página após sucesso no backend
                console.log('🔄 Iniciando reload em 1.5 segundos...');
                setTimeout(() => {
                    console.log('🔄 Recarregando página agora...');
                    window.location.reload();
                }, 1500);
            } else {
                console.error('❌ Erro ao salvar transformação:', data.data);
                showSidebarNotification('Erro ao salvar transformação no backend.', 'error');
            }
        })
        .catch(error => {
            console.error('Erro na requisição:', error);
            showSidebarNotification('Erro ao comunicar com o servidor.', 'error');
        });
    }

    // Add drag events to submenu items (for reverting back to main menu)
    function addSubmenuDragEvents($submenuElement) {
        $submenuElement.on('dragstart', function(e) {
            draggedItem = this;
            $(this).addClass('dragging');
            e.originalEvent.dataTransfer.effectAllowed = 'move';
            console.log('Submenu drag started:', this, 'has mpa-submenu-item class:', $(this).hasClass('mpa-submenu-item'));
        });

        $submenuElement.on('dragend', function(e) {
            $(this).removeClass('dragging');
            // Clean up any drop zone indicators
            $('.mpa-menu-list').removeClass('main-menu-drop-zone');
        });
    }

    // Transform submenu back to main menu in sidebar
    function transformSidebarSubmenuToMenu($submenuItem) {
        console.log('transformSidebarSubmenuToMenu called with:', $submenuItem);

        const menuSlug = $submenuItem.attr('data-menu-slug');
        const parentSlug = $submenuItem.attr('data-parent-slug');
        const menuTitle = $submenuItem.find('span').text().replace('↳ ', '').trim();
        const menuUrl = $submenuItem.attr('href');

        console.log('Menu data:', { menuSlug, parentSlug, menuTitle, menuUrl });

        // Remove the submenu item
        $submenuItem.remove();

        // Check if parent submenu is now empty and clean up if needed
        const $parentContainer = $(`.mpa-nav-item-container[data-menu-slug="${parentSlug}"]`);
        const $submenu = $parentContainer.find('.mpa-submenu');
        if ($submenu.children().length === 0) {
            $submenu.remove();
            $parentContainer.removeClass('expanded');
        }

        // Create new main menu item
        const newMainMenuItem = `
            <div class="mpa-nav-item-container" data-menu-slug="${menuSlug}">
                <a href="${menuUrl}" class="mpa-nav-item">
                    <div class="mpa-nav-content">
                        <div class="mpa-nav-icon">
                            <i class="dashicons dashicons-admin-generic"></i>
                        </div>
                        <h3>${menuTitle}</h3>
                    </div>
                </a>
            </div>
        `;

        // Add the new menu to the main nav (at the end)
        const $newElement = $(newMainMenuItem);
        $('.mpa-menu-list').append($newElement);

        // Add drag events to the new main menu item
        $newElement.find('.mpa-nav-item').on('dragstart', function(e) {
            draggedItem = this.parentElement;
            $(draggedItem).addClass('dragging');
            e.originalEvent.effectAllowed = 'move';
        });

        $newElement.find('.mpa-nav-item').on('dragend', function(e) {
            if (draggedItem) {
                $(draggedItem).removeClass('dragging');
                $('.mpa-nav-item-container').removeClass('drag-over submenu-drop-zone');
                $('.mpa-submenu').removeClass('submenu-drop-zone');
            }
            draggedItem = null;
        });

        // Add drop zone events
        $newElement.on('dragover', function(e) {
            e.preventDefault();
            e.originalEvent.dataTransfer.dropEffect = 'move';
            if (this !== draggedItem) {
                $(this).addClass('drag-over');
            }
        });

        $newElement.on('dragenter', function(e) {
            e.preventDefault();
        });

        $newElement.on('dragleave', function(e) {
            $(this).removeClass('drag-over submenu-drop-zone');
        });

        $newElement.on('drop', function(e) {
            e.preventDefault();
            $(this).removeClass('drag-over submenu-drop-zone');
            // Handle reordering logic if needed
        });

        // Send transformation to backend
        saveSubmenuToMenuTransformation(menuSlug, parentSlug, menuTitle);

        showSidebarNotification(`Submenu "${menuTitle}" transformado em menu principal com sucesso!`, 'success');
    }

    // Save submenu to menu transformation

    function saveSubmenuToMenuTransformation(menuSlug, parentSlug, menuTitle) {
        const formData = new FormData();
        formData.append('action', 'mpa_transform_submenu_to_menu');
        formData.append('menu_slug', menuSlug);
        formData.append('parent_slug', parentSlug);
        formData.append('menu_title', menuTitle);
        formData.append('nonce', mpaDragDropVars ? mpaDragDropVars.nonce : '');

        fetch(mpaDragDropVars ? mpaDragDropVars.ajax_url : '/wp-admin/admin-ajax.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('Submenu revertido com sucesso!', data.data);

                showSidebarNotification('Submenu transformado em menu principal com sucesso! Recarregando...', 'success');

                // Recarregar página após sucesso para garantir sincronização
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                console.error('Erro ao reverter submenu:', data.data);
                showSidebarNotification('Erro ao reverter submenu no backend.', 'error');
            }
        })
        .catch(error => {
            console.error('Erro na requisição:', error);
            showSidebarNotification('Erro ao comunicar com o servidor.', 'error');
        });
    }

    // Initialize all functionality
    $(document).ready(function() {
        initSidebarToggle();
        initSubmenuToggle();
        initActiveMenuState();
        initResponsiveBehavior();
        preventToggleNavigation();
        initScrollPreservation();
        fixBrokenUrls();
        cleanMenuTitles();
        initSidebarDragDrop(); // Initialize drag and drop functionality

        console.log('MPA Admin Menu initialized with drag-and-drop');
    });
});
