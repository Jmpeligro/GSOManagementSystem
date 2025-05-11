document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('search');
    const searchContainer = searchInput ? searchInput.closest('.search-container') : null;
    
    if (!searchInput || !searchContainer) return;
    
    // Create search results container
    const searchResults = document.createElement('div');
    searchResults.className = 'search-results';
    searchResults.style.display = 'none';
    
    // Create search icon
    const searchIconContainer = document.createElement('div');
    searchIconContainer.className = 'search-icon-container';
    searchIconContainer.innerHTML = '<i class="fa fa-search"></i>';
    
    // Add the search icon to the input container
    searchContainer.classList.add('has-search-icon');
    searchContainer.appendChild(searchIconContainer);
    
    // Insert the search results container after the search input
    searchContainer.appendChild(searchResults);
    
    // Add event listeners
    searchInput.addEventListener('input', debounce(handleSearch, 300));
    searchInput.addEventListener('focus', function() {
        if (searchInput.value.length > 1) {
            showResults();
        }
    });
    
    // Close results when clicking outside
    document.addEventListener('click', function(event) {
        if (!searchInput.contains(event.target) && !searchResults.contains(event.target)) {
            hideResults();
        }
    });
    
    // Debounce function to limit API calls
    function debounce(func, delay) {
        let timeout;
        return function() {
            const context = this;
            const args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(context, args), delay);
        };
    }
    
    // Handle search input
    function handleSearch() {
        const query = searchInput.value.trim();
        
        if (query.length > 1) {
            fetchEquipmentData(query);
            showResults();
        } else {
            hideResults();
        }
    }
    
    // Show search results
    function showResults() {
        searchResults.style.display = 'block';
    }
    
    // Hide search results
    function hideResults() {
        searchResults.style.display = 'none';
    }
    
    // Fetch equipment data via AJAX
    function fetchEquipmentData(query) {
        // Show loading indicator
        searchResults.innerHTML = '<div class="search-loading"><i class="fa fa-spinner fa-spin"></i> Loading...</div>';
        
        // Make AJAX request
        const xhr = new XMLHttpRequest();
        xhr.open('GET', `search_equipment.php?q=${encodeURIComponent(query)}`, true);
        
        xhr.onload = function() {
            if (xhr.status === 200) {
                try {
                    const data = JSON.parse(xhr.responseText);
                    renderResults(data, query);
                } catch (e) {
                    searchResults.innerHTML = '<div class="search-error"><i class="fa fa-exclamation-circle"></i> Error parsing results</div>';
                }
            } else {
                searchResults.innerHTML = '<div class="search-error"><i class="fa fa-exclamation-circle"></i> Error loading results</div>';
            }
        };
        
        xhr.onerror = function() {
            searchResults.innerHTML = '<div class="search-error"><i class="fa fa-exclamation-circle"></i> Request failed</div>';
        };
        
        xhr.send();
    }
    
    // Render search results
    function renderResults(data, query) {
        if (data.length === 0) {
            searchResults.innerHTML = `<div class="search-no-results"><i class="fa fa-info-circle"></i> No equipment found matching "${escapeHtml(query)}"</div>`;
            return;
        }
        
        let html = '<ul class="search-results-list">';
        
        data.forEach(item => {
            html += `
                <li class="search-result-item">
                    <div class="search-result-info">
                        <div class="search-result-name">${escapeHtml(item.name)}</div>
                        <div class="search-result-category">${escapeHtml(item.category_name)}</div>
                    </div>
                    <div class="search-result-actions">
                        <span class="status-badge status-${item.status}">${capitalizeFirstLetter(item.status)}</span>
                        ${item.status === 'available' ? 
                            `<a href="../borrowings/borrow_equipment.php?id=${item.equipment_id}" class="btn-borrow">
                                <i class="fa fa-arrow-right-to-bracket"></i> Borrow
                            </a>` : ''}
                        <a href="../equipment/view_equipment.php?id=${item.equipment_id}" class="btn btn-small btn-primary">
                            <i class="fa fa-eye"></i>
                        </a>
                    </div>
                </li>
            `;
        });
        
        html += '</ul>';
        searchResults.innerHTML = html;
        
        // Add click handlers for search result items
        const resultItems = searchResults.querySelectorAll('.search-result-item');
        resultItems.forEach(item => {
            item.addEventListener('click', function(e) {
                // Don't trigger if clicking on a button or link
                if (e.target.tagName !== 'A' && !e.target.closest('a')) {
                    const name = this.querySelector('.search-result-name').textContent;
                    searchInput.value = name;
                    hideResults();
                }
            });
        });
    }
    
    // Helper function to get status class
    function getStatusClass(status) {
        switch (status) {
            case 'available': return 'status-available';
            case 'borrowed': return 'status-borrowed';
            case 'maintenance': return 'status-maintenance';
            case 'retired': return 'status-retired';
            default: return '';
        }
    }
    
    // Helper function to capitalize first letter
    function capitalizeFirstLetter(string) {
        return string.charAt(0).toUpperCase() + string.slice(1);
    }
    
    // Helper function to escape HTML
    function escapeHtml(unsafe) {
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
});