<?php
require_once __DIR__ . '/../db.php';
$filter = isset($_GET['student']) ? trim($_GET['student']) : '';
$conn = db_connect();

if ($filter !== '') {
    $stmt = db_query($conn, 'SELECT * FROM worksheets WHERE student_name LIKE ? ORDER BY created_at DESC', ['%' . $filter . '%']);
} else {
    $stmt = db_query($conn, 'SELECT * FROM worksheets ORDER BY created_at DESC');
}
$result = $stmt->get_result();
$worksheets = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Worksheet Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h1 class="h3 mb-0">Admin Dashboard</h1>
      <a class="btn btn-outline-primary" href="../index.php">Back to Generator</a>
    </div>

    <form class="row g-2 mb-3" method="get">
      <div class="col-md-4">
        <input type="text" name="student" class="form-control" placeholder="Filter by student name" value="<?php echo htmlspecialchars($filter); ?>">
      </div>
      <div class="col-md-2">
        <button type="submit" class="btn btn-primary w-100">Filter</button>
      </div>
    </form>

    <div class="table-responsive">
      <table class="table table-striped table-bordered">
        <thead class="table-light">
          <tr>
            <th>ID</th>
            <th>Student</th>
            <th>Operation</th>
            <th>Digits</th>
            <th>Total Questions</th>
            <th>Rows</th>
            <th>Level</th>
            <th>Created</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($worksheets)) : ?>
            <tr>
              <td colspan="9" class="text-center">No worksheets found.</td>
            </tr>
          <?php else : ?>
            <?php foreach ($worksheets as $ws) : ?>
              <tr>
                <td><?php echo $ws['id']; ?></td>
                <td><?php echo htmlspecialchars($ws['student_name']); ?></td>
                <td><?php echo htmlspecialchars($ws['operation']); ?></td>
                <td><?php echo $ws['digits']; ?></td>
                <td><?php echo $ws['total_questions']; ?></td>
                <td><?php echo $ws['rows_count']; ?></td>
                <td><?php echo $ws['level']; ?></td>
                <td><?php echo $ws['created_at']; ?></td>
                <td>
                  <a class="btn btn-sm btn-outline-secondary" href="../download-pdf.php?id=<?php echo $ws['id']; ?>">Download PDF</a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</body>
</html>
