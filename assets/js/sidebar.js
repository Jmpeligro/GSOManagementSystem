document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, initializing sidebar');
    
    // Make sure feather icons are replaced
    if (typeof feather !== 'undefined') {
        feather.replace();
    } else {
        console.error('Feather icons not loaded');
    }
    
    const sidebar = document.querySelector('.sidebar');
    const contentWrapper = document.querySelector('.content-wrapper');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const userProfile = document.getElementById('userProfile');
    const userMenuDropdown = document.getElementById('userMenuDropdown');
    const mobileToggle = document.getElementById('mobileToggle');
    
    console.log('Elements found:', {
        sidebar: !!sidebar,
        contentWrapper: !!contentWrapper,
        sidebarToggle: !!sidebarToggle,
        userProfile: !!userProfile,
        userMenuDropdown: !!userMenuDropdown,
        mobileToggle: !!mobileToggle
    });

    // User dropdown functionality
    if (userProfile && userMenuDropdown) {
        userProfile.addEventListener('click', function(e) {
            console.log('User profile clicked');
            e.stopPropagation();
            userMenuDropdown.classList.toggle('active');
            
            // Position the dropdown correctly
            const profileRect = userProfile.getBoundingClientRect();
            const dropdownHeight = userMenuDropdown.offsetHeight;
            const spaceBelow = window.innerHeight - profileRect.bottom;
            
            if (spaceBelow > dropdownHeight || spaceBelow > profileRect.top) {
                userMenuDropdown.style.bottom = 'auto';
                userMenuDropdown.style.top = '100%';
            } else {
                userMenuDropdown.style.bottom = '100%';
                userMenuDropdown.style.top = 'auto';
            }
            userMenuDropdown.style.right = '0';
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            if (userMenuDropdown.classList.contains('active') && !userProfile.contains(event.target)) {
                console.log('Closing user menu');
                userMenuDropdown.classList.remove('active');
            }
        });
    }
    
    // Function to check if on mobile view
    function isMobile() {
        return window.innerWidth <= 992;
    }
    
    // Responsive sidebar handling
    function handleResponsive() {
        if (window.innerWidth <= 992) {
            // Mobile view
            sidebar.classList.add('mobile-view');
            // Restore previous mobile state if it exists
            sidebar.classList.remove('mobile-open');
            // Always show sidebar on mobile unless explicitly toggled
            sidebar.classList.remove('hidden');
            contentWrapper.classList.remove('full-width');
        } else {
            // Desktop view
            sidebar.classList.remove('mobile-view', 'mobile-open');
            // Only hide if user explicitly hid it
            sidebar.classList.remove('hidden');
            contentWrapper.classList.remove('full-width');
        }
    }
    
    // Initialize responsive state
    handleResponsive();
    window.addEventListener('resize', handleResponsive);
    
    // Sidebar toggle functionality
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function(e) {
            e.preventDefault();
            if (window.innerWidth <= 992) {
                // Mobile toggle - just show/hide
                if (sidebar.classList.contains('mobile-open')) {
                    sidebar.classList.remove('mobile-open');
                } else {
                    sidebar.classList.add('mobile-open');
                }
            } else {
                // Desktop toggle - show/hide
                if (sidebar.classList.contains('hidden')) {
                    sidebar.classList.remove('hidden');
                    contentWrapper.classList.remove('full-width');
                } else {
                    sidebar.classList.add('hidden');
                    contentWrapper.classList.add('full-width');
                }
            }
        });
    }

    // Mobile toggle button functionality
    if (mobileToggle) {
        mobileToggle.addEventListener('click', function (e) {
            e.stopPropagation();
            if (window.innerWidth <= 992) {
                // Mobile toggle behavior
                if (sidebar.classList.contains('mobile-open')) {
                    sidebar.classList.remove('mobile-open');
                } else {
                    sidebar.classList.add('mobile-open');
                }
            } else {
                // Desktop behavior
                if (sidebar.classList.contains('collapsed')) {
                    sidebar.classList.remove('collapsed');
                    contentWrapper.classList.remove('expanded');
                } else if (sidebar.classList.contains('hidden')) {
                    sidebar.classList.remove('hidden');
                    contentWrapper.classList.remove('full-width');
                } else {
                    sidebar.classList.add('collapsed');
                    contentWrapper.classList.add('expanded');
                }
            }
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function (e) {
            if (
                window.innerWidth <= 992 &&
                sidebar.classList.contains('mobile-open') &&
                !sidebar.contains(e.target) &&
                e.target !== mobileToggle
            ) {
                sidebar.classList.remove('mobile-open');
            }
        });
    }
    
    // Set active link based on current page
    const currentPath = window.location.pathname;
    const navLinks = document.querySelectorAll('.sidebar-link');
    
    navLinks.forEach(link => {
        const href = link.getAttribute('href');
        if (currentPath === href || 
            currentPath.includes(href) && href !== '/' || 
            (currentPath.endsWith('/') && href.includes('index.php'))) {
            link.classList.add('active');
            console.log('Active link set:', href);
        }
    });
    
    // Re-initialize Feather icons after a delay
    setTimeout(function() {
        if (typeof feather !== 'undefined') {
            feather.replace();
        }
    }, 500);

    // After all event listeners and initialization, ensure sidebar is visible by default on desktop
    if (window.innerWidth > 992 && localStorage.getItem('sidebarHidden') === null) {
        sidebar.classList.remove('hidden');
        contentWrapper.classList.remove('full-width');
    }
});