document.addEventListener('DOMContentLoaded', function() {
    const canvas = document.getElementById('equipmentStatusChart');
    if (canvas) {
        const ctx = canvas.getContext('2d');
        const available = parseInt(canvas.getAttribute('data-available')) || 0;
        const borrowed = parseInt(canvas.getAttribute('data-borrowed')) || 0;
        const maintenance = parseInt(canvas.getAttribute('data-maintenance')) || 0;
     
        canvas.width = 300;
        canvas.height = 300;
   
        canvas.style.width = '350px';
        canvas.style.height = '350px';
        canvas.style.margin = '0 auto'; 

        const equipmentStatusChart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: ['Available', 'Borrowed', 'Maintenance'],
                datasets: [{
                    data: [available, borrowed, maintenance],
                    backgroundColor: ['#8ecae6', '#219ebc', '#023047'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: false, 
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 12,
                            font: {
                                size: 11
                            }
                        }
                    }
                }
            }   
        });
    }
});