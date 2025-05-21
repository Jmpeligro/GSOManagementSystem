function openStatusModal(equipmentId, currentStatus, borrowedCount) {
  const modal = document.getElementById('statusModal');
  document.getElementById('modal_equipment_id').value = equipmentId;
  
  const statusSelect = document.getElementById('modal_new_status');
  
  Array.from(statusSelect.options).forEach(option => {
    if (option.value === currentStatus) {
      option.selected = true;
    }
  });
  
  if (borrowedCount > 0) {
    const noticeDiv = document.getElementById('borrowed_notice') || document.createElement('div');
    noticeDiv.id = 'borrowed_notice';
    noticeDiv.className = 'alert alert-warning';
    noticeDiv.textContent = `This equipment has ${borrowedCount} borrowed units. Some status options may be limited.`;
    
    const heading = modal.querySelector('h3');
    if (!document.getElementById('borrowed_notice')) {
      heading.parentNode.insertBefore(noticeDiv, heading.nextSibling);
    }
  } else {
    const noticeDiv = document.getElementById('borrowed_notice');
    if (noticeDiv) {
      noticeDiv.remove();
    }
  }
  
  modal.style.display = 'flex';
}