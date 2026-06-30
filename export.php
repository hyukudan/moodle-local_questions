<?php
/**
 * Export questions page.
 *
 * @package    local_questions
 * @copyright  2026 Sergio C.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/questionlib.php');

use local_questions\question_io;

require_login();
$context = context_system::instance();
require_capability('local/questions:export', $context);

// Check if export is enabled.
if (!get_config('local_questions', 'enable_export')) {
    throw new moodle_exception('nopermission');
}

$PAGE->set_url(new moodle_url('/local/questions/export.php'));
$PAGE->set_context($context);
$PAGE->set_title(get_string('exportquestions', 'local_questions'));
$PAGE->set_heading(get_string('exportquestions', 'local_questions'));

// Handle form submission.
$action = optional_param('action', '', PARAM_ALPHA);

if ($action === 'export' && confirm_sesskey()) {
    $categoryid = required_param('categoryid', PARAM_INT);
    $recurse = optional_param('recurse', 0, PARAM_BOOL);
    $format = optional_param('format', 'csv', PARAM_ALPHA);
    $qtype = optional_param('qtype', '', PARAM_ALPHANUMEXT);

    if ($categoryid > 0) {
        $category = $DB->get_record('question_categories', ['id' => $categoryid], 'id, name, contextid', MUST_EXIST);
        $catcontext = \context::instance_by_id($category->contextid);
        require_capability('moodle/question:viewall', $catcontext);
    } else {
        require_capability('moodle/question:viewall', context_system::instance());
    }

    $filters = [];
    if (!empty($qtype)) {
        $filters['qtype'] = $qtype;
    }

    // Get category name for filename / async notifications.
    $catname = $categoryid > 0 ? $category->name : get_string('allcategories', 'local_questions');

    // For PDF exports we may need to dispatch to an ad-hoc task: TCPDF is
    // memory-hungry and large categories can blow the request limits even with
    // raise_memory_limit(MEMORY_EXTRA). Decide based on a cheap COUNT.
    if ($format === 'pdf') {
        $threshold = (int)get_config('local_questions', 'pdf_async_threshold');
        if ($threshold < 0) {
            $threshold = 0;
        }

        $expected = question_io::count_for_export($categoryid, $recurse, $filters);

        if ($expected === 0) {
            redirect(
                new moodle_url('/local/questions/index.php', ['tab' => 'export']),
                get_string('noquestionstoexport', 'local_questions'),
                null,
                \core\output\notification::NOTIFY_WARNING
            );
        }

        if ($threshold > 0 && $expected > $threshold) {
            // Queue ad-hoc task; user will be notified when the PDF is ready.
            $task = new \local_questions\task\export_pdf_task();
            $task->set_userid($USER->id);
            $task->set_custom_data([
                'categoryid'   => $categoryid,
                'recurse'      => $recurse ? 1 : 0,
                'filters'      => $filters,
                'categoryname' => $catname,
            ]);
            \core\task\manager::queue_adhoc_task($task);

            redirect(
                new moodle_url('/local/questions/index.php', ['tab' => 'export']),
                get_string('exportpdf_queued', 'local_questions',
                    (object)['count' => $expected]),
                null,
                \core\output\notification::NOTIFY_SUCCESS
            );
        }

        $result = question_io::export_to_pdf($categoryid, $recurse, $filters);
    } else {
        $result = question_io::export_to_csv($categoryid, $recurse, $filters);
    }

    if ($result['count'] === 0) {
        redirect(
            new moodle_url('/local/questions/index.php', ['tab' => 'export']),
            get_string('noquestionstoexport', 'local_questions'),
            null,
            \core\output\notification::NOTIFY_WARNING
        );
    }

    if ($format === 'pdf') {
        $filename = clean_filename('questions_' . $catname . '_' . date('Ymd_His') . '.pdf');
        header('Content-Type: application/pdf');
    } else {
        $filename = clean_filename('questions_' . $catname . '_' . date('Ymd_His') . '.csv');
        header('Content-Type: text/csv; charset=utf-8');
    }

    header('Content-Disposition: attachment; filename="' . $filename . '"');
    // Use 8bit-safe length for binary PDF/CSV payloads — strlen() can be
    // overridden by the legacy mbstring.func_overload directive on some PHP
    // builds and report character count instead of bytes, which would
    // truncate the download client-side.
    header('Content-Length: ' . mb_strlen($result['data'], '8bit'));
    header('Cache-Control: no-cache, no-store, must-revalidate');

    echo $result['data'];
    exit;
}

// Get renderer.
$output = $PAGE->get_renderer('local_questions');

echo $output->header();
echo $output->render_tabs('export');
echo $output->render_export_form();
echo $output->footer();
