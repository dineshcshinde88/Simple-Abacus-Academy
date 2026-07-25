<?php
declare(strict_types=1);
require_once __DIR__ . '/../plain/core.php';
require_once __DIR__ . '/../plain/controllers_worksheet_sub.php';
function assert_dynamic(bool $condition, string $message): void { if (!$condition) throw new RuntimeException($message); }
$expectedCounts = [2 => 7, 3 => 18, 4 => 18, 5 => 18, 6 => 21, 7 => 13];
$expectedVedicCounts = [1 => 22, 2 => 14, 3 => 16, 4 => 12];
assert_dynamic(worksheet_sub_dynamic_topic_specs(0) === [], 'Level 0 must remain static.');
assert_dynamic(worksheet_sub_dynamic_topic_specs(1) === [], 'Level 1 must remain static.');
foreach ($expectedVedicCounts as $level => $expectedCount) {
    $specs = worksheet_sub_vedic_topic_specs($level);
    assert_dynamic(count($specs) === $expectedCount, 'Vedic Level ' . $level . ' topic count mismatch.');
    $slugs = array_column($specs, 'slug');
    assert_dynamic(count(array_unique($slugs)) === $expectedCount, 'Duplicate Vedic topic slug.');
    assert_dynamic(in_array('calendar-method', $slugs, true) === ($level === 4), 'Calendar Method must only appear in Vedic Level 4.');
}
foreach ($expectedCounts as $level => $expectedCount) {
    $specs = worksheet_sub_dynamic_topic_specs($level);
    assert_dynamic(count($specs) === $expectedCount, 'Level ' . $level . ' topic count mismatch.');
    assert_dynamic(count(array_unique(array_column($specs, 'slug'))) === $expectedCount, 'Duplicate topic slug.');
    foreach ($specs as $spec) {
        $seen = [];
        for ($i = 0; $i < 60; $i++) {
            $item = worksheet_sub_generate_official_dynamic_item($spec);
            $seen[$item['question']] = true;
            assert_dynamic(count($item['options']) === 4 && count(array_unique($item['options'])) === 4, 'Invalid options: ' . $spec['name']);
            assert_dynamic(in_array($item['answer'], $item['options'], true), 'Missing answer option: ' . $spec['name']);
            if (in_array($spec['operation'], ['multiplication', 'division'], true)) {
                preg_match('/^(\d+) [x\/] (\d+)$/', $item['question'], $parts);
                assert_dynamic(count($parts) === 3, 'Invalid binary question.');
                [$leftDigits, $rightDigits] = $spec['digits'];
                assert_dynamic(strlen($parts[1]) === $leftDigits && strlen($parts[2]) === $rightDigits, 'Invalid binary digits.');
                $answer = $spec['operation'] === 'division' ? (int) $parts[1] / (int) $parts[2] : (int) $parts[1] * (int) $parts[2];
                assert_dynamic((float) $item['answer'] === (float) $answer, 'Invalid binary answer.');
                if ($spec['operation'] === 'division') assert_dynamic((int) $parts[1] % (int) $parts[2] === 0, 'Division remainder found.');
                continue;
            }
            $lines = explode(PHP_EOL, $item['question']);
            assert_dynamic(count($lines) === $spec['rows'], 'Invalid row count: ' . $spec['name']);
            $running = 0.0;
            foreach ($lines as $index => $line) {
                $line = preg_replace('/^\x{2007}/u', '', $line);
                assert_dynamic((bool) preg_match('/^[+-]?\d+(?:\.\d)?$/', $line), 'Invalid number format.');
                $absolute = abs((float) $line);
                assert_dynamic(strlen((string) (int) floor($absolute)) === $spec['digits'], 'Invalid operand digits.');
                if (($spec['decimal_places'] ?? 0) === 1) assert_dynamic((bool) preg_match('/\.\d$/', $line), 'Invalid decimal format.');
                if ($index > 0 && $spec['operation'] === 'addition') assert_dynamic(str_starts_with($line, '+'), 'Non-add operator found.');
                if ($index > 0 && $spec['operation'] === 'subtraction') assert_dynamic(str_starts_with($line, '-'), 'Non-subtract operator found.');
                $running += (float) $line;
                assert_dynamic($running >= -0.0001, 'Negative running total: ' . $spec['name']);
            }
            assert_dynamic(abs($running - (float) $item['answer']) < 0.0001, 'Invalid linear answer.');
        }
        assert_dynamic(count($seen) > 1, 'No refresh variation: ' . $spec['name']);
    }
}
