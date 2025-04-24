document.addEventListener('DOMContentLoaded', function() {
    // Form validation
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(event) {
            const passwordField = document.getElementById('password');
            if (!passwordField) return;
            
            const password = passwordField.value;
            const modeElement = document.querySelector('[data-mode]');
            const mode = modeElement ? modeElement.getAttribute('data-mode') : 'add';
            
            // Only validate password length if it's add mode or if a password is provided in edit mode
            if ((mode === 'add' || password.length > 0) && password.length < 8) {
                event.preventDefault();
                alert('Password must be at least 8 characters long');
                return false;
            }
            
            // If in add mode, also check that passwords match
            if (mode === 'add') {
                const confirmPassword = document.getElementById('confirm_password').value;
                if (password !== confirmPassword) {
                    event.preventDefault();
                    alert('Passwords do not match');
                    return false;
                }
            }
        });
    }
    
    // Add data-mode attribute to form for JavaScript to determine mode
    const urlParams = new URLSearchParams(window.location.search);
    const id = urlParams.get('id');
    if (form) {
        form.setAttribute('data-mode', id ? 'edit' : 'add');
    }
});