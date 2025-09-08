// Dynamic Sidebar Menu JavaScript
jQuery(function ($) {
    'use strict';

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
        
        console.log('MPA Admin Menu initialized');
    });
});
