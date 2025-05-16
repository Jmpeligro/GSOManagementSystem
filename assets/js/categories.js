document.addEventListener('DOMContentLoaded', function() {
    const showFormButton = document.getElementById('showAddCategoryForm');
    const addCategoryForm = document.getElementById('addCategoryForm');
    const cancelButton = document.getElementById('cancelAddCategory');
    
    if (showFormButton && addCategoryForm && cancelButton) {
        showFormButton.addEventListener('click', function() {
            addCategoryForm.style.display = 'block';
            showFormButton.style.display = 'none';
        });
        
        cancelButton.addEventListener('click', function() {
            addCategoryForm.style.display = 'none';
            showFormButton.style.display = 'inline-block';
        });
    }   
});

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