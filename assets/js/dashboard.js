// Toggle sidebar
        document.getElementById('sidebar-toggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('collapsed');
        });
        
        // Initialize charts
        document.addEventListener('DOMContentLoaded', function() {
            // Spending Analysis Chart
            const spendingCtx = document.getElementById('spendingChart').getContext('2d');
            const spendingChart = new Chart(spendingCtx, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    datasets: [{
                        label: 'Income',
                        data: [4500, 4800, 5200, 4900, 5300, 5500],
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        tension: 0.3,
                        fill: true
                    }, {
                        label: 'Expenses',
                        data: [3200, 3400, 3600, 3800, 3700, 3850],
                        borderColor: '#ef4444',
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            labels: {
                                color: '#f1f5f9'
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                color: 'rgba(255, 255, 255, 0.05)'
                            },
                            ticks: {
                                color: '#64748b'
                            }
                        },
                        y: {
                            grid: {
                                color: 'rgba(255, 255, 255, 0.05)'
                            },
                            ticks: {
                                color: '#64748b'
                            }
                        }
                    }
                }
            });
            
            // Budget Allocation Chart
            const budgetCtx = document.getElementById('budgetChart').getContext('2d');
            const budgetChart = new Chart(budgetCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Housing', 'Food', 'Transport', 'Entertainment', 'Utilities', 'Savings'],
                    datasets: [{
                        data: [35, 15, 12, 10, 8, 20],
                        backgroundColor: [
                            '#2563eb',
                            '#8b5cf6',
                            '#10b981',
                            '#f59e0b',
                            '#ef4444',
                            '#64748b'
                        ],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                color: '#f1f5f9',
                                padding: 15
                            }
                        }
                    }
                }
            });
            
            // Add animation to stat cards
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
               card.style.transform = 'translateY(20px)';
               card.style.opacity = '0';
               card.style.transition = 'transform 0.5s ease, opacity 0.5s ease';
                
                setTimeout(() => {
                    card.style.transform = 'translateY(0)';
                    card.style.opacity = '1';
                }, 200 * index);
            });
        });

        