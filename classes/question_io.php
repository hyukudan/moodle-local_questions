<?php
/**
 * Question import/export helper class.
 *
 * @package    local_questions
 * @copyright  2026 Sergio C.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_questions;

defined('MOODLE_INTERNAL') || die();

/**
 * Helper class for question import and export operations.
 */
class question_io {

    /** @var array Supported question types for CSV export/import */
    const SUPPORTED_QTYPES = ['multichoice', 'truefalse', 'shortanswer', 'essay', 'match', 'numerical'];

    /**
     * Count the questions that an export call would return.
     *
     * Cheap pre-check used by the export.php controller to decide whether to
     * generate the PDF synchronously or hand it off to an ad-hoc task.
     * Mirrors the WHERE clause of {@see export_to_pdf()} / {@see export_to_csv()}.
     *
     * @param int   $categoryid The category ID to export from.
     * @param bool  $recurse    Whether to include subcategories.
     * @param array $filters    Optional filters (qtype, etc).
     * @return int  Number of questions matching the export criteria.
     */
    public static function count_for_export(int $categoryid, bool $recurse = false, array $filters = []): int {
        global $DB;

        $catids = [$categoryid];
        if ($recurse) {
            $catids = array_merge($catids, self::get_subcategory_ids($categoryid));
        }

        list($insql, $inparams) = $DB->get_in_or_equal($catids);

        $sql = "SELECT COUNT(DISTINCT q.id)
                  FROM {question} q
                  JOIN {question_versions} qv ON qv.questionid = q.id
                  JOIN {question_bank_entries} qbe ON qbe.id = qv.questionbankentryid
                 WHERE qbe.questioncategoryid $insql
                   AND qv.version = (
                       SELECT MAX(qv2.version)
                         FROM {question_versions} qv2
                        WHERE qv2.questionbankentryid = qv.questionbankentryid
                   )";

        if (!empty($filters['qtype'])) {
            $sql .= " AND q.qtype = ?";
            $inparams[] = $filters['qtype'];
        }

        return (int)$DB->count_records_sql($sql, $inparams);
    }

    /**
     * Export questions from a category to CSV format.
     *
     * @param int $categoryid The category ID to export from.
     * @param bool $recurse Whether to include subcategories.
     * @param array $filters Optional filters (qtype, etc).
     * @return array Array with 'data' (CSV string) and 'count' (number of questions).
     */
    public static function export_to_csv(int $categoryid, bool $recurse = false, array $filters = []): array {
        global $DB;

        $catids = [$categoryid];
        if ($recurse) {
            $catids = array_merge($catids, self::get_subcategory_ids($categoryid));
        }

        // Moodle 4.0+ uses question_bank_entries for category relationship.
        // We need to join through question_versions to get questions by category.
        list($insql, $inparams) = $DB->get_in_or_equal($catids);

        $sql = "SELECT q.*
                  FROM {question} q
                  JOIN {question_versions} qv ON qv.questionid = q.id
                  JOIN {question_bank_entries} qbe ON qbe.id = qv.questionbankentryid
                 WHERE qbe.questioncategoryid $insql
                   AND qv.version = (
                       SELECT MAX(qv2.version)
                         FROM {question_versions} qv2
                        WHERE qv2.questionbankentryid = qv.questionbankentryid
                   )";

        // Apply qtype filter if set.
        if (!empty($filters['qtype'])) {
            $sql .= " AND q.qtype = ?";
            $inparams[] = $filters['qtype'];
        }

        $sql .= " ORDER BY q.id ASC";

        $questions = $DB->get_records_sql($sql, $inparams);

        if (empty($questions)) {
            return ['data' => '', 'count' => 0];
        }

        // Batch-load all answers.
        $questionids = array_keys($questions);
        list($qinsql, $qinparams) = $DB->get_in_or_equal($questionids);
        $allanswers = $DB->get_records_select('question_answers', "question $qinsql", $qinparams, 'question ASC, id ASC');
        $answersbyquestion = [];
        foreach ($allanswers as $a) {
            $answersbyquestion[$a->question][] = $a;
        }
        unset($allanswers);

        // Build CSV.
        $output = fopen('php://temp', 'r+');
        
        // Header row.
        fputcsv($output, [
            'id',
            'name',
            'questiontext',
            'qtype',
            'answers',
            'feedback',
            'fractions',
            'generalfeedback',
            'defaultmark'
        ]);

        foreach ($questions as $q) {
            // Get answers for this question.
            $answers = $answersbyquestion[$q->id] ?? [];
            
            $answerTexts = [];
            $feedbackTexts = [];
            $fractions = [];
            
            foreach ($answers as $a) {
                $answerTexts[] = strip_tags($a->answer);
                $feedbackTexts[] = strip_tags($a->feedback);
                $fractions[] = $a->fraction;
            }

            fputcsv($output, [
                $q->id,
                $q->name,
                strip_tags($q->questiontext),
                $q->qtype,
                implode('|', $answerTexts),
                implode('|', $feedbackTexts),
                implode('|', $fractions),
                strip_tags($q->generalfeedback ?? ''),
                $q->defaultmark ?? 1
            ]);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return ['data' => $csv, 'count' => count($questions)];
    }

    /**
     * Export questions from a category to PDF format.
     *
     * @param int $categoryid The category ID to export from.
     * @param bool $recurse Whether to include subcategories.
     * @param array $filters Optional filters (qtype, etc).
     * @return array Array with 'data' (PDF binary string) and 'count' (number of questions).
     */
    public static function export_to_pdf(int $categoryid, bool $recurse = false, array $filters = []): array {
        global $CFG, $DB, $USER;

        require_once($CFG->libdir . '/pdflib.php');

        // Raise limits for large exports - TCPDF is memory intensive.
        raise_memory_limit(MEMORY_EXTRA);
        \core_php_time_limit::raise(300);

        $catids = [$categoryid];
        if ($recurse) {
            $catids = array_merge($catids, self::get_subcategory_ids($categoryid));
        }

        list($insql, $inparams) = $DB->get_in_or_equal($catids);

        $sql = "SELECT q.*
                  FROM {question} q
                  JOIN {question_versions} qv ON qv.questionid = q.id
                  JOIN {question_bank_entries} qbe ON qbe.id = qv.questionbankentryid
                 WHERE qbe.questioncategoryid $insql
                   AND qv.version = (
                       SELECT MAX(qv2.version)
                         FROM {question_versions} qv2
                        WHERE qv2.questionbankentryid = qv.questionbankentryid
                   )";

        if (!empty($filters['qtype'])) {
            $sql .= " AND q.qtype = ?";
            $inparams[] = $filters['qtype'];
        }

        $sql .= " ORDER BY q.id ASC";

        $questions = $DB->get_records_sql($sql, $inparams);

        if (empty($questions)) {
            return ['data' => '', 'count' => 0];
        }

        // Batch-load all answers for all questions in a single query.
        $questionids = array_keys($questions);
        list($qinsql, $qinparams) = $DB->get_in_or_equal($questionids);
        $allanswers = $DB->get_records_select('question_answers', "question $qinsql", $qinparams, 'question ASC, id ASC');

        // Group answers by question ID.
        $answersbyquestion = [];
        foreach ($allanswers as $a) {
            $answersbyquestion[$a->question][] = $a;
        }
        unset($allanswers); // Free memory.

        // Get category name for title.
        $category = $DB->get_record('question_categories', ['id' => $categoryid], 'name');
        $categoryname = $category ? $category->name : '';

        // Initialize PDF.
        $pdf = new \pdf('P', 'mm', 'A4', true, 'UTF-8');
        $pdf->SetCreator('Moodle - Questions Management');
        $pdf->SetAuthor(fullname($USER));
        $pdf->SetTitle(get_string('exportquestions', 'local_questions') . ' - ' . $categoryname);

        $pdf->SetMargins(15, 20, 15);
        $pdf->SetAutoPageBreak(true, 20);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(true);

        $pdf->AddPage();

        // Title.
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->SetTextColor(0, 51, 102);
        $pdf->Cell(0, 10, get_string('exportquestions', 'local_questions'), 0, 1);

        if ($categoryname) {
            $pdf->SetFont('helvetica', 'I', 10);
            $pdf->SetTextColor(128, 128, 128);
            $pdf->Cell(0, 6, get_string('category', 'local_questions') . ': ' . $categoryname . ' | ' . count($questions) . ' ' . get_string('questions', 'local_questions'), 0, 1);
        }

        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetTextColor(128, 128, 128);
        $pdf->Cell(0, 6, userdate(time()), 0, 1);

        $pdf->Ln(3);
        $pdf->SetDrawColor(200, 200, 200);
        $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
        $pdf->Ln(5);

        $letters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
        $qnum = 1;

        foreach ($questions as $q) {
            $answers = $answersbyquestion[$q->id] ?? [];

            // Check if we need a page break (estimate: question needs at least 40mm).
            if ($pdf->GetY() > 240) {
                $pdf->AddPage();
            }

            // Question number and text.
            $pdf->SetFont('helvetica', 'B', 11);
            $pdf->SetTextColor(0, 0, 0);

            $questiontext = strip_tags(format_text($q->questiontext, FORMAT_HTML));
            $pdf->MultiCell(0, 6, $qnum . '. ' . $questiontext, 0, 'L');
            $pdf->Ln(2);

            // Answers.
            $anum = 0;
            foreach ($answers as $a) {
                $letter = $letters[$anum] ?? chr(65 + $anum);
                $answertext = strip_tags($a->answer);
                $iscorrect = $a->fraction > 0;

                if ($iscorrect) {
                    $pdf->SetFont('helvetica', 'B', 10);
                    $pdf->SetTextColor(0, 128, 0);
                    $fractionLabel = ' [' . self::format_fraction($a->fraction) . ']';
                } else {
                    $pdf->SetFont('helvetica', '', 10);
                    $pdf->SetTextColor(64, 64, 64);
                    $fractionLabel = ' [' . self::format_fraction($a->fraction) . ']';
                }

                $pdf->Cell(8, 5, '', 0, 0); // Indent.
                $pdf->MultiCell(0, 5, $letter . ') ' . $answertext . $fractionLabel, 0, 'L');

                $anum++;
            }

            $pdf->Ln(2);

            // Feedback section.
            $hasFeedback = false;
            foreach ($answers as $a) {
                $feedback = strip_tags($a->feedback ?? '');
                if (!empty(trim($feedback))) {
                    $hasFeedback = true;
                    break;
                }
            }

            if ($hasFeedback || !empty(trim(strip_tags($q->generalfeedback ?? '')))) {
                $pdf->SetDrawColor(220, 220, 220);
                $pdf->SetFillColor(248, 249, 250);

                // Save position for background box.
                $startY = $pdf->GetY();
                $startPage = $pdf->getPage();

                $pdf->SetFont('helvetica', 'B', 9);
                $pdf->SetTextColor(100, 100, 100);
                $pdf->Cell(8, 5, '', 0, 0);
                $pdf->Cell(0, 5, 'Feedback:', 0, 1);

                // Per-answer feedback.
                $anum = 0;
                foreach ($answers as $a) {
                    $feedback = strip_tags($a->feedback ?? '');
                    if (!empty(trim($feedback))) {
                        $letter = $letters[$anum] ?? chr(65 + $anum);
                        $pdf->SetFont('helvetica', 'I', 9);
                        $pdf->SetTextColor(120, 120, 120);
                        $pdf->Cell(12, 4, '', 0, 0);
                        $pdf->MultiCell(0, 4, $letter . ') ' . $feedback, 0, 'L');
                    }
                    $anum++;
                }

                // General feedback.
                $generalfeedback = strip_tags($q->generalfeedback ?? '');
                if (!empty(trim($generalfeedback))) {
                    $pdf->SetFont('helvetica', 'I', 9);
                    $pdf->SetTextColor(100, 100, 100);
                    $pdf->Cell(8, 4, '', 0, 0);
                    $pdf->MultiCell(0, 4, get_string('generalfeedback', 'local_questions') . ': ' . $generalfeedback, 0, 'L');
                }
            }

            // Separator line.
            $pdf->Ln(3);
            $pdf->SetDrawColor(230, 230, 230);
            $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
            $pdf->Ln(5);

            $qnum++;
        }

        $content = $pdf->Output('', 'S');
        return ['data' => $content, 'count' => count($questions)];
    }

    /**
     * Format a fraction value as a percentage string.
     *
     * @param float $fraction The fraction value.
     * @return string Formatted percentage (e.g. "100%", "-33,33%").
     */
    private static function format_fraction(float $fraction): string {
        $pct = round($fraction * 100, 2);
        if ($pct == intval($pct)) {
            return intval($pct) . '%';
        }
        return str_replace('.', ',', number_format($pct, 2)) . '%';
    }

    /**
     * Import questions from CSV file.
     *
     * @param string $filepath Path to the uploaded CSV file.
     * @param int $categoryid Target category ID.
     * @return array Array with 'imported', 'skipped', 'errors'.
     */
    public static function import_from_csv(string $filepath, int $categoryid): array {
        global $DB, $USER;

        $result = [
            'imported' => 0,
            'skipped' => 0,
            'errors' => []
        ];

        // Defense in depth: even though all current callers pass a path that
        // PHP/Moodle generated (never user-controlled), this is a public static
        // method — a future caller could pass user input. Resolve and ensure
        // the file is inside an allowed temp directory.
        $safepath = self::resolve_safe_temp_path($filepath);
        if ($safepath === null) {
            $result['errors'][] = 'File not found.';
            return $result;
        }
        $filepath = $safepath;

        $handle = fopen($filepath, 'r');
        if (!$handle) {
            $result['errors'][] = 'Could not open file.';
            return $result;
        }

        // Read header row.
        $header = fgetcsv($handle);
        if (!$header || !self::validate_csv_header($header)) {
            $result['errors'][] = get_string('invalidcsvformat', 'local_questions');
            fclose($handle);
            return $result;
        }

        $headerMap = array_flip($header);
        $rownum = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rownum++;
            
            try {
                $validation = self::validate_csv_row($row, $headerMap);
                if ($validation !== true) {
                    $result['errors'][] = "Row $rownum: $validation";
                    $result['skipped']++;
                    continue;
                }

                // Create question (Moodle 4.0+ structure).
                $question = new \stdClass();
                $question->name = $row[$headerMap['name']];
                $question->questiontext = $row[$headerMap['questiontext']];
                $question->questiontextformat = FORMAT_HTML;
                $question->qtype = $row[$headerMap['qtype']] ?? 'multichoice';
                $question->generalfeedback = $row[$headerMap['generalfeedback']] ?? '';
                $question->generalfeedbackformat = FORMAT_HTML;
                $question->defaultmark = $row[$headerMap['defaultmark']] ?? 1;
                $question->penalty = 0.3333333;
                $question->timecreated = time();
                $question->timemodified = time();
                $question->createdby = $USER->id;
                $question->modifiedby = $USER->id;

                $questionid = $DB->insert_record('question', $question);

                // Create question_bank_entry linking to category.
                $entry = new \stdClass();
                $entry->questioncategoryid = $categoryid;
                $entry->idnumber = null;
                $entry->ownerid = $USER->id;
                $entryid = $DB->insert_record('question_bank_entries', $entry);

                // Create question_version linking question to entry.
                $version = new \stdClass();
                $version->questionbankentryid = $entryid;
                $version->questionid = $questionid;
                $version->version = 1;
                $version->status = 'ready';
                $DB->insert_record('question_versions', $version);

                // Create answers if present.
                if (!empty($row[$headerMap['answers']])) {
                    $answers = explode('|', $row[$headerMap['answers']]);
                    $feedbacks = isset($headerMap['feedback']) ? explode('|', $row[$headerMap['feedback']] ?? '') : [];
                    $fractions = isset($headerMap['fractions']) ? explode('|', $row[$headerMap['fractions']] ?? '') : [];

                    foreach ($answers as $i => $answerText) {
                        if (trim($answerText) === '') {
                            continue;
                        }
                        
                        $answer = new \stdClass();
                        $answer->question = $questionid;
                        $answer->answer = trim($answerText);
                        $answer->answerformat = FORMAT_HTML;
                        $answer->feedback = trim($feedbacks[$i] ?? '');
                        $answer->feedbackformat = FORMAT_HTML;
                        $answer->fraction = floatval($fractions[$i] ?? 0);

                        $DB->insert_record('question_answers', $answer);
                    }
                }

                $result['imported']++;

            } catch (\Exception $e) {
                $result['errors'][] = "Row $rownum: " . $e->getMessage();
                $result['skipped']++;
            }
        }

        fclose($handle);
        return $result;
    }

    /**
     * Parse CSV for preview (without importing).
     *
     * @param string $filepath Path to the uploaded CSV file.
     * @return array Array of parsed rows for preview.
     */
    public static function preview_csv(string $filepath): array {
        $preview = [];

        // Defense in depth — see import_from_csv() for rationale.
        $safepath = self::resolve_safe_temp_path($filepath);
        if ($safepath === null) {
            return $preview;
        }
        $filepath = $safepath;

        $handle = fopen($filepath, 'r');
        if (!$handle) {
            return $preview;
        }

        $header = fgetcsv($handle);
        if (!$header || !self::validate_csv_header($header)) {
            fclose($handle);
            return $preview;
        }

        $headerMap = array_flip($header);
        $count = 0;

        while (($row = fgetcsv($handle)) !== false && $count < 20) {
            $preview[] = [
                'name' => $row[$headerMap['name']] ?? '',
                'questiontext' => mb_substr($row[$headerMap['questiontext']] ?? '', 0, 100) . '...',
                'qtype' => $row[$headerMap['qtype']] ?? 'unknown',
                'valid' => self::validate_csv_row($row, $headerMap) === true
            ];
            $count++;
        }

        fclose($handle);
        return $preview;
    }

    /**
     * Validate CSV header.
     *
     * @param array $header The header row.
     * @return bool True if valid.
     */
    public static function validate_csv_header(array $header): bool {
        $required = ['name', 'questiontext'];
        foreach ($required as $col) {
            if (!in_array($col, $header)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Validate a CSV row.
     *
     * @param array $row The row data.
     * @param array $headerMap Map of column names to indices.
     * @return mixed True if valid, error message string otherwise.
     */
    public static function validate_csv_row(array $row, array $headerMap) {
        // Name required.
        if (empty($row[$headerMap['name']] ?? '')) {
            return 'Missing question name.';
        }

        // Question text required.
        if (empty($row[$headerMap['questiontext']] ?? '')) {
            return 'Missing question text.';
        }

        // Validate qtype if present.
        if (isset($headerMap['qtype']) && !empty($row[$headerMap['qtype']])) {
            $qtype = $row[$headerMap['qtype']];
            if (!in_array($qtype, self::SUPPORTED_QTYPES)) {
                return "Unsupported question type: $qtype";
            }
        }

        return true;
    }

    /**
     * Get all subcategory IDs recursively.
     *
     * @param int $parentid Parent category ID.
     * @return array Array of subcategory IDs.
     */
    private static function get_subcategory_ids(int $parentid): array {
        global $DB;

        $ids = [];
        $children = $DB->get_records('question_categories', ['parent' => $parentid], '', 'id');

        foreach ($children as $child) {
            $ids[] = $child->id;
            $ids = array_merge($ids, self::get_subcategory_ids($child->id));
        }

        return $ids;
    }

    /**
     * Resolve a path and verify it lives inside an allowed temp directory.
     *
     * Rejects path traversal attempts (../) by relying on realpath() canonicalisation
     * and a whitelist of known temp roots: Moodle's tempdir, dataroot/temp, the system
     * temp dir, and PHP's upload_tmp_dir. Returns the canonical path on success or
     * null if the file is missing or outside the whitelist.
     *
     * @param string $filepath Raw filepath supplied by a caller.
     * @return string|null Canonical safe path, or null if invalid/outside whitelist.
     */
    private static function resolve_safe_temp_path(string $filepath): ?string {
        global $CFG;

        $real = realpath($filepath);
        if ($real === false || !is_file($real)) {
            return null;
        }

        $candidates = [
            $CFG->tempdir ?? '',
            ($CFG->dataroot ?? '') . '/temp',
            sys_get_temp_dir(),
            ini_get('upload_tmp_dir') ?: '',
        ];

        foreach ($candidates as $root) {
            if ($root === '') {
                continue;
            }
            $rootreal = realpath($root);
            if ($rootreal === false) {
                continue;
            }
            // Match the directory itself OR anything strictly under it.
            if ($real === $rootreal || str_starts_with($real, $rootreal . DIRECTORY_SEPARATOR)) {
                return $real;
            }
        }

        return null;
    }
}
