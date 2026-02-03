// Chart Handling Functions

// Initialize Chart
function initChart(ctx, type, data, options = {}) {
    return new Chart(ctx, {
        type: type,
        data: data,
        options: options
    });
}

// Update Chart Data
function updateChartData(chart, newData) {
    chart.data = newData;
    chart.update();
}

// Add Dataset to Chart
function addChartDataset(chart, dataset) {
    chart.data.datasets.push(dataset);
    chart.update();
}

// Remove Dataset from Chart
function removeChartDataset(chart, index) {
    chart.data.datasets.splice(index, 1);
    chart.update();
}

// Update Chart Options
function updateChartOptions(chart, newOptions) {
    chart.options = newOptions;
    chart.update();
}

// Destroy Chart
function destroyChart(chart) {
    if (chart) {
        chart.destroy();
    }
}
