document.addEventListener('DOMContentLoaded', function() {
    const table = document.querySelector('table');
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    const searchInput = document.createElement('input');
    const paginationContainer = document.createElement('div');
    const rowsPerPage = 20;
    let currentPage = 1;

    // --- Estilos básicos para que se vea mejor (puedes personalizar con CSS) ---
    table.style.width = '100%';
    table.style.borderCollapse = 'collapse';
    table.style.marginTop = '20px';
    const ths = table.querySelectorAll('th');
    ths.forEach(th => {
        th.style.border = '1px solid #ddd';
        th.style.padding = '8px';
        th.style.textAlign = 'left';
        th.style.backgroundColor = '#f2f2f2';
    });
    const tds = tbody.querySelectorAll('td');
    tds.forEach(td => {
        td.style.border = '1px solid #ddd';
        td.style.padding = '8px';
    });
    searchInput.style.padding = '8px';
    searchInput.style.marginBottom = '10px';
    searchInput.style.width = '300px';
    searchInput.placeholder = 'Buscar...';
    paginationContainer.style.marginTop = '10px';

    // --- Insertar el buscador ---
    table.parentNode.insertBefore(searchInput, table);

    // --- Insertar el contenedor de la paginación ---
    table.parentNode.insertBefore(paginationContainer, table.nextSibling);

    // --- Función para filtrar la tabla ---
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        currentPage = 1; // Resetear la página al buscar
        updateTable();
    });

    function filterRows(searchTerm) {
        return rows.filter(row => {
            const cells = Array.from(row.querySelectorAll('td'));
            return cells.some(cell => cell.textContent.toLowerCase().includes(searchTerm));
        });
    }

    // --- Función para aplicar colores intercalados ---
    function applyRowColors(visibleRows) {
        visibleRows.forEach((row, index) => {
            row.style.backgroundColor = index % 2 === 0 ? '#fff' : '#f9f9f9';
        });
    }

    // --- Funciones para la paginación ---
    function updatePagination(filteredRows) {
        const totalPages = Math.ceil(filteredRows.length / rowsPerPage);
        paginationContainer.innerHTML = '';

        if (totalPages > 1) {
            const prevButton = document.createElement('button');
            prevButton.textContent = 'Anterior';
            prevButton.addEventListener('click', () => {
                if (currentPage > 1) {
                    currentPage--;
                    updateTable();
                }
            });
            paginationContainer.appendChild(prevButton);

            for (let i = 1; i <= totalPages; i++) {
                const pageButton = document.createElement('button');
                pageButton.textContent = i;
                pageButton.style.margin = '0 5px';
                if (i === currentPage) {
                    pageButton.style.fontWeight = 'bold';
                }
                pageButton.addEventListener('click', () => {
                    currentPage = i;
                    updateTable();
                });
                paginationContainer.appendChild(pageButton);
            }

            const nextButton = document.createElement('button');
            nextButton.textContent = 'Siguiente';
            nextButton.addEventListener('click', () => {
                if (currentPage < totalPages) {
                    currentPage++;
                    updateTable();
                }
            });
            paginationContainer.appendChild(nextButton);
        }
    }

    function updateTable() {
        const searchTerm = searchInput.value.toLowerCase();
        const filteredRows = filterRows(searchTerm);
        const startIndex = (currentPage - 1) * rowsPerPage;
        const endIndex = startIndex + rowsPerPage;
        const currentPageRows = filteredRows.slice(startIndex, endIndex);

        // Ocultar todas las filas primero
        rows.forEach(row => row.style.display = 'none');

        // Mostrar solo las filas de la página actual
        currentPageRows.forEach(row => row.style.display = '');

        applyRowColors(currentPageRows);
        updatePagination(filteredRows);
    }

    // --- Inicializar la tabla ---
    updateTable();
});