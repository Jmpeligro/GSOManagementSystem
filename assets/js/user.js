document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(event) {
            const passwordField = document.getElementById('password');
            if (!passwordField) return;
            
            const password = passwordField.value;
            const modeElement = document.querySelector('[data-mode]');
            const mode = modeElement ? modeElement.getAttribute('data-mode') : 'add';
     
            if ((mode === 'add' || password.length > 0) && password.length < 8) {
                event.preventDefault();
                alert('Password must be at least 8 characters long');
                return false;
            }

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
   
    const urlParams = new URLSearchParams(window.location.search);
    const id = urlParams.get('id');
    if (form) {
        form.setAttribute('data-mode', id ? 'edit' : 'add');
    }
});