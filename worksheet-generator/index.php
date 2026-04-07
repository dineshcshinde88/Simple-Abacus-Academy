<?php
// Worksheet Generator - main UI
$today = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Worksheet Generator</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;600&family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
  <div class="page">
    <div class="hero">
      <h1>Worksheet Generator</h1>
      <p>Build fast abacus-style worksheets with multi-line questions, instant preview, and PDF export.</p>
    </div>

    <div id="message" class="alert d-none" role="alert"></div>

    <div class="grid">
      <div class="card form-section">
        <form id="worksheetForm">
          <div class="row g-3">
            <div class="col-md-4">
              <label for="operation">Operation To Perform</label>
              <select class="form-select" id="operation" name="operation" required>
                <option value="Addition">Addition</option>
                <option value="Subtraction">Subtraction</option>
                <option value="Multiplication">Multiplication</option>
                <option value="Division">Division</option>
              </select>
            </div>
            <div class="col-md-4">
              <label for="rows_count">Total Numbers of Rows</label>
              <input type="number" class="form-control" id="rows_count" name="rows_count" min="2" max="20" value="4" required>
            </div>
            <div class="col-md-4">
              <label for="total_questions">Total No of Questions</label>
              <input type="number" class="form-control" id="total_questions" name="total_questions" min="10" max="100" value="10" required>
            </div>
            <div class="col-md-4">
              <label for="digits1">Number1 (Length Upto)</label>
              <input type="number" class="form-control" id="digits1" name="digits1" min="1" max="5" value="3" required>
            </div>
            <div class="col-md-4">
              <label for="digits2">Number2 (Length Upto)</label>
              <input type="number" class="form-control" id="digits2" name="digits2" min="1" max="5" value="2" required>
            </div>
            <div class="col-md-4">
              <label for="digits3">Number3 (Length Upto)</label>
              <input type="number" class="form-control" id="digits3" name="digits3" min="0" max="5" value="2">
            </div>
            <div class="col-md-4">
              <label for="digits4">Number4 (Length Upto)</label>
              <input type="number" class="form-control" id="digits4" name="digits4" min="0" max="5" value="2">
            </div>
            <div class="col-md-4">
              <label for="student_name">Student Name</label>
              <input type="text" class="form-control" id="student_name" name="student_name" placeholder="Enter student name">
            </div>
            <div class="col-md-4">
              <label for="date">Date</label>
              <input type="date" class="form-control" id="date" name="date" value="<?php echo $today; ?>" required>
            </div>
            <div class="col-md-4">
              <label for="timer_minutes">Timer (minutes, optional)</label>
              <input type="number" class="form-control" id="timer_minutes" name="timer_minutes" min="1" max="120" placeholder="e.g., 10">
            </div>
            <div class="col-12">
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="showAnswersInput">
                <label class="form-check-label" for="showAnswersInput">Show Answers in Preview</label>
              </div>
            </div>
          </div>

          <div class="action-row">
            <div class="action-left">
              <button type="submit" class="btn btn-accent">Start Solving Online</button>
            </div>
            <div class="action-right">
              <button type="button" class="btn btn-outline-accent" id="downloadPdf" disabled>Download PDF</button>
              <button type="button" class="btn btn-outline-secondary" id="printWorksheet" disabled>Print</button>
            </div>
          </div>
          <p class="small-note mt-3">Tip: Extra number fields (3 & 4) are used only for addition.</p>
        </form>
      </div>

      <div class="card preview-section">
        <div class="preview" id="preview">
          <h2>Preview</h2>
          <p class="small-note">Generate a worksheet to see the questions here.</p>
        </div>
      </div>
    </div>
  </div>

  <script src="assets/app.js"></script>
</body>
</html>
