let lastWorksheetId = null;

const form = document.getElementById('worksheetForm');
const preview = document.getElementById('preview');
const downloadBtn = document.getElementById('downloadPdf');
const printBtn = document.getElementById('printWorksheet');
const answersToggle = document.getElementById('showAnswersInput');
const messageBox = document.getElementById('message');

function showMessage(text, type = 'info') {
  messageBox.textContent = text;
  messageBox.className = `alert alert-${type}`;
  messageBox.classList.remove('d-none');
  setTimeout(() => messageBox.classList.add('d-none'), 4000);
}

function renderWorksheet(data, meta) {
  const cols = Number(meta.rows_count);
  const now = new Date();
  const timeString = now.toLocaleTimeString();
  const showAnswers = answersToggle.checked;

  preview.innerHTML = `
    <div class="toolbar">
      <span class="badge-soft">${meta.operation} • ${meta.total_questions} questions</span>
      <div class="form-check form-switch">
        <input class="form-check-input" type="checkbox" id="showAnswersPreview" ${showAnswers ? 'checked' : ''}>
        <label class="form-check-label" for="showAnswersPreview">Show Answers</label>
      </div>
    </div>
    <h2>Abacus Worksheet</h2>
    <div class="meta">
      <div><strong>Name:</strong> ${meta.student_name || '__________'}</div>
      <div><strong>Date:</strong> ${meta.date}</div>
      <div><strong>Time:</strong> ${timeString}</div>
    </div>
    <div class="question-grid" style="grid-template-columns: repeat(${cols}, minmax(180px, 1fr));"></div>
  `;

  const grid = preview.querySelector('.question-grid');

  data.forEach((item, idx) => {
    const card = document.createElement('div');
    card.className = 'question-card';

    const header = document.createElement('h4');
    header.textContent = `Q${idx + 1}.`;
    card.appendChild(header);

    const list = document.createElement('div');
    list.className = 'question-nums';

    const numbers = item.numbers || [];
    numbers.forEach((num, index) => {
      const line = document.createElement('div');
      line.className = 'question-line';

      const op = document.createElement('span');
      op.className = 'question-operator';
      op.textContent = (index === numbers.length - 1) ? item.operator : '';

      const value = document.createElement('span');
      value.textContent = num;

      line.appendChild(op);
      line.appendChild(value);
      list.appendChild(line);
    });

    card.appendChild(list);

    const footer = document.createElement('div');
    footer.className = 'question-footer';
    footer.textContent = showAnswers ? item.answer : '________';
    card.appendChild(footer);

    grid.appendChild(card);
  });

  preview.querySelector('#showAnswersPreview').addEventListener('change', (e) => {
    answersToggle.checked = e.target.checked;
    renderWorksheet(data, meta);
  });
}

form.addEventListener('submit', async (e) => {
  e.preventDefault();
  const formData = new FormData(form);

  try {
    const response = await fetch('generate.php', {
      method: 'POST',
      body: formData
    });

    const text = await response.text();
    let result = null;
    try {
      result = JSON.parse(text);
    } catch (err) {
      showMessage('Server error: unable to parse response. Check PHP errors/logs.', 'danger');
      console.error(text);
      return;
    }

    if (!result.success) {
      showMessage(result.message || 'Failed to generate worksheet.', 'danger');
      return;
    }

    lastWorksheetId = result.id;
    downloadBtn.disabled = false;
    printBtn.disabled = false;
    renderWorksheet(result.data, result.meta);
    showMessage('Worksheet generated successfully.', 'success');
  } catch (error) {
    showMessage('Network or server error. Check XAMPP/PHP status.', 'danger');
    console.error(error);
  }
});

downloadBtn.addEventListener('click', () => {
  if (!lastWorksheetId) {
    showMessage('Generate a worksheet first.', 'warning');
    return;
  }
  window.location.href = `download-pdf.php?id=${lastWorksheetId}`;
});

printBtn.addEventListener('click', () => {
  if (!lastWorksheetId) {
    showMessage('Generate a worksheet first.', 'warning');
    return;
  }
  window.print();
});
