// Modal functions for users
function openAddUserModal() {
    document.getElementById('addUserModal').style.display = 'block';
    // Prevent body scrolling when modal is open
    document.body.style.overflow = 'hidden';
}

function closeAddUserModal() {
    document.getElementById('addUserModal').style.display = 'none';
    // Restore body scrolling
    document.body.style.overflow = 'auto';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('addUserModal');
    if (event.target === modal) {
        closeAddUserModal();
    }
}

// Handle form submission
document.addEventListener('DOMContentLoaded', function() {
    const addUserForm = document.querySelector('#addUserForm form');
    if (addUserForm) {
        addUserForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Submit the form using fetch API
            fetch(this.action, {
                method: 'POST',
                body: new FormData(this)
            })
            .then(response => response.text())
            .then(data => {
                // If successful, close modal and refresh page
                if (data.includes('success')) {
                    closeAddUserModal();
                    location.reload();
                } else {
                    // If there are errors, update the form content with the response
                    document.getElementById('addUserForm').innerHTML = data;
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        });
    }
});
