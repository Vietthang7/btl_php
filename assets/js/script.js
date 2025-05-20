document.addEventListener('DOMContentLoaded', function() {
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
    
    // Format currency inputs
    const fineAmountInput = document.getElementById('fine_amount');
    if (fineAmountInput) {
        fineAmountInput.addEventListener('input', function(e) {
            // Remove non-numeric characters
            let value = this.value.replace(/[^0-9]/g, '');
            
            // Update the input value
            this.value = value;
        });
    }
    
    // License plate validation
    const licensePlateInput = document.querySelectorAll('input[name="license_plate"]');
    if (licensePlateInput.length > 0) {
        licensePlateInput.forEach(function(input) {
            input.addEventListener('blur', function() {
                // Convert to uppercase
                this.value = this.value.toUpperCase();
                
                // Format license plate (optional)
                let value = this.value.trim();
                if (value.length > 0 && !value.includes('-') && value.length >= 5) {
                    // Try to auto-format common license plate patterns
                    const matches = value.match(/^([0-9]{2})([A-Z]{1,2})([0-9]{4,5})$/);
                    if (matches) {
                        this.value = matches[1] + matches[2] + '-' + matches[3];
                    }
                }
            });
        });
    }
    
    // Confirm deletion
    const deleteButtons = document.querySelectorAll('[data-confirm]');
    if (deleteButtons.length > 0) {
        deleteButtons.forEach(function(button) {
            button.addEventListener('click', function(e) {
                if (!confirm(this.getAttribute('data-confirm'))) {
                    e.preventDefault();
                }
            });
        });
    }
    
    // Toggle password visibility
    const togglePassword = document.querySelector('.toggle-password');
    if (togglePassword) {
        togglePassword.addEventListener('click', function() {
            const passwordInput = document.querySelector(this.getAttribute('toggle'));
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                this.innerHTML = '<i class="fas fa-eye-slash"></i>';
            } else {
                passwordInput.type = 'password';
                this.innerHTML = '<i class="fas fa-eye"></i>';
            }
        });
    }
    
    // Print functionality
    const printButtons = document.querySelectorAll('.btn-print');
    if (printButtons.length > 0) {
        printButtons.forEach(function(button) {
            button.addEventListener('click', function() {
                window.print();
            });
        });
    }
    
    // Tooltips initialization
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    const tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});