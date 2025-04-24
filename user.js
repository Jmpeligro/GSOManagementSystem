document.addEventListener('DOMContentLoaded', function() {
    // Form validation
    const form = document.querySelector('form');
    form.addEventListener('submit', function(event) {
        const password = document.getElementById('password').value;
        const mode = '<?php echo $mode; ?>';
        
        if (mode === 'add' && password.length < 6) {
            event.preventDefault();
            alert('Password must be at least 6 characters long');
        }
    });
});