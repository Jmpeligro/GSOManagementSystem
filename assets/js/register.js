document.addEventListener('DOMContentLoaded', function() {
    const emailField = document.querySelector('input[name="email"]');
    
    if (emailField) {
        emailField.addEventListener('blur', function() {
            const email = this.value.trim();
            const errorElement = document.getElementById('email-error') || 
                                document.createElement('div');
            
            errorElement.id = 'email-error';
            errorElement.className = 'error-message text-danger';
            
            if (email && !/@plpasig\.edu\.ph$/i.test(email)) {
                errorElement.textContent = 'Email must be from the plpasig.edu.ph domain.';
                this.after(errorElement);
                this.classList.add('is-invalid');
            } else {
                errorElement.remove();
                this.classList.remove('is-invalid');
            }
        });
    }
});

(function () {
    'use strict'
    
    // Form validation
    var forms = document.querySelectorAll('.needs-validation')
    Array.prototype.slice.call(forms)
        .forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault()
                    event.stopPropagation()
                }

                form.classList.add('was-validated')
            }, false)
        })
        
    // Dynamic field visibility based on role
    const roleSelect = document.getElementById('role');
    const programYearSectionContainer = document.getElementById('program_year_section_container');
    const programYearSectionInput = document.getElementById('program_year_section');
    
    roleSelect.addEventListener('change', function() {
        if (this.value === 'student') {
            programYearSectionContainer.style.display = 'block';
            programYearSectionInput.required = true;
        } else {
            programYearSectionContainer.style.display = 'none';
            programYearSectionInput.required = false;
        }
    });
})()