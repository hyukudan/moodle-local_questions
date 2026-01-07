<?php
/**
 * Main entry point for local_questions dashboard.
 *
 * @package    local_questions
 * @copyright  2026 Sergio C.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

// Security checks.
require_login();
$context = context_system::instance();
require_capability('local/questions:view', $context);

$PAGE->set_url(new moodle_url('/local/questions/index.php'));
$PAGE->set_context($context);
$PAGE->set_title(get_string('pluginname', 'local_questions'));
$PAGE->set_heading(get_string('dashboard', 'local_questions'));

// Get renderer.
$output = $PAGE->get_renderer('local_questions');

// Logic to get data.
global $DB;
$totalquestions = $DB->count_records('question');
$enablefeatures = get_config('local_questions', 'enable_features');

// Get tab parameter.
$tab = optional_param('tab', 'dashboard', PARAM_ALPHA);

echo $output->header();
echo $output->render_tabs($tab);

switch ($tab) {
    case 'questions':
        $categoryid = optional_param('cat', 0, PARAM_INT);
        $recurse = optional_param('recurse', 0, PARAM_BOOL);
        echo $output->render_questions_view($categoryid, $recurse);
        break;
    case 'dashboard':
    default:
        echo $output->render_dashboard($totalquestions, (bool)$enablefeatures);
        break;
}
echo $output->footer();
