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
    
    const quantityInput = document.getElementById('quantity');
    const availableQuantityInput = document.getElementById('available_quantity');
    
    if (quantityInput && availableQuantityInput) {
        const borrowedCount = parseInt(availableQuantityInput.getAttribute('data-borrowed-count') || 0);
        
        const updateAvailableQuantity = (totalQuantity) => {
            const newAvailable = totalQuantity - borrowedCount;
            availableQuantityInput.value = newAvailable;
            availableQuantityInput.max = totalQuantity;
        };
        
        quantityInput.addEventListener('change', function() {
            const totalQuantity = parseInt(this.value) || 0;
            if (totalQuantity < borrowedCount) {
                alert(`Total quantity cannot be less than borrowed count (${borrowedCount})`);
                this.value = Math.max(borrowedCount, 1);
            }
            
            updateAvailableQuantity(parseInt(this.value));
        });
        
        availableQuantityInput.addEventListener('change', function() {
            const totalQuantity = parseInt(quantityInput.value) || 0;
            const availableQuantity = parseInt(this.value) || 0;
            const minAvailable = totalQuantity - borrowedCount;
            
            if (availableQuantity < minAvailable) {
                alert(`Available quantity must be at least ${minAvailable} (total quantity - borrowed count)`);
                this.value = minAvailable;
            } else if (availableQuantity > totalQuantity) {
                alert('Available quantity cannot exceed total quantity');
                this.value = totalQuantity;
            }
        });
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
        
        if (quantityInput && availableQuantityInput) {
            const totalQuantity = parseInt(quantityInput.value) || 0;
            const availableQuantity = parseInt(availableQuantityInput.value) || 0;
            const borrowedCount = parseInt(availableQuantityInput.getAttribute('data-borrowed-count')) || 0;
            const minAvailable = totalQuantity - borrowedCount;
            
            if (totalQuantity < borrowedCount) {
                isValid = false;
                alert(`Total quantity cannot be less than borrowed count (${borrowedCount})`);
            } else if (availableQuantity < minAvailable) {
                isValid = false;
                alert(`Available quantity must be at least ${minAvailable} (total quantity - borrowed count)`);
            } else if (availableQuantity > totalQuantity) {
                isValid = false;
                alert('Available quantity cannot exceed total quantity');
            }
        }
        
        if (!isValid) {
            event.preventDefault();
            if (!document.querySelector('.error')) {
                alert('Please check the quantity values.');
            } else {
                alert('Please fill in all required fields.');
            }
        }
    });
});