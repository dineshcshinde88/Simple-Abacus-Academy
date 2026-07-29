<?php

function worksheet_sub_table_has_column(string $table, string $column): bool
{
    return (int) db_value(
        'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND COLUMN_NAME = :column',
        ['table' => $table, 'column' => $column]
    ) > 0;
}

function worksheet_sub_ensure_practice_column(string $column, string $definition): void
{
    if (worksheet_sub_table_has_column('worksheet_practices', $column)) {
        return;
    }

    try {
        db_exec_sql("ALTER TABLE worksheet_practices ADD COLUMN {$column} {$definition}");
    } catch (Throwable $e) {
        if (!str_contains($e->getMessage(), 'Duplicate column')) {
            throw $e;
        }
    }
}
function ensure_worksheet_sub_schema(): void
{
    db_exec_sql(
        'CREATE TABLE IF NOT EXISTS worksheet_levels (
            id VARCHAR(191) NOT NULL PRIMARY KEY,
            level_name VARCHAR(191) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    db_exec_sql(
        'CREATE TABLE IF NOT EXISTS worksheet_topics (
            id VARCHAR(191) NOT NULL PRIMARY KEY,
            level_id VARCHAR(191) NOT NULL,
            topic_name VARCHAR(255) NOT NULL,
            total_questions INT NOT NULL DEFAULT 0,
            INDEX idx_worksheet_topics_level_id (level_id),
            CONSTRAINT fk_worksheet_topics_level
                FOREIGN KEY (level_id) REFERENCES worksheet_levels(id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    db_exec_sql(
        'CREATE TABLE IF NOT EXISTS worksheet_questions (
            id VARCHAR(191) NOT NULL PRIMARY KEY,
            topic_id VARCHAR(191) NOT NULL,
            question VARCHAR(255) NOT NULL,
            answer VARCHAR(100) NOT NULL,
            INDEX idx_worksheet_questions_topic_id (topic_id),
            CONSTRAINT fk_worksheet_questions_topic
                FOREIGN KEY (topic_id) REFERENCES worksheet_topics(id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    db_exec_sql(
        'CREATE TABLE IF NOT EXISTS worksheet_practices (
            id VARCHAR(191) NOT NULL PRIMARY KEY,
            student_id VARCHAR(191) NOT NULL,
            topic_id VARCHAR(191) NOT NULL,
            score INT NOT NULL DEFAULT 0,
            accuracy DECIMAL(5,2) NOT NULL DEFAULT 0,
            total_questions INT NOT NULL DEFAULT 0,
            correct_answers INT NOT NULL DEFAULT 0,
            time_taken INT NOT NULL DEFAULT 0,
            status VARCHAR(40) NOT NULL DEFAULT "Needs Practice",
            mode VARCHAR(30) NOT NULL DEFAULT "practice",
            speed_tier INT NULL,
            created_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
            INDEX idx_worksheet_practices_student_id (student_id),
            INDEX idx_worksheet_practices_topic_id (topic_id),
            CONSTRAINT fk_worksheet_practices_topic
                FOREIGN KEY (topic_id) REFERENCES worksheet_topics(id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    if (function_exists('ensure_billing_schema')) {
        ensure_billing_schema();
    }
    worksheet_sub_ensure_practice_column('mode', 'VARCHAR(30) NOT NULL DEFAULT "practice" AFTER status');
    worksheet_sub_ensure_practice_column('speed_tier', 'INT NULL AFTER mode');
    worksheet_sub_ensure_practice_column('subscription_id', 'VARCHAR(191) NULL AFTER student_id');
    worksheet_sub_ensure_practice_column('program_type', 'VARCHAR(40) NOT NULL DEFAULT "abacus" AFTER subscription_id');
    worksheet_sub_ensure_practice_column('level_id', 'VARCHAR(191) NULL AFTER program_type');
    worksheet_sub_ensure_practice_column('subtopic_code', 'VARCHAR(191) NULL AFTER topic_id');
    worksheet_sub_ensure_practice_column('attempted_count', 'INT NOT NULL DEFAULT 0 AFTER total_questions');
    worksheet_sub_ensure_practice_column('wrong_count', 'INT NOT NULL DEFAULT 0 AFTER correct_answers');
    worksheet_sub_ensure_practice_column('skipped_count', 'INT NOT NULL DEFAULT 0 AFTER wrong_count');
    worksheet_sub_ensure_practice_column('percentage', 'DECIMAL(5,2) NOT NULL DEFAULT 0 AFTER accuracy');
    worksheet_sub_ensure_practice_column('answers_json', 'LONGTEXT NULL AFTER speed_tier');
    worksheet_sub_ensure_practice_column('review_json', 'LONGTEXT NULL AFTER answers_json');
    worksheet_sub_ensure_practice_column('started_at', 'DATETIME NULL AFTER review_json');
    worksheet_sub_ensure_practice_column('completed_at', 'DATETIME NULL AFTER started_at');
    worksheet_sub_ensure_practice_column('duration_seconds', 'INT NOT NULL DEFAULT 0 AFTER completed_at');
    worksheet_sub_ensure_practice_column('passed', 'TINYINT(1) NOT NULL DEFAULT 0 AFTER duration_seconds');
    worksheet_sub_ensure_practice_column('updated_at', 'DATETIME NULL AFTER created_at');
    db_exec_sql(
        'CREATE TABLE IF NOT EXISTS worksheet_attempts (
            id VARCHAR(191) NOT NULL PRIMARY KEY,
            student_id VARCHAR(191) NOT NULL,
            level_id VARCHAR(191) NULL,
            paper_id VARCHAR(191) NULL,
            topic_id VARCHAR(191) NULL,
            worksheet_name VARCHAR(255) NOT NULL,
            mode VARCHAR(30) NOT NULL DEFAULT "practice",
            total_questions INT NOT NULL DEFAULT 0,
            attempted INT NOT NULL DEFAULT 0,
            correct_answers INT NOT NULL DEFAULT 0,
            wrong_answers INT NOT NULL DEFAULT 0,
            score INT NOT NULL DEFAULT 0,
            percentage DECIMAL(5,2) NOT NULL DEFAULT 0,
            status VARCHAR(40) NOT NULL DEFAULT "Needs Practice",
            answers_json LONGTEXT NULL,
            review_json LONGTEXT NULL,
            started_at DATETIME NULL,
            completed_at DATETIME NOT NULL,
            duration_seconds INT NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX idx_worksheet_attempts_student (student_id),
            INDEX idx_worksheet_attempts_paper (paper_id),
            INDEX idx_worksheet_attempts_topic (topic_id),
            INDEX idx_worksheet_attempts_completed (completed_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    db_exec_sql(
        'CREATE TABLE IF NOT EXISTS worksheet_competition_unlocks (
            id VARCHAR(191) NOT NULL PRIMARY KEY,
            student_id VARCHAR(191) NOT NULL,
            topic_id VARCHAR(191) NOT NULL,
            unlocked_tier INT NOT NULL DEFAULT 15,
            passing_percentage DECIMAL(5,2) NOT NULL DEFAULT 90,
            updated_at DATETIME NOT NULL,
            UNIQUE KEY uniq_worksheet_competition_topic (student_id, topic_id),
            INDEX idx_worksheet_competition_student (student_id),
            INDEX idx_worksheet_competition_topic (topic_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    db_exec_sql(
        'CREATE TABLE IF NOT EXISTS worksheet_competition_config (
            id TINYINT PRIMARY KEY,
            passing_percentage DECIMAL(5,2) NOT NULL DEFAULT 90,
            updated_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    db_exec_sql(
        'INSERT INTO worksheet_competition_config (id, passing_percentage, updated_at)
         VALUES (1, 90, :updated_at)
         ON DUPLICATE KEY UPDATE passing_percentage = passing_percentage',
        ['updated_at' => now_sql()]
    );
    $levels = db_all('SELECT id, level_name FROM levels WHERE level_name LIKE "%Worksheet%" ORDER BY level_name ASC');
    foreach ($levels as $level) {
        db_exec_sql(
            'INSERT INTO worksheet_levels (id, level_name)
             VALUES (:id, :level_name)
             ON DUPLICATE KEY UPDATE level_name = VALUES(level_name)',
            ['id' => $level['id'], 'level_name' => $level['level_name']]
        );
    }
}

function worksheet_sub_status(float $accuracy): string
{
    if ($accuracy >= 90) {
        return 'Excellent';
    }
    if ($accuracy >= 70) {
        return 'Good';
    }
    return 'Needs Practice';
}

function worksheet_sub_is_vedic_level_name(string $levelName): bool
{
    return str_contains(strtolower($levelName), 'vedic');
}

function worksheet_sub_is_abacus_level_name(string $levelName): bool
{
    return str_contains(strtolower($levelName), 'abacus');
}

function worksheet_sub_vedic_topic_specs(int $levelNumber): array
{
    $levels = [
        1 => [
            ['slug' => 'complement-base-100', 'name' => 'Complement: Base 100', 'kind' => 'complement', 'base' => 100, 'range' => [11, 99]],
            ['slug' => 'complement-base-1000', 'name' => 'Complement: Base 1000', 'kind' => 'complement', 'base' => 1000, 'range' => [101, 999]],
            ['slug' => 'complement-base-10000', 'name' => 'Complement: Base 10,000', 'kind' => 'complement', 'base' => 10000, 'range' => [1001, 9999]],
            ['slug' => 'subtraction-2-digit', 'name' => 'Subtraction: 2 Digit', 'kind' => 'subtraction', 'a' => [50, 99], 'b' => [11, 49], 'borrow' => true],
            ['slug' => 'subtraction-3-digit', 'name' => 'Subtraction: 3 Digit', 'kind' => 'subtraction', 'a' => [500, 999], 'b' => [100, 499], 'borrow' => true],
            ['slug' => 'subtraction-4-digit', 'name' => 'Subtraction: 4 Digit', 'kind' => 'subtraction', 'a' => [5000, 9999], 'b' => [1000, 4999]],
            ['slug' => 'multiplication-2d-x-2d', 'name' => 'Multiplication: 2D x 2D', 'kind' => 'multiply', 'a' => [10, 99], 'b' => [11, 19]],
            ['slug' => 'multiplication-3d-x-2d', 'name' => 'Multiplication: 3D x 2D', 'kind' => 'multiply', 'a' => [100, 999], 'b' => [11, 19]],
            ['slug' => 'multiplication-4d-x-2d', 'name' => 'Multiplication: 4D x 2D', 'kind' => 'multiply', 'a' => [1000, 9999], 'b' => [11, 19]],
            ['slug' => 'multiplication-5d-x-2d', 'name' => 'Multiplication: 5D x 2D', 'kind' => 'multiply', 'a' => [10000, 99999], 'b' => [11, 19]],
            ['slug' => 'multiplication-6d-x-2d', 'name' => 'Multiplication: 6D x 2D', 'kind' => 'multiply', 'a' => [100000, 999999], 'b' => [11, 19]],
            ['slug' => '99-multi-2d-x-2d', 'name' => '99 Multi: 2D x 2D', 'kind' => 'fixed_multiply', 'a' => [10, 98], 'fixed' => 99],
            ['slug' => '99-multi-3d-x-3d', 'name' => '99 Multi: 3D x 3D', 'kind' => 'fixed_multiply', 'a' => [100, 998], 'fixed' => 999],
            ['slug' => '99-multi-4d-x-4d', 'name' => '99 Multi: 4D x 4D', 'kind' => 'fixed_multiply', 'a' => [1000, 9998], 'fixed' => 9999],
            ['slug' => 'above-base-10', 'name' => 'Above Base: 10', 'kind' => 'multiply_same_range', 'range' => [11, 19]],
            ['slug' => 'above-base-100', 'name' => 'Above Base: 100', 'kind' => 'multiply_same_range', 'range' => [101, 125]],
            ['slug' => 'above-base-1000', 'name' => 'Above Base: 1000', 'kind' => 'multiply_same_range', 'range' => [1001, 1125]],
            ['slug' => 'below-base-100', 'name' => 'Below Base: 100', 'kind' => 'multiply_same_range', 'range' => [85, 99]],
            ['slug' => 'below-base-1000', 'name' => 'Below Base: 1000', 'kind' => 'multiply_same_range', 'range' => [980, 999]],
            ['slug' => 'below-base-10000', 'name' => 'Below Base: 10,000', 'kind' => 'multiply_same_range', 'range' => [9980, 9999]],
            ['slug' => 'vertical-crosswise-2d', 'name' => 'Vertical Crosswise (2D)', 'kind' => 'multiply_no_zero', 'a' => [21, 88], 'b' => [21, 88]],
            ['slug' => 'square-2-digit', 'name' => 'Square (2 Digit)', 'kind' => 'power_prompt', 'power' => 2, 'range' => [11, 99], 'prompt' => 'Find square of'],
        ],
        2 => [
            ['slug' => 'square-3-digit', 'name' => 'Square (3 Digit)', 'kind' => 'power_prompt', 'power' => 2, 'range' => [100, 999], 'prompt' => 'Find square of'],
            ['slug' => 'square-root-3d-4d', 'name' => 'Square Root (3D & 4D)', 'kind' => 'root_prompt', 'power' => 2, 'range' => [10, 99], 'prompt' => 'Find square root'],
            ['slug' => 'cube-2-digit', 'name' => 'Cube (2 Digit)', 'kind' => 'power_prompt', 'power' => 3, 'range' => [11, 99], 'prompt' => 'Find cube of'],
            ['slug' => 'cube-root-4-digit', 'name' => 'Cube Root (4 Digit)', 'kind' => 'root_prompt', 'power' => 3, 'range' => [10, 21], 'prompt' => 'Find cube root'],
            ['slug' => 'cube-root-5-digit', 'name' => 'Cube Root (5 Digit)', 'kind' => 'root_prompt', 'power' => 3, 'range' => [22, 46], 'prompt' => 'Find cube root'],
            ['slug' => 'cube-root-6-digit', 'name' => 'Cube Root (6 Digit)', 'kind' => 'root_prompt', 'power' => 3, 'range' => [47, 99], 'prompt' => 'Find cube root'],
            ['slug' => 'first-same-last-add-10', 'name' => 'First Same, Last Add 10', 'kind' => 'first_same_last_add_10'],
            ['slug' => 'last-same-first-add-10', 'name' => 'Last Same, First Add 10', 'kind' => 'last_same_first_add_10'],
            ['slug' => 'division-above-base-10', 'name' => 'Division: Above Base 10', 'kind' => 'division_decimal', 'a' => [100, 999], 'b_set' => [11, 12, 13]],
            ['slug' => 'division-below-base-100', 'name' => 'Division: Below Base 100', 'kind' => 'division_decimal', 'a' => [100, 999], 'b' => [89, 99]],
            ['slug' => 'factorwise-multi-4d', 'name' => 'Factorwise Multi (4D)', 'kind' => 'factor_multiply', 'a' => [1000, 9999]],
            ['slug' => 'factorwise-multi-5d', 'name' => 'Factorwise Multi (5D)', 'kind' => 'factor_multiply', 'a' => [10000, 99999]],
            ['slug' => 'factorwise-div-4d', 'name' => 'Factorwise Div (4D)', 'kind' => 'factor_division', 'a' => [1000, 9999]],
            ['slug' => 'factorwise-div-5d', 'name' => 'Factorwise Div (5D)', 'kind' => 'factor_division', 'a' => [10000, 99999]],
        ],
        3 => [
            ['slug' => 'division-by-5-3d', 'name' => 'Division by 5 (3D)', 'kind' => 'fixed_division_decimal', 'a' => [100, 999], 'b' => 5],
            ['slug' => 'division-by-5-4d', 'name' => 'Division by 5 (4D)', 'kind' => 'fixed_division_decimal', 'a' => [1000, 9999], 'b' => 5],
            ['slug' => 'division-by-25-3d', 'name' => 'Division by 25 (3D)', 'kind' => 'fixed_division_decimal', 'a' => [100, 999], 'b' => 25],
            ['slug' => 'division-by-25-4d', 'name' => 'Division by 25 (4D)', 'kind' => 'fixed_division_decimal', 'a' => [1000, 9999], 'b' => 25],
            ['slug' => 'division-by-50', 'name' => 'Division by 50', 'kind' => 'fixed_division_decimal', 'a' => [1000, 9999], 'b' => 50],
            ['slug' => 'division-by-125', 'name' => 'Division by 125', 'kind' => 'fixed_division_decimal', 'a' => [1000, 9999], 'b' => 125],
            ['slug' => 'division-general-method', 'name' => 'Division: General Method', 'kind' => 'division_decimal_no_zero_divisor', 'a' => [1000, 9999], 'b' => [14, 89]],
            ['slug' => 'fraction-addition', 'name' => 'Fraction Addition', 'kind' => 'fraction', 'op' => '+'],
            ['slug' => 'fraction-subtraction', 'name' => 'Fraction Subtraction', 'kind' => 'fraction', 'op' => '-'],
            ['slug' => 'fraction-multiplication', 'name' => 'Fraction Multiplication', 'kind' => 'fraction', 'op' => 'x'],
            ['slug' => 'fraction-division', 'name' => 'Fraction Division', 'kind' => 'fraction', 'op' => '÷'],
            ['slug' => 'vertical-crosswise-3d', 'name' => 'Vertical Crosswise (3D)', 'kind' => 'multiply_no_zero', 'a' => [111, 888], 'b' => [111, 888]],
            ['slug' => 'mixed-base-100-3d-x-2d', 'name' => 'Mixed Base 100 (3D x 2D)', 'kind' => 'multiply', 'a' => [101, 112], 'b' => [88, 99]],
            ['slug' => 'mixed-base-100-2d-x-3d', 'name' => 'Mixed Base 100 (2D x 3D)', 'kind' => 'multiply', 'a' => [88, 99], 'b' => [101, 112]],
            ['slug' => 'mixed-base-1000-4d-x-3d', 'name' => 'Mixed Base 1000 (4D x 3D)', 'kind' => 'multiply', 'a' => [1001, 1015], 'b' => [985, 999]],
            ['slug' => 'mixed-base-1000-3d-x-4d', 'name' => 'Mixed Base 1000 (3D x 4D)', 'kind' => 'multiply', 'a' => [985, 999], 'b' => [1001, 1015]],
        ],
        4 => [
            ['slug' => 'vertical-crosswise-4d', 'name' => 'Vertical Crosswise (4D)', 'kind' => 'multiply_no_zero', 'a' => [1111, 8888], 'b' => [1111, 8888]],
            ['slug' => 'duplex-square-2-digit', 'name' => 'Duplex Square: 2 Digit', 'kind' => 'power_prompt', 'power' => 2, 'range' => [11, 99], 'prompt' => 'Find square'],
            ['slug' => 'duplex-square-3-digit', 'name' => 'Duplex Square: 3 Digit', 'kind' => 'power_prompt', 'power' => 2, 'range' => [100, 999], 'prompt' => 'Find square'],
            ['slug' => 'duplex-square-4-digit', 'name' => 'Duplex Square: 4 Digit', 'kind' => 'power_prompt', 'power' => 2, 'range' => [1000, 9999], 'prompt' => 'Find square'],
            ['slug' => 'duplex-square-5-digit', 'name' => 'Duplex Square: 5 Digit', 'kind' => 'power_prompt', 'power' => 2, 'range' => [10000, 99999], 'prompt' => 'Find square'],
            ['slug' => 'cube-3-digit', 'name' => 'Cube (3 Digit)', 'kind' => 'power_prompt', 'power' => 3, 'range' => [100, 999], 'prompt' => 'Find cube'],
            ['slug' => 'square-root-6-digit', 'name' => 'Square Root (6 Digit)', 'kind' => 'root_prompt', 'power' => 2, 'range' => [317, 999], 'prompt' => 'Find square root'],
            ['slug' => 'cube-root-8d-odd', 'name' => 'Cube Root: 8D (Odd)', 'kind' => 'root_prompt_parity', 'power' => 3, 'range' => [216, 463], 'parity' => 'odd', 'prompt' => 'Find cube root'],
            ['slug' => 'cube-root-9d-odd', 'name' => 'Cube Root: 9D (Odd)', 'kind' => 'root_prompt_parity', 'power' => 3, 'range' => [464, 999], 'parity' => 'odd', 'prompt' => 'Find cube root'],
            ['slug' => 'cube-root-8d-even', 'name' => 'Cube Root: 8D (Even)', 'kind' => 'root_prompt_parity', 'power' => 3, 'range' => [216, 463], 'parity' => 'even', 'prompt' => 'Find cube root'],
            ['slug' => 'cube-root-9d-even', 'name' => 'Cube Root: 9D (Even)', 'kind' => 'root_prompt_parity', 'power' => 3, 'range' => [464, 999], 'parity' => 'even', 'prompt' => 'Find cube root'],
            ['slug' => 'calendar-method', 'name' => 'Calendar Method', 'kind' => 'calendar'],
        ],
    ];

    return $levels[$levelNumber] ?? [];
}

function worksheet_sub_vedic_topic_id(string $levelId, string $slug): string
{
    return 'vedic-' . substr(sha1($levelId), 0, 12) . '-' . $slug;
}

function worksheet_sub_number_without_zero(int $min, int $max): int
{
    do {
        $value = random_int($min, $max);
    } while (str_contains((string) $value, '0'));
    return $value;
}

function worksheet_sub_pick_factor(): int
{
    $factors = [12, 14, 15, 16, 18, 24, 25, 32, 36, 42, 45];
    return $factors[array_rand($factors)];
}

function worksheet_sub_decimal_answer(float $value): string
{
    $rounded = round($value, 4);
    return rtrim(rtrim(number_format($rounded, 4, '.', ''), '0'), '.');
}

function worksheet_sub_gcd(int $a, int $b): int
{
    $a = abs($a);
    $b = abs($b);
    while ($b !== 0) {
        $tmp = $b;
        $b = $a % $b;
        $a = $tmp;
    }
    return max(1, $a);
}

function worksheet_sub_fraction_string(int $num, int $den): string
{
    if ($den < 0) {
        $num *= -1;
        $den *= -1;
    }
    $gcd = worksheet_sub_gcd($num, $den);
    $num = intdiv($num, $gcd);
    $den = intdiv($den, $gcd);
    return $den === 1 ? (string) $num : $num . '/' . $den;
}

function worksheet_sub_make_string_options(string $answer, array $candidates): array
{
    $options = [$answer];
    foreach ($candidates as $candidate) {
        $candidate = (string) $candidate;
        if ($candidate !== $answer && !in_array($candidate, $options, true)) {
            $options[] = $candidate;
        }
        if (count($options) === 4) {
            break;
        }
    }
    while (count($options) < 4) {
        $candidate = (string) random_int(1, 9999);
        if ($candidate !== $answer && !in_array($candidate, $options, true)) {
            $options[] = $candidate;
        }
    }
    shuffle($options);
    return $options;
}

function worksheet_sub_vedic_generate_one(array $spec): array
{
    $kind = (string) $spec['kind'];
    $question = '';
    $answer = '';
    $distractors = [];

    if ($kind === 'complement') {
        $n = random_int($spec['range'][0], $spec['range'][1]);
        $answer = (string) ($spec['base'] - $n);
        $question = 'Find complement: ' . $n;
        $distractors = [(string) ($answer + 1), (string) max(0, ((int) $answer) - 1), (string) ($spec['base'] - $n + 10)];
    } elseif ($kind === 'subtraction') {
        do {
            $a = random_int($spec['a'][0], $spec['a'][1]);
            $b = random_int($spec['b'][0], $spec['b'][1]);
        } while (!empty($spec['borrow']) && ($b % 10) <= ($a % 10));
        $answer = (string) ($a - $b);
        $question = $a . ' - ' . $b;
        $distractors = [(string) ($a - $b + 10), (string) abs($b - $a), (string) ($a - $b - random_int(1, 9))];
    } elseif (in_array($kind, ['multiply', 'multiply_no_zero'], true)) {
        $a = $kind === 'multiply_no_zero' ? worksheet_sub_number_without_zero($spec['a'][0], $spec['a'][1]) : random_int($spec['a'][0], $spec['a'][1]);
        $b = $kind === 'multiply_no_zero' ? worksheet_sub_number_without_zero($spec['b'][0], $spec['b'][1]) : random_int($spec['b'][0], $spec['b'][1]);
        $answer = (string) ($a * $b);
        $question = $a . ' x ' . $b;
        $distractors = [(string) (($a + 1) * $b), (string) ($a * ($b + 1)), (string) ($a * $b + random_int(9, 99))];
    } elseif ($kind === 'fixed_multiply') {
        $a = random_int($spec['a'][0], $spec['a'][1]);
        $b = (int) $spec['fixed'];
        $answer = (string) ($a * $b);
        $question = $a . ' x ' . $b;
        $distractors = [(string) ($a * ($b - 1)), (string) (($a + 1) * $b), (string) ($a * $b - random_int(9, 99))];
    } elseif ($kind === 'multiply_same_range') {
        $a = random_int($spec['range'][0], $spec['range'][1]);
        $b = random_int($spec['range'][0], $spec['range'][1]);
        $answer = (string) ($a * $b);
        $question = $a . ' x ' . $b;
        $distractors = [(string) (($a + 1) * $b), (string) ($a * ($b - 1)), (string) ($a * $b + random_int(5, 50))];
    } elseif ($kind === 'power_prompt') {
        $a = random_int($spec['range'][0], $spec['range'][1]);
        $answer = (string) ($spec['power'] === 3 ? $a ** 3 : $a ** 2);
        $question = $spec['prompt'] . ': ' . $a;
        $distractors = [(string) (($a + 1) ** (int) $spec['power']), (string) (($a - 1) ** (int) $spec['power']), (string) (((int) $answer) + random_int(10, 200))];
    } elseif ($kind === 'root_prompt' || $kind === 'root_prompt_parity') {
        do {
            $root = random_int($spec['range'][0], $spec['range'][1]);
        } while ($kind === 'root_prompt_parity' && (($spec['parity'] === 'odd') !== ((bool) ($root % 2))));
        $n = $spec['power'] === 3 ? $root ** 3 : $root ** 2;
        $answer = (string) $root;
        $question = $spec['prompt'] . ': ' . $n;
        $distractors = [(string) ($root + 1), (string) max(1, $root - 1), (string) ($root + random_int(2, 5))];
    } elseif ($kind === 'first_same_last_add_10') {
        $tens = random_int(1, 9);
        $unit = random_int(1, 9);
        $a = ($tens * 10) + $unit;
        $b = ($tens * 10) + (10 - $unit);
        $answer = (string) ($a * $b);
        $question = $a . ' x ' . $b;
        $distractors = [(string) (($a + 1) * $b), (string) ($a * ($b + 1)), (string) ($a * $b - 10)];
    } elseif ($kind === 'last_same_first_add_10') {
        $first = random_int(1, 9);
        $unit = random_int(1, 9);
        $a = ($first * 10) + $unit;
        $b = ((10 - $first) * 10) + $unit;
        $answer = (string) ($a * $b);
        $question = $a . ' x ' . $b;
        $distractors = [(string) (($a + 10) * $b), (string) ($a * ($b - 10)), (string) ($a * $b + 100)];
    } elseif (in_array($kind, ['division_decimal', 'division_decimal_no_zero_divisor'], true)) {
        $a = random_int($spec['a'][0], $spec['a'][1]);
        if (!empty($spec['b_set'])) {
            $b = $spec['b_set'][array_rand($spec['b_set'])];
        } else {
            do { $b = random_int($spec['b'][0], $spec['b'][1]); } while ($kind === 'division_decimal_no_zero_divisor' && $b % 10 === 0);
        }
        $answer = worksheet_sub_decimal_answer($a / $b);
        $question = $a . ' ÷ ' . $b;
        $distractors = [worksheet_sub_decimal_answer(($a + $b) / $b), worksheet_sub_decimal_answer(max(1, $a - $b) / $b), worksheet_sub_decimal_answer($a / ($b + 1))];
    } elseif ($kind === 'fixed_division_decimal') {
        $a = random_int($spec['a'][0], $spec['a'][1]);
        $b = (int) $spec['b'];
        $answer = worksheet_sub_decimal_answer($a / $b);
        $question = $a . ' ÷ ' . $b;
        $distractors = [worksheet_sub_decimal_answer(($a + $b) / $b), worksheet_sub_decimal_answer(max(1, $a - $b) / $b), worksheet_sub_decimal_answer($a / ($b * 2))];
    } elseif ($kind === 'factor_multiply') {
        $a = random_int($spec['a'][0], $spec['a'][1]);
        $b = worksheet_sub_pick_factor();
        $answer = (string) ($a * $b);
        $question = $a . ' x ' . $b;
        $distractors = [(string) ($a * ($b + 1)), (string) ($a * max(1, $b - 1)), (string) ($a * $b + random_int(50, 500))];
    } elseif ($kind === 'factor_division') {
        $b = worksheet_sub_pick_factor();
        $q = random_int(max(1, intdiv($spec['a'][0], $b)), max(2, intdiv($spec['a'][1], $b)));
        $a = $q * $b;
        $answer = (string) $q;
        $question = $a . ' ÷ ' . $b;
        $distractors = [(string) ($q + 1), (string) max(1, $q - 1), (string) ($q + random_int(2, 9))];
    } elseif ($kind === 'fraction') {
        do {
            $n1 = random_int(1, 9); $d1 = random_int(2, 12);
            $n2 = random_int(1, 9); $d2 = random_int(2, 12);
        } while ($d1 === $d2 || ($spec['op'] === '-' && ($n1 / $d1) <= ($n2 / $d2)));
        if ($spec['op'] === '+') { $num = $n1 * $d2 + $n2 * $d1; $den = $d1 * $d2; }
        elseif ($spec['op'] === '-') { $num = $n1 * $d2 - $n2 * $d1; $den = $d1 * $d2; }
        elseif ($spec['op'] === 'x') { $num = $n1 * $n2; $den = $d1 * $d2; }
        else { $num = $n1 * $d2; $den = $d1 * $n2; }
        $answer = worksheet_sub_fraction_string($num, $den);
        $question = '(' . $n1 . '/' . $d1 . ') ' . $spec['op'] . ' (' . $n2 . '/' . $d2 . ')';
        $distractors = [worksheet_sub_fraction_string($num + 1, $den), worksheet_sub_fraction_string(max(1, $num - 1), $den), worksheet_sub_fraction_string($num, $den + 1)];
    } elseif ($kind === 'calendar') {
        $ts = random_int(strtotime('1900-01-01'), strtotime('2100-12-31'));
        $question = 'Find day of week: ' . gmdate('d / m / Y', $ts);
        $answer = gmdate('l', $ts);
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $distractors = array_values(array_filter($days, static fn(string $day): bool => $day !== $answer));
        shuffle($distractors);
    }

    if (preg_match('/^(-?\d+(?:\.\d+)?)\s+([+\-x\/])\s+(-?\d+(?:\.\d+)?)$/', $question, $parts)) {
        $question = $parts[1] . "\n" . $parts[2] . $parts[3];
    }

    return ['question' => $question, 'answer' => $answer, 'options' => worksheet_sub_make_string_options($answer, $distractors)];
}

function worksheet_sub_generate_vedic_questions(array $topic): array
{
    $levelNumber = (int) worksheet_sub_level_number((string) ($topic['level_name'] ?? ''));
    $spec = null;
    foreach (worksheet_sub_vedic_topic_specs($levelNumber) as $candidate) {
        if (worksheet_sub_vedic_topic_id((string) $topic['level_id'], (string) $candidate['slug']) === (string) $topic['id']) {
            $spec = $candidate;
            break;
        }
    }
    if (!$spec) {
        return [];
    }

    $questions = [];
    for ($i = 1; $i <= 60; $i++) {
        $item = worksheet_sub_vedic_generate_one($spec);
        $questions[] = [
            'id' => (string) $topic['id'] . '-q' . $i . '-' . bin2hex(random_bytes(3)),
            'topic_id' => (string) $topic['id'],
            'question' => $item['question'],
            'answer' => $item['answer'],
            'options' => $item['options'],
            'generated' => true,
        ];
    }
    return $questions;
}
function worksheet_sub_dynamic_topic_specs(int $levelNumber): array
{
    $matrix = worksheet_sub_official_dynamic_topic_matrix();
    return array_map(static function (array $row): array {
        $spec = ['slug' => $row[0], 'name' => $row[1], 'operation' => $row[2], 'digits' => $row[4]];
        if ($row[3] > 0) $spec['rows'] = $row[3];
        if (($row[5] ?? 0) > 0) $spec['decimal_places'] = $row[5];
        return $spec;
    }, $matrix[$levelNumber] ?? []);
}

function worksheet_sub_official_dynamic_topic_matrix(): array
{
    return [
        2 => [
            ['3-row-single-digit-addition','3 Rows - Single Digit Addition','addition',3,1], ['3-row-single-digit-subtraction','3 Rows - Single Digit Subtraction','subtraction',3,1], ['3-row-mixed-single-digit','3 Rows - Mixed Single Digit','mixed',3,1],
            ['4-row-single-digit-addition','4 Rows - Single Digit Addition','addition',4,1], ['4-row-mixed-single-digit','4 Rows - Mixed Single Digit','mixed',4,1], ['5-row-single-digit-addition','5 Rows - Single Digit Addition','addition',5,1], ['5-row-mixed-single-digit','5 Rows - Mixed Single Digit','mixed',5,1],
        ],
        3 => [
            ['5-row-single-digit-addition','5 Rows - Single Digit Addition','addition',5,1], ['5-row-single-digit-mixed','5 Rows - Single Digit Mixed','mixed',5,1], ['10-row-single-digit-addition','10 Rows - Single Digit Addition','addition',10,1], ['10-row-single-digit-mixed','10 Rows - Single Digit Mixed','mixed',10,1],
            ['3-row-two-digit-addition','3 Rows - Two Digit Addition','addition',3,2], ['3-row-two-digit-subtraction','3 Rows - Two Digit Subtraction','subtraction',3,2], ['3-row-two-digit-mixed','3 Rows - Two Digit Mixed','mixed',3,2],
            ['4-row-two-digit-addition','4 Rows - Two Digit Addition','addition',4,2], ['4-row-two-digit-subtraction','4 Rows - Two Digit Subtraction','subtraction',4,2], ['4-row-two-digit-mixed','4 Rows - Two Digit Mixed','mixed',4,2],
            ['6-row-two-digit-addition','6 Rows - Two Digit Addition','addition',6,2], ['6-row-two-digit-mixed','6 Rows - Two Digit Mixed','mixed',6,2],
            ['10-row-two-digit-addition','10 Rows - Two Digit Addition','addition',10,2], ['10-row-two-digit-subtraction','10 Rows - Two Digit Subtraction','subtraction',10,2], ['10-row-two-digit-mixed','10 Rows - Two Digit Mixed','mixed',10,2],
            ['multiplication-2x1','2 Digit x 1 Digit','multiplication',0,[2,1]], ['multiplication-2x2','2 Digit x 2 Digit','multiplication',0,[2,2]], ['multiplication-3x1','3 Digit x 1 Digit','multiplication',0,[3,1]],
        ],
        4 => [
            ['3-row-two-digit-addition','3 Rows - Two Digit Addition','addition',3,2], ['3-row-two-digit-subtraction','3 Rows - Two Digit Subtraction','subtraction',3,2], ['3-row-two-digit-mixed','3 Rows - Two Digit Mixed','mixed',3,2],
            ['10-row-two-digit-addition','10 Rows - Two Digit Addition','addition',10,2], ['10-row-two-digit-subtraction','10 Rows - Two Digit Subtraction','subtraction',10,2], ['10-row-two-digit-mixed','10 Rows - Two Digit Mixed','mixed',10,2],
            ['3-row-three-digit-addition','3 Rows - Three Digit Addition','addition',3,3], ['3-row-three-digit-subtraction','3 Rows - Three Digit Subtraction','subtraction',3,3], ['3-row-three-digit-mixed','3 Rows - Three Digit Mixed','mixed',3,3],
            ['4-row-three-digit-addition','4 Rows - Three Digit Addition','addition',4,3], ['4-row-three-digit-subtraction','4 Rows - Three Digit Subtraction','subtraction',4,3], ['4-row-three-digit-mixed','4 Rows - Three Digit Mixed','mixed',4,3],
            ['5-row-three-digit-addition','5 Rows - Three Digit Addition','addition',5,3], ['5-row-three-digit-subtraction','5 Rows - Three Digit Subtraction','subtraction',5,3], ['5-row-three-digit-mixed','5 Rows - Three Digit Mixed','mixed',5,3],
            ['multiplication-2x1','2 Digit x 1 Digit','multiplication',0,[2,1]], ['multiplication-3x1','3 Digit x 1 Digit','multiplication',0,[3,1]], ['multiplication-2x2','2 Digit x 2 Digit','multiplication',0,[2,2]],
        ],
        5 => [
            ['3-row-three-digit-addition','3 Rows - Three Digit Addition','addition',3,3], ['3-row-three-digit-subtraction','3 Rows - Three Digit Subtraction','subtraction',3,3], ['3-row-three-digit-mixed','3 Rows - Three Digit Mixed','mixed',3,3],
            ['5-row-three-digit-addition','5 Rows - Three Digit Addition','addition',5,3], ['5-row-three-digit-subtraction','5 Rows - Three Digit Subtraction','subtraction',5,3], ['5-row-three-digit-mixed','5 Rows - Three Digit Mixed','mixed',5,3],
            ['multiplication-2x1','2 Digit x 1 Digit','multiplication',0,[2,1]], ['multiplication-2x2','2 Digit x 2 Digit','multiplication',0,[2,2]], ['multiplication-3x1','3 Digit x 1 Digit','multiplication',0,[3,1]],
            ['division-2-by-1','2 Digit / 1 Digit','division',0,[2,1]], ['division-3-by-1','3 Digit / 1 Digit','division',0,[3,1]], ['division-4-by-1','4 Digit / 1 Digit','division',0,[4,1]],
            ['10-row-single-digit-addition','10 Rows - Single Digit Addition','addition',10,1], ['10-row-single-digit-mixed','10 Rows - Single Digit Mixed','mixed',10,1],
            ['4-row-single-digit-decimal-add','4 Rows - Single Digit Decimal Add','addition',4,1,1], ['4-row-single-digit-decimal-mixed','4 Rows - Single Digit Decimal Mixed','mixed',4,1,1],
            ['10-row-single-decimal-digit-add','10 Rows - Single Decimal Digit Add','addition',10,1,1], ['10-row-single-decimal-digit-mixed','10 Rows - Single Decimal Digit Mixed','mixed',10,1,1],
        ],
        6 => [
            ['4-row-three-digit-addition','4 Rows - Three Digit Addition','addition',4,3], ['4-row-three-digit-subtraction','4 Rows - Three Digit Subtraction','subtraction',4,3], ['4-row-three-digit-mixed','4 Rows - Three Digit Mixed','mixed',4,3],
            ['5-row-three-digit-addition','5 Rows - Three Digit Addition','addition',5,3], ['5-row-three-digit-subtraction','5 Rows - Three Digit Subtraction','subtraction',5,3], ['5-row-three-digit-mixed','5 Rows - Three Digit Mixed','mixed',5,3],
            ['5-row-four-digit-addition','5 Rows - Four Digit Addition','addition',5,4], ['5-row-four-digit-subtraction','5 Rows - Four Digit Subtraction','subtraction',5,4], ['5-row-four-digit-mixed','5 Rows - Four Digit Mixed','mixed',5,4],
            ['4-row-two-digit-decimal-add','4 Rows - Two Digit Decimal Add','addition',4,2,1], ['4-row-two-digit-decimal-sub','4 Rows - Two Digit Decimal Sub','subtraction',4,2,1], ['4-row-two-digit-decimal-mixed','4 Rows - Two Digit Decimal Mixed','mixed',4,2,1],
            ['5-row-two-digit-decimal-add','5 Rows - Two Digit Decimal Add','addition',5,2,1], ['5-row-two-digit-decimal-sub','5 Rows - Two Digit Decimal Sub','subtraction',5,2,1], ['5-row-two-digit-decimal-mixed','5 Rows - Two Digit Decimal Mixed','mixed',5,2,1],
            ['multiplication-4x1','4 Digit x 1 Digit','multiplication',0,[4,1]], ['multiplication-5x1','5 Digit x 1 Digit','multiplication',0,[5,1]], ['multiplication-3x2','Three Digit x Two Digit','multiplication',0,[3,2]], ['multiplication-3x3','Three Digit x Three Digit','multiplication',0,[3,3]], ['multiplication-4x2','Four Digit x Two Digit','multiplication',0,[4,2]], ['division-3-by-1','Three Digit / One Digit','division',0,[3,1]],
        ],
        7 => [
            ['5-row-four-digit-addition','5 Rows - Four Digit Addition','addition',5,4], ['5-row-four-digit-subtraction','5 Rows - Four Digit Subtraction','subtraction',5,4], ['5-row-four-digit-mixed','5 Rows - Four Digit Mixed','mixed',5,4],
            ['6-row-four-digit-addition','6 Rows - Four Digit Addition','addition',6,4], ['6-row-four-digit-subtraction','6 Rows - Four Digit Subtraction','subtraction',6,4], ['6-row-four-digit-mixed','6 Rows - Four Digit Mixed','mixed',6,4],
            ['7-row-four-digit-addition','7 Rows - Four Digit Addition','addition',7,4], ['7-row-four-digit-subtraction','7 Rows - Four Digit Subtraction','subtraction',7,4], ['7-row-four-digit-mixed','7 Rows - Four Digit Mixed','mixed',7,4],
            ['multiplication-3x3','Three Digit x Three Digit','multiplication',0,[3,3]], ['multiplication-4x4','Four Digit x Four Digit','multiplication',0,[4,4]], ['division-4-by-1','Four Digit / One Digit','division',0,[4,1]], ['division-4-by-2','Four Digit / Two Digit','division',0,[4,2]],
        ],
    ];
}

function worksheet_sub_legacy_dynamic_topic_specs(int $levelNumber): array
{
    $specs = [
        2 => [
            ['slug' => 'addition-2-digit-3-row', 'name' => 'Generated Addition - 2 Digit, 3 Row', 'operation' => 'addition', 'rows' => 3, 'digits' => [2, 2, 2]],
            ['slug' => 'subtraction-2-digit-3-row', 'name' => 'Generated Subtraction - 2 Digit, 3 Row', 'operation' => 'subtraction', 'rows' => 3, 'digits' => [2, 2, 2]],
        ],
        3 => [
            ['slug' => 'addition-3-digit-4-row', 'name' => 'Generated Addition - 3 Digit, 4 Row', 'operation' => 'addition', 'rows' => 4, 'digits' => [3, 3, 3, 3]],
            ['slug' => 'subtraction-3-digit-4-row', 'name' => 'Generated Subtraction - 3 Digit, 4 Row', 'operation' => 'subtraction', 'rows' => 4, 'digits' => [3, 3, 3, 3]],
        ],
        4 => [
            ['slug' => 'addition-4-digit-5-row', 'name' => 'Generated Addition - 4 Digit, 5 Row', 'operation' => 'addition', 'rows' => 5, 'digits' => [4, 4, 4, 4, 4]],
            ['slug' => 'subtraction-4-digit-5-row', 'name' => 'Generated Subtraction - 4 Digit, 5 Row', 'operation' => 'subtraction', 'rows' => 5, 'digits' => [4, 4, 4, 4, 4]],
        ],
        5 => [
            ['slug' => 'multiplication-2x1', 'name' => 'Generated Multiplication - 2 Digit x 1 Digit', 'operation' => 'multiplication', 'digits' => [2, 1]],
            ['slug' => 'multiplication-2x2', 'name' => 'Generated Multiplication - 2 Digit x 2 Digit', 'operation' => 'multiplication', 'digits' => [2, 2]],
        ],
        6 => [
            ['slug' => 'division-2-by-1', 'name' => 'Generated Division - 2 Digit by 1 Digit', 'operation' => 'division', 'digits' => [2, 1]],
            ['slug' => 'division-3-by-1', 'name' => 'Generated Division - 3 Digit by 1 Digit', 'operation' => 'division', 'digits' => [3, 1]],
        ],
        7 => [
            ['slug' => 'multiplication-3x2', 'name' => 'Generated Multiplication - 3 Digit x 2 Digit', 'operation' => 'multiplication', 'digits' => [3, 2]],
            ['slug' => 'division-3-by-2', 'name' => 'Generated Division - 3 Digit by 2 Digit', 'operation' => 'division', 'digits' => [3, 2]],
            ['slug' => 'addition-4-digit-6-row', 'name' => 'Generated Addition - 4 Digit, 6 Row', 'operation' => 'addition', 'rows' => 6, 'digits' => [4, 4, 4, 4, 4, 4]],
        ],
    ];

    return $specs[$levelNumber] ?? [];
}

function worksheet_sub_dynamic_topic_id(string $levelId, string $slug): string
{
    return 'dynamic-' . substr(sha1($levelId), 0, 12) . '-' . $slug;
}

function worksheet_sub_ensure_dynamic_topics(array $level): void
{
    $levelName = (string) ($level['level_name'] ?? '');
    $levelNumber = worksheet_sub_level_number($levelName);
    if ($levelNumber === null) {
        return;
    }

    if (worksheet_sub_is_vedic_level_name($levelName)) {
        foreach (worksheet_sub_vedic_topic_specs((int) $levelNumber) as $spec) {
            db_exec_sql(
                'INSERT INTO worksheet_topics (id, level_id, topic_name, total_questions)
                 VALUES (:id, :level_id, :topic_name, 60)
                 ON DUPLICATE KEY UPDATE topic_name = VALUES(topic_name), total_questions = 60',
                [
                    'id' => worksheet_sub_vedic_topic_id((string) $level['id'], (string) $spec['slug']),
                    'level_id' => $level['id'],
                    'topic_name' => $spec['name'],
                ]
            );
        }
        return;
    }

    if (!worksheet_sub_is_abacus_level_name($levelName) || (int) $levelNumber < 2 || (int) $levelNumber > 7) {
        return;
    }

    foreach (worksheet_sub_dynamic_topic_specs((int) $levelNumber) as $spec) {
        db_exec_sql(
            'INSERT INTO worksheet_topics (id, level_id, topic_name, total_questions)
             VALUES (:id, :level_id, :topic_name, 60)
             ON DUPLICATE KEY UPDATE topic_name = VALUES(topic_name), total_questions = 60',
            [
                'id' => worksheet_sub_dynamic_topic_id((string) $level['id'], (string) $spec['slug']),
                'level_id' => $level['id'],
                'topic_name' => $spec['name'],
            ]
        );
    }
}

function worksheet_sub_random_number(int $digits): int
{
    $digits = max(1, $digits);
    if ($digits === 1) {
        return random_int(1, 9);
    }
    return random_int(10 ** ($digits - 1), (10 ** $digits) - 1);
}

function worksheet_sub_make_options(int $answer): array
{
    $options = [$answer];
    $spread = max(5, min(50, abs($answer) + 10));
    while (count($options) < 4) {
        $delta = random_int(1, $spread);
        $candidate = $answer + (random_int(0, 1) ? $delta : -$delta);
        if ($candidate < 0) {
            $candidate = $answer + $delta;
        }
        if (!in_array($candidate, $options, true)) {
            $options[] = $candidate;
        }
    }
    shuffle($options);
    return array_map('strval', $options);
}

function worksheet_sub_render_question(array $numbers, string $operation): string
{
    $symbol = match ($operation) {
        'subtraction' => '-',
        'multiplication' => 'x',
        'division' => '÷',
        default => '+',
    };

    if ($operation === 'multiplication' || $operation === 'division') {
        return $numbers[0] . ' ' . $symbol . ' ' . $numbers[1];
    }

    return implode("\n", array_map(
        static fn(int $number, int $index): string => ($index === 0 ? (string) $number : ($number >= 0 ? '+' . $number : (string) $number)),
        $numbers,
        array_keys($numbers)
    ));
}

function worksheet_sub_generate_official_dynamic_item(array $spec): array
{
    $operation = (string) $spec['operation'];
    $places = (int) ($spec['decimal_places'] ?? 0); $scale = 10 ** $places;
    $format = static fn(int $units): string => $places > 0 ? number_format($units / $scale, $places, '.', '') : (string) $units;
    if ($operation === 'multiplication' || $operation === 'division') {
        [$leftDigits, $rightDigits] = array_map('intval', $spec['digits']);
        if ($operation === 'multiplication') {
            $left = worksheet_sub_random_number($leftDigits); $right = worksheet_sub_random_number($rightDigits);
            $answerUnits = $left * $right; $question = $left . ' x ' . $right;
        } else {
            $minDividend = 10 ** ($leftDigits - 1); $maxDividend = (10 ** $leftDigits) - 1;
            do {
                $right = worksheet_sub_random_number($rightDigits);
                $minQuotient = max(1, (int) ceil($minDividend / $right));
                $maxQuotient = (int) floor($maxDividend / $right);
            } while ($minQuotient > $maxQuotient);
            $answerUnits = random_int($minQuotient, $maxQuotient); $left = $right * $answerUnits;
            $question = $left . ' ÷ ' . $right;
        }
    } else {
        $rows = (int) $spec['rows']; $digits = (int) $spec['digits'];
        $min = (10 ** ($digits - 1)) * $scale; $max = ((10 ** $digits) * $scale) - 1;
        $numbers = [];
        if ($operation === 'subtraction') {
            $running = random_int(max($min, $min * ($rows - 1)), $max); $numbers[] = $running;
            for ($row = 1; $row < $rows; $row++) {
                $reserved = ($rows - $row - 1) * $min;
                $value = random_int($min, min($max, $running - $reserved));
                $running -= $value; $numbers[] = -$value;
            }
        } else {
            $running = random_int($min, $max); $numbers[] = $running;
            for ($row = 1; $row < $rows; $row++) {
                $value = random_int($min, $max);
                $subtract = $operation === 'mixed' && random_int(0, 1) === 1 && $value <= $running;
                $numbers[] = $subtract ? -$value : $value; $running += $subtract ? -$value : $value;
            }
        }
        $answerUnits = array_sum($numbers);
        $question = implode(PHP_EOL, array_map(static function (int $value, int $index) use ($format): string {
            $rendered = $format(abs($value));
            return $index === 0 ? pack('C*', 0xE2, 0x80, 0x87) . $rendered : ($value < 0 ? '-' : '+') . $rendered;
        }, $numbers, array_keys($numbers)));
    }
    $optionUnits = [$answerUnits]; $offsets = [$scale, 5 * $scale, 10 * $scale, max($scale, (int) round(abs($answerUnits) * 0.1))];
    while (count($optionUnits) < 4) {
        $delta = $offsets[array_rand($offsets)]; $candidate = $answerUnits + (random_int(0, 1) ? $delta : -$delta);
        if ($candidate < 0) $candidate = $answerUnits + $delta;
        if (!in_array($candidate, $optionUnits, true)) $optionUnits[] = $candidate;
    }
    shuffle($optionUnits);
    return ['question' => $question, 'answer' => $format($answerUnits), 'options' => array_map($format, $optionUnits)];
}

function worksheet_sub_generate_dynamic_questions(array $topic): array
{
    if (worksheet_sub_is_vedic_level_name((string) ($topic['level_name'] ?? ''))) return worksheet_sub_generate_vedic_questions($topic);
    $levelNumber = worksheet_sub_level_number((string) ($topic['level_name'] ?? ''));
    if ($levelNumber === null) return [];
    $spec = null;
    foreach (worksheet_sub_dynamic_topic_specs((int) $levelNumber) as $candidate) {
        if (worksheet_sub_dynamic_topic_id((string) $topic['level_id'], (string) $candidate['slug']) === (string) $topic['id']) { $spec = $candidate; break; }
    }
    if (!$spec) return [];
    $questions = [];
    for ($i = 1; $i <= 60; $i++) {
        $item = worksheet_sub_generate_official_dynamic_item($spec);
        $questions[] = ['id' => (string) $topic['id'] . '-q' . $i . '-' . bin2hex(random_bytes(3)), 'topic_id' => (string) $topic['id'], 'question' => $item['question'], 'answer' => $item['answer'], 'options' => $item['options'], 'generated' => true];
    }
    return $questions;
}

function worksheet_sub_legacy_generate_dynamic_questions(array $topic): array
{
    if (worksheet_sub_is_vedic_level_name((string) ($topic['level_name'] ?? ''))) {
        return worksheet_sub_generate_vedic_questions($topic);
    }

    $levelNumber = worksheet_sub_level_number((string) ($topic['level_name'] ?? ''));
    if ($levelNumber === null) {
        return [];
    }

    $spec = null;
    foreach (worksheet_sub_dynamic_topic_specs((int) $levelNumber) as $candidate) {
        if (worksheet_sub_dynamic_topic_id((string) $topic['level_id'], (string) $candidate['slug']) === (string) $topic['id']) {
            $spec = $candidate;
            break;
        }
    }
    if (!$spec) {
        return [];
    }

    $questions = [];
    for ($i = 1; $i <= 60; $i++) {
        $operation = (string) $spec['operation'];
        $digits = array_values($spec['digits']);
        if ($operation === 'addition') {
            $numbers = array_map(static fn(int $digit): int => worksheet_sub_random_number($digit), $digits);
            $answer = array_sum($numbers);
        } elseif ($operation === 'subtraction') {
            $first = worksheet_sub_random_number((int) $digits[0]);
            $remaining = $first;
            $numbers = [$first];
            foreach (array_slice($digits, 1) as $index => $digit) {
                $rowsLeft = count($digits) - $index - 2;
                $max = max(1, min((10 ** (int) $digit) - 1, $remaining - $rowsLeft));
                $value = random_int(1, $max);
                $remaining -= $value;
                $numbers[] = -$value;
            }
            $answer = array_sum($numbers);
        } elseif ($operation === 'multiplication') {
            $numbers = [worksheet_sub_random_number((int) $digits[0]), worksheet_sub_random_number((int) $digits[1])];
            $answer = $numbers[0] * $numbers[1];
        } else {
            $divisor = worksheet_sub_random_number((int) $digits[1]);
            $quotient = worksheet_sub_random_number(max(1, (int) $digits[0] - (int) $digits[1] + 1));
            $numbers = [$divisor * $quotient, $divisor];
            $answer = $quotient;
        }

        $questions[] = [
            'id' => (string) $topic['id'] . '-q' . $i . '-' . bin2hex(random_bytes(3)),
            'topic_id' => (string) $topic['id'],
            'question' => worksheet_sub_render_question($numbers, $operation),
            'answer' => (string) $answer,
            'options' => worksheet_sub_make_options((int) $answer),
            'generated' => true,
        ];
    }

    return $questions;
}

function worksheet_sub_table_exists(string $table): bool
{
    return (int) db_value(
        'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table',
        ['table' => $table]
    ) > 0;
}

function worksheet_sub_repair_level_paper_topics(array $level): void
{
    ensure_worksheet_sub_schema();
    if (!worksheet_sub_table_exists('worksheet_papers')) {
        return;
    }

    $levelId = (string) ($level['id'] ?? '');
    if ($levelId === '') {
        return;
    }

    $papers = db_all(
        'SELECT wp.id, wp.topic_id, wp.paper_number, wp.title, wp.total_questions
         FROM worksheet_papers wp
         LEFT JOIN worksheet_topics wt ON wt.id = wp.topic_id
         WHERE wp.level_id = :level_id AND (wp.topic_id IS NULL OR wp.topic_id = "" OR wt.id IS NULL)
         ORDER BY wp.paper_number ASC',
        ['level_id' => $levelId]
    );

    foreach ($papers as $paper) {
        $topicId = trim((string) ($paper['topic_id'] ?? ''));
        if ($topicId === '') {
            $topicId = 'paper-topic-' . substr(str_replace('-', '', (string) $paper['id']), 0, 24);
        }
        $title = trim((string) ($paper['title'] ?? ''));
        if ($title === '') {
            $title = 'Paper ' . (int) ($paper['paper_number'] ?? 0);
        }
        $total = max(0, (int) ($paper['total_questions'] ?? 0));
        db_exec_sql(
            'INSERT INTO worksheet_topics (id, level_id, topic_name, total_questions)
             VALUES (:id, :level_id, :topic_name, :total_questions)
             ON DUPLICATE KEY UPDATE level_id = VALUES(level_id), topic_name = VALUES(topic_name), total_questions = VALUES(total_questions)',
            ['id' => $topicId, 'level_id' => $levelId, 'topic_name' => $title, 'total_questions' => $total]
        );
        db_exec_sql(
            'UPDATE worksheet_papers SET topic_id = :topic_id, updated_at = :updated_at WHERE id = :id',
            ['topic_id' => $topicId, 'updated_at' => now_sql(), 'id' => $paper['id']]
        );
    }

    $paperCount = (int) db_value('SELECT COUNT(*) FROM worksheet_papers WHERE level_id = :level_id', ['level_id' => $levelId]);
    $topicCount = (int) db_value('SELECT COUNT(*) FROM worksheet_topics WHERE level_id = :level_id', ['level_id' => $levelId]);
    if ($paperCount > 0 && $topicCount === 0) {
        worksheet_sub_log_access_denied('', $levelId, 'Worksheet papers exist but no topics were mapped after repair.', [
            'levelName' => $level['level_name'] ?? null,
            'paperCount' => $paperCount,
            'topicCount' => $topicCount,
        ]);
    }
}


function worksheet_sub_is_foundation_or_level1(array $level): bool
{
    $name = (string) ($level['level_name'] ?? '');
    if (!worksheet_sub_is_abacus_level_name($name)) {
        return false;
    }
    $levelNumber = worksheet_sub_level_number($name);
    return $levelNumber === '0' || $levelNumber === '1';
}

function worksheet_sub_level_papers(array $level): array
{
    $levelId = (string) ($level['id'] ?? '');
    if ($levelId === '' || !worksheet_sub_table_exists('worksheet_papers')) {
        return [];
    }

    $hasQuestionPaperId = worksheet_sub_table_has_column('worksheet_questions', 'paper_id');
    $questionJoin = $hasQuestionPaperId ? 'LEFT JOIN worksheet_questions wq ON wq.paper_id = wp.id' : '';
    $questionCount = $hasQuestionPaperId ? 'COUNT(wq.id)' : '0';

    return db_all(
        'SELECT
            wp.id,
            wp.level_id,
            COALESCE(NULLIF(wp.title, ""), CONCAT("Paper ", wp.paper_number)) AS topic_name,
            COALESCE(NULLIF(wp.total_questions, 0), ' . $questionCount . ', 0) AS total_questions,
            wp.id AS paper_id,
            wp.topic_id,
            wp.paper_number,
            "paper" AS content_type
         FROM worksheet_papers wp
         ' . $questionJoin . '
         WHERE wp.level_id = :level_id
         GROUP BY wp.id, wp.level_id, wp.title, wp.total_questions, wp.topic_id, wp.paper_number
         ORDER BY wp.paper_number ASC, wp.id ASC',
        ['level_id' => $levelId]
    );
}
function worksheet_sub_level_topics(array $level): array
{
    $levelId = (string) ($level['id'] ?? '');
    if ($levelId === '') {
        error_log('[WorksheetAccess] Missing level id while resolving worksheet content.');
        return [];
    }

    if (worksheet_sub_is_foundation_or_level1($level)) {
        $papers = worksheet_sub_level_papers($level);
        error_log('[WorksheetAccess] Paper lookup level_id=' . $levelId . ' paper_count=' . count($papers));
        return $papers;
    }

    $topics = db_all(
        'SELECT id, level_id, topic_name, total_questions, NULL AS paper_id, NULL AS paper_number, "topic" AS content_type
         FROM worksheet_topics
         WHERE level_id = :level_id
         ORDER BY id ASC',
        ['level_id' => $levelId]
    );

    $levelNumber = worksheet_sub_level_number((string) ($level['level_name'] ?? ''));
    if (worksheet_sub_is_abacus_level_name((string) ($level['level_name'] ?? '')) && $levelNumber !== null && (int) $levelNumber >= 2 && (int) $levelNumber <= 7) {
        $byId = [];
        foreach ($topics as $topic) $byId[(string) $topic['id']] = $topic;
        $officialTopics = [];
        foreach (worksheet_sub_dynamic_topic_specs((int) $levelNumber) as $spec) {
            $id = worksheet_sub_dynamic_topic_id($levelId, (string) $spec['slug']);
            if (isset($byId[$id])) $officialTopics[] = $byId[$id];
        }
        $topics = $officialTopics;
    }

    if (worksheet_sub_is_vedic_level_name((string) ($level['level_name'] ?? '')) && $levelNumber !== null && (int) $levelNumber >= 1 && (int) $levelNumber <= 4) {
        $byId = [];
        foreach ($topics as $topic) $byId[(string) $topic['id']] = $topic;
        $officialTopics = [];
        foreach (worksheet_sub_vedic_topic_specs((int) $levelNumber) as $spec) {
            $id = worksheet_sub_vedic_topic_id($levelId, (string) $spec['slug']);
            if (isset($byId[$id])) $officialTopics[] = $byId[$id];
        }
        $topics = $officialTopics;
    }

    if (!$topics) {
        $papers = worksheet_sub_level_papers($level);
        if ($papers) {
            error_log('[WorksheetAccess] Topic lookup empty; using papers level_id=' . $levelId . ' paper_count=' . count($papers));
            return $papers;
        }
    }

    error_log('[WorksheetAccess] Topic lookup level_id=' . $levelId . ' topic_count=' . count($topics));
    return $topics;
}
function worksheet_sub_level_number(?string $name): ?string
{
    if (!is_string($name) || trim($name) === '') {
        return null;
    }
    if (preg_match('/level\s*0|foundation/i', $name)) {
        return '0';
    }
    if (preg_match('/level\s*(\d+)/i', $name, $m)) {
        return (string) ((int) $m[1]);
    }
    return null;
}

function worksheet_sub_is_active_paid(array $sub): bool
{
    $paymentStatus = strtolower((string) ($sub['paymentStatus'] ?? $sub['payment_status'] ?? ''));
    $status = strtolower((string) ($sub['status'] ?? ''));
    $expiry = (string) ($sub['expiryDate'] ?? $sub['expiry_date'] ?? '');

    return $status === 'active'
        && in_array($paymentStatus, ['paid', 'captured', 'success'], true)
        && $expiry !== ''
        && strtotime($expiry) !== false
        && strtotime($expiry) >= time();
}

function worksheet_sub_is_worksheet_subscription(array $subscription): bool
{
    $haystack = strtolower(trim(
        (string) ($subscription['planName'] ?? $subscription['plan_name'] ?? '') . ' ' .
        (string) ($subscription['levelName'] ?? $subscription['level_name'] ?? '') . ' ' .
        (string) ($subscription['courseSlug'] ?? $subscription['course_slug'] ?? '') . ' ' .
        (string) ($subscription['courseName'] ?? $subscription['course_name'] ?? '')
    ));

    return str_contains($haystack, 'worksheet') && (str_contains($haystack, 'abacus') || str_contains($haystack, 'vedic'));
}

function worksheet_sub_normalize_program_type(?string $program): ?string
{
    $program = strtolower(trim((string) $program));
    $program = str_replace(['-', ' '], '_', $program);
    if (in_array($program, ['vedic', 'vedic_maths', 'vedic_math'], true)) {
        return 'vedic_maths';
    }
    if ($program === 'abacus') {
        return 'abacus';
    }
    return null;
}

function worksheet_sub_program_type_from_text(string $text): ?string
{
    $text = strtolower($text);
    if (str_contains($text, 'vedic')) {
        return 'vedic_maths';
    }
    if (str_contains($text, 'abacus')) {
        return 'abacus';
    }
    return null;
}

function worksheet_sub_match_level(array $subscription): ?array
{
    $levelId = trim((string) ($subscription['levelId'] ?? $subscription['level_id'] ?? ''));
    if ($levelId !== '') {
        $level = db_one('SELECT id, level_name FROM worksheet_levels WHERE id = :id LIMIT 1', ['id' => $levelId]);
        if ($level) {
            return $level;
        }
    }

    $levelName = (string) ($subscription['levelName'] ?? $subscription['level_name'] ?? '');
    $planName = (string) ($subscription['planName'] ?? $subscription['plan_name'] ?? '');
    $courseSlug = (string) ($subscription['courseSlug'] ?? $subscription['course_slug'] ?? '');
    $courseName = (string) ($subscription['courseName'] ?? $subscription['course_name'] ?? '');
    $haystack = trim($courseSlug . ' ' . $courseName . ' ' . $levelName . ' ' . $planName);
    $subscriptionLevelNumber = worksheet_sub_level_number($levelName !== '' ? $levelName : $planName);
    $programType = worksheet_sub_program_type_from_text($haystack);
    if ($subscriptionLevelNumber === null || $programType === null) {
        return null;
    }

    $levels = db_all('SELECT id, level_name FROM worksheet_levels ORDER BY level_name ASC');
    foreach ($levels as $level) {
        $worksheetLevelName = (string) ($level['level_name'] ?? '');
        if (worksheet_sub_program_type_from_text($worksheetLevelName) !== $programType) {
            continue;
        }
        if (worksheet_sub_level_number($worksheetLevelName) === $subscriptionLevelNumber) {
            return $level;
        }
    }

    return null;
}

function worksheet_sub_debug_log(string $event, array $metadata = []): void
{
    error_log('[worksheet-sub] ' . $event . ' ' . json_encode($metadata));
}

function worksheet_sub_log_access_denied(string $studentId, ?string $requestedLevelId, string $reason, array $metadata = []): void
{
    worksheet_sub_debug_log('access_denied', $metadata + [
        'student_id' => $studentId,
        'requested_level_id' => $requestedLevelId,
        'reason' => $reason,
    ]);

    if (function_exists('payment_audit_log')) {
        payment_audit_log(
            'Worksheet Access Denied',
            'warning',
            $studentId,
            null,
            $metadata['subscriptionId'] ?? null,
            $requestedLevelId,
            $reason,
            $metadata
        );
    }
}

function worksheet_sub_student_levels(array $student): array
{
    ensure_worksheet_sub_schema();
    $studentId = (string) ($student['id'] ?? '');
    if ($studentId === '') {
        return [];
    }

    if (function_exists('repair_paid_payment_attempt_subscriptions')) {
        repair_paid_payment_attempt_subscriptions($studentId);
    }
    if (function_exists('repair_student_course_enrollments')) {
        repair_student_course_enrollments($studentId);
    }

    if (function_exists('getActiveWorksheetSubscriptions')) {
        $activeSubscriptions = getActiveWorksheetSubscriptions($studentId);
        $unlocked = [];
        foreach ($activeSubscriptions as $active) {
            if (empty($active['is_active']) || empty($active['level_id'])) {
                continue;
            }
            $level = [
                'id' => $active['level_id'],
                'level_name' => $active['level_name'] ?? 'Worksheet Subscription',
                'subscription_id' => $active['subscription_id'] ?? null,
                'plan_id' => $active['plan_id'] ?? null,
                'product_id' => $active['product_id'] ?? ($active['course_id'] ?? null),
                'course_id' => $active['course_id'] ?? ($active['product_id'] ?? null),
                'program_type' => $active['program_type'] ?? null,
                'start_date' => $active['start_date'] ?? null,
                'expiry_date' => $active['end_date'] ?? null,
                'payment_id' => $active['payment_id'] ?? null,
                'order_id' => $active['order_id'] ?? null,
            ];
            $key = (string) ($level['subscription_id'] ?: $level['id']);
            $unlocked[$key] = $level;
        }
        if ($unlocked) {
            return array_values($unlocked);
        }
    }

    $rows = db_all(
        'SELECT
            ss.id AS subscription_id,
            ss.student_id,
            ss.plan_id,
            ss.level_id,
            ss.plan_name,
            ss.payment_status,
            ss.status,
            ss.start_date,
            ss.expiry_date,
            ss.payment_attempt_id,
            ss.razorpay_order_id,
            ss.razorpay_payment_id,
            l.level_name,
            c.slug AS course_slug,
            c.name AS course_name
         FROM student_subscriptions ss
         LEFT JOIN levels l ON l.id = ss.level_id
         LEFT JOIN courses c ON c.id = l.course_id
         WHERE ss.student_id = :student_id
           AND ss.status = "active"
           AND ss.payment_status IN ("paid", "captured", "success")
           AND ss.expiry_date >= :now_ts
         ORDER BY ss.expiry_date DESC, ss.created_at DESC',
        ['student_id' => $studentId, 'now_ts' => now_sql()]
    );

    $unlocked = [];
    foreach ($rows as $row) {
        if (!worksheet_sub_is_worksheet_subscription($row)) {
            continue;
        }
        $level = worksheet_sub_match_level($row);
        if (!$level) {
            worksheet_sub_log_access_denied(
                $studentId,
                $row['level_id'] ?? null,
                'Active paid worksheet subscription could not be mapped to a worksheet level.',
                [
                    'subscriptionId' => $row['subscription_id'] ?? null,
                    'planId' => $row['plan_id'] ?? null,
                    'planName' => $row['plan_name'] ?? null,
                    'levelName' => $row['level_name'] ?? null,
                    'courseSlug' => $row['course_slug'] ?? null,
                    'paymentId' => $row['razorpay_payment_id'] ?? null,
                    'orderId' => $row['razorpay_order_id'] ?? null,
                ]
            );
            continue;
        }
        $level['subscription_id'] = $row['subscription_id'] ?? null;
        $level['plan_id'] = $row['plan_id'] ?? null;
        $level['program_type'] = worksheet_sub_program_type_from_text((string) ($level['level_name'] ?? ''));
        $level['start_date'] = $row['start_date'] ?? null;
        $level['expiry_date'] = $row['expiry_date'] ?? null;
        $unlocked[(string) $level['id']] = $level;
    }

    return array_values($unlocked);
}
function worksheet_sub_student_level(array $student, ?string $requestedLevelId = null, ?string $requestedSubscriptionId = null, ?string $requestedProgramType = null): ?array
{
    $levels = worksheet_sub_student_levels($student);
    if (!$levels) {
        return null;
    }

    $requestedLevelId = trim((string) $requestedLevelId);
    $requestedSubscriptionId = trim((string) $requestedSubscriptionId);
    $requestedProgramType = strtolower(trim((string) $requestedProgramType));

    foreach ($levels as $level) {
        $levelMatches = $requestedLevelId === '' || (string) ($level['id'] ?? '') === $requestedLevelId;
        $subscriptionMatches = $requestedSubscriptionId === '' || (string) ($level['subscription_id'] ?? '') === $requestedSubscriptionId;
        $levelProgramType = worksheet_sub_normalize_program_type((string) ($level['program_type'] ?? ''));
        $programMatches = $requestedProgramType === '' || $levelProgramType === worksheet_sub_normalize_program_type($requestedProgramType);
        if ($levelMatches && $subscriptionMatches && $programMatches) {
            return $level;
        }
    }

    if ($requestedLevelId !== '' || $requestedSubscriptionId !== '' || $requestedProgramType !== '') {
        return null;
    }

    return $levels[0];
}

function worksheet_sub_request_level_id(): ?string
{
    $levelId = trim((string) ($_GET['levelId'] ?? $_GET['level_id'] ?? ''));
    return $levelId !== '' ? $levelId : null;
}

function worksheet_sub_request_subscription_id(): ?string
{
    $subscriptionId = trim((string) ($_GET['subscriptionId'] ?? $_GET['subscription_id'] ?? ''));
    return $subscriptionId !== '' ? $subscriptionId : null;
}

function worksheet_sub_request_program_type(): ?string
{
    return worksheet_sub_normalize_program_type((string) ($_GET['program'] ?? $_GET['programType'] ?? $_GET['program_type'] ?? ''));
}

function worksheet_sub_topic_level_id(string $topicId): ?string
{
    if (worksheet_sub_table_exists('worksheet_papers')) {
        $paper = db_one('SELECT level_id FROM worksheet_papers WHERE id = :id LIMIT 1', ['id' => $topicId]);
        if ($paper) {
            return (string) $paper['level_id'];
        }
    }

    $topic = db_one('SELECT level_id FROM worksheet_topics WHERE id = :id LIMIT 1', ['id' => $topicId]);
    return $topic ? (string) $topic['level_id'] : null;
}

function worksheet_sub_student_has_level(array $student, string $levelId, ?string $subscriptionId = null): bool
{
    foreach (worksheet_sub_student_levels($student) as $level) {
        $levelMatches = (string) ($level['id'] ?? '') === $levelId;
        $subscriptionMatches = $subscriptionId === null || $subscriptionId === '' || (string) ($level['subscription_id'] ?? '') === $subscriptionId;
        if ($levelMatches && $subscriptionMatches) {
            return true;
        }
    }
    return false;
}
function worksheet_sub_require_student_level(array $ctx): array
{
    ensure_worksheet_sub_schema();

    $student = current_student($ctx['user']['id']);
    if (!$student) {
        json_response(['message' => 'Student not found'], 404);
    }

    $requestedLevelId = worksheet_sub_request_level_id();
    $requestedSubscriptionId = worksheet_sub_request_subscription_id();
    $requestedProgramType = worksheet_sub_request_program_type();
    $level = worksheet_sub_student_level($student, $requestedLevelId, $requestedSubscriptionId, $requestedProgramType);
    worksheet_sub_debug_log('dashboard_access_check', [
        'user_id' => $ctx['user']['id'] ?? null,
        'student_id' => $student['id'] ?? null,
        'subscription_id' => $requestedSubscriptionId,
        'requested_level_id' => $requestedLevelId,
        'program' => $requestedProgramType,
        'access_result' => $level ? 'allowed' : 'denied',
        'resolved_level_id' => $level['id'] ?? null,
        'resolved_level_name' => $level['level_name'] ?? null,
    ]);
    if (!$level) {
        worksheet_sub_log_access_denied(
            (string) $student['id'],
            $requestedLevelId,
            'No active paid worksheet subscription matched the requested level/subscription.',
            ['userId' => $ctx['user']['id'] ?? null, 'subscriptionId' => $requestedSubscriptionId, 'requestedLevelId' => $requestedLevelId, 'program' => $requestedProgramType]
        );
        json_response(['message' => 'No active worksheet subscription level is assigned to this student. Please purchase the required Abacus or Vedic Maths level subscription.'], 403);
    }

    return [$student, $level];
}

function worksheet_sub_require_topic_access(array $ctx, string $topicId): array
{
    ensure_worksheet_sub_schema();

    $student = current_student($ctx['user']['id']);
    if (!$student) {
        json_response(['message' => 'Student not found'], 404);
    }

    $requestedLevelId = worksheet_sub_request_level_id() ?: worksheet_sub_topic_level_id($topicId);
    $requestedSubscriptionId = worksheet_sub_request_subscription_id();
    $requestedProgramType = worksheet_sub_request_program_type();
    $level = worksheet_sub_student_level($student, $requestedLevelId, $requestedSubscriptionId, $requestedProgramType);
    $hasLevel = $level && worksheet_sub_student_has_level($student, (string) $level['id'], $requestedSubscriptionId);
    worksheet_sub_debug_log('topic_access_check', [
        'user_id' => $ctx['user']['id'] ?? null,
        'student_id' => $student['id'] ?? null,
        'topic_id' => $topicId,
        'subscription_id' => $requestedSubscriptionId,
        'requested_level_id' => $requestedLevelId,
        'program' => $requestedProgramType,
        'access_result' => $hasLevel ? 'allowed' : 'denied',
        'resolved_level_id' => $level['id'] ?? null,
        'resolved_level_name' => $level['level_name'] ?? null,
    ]);
    if (!$hasLevel) {
        worksheet_sub_log_access_denied(
            (string) $student['id'],
            $requestedLevelId,
            'Topic access denied because the student does not have an active paid subscription for this worksheet level/subscription.',
            ['userId' => $ctx['user']['id'] ?? null, 'subscriptionId' => $requestedSubscriptionId, 'topicId' => $topicId, 'requestedLevelId' => $requestedLevelId, 'program' => $requestedProgramType]
        );
        json_response(['message' => 'This worksheet level is locked. Please purchase the matching worksheet level subscription.'], 403);
    }

    $topic = null;

    if (worksheet_sub_is_foundation_or_level1($level) && !worksheet_sub_table_exists('worksheet_papers')) {
        json_response(['message' => 'Worksheet papers table is missing for this subscription level.'], 500);
    }

    if (worksheet_sub_table_exists('worksheet_papers')) {
        $hasQuestionPaperId = worksheet_sub_table_has_column('worksheet_questions', 'paper_id');
        $questionJoin = $hasQuestionPaperId ? 'LEFT JOIN worksheet_questions wq ON wq.paper_id = wp.id' : '';
        $questionCount = $hasQuestionPaperId ? 'COUNT(wq.id)' : '0';
        $topic = db_one(
            'SELECT
                wp.id,
                wp.level_id,
                COALESCE(NULLIF(wp.title, ""), CONCAT("Paper ", wp.paper_number)) AS topic_name,
                COALESCE(NULLIF(wp.total_questions, 0), ' . $questionCount . ', 0) AS total_questions,
                wl.level_name,
                wp.id AS paper_id,
                wp.paper_number,
                "paper" AS content_type
             FROM worksheet_papers wp
             INNER JOIN worksheet_levels wl ON wl.id = wp.level_id
             ' . $questionJoin . '
             WHERE wp.id = :id AND wp.level_id = :level_id
             GROUP BY wp.id, wp.level_id, wp.title, wp.total_questions, wl.level_name, wp.paper_number
             LIMIT 1',
            ['id' => $topicId, 'level_id' => $level['id']]
        );
    }

    if (!$topic && !worksheet_sub_is_foundation_or_level1($level)) {
        $topic = db_one(
            'SELECT t.id, t.level_id, t.topic_name, t.total_questions, l.level_name, NULL AS paper_id, NULL AS paper_number, "topic" AS content_type
             FROM worksheet_topics t
             INNER JOIN worksheet_levels l ON l.id = t.level_id
             WHERE t.id = :id AND t.level_id = :level_id
             LIMIT 1',
            ['id' => $topicId, 'level_id' => $level['id']]
        );
    }

    if (!$topic) {
        json_response(['message' => 'Worksheet paper not found for your subscription'], 404);
    }

    return [$student, $level, $topic];
}

function worksheet_sub_competition_passing_percentage(): float
{
    ensure_worksheet_sub_schema();
    return (float) (db_value('SELECT passing_percentage FROM worksheet_competition_config WHERE id = 1') ?: 90);
}

function worksheet_sub_competition_state(string $studentId, string $topicId): array
{
    ensure_worksheet_sub_schema();
    $passing = worksheet_sub_competition_passing_percentage();
    $row = db_one(
        'SELECT unlocked_tier, passing_percentage FROM worksheet_competition_unlocks WHERE student_id = :student_id AND topic_id = :topic_id LIMIT 1',
        ['student_id' => $studentId, 'topic_id' => $topicId]
    );
    $unlockedTier = $row ? (int) ($row['unlocked_tier'] ?? 15) : 15;
    $unlockedTier = max(1, min(15, $unlockedTier));
    $tiers = [];
    for ($tier = 15; $tier >= 1; $tier--) {
        $tiers[] = [
            'seconds' => $tier,
            'unlocked' => $tier >= $unlockedTier,
            'current' => $tier === $unlockedTier,
        ];
    }
    return ['unlockedTier' => $unlockedTier, 'passingPercentage' => $passing, 'tiers' => $tiers];
}

function worksheet_sub_assert_competition_tier(string $studentId, string $topicId, int $speedTier): void
{
    $state = worksheet_sub_competition_state($studentId, $topicId);
    if ($speedTier < 1 || $speedTier > 15 || $speedTier < (int) $state['unlockedTier']) {
        json_response(['message' => 'This competition speed tier is locked. Clear the previous tier first.'], 403);
    }
}

function worksheet_sub_maybe_unlock_next_tier(string $studentId, string $topicId, int $speedTier, float $accuracy): void
{
    $state = worksheet_sub_competition_state($studentId, $topicId);
    $passing = (float) $state['passingPercentage'];
    if ($accuracy < $passing || $speedTier !== (int) $state['unlockedTier'] || $speedTier <= 1) {
        return;
    }
    $nextTier = $speedTier - 1;
    db_exec_sql(
        'INSERT INTO worksheet_competition_unlocks (id, student_id, topic_id, unlocked_tier, passing_percentage, updated_at)
         VALUES (:id, :student_id, :topic_id, :unlocked_tier, :passing_percentage, :updated_at)
         ON DUPLICATE KEY UPDATE unlocked_tier = LEAST(unlocked_tier, VALUES(unlocked_tier)), passing_percentage = VALUES(passing_percentage), updated_at = VALUES(updated_at)',
        [
            'id' => uuid_v4(),
            'student_id' => $studentId,
            'topic_id' => $topicId,
            'unlocked_tier' => $nextTier,
            'passing_percentage' => $passing,
            'updated_at' => now_sql(),
        ]
    );
}
function controller_student_worksheet_sub_dashboard(array $ctx): void
{
    [$student, $level] = worksheet_sub_require_student_level($ctx);
    if (!worksheet_sub_is_foundation_or_level1($level)) {
        worksheet_sub_ensure_dynamic_topics($level);
    }

    $topics = worksheet_sub_level_topics($level);
    worksheet_sub_debug_log('dashboard_resolved', [
        'user_id' => $ctx['user']['id'] ?? null,
        'student_id' => $student['id'] ?? null,
        'subscription_id' => $level['subscription_id'] ?? null,
        'requested_level_id' => worksheet_sub_request_level_id(),
        'resolved_level_id' => $level['id'] ?? null,
        'resolved_level_name' => $level['level_name'] ?? null,
        'content_source' => worksheet_sub_is_foundation_or_level1($level) ? 'worksheet_papers' : 'dynamic_generator',
        'paper_topic_count' => count($topics),
    ]);

    if (!$topics) {
        worksheet_sub_log_access_denied(
            (string) $student['id'],
            (string) ($level['id'] ?? ''),
            'Active worksheet subscription resolved, but no worksheet papers/topics were found for the level.',
            [
                'subscriptionId' => $level['subscription_id'] ?? null,
                'planId' => $level['plan_id'] ?? null,
                'programType' => $level['program_type'] ?? null,
                'levelName' => $level['level_name'] ?? null,
            ]
        );
    }

    if (worksheet_sub_is_vedic_level_name((string) ($level['level_name'] ?? ''))) {
        foreach ($topics as $index => $topic) {
            $topics[$index]['mode'] = 'vedic';
            $topics[$index]['competition'] = worksheet_sub_competition_state((string) $student['id'], (string) $topic['id']);
        }
    }
    json_response(['level' => $level, 'topics' => $topics]);
}

function controller_student_worksheet_sub_questions(array $ctx, string $topicId): void
{
    $access = worksheet_sub_require_topic_access($ctx, $topicId);
    $student = $access[0];
    $topic = $access[2];
    $mode = strtolower(trim((string) ($_GET['mode'] ?? 'practice')));
    if ($mode === 'competition') {
        $speedTier = max(1, min(15, (int) ($_GET['speedTier'] ?? 15)));
        worksheet_sub_assert_competition_tier((string) $student['id'], $topicId, $speedTier);
    }

    $isPaper = (($topic['content_type'] ?? '') === 'paper') || !empty($topic['paper_id']);
    $hasQuestionPaperId = worksheet_sub_table_has_column('worksheet_questions', 'paper_id');
    $hasQuestionNumber = worksheet_sub_table_has_column('worksheet_questions', 'question_number');
    $paperSelect = $hasQuestionPaperId ? 'paper_id' : 'NULL AS paper_id';
    $orderBy = $hasQuestionNumber ? 'COALESCE(question_number, 999999), id ASC' : 'id ASC';

    if ($isPaper && $hasQuestionPaperId) {
        $questions = db_all(
            'SELECT id, COALESCE(topic_id, paper_id) AS topic_id, ' . $paperSelect . ', question, answer
             FROM worksheet_questions
             WHERE paper_id = :paper_id
             ORDER BY ' . $orderBy,
            ['paper_id' => $topicId]
        );
    } else {
        $questions = db_all(
            'SELECT id, topic_id, ' . $paperSelect . ', question, answer
             FROM worksheet_questions
             WHERE topic_id = :topic_id
             ORDER BY ' . $orderBy,
            ['topic_id' => $topicId]
        );
    }

    if (!$questions && !$isPaper) {
        $questions = worksheet_sub_generate_dynamic_questions($topic);
    }

    $hasGeneratedOptions = $questions && !empty($questions[0]['generated']);
    if ($questions && !$hasGeneratedOptions) {
        $params = [];
        $placeholders = [];
        foreach ($questions as $index => $question) {
            $key = 'question_id_' . $index;
            $params[$key] = $question['id'];
            $placeholders[] = ':' . $key;
            $questions[$index]['options'] = [];
        }

        $options = db_all(
            'SELECT question_id, option_text
             FROM question_options
             WHERE question_id IN (' . implode(',', $placeholders) . ')
             ORDER BY question_id ASC, sort_order ASC',
            $params
        );

        $byQuestion = [];
        foreach ($options as $option) {
            $byQuestion[(string) $option['question_id']][] = (string) $option['option_text'];
        }
        foreach ($questions as $index => $question) {
            $questions[$index]['options'] = $byQuestion[(string) $question['id']] ?? [];
        }
    }

    json_response(['questions' => $questions]);
}

function worksheet_attempt_payload(array $row): array
{
    return [
        'id' => $row['id'],
        'student_id' => $row['student_id'] ?? null,
        'topic_id' => $row['topic_id'] ?? ($row['paper_id'] ?? ''),
        'paper_id' => $row['paper_id'] ?? null,
        'worksheet_name' => $row['worksheet_name'] ?? 'Worksheet',
        'mode' => $row['mode'] ?? 'practice',
        'total_questions' => (int) ($row['total_questions'] ?? 0),
        'attempted' => (int) ($row['attempted'] ?? 0),
        'correct_answers' => (int) ($row['correct_answers'] ?? 0),
        'wrong_answers' => (int) ($row['wrong_answers'] ?? 0),
        'score' => (int) ($row['score'] ?? 0),
        'accuracy' => (float) ($row['percentage'] ?? 0),
        'percentage' => (float) ($row['percentage'] ?? 0),
        'time_taken' => (int) ($row['duration_seconds'] ?? 0),
        'duration_seconds' => (int) ($row['duration_seconds'] ?? 0),
        'status' => $row['status'] ?? 'Needs Practice',
        'started_at' => sql_datetime_to_iso_utc($row['started_at'] ?? null),
        'completed_at' => sql_datetime_to_iso_utc($row['completed_at'] ?? null),
        'created_at' => sql_datetime_to_iso_utc($row['created_at'] ?? null),
        'review' => json_decode((string) ($row['review_json'] ?? '[]'), true) ?: [],
    ];
}

function worksheet_practice_payload(array $row): array
{
    foreach (['started_at', 'completed_at', 'created_at'] as $field) {
        if (array_key_exists($field, $row)) $row[$field] = sql_datetime_to_iso_utc($row[$field]);
    }
    if (array_key_exists('review_json', $row)) {
        $row['review'] = json_decode((string) ($row['review_json'] ?? '[]'), true) ?: [];
        unset($row['review_json']);
    }
    return $row;
}

function worksheet_sub_sanitize_submitted_review(mixed $submitted, int $limit): array
{
    if (!is_array($submitted)) return [];
    $review = [];
    foreach (array_slice($submitted, 0, max(0, $limit)) as $index => $item) {
        if (!is_array($item)) continue;
        $questionId = substr(trim((string) ($item['questionId'] ?? '')), 0, 255);
        $questionText = substr(trim((string) ($item['questionText'] ?? '')), 0, 2000);
        $correctAnswer = substr(trim((string) ($item['correctAnswer'] ?? '')), 0, 500);
        $studentAnswer = substr(trim((string) ($item['studentAnswer'] ?? $item['selectedAnswer'] ?? '')), 0, 500);
        if ($questionId === '' || $questionText === '') continue;
        $review[] = [
            'questionId' => $questionId,
            'questionNumber' => max(1, (int) ($item['questionNumber'] ?? ($index + 1))),
            'questionText' => $questionText,
            'studentAnswer' => $studentAnswer,
            'selectedAnswer' => $studentAnswer,
            'correctAnswer' => $correctAnswer,
            'isCorrect' => $studentAnswer !== '' && $studentAnswer === $correctAnswer,
        ];
    }
    return $review;
}

function worksheet_sub_save_paper_attempt(array $student, array $topic, array $data): array
{
    $topicId = (string) ($topic['id'] ?? '');
    $paperId = (string) ($topic['paper_id'] ?? $topicId);
    $levelId = (string) ($topic['level_id'] ?? '');
    $mode = strtolower(trim((string) ($data['mode'] ?? 'practice')));
    $mode = in_array($mode, ['practice', 'visualization', 'competition'], true) ? $mode : 'practice';
    $answers = is_array($data['answers'] ?? null) ? $data['answers'] : [];
    $duration = max(0, (int) ($data['durationSeconds'] ?? $data['timeTaken'] ?? 0));
    $startedAt = trim((string) ($data['startedAt'] ?? ''));
    $startedSql = $startedAt !== '' && strtotime($startedAt) !== false ? gmdate('Y-m-d H:i:s', strtotime($startedAt)) : null;
    $completedAt = now_sql();

    $hasQuestionNumber = worksheet_sub_table_has_column('worksheet_questions', 'question_number');
    $orderBy = $hasQuestionNumber ? 'COALESCE(question_number, 999999), id ASC' : 'id ASC';
    $questions = db_all(
        'SELECT id, question, answer, ' . ($hasQuestionNumber ? 'question_number' : 'NULL AS question_number') . '
         FROM worksheet_questions
         WHERE paper_id = :paper_id
         ORDER BY ' . $orderBy,
        ['paper_id' => $paperId]
    );

    $correct = 0;
    $attempted = 0;
    $review = [];
    foreach ($questions as $index => $question) {
        $qid = (string) $question['id'];
        $selected = isset($answers[$qid]) ? trim((string) $answers[$qid]) : '';
        $expected = trim((string) ($question['answer'] ?? ''));
        $isAttempted = $selected !== '';
        $isCorrect = $isAttempted && $selected === $expected;
        if ($isAttempted) {
            $attempted++;
        }
        if ($isCorrect) {
            $correct++;
        }
        $review[] = [
            'questionId' => $qid,
            'questionNumber' => (int) ($question['question_number'] ?? ($index + 1)),
            'questionText' => $question['question'] ?? '',
            'studentAnswer' => $selected,
            'selectedAnswer' => $selected,
            'correctAnswer' => $expected,
            'isCorrect' => $isCorrect,
        ];
    }

    $total = count($questions);
    $wrong = max(0, $attempted - $correct);
    $percentage = $total > 0 ? round(($correct / $total) * 100, 2) : 0;
    $status = $percentage >= 40 ? 'Pass' : 'Fail';
    $id = uuid_v4();
    $now = now_sql();

    db_exec_sql(
        'INSERT INTO worksheet_attempts
         (id, student_id, level_id, paper_id, topic_id, worksheet_name, mode, total_questions, attempted, correct_answers, wrong_answers,
          score, percentage, status, answers_json, review_json, started_at, completed_at, duration_seconds, created_at, updated_at)
         VALUES
         (:id, :student_id, :level_id, :paper_id, :topic_id, :worksheet_name, :mode, :total_questions, :attempted, :correct_answers, :wrong_answers,
          :score, :percentage, :status, :answers_json, :review_json, :started_at, :completed_at, :duration_seconds, :created_at, :updated_at)',
        [
            'id' => $id,
            'student_id' => $student['id'],
            'level_id' => $levelId !== '' ? $levelId : null,
            'paper_id' => $paperId,
            'topic_id' => $topicId,
            'worksheet_name' => (string) ($topic['topic_name'] ?? 'Worksheet'),
            'mode' => $mode,
            'total_questions' => $total,
            'attempted' => $attempted,
            'correct_answers' => $correct,
            'wrong_answers' => $wrong,
            'score' => $correct,
            'percentage' => $percentage,
            'status' => $status,
            'answers_json' => json_encode($answers, JSON_UNESCAPED_SLASHES),
            'review_json' => json_encode($review, JSON_UNESCAPED_SLASHES),
            'started_at' => $startedSql,
            'completed_at' => $completedAt,
            'duration_seconds' => $duration,
            'created_at' => $now,
            'updated_at' => $now,
        ]
    );

    $row = db_one('SELECT * FROM worksheet_attempts WHERE id = :id LIMIT 1', ['id' => $id]);
    return worksheet_attempt_payload($row ?: []);
}

function controller_student_worksheet_sub_practices(array $ctx, string $topicId): void
{
    $access = worksheet_sub_require_topic_access($ctx, $topicId);
    $student = $access[0];
    $level = $access[1];
    $content = $access[2];

    if (worksheet_sub_is_foundation_or_level1($level) || (($content['content_type'] ?? '') === 'paper')) {
        $rows = db_all(
            'SELECT * FROM worksheet_attempts
             WHERE student_id = :student_id AND (paper_id = :paper_id OR topic_id = :topic_id)
             ORDER BY completed_at DESC, created_at DESC',
            ['student_id' => $student['id'], 'paper_id' => $topicId, 'topic_id' => $topicId]
        );
        json_response(['practices' => array_map('worksheet_attempt_payload', $rows)]);
    }

    $rows = db_all(
        'SELECT id, student_id, subscription_id, program_type, level_id, topic_id, subtopic_code, score, accuracy, percentage,
                total_questions, attempted_count AS attempted, correct_answers, wrong_count AS wrong_answers, skipped_count,
                time_taken, duration_seconds, status, mode, speed_tier, started_at, completed_at, passed, created_at
         FROM worksheet_practices
         WHERE student_id = :student_id AND topic_id = :topic_id
         ORDER BY COALESCE(completed_at, created_at) DESC, created_at DESC',
        ['student_id' => $student['id'], 'topic_id' => $topicId]
    );
    json_response(['practices' => array_map('worksheet_practice_payload', $rows)]);
}

function controller_student_worksheet_sub_save_practice(array $ctx, array $data): void
{
    ensure_worksheet_sub_schema();

    $student = current_student($ctx['user']['id']);
    if (!$student) {
        json_response(['message' => 'Student not found'], 404);
    }

    $topicId = trim((string) ($data['topicId'] ?? ''));
    $totalQuestions = max(0, (int) ($data['totalQuestions'] ?? 0));
    $correctAnswers = max(0, (int) ($data['correctAnswers'] ?? 0));
    $score = max(0, (int) ($data['score'] ?? $correctAnswers));
    $accuracy = max(0, min(100, (float) ($data['accuracy'] ?? 0)));
    $timeTaken = max(0, (int) ($data['timeTaken'] ?? 0));
    $mode = strtolower(trim((string) ($data['mode'] ?? 'practice')));
    $mode = in_array($mode, ['practice', 'visualization', 'competition'], true) ? $mode : 'practice';
    $speedTier = isset($data['speedTier']) ? max(1, min(15, (int) $data['speedTier'])) : null;
    if (!empty($data['levelId']) && empty($_GET['levelId'])) {
        $_GET['levelId'] = (string) $data['levelId'];
    }
    if (!empty($data['subscriptionId']) && empty($_GET['subscriptionId'])) {
        $_GET['subscriptionId'] = (string) $data['subscriptionId'];
    }
    if (!empty($data['program']) && empty($_GET['program'])) {
        $_GET['program'] = (string) $data['program'];
    }

    if ($topicId === '' || $totalQuestions <= 0) {
        json_response(['message' => 'topicId and totalQuestions are required'], 422);
    }

    $access = worksheet_sub_require_topic_access($ctx, $topicId);
    $level = $access[1];
    $topic = $access[2];

    if (worksheet_sub_is_foundation_or_level1($level) || (($topic['content_type'] ?? '') === 'paper')) {
        $attempt = worksheet_sub_save_paper_attempt($student, $topic, $data);
        json_response(['practice' => $attempt], 201);
    }

    if ($mode === 'competition') {
        if (!worksheet_sub_is_vedic_level_name((string) ($topic['level_name'] ?? ''))) {
            json_response(['message' => 'This worksheet mode is unavailable.'], 422);
        }
        $speedTier = $speedTier ?: 15;
        worksheet_sub_assert_competition_tier((string) $student['id'], $topicId, $speedTier);
    }

    $answers = is_array($data['answers'] ?? null) ? $data['answers'] : [];
    $review = worksheet_sub_sanitize_submitted_review($data['review'] ?? [], $totalQuestions);
    $attemptedCount = 0;
    foreach ($answers as $value) {
        if (trim((string) $value) !== '') {
            $attemptedCount++;
        }
    }
    $wrongCount = max(0, $attemptedCount - $correctAnswers);
    $skippedCount = max(0, $totalQuestions - $attemptedCount);
    $passing = worksheet_sub_competition_passing_percentage();
    $passed = $mode === 'competition' ? ($accuracy >= $passing) : ($accuracy >= 40);
    $startedAt = trim((string) ($data['startedAt'] ?? ''));
    $startedSql = $startedAt !== '' && strtotime($startedAt) !== false ? gmdate('Y-m-d H:i:s', strtotime($startedAt)) : null;
    $completedAt = now_sql();
    $programType = worksheet_sub_normalize_program_type((string) ($data['program'] ?? ($level['program_type'] ?? ''))) ?: (worksheet_sub_is_vedic_level_name((string) ($topic['level_name'] ?? '')) ? 'vedic_maths' : 'abacus');

    $id = uuid_v4();
    $now = now_sql();
    db_exec_sql(
        'INSERT INTO worksheet_practices
         (id, student_id, subscription_id, program_type, level_id, topic_id, subtopic_code, score, accuracy, percentage,
          total_questions, attempted_count, correct_answers, wrong_count, skipped_count, time_taken, duration_seconds,
          status, mode, speed_tier, answers_json, review_json, started_at, completed_at, passed, created_at, updated_at)
         VALUES
         (:id, :student_id, :subscription_id, :program_type, :level_id, :topic_id, :subtopic_code, :score, :accuracy, :percentage,
          :total_questions, :attempted_count, :correct_answers, :wrong_count, :skipped_count, :time_taken, :duration_seconds,
          :status, :mode, :speed_tier, :answers_json, :review_json, :started_at, :completed_at, :passed, :created_at, :updated_at)',
        [
            'id' => $id,
            'student_id' => $student['id'],
            'subscription_id' => $level['subscription_id'] ?? ($data['subscriptionId'] ?? null),
            'program_type' => $programType,
            'level_id' => $level['id'] ?? ($data['levelId'] ?? null),
            'topic_id' => $topicId,
            'subtopic_code' => $topicId,
            'score' => $score,
            'accuracy' => $accuracy,
            'percentage' => $accuracy,
            'total_questions' => $totalQuestions,
            'attempted_count' => $attemptedCount,
            'correct_answers' => $correctAnswers,
            'wrong_count' => $wrongCount,
            'skipped_count' => $skippedCount,
            'time_taken' => $timeTaken,
            'duration_seconds' => max(0, (int) ($data['durationSeconds'] ?? $timeTaken)),
            'status' => $mode === 'competition' ? ($passed ? 'Pass' : 'Fail') : worksheet_sub_status($accuracy),
            'mode' => $mode,
            'speed_tier' => $speedTier,
            'answers_json' => json_encode($answers, JSON_UNESCAPED_SLASHES),
            'review_json' => json_encode($review, JSON_UNESCAPED_SLASHES),
            'started_at' => $startedSql,
            'completed_at' => $completedAt,
            'passed' => $passed ? 1 : 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]
    );

    if ($mode === 'competition' && $speedTier !== null) {
        worksheet_sub_maybe_unlock_next_tier((string) $student['id'], $topicId, $speedTier, $accuracy);
    }

    json_response([
        'practice' => worksheet_practice_payload(db_one(
            'SELECT id, student_id, subscription_id, program_type, level_id, topic_id, subtopic_code, score, accuracy, percentage,
                    total_questions, attempted_count AS attempted, correct_answers, wrong_count AS wrong_answers,
                    skipped_count, time_taken, duration_seconds, status, mode, speed_tier, review_json, started_at, completed_at, passed, created_at
             FROM worksheet_practices WHERE id = :id',
            ['id' => $id]
        ) ?: []),
    ], 201);
}
function controller_admin_worksheet_competition_config(): void
{
    ensure_worksheet_sub_schema();
    json_response(['passingPercentage' => worksheet_sub_competition_passing_percentage()]);
}

function controller_admin_update_worksheet_competition_config(array $data): void
{
    ensure_worksheet_sub_schema();
    $passing = max(1, min(100, (float) ($data['passingPercentage'] ?? $data['passing_percentage'] ?? 90)));
    db_exec_sql(
        'INSERT INTO worksheet_competition_config (id, passing_percentage, updated_at)
         VALUES (1, :passing_percentage, :updated_at)
         ON DUPLICATE KEY UPDATE passing_percentage = VALUES(passing_percentage), updated_at = VALUES(updated_at)',
        ['passing_percentage' => $passing, 'updated_at' => now_sql()]
    );
    controller_admin_worksheet_competition_config();
}
function controller_admin_worksheet_sub_levels(): void
{
    ensure_worksheet_sub_schema();
    json_response(['levels' => db_all('SELECT * FROM worksheet_levels ORDER BY id ASC')]);
}

function controller_admin_worksheet_sub_create_level(array $data): void
{
    ensure_worksheet_sub_schema();
    $name = trim((string) ($data['levelName'] ?? $data['level_name'] ?? ''));
    if ($name === '') {
        json_response(['message' => 'levelName is required'], 422);
    }

    $id = uuid_v4();
    db_exec_sql('INSERT INTO worksheet_levels (id, level_name) VALUES (:id, :level_name)', [
        'id' => $id,
        'level_name' => $name,
    ]);
    json_response(['level' => db_one('SELECT * FROM worksheet_levels WHERE id = :id', ['id' => $id])], 201);
}

function controller_admin_worksheet_sub_update_level(string $levelId, array $data): void
{
    ensure_worksheet_sub_schema();
    $name = trim((string) ($data['levelName'] ?? $data['level_name'] ?? ''));
    if ($name === '') {
        json_response(['message' => 'levelName is required'], 422);
    }
    db_exec_sql('UPDATE worksheet_levels SET level_name = :level_name WHERE id = :id', ['level_name' => $name, 'id' => $levelId]);
    json_response(['level' => db_one('SELECT * FROM worksheet_levels WHERE id = :id', ['id' => $levelId])]);
}

function controller_admin_worksheet_sub_delete_level(string $levelId): void
{
    ensure_worksheet_sub_schema();
    db_exec_sql('DELETE FROM worksheet_levels WHERE id = :id', ['id' => $levelId]);
    json_response(['message' => 'Level deleted']);
}

function controller_admin_worksheet_sub_topics(): void
{
    ensure_worksheet_sub_schema();
    $rows = db_all(
        'SELECT t.*, l.level_name
         FROM worksheet_topics t
         LEFT JOIN worksheet_levels l ON l.id = t.level_id
         ORDER BY t.id ASC'
    );
    json_response(['topics' => $rows]);
}

function controller_admin_worksheet_sub_create_topic(array $data): void
{
    ensure_worksheet_sub_schema();
    $levelId = trim((string) ($data['levelId'] ?? $data['level_id'] ?? ''));
    $name = trim((string) ($data['topicName'] ?? $data['topic_name'] ?? ''));
    $total = max(0, (int) ($data['totalQuestions'] ?? $data['total_questions'] ?? 0));
    if ($levelId === '' || $name === '') {
        json_response(['message' => 'levelId and topicName are required'], 422);
    }
    $id = uuid_v4();
    db_exec_sql(
        'INSERT INTO worksheet_topics (id, level_id, topic_name, total_questions) VALUES (:id, :level_id, :topic_name, :total_questions)',
        ['id' => $id, 'level_id' => $levelId, 'topic_name' => $name, 'total_questions' => $total]
    );
    json_response(['topic' => db_one('SELECT * FROM worksheet_topics WHERE id = :id', ['id' => $id])], 201);
}

function controller_admin_worksheet_sub_update_topic(string $topicId, array $data): void
{
    ensure_worksheet_sub_schema();
    $topic = db_one('SELECT * FROM worksheet_topics WHERE id = :id', ['id' => $topicId]);
    if (!$topic) {
        json_response(['message' => 'Topic not found'], 404);
    }
    db_exec_sql(
        'UPDATE worksheet_topics SET level_id = :level_id, topic_name = :topic_name, total_questions = :total_questions WHERE id = :id',
        [
            'level_id' => trim((string) ($data['levelId'] ?? $data['level_id'] ?? $topic['level_id'])),
            'topic_name' => trim((string) ($data['topicName'] ?? $data['topic_name'] ?? $topic['topic_name'])),
            'total_questions' => (int) ($data['totalQuestions'] ?? $data['total_questions'] ?? $topic['total_questions']),
            'id' => $topicId,
        ]
    );
    json_response(['topic' => db_one('SELECT * FROM worksheet_topics WHERE id = :id', ['id' => $topicId])]);
}

function controller_admin_worksheet_sub_delete_topic(string $topicId): void
{
    ensure_worksheet_sub_schema();
    db_exec_sql('DELETE FROM worksheet_topics WHERE id = :id', ['id' => $topicId]);
    json_response(['message' => 'Topic deleted']);
}

function controller_admin_worksheet_sub_questions(string $topicId): void
{
    ensure_worksheet_sub_schema();
    json_response(['questions' => db_all('SELECT * FROM worksheet_questions WHERE topic_id = :topic_id ORDER BY id ASC', ['topic_id' => $topicId])]);
}

function controller_admin_worksheet_sub_create_question(array $data): void
{
    ensure_worksheet_sub_schema();
    $topicId = trim((string) ($data['topicId'] ?? $data['topic_id'] ?? ''));
    $question = trim((string) ($data['question'] ?? ''));
    $answer = trim((string) ($data['answer'] ?? ''));
    if ($topicId === '' || $question === '' || $answer === '') {
        json_response(['message' => 'topicId, question and answer are required'], 422);
    }
    $id = uuid_v4();
    db_exec_sql(
        'INSERT INTO worksheet_questions (id, topic_id, question, answer) VALUES (:id, :topic_id, :question, :answer)',
        ['id' => $id, 'topic_id' => $topicId, 'question' => $question, 'answer' => $answer]
    );
    db_exec_sql('UPDATE worksheet_topics SET total_questions = (SELECT COUNT(*) FROM worksheet_questions WHERE topic_id = :topic_id) WHERE id = :topic_id', ['topic_id' => $topicId]);
    json_response(['question' => db_one('SELECT * FROM worksheet_questions WHERE id = :id', ['id' => $id])], 201);
}

function controller_admin_worksheet_sub_update_question(string $questionId, array $data): void
{
    ensure_worksheet_sub_schema();
    $row = db_one('SELECT * FROM worksheet_questions WHERE id = :id', ['id' => $questionId]);
    if (!$row) {
        json_response(['message' => 'Question not found'], 404);
    }
    db_exec_sql(
        'UPDATE worksheet_questions SET question = :question, answer = :answer WHERE id = :id',
        [
            'question' => trim((string) ($data['question'] ?? $row['question'])),
            'answer' => trim((string) ($data['answer'] ?? $row['answer'])),
            'id' => $questionId,
        ]
    );
    json_response(['question' => db_one('SELECT * FROM worksheet_questions WHERE id = :id', ['id' => $questionId])]);
}

function controller_admin_worksheet_sub_delete_question(string $questionId): void
{
    ensure_worksheet_sub_schema();
    $row = db_one('SELECT topic_id FROM worksheet_questions WHERE id = :id', ['id' => $questionId]);
    db_exec_sql('DELETE FROM worksheet_questions WHERE id = :id', ['id' => $questionId]);
    if ($row) {
        db_exec_sql('UPDATE worksheet_topics SET total_questions = (SELECT COUNT(*) FROM worksheet_questions WHERE topic_id = :topic_id) WHERE id = :topic_id', ['topic_id' => $row['topic_id']]);
    }
    json_response(['message' => 'Question deleted']);
}

function controller_admin_worksheet_sub_reports(): void
{
    ensure_worksheet_sub_schema();
    $topicId = trim((string) ($_GET['topicId'] ?? ''));
    $where = $topicId !== '' ? 'WHERE p.topic_id = :topic_id' : '';
    $params = $topicId !== '' ? ['topic_id' => $topicId] : [];
    $rows = db_all(
        "SELECT p.*, t.topic_name, u.name AS student_name, u.email AS student_email
         FROM worksheet_practices p
         INNER JOIN worksheet_topics t ON t.id = p.topic_id
         LEFT JOIN students s ON s.id = p.student_id
         LEFT JOIN users u ON u.id = s.user_id
         {$where}
         ORDER BY p.created_at DESC",
        $params
    );
    json_response(['reports' => $rows]);
}

function controller_admin_worksheet_sub_upload_csv(): void
{
    ensure_worksheet_sub_schema();
    $topicId = trim((string) ($_POST['topicId'] ?? ''));
    if ($topicId === '' || !isset($_FILES['file']) || !is_array($_FILES['file'])) {
        json_response(['message' => 'topicId and CSV file are required'], 422);
    }
    if (($_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        json_response(['message' => 'CSV upload failed'], 400);
    }

    $handle = fopen((string) $_FILES['file']['tmp_name'], 'r');
    if (!$handle) {
        json_response(['message' => 'Unable to read CSV'], 400);
    }

    $created = 0;
    $rowNumber = 0;
    while (($row = fgetcsv($handle)) !== false) {
        $rowNumber++;
        if ($rowNumber === 1 && strtolower(trim((string) ($row[0] ?? ''))) === 'question') {
            continue;
        }
        $question = trim((string) ($row[0] ?? ''));
        $answer = trim((string) ($row[1] ?? ''));
        if ($question === '' || $answer === '') {
            continue;
        }
        db_exec_sql(
            'INSERT INTO worksheet_questions (id, topic_id, question, answer) VALUES (:id, :topic_id, :question, :answer)',
            ['id' => uuid_v4(), 'topic_id' => $topicId, 'question' => $question, 'answer' => $answer]
        );
        $created++;
    }
    fclose($handle);

    db_exec_sql('UPDATE worksheet_topics SET total_questions = (SELECT COUNT(*) FROM worksheet_questions WHERE topic_id = :topic_id) WHERE id = :topic_id', ['topic_id' => $topicId]);
    json_response(['message' => 'CSV imported', 'created' => $created], 201);
}








