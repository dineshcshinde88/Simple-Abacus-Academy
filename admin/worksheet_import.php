<?php
$pageTitle = 'Worksheet Import';
$activeMenu = 'worksheet_import';
require_once __DIR__ . '/includes/header.php';

$backendRoot = dirname(__DIR__) . '/backend';
require_once $backendRoot . '/plain/core.php';
require_once $backendRoot . '/plain/auth.php';
require_once $backendRoot . '/plain/controllers_subscriptions.php';
require_once $backendRoot . '/plain/controllers_worksheet_sub.php';
require_once $backendRoot . '/plain/worksheet_docx_importer.php';
load_env_file($backendRoot . '/.env');

$results = [];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $force = isset($_POST['force']) && $_POST['force'] === '1';
        $results = worksheet_import_uploaded_docx_files('files', $force);
    } catch (Throwable $e) {
        $errors[] = $e->getMessage();
    }
}

try {
    ensure_worksheet_docx_import_schema();
    $summary = db_all(
        'SELECT wl.level_name, COUNT(DISTINCT wp.id) AS papers, COUNT(wq.id) AS questions
         FROM worksheet_levels wl
         LEFT JOIN worksheet_papers wp ON wp.level_id = wl.id
         LEFT JOIN worksheet_questions wq ON wq.paper_id = wp.id
         WHERE wl.level_name LIKE "Abacus Worksheet%"
         GROUP BY wl.id, wl.level_name
         ORDER BY wl.level_name ASC'
    );
    $runs = db_all('SELECT * FROM worksheet_import_runs ORDER BY imported_at DESC LIMIT 10');
} catch (Throwable $e) {
    $summary = [];
    $runs = [];
    $errors[] = 'Unable to load worksheet import summary: ' . $e->getMessage();
}
?>

<?php if ($errors): ?>
  <div class="alert alert-danger">
    <?php foreach ($errors as $error): ?>
      <div><?php echo htmlspecialchars($error); ?></div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php if ($results): ?>
  <div class="alert alert-success">
    <div class="fw-semibold mb-2">Import completed</div>
    <?php foreach ($results as $result): ?>
      <div>
        <?php echo htmlspecialchars(($result['skipped'] ?? false) ? 'Skipped' : 'Imported'); ?>
        <?php echo htmlspecialchars($result['sourceFile'] ?? 'file'); ?>:
        <?php echo (int) ($result['papers'] ?? 0); ?> papers,
        <?php echo (int) ($result['questions'] ?? 0); ?> questions.
        <?php echo htmlspecialchars($result['message'] ?? ''); ?>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<div class="row g-4">
  <div class="col-lg-5">
    <div class="card border-0 shadow-sm">
      <div class="card-body">
        <h2 class="h6 mb-3">Import worksheet Word files</h2>
        <form method="post" enctype="multipart/form-data" class="vstack gap-3">
          <div>
            <label class="form-label">DOCX files</label>
            <input class="form-control" type="file" name="files[]" accept=".docx" multiple required>
            <div class="form-text">Upload Level 0 and/or Level 1 worksheet subscription paper Word files.</div>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="force" value="1" id="forceImport">
            <label class="form-check-label" for="forceImport">Re-import and replace existing imported papers for these levels</label>
          </div>
          <button type="submit" class="btn btn-primary">Import Worksheets</button>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-7">
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-body">
        <h2 class="h6 mb-3">Current imported worksheet papers</h2>
        <div class="table-responsive">
          <table class="table table-sm align-middle">
            <thead>
              <tr><th>Level</th><th class="text-end">Papers</th><th class="text-end">Questions</th></tr>
            </thead>
            <tbody>
              <?php foreach ($summary as $row): ?>
                <tr>
                  <td><?php echo htmlspecialchars((string) $row['level_name']); ?></td>
                  <td class="text-end"><?php echo (int) $row['papers']; ?></td>
                  <td class="text-end"><?php echo (int) $row['questions']; ?></td>
                </tr>
              <?php endforeach; ?>
              <?php if (!$summary): ?>
                <tr><td colspan="3" class="text-muted text-center py-4">No worksheet papers imported yet.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="card border-0 shadow-sm">
      <div class="card-body">
        <h2 class="h6 mb-3">Recent import runs</h2>
        <div class="table-responsive">
          <table class="table table-sm align-middle">
            <thead>
              <tr><th>File</th><th>Level</th><th class="text-end">Papers</th><th class="text-end">Questions</th><th>Imported</th></tr>
            </thead>
            <tbody>
              <?php foreach ($runs as $run): ?>
                <tr>
                  <td><?php echo htmlspecialchars((string) $run['source_file']); ?></td>
                  <td>Level <?php echo (int) $run['level_number']; ?></td>
                  <td class="text-end"><?php echo (int) $run['paper_count']; ?></td>
                  <td class="text-end"><?php echo (int) $run['question_count']; ?></td>
                  <td><?php echo htmlspecialchars((string) $run['imported_at']); ?></td>
                </tr>
              <?php endforeach; ?>
              <?php if (!$runs): ?>
                <tr><td colspan="5" class="text-muted text-center py-4">No imports yet.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
