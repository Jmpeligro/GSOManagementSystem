const sentimentChart = document.getElementById('sentimentChart');
const monthlySentimentChart = document.getElementById('monthlySentimentChart');
const conditionSentimentChart = document.getElementById('conditionSentimentChart');

// Modified sentiment chart to match equipment status chart size and styling
if (sentimentChart) {
    const positive = parseInt(sentimentChart.dataset.positive);
    const neutral = parseInt(sentimentChart.dataset.neutral);
    const negative = parseInt(sentimentChart.dataset.negative);
    
    // Set explicit dimensions to match the equipment status chart
    sentimentChart.width = 300;
    sentimentChart.height = 300;
    
    // Apply the same styling as equipment status chart
    sentimentChart.style.width = '350px';
    sentimentChart.style.height = '350px';
    sentimentChart.style.margin = '0 auto';

    new Chart(sentimentChart, {
        type: 'doughnut',
        data: {
            labels: ['Positive', 'Neutral', 'Negative'],
            datasets: [{
                data: [positive, neutral, negative],
                backgroundColor: ['#27ae60', '#7f8c8d', '#c0392b'],
                borderColor: ['#219653', '#6c757d', '#a93226'],
                borderWidth: 1
            }]
        },
        options: {
            responsive: false,  // Changed from true to false to match equipment chart
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        boxWidth: 12,
                        font: {
                            size: 11
                        },
                        usePointStyle: true,
                        padding: 20
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
            },
            cutout: '60%'
        }
    });
}

if (monthlySentimentChart && window.monthlyData) {
    new Chart(monthlySentimentChart, {
        type: 'line',
        data: {
            labels: window.monthlyData.labels,
            datasets: [
                {
                    label: 'Positive',
                    data: window.monthlyData.positive,
                    borderColor: '#27ae60',
                    backgroundColor: 'rgba(39, 174, 96, 0.1)',
                    fill: true
                },
                {
                    label: 'Neutral',
                    data: window.monthlyData.neutral,
                    borderColor: '#7f8c8d',
                    backgroundColor: 'rgba(127, 140, 141, 0.1)',
                    fill: true
                },
                {
                    label: 'Negative',
                    data: window.monthlyData.negative,
                    borderColor: '#c0392b',
                    backgroundColor: 'rgba(192, 57, 43, 0.1)',
                    fill: true
                },
                {
                    label: 'Average Polarity',
                    data: window.monthlyData.polarity,
                    borderColor: '#2980b9',
                    borderDash: [5, 5],
                    fill: false,
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Number of Responses'
                    }
                },
                y1: {
                    type: 'linear',
                    position: 'right',
                    beginAtZero: true,
                    min: -1,
                    max: 1,
                    title: {
                        display: true,
                        text: 'Polarity'
                    }
                }
            }
        }
    });
}

// Condition vs Sentiment Chart
if (conditionSentimentChart && window.conditionData) {
    new Chart(conditionSentimentChart, {
        type: 'bar',
        data: {
            labels: window.conditionData.labels,
            datasets: [
                {
                    label: 'Positive',
                    data: window.conditionData.positive,
                    backgroundColor: 'rgba(39, 174, 96, 0.7)'
                },
                {
                    label: 'Neutral',
                    data: window.conditionData.neutral,
                    backgroundColor: 'rgba(127, 140, 141, 0.7)'
                },
                {
                    label: 'Negative',
                    data: window.conditionData.negative,
                    backgroundColor: 'rgba(192, 57, 43, 0.7)'
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                x: {
                    stacked: true,
                },
                y: {
                    stacked: true,
                    title: {
                        display: true,
                        text: 'Number of Responses'
                    }
                }
            },
            plugins: {
                legend: {
                    position: 'bottom'
                },
                title: {
                    display: true,
                    text: 'Sentiment Distribution by Equipment Condition'
                }
            }
        }
    });
}