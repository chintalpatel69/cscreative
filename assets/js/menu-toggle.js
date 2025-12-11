document.addEventListener('DOMContentLoaded', function() {
    const menuToggle = document.querySelector('.menu-toggle');
    const primaryMenu = document.querySelector('#menu-primary-menu');

    if (menuToggle && primaryMenu) { // Ensure both elements exist
        menuToggle.addEventListener('click', function(event) {
            event.stopPropagation(); // Prevent click event from bubbling up
            const isExpanded = menuToggle.getAttribute('aria-expanded') === 'true';
            menuToggle.setAttribute('aria-expanded', !isExpanded);
            primaryMenu.classList.toggle('active');
        });

        // Close the menu when clicking outside
        document.addEventListener('click', function(event) {
            if (!menuToggle.contains(event.target) && !primaryMenu.contains(event.target)) {
                if (primaryMenu.classList.contains('active')) {
                    menuToggle.setAttribute('aria-expanded', 'false');
                    primaryMenu.classList.remove('active');
                }
            }
        });
    } else {
        console.error('Menu Toggle or Primary Menu not found');
    }
});
