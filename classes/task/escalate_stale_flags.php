<?php
/**
 * Scheduled task to escalate stale pending question flags.
 *
 * @package    local_questions
 * @copyright  2026 Sergio C.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_questions\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Scheduled task: email reviewers a daily summary of stale pending flags.
 */
class escalate_stale_flags extends \core\task\scheduled_task {

    /** @var int Default age threshold in days. */
    private const DEFAULT_ESCALATE_DAYS = 7;

    /** @var string Internal config key containing question ID => last notification time. */
    private const NOTIFICATION_STATE_CONFIG = 'stale_flag_last_notified';

    /** @var string Audit action used when the task only performs the check. */
    private const ACTION_CHECK = 'stale_flags_check';

    /** @var string Audit action used when at least one summary email is sent. */
    private const ACTION_ALERT = 'stale_flags_alert';

    /**
     * Get task name.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('task_escalate_stale_flags', 'local_questions');
    }

    /**
     * Find stale flags, notify reviewers, persist throttling state and audit the execution.
     */
    public function execute() {
        $now = time();
        $configuredays = get_config('local_questions', 'escalate_days');
        $escalatedays = $configuredays === false ? self::DEFAULT_ESCALATE_DAYS : (int)$configuredays;
        $escalatedays = max(1, $escalatedays);
        $cutoff = $now - ($escalatedays * DAYSECS);

        $staleflags = $this->get_stale_flags($cutoff, $now);
        $originalstate = $this->get_notification_state();
        $notificationstate = $this->prune_notification_state($originalstate);
        $eligibleflags = [];
        $renotifycutoff = $now - (7 * DAYSECS);

        foreach ($staleflags as $flag) {
            $lastnotified = $notificationstate[$flag->questionid] ?? 0;
            if ($lastnotified <= $renotifycutoff) {
                $eligibleflags[] = $flag;
            }
        }

        $sentemails = 0;
        if ($eligibleflags) {
            $this->add_latest_reasons($eligibleflags);
            $sentemails = $this->notify_reviewers($eligibleflags, $escalatedays);

            // The summary is global for all reviewers. Mark its flags after at least
            // one successful delivery so a broken recipient cannot cause daily repeats.
            if ($sentemails > 0) {
                foreach ($eligibleflags as $flag) {
                    $notificationstate[$flag->questionid] = $now;
                }
            }
        }

        if ($notificationstate !== $originalstate) {
            $this->save_notification_state($notificationstate);
        }

        $this->write_execution_log($sentemails > 0);

        mtrace('local_questions: Stale flag escalation checked ' . count($staleflags)
            . ' stale flags; ' . count($eligibleflags) . ' eligible; '
            . $sentemails . ' summary emails sent.');
    }

    /**
     * Get pending flags whose last status change is at or before the cutoff.
     *
     * @param int $cutoff Oldest allowed timestamp
     * @param int $now Current timestamp
     * @return \stdClass[]
     */
    private function get_stale_flags(int $cutoff, int $now): array {
        global $DB;

        $pendingtime = 'CASE WHEN fs.timemodified > 0 THEN fs.timemodified ELSE fs.timecreated END';
        $sql = "SELECT fs.id, fs.questionid, fs.timecreated, fs.timemodified,
                       q.name AS questionname, qc.name AS categoryname
                  FROM {local_questions_flag_status} fs
                  JOIN {question} q ON q.id = fs.questionid
             LEFT JOIN {question_versions} qv ON qv.id = (
                       SELECT MIN(qv2.id)
                         FROM {question_versions} qv2
                        WHERE qv2.questionid = q.id
                       )
             LEFT JOIN {question_bank_entries} qbe ON qbe.id = qv.questionbankentryid
             LEFT JOIN {question_categories} qc ON qc.id = qbe.questioncategoryid
                 WHERE fs.status = :status
                   AND {$pendingtime} <= :cutoff
              ORDER BY {$pendingtime} ASC, fs.questionid ASC";

        $flags = $DB->get_records_sql($sql, [
            'status' => \local_questions\flag_manager::STATUS_PENDING,
            'cutoff' => $cutoff,
        ]);

        foreach ($flags as $flag) {
            $pendingtimevalue = (int)$flag->timemodified > 0
                ? (int)$flag->timemodified
                : (int)$flag->timecreated;
            $flag->pendingdays = max(0, (int)floor(($now - $pendingtimevalue) / DAYSECS));
        }

        return array_values($flags);
    }

    /**
     * Attach the newest flag reason to every status record.
     *
     * @param \stdClass[] $staleflags Stale flag status records
     */
    private function add_latest_reasons(array $staleflags): void {
        global $DB;

        $questionids = array_map(static function(\stdClass $flag): int {
            return (int)$flag->questionid;
        }, $staleflags);
        [$insql, $params] = $DB->get_in_or_equal($questionids, SQL_PARAMS_NAMED, 'latestqid');
        $recordset = $DB->get_recordset_select(
            'local_questions_flags',
            "questionid {$insql}",
            $params,
            'questionid ASC, timecreated DESC, id DESC',
            'id, questionid, reason'
        );

        $latestreasons = [];
        foreach ($recordset as $flag) {
            if (!array_key_exists($flag->questionid, $latestreasons)) {
                $latestreasons[$flag->questionid] = $flag->reason;
            }
        }
        $recordset->close();

        foreach ($staleflags as $flag) {
            $flag->latestreason = $latestreasons[$flag->questionid] ?? '';
        }
    }

    /**
     * Send one summary email to each available reviewer.
     *
     * @param \stdClass[] $staleflags Flags to include
     * @param int $escalatedays Configured age threshold
     * @return int Number of successfully submitted emails
     */
    private function notify_reviewers(array $staleflags, int $escalatedays): int {
        $sent = 0;

        foreach (\local_questions\flag_manager::get_reviewer_ids() as $userid) {
            $user = \core_user::get_user($userid);
            if (!$user || !empty($user->deleted) || !empty($user->suspended)) {
                continue;
            }

            try {
                if ($this->send_summary_email($user, $staleflags, $escalatedays)) {
                    $sent++;
                }
            } catch (\Throwable $e) {
                mtrace('local_questions: Could not send stale flag summary to user '
                    . $userid . ': ' . $e->getMessage());
            }
        }

        return $sent;
    }

    /**
     * Build and send a localized summary email.
     *
     * @param \stdClass $user Recipient
     * @param \stdClass[] $staleflags Flags to include
     * @param int $escalatedays Configured age threshold
     * @return bool Whether Moodle submitted the email successfully
     */
    private function send_summary_email(\stdClass $user, array $staleflags, int $escalatedays): bool {
        $stringmanager = get_string_manager();
        $lang = !empty($user->lang) ? $user->lang : current_language();
        $reviewurl = new \moodle_url('/local/questions/index.php', [
            'tab' => 'flags',
            'filter' => \local_questions\flag_manager::STATUS_PENDING,
        ]);
        $stringdata = (object)[
            'count' => count($staleflags),
            'days' => $escalatedays,
        ];

        $subject = $stringmanager->get_string(
            'notification_stale_flags_subject',
            'local_questions',
            $stringdata,
            $lang
        );
        $intro = $stringmanager->get_string(
            'notification_stale_flags_intro',
            'local_questions',
            $stringdata,
            $lang
        );
        $labels = [
            'qid' => $stringmanager->get_string('notification_stale_flags_qid', 'local_questions', null, $lang),
            'category' => $stringmanager->get_string('category', 'local_questions', null, $lang),
            'days' => $stringmanager->get_string('notification_stale_flags_days', 'local_questions', null, $lang),
            'reason' => $stringmanager->get_string('notification_stale_flags_reason', 'local_questions', null, $lang),
            'review' => $stringmanager->get_string('notification_stale_flags_review', 'local_questions', null, $lang),
            'more' => $stringmanager->get_string('notification_stale_flags_more', 'local_questions', null, $lang),
            'uncategorized' => $stringmanager->get_string(
                'notification_stale_flags_uncategorized',
                'local_questions',
                null,
                $lang
            ),
            'unknownreason' => $stringmanager->get_string(
                'notification_stale_flags_unknown_reason',
                'local_questions',
                null,
                $lang
            ),
        ];

        $plainrows = [
            implode(' | ', [$labels['qid'], $labels['category'], $labels['days'], $labels['reason']]),
        ];
        $htmlrows = '';

        // Cap del resumen: las olas de detección crean flags por centenares y el email debe seguir siendo legible.
        $maxrows = 100;
        $omitted = max(0, count($staleflags) - $maxrows);
        $staleflags = array_slice($staleflags, 0, $maxrows);

        foreach ($staleflags as $flag) {
            $category = $flag->categoryname ?: $labels['uncategorized'];
            $reason = $this->get_localized_reason(
                (string)$flag->latestreason,
                $lang,
                $labels['unknownreason']
            );
            $plainrows[] = implode(' | ', [
                (int)$flag->questionid,
                $category,
                (int)$flag->pendingdays,
                $reason,
            ]);
            $htmlrows .= '<tr><td>' . (int)$flag->questionid . '</td><td>' . s($category)
                . '</td><td>' . (int)$flag->pendingdays . '</td><td>' . s($reason) . '</td></tr>';
        }

        $moretext = $omitted > 0 ? "\n(+" . $omitted . ' ' . $labels['more'] . ')' : '';
        $plainmessage = $intro . "\n\n" . implode("\n", $plainrows) . $moretext
            . "\n\n" . $labels['review'] . ': ' . $reviewurl->out(false);
        $htmlmessage = '<p>' . s($intro) . '</p>'
            . '<table style="border-collapse:collapse" border="1" cellpadding="6">'
            . '<thead><tr><th>' . s($labels['qid']) . '</th><th>' . s($labels['category'])
            . '</th><th>' . s($labels['days']) . '</th><th>' . s($labels['reason'])
            . '</th></tr></thead><tbody>' . $htmlrows . '</tbody></table>'
            . ($omitted > 0 ? '<p>+' . $omitted . ' ' . s($labels['more']) . '</p>' : '')
            . '<p><a href="' . s($reviewurl->out(false)) . '">' . s($labels['review']) . '</a></p>';

        return email_to_user(
            $user,
            \core_user::get_noreply_user(),
            $subject,
            $plainmessage,
            $htmlmessage
        );
    }

    /**
     * Localize a known reason code, falling back to the raw code.
     *
     * @param string $reason Reason code
     * @param string $lang Recipient language
     * @param string $unknownreason Label used when no reason exists
     * @return string
     */
    private function get_localized_reason(string $reason, string $lang, string $unknownreason): string {
        if ($reason === '') {
            return $unknownreason;
        }

        $identifier = 'reason_' . $reason;
        $stringmanager = get_string_manager();
        if ($stringmanager->string_exists($identifier, 'local_questions')) {
            return $stringmanager->get_string($identifier, 'local_questions', null, $lang);
        }

        return $reason;
    }

    /**
     * Read the persisted question ID => last notification time map.
     *
     * @return int[]
     */
    private function get_notification_state(): array {
        $json = get_config('local_questions', self::NOTIFICATION_STATE_CONFIG);
        if (!is_string($json) || $json === '') {
            return [];
        }

        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            return [];
        }

        $state = [];
        foreach ($decoded as $questionid => $timestamp) {
            if ((int)$questionid > 0 && (int)$timestamp > 0) {
                $state[(int)$questionid] = (int)$timestamp;
            }
        }

        return $state;
    }

    /**
     * Remove throttling entries for flags that are no longer pending.
     *
     * @param int[] $state Current state
     * @return int[]
     */
    private function prune_notification_state(array $state): array {
        global $DB;

        if (!$state) {
            return [];
        }

        [$insql, $params] = $DB->get_in_or_equal(array_keys($state), SQL_PARAMS_NAMED, 'stateqid');
        $params['pendingstatus'] = \local_questions\flag_manager::STATUS_PENDING;
        $pendingids = $DB->get_fieldset_select(
            'local_questions_flag_status',
            "questionid {$insql} AND status = :pendingstatus",
            $params
        );
        $pendingkeys = array_fill_keys(array_map('intval', $pendingids), true);

        return array_intersect_key($state, $pendingkeys);
    }

    /**
     * Persist the throttling map without adding a database column.
     *
     * @param int[] $state Question ID => last notification time
     */
    private function save_notification_state(array $state): void {
        ksort($state, SORT_NUMERIC);
        set_config(self::NOTIFICATION_STATE_CONFIG, json_encode($state), 'local_questions');
    }

    /**
     * Write exactly one audit row for this execution.
     *
     * The task operates on a set of questions and as the system user, so zero is
     * used for the non-null questionid and userid sentinel fields.
     *
     * @param bool $alertsent Whether at least one email was sent
     */
    private function write_execution_log(bool $alertsent): void {
        global $DB;

        $record = (object)[
            'questionid' => 0,
            'userid' => 0,
            'action' => $alertsent ? self::ACTION_ALERT : self::ACTION_CHECK,
            'contextid' => \context_system::instance()->id,
            'timecreated' => time(),
        ];
        $DB->insert_record('local_questions_log', $record);
    }
}
