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
            $answers = $DB->get_records('question_answers', ['question' => $q->id], 'id ASC');
            
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

        if (!file_exists($filepath)) {
            $result['errors'][] = 'File not found.';
            return $result;
        }

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

        if (!file_exists($filepath)) {
            return $preview;
        }

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
}
