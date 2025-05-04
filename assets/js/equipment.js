document.addEventListener('DOMContentLoaded', function() {
    // Date initialization (only once)
    const now = new Date();
    const tomorrow = new Date();
    tomorrow.setDate(now.getDate() + 7); 

    const formatDate = (date) => date.toISOString().slice(0, 16);
    
    const borrowDate = document.getElementById('borrow_date');
    const dueDate = document.getElementById('due_date');
    
    if (borrowDate) borrowDate.value = formatDate(now);
    if (dueDate) dueDate.value = formatDate(tomorrow);

    // Modal Handling
    const statusModal = document.getElementById('statusModal');
    const closeModalBtn = document.querySelector('.close-modal');
    const closeModalBtnSecondary = document.querySelector('.close-modal-btn');

    // Make functions available globally
    window.openStatusModal = function(equipmentId, currentStatus) {
        if (!statusModal) return;
        
        document.getElementById('modal_equipment_id').value = equipmentId;
        
        const statusSelect = document.getElementById('modal_new_status');
        if (statusSelect) {
            Array.from(statusSelect.options).forEach(option => {
                option.disabled = (option.value === currentStatus);
            });
        }
        
        statusModal.style.display = 'block';
    };

    window.closeStatusModal = function() {
        if (statusModal) statusModal.style.display = 'none';
    };

    window.submitStatusChange = function() {
        const form = document.getElementById('statusChangeForm');
        if (!form || !form.new_status.value) {
            alert('Please select a status');
            return;
        }
        form.submit();
    };

    window.deleteEquipment = function(equipmentId, equipmentName) {
        if (confirm(`Are you sure you want to delete "${equipmentName}"? This action cannot be undone.`)) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '../equipment/equipment.php';
            
            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'equipment_id';
            idInput.value = equipmentId;
            
            const deleteInput = document.createElement('input');
            deleteInput.type = 'hidden';
            deleteInput.name = 'delete_equipment';
            deleteInput.value = '1';
            
            form.appendChild(idInput);
            form.appendChild(deleteInput);
            document.body.appendChild(form);
            form.submit();
        }
    };

    // Event Listeners
    if (closeModalBtn) {
        closeModalBtn.addEventListener('click', closeStatusModal);
    }

    if (closeModalBtnSecondary) {
        closeModalBtnSecondary.addEventListener('click', closeStatusModal);
    }

    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        if (event.target === statusModal) {
            closeStatusModal();
        }
    });
});