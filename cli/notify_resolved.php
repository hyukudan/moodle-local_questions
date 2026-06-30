<?php
/**
 * CLI script to resolve flagged questions and notify flaggers.
 *
 * Usage:
 *   php local/questions/cli/notify_resolved.php --ids=123,456 --resolution=fixed --feedback="Updated."
 *
 * @package    local_questions
 * @copyright  2026 Sergio C.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/local/questions/classes/flag_manager.php');

[$options, $unrecognised] = cli_get_params(
    [
        'help' => false,
        'ids' => '',
        'resolution' => \local_questions\flag_manager::RESOLUTION_FIXED,
        'feedback' => '',
    ],
    [
        'h' => 'help',
    ]
);

if ($unrecognised) {
    $unrecognised = implode("\n  ", $unrecognised);
    cli_error("Unknown options:\n  {$unrecognised}");
}

if (!empty($options['help']) || empty($options['ids'])) {
    $help = "Resolve flagged questions and notify users who reported them.

Options:
--ids          Comma-separated question IDs to resolve.
--resolution   Resolution type: fixed, no_action or duplicate. Defaults to fixed.
--feedback     Feedback sent to users who reported the question.
-h, --help     Show this help.
";
    mtrace($help);
    exit(empty($options['ids']) ? 1 : 0);
}

$ids = clean_param($options['ids'], PARAM_SEQUENCE);
$questionids = array_filter(array_map('intval', explode(',', $ids)));
$resolution = clean_param($options['resolution'], PARAM_ALPHANUMEXT);
$feedback = clean_param($options['feedback'], PARAM_TEXT);

if (empty($questionids)) {
    cli_error('No valid question IDs supplied.');
}

if (!array_key_exists($resolution, \local_questions\flag_manager::get_resolutions())) {
    cli_error('Invalid resolution. Use fixed, no_action or duplicate.');
}

$admin = get_admin();
if (!$admin) {
    cli_error('No site admin user found to record as resolver.');
}

$resolved = 0;
$skipped = 0;
$errors = 0;

foreach ($questionids as $questionid) {
    $status = $DB->get_record('local_questions_flag_status', ['questionid' => $questionid]);
    if (!$status) {
        mtrace("SKIP  q={$questionid}: flag status not found.");
        $skipped++;
        continue;
    }

    try {
        \local_questions\flag_manager::resolve($questionid, (int)$admin->id, $resolution, $feedback);
        mtrace("OK    q={$questionid}: resolved with {$resolution}.");
        $resolved++;
    } catch (\Throwable $e) {
        mtrace("ERROR q={$questionid}: " . $e->getMessage());
        $errors++;
    }
}

mtrace("");
mtrace("Done. resolved={$resolved} skipped={$skipped} errors={$errors}");
