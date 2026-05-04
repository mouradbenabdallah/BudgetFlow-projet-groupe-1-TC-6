// JavaScript global BudgetFlow.
(function () {
    'use strict';

    document.querySelectorAll('[data-password-toggle]').forEach(function (button) {
        button.addEventListener('click', function () {
            var target = document.getElementById(button.getAttribute('data-password-toggle'));
            if (!target) {
                return;
            }

            var isPassword = target.getAttribute('type') === 'password';
            target.setAttribute('type', isPassword ? 'text' : 'password');
            button.setAttribute('aria-label', isPassword ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
            button.innerHTML = isPassword
                ? '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 3l18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M10.58 10.58A2 2 0 0012 14a2 2 0 001.42-.58M9.88 5.09A9.77 9.77 0 0112 4c5 0 9 5 9 8a10.88 10.88 0 01-2.2 3.43M6.1 6.1C4.22 7.43 3 9.78 3 12c0 3 4 8 9 8a9.65 9.65 0 004.08-.93" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>'
                : '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/></svg>';
        });
    });

    if (typeof Chart !== 'undefined') {
        Chart.defaults.color = '#718096';
        Chart.defaults.borderColor = '#e7efed';
        Chart.defaults.font.family = 'Inter';
    }

    function tnd(value) {
        return new Intl.NumberFormat('fr-FR', {
            maximumFractionDigits: 0,
        }).format(Number(value) || 0) + ' TND';
    }

    function readJsonScript(id) {
        var script = document.getElementById(id);

        if (!script) {
            return [];
        }

        try {
            return JSON.parse(script.textContent || '[]');
        } catch (error) {
            return [];
        }
    }

    window.initMonthlyBars = function (canvasId, months, incomeData, expenseData) {
        var canvas = document.getElementById(canvasId);

        if (!canvas || typeof Chart === 'undefined') {
            return null;
        }

        return new Chart(canvas, {
            type: 'bar',
            data: {
                labels: months,
                datasets: [
                    {
                        label: 'Revenus',
                        data: incomeData,
                        backgroundColor: '#007f5f',
                        borderRadius: 7,
                        barThickness: 18,
                    },
                    {
                        label: 'Dépenses',
                        data: expenseData,
                        backgroundColor: '#e8efed',
                        borderColor: '#c6d6d2',
                        borderWidth: 1,
                        borderRadius: 7,
                        barThickness: 46,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        stacked: true,
                        grid: {
                            display: false,
                        },
                        ticks: {
                            color: '#60757a',
                        },
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: '#edf3f2',
                            drawBorder: false,
                        },
                        ticks: {
                            color: '#60757a',
                            callback: function (value) {
                                if (value >= 1000) {
                                    return (value / 1000) + 'k';
                                }

                                return value;
                            },
                        },
                    },
                },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: '#60757a',
                            boxWidth: 14,
                            boxHeight: 14,
                            padding: 22,
                            font: {
                                size: 12,
                                weight: '500',
                            },
                        },
                    },
                    tooltip: {
                        backgroundColor: '#002b36',
                        borderColor: '#b9c9c6',
                        borderWidth: 1,
                        titleColor: '#ffffff',
                        bodyColor: '#e8f2ef',
                        callbacks: {
                            label: function (context) {
                                return ' ' + context.dataset.label + ': ' + tnd(context.parsed.y);
                            },
                        },
                    },
                },
            },
        });
    };

    window.initDoughnutChart = function (canvasId, labels, data, colors) {
        var canvas = document.getElementById(canvasId);

        if (!canvas || typeof Chart === 'undefined') {
            return null;
        }

        return new Chart(canvas, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: colors,
                    borderColor: '#ffffff',
                    borderWidth: 6,
                    hoverOffset: 4,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '62%',
                plugins: {
                    legend: {
                        display: false,
                    },
                    tooltip: {
                        backgroundColor: '#002b36',
                        borderColor: '#b9c9c6',
                        borderWidth: 1,
                        titleColor: '#ffffff',
                        bodyColor: '#e8f2ef',
                        callbacks: {
                            label: function (context) {
                                return ' ' + context.label + ': ' + tnd(context.parsed);
                            },
                        },
                    },
                },
            },
        });
    };

    document.addEventListener('DOMContentLoaded', function () {
        var evolutionData = readJsonScript('bf-evolution-chart-data');
        var chartData = readJsonScript('bf-category-chart-data');

        if (document.getElementById('monthlyChart')) {
            window.initMonthlyBars(
                'monthlyChart',
                evolutionData.map(function (item) { return item.month; }),
                evolutionData.map(function (item) { return Number(item.income); }),
                evolutionData.map(function (item) { return Number(item.expense); })
            );
        }

        if (document.getElementById('categoryChart') && chartData.length > 0) {
            window.initDoughnutChart(
                'categoryChart',
                chartData.map(function (item) { return item.name; }),
                chartData.map(function (item) { return Number(item.amount); }),
                chartData.map(function (item) { return item.color || '#718096'; })
            );
        }
    });
})();
