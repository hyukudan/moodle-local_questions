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
