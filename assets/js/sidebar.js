document.addEventListener('DOMContentLoaded', function() {
    // Initialize Feather Icons
    feather.replace();
    
    const sidebar = document.querySelector('.sidebar');
    const contentWrapper = document.querySelector('.content-wrapper');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const userProfile = document.getElementById('userProfile');
    const userMenuDropdown = document.getElementById('userMenuDropdown');

    // User dropdown functionality
    if (userProfile && userMenuDropdown) {
        userProfile.addEventListener('click', function(e) {
            e.stopPropagation();
            userMenuDropdown.classList.toggle('active');
            const profileRect = userProfile.getBoundingClientRect();
            const dropdownHeight = userMenuDropdown.offsetHeight;
            const spaceBelow = window.innerHeight - profileRect.bottom;
            
            // Position dropdown based on available space
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
            if (!userProfile.contains(event.target)) {
                userMenuDropdown.classList.remove('active');
            }
        });
    }
    
    // Handle responsive behavior
    function handleResponsive() {
        if (window.innerWidth <= 992) {
            sidebar.classList.add('mobile-view');
            sidebar.classList.remove('collapsed');
            contentWrapper.classList.remove('expanded');
        } else {
            sidebar.classList.remove('mobile-view', 'mobile-open');
        }
    }
    
    handleResponsive();
    window.addEventListener('resize', handleResponsive);
    
    // Sidebar toggle functionality
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
            contentWrapper.classList.toggle('expanded');
        });
    }
    
    // Mobile menu toggle
    const mobileToggle = document.getElementById('mobileToggle');
    if (mobileToggle) {
        mobileToggle.addEventListener('click', function() {
            sidebar.classList.toggle('mobile-open');
        });
    }
    
    // Highlight active page
    const currentPage = window.location.pathname.split('/').pop();
    const navLinks = document.querySelectorAll('.sidebar-link');
    
    navLinks.forEach(link => {
        const href = link.getAttribute('href');
        if (href === currentPage || 
            (currentPage === '' && href === 'index.php') ||
            (currentPage !== 'index.php' && href.includes(currentPage))) {
            link.classList.add('active');
        }
    });
});