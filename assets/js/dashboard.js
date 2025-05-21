document.addEventListener('DOMContentLoaded', function() {
    const canvas = document.getElementById('equipmentStatusChart');
    if (canvas) {
        const ctx = canvas.getContext('2d');
        const available = parseInt(canvas.getAttribute('data-available')) || 0;
        const borrowed = parseInt(canvas.getAttribute('data-borrowed')) || 0;
        const maintenance = parseInt(canvas.getAttribute('data-maintenance')) || 0;
        const critical = parseInt(canvas.getAttribute('data-critical')) || 0; 
     
        canvas.width = 300;
        canvas.height = 300;
   
        canvas.style.width = '350px';
        canvas.style.height = '350px';
        canvas.style.margin = '0 auto'; 

        const equipmentStatusChart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: ['Available', 'Borrowed', 'Maintenance', 'Critical'],
                datasets: [{
                    data: [available, borrowed, maintenance, critical],
                    backgroundColor: ['#8ecae6', '#219ebc', '#023047', '#e74c3c'],
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
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((acc, val) => acc + val, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }   
        });
    }
});