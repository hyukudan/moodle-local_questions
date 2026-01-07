<?php
/**
 * Import questions page.
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
require_capability('local/questions:import', $context);

// Check if import is enabled.
if (!get_config('local_questions', 'enable_import')) {
    throw new moodle_exception('nopermission');
}

$PAGE->set_url(new moodle_url('/local/questions/import.php'));
$PAGE->set_context($context);
$PAGE->set_title(get_string('importquestions', 'local_questions'));
$PAGE->set_heading(get_string('importquestions', 'local_questions'));

// Get renderer.
$output = $PAGE->get_renderer('local_questions');

$action = optional_param('action', '', PARAM_ALPHA);
$categoryid = optional_param('categoryid', 0, PARAM_INT);

$preview = null;
$results = null;

if ($action === 'preview' && confirm_sesskey()) {
    // Handle file upload for preview.
    $categoryid = required_param('categoryid', PARAM_INT);
    
    if (isset($_FILES['csvfile']) && $_FILES['csvfile']['error'] === UPLOAD_ERR_OK) {
        $tmpfile = $_FILES['csvfile']['tmp_name'];
        
        // Save to draft area for later use.
        $draftitemid = file_get_unused_draft_itemid();
        $usercontext = context_user::instance($USER->id);
        
        $fs = get_file_storage();
        $filerecord = [
            'contextid' => $usercontext->id,
            'component' => 'user',
            'filearea' => 'draft',
            'itemid' => $draftitemid,
            'filepath' => '/',
            'filename' => $_FILES['csvfile']['name']
        ];
        
        $storedfile = $fs->create_file_from_pathname($filerecord, $tmpfile);
        
        // Generate preview.
        $preview = question_io::preview_csv($tmpfile);
        
        echo $output->header();
        echo $output->render_tabs('import');
        echo $output->render_import_form($categoryid, $preview, $draftitemid);
        echo $output->footer();
        exit;
    } else {
        redirect(
            new moodle_url('/local/questions/import.php'),
            get_string('nofileselected', 'local_questions'),
            null,
            \core\output\notification::NOTIFY_ERROR
        );
    }

} else if ($action === 'confirm' && confirm_sesskey()) {
    // Process actual import.
    $categoryid = required_param('categoryid', PARAM_INT);
    $draftid = required_param('draftid', PARAM_INT);
    
    $usercontext = context_user::instance($USER->id);
    $fs = get_file_storage();
    
    $files = $fs->get_area_files($usercontext->id, 'user', 'draft', $draftid, '', false);
    
    if (empty($files)) {
        redirect(
            new moodle_url('/local/questions/import.php'),
            get_string('nofileselected', 'local_questions'),
            null,
            \core\output\notification::NOTIFY_ERROR
        );
    }
    
    $file = reset($files);
    $tmpfile = $file->copy_content_to_temp();
    
    $results = question_io::import_from_csv($tmpfile, $categoryid);
    
    // Clean up.
    $fs->delete_area_files($usercontext->id, 'user', 'draft', $draftid);
    @unlink($tmpfile);
    
    echo $output->header();
    echo $output->render_tabs('import');
    echo $output->render_import_form($categoryid, null, null, $results);
    echo $output->footer();
    exit;
}

// Default: show upload form.
echo $output->header();
echo $output->render_tabs('import');
echo $output->render_import_form();
echo $output->footer();
