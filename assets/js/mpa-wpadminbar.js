// JavaScript para Header baseado no modelo_dashboard.html
document.addEventListener('DOMContentLoaded', function() {
    // Dark Mode Toggle
    const darkModeToggle = document.getElementById('mpa-dark-mode-toggle');
    const body = document.body;

    if (darkModeToggle) {
        // Verificar preferência salva
        const savedTheme = localStorage.getItem('mpa-theme');
        if (savedTheme === 'dark') {
            body.classList.add('mpa-dark-mode');
        }

        darkModeToggle.addEventListener('click', function() {
            // Toggle dark mode class
            body.classList.toggle('mpa-dark-mode');

            // Salvar preferência no localStorage
            if (body.classList.contains('mpa-dark-mode')) {
                localStorage.setItem('mpa-theme', 'dark');
            } else {
                localStorage.setItem('mpa-theme', 'light');
            }

            // Feedback visual
            darkModeToggle.style.transform = 'scale(0.95)';
            setTimeout(() => {
                darkModeToggle.style.transform = 'scale(1)';
            }, 150);
        });
    }

    // Fullscreen Toggle
    const fullscreenToggle = document.getElementById('mpa-fullscreen-toggle');
    if (fullscreenToggle) {
        const selectorTargets = ['#wpwrap', '#wpcontent', '#wpbody-content', '.mpa-dashboard-wrapper', '.mpa-content'];
        const dynamicTargets = selectorTargets
            .map(selector => document.querySelector(selector))
            .filter(Boolean);

        const fullscreenTargets = [document.documentElement, document.body, ...dynamicTargets]
            .filter((element, index, array) => element && array.indexOf(element) === index);

        const apiMap = [
            {
                request: 'requestFullscreen',
                exit: 'exitFullscreen',
                element: 'fullscreenElement',
                enabled: 'fullscreenEnabled',
                change: 'fullscreenchange',
                error: 'fullscreenerror'
            },
            {
                request: 'webkitRequestFullscreen',
                exit: 'webkitExitFullscreen',
                element: 'webkitFullscreenElement',
                enabled: 'webkitFullscreenEnabled',
                change: 'webkitfullscreenchange',
                error: 'webkitfullscreenerror'
            },
            {
                request: 'webkitRequestFullScreen',
                exit: 'webkitCancelFullScreen',
                element: 'webkitCurrentFullScreenElement',
                enabled: 'webkitFullscreenEnabled',
                change: 'webkitfullscreenchange',
                error: 'webkitfullscreenerror'
            },
            {
                request: 'mozRequestFullScreen',
                exit: 'mozCancelFullScreen',
                element: 'mozFullScreenElement',
                enabled: 'mozFullScreenEnabled',
                change: 'mozfullscreenchange',
                error: 'mozfullscreenerror'
            },
            {
                request: 'msRequestFullscreen',
                exit: 'msExitFullscreen',
                element: 'msFullscreenElement',
                enabled: 'msFullscreenEnabled',
                change: 'MSFullscreenChange',
                error: 'MSFullscreenError'
            }
        ];

        const protoTargets = [
            typeof Element !== 'undefined' ? Element.prototype : null,
            typeof HTMLElement !== 'undefined' ? HTMLElement.prototype : null,
            typeof Document !== 'undefined' ? Document.prototype : null
        ].filter(Boolean);

        const detectionTargets = [...fullscreenTargets, ...protoTargets];

        const fullscreenApi = (() => {
            for (let i = 0; i < apiMap.length; i += 1) {
                const api = apiMap[i];
                const hasRequest = detectionTargets.some(target => target && api.request in target);
                if (hasRequest) {
                    return api;
                }
            }
            return null;
        })();

        const getActiveElement = () => {
            if (!fullscreenApi) {
                return null;
            }
            const prop = fullscreenApi.element;
            return prop && prop in document ? document[prop] : null;
        };

        const isFullscreenSupported = () => {
            if (!fullscreenApi) {
                return false;
            }

            const enabledProp = fullscreenApi.enabled;
            if (enabledProp && enabledProp in document) {
                const enabled = document[enabledProp];
                if (enabled === false) {
                    return false;
                }
            }

            const requestMethod = fullscreenApi.request;
            return detectionTargets.some(target => target && requestMethod in target);
        };

        const hasRequestMethod = element => {
            if (!element || !fullscreenApi) {
                return false;
            }
            const method = fullscreenApi.request;
            return typeof element[method] === 'function';
        };
        const callRequest = element => {
            const method = fullscreenApi.request;
            if (method === 'webkitRequestFullScreen' || method === 'webkitRequestFullscreen') {
                const keyboardFlag = typeof Element !== 'undefined' && 'ALLOW_KEYBOARD_INPUT' in Element
                    ? Element.ALLOW_KEYBOARD_INPUT
                    : undefined;
                return keyboardFlag !== undefined ? element[method](keyboardFlag) : element[method]();
            }
            return element[method]();
        };

        const openFullscreen = () => {
            if (!fullscreenApi) {
                return Promise.reject(new Error('Fullscreen API não suportada.'));
            }

            const availableTargets = fullscreenTargets.filter(hasRequestMethod);
            if (!availableTargets.length) {
                return Promise.reject(new Error('Nenhum elemento compatível encontrado para o modo tela cheia.'));
            }

            let lastError = null;

            const attempt = index => {
                if (index >= availableTargets.length) {
                    return Promise.reject(lastError || new Error('Não foi possível entrar em tela cheia.'));
                }

                const target = availableTargets[index];

                try {
                    const result = callRequest(target);

                    if (result && typeof result.then === 'function') {
                        return result.catch(error => {
                            lastError = error;
                            return attempt(index + 1);
                        });
                    }

                    return new Promise((resolve, reject) => {
                        setTimeout(() => {
                            if (getActiveElement()) {
                                resolve();
                            } else {
                                attempt(index + 1).then(resolve).catch(reject);
                            }
                        }, 50);
                    });
                } catch (error) {
                    lastError = error;
                    return attempt(index + 1);
                }
            };

            return attempt(0);
        };

        const exitFullscreen = () => {
            if (!fullscreenApi) {
                return Promise.reject(new Error('Fullscreen API não suportada.'));
            }

            const activeElement = getActiveElement();
            if (!activeElement) {
                return Promise.resolve();
            }

            const exitMethod = fullscreenApi.exit;
            if (!(exitMethod in document) || typeof document[exitMethod] !== 'function') {
                return Promise.reject(new Error('Método de saída da tela cheia indisponível.'));
            }

            try {
                const response = document[exitMethod]();
                return response && typeof response.then === 'function' ? response : Promise.resolve(response);
            } catch (error) {
                return Promise.reject(error);
            }
        };

        const ua = navigator.userAgent || '';
        const isIOS = /iPad|iPhone|iPod/i.test(ua);
        const supportsFullscreen = !isIOS && isFullscreenSupported();
        const simulateWithCSS = isIOS ? true : !supportsFullscreen;
        let isSimulatedFullscreen = false;

        const applySimulatedState = () => {
            if (!simulateWithCSS) {
                body.classList.remove('mpa-fullscreen-simulado');
                return;
            }

            if (isSimulatedFullscreen) {
                body.classList.add('mpa-fullscreen-simulado');
            } else {
                body.classList.remove('mpa-fullscreen-simulado');
            }
        };

        const updateFullscreenState = () => {
            const activeNative = supportsFullscreen ? !!getActiveElement() : false;
            const activeSimulated = simulateWithCSS ? isSimulatedFullscreen : false;
            const active = simulateWithCSS ? activeSimulated : activeNative;

            fullscreenToggle.classList.toggle('is-fullscreen', active);
            fullscreenToggle.setAttribute('aria-pressed', active ? 'true' : 'false');
            fullscreenToggle.setAttribute('aria-label', active ? 'Sair da tela cheia' : 'Abrir em tela cheia');
            body.classList.toggle('mpa-fullscreen-active', supportsFullscreen && activeNative);
            applySimulatedState();
        };

        if (!supportsFullscreen && !simulateWithCSS) {
            fullscreenToggle.classList.add('mpa-fullscreen-unsupported');
            fullscreenToggle.setAttribute('aria-pressed', 'false');
            fullscreenToggle.setAttribute('aria-label', 'Tela cheia indisponível');
            return;
        }

        const animateToggle = () => {
            fullscreenToggle.style.transform = 'scale(0.95)';
            setTimeout(() => {
                fullscreenToggle.style.transform = 'scale(1)';
            }, 150);
        };

        const handleToggle = () => {
            animateToggle();

            if (simulateWithCSS) {
                isSimulatedFullscreen = !isSimulatedFullscreen;
                updateFullscreenState();
                return;
            }

            if (supportsFullscreen) {
                const active = !!getActiveElement();
                const action = active ? exitFullscreen() : openFullscreen();

                action
                    .then(() => {
                        setTimeout(updateFullscreenState, 50);
                    })
                    .catch(error => {
                        console.warn('Tela cheia indisponível:', error);
                        updateFullscreenState();
                    });
            }
        };

        let lastTouchTime = 0;

        fullscreenToggle.addEventListener('click', function(event) {
            if (Date.now() - lastTouchTime < 400) {
                return;
            }

            event.preventDefault();
            handleToggle();
        });

        fullscreenToggle.addEventListener('keydown', function(event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                handleToggle();
            }
        });

        if ('ontouchend' in window) {
            fullscreenToggle.addEventListener('touchend', function(event) {
                lastTouchTime = Date.now();
                event.preventDefault();
                handleToggle();
            }, { passive: false });
        }

        if (supportsFullscreen) {
            const events = [fullscreenApi.change, fullscreenApi.error]
                .filter(Boolean)
                .concat(['fullscreenchange', 'webkitfullscreenchange', 'mozfullscreenchange', 'MSFullscreenChange']);

            const uniqueEvents = Array.from(new Set(events));
            uniqueEvents.forEach(eventName => {
                document.addEventListener(eventName, updateFullscreenState, { passive: true });
            });
        }

        updateFullscreenState();
    }

    // Mobile Menu Toggle - coordinated with sidebar JS
    const mobileMenuBtn = document.getElementById('mpa-mobile-menu-btn');
    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', function(e) {
            e.preventDefault();
            // The actual toggle is handled by mpa-adminmenumain.js
            // This just adds the visual feedback
            mobileMenuBtn.style.transform = 'scale(0.95)';
            setTimeout(() => {
                mobileMenuBtn.style.transform = 'scale(1)';
            }, 150);
        });
    }

    // User Dropdown Toggle
    const userInfo = document.getElementById('mpa-user-info');
    const userDropdown = document.getElementById('mpa-user-dropdown');
    
    if (userInfo && userDropdown) {
        // Toggle dropdown on click
        userInfo.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Toggle classes
            userInfo.classList.toggle('active');
            userDropdown.classList.toggle('show');
            
            // Visual feedback
            userInfo.style.transform = 'scale(0.98)';
            setTimeout(() => {
                userInfo.style.transform = 'scale(1)';
            }, 100);
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!userInfo.contains(e.target)) {
                userInfo.classList.remove('active');
                userDropdown.classList.remove('show');
            }
        });
        
        // Close dropdown on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                userInfo.classList.remove('active');
                userDropdown.classList.remove('show');
            }
        });
        
        // Prevent dropdown from closing when clicking inside
        userDropdown.addEventListener('click', function(e) {
            e.stopPropagation();
        });
        
        // Close dropdown after clicking on menu items (except the dropdown itself)
        const dropdownItems = userDropdown.querySelectorAll('.mpa-dropdown-item');
        dropdownItems.forEach(item => {
            item.addEventListener('click', function() {
                // Small delay to allow navigation
                setTimeout(() => {
                    userInfo.classList.remove('active');
                    userDropdown.classList.remove('show');
                }, 150);
            });
        });
    }

    // Notifications Dropdown Toggle
    const notificationsBtn = document.getElementById('mpa-notifications-btn');
    const notificationsDropdown = document.getElementById('mpa-notifications-dropdown');
    
    if (notificationsBtn && notificationsDropdown) {
        // Toggle dropdown on click
        notificationsBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Close user dropdown if open
            const userInfo = document.getElementById('mpa-user-info');
            const userDropdown = document.getElementById('mpa-user-dropdown');
            if (userInfo && userDropdown) {
                userInfo.classList.remove('active');
                userDropdown.classList.remove('show');
            }
            
            // Toggle notifications dropdown
            const isShowing = notificationsDropdown.classList.contains('show');
            notificationsDropdown.classList.toggle('show');
            
            // Adjust positioning if dropdown goes off-screen
            if (!isShowing) {
                setTimeout(() => {
                    adjustDropdownPosition(notificationsBtn, notificationsDropdown);
                }, 10);
            }
            
            // Visual feedback
            notificationsBtn.style.transform = 'scale(0.95)';
            setTimeout(() => {
                notificationsBtn.style.transform = 'scale(1)';
            }, 150);
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            const notificationsContainer = document.querySelector('.mpa-notifications-container');
            if (!notificationsContainer || !notificationsContainer.contains(e.target)) {
                notificationsDropdown.classList.remove('show');
            }
        });
        
        // Close dropdown on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                notificationsDropdown.classList.remove('show');
            }
        });
        
        // Prevent dropdown from closing when clicking inside
        notificationsDropdown.addEventListener('click', function(e) {
            e.stopPropagation();
        });
        
        // Mark all as read functionality
        const markAllReadBtn = document.getElementById('mpa-mark-all-read');
        if (markAllReadBtn) {
            markAllReadBtn.addEventListener('click', function(e) {
                e.preventDefault();
                
                // AJAX call to mark all notifications as read
                if (typeof mpa_ajax !== 'undefined') {
                    const formData = new FormData();
                    formData.append('action', 'mpa_mark_all_notifications_read');
                    formData.append('nonce', mpa_ajax.nonce);
                    
                    fetch(mpa_ajax.url, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Mark all notifications as read visually
                            const unreadItems = notificationsDropdown.querySelectorAll('.mpa-notification-item.unread');
                            unreadItems.forEach(item => {
                                item.classList.remove('unread');
                                item.classList.add('read');
                            });
                            
                            // Hide notification dot
                            const notificationDot = notificationsBtn.querySelector('.mpa-notification-dot');
                            if (notificationDot) {
                                notificationDot.style.opacity = '0';
                                notificationDot.style.transform = 'scale(0)';
                                setTimeout(() => {
                                    notificationDot.remove();
                                }, 200);
                            }
                            
                            // Hide mark all read button
                            markAllReadBtn.style.opacity = '0';
                            markAllReadBtn.style.transform = 'scale(0.9)';
                            setTimeout(() => {
                                markAllReadBtn.style.display = 'none';
                            }, 200);
                        }
                    })
                    .catch(error => {
                        console.error('Error marking notifications as read:', error);
                    });
                }
                
                // Visual feedback
                markAllReadBtn.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    markAllReadBtn.style.transform = 'scale(1)';
                }, 100);
            });
        }
        
        // Individual notification click handling
        const notificationItems = notificationsDropdown.querySelectorAll('.mpa-notification-item');
        notificationItems.forEach(item => {
            item.addEventListener('click', function(e) {
                // Don't trigger if clicking on action button
                if (e.target.classList.contains('mpa-notification-action')) {
                    return;
                }
                
                // Mark as read
                if (item.classList.contains('unread')) {
                    const notificationId = item.getAttribute('data-notification-id');
                    
                    if (notificationId && typeof mpa_ajax !== 'undefined') {
                        const formData = new FormData();
                        formData.append('action', 'mpa_mark_notification_read');
                        formData.append('notification_id', notificationId);
                        formData.append('nonce', mpa_ajax.nonce);
                        
                        fetch(mpa_ajax.url, {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                item.classList.remove('unread');
                                item.classList.add('read');
                                
                                // Update notification count
                                const notificationDot = notificationsBtn.querySelector('.mpa-notification-dot');
                                if (notificationDot) {
                                    const currentCount = parseInt(notificationDot.textContent) || 0;
                                    const newCount = Math.max(0, currentCount - 1);
                                    
                                    if (newCount === 0) {
                                        notificationDot.style.opacity = '0';
                                        notificationDot.style.transform = 'scale(0)';
                                        setTimeout(() => {
                                            notificationDot.remove();
                                        }, 200);
                                        
                                        // Also hide mark all read button
                                        const markAllReadBtn = document.getElementById('mpa-mark-all-read');
                                        if (markAllReadBtn) {
                                            markAllReadBtn.style.display = 'none';
                                        }
                                    } else {
                                        notificationDot.textContent = newCount > 9 ? '9+' : newCount;
                                    }
                                }
                            }
                        })
                        .catch(error => {
                            console.error('Error marking notification as read:', error);
                        });
                    } else {
                        // Fallback for items without ID
                        item.classList.remove('unread');
                        item.classList.add('read');
                    }
                }
                
                // Close dropdown after short delay
                setTimeout(() => {
                    notificationsDropdown.classList.remove('show');
                }, 100);
            });
        });
    }

    // Function to adjust dropdown position if it goes off-screen
    function adjustDropdownPosition(button, dropdown) {
        const rect = dropdown.getBoundingClientRect();
        const viewportWidth = window.innerWidth;
        const viewportHeight = window.innerHeight;
        
        // Reset positioning classes first
        dropdown.classList.remove('position-left', 'position-center');
        
        // Check if dropdown goes off the right edge
        if (rect.right > viewportWidth - 10) {
            dropdown.classList.add('position-left');
        }
        
        // Check if dropdown goes off the left edge
        if (rect.left < 10) {
            dropdown.classList.add('position-center');
        }
        
        // Check if dropdown goes off the bottom edge
        if (rect.bottom > viewportHeight - 10) {
            dropdown.style.maxHeight = `${viewportHeight - rect.top - 20}px`;
        }
    }

    // Menu activation is now handled by mpa-adminmenumain.js
});
