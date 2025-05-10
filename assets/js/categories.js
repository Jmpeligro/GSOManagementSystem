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