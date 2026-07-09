<?php

function worksheet_import_table_has_column(string $table, string $column): bool
{
    $row = db_one(
        'SELECT COUNT(*) AS c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name AND COLUMN_NAME = :column_name',
        ['table_name' => $table, 'column_name' => $column]
    );
    return ((int) ($row['c'] ?? 0)) > 0;
}

function ensure_worksheet_docx_import_schema(): void
{
    ensure_worksheet_sub_schema();

    db_exec_sql(
        'CREATE TABLE IF NOT EXISTS worksheet_papers (
            id VARCHAR(191) NOT NULL PRIMARY KEY,
            level_id VARCHAR(191) NOT NULL,
            topic_id VARCHAR(191) NULL,
            paper_number INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            total_questions INT NOT NULL DEFAULT 0,
            source_file VARCHAR(255) NULL,
            source_hash VARCHAR(64) NULL,
            import_batch VARCHAR(191) NULL,
            imported_at DATETIME NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            UNIQUE KEY uniq_worksheet_papers_level_paper (level_id, paper_number),
            INDEX idx_worksheet_papers_level_id (level_id),
            INDEX idx_worksheet_papers_topic_id (topic_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    if (!worksheet_import_table_has_column('worksheet_questions', 'paper_id')) {
        db_exec_sql('ALTER TABLE worksheet_questions ADD COLUMN paper_id VARCHAR(191) NULL AFTER topic_id');
    }
    if (!worksheet_import_table_has_column('worksheet_questions', 'question_number')) {
        db_exec_sql('ALTER TABLE worksheet_questions ADD COLUMN question_number INT NULL AFTER paper_id');
    }
    if (!worksheet_import_table_has_column('worksheet_questions', 'question_rows')) {
        db_exec_sql('ALTER TABLE worksheet_questions ADD COLUMN question_rows LONGTEXT NULL AFTER question');
    }
    if (!worksheet_import_table_has_column('worksheet_questions', 'source_hash')) {
        db_exec_sql('ALTER TABLE worksheet_questions ADD COLUMN source_hash VARCHAR(64) NULL AFTER answer');
    }
    if (!worksheet_import_table_has_column('worksheet_questions', 'import_batch')) {
        db_exec_sql('ALTER TABLE worksheet_questions ADD COLUMN import_batch VARCHAR(191) NULL AFTER source_hash');
    }
    if (!worksheet_import_table_has_column('worksheet_questions', 'created_at')) {
        db_exec_sql('ALTER TABLE worksheet_questions ADD COLUMN created_at DATETIME NULL AFTER import_batch');
    }
    if (!worksheet_import_table_has_column('worksheet_questions', 'updated_at')) {
        db_exec_sql('ALTER TABLE worksheet_questions ADD COLUMN updated_at DATETIME NULL AFTER created_at');
    }

    db_exec_sql(
        'CREATE TABLE IF NOT EXISTS question_options (
            id VARCHAR(191) NOT NULL PRIMARY KEY,
            question_id VARCHAR(191) NOT NULL,
            option_text VARCHAR(100) NOT NULL,
            is_correct TINYINT(1) NOT NULL DEFAULT 0,
            sort_order INT NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX idx_question_options_question_id (question_id),
            CONSTRAINT fk_question_options_question
                FOREIGN KEY (question_id) REFERENCES worksheet_questions(id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    db_exec_sql(
        'CREATE TABLE IF NOT EXISTS worksheet_import_runs (
            id VARCHAR(191) NOT NULL PRIMARY KEY,
            source_hash VARCHAR(64) NOT NULL,
            source_file VARCHAR(255) NOT NULL,
            level_number INT NOT NULL,
            paper_count INT NOT NULL DEFAULT 0,
            question_count INT NOT NULL DEFAULT 0,
            force_import TINYINT(1) NOT NULL DEFAULT 0,
            status VARCHAR(30) NOT NULL DEFAULT "success",
            message TEXT NULL,
            imported_at DATETIME NOT NULL,
            UNIQUE KEY uniq_worksheet_import_source_hash (source_hash)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
}

function worksheet_docx_tokens(string $path): array
{
    if (!is_file($path)) {
        throw new RuntimeException('DOCX file not found: ' . $path);
    }
    if (!class_exists('ZipArchive')) {
        throw new RuntimeException('PHP ZipArchive extension is required to import DOCX files.');
    }

    $zip = new ZipArchive();
    if ($zip->open($path) !== true) {
        throw new RuntimeException('Unable to open DOCX file: ' . basename($path));
    }

    $xml = $zip->getFromName('word/document.xml');
    $zip->close();
    if (!is_string($xml) || $xml === '') {
        throw new RuntimeException('word/document.xml not found in DOCX file: ' . basename($path));
    }

    $dom = new DOMDocument();
    $previous = libxml_use_internal_errors(true);
    $loaded = $dom->loadXML($xml);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);
    if (!$loaded) {
        throw new RuntimeException('Unable to parse DOCX XML: ' . basename($path));
    }

    $xpath = new DOMXPath($dom);
    $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
    $nodes = $xpath->query('//w:t');
    $tokens = [];
    if ($nodes === false) {
        return $tokens;
    }

    foreach ($nodes as $node) {
        $value = html_entity_decode(trim((string) $node->nodeValue), ENT_QUOTES | ENT_XML1, 'UTF-8');
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
        if ($value === '') {
            continue;
        }
        $tokens[] = $value;
    }

    return $tokens;
}

function worksheet_import_normalize_title(string $raw): string
{
    $title = html_entity_decode($raw, ENT_QUOTES | ENT_XML1, 'UTF-8');
    $title = str_replace(["\xC2\xA0", '–', '—'], [' ', '-', '-'], $title);
    $title = preg_replace('/\s+/u', ' ', $title) ?? $title;
    return strtoupper(trim($title));
}

function worksheet_docx_title_at(array $tokens, int $index): ?array
{
    $current = worksheet_import_normalize_title((string) ($tokens[$index] ?? ''));
    if (!str_starts_with($current, 'LEVEL')) {
        return null;
    }

    $windowTokens = array_slice($tokens, $index, 12);
    $title = worksheet_import_normalize_title(implode(' ', $windowTokens));
    if (preg_match('/LEVEL\s*(\d+).*?WORKSHEET\s+SUBSCRIPTION/i', $title, $m) !== 1) {
        return null;
    }

    $paperNumber = null;
    foreach ($windowTokens as $offset => $token) {
        $normalized = worksheet_import_normalize_title((string) $token);
        if (!str_starts_with($normalized, 'PAPER')) {
            continue;
        }

        if (preg_match('/PAPER\s*(\d+)/i', $normalized, $pm) === 1) {
            $paperNumber = (int) $pm[1];
            break;
        }

        $first = trim((string) ($windowTokens[$offset + 1] ?? ''));
        $second = trim((string) ($windowTokens[$offset + 2] ?? ''));
        $third = trim((string) ($windowTokens[$offset + 3] ?? ''));
        $fourth = trim((string) ($windowTokens[$offset + 4] ?? ''));
        if (preg_match('/^\d$/', $first) === 1 && preg_match('/^\d$/', $second) === 1 && $third === '1' && $fourth === '2') {
            $paperNumber = (int) ($first . $second);
            break;
        }
        if (preg_match('/^\d+$/', $first) === 1 && $second === '1' && $third === '2') {
            $paperNumber = (int) $first;
            break;
        }
    }

    if ($paperNumber === null || $paperNumber <= 0) {
        return null;
    }

    return [
        'level' => (int) $m[1],
        'paper' => $paperNumber,
        'title' => 'LEVEL ' . (int) $m[1] . ' - WORKSHEET SUBSCRIPTION PAPER ' . $paperNumber,
    ];
}
function worksheet_docx_extract_papers(string $path): array
{
    $tokens = worksheet_docx_tokens($path);
    $titles = [];
    $seen = [];

    foreach ($tokens as $index => $_token) {
        $title = worksheet_docx_title_at($tokens, $index);
        if (!$title) {
            continue;
        }
        $key = $title['level'] . ':' . $title['paper'];
        if (isset($seen[$key])) {
            continue;
        }
        $seen[$key] = true;
        $title['index'] = $index;
        $titles[] = $title;
    }

    $papers = [];
    $groups = ['A', 'B', 'C', 'D', 'E', 'F'];
    foreach ($titles as $titleIndex => $title) {
        $start = (int) $title['index'] + 1;
        $end = isset($titles[$titleIndex + 1]) ? (int) $titles[$titleIndex + 1]['index'] : count($tokens);
        $block = array_slice($tokens, $start, max(0, $end - $start));
        $questions = [];

        foreach ($groups as $groupIndex => $group) {
            $groupPos = null;
            foreach ($block as $i => $token) {
                if (strtoupper(trim((string) $token)) === $group) {
                    $groupPos = $i;
                    break;
                }
            }
            if ($groupPos === null) {
                continue;
            }

            $values = [];
            for ($i = $groupPos + 1; $i < count($block); $i++) {
                $token = strtoupper(trim((string) $block[$i]));
                if ($token === 'ANS' || in_array($token, $groups, true)) {
                    break;
                }
                if (($token === '+' || $token === '-') && isset($block[$i + 1])) {
                    $next = trim((string) $block[$i + 1]);
                    if (preg_match('/^\d+$/', $next) === 1) {
                        $values[] = (int) ($token . $next);
                        $i++;
                    }
                } elseif (preg_match('/^[+-]?\d+$/', $token) === 1) {
                    $values[] = (int) $token;
                }
                if (count($values) >= 30) {
                    break;
                }
            }

            for ($q = 0; $q < 10; $q++) {
                $rows = array_slice($values, $q * 3, 3);
                if (count($rows) !== 3) {
                    continue;
                }
                $number = ($groupIndex * 10) + $q + 1;
                $questions[] = [
                    'number' => $number,
                    'rows' => $rows,
                    'question' => implode("\n", array_map(static fn(int $n): string => $n > 0 ? '+' . $n : (string) $n, $rows)),
                    'answer' => array_sum($rows),
                ];
            }
        }

        usort($questions, static fn(array $a, array $b): int => $a['number'] <=> $b['number']);
        $papers[] = [
            'level' => (int) $title['level'],
            'paper' => (int) $title['paper'],
            'title' => (string) $title['title'],
            'questions' => $questions,
        ];
    }

    usort($papers, static fn(array $a, array $b): int => [$a['level'], $a['paper']] <=> [$b['level'], $b['paper']]);
    return $papers;
}

function worksheet_import_options(int $answer): array
{
    $options = [$answer];
    $deltas = [1, -1, 2, -2, 3, -3, 4, -4, 5, -5, 7, -7, 10, -10];
    foreach ($deltas as $delta) {
        $candidate = $answer + $delta;
        if (!in_array($candidate, $options, true)) {
            $options[] = $candidate;
        }
        if (count($options) === 4) {
            break;
        }
    }
    while (count($options) < 4) {
        $candidate = $answer + count($options) + 11;
        if (!in_array($candidate, $options, true)) {
            $options[] = $candidate;
        }
    }

    $seed = abs($answer) + 17;
    usort($options, static fn(int $a, int $b): int => (($a * 31 + $seed) % 97) <=> (($b * 31 + $seed) % 97));
    return $options;
}

function worksheet_import_find_level_id(int $levelNumber): string
{
    ensure_worksheet_docx_import_schema();
    $patterns = $levelNumber === 0
        ? ['Abacus Worksheet Level 0 (Foundation)', 'Abacus Worksheet Foundation']
        : ['Abacus Worksheet Level ' . $levelNumber];

    foreach ($patterns as $name) {
        $row = db_one('SELECT id FROM levels WHERE level_name = :level_name LIMIT 1', ['level_name' => $name]);
        if ($row) {
            db_exec_sql(
                'INSERT INTO worksheet_levels (id, level_name) VALUES (:id, :level_name) ON DUPLICATE KEY UPDATE level_name = VALUES(level_name)',
                ['id' => $row['id'], 'level_name' => $name]
            );
            return (string) $row['id'];
        }
    }

    $course = db_one('SELECT id FROM courses WHERE slug = "abacus-worksheet" LIMIT 1');
    if (!$course && function_exists('ensure_worksheet_subscription_plans')) {
        ensure_worksheet_subscription_plans();
        $course = db_one('SELECT id FROM courses WHERE slug = "abacus-worksheet" LIMIT 1');
    }

    $id = uuid_v4();
    $name = $levelNumber === 0 ? 'Abacus Worksheet Level 0 (Foundation)' : 'Abacus Worksheet Level ' . $levelNumber;
    $now = now_sql();
    db_exec_sql(
        'INSERT INTO levels (id, level_name, course_id, duration, description, created_at, updated_at)
         VALUES (:id, :level_name, :course_id, 0, :description, :created_at, :updated_at)',
        [
            'id' => $id,
            'level_name' => $name,
            'course_id' => $course['id'] ?? null,
            'description' => $name,
            'created_at' => $now,
            'updated_at' => $now,
        ]
    );
    db_exec_sql('INSERT INTO worksheet_levels (id, level_name) VALUES (:id, :level_name)', ['id' => $id, 'level_name' => $name]);
    return $id;
}

function worksheet_import_delete_existing_papers(string $levelId): void
{
    $papers = db_all('SELECT id, topic_id FROM worksheet_papers WHERE level_id = :level_id', ['level_id' => $levelId]);
    foreach ($papers as $paper) {
        if (!empty($paper['topic_id'])) {
            db_exec_sql('DELETE FROM worksheet_topics WHERE id = :id', ['id' => $paper['topic_id']]);
        } else {
            db_exec_sql('DELETE FROM worksheet_questions WHERE paper_id = :paper_id', ['paper_id' => $paper['id']]);
        }
    }
    db_exec_sql('DELETE FROM worksheet_papers WHERE level_id = :level_id', ['level_id' => $levelId]);
}

function worksheet_import_docx_file(string $path, bool $force = false): array
{
    ensure_worksheet_docx_import_schema();

    $sourceHash = hash_file('sha256', $path);
    if (!$force) {
        $existing = db_one('SELECT * FROM worksheet_import_runs WHERE source_hash = :source_hash AND status = "success" LIMIT 1', ['source_hash' => $sourceHash]);
        if ($existing) {
            return [
                'sourceFile' => basename($path),
                'sourceHash' => $sourceHash,
                'skipped' => true,
                'message' => 'Already imported. Use re-import to replace existing papers.',
                'papers' => (int) ($existing['paper_count'] ?? 0),
                'questions' => (int) ($existing['question_count'] ?? 0),
            ];
        }
    }

    $papers = worksheet_docx_extract_papers($path);
    if (!$papers) {
        throw new RuntimeException('No worksheet subscription papers were detected in ' . basename($path));
    }

    $levels = array_values(array_unique(array_map(static fn(array $paper): int => (int) $paper['level'], $papers)));
    $expectedByLevel = [0 => 10, 1 => 60];
    foreach ($levels as $levelNumber) {
        $count = count(array_filter($papers, static fn(array $paper): bool => (int) $paper['level'] === $levelNumber));
        if (isset($expectedByLevel[$levelNumber]) && $count !== $expectedByLevel[$levelNumber]) {
            throw new RuntimeException('Expected ' . $expectedByLevel[$levelNumber] . ' papers for Level ' . $levelNumber . ', found ' . $count . '.');
        }
    }

    foreach ($papers as $paper) {
        if (count($paper['questions']) !== 60) {
            throw new RuntimeException($paper['title'] . ' must contain 60 questions; found ' . count($paper['questions']) . '.');
        }
    }

    $batch = uuid_v4();
    $now = now_sql();
    $createdPapers = 0;
    $createdQuestions = 0;

    try {
        $levelIds = [];
        foreach ($levels as $levelNumber) {
            $levelIds[$levelNumber] = worksheet_import_find_level_id($levelNumber);
            if ($force) {
                worksheet_import_delete_existing_papers($levelIds[$levelNumber]);
            }
        }

        foreach ($papers as $paper) {
            $levelId = $levelIds[(int) $paper['level']];
            $topicId = uuid_v4();
            $paperId = uuid_v4();
            db_exec_sql(
                'INSERT INTO worksheet_topics (id, level_id, topic_name, total_questions) VALUES (:id, :level_id, :topic_name, :total_questions)
                 ON DUPLICATE KEY UPDATE topic_name = VALUES(topic_name), total_questions = VALUES(total_questions)',
                ['id' => $topicId, 'level_id' => $levelId, 'topic_name' => $paper['title'], 'total_questions' => 60]
            );
            db_exec_sql(
                'INSERT INTO worksheet_papers
                 (id, level_id, topic_id, paper_number, title, total_questions, source_file, source_hash, import_batch, imported_at, created_at, updated_at)
                 VALUES
                 (:id, :level_id, :topic_id, :paper_number, :title, :total_questions, :source_file, :source_hash, :import_batch, :imported_at, :created_at, :updated_at)
                 ON DUPLICATE KEY UPDATE topic_id = VALUES(topic_id), title = VALUES(title), total_questions = VALUES(total_questions), source_file = VALUES(source_file), source_hash = VALUES(source_hash), import_batch = VALUES(import_batch), imported_at = VALUES(imported_at), updated_at = VALUES(updated_at)',
                [
                    'id' => $paperId,
                    'level_id' => $levelId,
                    'topic_id' => $topicId,
                    'paper_number' => $paper['paper'],
                    'title' => $paper['title'],
                    'total_questions' => 60,
                    'source_file' => basename($path),
                    'source_hash' => $sourceHash,
                    'import_batch' => $batch,
                    'imported_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
            $storedPaper = db_one('SELECT id FROM worksheet_papers WHERE level_id = :level_id AND paper_number = :paper_number LIMIT 1', ['level_id' => $levelId, 'paper_number' => $paper['paper']]);
            $paperId = (string) ($storedPaper['id'] ?? $paperId);
            $createdPapers++;

            foreach ($paper['questions'] as $question) {
                $questionId = uuid_v4();
                db_exec_sql(
                    'INSERT INTO worksheet_questions
                     (id, topic_id, paper_id, question_number, question, question_rows, answer, source_hash, import_batch, created_at, updated_at)
                     VALUES
                     (:id, :topic_id, :paper_id, :question_number, :question, :question_rows, :answer, :source_hash, :import_batch, :created_at, :updated_at)',
                    [
                        'id' => $questionId,
                        'topic_id' => $topicId,
                        'paper_id' => $paperId,
                        'question_number' => $question['number'],
                        'question' => $question['question'],
                        'question_rows' => json_encode($question['rows'], JSON_UNESCAPED_SLASHES),
                        'answer' => (string) $question['answer'],
                        'source_hash' => $sourceHash,
                        'import_batch' => $batch,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
                foreach (worksheet_import_options((int) $question['answer']) as $sort => $option) {
                    db_exec_sql(
                        'INSERT INTO question_options (id, question_id, option_text, is_correct, sort_order, created_at, updated_at)
                         VALUES (:id, :question_id, :option_text, :is_correct, :sort_order, :created_at, :updated_at)',
                        [
                            'id' => uuid_v4(),
                            'question_id' => $questionId,
                            'option_text' => (string) $option,
                            'is_correct' => $option === (int) $question['answer'] ? 1 : 0,
                            'sort_order' => $sort + 1,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]
                    );
                }
                $createdQuestions++;
            }
        }

        db_exec_sql(
            'INSERT INTO worksheet_import_runs (id, source_hash, source_file, level_number, paper_count, question_count, force_import, status, message, imported_at)
             VALUES (:id, :source_hash, :source_file, :level_number, :paper_count, :question_count, :force_import, :status, :message, :imported_at)
             ON DUPLICATE KEY UPDATE paper_count = VALUES(paper_count), question_count = VALUES(question_count), force_import = VALUES(force_import), status = VALUES(status), message = VALUES(message), imported_at = VALUES(imported_at)',
            [
                'id' => uuid_v4(),
                'source_hash' => $sourceHash,
                'source_file' => basename($path),
                'level_number' => (int) $levels[0],
                'paper_count' => $createdPapers,
                'question_count' => $createdQuestions,
                'force_import' => $force ? 1 : 0,
                'status' => 'success',
                'message' => 'Imported successfully',
                'imported_at' => $now,
            ]
        );
    } catch (Throwable $e) {
        throw $e;
    }

    return [
        'sourceFile' => basename($path),
        'sourceHash' => $sourceHash,
        'skipped' => false,
        'message' => 'Imported successfully',
        'papers' => $createdPapers,
        'questions' => $createdQuestions,
    ];
}

function worksheet_import_docx_files(array $paths, bool $force = false): array
{
    $results = [];
    foreach ($paths as $path) {
        $path = trim((string) $path);
        if ($path === '') {
            continue;
        }
        $results[] = worksheet_import_docx_file($path, $force);
    }
    return $results;
}

function worksheet_import_uploaded_docx_files(string $fieldName, bool $force = false): array
{
    if (!isset($_FILES[$fieldName]) || !is_array($_FILES[$fieldName])) {
        throw new RuntimeException('Please upload one or more DOCX files.');
    }

    $files = $_FILES[$fieldName];
    $names = is_array($files['name'] ?? null) ? $files['name'] : [$files['name'] ?? ''];
    $tmpNames = is_array($files['tmp_name'] ?? null) ? $files['tmp_name'] : [$files['tmp_name'] ?? ''];
    $errors = is_array($files['error'] ?? null) ? $files['error'] : [$files['error'] ?? UPLOAD_ERR_NO_FILE];

    $paths = [];
    foreach ($names as $i => $name) {
        if (($errors[$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            continue;
        }
        $tmp = (string) ($tmpNames[$i] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            continue;
        }
        if (strtolower(pathinfo((string) $name, PATHINFO_EXTENSION)) !== 'docx') {
            throw new RuntimeException('Only .docx files are supported: ' . (string) $name);
        }
        $paths[] = $tmp;
    }

    if (!$paths) {
        throw new RuntimeException('No valid DOCX files were uploaded.');
    }

    return worksheet_import_docx_files($paths, $force);
}

function controller_admin_worksheet_docx_import(): void
{
    require_role(['admin']);
    try {
        $force = filter_var($_POST['force'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $results = worksheet_import_uploaded_docx_files('files', $force);
        json_response(['message' => 'Worksheet import completed', 'results' => $results], 201);
    } catch (Throwable $e) {
        json_response(['message' => $e->getMessage()], 422);
    }
}
