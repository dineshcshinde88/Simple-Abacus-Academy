    </div>
  </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jspdf-autotable@3.8.2/dist/jspdf.plugin.autotable.min.js"></script>
<script>
function adminExportRows(targetId) {
  const target = document.getElementById(targetId);
  if (!target) return [];
  const rows = [];
  const tables = target.matches('table') ? [target] : Array.from(target.querySelectorAll('table'));
  tables.forEach((table, tableIndex) => {
    const headers = Array.from(table.querySelectorAll('thead th'));
    const excluded = headers.reduce((indexes, cell, index) => {
      if (/^actions?$/i.test(cell.textContent.trim())) indexes.push(index);
      return indexes;
    }, []);
    const cleanRow = (row) => Array.from(row.querySelectorAll(':scope > th, :scope > td'))
      .filter((cell, index) => !excluded.includes(index))
      .map((cell) => cell.innerText.replace(/\s+/g, ' ').trim());

    if (tableIndex > 0 && rows.length) rows.push([]);
    const headerRow = table.querySelector('thead tr');
    if (headerRow) rows.push(cleanRow(headerRow));
    table.querySelectorAll('tbody tr').forEach((row) => {
      const values = cleanRow(row);
      if (values.some(Boolean) && !values.some((value) => /^No .+ found\.?$/i.test(value))) rows.push(values);
    });
  });
  return rows;
}
function downloadTableCsv(targetId, filename) {
  const rows = adminExportRows(targetId);
  if (!rows.length) {
    alert('No records are available to download.');
    return;
  }
  if (window.XLSX) {
    const worksheet = XLSX.utils.aoa_to_sheet(rows);
    worksheet['!cols'] = rows.reduce((widths, row) => {
      row.forEach((value, index) => { widths[index] = { wch: Math.min(50, Math.max(widths[index]?.wch || 10, String(value).length + 2)) }; });
      return widths;
    }, []);
    const workbook = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(workbook, worksheet, 'List');
    XLSX.writeFile(workbook, filename.replace(/\.csv$/i, '.xlsx'));
    return;
  }
  const csv = rows.map((row) => row.map((value) => `"${value.replace(/"/g, '""')}"`).join(',')).join('\r\n');
  const link = document.createElement('a');
  const objectUrl = URL.createObjectURL(new Blob(['\ufeff' + csv], { type: 'text/csv;charset=utf-8' }));
  link.href = objectUrl;
  link.download = filename;
  document.body.appendChild(link);
  link.click();
  link.remove();
  window.setTimeout(() => URL.revokeObjectURL(objectUrl), 1000);
}
function printAdminTable(targetId, title) {
  const rows = adminExportRows(targetId);
  if (!rows.length) {
    alert('No records are available to download.');
    return;
  }
  if (!window.jspdf?.jsPDF) {
    alert('PDF generator could not be loaded. Please check your internet connection and try again.');
    return;
  }
  const { jsPDF } = window.jspdf;
  const documentPdf = new jsPDF({ orientation: 'landscape', unit: 'pt', format: 'a4' });
  documentPdf.setFontSize(16);
  documentPdf.text(title, 40, 36);
  documentPdf.setFontSize(9);
  documentPdf.setTextColor(100);
  documentPdf.text('Generated: ' + new Date().toLocaleString(), 40, 52);
  const firstDataRow = rows.findIndex((row) => row.length > 0);
  const head = firstDataRow >= 0 ? [rows[firstDataRow]] : [];
  const body = rows.slice(firstDataRow + 1);
  documentPdf.autoTable({
    head,
    body,
    startY: 64,
    theme: 'grid',
    styles: { fontSize: 7, cellPadding: 4, overflow: 'linebreak' },
    headStyles: { fillColor: [75, 30, 131] },
    margin: { left: 24, right: 24 },
  });
  documentPdf.save(title.toLowerCase().replace(/[^a-z0-9]+/g, '-') + '.pdf');
}
</script>
</body>
</html>
