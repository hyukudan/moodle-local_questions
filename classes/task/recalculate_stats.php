<?php
/**
 * Scheduled task to recalculate question statistics.
 *
 * @package    local_questions
 * @copyright  2026 Sergio C.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_questions\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Scheduled task: Recalculate statistics.
 */
class recalculate_stats extends \core\task\scheduled_task {

    /**
     * Get task name.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('task_recalculate_stats', 'local_questions');
    }

    /**
     * Execute the task.
     */
    public function execute() {
        global $DB;

        // Invalidate cache to force fresh data.
        $cache = \cache::make('local_questions', 'statistics');
        $cache->purge();

        // Get question counts per category.
        $sql = "SELECT qc.id as categoryid, qc.contextid, COUNT(q.id) as questioncount
                FROM {question_categories} qc
                LEFT JOIN {question_bank_entries} qbe ON qbe.questioncategoryid = qc.id
                LEFT JOIN {question_versions} qv ON qv.questionbankentryid = qbe.id
                LEFT JOIN {question} q ON q.id = qv.questionid
                GROUP BY qc.id, qc.contextid";
        
        $stats = $DB->get_records_sql($sql);
        
        foreach ($stats as $stat) {
            $record = new \stdClass();
            $record->categoryid = $stat->categoryid;
            $record->questioncount = $stat->questioncount;
            $record->timemodified = time();
            
            // Check if record exists.
            $existing = $DB->get_record('local_questions_stats', ['categoryid' => $stat->categoryid]);
            if ($existing) {
                $record->id = $existing->id;
                $DB->update_record('local_questions_stats', $record);
            } else {
                $record->timecreated = time();
                $DB->insert_record('local_questions_stats', $record);
            }
        }

        mtrace('local_questions: Statistics recalculated for ' . count($stats) . ' categories.');
    }
}
