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

function openStatusModal(equipmentId, currentStatus) {
    document.getElementById('statusModal').style.display = 'flex';
    document.getElementById('modal_equipment_id').value = equipmentId;
    document.getElementById('modal_new_status').value = currentStatus;
  }

function closeStatusModal() {
    document.getElementById('statusModal').style.display = 'none';
  }
  window.onclick = function(event) {
    var modal = document.getElementById('statusModal');
    if (event.target === modal) {
      closeStatusModal();
    }
}