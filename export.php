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

    $filters = [];
    if (!empty($qtype)) {
        $filters['qtype'] = $qtype;
    }

    $result = question_io::export_to_csv($categoryid, $recurse, $filters);

    if ($result['count'] === 0) {
        redirect(
            new moodle_url('/local/questions/index.php', ['tab' => 'export']),
            get_string('noquestionstoexport', 'local_questions'),
            null,
            \core\output\notification::NOTIFY_WARNING
        );
    }

    // Get category name for filename.
    $category = $DB->get_record('question_categories', ['id' => $categoryid], 'name');
    $filename = clean_filename('questions_' . ($category ? $category->name : $categoryid) . '_' . date('Ymd_His') . '.csv');

    // Send file download.
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($result['data']));
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
