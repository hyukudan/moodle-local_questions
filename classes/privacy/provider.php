<?php
/**
 * Privacy provider for local_questions.
 *
 * @package    local_questions
 * @copyright  2026 Sergio C.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_questions\privacy;

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy provider class - declares and handles user data for GDPR compliance.
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {

    /**
     * Returns meta data about this system.
     *
     * @param collection $collection The initialised collection to add items to.
     * @return collection A listing of user data stored through this system.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(
            'local_questions_flags',
            [
                'userid' => 'privacy:metadata:local_questions_flags:userid',
                'questionid' => 'privacy:metadata:local_questions_flags:questionid',
                'reason' => 'privacy:metadata:local_questions_flags:reason',
                'comment' => 'privacy:metadata:local_questions_flags:comment',
                'timecreated' => 'privacy:metadata:local_questions_flags:timecreated',
            ],
            'privacy:metadata:local_questions_flags'
        );

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid The user to search.
     * @return contextlist The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        // Flags are stored at system context level.
        $sql = "SELECT ctx.id
                  FROM {context} ctx
                  JOIN {local_questions_flags} lqf ON ctx.contextlevel = :contextlevel
                 WHERE lqf.userid = :userid";

        $params = [
            'contextlevel' => CONTEXT_SYSTEM,
            'userid' => $userid,
        ];

        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist The userlist containing the list of users.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if ($context->contextlevel !== CONTEXT_SYSTEM) {
            return;
        }

        $sql = "SELECT DISTINCT userid FROM {local_questions_flags}";
        $userlist->add_from_sql('userid', $sql, []);
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel !== CONTEXT_SYSTEM) {
                continue;
            }

            // Get all flags by this user.
            $flags = $DB->get_records('local_questions_flags', ['userid' => $userid]);

            if (empty($flags)) {
                continue;
            }

            $flagdata = [];
            foreach ($flags as $flag) {
                // Get question name for context.
                $question = $DB->get_record('question', ['id' => $flag->questionid], 'id, name');

                $flagdata[] = [
                    'questionid' => $flag->questionid,
                    'questionname' => $question ? $question->name : get_string('questionnotfound', 'local_questions'),
                    'reason' => $flag->reason,
                    'comment' => $flag->comment,
                    'timecreated' => \core_privacy\local\request\transform::datetime($flag->timecreated),
                ];
            }

            writer::with_context($context)->export_data(
                [get_string('pluginname', 'local_questions'), get_string('flags', 'local_questions')],
                (object)['flags' => $flagdata]
            );
        }
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context The specific context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if ($context->contextlevel !== CONTEXT_SYSTEM) {
            return;
        }

        // Delete all flags and recalculate status counts.
        $DB->delete_records('local_questions_flags');

        // Reset all flag status records to zero.
        $DB->execute("UPDATE {local_questions_flag_status} SET flagcount = 0");
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel !== CONTEXT_SYSTEM) {
                continue;
            }

            // Get affected question IDs before deletion.
            $questionids = $DB->get_fieldset_select(
                'local_questions_flags',
                'DISTINCT questionid',
                'userid = ?',
                [$userid]
            );

            // Delete user's flags.
            $DB->delete_records('local_questions_flags', ['userid' => $userid]);

            // Update flag counts for affected questions.
            foreach ($questionids as $questionid) {
                $newcount = $DB->count_records('local_questions_flags', ['questionid' => $questionid]);
                $DB->set_field('local_questions_flag_status', 'flagcount', $newcount, ['questionid' => $questionid]);
            }
        }
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();

        if ($context->contextlevel !== CONTEXT_SYSTEM) {
            return;
        }

        $userids = $userlist->get_userids();
        if (empty($userids)) {
            return;
        }

        list($insql, $inparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);

        // Get affected question IDs before deletion.
        $questionids = $DB->get_fieldset_select(
            'local_questions_flags',
            'DISTINCT questionid',
            "userid $insql",
            $inparams
        );

        // Delete flags for all specified users.
        $DB->delete_records_select('local_questions_flags', "userid $insql", $inparams);

        // Update flag counts for affected questions.
        foreach ($questionids as $questionid) {
            $newcount = $DB->count_records('local_questions_flags', ['questionid' => $questionid]);
            $DB->set_field('local_questions_flag_status', 'flagcount', $newcount, ['questionid' => $questionid]);
        }
    }
}
