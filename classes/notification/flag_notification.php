<?php
/**
 * Flag notification sender.
 *
 * @package    local_questions
 * @copyright  2026 Sergio C.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_questions\notification;

defined('MOODLE_INTERNAL') || die();

/**
 * Handles sending notifications for question flags.
 */
class flag_notification {

    /**
     * Send a notification to a user about their flagged question.
     *
     * @param int $userid The user to notify
     * @param string $messagename The message provider name (flagresolved/flagdismissed)
     * @param \stdClass $question The question object
     * @param string $feedback The feedback message
     * @param string $resolution The resolution type
     */
    public static function send(int $userid, string $messagename, \stdClass $question,
            string $feedback, string $resolution): void {
        global $DB;

        $user = $DB->get_record('user', ['id' => $userid]);
        if (!$user || $user->deleted) {
            return;
        }

        // Build message content.
        $a = new \stdClass();
        $a->questionname = $question->name;
        $a->questionid = $question->id;
        $a->feedback = $feedback;
        $a->resolution = get_string('resolution_' . $resolution, 'local_questions');

        if ($messagename === 'flagresolved') {
            $subject = get_string('notification_resolved_subject', 'local_questions', $a);
            $fullmessage = get_string('notification_resolved_full', 'local_questions', $a);
            $smallmessage = get_string('notification_resolved_small', 'local_questions', $a);
        } else {
            $subject = get_string('notification_dismissed_subject', 'local_questions', $a);
            $fullmessage = get_string('notification_dismissed_full', 'local_questions', $a);
            $smallmessage = get_string('notification_dismissed_small', 'local_questions', $a);
        }

        // Create the message.
        $message = new \core\message\message();
        $message->component = 'local_questions';
        $message->name = $messagename;
        $message->userfrom = \core_user::get_noreply_user();
        $message->userto = $user;
        $message->subject = $subject;
        $message->fullmessage = $fullmessage;
        $message->fullmessageformat = FORMAT_PLAIN;
        $message->fullmessagehtml = self::format_html_message($fullmessage, $a);
        $message->smallmessage = $smallmessage;
        $message->notification = 1;
        $message->contexturl = new \moodle_url('/local/questions/index.php');
        $message->contexturlname = get_string('pluginname', 'local_questions');

        message_send($message);
    }

    /**
     * Send notification to reviewers about a new flag.
     *
     * @param int $userid The reviewer to notify
     * @param int $questionid The flagged question ID
     */
    public static function send_new_flag(int $userid, int $questionid): void {
        global $DB;

        $user = $DB->get_record('user', ['id' => $userid]);
        if (!$user || $user->deleted) {
            return;
        }

        $question = $DB->get_record('question', ['id' => $questionid], 'id, name');
        if (!$question) {
            return;
        }

        $a = new \stdClass();
        $a->questionname = $question->name;
        $a->questionid = $question->id;

        $subject = get_string('notification_newflag_subject', 'local_questions', $a);
        $fullmessage = get_string('notification_newflag_full', 'local_questions', $a);
        $smallmessage = get_string('notification_newflag_small', 'local_questions', $a);

        $message = new \core\message\message();
        $message->component = 'local_questions';
        $message->name = 'newflag';
        $message->userfrom = \core_user::get_noreply_user();
        $message->userto = $user;
        $message->subject = $subject;
        $message->fullmessage = $fullmessage;
        $message->fullmessageformat = FORMAT_PLAIN;
        $message->fullmessagehtml = self::format_html_message($fullmessage, $a);
        $message->smallmessage = $smallmessage;
        $message->notification = 1;
        $message->contexturl = new \moodle_url('/local/questions/index.php', ['tab' => 'flags']);
        $message->contexturlname = get_string('flaggedquestions', 'local_questions');

        message_send($message);
    }

    /**
     * Format a simple HTML message.
     *
     * @param string $text The plain text message
     * @param \stdClass $a The replacement object
     * @return string HTML formatted message
     */
    private static function format_html_message(string $text, \stdClass $a): string {
        $html = nl2br(s($text));

        // Add a styled container.
        return '<div style="font-family: Arial, sans-serif; padding: 15px; background: #f5f5f5; border-radius: 5px;">'
             . '<h3 style="color: #333; margin-top: 0;">' . s($a->questionname) . '</h3>'
             . '<p>' . $html . '</p>'
             . '</div>';
    }
}
