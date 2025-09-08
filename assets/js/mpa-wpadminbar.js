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