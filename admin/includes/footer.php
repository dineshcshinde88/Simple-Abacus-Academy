    </div>
  </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
function adminExportRows(targetId) {
  const target = document.getElementById(targetId);
  if (!target) return [];
  return Array.from(target.querySelectorAll('tr')).map((row) =>
    Array.from(row.querySelectorAll('th,td')).filter((cell) => cell.textContent.trim() !== 'Actions').map((cell) => cell.innerText.replace(/\s+/g, ' ').trim())
  ).filter((row) => row.length);
}
function downloadTableCsv(targetId, filename) {
  const csv = adminExportRows(targetId).map((row) => row.map((value) => `"${value.replace(/"/g, '""')}"`).join(',')).join('\r\n');
  const link = document.createElement('a');
  link.href = URL.createObjectURL(new Blob(['\ufeff' + csv], { type: 'text/csv;charset=utf-8' }));
  link.download = filename;
  link.click();
  URL.revokeObjectURL(link.href);
}
function printAdminTable(targetId, title) {
  const target = document.getElementById(targetId);
  if (!target) return;
  const popup = window.open('', '_blank');
  if (!popup) return;
  popup.document.write(`<!doctype html><html><head><title>${title}</title><style>body{font-family:Arial;padding:20px}table{width:100%;border-collapse:collapse;font-size:11px}th,td{border:1px solid #bbb;padding:6px;text-align:left}button,form,.btn,img{display:none!important}h2{margin:0 0 16px}</style></head><body><h2>${title}</h2>${target.innerHTML}</body></html>`);
  popup.document.close();
  popup.focus();
  popup.print();
}
</script>
</body>
</html>
