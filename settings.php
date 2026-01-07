<?php
/**
 * Plugin settings.
 *
 * @package    local_questions
 * @copyright  2026 Sergio C.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_questions', get_string('settings', 'local_questions'));

    $ADMIN->add('localplugins', $settings);

    $settings->add(new admin_setting_configcheckbox(
        'local_questions/enable_features',
        get_string('enable_features', 'local_questions'),
        get_string('enable_features_desc', 'local_questions'),
        1
    ));
}
