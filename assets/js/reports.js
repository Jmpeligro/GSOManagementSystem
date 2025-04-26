document.addEventListener('DOMContentLoaded', function() {
    const reportOptions = document.querySelectorAll('.report-options');
    reportOptions.forEach(option => {
        option.style.display = 'none';
    });
    
    const reportCards = document.querySelectorAll('.report-card');
    reportCards.forEach(card => {
        card.addEventListener('click', function() {
            const reportType = this.getAttribute('data-report');
 
            reportOptions.forEach(option => {
                option.style.display = 'none';
            });
         
            const selectedOption = document.getElementById(`report-options-${reportType}`);
            if (selectedOption) {
                selectedOption.style.display = 'block';
    
                reportCards.forEach(c => c.classList.remove('active'));
                this.classList.add('active');

                selectedOption.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        });
    });
    
    const usagePeriodSelect = document.getElementById('usage-period');
    const customDateRange = document.querySelector('.custom-date-range');
    
    if (usagePeriodSelect && customDateRange) {
        usagePeriodSelect.addEventListener('change', function() {
            if (this.value === 'custom') {
                customDateRange.style.display = 'block';
            } else {
                customDateRange.style.display = 'none';
            }
        });
    }

    const reportForms = document.querySelectorAll('form[id$="-report-form"]');
    reportForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const dateFrom = this.querySelector('input[name="date_from"]');
            const dateTo = this.querySelector('input[name="date_to"]');
            
            if (dateFrom && dateTo && dateFrom.value && dateTo.value) {
                if (new Date(dateFrom.value) > new Date(dateTo.value)) {
                    e.preventDefault();
                    alert('Start date cannot be after end date.');
                    return false;
                }
            }
 
            if (this.id === 'usage-report-form') {
                const periodSelect = document.getElementById('usage-period');
                if (periodSelect.value === 'custom') {
                    const customFrom = document.getElementById('usage-date-from');
                    const customTo = document.getElementById('usage-date-to');
                    
                    if (!customFrom.value || !customTo.value) {
                        e.preventDefault();
                        alert('Please specify both start and end dates for custom date range.');
                        return false;
                    }
                }
            }

            switch (this.querySelector('input[name="report_type"]').value) {
                case 'borrowings':
                    const borrowingsFilters = [
                        this.querySelector('select[name="status"]'),
                        this.querySelector('select[name="approval_status"]'),
                        this.querySelector('input[name="date_from"]'),
                        this.querySelector('select[name="user_id"]')
                    ];
                    
                    const hasFilter = borrowingsFilters.some(filter => 
                        filter && filter.value && filter.value !== '');
                    
                    if (!hasFilter) {
                        if (!confirm('You are about to generate a report with no filters. This might return a large dataset. Continue?')) {
                            e.preventDefault();
                            return false;
                        }
                    }
                    break;
                    
                case 'maintenance':
                    const sumCost = this.querySelector('input[name="sum_cost"]');
                    const showCost = this.querySelector('input[name="show_cost"]');
                    
                    if (sumCost && sumCost.checked && showCost && !showCost.checked) {
                        showCost.checked = true;
                    }
                    break;
            }
        });
    });
    
    if (reportCards.length > 0) {
        reportCards[0].click();
    }
});