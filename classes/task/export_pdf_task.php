<?php
/**
 * Ad-hoc task: generate large PDF question exports in background.
 *
 * Synchronous PDF generation via TCPDF can exhaust memory and the request
 * timeout when the selected category contains thousands of questions, even
 * with raise_memory_limit(MEMORY_EXTRA). This task generates the PDF off the
 * web request, stores it in the user's private files area, and notifies the
 * user when it is ready.
 *
 * @package    local_questions
 * @copyright  2026 Sergio C.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_questions\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Ad-hoc task that builds a large question PDF export in background.
 *
 * Custom data payload (set via set_custom_data):
 *   - categoryid (int)        Question category to export.
 *   - recurse    (int|bool)   Whether to include subcategories.
 *   - filters    (array)      Optional filters (qtype, etc).
 *   - categoryname (string)   Pre-resolved name for the filename/notification.
 *
 * The owning user is set via set_userid() before queueing.
 */
class export_pdf_task extends \core\task\adhoc_task {

    /**
     * Build the PDF in background and stash it in the user's private files
     * area, then notify the user with a download link.
     */
    public function execute() {
        global $DB, $CFG;

        $data = $this->get_custom_data();
        $userid = $this->get_userid();

        if (empty($userid)) {
            mtrace('local_questions export_pdf_task: missing userid, aborting.');
            return;
        }

        $categoryid = (int)($data->categoryid ?? 0);
        $recurse = !empty($data->recurse);
        $filters = isset($data->filters) ? (array)$data->filters : [];
        $categoryname = (string)($data->categoryname ?? '');

        if ($categoryid <= 0) {
            mtrace('local_questions export_pdf_task: missing categoryid, aborting.');
            return;
        }

        // Background context - raise generously, this is not a web request.
        \core_php_time_limit::raise(0);
        raise_memory_limit(MEMORY_HUGE);

        $user = \core_user::get_user($userid);
        if (!$user || !empty($user->deleted) || !empty($user->suspended)) {
            mtrace("local_questions export_pdf_task: user {$userid} unavailable, aborting.");
            return;
        }

        try {
            $result = \local_questions\question_io::export_to_pdf($categoryid, $recurse, $filters);
        } catch (\Throwable $e) {
            mtrace('local_questions export_pdf_task: export failed - ' . $e->getMessage());
            $this->notify_failure($user, $categoryname, $e->getMessage());
            return;
        }

        if (empty($result['count']) || empty($result['data'])) {
            mtrace('local_questions export_pdf_task: empty export, aborting.');
            $this->notify_failure($user, $categoryname,
                get_string('noquestionstoexport', 'local_questions'));
            return;
        }

        // Stash PDF in user's private files so they can download it later.
        $usercontext = \context_user::instance($userid);
        $fs = get_file_storage();

        $filename = clean_filename(
            'questions_' . ($categoryname ?: $categoryid) . '_' . date('Ymd_His') . '.pdf'
        );

        $filerecord = [
            'contextid' => $usercontext->id,
            'component' => 'user',
            'filearea'  => 'private',
            'itemid'    => 0,
            'filepath'  => '/',
            'filename'  => $filename,
            'userid'    => $userid,
        ];

        // If a file with the exact same name already exists, suffix it.
        if ($fs->file_exists($usercontext->id, 'user', 'private', 0, '/', $filename)) {
            $filerecord['filename'] = clean_filename(
                pathinfo($filename, PATHINFO_FILENAME) . '_' . uniqid() . '.pdf'
            );
        }

        $storedfile = $fs->create_file_from_string($filerecord, $result['data']);
        unset($result); // Free memory.

        mtrace("local_questions export_pdf_task: stored {$storedfile->get_filename()} "
            . '(' . display_size($storedfile->get_filesize()) . ') for user ' . $userid);

        $this->notify_success($user, $categoryname, $storedfile);
    }

    /**
     * Notify the user that their PDF export is ready.
     *
     * @param \stdClass     $user
     * @param string        $categoryname
     * @param \stored_file  $file
     */
    private function notify_success(\stdClass $user, string $categoryname, \stored_file $file): void {
        global $CFG;

        $downloadurl = new \moodle_url('/user/files.php');

        $a = (object)[
            'category' => $categoryname,
            'filename' => $file->get_filename(),
            'size'     => display_size($file->get_filesize()),
            'url'      => $downloadurl->out(false),
        ];

        $message = new \core\message\message();
        $message->component = 'moodle';
        $message->name = 'instantmessage';
        $message->userfrom = \core_user::get_noreply_user();
        $message->userto = $user;
        $message->subject = get_string('exportpdf_ready_subject', 'local_questions', $a);
        $message->fullmessage = get_string('exportpdf_ready_body', 'local_questions', $a);
        $message->fullmessageformat = FORMAT_PLAIN;
        $message->fullmessagehtml = '<p>' . s($message->fullmessage) . '</p>'
            . '<p><a href="' . s($downloadurl->out(false)) . '">'
            . get_string('exportpdf_ready_link', 'local_questions') . '</a></p>';
        $message->smallmessage = get_string('exportpdf_ready_small', 'local_questions', $a);
        $message->notification = 1;
        $message->contexturl = $downloadurl->out(false);
        $message->contexturlname = get_string('privatefiles');

        message_send($message);
    }

    /**
     * Notify the user that their PDF export failed.
     *
     * @param \stdClass $user
     * @param string    $categoryname
     * @param string    $reason
     */
    private function notify_failure(\stdClass $user, string $categoryname, string $reason): void {
        $a = (object)[
            'category' => $categoryname,
            'reason'   => $reason,
        ];

        $message = new \core\message\message();
        $message->component = 'moodle';
        $message->name = 'instantmessage';
        $message->userfrom = \core_user::get_noreply_user();
        $message->userto = $user;
        $message->subject = get_string('exportpdf_failed_subject', 'local_questions', $a);
        $message->fullmessage = get_string('exportpdf_failed_body', 'local_questions', $a);
        $message->fullmessageformat = FORMAT_PLAIN;
        $message->fullmessagehtml = '<p>' . s($message->fullmessage) . '</p>';
        $message->smallmessage = get_string('exportpdf_failed_small', 'local_questions', $a);
        $message->notification = 1;

        message_send($message);
    }
}
