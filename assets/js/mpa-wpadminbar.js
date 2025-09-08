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

    // Menu activation is now handled by mpa-adminmenumain.js
});