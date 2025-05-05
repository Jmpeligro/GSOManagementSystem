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
    
    console.log('Elements found:', {
        sidebar: !!sidebar,
        contentWrapper: !!contentWrapper,
        sidebarToggle: !!sidebarToggle,
        userProfile: !!userProfile,
        userMenuDropdown: !!userMenuDropdown
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
    
    // Responsive sidebar handling
    function handleResponsive() {
        console.log('Window resized:', window.innerWidth);
        if (window.innerWidth <= 992) {
            sidebar.classList.add('mobile-view');
            sidebar.classList.remove('collapsed');
            contentWrapper.classList.remove('expanded');
        } else {
            sidebar.classList.remove('mobile-view', 'mobile-open');
        }
    }
    
    // Initialize responsive state
    handleResponsive();
    window.addEventListener('resize', handleResponsive);
    
    // Sidebar toggle functionality
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function(e) {
            console.log('Sidebar toggle clicked');
            e.preventDefault();
            sidebar.classList.toggle('collapsed');
            contentWrapper.classList.toggle('expanded');
        });
    }

    // Mobile toggle functionality
    const mobileToggle = document.getElementById('mobileToggle');
    if (mobileToggle) {
        mobileToggle.addEventListener('click', function(e) {
            console.log('Mobile toggle clicked');
            e.preventDefault();
            sidebar.classList.toggle('mobile-open');
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
}); 