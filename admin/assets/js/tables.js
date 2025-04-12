// Table Handling Functions

// Initialize DataTable
function initDataTable(tableId, options = {}) {
    const defaultOptions = {
        pageLength: 10,
        order: [],
        columnDefs: []
    };

    const table = $(`#${tableId}`).DataTable({
        ...defaultOptions,
        ...options
    });

    return table;
}

// Add Row to Table
function addTableRow(table, data) {
    const row = table.row.add(data);
    table.draw();
    return row;
}

// Remove Row from Table
function removeTableRow(table, row) {
    table.row(row).remove().draw();
}

// Update Row in Table
function updateTableRow(table, row, data) {
    table.row(row).data(data).draw();
}

// Search Table
function searchTable(table, searchTerm) {
    table.search(searchTerm).draw();
}

// Sort Table
function sortTable(table, columnIndex, order) {
    table.order([columnIndex, order]).draw();
}

// Export functions
export {
    initDataTable,
    addTableRow,
    removeTableRow,
    updateTableRow,
    searchTable,
    sortTable
};
