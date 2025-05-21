document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('search');
    if (!searchInput) return;

    let searchContainer = searchInput.parentElement;
    if (!searchContainer.classList.contains('search-container')) {
        searchContainer = document.createElement('div');
        searchContainer.className = 'search-container';
        searchInput.parentNode.insertBefore(searchContainer, searchInput);
        searchContainer.appendChild(searchInput);
    }
    
    const searchResults = document.createElement('div');
    searchResults.className = 'search-results';
    searchResults.style.display = 'none';
    
    const searchIconContainer = document.createElement('div');
    searchIconContainer.className = 'search-icon-container';
    
    searchContainer.classList.add('has-search-icon');
    searchContainer.insertBefore(searchIconContainer, searchInput);
    searchContainer.appendChild(searchResults);
    
    searchInput.closest('form').addEventListener('submit', function(e) {
        if (document.activeElement === searchInput) {
            e.preventDefault();
        }
    });
    
    searchInput.addEventListener('input', debounce(handleSearch, 300));
    searchInput.addEventListener('focus', function() {
        if (searchInput.value.length > 1) {
            showResults();
        }
    });
    
    document.addEventListener('click', function(event) {
        if (!searchInput.contains(event.target) && !searchResults.contains(event.target)) {
            hideResults();
        }
    });
    
    function debounce(func, delay) {
        let timeout;
        return function() {
            const context = this;
            const args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(context, args), delay);
        };
    }
    
    function handleSearch() {
        const query = searchInput.value.trim();
        
        if (query.length > 1) {
            fetchEquipmentData(query);
            showResults();
        } else {
            hideResults();
        }
    }
    
    function showResults() {
        searchResults.style.display = 'block';
    }
    
    function hideResults() {
        searchResults.style.display = 'none';
    }
    
    function fetchEquipmentData(query) {
        searchResults.innerHTML = '<div class="search-loading"><i class="fa fa-spinner fa-spin"></i> Loading...</div>';        const xhr = new XMLHttpRequest();
        xhr.open('GET', `../../php/equipment/search_equipment.php?q=${encodeURIComponent(query)}`, true);
        
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
    

    function renderResults(data, query) {
        if (data.length === 0) {
            searchResults.innerHTML = `<div class="search-no-results"><i class="fa fa-info-circle"></i> No equipment found matching "${escapeHtml(query)}"</div>`;
            return;
        }
        
        let html = '<ul class="search-results-list">';
        
        data.forEach(item => {
            const isAdmin = item.is_admin;
            const canBorrow = (item.status === 'available' || item.display_status === 'partially_borrowed') && 
                            item.available_quantity > 0 && 
                            !isAdmin;
            
            html += `
                <li class="search-result-item">
                    <div class="search-result-info">
                        <div class="search-result-name">${escapeHtml(item.name)}</div>
                        <div class="search-result-category">${escapeHtml(item.category_name)}</div>
                        ${item.available_quantity ? 
                            `<div class="search-result-quantity">Available: ${item.available_quantity} / ${item.quantity}</div>` 
                            : ''}
                    </div>
                    <div class="search-result-actions">
                        <span class="status-badge status-${item.display_status || item.status}">
                            ${capitalizeFirstLetter(item.display_status === 'partially_borrowed' ? 'Partially Borrowed' : (item.display_status || item.status))}
                        </span>
                        ${canBorrow ? 
                            `<a href="../borrowings/borrow_equipment.php?id=${item.equipment_id}" class="btn-borrow">
                                <i class="fa fa-arrow-right-to-bracket"></i> Borrow
                            </a>` 
                            : ''}
                        <a href="../equipment/view_equipment.php?id=${item.equipment_id}" class="btn btn-small btn-primary">
                            <i class="fa fa-eye"></i>
                        </a>
                    </div>
                </li>
            `;
        });
        
        html += '</ul>';
        searchResults.innerHTML = html;
        
        const resultItems = searchResults.querySelectorAll('.search-result-item');
        resultItems.forEach(item => {
            item.addEventListener('click', function(e) {
                if (e.target.tagName !== 'A' && !e.target.closest('a')) {
                    const name = this.querySelector('.search-result-name').textContent;
                    searchInput.value = name;
                    hideResults();
                }
            });
        });
    }
    
    function getStatusClass(status) {
        switch (status) {
            case 'available': return 'status-available';
            case 'borrowed': return 'status-borrowed';
            case 'maintenance': return 'status-maintenance';
            case 'retired': return 'status-retired';
            default: return '';
        }
    }
    
    function capitalizeFirstLetter(string) {
        return string.charAt(0).toUpperCase() + string.slice(1);
    }
    
    function escapeHtml(unsafe) {
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
});