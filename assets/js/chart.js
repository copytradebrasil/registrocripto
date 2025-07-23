// Profit Chart Implementation with Real Data
document.addEventListener('DOMContentLoaded', function() {
    // Check if chart container exists
    const chartCanvas = document.getElementById('profitChart');
    if (!chartCanvas) {
        console.log('Chart canvas not found');
        return;
    }
    
    console.log('Chart canvas found, initializing...');
    
    const ctx = chartCanvas.getContext('2d');
    const chartPeriodSelect = document.getElementById('chartPeriod');
    const chartModeToggle = document.getElementById('chartModeToggle');
    
    let currentChart = null;
    let isROIMode = false;
    let isDailyMode = true; // New: toggle between daily and cumulative
    let chartData = null;
    
    // Fetch chart data from server
    async function fetchChartData(days = 30) {
        try {
            const response = await fetch(`ajax/get-chart-data.php?days=${days}`);
            const data = await response.json();
            console.log('Chart data received:', data);
            
            if (data.error) {
                console.error('Server error:', data.error);
                return [];
            }
            
            // Return only real data from database
            return data;
        } catch (error) {
            console.error('Error fetching chart data:', error);
            return [];
        }
    }
    

    
    // Process data for chart display
    function processChartData(data) {
        const labels = data.map(item => item.formatted_date);
        const dailyProfitData = data.map(item => parseFloat(item.daily_profit));
        const cumulativeProfitData = data.map(item => parseFloat(item.cumulative_profit));
        const roiData = data.map(item => parseFloat(item.roi_percentage));
        
        return { 
            labels, 
            dailyProfitData, 
            cumulativeProfitData, 
            roiData 
        };
    }
    
    // Create chart with data
    function createChart(labels, data, isROI = false, chartTitle = 'Lucro') {
        console.log('Creating chart with labels:', labels, 'data:', data, 'isROI:', isROI, 'title:', chartTitle);
        
        if (currentChart) {
            currentChart.destroy();
        }
        
        // Dynamic colors based on chart type
        let borderColor = '#00ffff';
        let backgroundColor = 'rgba(0, 255, 255, 0.1)';
        
        if (isDailyMode && !isROI) {
            borderColor = '#00ff88';
            backgroundColor = 'rgba(0, 255, 136, 0.1)';
        }
        
        const dataset = {
            label: chartTitle,
            data: data,
            borderColor: borderColor,
            backgroundColor: backgroundColor,
            borderWidth: 2,
            fill: true,
            tension: 0.4,
            pointBackgroundColor: borderColor,
            pointBorderColor: '#ffffff',
            pointHoverBackgroundColor: '#ffffff',
            pointHoverBorderColor: borderColor
        };
        
        currentChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [dataset]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleColor: '#ffffff',
                        bodyColor: '#ffffff',
                        borderColor: '#00ffff',
                        borderWidth: 1,
                        callbacks: {
                            label: function(context) {
                                const value = context.parsed.y;
                                if (isROI) {
                                    return `ROI: ${value.toFixed(2)}%`;
                                } else {
                                    return `Lucro: $${value.toFixed(2)}`;
                                }
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)',
                            borderColor: 'rgba(255, 255, 255, 0.2)'
                        },
                        ticks: {
                            color: '#ffffff',
                            font: {
                                size: 11
                            }
                        }
                    },
                    y: {
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)',
                            borderColor: 'rgba(255, 255, 255, 0.2)'
                        },
                        ticks: {
                            color: '#ffffff',
                            font: {
                                size: 11
                            },
                            callback: function(value) {
                                if (isROI) {
                                    return value.toFixed(1) + '%';
                                } else {
                                    return '$' + value.toFixed(0);
                                }
                            }
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                elements: {
                    point: {
                        radius: 3,
                        hoverRadius: 6
                    }
                }
            }
        });
        
        console.log('Chart created successfully');
    }
    
    // Load initial chart
    async function loadChart(days = 30) {
        console.log('Loading chart with days:', days);
        const data = await fetchChartData(days);
        console.log('Data received in loadChart:', data);
        
        if (data && data.length > 0) {
            chartData = processChartData(data);
            console.log('Processed chart data:', chartData);
            
            let displayData, chartTitle;
            if (isROIMode) {
                displayData = chartData.roiData;
                chartTitle = 'ROI (%)';
            } else if (isDailyMode) {
                displayData = chartData.dailyProfitData;
                chartTitle = 'Lucro Diário (USDT)';
            } else {
                displayData = chartData.cumulativeProfitData;
                chartTitle = 'Lucro Acumulado (USDT)';
            }
            
            console.log('Display data:', displayData);
            createChart(chartData.labels, displayData, isROIMode, chartTitle);
        } else {
            // Clear canvas and show empty state
            if (currentChart) {
                currentChart.destroy();
                currentChart = null;
            }
            
            ctx.clearRect(0, 0, chartCanvas.width, chartCanvas.height);
            ctx.fillStyle = '#ffffff';
            ctx.font = '16px Inter';
            ctx.textAlign = 'center';
            ctx.fillText('Registre lucros para visualizar o gráfico', chartCanvas.width / 2, chartCanvas.height / 2);
        }
    }
    
    // Event listeners
    if (chartPeriodSelect) {
        chartPeriodSelect.addEventListener('change', function() {
            const days = parseInt(this.value);
            loadChart(days);
        });
    }
    
    if (chartModeToggle) {
        chartModeToggle.addEventListener('click', function() {
            if (isROIMode) {
                // ROI -> Daily
                isROIMode = false;
                isDailyMode = true;
                this.innerHTML = '<i class="fas fa-chart-line me-1"></i>Acumulado';
            } else if (isDailyMode) {
                // Daily -> Cumulative
                isDailyMode = false;
                this.innerHTML = '<i class="fas fa-percentage me-1"></i>ROI %';
            } else {
                // Cumulative -> ROI
                isROIMode = true;
                this.innerHTML = '<i class="fas fa-chart-bar me-1"></i>Diário';
            }
            
            if (chartData) {
                let displayData, chartTitle;
                if (isROIMode) {
                    displayData = chartData.roiData;
                    chartTitle = 'ROI (%)';
                } else if (isDailyMode) {
                    displayData = chartData.dailyProfitData;
                    chartTitle = 'Lucro Diário (USDT)';
                } else {
                    displayData = chartData.cumulativeProfitData;
                    chartTitle = 'Lucro Acumulado (USDT)';
                }
                createChart(chartData.labels, displayData, isROIMode, chartTitle);
            }
        });
    }
    
    // Initialize chart
    loadChart();
});