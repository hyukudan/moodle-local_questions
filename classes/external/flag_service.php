<?php
/**
 * External services for question flags.
 *
 * @package    local_questions
 * @copyright  2026 Sergio C.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_questions\external;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/externallib.php");

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use external_multiple_structure;
use context_system;
use local_questions\flag_manager;

/**
 * External API for question flag operations.
 */
class flag_service extends external_api {

    // ==================== SUBMIT FLAG ====================

    /**
     * Parameters for submit_flag.
     */
    public static function submit_flag_parameters() {
        return new external_function_parameters([
            'questionid' => new external_value(PARAM_INT, 'The question ID to flag'),
            'reason' => new external_value(PARAM_ALPHANUMEXT, 'The reason code'),
            // Security: Use PARAM_TEXT to sanitize user input and prevent XSS.
            'comment' => new external_value(PARAM_TEXT, 'Optional comment', VALUE_DEFAULT, ''),
            'attemptid' => new external_value(PARAM_INT, 'Optional quiz attempt ID', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Submit a flag for a question.
     *
     * @param int $questionid The question ID
     * @param string $reason The reason code
     * @param string $comment Optional comment
     * @param int $attemptid Optional quiz attempt ID
     * @return array Result with success status and flag ID
     */
    public static function submit_flag($questionid, $reason, $comment = '', $attemptid = 0) {
        global $USER;

        $params = self::validate_parameters(self::submit_flag_parameters(), [
            'questionid' => $questionid,
            'reason' => $reason,
            'comment' => $comment,
            'attemptid' => $attemptid,
        ]);

        $context = context_system::instance();
        self::validate_context($context);
        require_capability('local/questions:flag', $context);

        try {
            $flagid = flag_manager::submit_flag(
                $params['questionid'],
                $USER->id,
                $params['reason'],
                $params['comment'],
                $params['attemptid'] > 0 ? $params['attemptid'] : null
            );

            return [
                'success' => true,
                'flagid' => $flagid,
                'message' => get_string('flagsubmitted', 'local_questions'),
            ];
        } catch (\moodle_exception $e) {
            return [
                'success' => false,
                'flagid' => 0,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Return structure for submit_flag.
     */
    public static function submit_flag_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether the flag was submitted'),
            'flagid' => new external_value(PARAM_INT, 'The flag ID if successful'),
            'message' => new external_value(PARAM_TEXT, 'Status message'),
        ]);
    }

    // ==================== CHECK FLAG STATUS ====================

    /**
     * Parameters for check_flag_status.
     */
    public static function check_flag_status_parameters() {
        return new external_function_parameters([
            'questionid' => new external_value(PARAM_INT, 'The question ID'),
        ]);
    }

    /**
     * Check if current user has flagged a question.
     *
     * @param int $questionid The question ID
     * @return array Result with flag status
     */
    public static function check_flag_status($questionid) {
        global $USER;

        $params = self::validate_parameters(self::check_flag_status_parameters(), [
            'questionid' => $questionid,
        ]);

        $context = context_system::instance();
        self::validate_context($context);

        $hasflagged = flag_manager::has_user_flagged($params['questionid'], $USER->id);
        $status = flag_manager::get_flag_status($params['questionid']);

        return [
            'hasflagged' => $hasflagged,
            'questionstatus' => $status ? $status->status : '',
            'flagcount' => $status ? (int)$status->flagcount : 0,
        ];
    }

    /**
     * Return structure for check_flag_status.
     */
    public static function check_flag_status_returns() {
        return new external_single_structure([
            'hasflagged' => new external_value(PARAM_BOOL, 'Whether user has flagged this question'),
            'questionstatus' => new external_value(PARAM_ALPHA, 'Current status of the question flags'),
            'flagcount' => new external_value(PARAM_INT, 'Total number of flags'),
        ]);
    }

    // ==================== GET FLAGGED QUESTIONS ====================

    /**
     * Parameters for get_flagged_questions.
     */
    public static function get_flagged_questions_parameters() {
        return new external_function_parameters([
            'status' => new external_value(PARAM_ALPHA, 'Filter by status', VALUE_DEFAULT, ''),
            'page' => new external_value(PARAM_INT, 'Page number', VALUE_DEFAULT, 0),
            'perpage' => new external_value(PARAM_INT, 'Items per page', VALUE_DEFAULT, 50),
        ]);
    }

    /**
     * Get list of flagged questions for review.
     *
     * @param string $status Filter by status
     * @param int $page Page number
     * @param int $perpage Items per page
     * @return array List of flagged questions
     */
    public static function get_flagged_questions($status = '', $page = 0, $perpage = 50) {
        $params = self::validate_parameters(self::get_flagged_questions_parameters(), [
            'status' => $status,
            'page' => $page,
            'perpage' => $perpage,
        ]);

        $context = context_system::instance();
        self::validate_context($context);
        require_capability('local/questions:reviewflags', $context);

        $statusfilter = !empty($params['status']) ? $params['status'] : null;
        $offset = $params['page'] * $params['perpage'];

        $questions = flag_manager::get_flagged_questions($statusfilter, $offset, $params['perpage']);
        $counts = flag_manager::count_by_status();
        $reasons = flag_manager::get_reasons();

        $result = [];
        foreach ($questions as $q) {
            $topreason = flag_manager::get_top_reason($q->questionid);
            $result[] = [
                'id' => (int)$q->id,
                'questionid' => (int)$q->questionid,
                'questionname' => $q->questionname ?? '',
                'questiontext_preview' => strip_tags($q->questiontext_preview ?? ''),
                'categoryname' => $q->categoryname ?? '',
                'status' => $q->status,
                'flagcount' => (int)$q->flagcount,
                'topreason' => $topreason ?? '',
                'topreason_label' => $topreason ? ($reasons[$topreason] ?? $topreason) : '',
                'timecreated' => (int)$q->timecreated,
                'timemodified' => (int)$q->timemodified,
            ];
        }

        return [
            'questions' => $result,
            'counts' => [
                'all' => $counts['all'],
                'pending' => $counts[flag_manager::STATUS_PENDING],
                'reviewing' => $counts[flag_manager::STATUS_REVIEWING],
                'resolved' => $counts[flag_manager::STATUS_RESOLVED],
                'dismissed' => $counts[flag_manager::STATUS_DISMISSED],
            ],
        ];
    }

    /**
     * Return structure for get_flagged_questions.
     */
    public static function get_flagged_questions_returns() {
        return new external_single_structure([
            'questions' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'Status record ID'),
                    'questionid' => new external_value(PARAM_INT, 'Question ID'),
                    'questionname' => new external_value(PARAM_TEXT, 'Question name'),
                    'questiontext_preview' => new external_value(PARAM_TEXT, 'Question text preview'),
                    'categoryname' => new external_value(PARAM_TEXT, 'Category name'),
                    'status' => new external_value(PARAM_ALPHA, 'Current status'),
                    'flagcount' => new external_value(PARAM_INT, 'Number of flags'),
                    'topreason' => new external_value(PARAM_ALPHANUMEXT, 'Most common reason code'),
                    'topreason_label' => new external_value(PARAM_TEXT, 'Most common reason label'),
                    'timecreated' => new external_value(PARAM_INT, 'Time created'),
                    'timemodified' => new external_value(PARAM_INT, 'Time modified'),
                ])
            ),
            'counts' => new external_single_structure([
                'all' => new external_value(PARAM_INT, 'Total count'),
                'pending' => new external_value(PARAM_INT, 'Pending count'),
                'reviewing' => new external_value(PARAM_INT, 'Reviewing count'),
                'resolved' => new external_value(PARAM_INT, 'Resolved count'),
                'dismissed' => new external_value(PARAM_INT, 'Dismissed count'),
            ]),
        ]);
    }

    // ==================== GET FLAG DETAILS ====================

    /**
     * Parameters for get_flag_details.
     */
    public static function get_flag_details_parameters() {
        return new external_function_parameters([
            'questionid' => new external_value(PARAM_INT, 'The question ID'),
        ]);
    }

    /**
     * Get detailed flag information for a question.
     *
     * @param int $questionid The question ID
     * @return array Detailed flag information
     */
    public static function get_flag_details($questionid) {
        global $DB;

        $params = self::validate_parameters(self::get_flag_details_parameters(), [
            'questionid' => $questionid,
        ]);

        $context = context_system::instance();
        self::validate_context($context);
        require_capability('local/questions:reviewflags', $context);

        $flags = flag_manager::get_flags_for_question($params['questionid']);
        $status = flag_manager::get_flag_status($params['questionid']);
        $question = $DB->get_record('question', ['id' => $params['questionid']], 'id, name, questiontext');
        $reasons = flag_manager::get_reasons();

        $flaglist = [];
        foreach ($flags as $flag) {
            $flaglist[] = [
                'id' => (int)$flag->id,
                'userid' => (int)$flag->userid,
                'username' => fullname($flag),
                'reason' => $flag->reason,
                'reason_label' => $reasons[$flag->reason] ?? $flag->reason,
                'comment' => $flag->comment ?? '',
                'timecreated' => (int)$flag->timecreated,
            ];
        }

        return [
            'questionid' => (int)$params['questionid'],
            'questionname' => $question ? $question->name : '',
            'questiontext' => $question ? $question->questiontext : '',
            'status' => $status ? $status->status : '',
            'flagcount' => $status ? (int)$status->flagcount : 0,
            'resolution' => $status ? ($status->resolution ?? '') : '',
            'resolutionfeedback' => $status ? ($status->resolutionfeedback ?? '') : '',
            'flags' => $flaglist,
        ];
    }

    /**
     * Return structure for get_flag_details.
     */
    public static function get_flag_details_returns() {
        return new external_single_structure([
            'questionid' => new external_value(PARAM_INT, 'Question ID'),
            'questionname' => new external_value(PARAM_TEXT, 'Question name'),
            // Note: questiontext may contain HTML (Moodle questions use formatted text).
            'questiontext' => new external_value(PARAM_RAW, 'Full question text'),
            'status' => new external_value(PARAM_ALPHA, 'Current status'),
            'flagcount' => new external_value(PARAM_INT, 'Total flag count'),
            'resolution' => new external_value(PARAM_ALPHANUMEXT, 'Resolution type'),
            'resolutionfeedback' => new external_value(PARAM_TEXT, 'Resolution feedback'),
            'flags' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'Flag ID'),
                    'userid' => new external_value(PARAM_INT, 'User ID'),
                    'username' => new external_value(PARAM_TEXT, 'User full name'),
                    'reason' => new external_value(PARAM_ALPHANUMEXT, 'Reason code'),
                    'reason_label' => new external_value(PARAM_TEXT, 'Reason label'),
                    'comment' => new external_value(PARAM_TEXT, 'User comment'),
                    'timecreated' => new external_value(PARAM_INT, 'Time created'),
                ])
            ),
        ]);
    }

    // ==================== UPDATE FLAG STATUS ====================

    /**
     * Parameters for update_flag_status.
     */
    public static function update_flag_status_parameters() {
        return new external_function_parameters([
            'questionid' => new external_value(PARAM_INT, 'The question ID'),
            'action' => new external_value(PARAM_ALPHA, 'Action: resolve or dismiss'),
            'resolution' => new external_value(PARAM_ALPHANUMEXT, 'Resolution type', VALUE_DEFAULT, ''),
            // Security: Use PARAM_TEXT to sanitize teacher feedback and prevent XSS.
            'feedback' => new external_value(PARAM_TEXT, 'Feedback message'),
        ]);
    }

    /**
     * Update flag status (resolve or dismiss).
     *
     * @param int $questionid The question ID
     * @param string $action The action (resolve/dismiss)
     * @param string $resolution Resolution type
     * @param string $feedback Feedback message
     * @return array Result status
     */
    public static function update_flag_status($questionid, $action, $resolution = '', $feedback = '') {
        global $USER;

        $params = self::validate_parameters(self::update_flag_status_parameters(), [
            'questionid' => $questionid,
            'action' => $action,
            'resolution' => $resolution,
            'feedback' => $feedback,
        ]);

        $context = context_system::instance();
        self::validate_context($context);
        require_capability('local/questions:resolveflags', $context);

        try {
            if ($params['action'] === 'resolve') {
                flag_manager::resolve(
                    $params['questionid'],
                    $USER->id,
                    $params['resolution'],
                    $params['feedback']
                );
                $message = get_string('flagresolved', 'local_questions');
            } else if ($params['action'] === 'dismiss') {
                flag_manager::dismiss(
                    $params['questionid'],
                    $USER->id,
                    $params['feedback']
                );
                $message = get_string('flagdismissed', 'local_questions');
            } else {
                throw new \moodle_exception('invalidaction', 'local_questions');
            }

            return [
                'success' => true,
                'message' => $message,
            ];
        } catch (\moodle_exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Return structure for update_flag_status.
     */
    public static function update_flag_status_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether the action was successful'),
            'message' => new external_value(PARAM_TEXT, 'Status message'),
        ]);
    }
}
