document.addEventListener('DOMContentLoaded', function() {
    const statusSelect = document.getElementById('status');
    const isBorrowed = statusSelect.querySelector('option[value="borrowed"]') !== null;
    
    if (isBorrowed) {
        statusSelect.disabled = true;

        const hiddenStatus = document.createElement('input');
        hiddenStatus.type = 'hidden';
        hiddenStatus.name = 'status';
        hiddenStatus.value = 'borrowed';
        statusSelect.parentNode.appendChild(hiddenStatus);
    }
    
    const form = document.querySelector('form');
    form.addEventListener('submit', function(event) {
        let isValid = true;
        
        const requiredFields = form.querySelectorAll('[required]');
        requiredFields.forEach(function(field) {
            if (!field.value.trim()) {
                isValid = false;
                field.classList.add('error');
            } else {
                field.classList.remove('error');
            }
        });
        
        if (!isValid) {
            event.preventDefault();
            alert('Please fill in all required fields.');
        }
    });
});