document.addEventListener('DOMContentLoaded', function() {
    const now = new Date();
    const nextWeek = new Date();
    nextWeek.setDate(now.getDate() + 7); 
put
    const formatDate = (date) => {
        return date.toISOString().slice(0, 16); 
    };
    
    document.getElementById('borrow_date').value = formatDate(now);
    document.getElementById('due_date').value = formatDate(nextWeek);
});


