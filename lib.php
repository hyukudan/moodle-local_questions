<?php
/**
 * Library functions for local_questions.
 *
 * @package    local_questions
 * @copyright  2026 Sergio C.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Extend navigation to add Questions Management link.
 *
 * @param global_navigation $navigation
 */
function local_questions_extend_navigation(global_navigation $navigation) {
    global $PAGE;
    
    $context = context_system::instance();
    if (has_capability('local/questions:view', $context)) {
        $node = $navigation->add(
            get_string('pluginname', 'local_questions'),
            new moodle_url('/local/questions/index.php'),
            navigation_node::TYPE_CUSTOM,
            null,
            'local_questions',
            new pix_icon('i/report', '')
        );
        $node->showinflatnavigation = true;
    }
}

/**
 * Add link to settings block navigation.
 *
 * @param settings_navigation $settingsnav
 * @param context $context
 */
function local_questions_extend_settings_navigation(settings_navigation $settingsnav, context $context) {
    if ($context->contextlevel === CONTEXT_SYSTEM && has_capability('local/questions:manage', $context)) {
        $settingsnav->add(
            get_string('pluginname', 'local_questions'),
            new moodle_url('/local/questions/index.php'),
            navigation_node::TYPE_SETTING,
            null,
            'local_questions_settings',
            new pix_icon('i/settings', '')
        );
    }
}

/**
 * Add flag button to question footer in quiz review.
 *
 * This hook is called by the question engine during rendering.
 *
 * @param int $questionid The question ID
 * @param context $context The context (optional)
 * @param string $component The component name (optional)
 * @return string HTML for the flag button
 */
function local_questions_get_question_footer($questionid, $context = null, $component = '') {
    global $DB, $OUTPUT, $PAGE, $USER;

    // Check if flagging is enabled.
    if (!get_config('local_questions', 'enable_flagging')) {
        return '';
    }

    // Only show flag button in quiz review mode, not during attempt.
    // Check the current page URL to determine if we're in review mode.
    $pagepath = $PAGE->url->get_path();
    if (strpos($pagepath, '/mod/quiz/attempt.php') !== false) {
        // During quiz attempt - don't show flag button.
        return '';
    }

    // Check capability.
    $syscontext = context_system::instance();
    if (!has_capability('local/questions:flag', $syscontext)) {
        return '';
    }

    // Check if user already flagged this question.
    $alreadyflagged = $DB->record_exists('local_questions_flags', [
        'questionid' => $questionid,
        'userid' => $USER->id,
    ]);

    // Load JS module.
    $PAGE->requires->js_call_amd('local_questions/flagging', 'init');

    // Get reasons for the modal template.
    $reasons = [];
    foreach (\local_questions\flag_manager::get_reasons() as $value => $label) {
        $reasons[] = ['value' => $value, 'label' => $label];
    }

    // Render button.
    $buttondata = [
        'questionid' => $questionid,
        'alreadyflagged' => $alreadyflagged,
        'canflag' => true,
    ];
    $buttonhtml = $OUTPUT->render_from_template('local_questions/flag_button', $buttondata);

    // Render modal (only once per page).
    static $modalrendered = false;
    $modalhtml = '';
    if (!$modalrendered) {
        $modaldata = ['reasons' => $reasons];
        $modalhtml = $OUTPUT->render_from_template('local_questions/flag_modal', $modaldata);
        $modalrendered = true;
    }

    return $buttonhtml . $modalhtml;
}
