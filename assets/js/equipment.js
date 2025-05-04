document.addEventListener('DOMContentLoaded', function() {
    const now = new Date();
    const tomorrow = new Date();
    tomorrow.setDate(now.getDate() + 7); 

    const formatDate = (date) => {
        return date.toISOString().slice(0, 16);
    };
    
    document.getElementById('borrow_date').value = formatDate(now);
    document.getElementById('due_date').value = formatDate(tomorrow);
});

function confirmDelete(id, name) {
    return confirm('Are you sure you want to delete "' + name + '"? This action cannot be undone.');
}
function validateStatusChange(selectElement) {
    if (selectElement.value === "") {
        return false; 
    }
    return true; 
}
