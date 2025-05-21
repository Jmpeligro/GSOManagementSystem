function confirmDelete(id, name) {
  return confirm(
    'Are you sure you want to delete the maintenance record for "' +
      name +
      '"? This action cannot be undone.'
  );
}

function showAddMaintenanceForm() {
  document.getElementById("addMaintenanceModal").style.display = "block";
}

function closeAddMaintenanceForm() {
  document.getElementById("addMaintenanceModal").style.display = "none";
}

function showUpdateForm(id) {
  document.getElementById("updateForm" + id).style.display = "block";
}

function closeUpdateForm(id) {
  document.getElementById("updateForm" + id).style.display = "none";
}

function validateCost(input) {
  input.value = input.value.replace(/[^0-9.]/g, "");

  const parts = input.value.split(".");
  if (parts.length > 2) {
    input.value = parts[0] + "." + parts.slice(1).join("");
  }
}

function updateMaxQuantity(equipmentId) {
  const select = document.getElementById("equipment_id");
  const option = select.selectedOptions[0];
  const quantityInput = document.getElementById("quantity");
  const maxQuantitySpan = document.getElementById("max-quantity");

  if (equipmentId && option) {
    const availableQuantity = option.getAttribute("data-available");
    maxQuantitySpan.textContent = availableQuantity;
    quantityInput.max = availableQuantity;

    if (parseInt(quantityInput.value) > parseInt(availableQuantity)) {
      quantityInput.value = availableQuantity;
    }
  } else {
    maxQuantitySpan.textContent = "-";
    quantityInput.value = "1";
    quantityInput.max = "";
  }
}
