import mask from '@alpinejs/mask';
import Chart from 'chart.js/auto';

// Chart.js Livewire integration
let charts = {};

// Global functions for chart management
window.initializeChart = function (chartId, chartType, chartData, chartOptions = {}) {
    const canvas = document.getElementById(chartId);
    if (!canvas) return;

    // Destroy existing chart if it exists
    if (charts[chartId]) {
        charts[chartId].destroy();
    }

    const ctx = canvas.getContext('2d');

    const config = {
        type: chartType,
        data: chartData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: chartType !== 'doughnut' ? {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: getComputedStyle(document.documentElement)
                            .getPropertyValue('--color-stone-200') || '#e5e7eb'
                    },
                    ticks: {
                        color: getComputedStyle(document.documentElement)
                            .getPropertyValue('--color-stone-500') || '#6b7280'
                    }
                },
                x: {
                    grid: {
                        color: getComputedStyle(document.documentElement)
                            .getPropertyValue('--color-stone-200') || '#e5e7eb'
                    },
                    ticks: {
                        color: getComputedStyle(document.documentElement)
                            .getPropertyValue('--color-stone-500') || '#6b7280'
                    }
                }
            } : {},
            ...chartOptions
        }
    };

    charts[chartId] = new Chart(ctx, config);
    return charts[chartId];
};

window.updateChart = function (chartId, newData) {
    if (charts[chartId]) {
        charts[chartId].data = newData;
        charts[chartId].update('none'); // No animation for updates
    }
};

window.destroyChart = function (chartId) {
    if (charts[chartId]) {
        charts[chartId].destroy();
        delete charts[chartId];
    }
};

// Livewire event listeners
document.addEventListener('livewire:init', () => {
    Livewire.on('chartDataUpdated', (data) => {
        if (data.chartId && data.chartData) {
            window.updateChart(data.chartId, data.chartData);
        }
    });
});

document.addEventListener('alpine:init', () => {
    Alpine.plugin(mask);
});
