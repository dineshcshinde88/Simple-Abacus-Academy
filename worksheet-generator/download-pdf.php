<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/fpdf/fpdf.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    die('Invalid worksheet ID.');
}

$conn = db_connect();
$stmt = db_query($conn, 'SELECT * FROM worksheets WHERE id = ?', [$id]);
$result = $stmt->get_result();
$worksheet = $result->fetch_assoc();
$stmt->close();
$conn->close();

if (!$worksheet) {
    die('Worksheet not found.');
}

$data = json_decode($worksheet['data'], true) ?? [];
$operation = $worksheet['operation'];
$student_name = $worksheet['student_name'];
$created_at = date('Y-m-d', strtotime($worksheet['created_at']));

$pdf = new FPDF('P', 'mm', 'A4');
$pdf->SetAutoPageBreak(true, 15);

// Worksheet page
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, 'Abacus Worksheet', 0, 1, 'C');
$pdf->Ln(2);

$pdf->SetFont('Arial', '', 11);
$pdf->Cell(0, 8, "Name: {$student_name}", 0, 1);
$pdf->Cell(0, 8, "Date: {$created_at}", 0, 1);
$pdf->Cell(0, 8, "Operation: {$operation}", 0, 1);
$pdf->Ln(4);

$pdf->SetFont('Arial', '', 12);
$columns = 3;
$cellWidth = 190 / $columns;
$cellHeight = 8;

foreach ($data as $index => $item) {
    $q = ($index + 1) . '. ' . $item['question'] . ' = ______';
    $pdf->Cell($cellWidth, $cellHeight, $q, 0, 0);
    if (($index + 1) % $columns === 0) {
        $pdf->Ln();
    }
}
$pdf->Ln(10);

// Answer sheet page
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 10, 'Answer Sheet', 0, 1, 'C');
$pdf->Ln(2);

$pdf->SetFont('Arial', '', 12);
foreach ($data as $index => $item) {
    $q = ($index + 1) . '. ' . $item['question'] . ' = ' . $item['answer'];
    $pdf->Cell($cellWidth, $cellHeight, $q, 0, 0);
    if (($index + 1) % $columns === 0) {
        $pdf->Ln();
    }
}

$pdf->Output('D', 'worksheet-' . $id . '.pdf');
?>
