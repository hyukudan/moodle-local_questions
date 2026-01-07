<?php
/**
 * Flag manager - business logic for question flags.
 *
 * @package    local_questions
 * @copyright  2026 Sergio C.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_questions;

defined('MOODLE_INTERNAL') || die();

/**
 * Manager class for question flags/reports.
 */
class flag_manager {

    /** @var string Reason: Error in question statement */
    const REASON_ERROR_STATEMENT = 'error_statement';
    /** @var string Reason: Wrong answer marked as correct */
    const REASON_WRONG_ANSWER = 'wrong_answer';
    /** @var string Reason: Outdated law/regulation */
    const REASON_OUTDATED_LAW = 'outdated_law';
    /** @var string Reason: Ambiguous question */
    const REASON_AMBIGUOUS = 'ambiguous';
    /** @var string Reason: Other */
    const REASON_OTHER = 'other';

    /** @var string Status: Pending review */
    const STATUS_PENDING = 'pending';
    /** @var string Status: Under review */
    const STATUS_REVIEWING = 'reviewing';
    /** @var string Status: Resolved */
    const STATUS_RESOLVED = 'resolved';
    /** @var string Status: Dismissed */
    const STATUS_DISMISSED = 'dismissed';

    /** @var string Resolution: Question was fixed */
    const RESOLUTION_FIXED = 'fixed';
    /** @var string Resolution: No action needed */
    const RESOLUTION_NO_ACTION = 'no_action';
    /** @var string Resolution: Duplicate report */
    const RESOLUTION_DUPLICATE = 'duplicate';

    /**
     * Get all available flag reasons.
     *
     * @return array Associative array of reason_code => localized_label
     */
    public static function get_reasons(): array {
        return [
            self::REASON_ERROR_STATEMENT => get_string('reason_error_statement', 'local_questions'),
            self::REASON_WRONG_ANSWER => get_string('reason_wrong_answer', 'local_questions'),
            self::REASON_OUTDATED_LAW => get_string('reason_outdated_law', 'local_questions'),
            self::REASON_AMBIGUOUS => get_string('reason_ambiguous', 'local_questions'),
            self::REASON_OTHER => get_string('reason_other', 'local_questions'),
        ];
    }

    /**
     * Get all available statuses.
     *
     * @return array Associative array of status_code => localized_label
     */
    public static function get_statuses(): array {
        return [
            self::STATUS_PENDING => get_string('status_pending', 'local_questions'),
            self::STATUS_REVIEWING => get_string('status_reviewing', 'local_questions'),
            self::STATUS_RESOLVED => get_string('status_resolved', 'local_questions'),
            self::STATUS_DISMISSED => get_string('status_dismissed', 'local_questions'),
        ];
    }

    /**
     * Get all available resolutions.
     *
     * @return array Associative array of resolution_code => localized_label
     */
    public static function get_resolutions(): array {
        return [
            self::RESOLUTION_FIXED => get_string('resolution_fixed', 'local_questions'),
            self::RESOLUTION_NO_ACTION => get_string('resolution_no_action', 'local_questions'),
            self::RESOLUTION_DUPLICATE => get_string('resolution_duplicate', 'local_questions'),
        ];
    }

    /**
     * Submit a flag for a question.
     *
     * @param int $questionid The question ID
     * @param int $userid The user submitting the flag
     * @param string $reason The reason code
     * @param string $comment Optional comment
     * @param int|null $attemptid Optional quiz attempt ID for context
     * @return int The flag ID
     * @throws \moodle_exception If user already flagged this question
     */
    public static function submit_flag(int $questionid, int $userid, string $reason,
            string $comment = '', ?int $attemptid = null): int {
        global $DB;

        // Validate reason.
        $validreasons = array_keys(self::get_reasons());
        if (!in_array($reason, $validreasons)) {
            throw new \moodle_exception('invalidreason', 'local_questions');
        }

        // Check if already flagged by this user.
        if ($DB->record_exists('local_questions_flags', ['questionid' => $questionid, 'userid' => $userid])) {
            throw new \moodle_exception('alreadyflagged', 'local_questions');
        }

        // Verify question exists.
        if (!$DB->record_exists('question', ['id' => $questionid])) {
            throw new \moodle_exception('questionnotfound', 'local_questions');
        }

        // Create flag record.
        $flag = new \stdClass();
        $flag->questionid = $questionid;
        $flag->userid = $userid;
        $flag->attemptid = $attemptid;
        $flag->reason = $reason;
        $flag->comment = $comment;
        $flag->timecreated = time();

        $flagid = $DB->insert_record('local_questions_flags', $flag);

        // Update or create aggregated status.
        self::update_flag_status_count($questionid);

        // Trigger event.
        $event = \local_questions\event\flag_created::create([
            'objectid' => $flagid,
            'context' => \context_system::instance(),
            'relateduserid' => $userid,
            'other' => [
                'questionid' => $questionid,
                'reason' => $reason,
            ],
        ]);
        $event->trigger();

        return $flagid;
    }

    /**
     * Check if a user has already flagged a question.
     *
     * @param int $questionid The question ID
     * @param int $userid The user ID
     * @return bool True if flagged, false otherwise
     */
    public static function has_user_flagged(int $questionid, int $userid): bool {
        global $DB;
        return $DB->record_exists('local_questions_flags', [
            'questionid' => $questionid,
            'userid' => $userid,
        ]);
    }

    /**
     * Get the aggregated flag status for a question.
     *
     * @param int $questionid The question ID
     * @return \stdClass|false The status record or false if not found
     */
    public static function get_flag_status(int $questionid) {
        global $DB;
        return $DB->get_record('local_questions_flag_status', ['questionid' => $questionid]);
    }

    /**
     * Get all flags for a question.
     *
     * @param int $questionid The question ID
     * @return array Array of flag records with user info
     */
    public static function get_flags_for_question(int $questionid): array {
        global $DB;

        $sql = "SELECT f.*, u.firstname, u.lastname, u.email
                  FROM {local_questions_flags} f
                  JOIN {user} u ON u.id = f.userid
                 WHERE f.questionid = :questionid
              ORDER BY f.timecreated DESC";

        return $DB->get_records_sql($sql, ['questionid' => $questionid]);
    }

    /**
     * Get all flagged questions with their status.
     *
     * @param string|null $status Filter by status (null for all)
     * @param int $limitfrom Offset for pagination
     * @param int $limitnum Number of records
     * @return array Array of flagged questions with status info
     */
    public static function get_flagged_questions(?string $status = null, int $limitfrom = 0, int $limitnum = 50): array {
        global $DB;

        $params = [];
        $statuswhere = '';

        if ($status !== null) {
            $statuswhere = 'AND fs.status = :status';
            $params['status'] = $status;
        }

        $sql = "SELECT fs.*, q.name as questionname,
                       SUBSTRING(q.questiontext, 1, 200) as questiontext_preview,
                       qc.name as categoryname
                  FROM {local_questions_flag_status} fs
                  JOIN {question} q ON q.id = fs.questionid
             LEFT JOIN {question_bank_entries} qbe ON qbe.id = (
                       SELECT qv.questionbankentryid
                         FROM {question_versions} qv
                        WHERE qv.questionid = q.id
                        LIMIT 1
                       )
             LEFT JOIN {question_categories} qc ON qc.id = qbe.questioncategoryid
                 WHERE 1=1 {$statuswhere}
              ORDER BY fs.flagcount DESC, fs.timemodified DESC";

        return $DB->get_records_sql($sql, $params, $limitfrom, $limitnum);
    }

    /**
     * Count flagged questions by status.
     *
     * @return array Associative array of status => count
     */
    public static function count_by_status(): array {
        global $DB;

        $counts = [
            'all' => 0,
            self::STATUS_PENDING => 0,
            self::STATUS_REVIEWING => 0,
            self::STATUS_RESOLVED => 0,
            self::STATUS_DISMISSED => 0,
        ];

        $sql = "SELECT status, COUNT(*) as cnt FROM {local_questions_flag_status} GROUP BY status";
        $records = $DB->get_records_sql($sql);

        foreach ($records as $record) {
            $counts[$record->status] = (int)$record->cnt;
            $counts['all'] += (int)$record->cnt;
        }

        return $counts;
    }

    /**
     * Get the most common reason for a flagged question.
     *
     * @param int $questionid The question ID
     * @return string|null The most common reason code or null
     */
    public static function get_top_reason(int $questionid): ?string {
        global $DB;

        $sql = "SELECT reason, COUNT(*) as cnt
                  FROM {local_questions_flags}
                 WHERE questionid = :questionid
              GROUP BY reason
              ORDER BY cnt DESC
                 LIMIT 1";

        $record = $DB->get_record_sql($sql, ['questionid' => $questionid]);
        return $record ? $record->reason : null;
    }

    /**
     * Update flag status to reviewing.
     *
     * @param int $questionid The question ID
     */
    public static function set_reviewing(int $questionid): void {
        global $DB;

        $status = $DB->get_record('local_questions_flag_status', ['questionid' => $questionid], '*', MUST_EXIST);

        if ($status->status === self::STATUS_PENDING) {
            $status->status = self::STATUS_REVIEWING;
            $status->timemodified = time();
            $DB->update_record('local_questions_flag_status', $status);
        }
    }

    /**
     * Resolve a flagged question.
     *
     * @param int $questionid The question ID
     * @param int $resolvedby User ID who resolved it
     * @param string $resolution Resolution type
     * @param string $feedback Feedback to send to flaggers
     */
    public static function resolve(int $questionid, int $resolvedby, string $resolution, string $feedback): void {
        global $DB;

        // Validate resolution.
        $validresolutions = array_keys(self::get_resolutions());
        if (!in_array($resolution, $validresolutions)) {
            throw new \moodle_exception('invalidresolution', 'local_questions');
        }

        $status = $DB->get_record('local_questions_flag_status', ['questionid' => $questionid], '*', MUST_EXIST);

        $status->status = self::STATUS_RESOLVED;
        $status->resolvedby = $resolvedby;
        $status->resolution = $resolution;
        $status->resolutionfeedback = $feedback;
        $status->timemodified = time();
        $status->timeresolved = time();

        $DB->update_record('local_questions_flag_status', $status);

        // Notify all users who flagged this question.
        self::notify_flaggers($questionid, 'flagresolved', $feedback, $resolution);

        // Trigger event.
        $event = \local_questions\event\flag_resolved::create([
            'objectid' => $status->id,
            'context' => \context_system::instance(),
            'relateduserid' => $resolvedby,
            'other' => [
                'questionid' => $questionid,
                'resolution' => $resolution,
            ],
        ]);
        $event->trigger();
    }

    /**
     * Dismiss a flagged question.
     *
     * @param int $questionid The question ID
     * @param int $dismissedby User ID who dismissed it
     * @param string $reason Reason for dismissal
     */
    public static function dismiss(int $questionid, int $dismissedby, string $reason): void {
        global $DB;

        $status = $DB->get_record('local_questions_flag_status', ['questionid' => $questionid], '*', MUST_EXIST);

        $status->status = self::STATUS_DISMISSED;
        $status->resolvedby = $dismissedby;
        $status->resolution = 'dismissed';
        $status->resolutionfeedback = $reason;
        $status->timemodified = time();
        $status->timeresolved = time();

        $DB->update_record('local_questions_flag_status', $status);

        // Notify all users who flagged this question.
        self::notify_flaggers($questionid, 'flagdismissed', $reason, 'dismissed');
    }

    /**
     * Update or create the aggregated flag status count.
     *
     * @param int $questionid The question ID
     */
    private static function update_flag_status_count(int $questionid): void {
        global $DB;

        $count = $DB->count_records('local_questions_flags', ['questionid' => $questionid]);

        $existing = $DB->get_record('local_questions_flag_status', ['questionid' => $questionid]);

        if ($existing) {
            // Only update count if not resolved/dismissed.
            if (in_array($existing->status, [self::STATUS_PENDING, self::STATUS_REVIEWING])) {
                $existing->flagcount = $count;
                $existing->timemodified = time();
                $DB->update_record('local_questions_flag_status', $existing);
            }
        } else {
            $status = new \stdClass();
            $status->questionid = $questionid;
            $status->status = self::STATUS_PENDING;
            $status->flagcount = $count;
            $status->timecreated = time();
            $status->timemodified = time();
            $DB->insert_record('local_questions_flag_status', $status);
        }
    }

    /**
     * Notify all users who flagged a question.
     *
     * @param int $questionid The question ID
     * @param string $messagename The message provider name
     * @param string $feedback The feedback message
     * @param string $resolution The resolution type
     */
    private static function notify_flaggers(int $questionid, string $messagename, string $feedback, string $resolution): void {
        global $DB;

        $flags = $DB->get_records('local_questions_flags', ['questionid' => $questionid], '', 'userid');
        $question = $DB->get_record('question', ['id' => $questionid], 'id, name, questiontext');

        if (!$question) {
            return;
        }

        foreach ($flags as $flag) {
            notification\flag_notification::send(
                $flag->userid,
                $messagename,
                $question,
                $feedback,
                $resolution
            );
        }
    }
}
