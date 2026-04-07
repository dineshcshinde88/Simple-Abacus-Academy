<?php
require_once __DIR__ . '/db.php';
header('Content-Type: application/json');

function int_param(string $key, int $default = 0): int {
    return isset($_POST[$key]) ? intval($_POST[$key]) : $default;
}

function str_param(string $key, string $default = ''): string {
    return isset($_POST[$key]) ? trim((string)$_POST[$key]) : $default;
}

function random_number(int $digits): int {
    if ($digits <= 1) {
        return rand(1, 9);
    }
    $min = (int) pow(10, $digits - 1);
    $max = (int) pow(10, $digits) - 1;
    return rand($min, $max);
}

function digits_count(int $value): int {
    return strlen((string) abs($value));
}

$operation = str_param('operation', 'Addition');
$total_questions = int_param('total_questions', 20);
$rows_count = int_param('rows_count', 5);
$digits1 = int_param('digits1', int_param('digits', 2));
$digits2 = int_param('digits2', int_param('digits', 2));
$digits3 = int_param('digits3', 0);
$digits4 = int_param('digits4', 0);
$student_name = str_param('student_name');
$date = str_param('date', date('Y-m-d'));
$level = int_param('level', 1);

$valid_operations = ['Addition', 'Subtraction', 'Multiplication', 'Division'];
if (!in_array($operation, $valid_operations, true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid operation type.']);
    exit;
}
if ($total_questions < 10 || $total_questions > 100) {
    echo json_encode(['success' => false, 'message' => 'Total questions must be between 10 and 100.']);
    exit;
}
if ($rows_count < 2 || $rows_count > 20) {
    echo json_encode(['success' => false, 'message' => 'Rows must be between 2 and 20.']);
    exit;
}
if ($digits1 < 1 || $digits1 > 5 || $digits2 < 1 || $digits2 > 5) {
    echo json_encode(['success' => false, 'message' => 'Number1 and Number2 must be between 1 and 5 digits.']);
    exit;
}
if ($digits3 < 0 || $digits3 > 5 || $digits4 < 0 || $digits4 > 5) {
    echo json_encode(['success' => false, 'message' => 'Number3 and Number4 must be between 0 and 5 digits.']);
    exit;
}

$questions = [];
$symbol = ['Addition' => '+', 'Subtraction' => '-', 'Multiplication' => '×', 'Division' => '÷'][$operation];

for ($i = 0; $i < $total_questions; $i++) {
    $answer = 0;
    $numbers = [];
    $question_text = '';

    if ($operation === 'Addition') {
        $digits_list = array_filter([$digits1, $digits2, $digits3, $digits4], function ($d) {
            return $d > 0;
        });
        foreach ($digits_list as $d) {
            $numbers[] = random_number((int) $d);
        }
        $answer = array_sum($numbers);
        $question_text = implode(' + ', $numbers);
    } elseif ($operation === 'Subtraction') {
        $a = random_number($digits1);
        $b = random_number($digits2);
        if ($a < $b) {
            [$a, $b] = [$b, $a];
        }
        $numbers = [$a, $b];
        $answer = $a - $b;
        $question_text = "{$a} - {$b}";
    } elseif ($operation === 'Multiplication') {
        $a = random_number($digits1);
        $b = random_number($digits2);
        $numbers = [$a, $b];
        $answer = $a * $b;
        $question_text = "{$a} × {$b}";
    } elseif ($operation === 'Division') {
        $attempts = 0;
        do {
            $b = random_number($digits2);
            $quotient = random_number($digits1);
            $a = $b * $quotient;
            $attempts++;
        } while (digits_count($a) > $digits1 && $attempts < 500);
        if (digits_count($a) > $digits1) {
            $b = rand(1, 9);
            $quotient = rand(1, 9);
            $a = $b * $quotient;
        }
        $numbers = [$a, $b];
        $answer = (int) ($a / $b);
        $question_text = "{$a} ÷ {$b}";
    }

    $questions[] = [
        'question' => $question_text,
        'numbers' => $numbers,
        'operator' => $symbol,
        'answer' => $answer
    ];
}

$conn = db_connect();
$sql = 'INSERT INTO worksheets (student_name, operation, digits, total_questions, rows_count, level, data) VALUES (?, ?, ?, ?, ?, ?, ?)';
$stmt = db_query($conn, $sql, [
    $student_name !== '' ? $student_name : 'Unnamed',
    $operation,
    $digits1,
    $total_questions,
    $rows_count,
    $level,
    json_encode($questions, JSON_UNESCAPED_UNICODE)
]);
$id = $stmt->insert_id;
$stmt->close();
$conn->close();

$meta = [
    'student_name' => $student_name,
    'operation' => $operation,
    'digits' => $digits1,
    'total_questions' => $total_questions,
    'rows_count' => $rows_count,
    'level' => $level,
    'date' => $date
];

echo json_encode([
    'success' => true,
    'id' => $id,
    'data' => $questions,
    'meta' => $meta
]);
?>
