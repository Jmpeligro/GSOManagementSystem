document.addEventListener('DOMContentLoaded', function() {
    // Initialize report options visibility
    const reportOptions = document.querySelectorAll('.report-options');
    reportOptions.forEach(option => {
        option.style.display = 'none';
    });
    
    // Handle report card clicks
    const reportCards = document.querySelectorAll('.report-card');
    reportCards.forEach(card => {
        card.addEventListener('click', function() {
            const reportType = this.getAttribute('data-report');
            
            // Hide all report options
            reportOptions.forEach(option => {
                option.style.display = 'none';
            });
            
            // Show selected report options
            const selectedOption = document.getElementById(`report-options-${reportType}`);
            if (selectedOption) {
                selectedOption.style.display = 'block';
                
                // Highlight the selected card
                reportCards.forEach(c => c.classList.remove('active'));
                this.classList.add('active');
                
                // Scroll to options if needed
                selectedOption.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        });
    });
    
    // Handle custom date range toggle in usage report
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
    
    // Initialize form validation
    const reportForms = document.querySelectorAll('form[id$="-report-form"]');
    reportForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            // Basic validation for date ranges
            const dateFrom = this.querySelector('input[name="date_from"]');
            const dateTo = this.querySelector('input[name="date_to"]');
            
            if (dateFrom && dateTo && dateFrom.value && dateTo.value) {
                if (new Date(dateFrom.value) > new Date(dateTo.value)) {
                    e.preventDefault();
                    alert('Start date cannot be after end date.');
                    return false;
                }
            }
            
            // For the usage report with custom date range
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
            
            // Additional validation for specific reports
            switch (this.querySelector('input[name="report_type"]').value) {
                case 'borrowings':
                    // Check if at least one filter is selected
                    const borrowingsFilters = [
                        this.querySelector('select[name="status"]'),
                        this.querySelector('select[name="approval_status"]'),
                        this.querySelector('input[name="date_from"]'),
                        this.querySelector('select[name="user_id"]')
                    ];
                    
                    const hasFilter = borrowingsFilters.some(filter => 
                        filter && filter.value && filter.value !== '');
                    
                    if (!hasFilter) {
                        // It's okay to submit without filters, just a warning
                        if (!confirm('You are about to generate a report with no filters. This might return a large dataset. Continue?')) {
                            e.preventDefault();
                            return false;
                        }
                    }
                    break;
                    
                case 'maintenance':
                    // If cost analysis is selected, ensure we're showing cost details
                    const sumCost = this.querySelector('input[name="sum_cost"]');
                    const showCost = this.querySelector('input[name="show_cost"]');
                    
                    if (sumCost && sumCost.checked && showCost && !showCost.checked) {
                        showCost.checked = true;
                    }
                    break;
            }
            
            // If we're generating a PDF, warn about potentially large reports
            const formatRadios = this.querySelectorAll('input[name="format"]');
            let selectedFormat = '';
            formatRadios.forEach(radio => {
                if (radio.checked) selectedFormat = radio.value;
            });
            
            if (selectedFormat === 'pdf' && !this.hasAttribute('data-pdf-warned')) {
                const groupByNone = this.querySelector('input[value="none"][name="group_by"]');
                if (groupByNone && groupByNone.checked) {
                    if (!confirm('Generating a non-grouped PDF report may result in a large file. Continue?')) {
                        e.preventDefault();
                        return false;
                    }
                    this.setAttribute('data-pdf-warned', 'true');
                }
            }
        });
    });
    
    // Auto-select the first report type
    if (reportCards.length > 0) {
        reportCards[0].click();
    }
}); 