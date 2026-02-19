// Table handling (vanilla JS, no jQuery/DataTables).
// initDataTable is optional; use PrimeVue DataTable or native tables when needed.

function initDataTable(tableId, options = {}) {
    var el = document.getElementById(tableId);
    if (!el) return null;
    // Stub API so callers that expect .draw(), .row.add(), etc. do not throw.
    return {
        draw: function () {},
        search: function () { return this; },
        order: function () { return this; },
        row: {
            add: function () { return { node: null }; },
            remove: function () { return this; }
        }
    };
}

function addTableRow(table, data) {
    var row = table && table.row && table.row.add ? table.row.add(data) : null;
    if (table && table.draw) table.draw();
    return row;
}

function removeTableRow(table, row) {
    if (table && table.row) table.row(row).remove();
    if (table && table.draw) table.draw();
}

function updateTableRow(table, row, data) {
    if (table && table.row) table.row(row).data(data);
    if (table && table.draw) table.draw();
}

function searchTable(table, searchTerm) {
    if (table && table.search) table.search(searchTerm);
    if (table && table.draw) table.draw();
}

function sortTable(table, columnIndex, order) {
    if (table && table.order) table.order([[columnIndex, order]]);
    if (table && table.draw) table.draw();
}
