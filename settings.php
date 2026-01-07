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

    $settings->add(new admin_setting_configcheckbox(
        'local_questions/enable_export',
        get_string('enable_export', 'local_questions'),
        get_string('enable_export_desc', 'local_questions'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_questions/enable_import',
        get_string('enable_import', 'local_questions'),
        get_string('enable_import_desc', 'local_questions'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_questions/enable_flagging',
        get_string('enable_flagging', 'local_questions'),
        get_string('enable_flagging_desc', 'local_questions'),
        1
    ));

    // Gemini AI Settings
    $settings->add(new admin_setting_heading(
        'local_questions/gemini_heading',
        get_string('gemini_settings', 'local_questions'),
        get_string('gemini_settings_desc', 'local_questions')
    ));

    $settings->add(new admin_setting_configpasswordunmask(
        'local_questions/gemini_apikey',
        get_string('gemini_apikey', 'local_questions'),
        get_string('gemini_apikey_desc', 'local_questions'),
        ''
    ));

    $settings->add(new admin_setting_configselect(
        'local_questions/gemini_model',
        get_string('gemini_model', 'local_questions'),
        get_string('gemini_model_desc', 'local_questions'),
        'gemini-1.5-flash',
        [
            'gemini-1.5-flash' => 'Gemini 1.5 Flash (Fast & Cost-effective)',
            'gemini-1.5-pro' => 'Gemini 1.5 Pro (Complex Reasoning)',
        ]
    ));

    $default_prompt = "You are a Moodle Question Quality Auditor. Review the provided questions for errors in spelling, grammar, clarity, or missing feedback.
    
    For each question, check:
    1. Question text clarity and spelling.
    2. Answer correctness and spelling.
    3. Missing or unhelpful feedback.
    
    Return a JSON object with 'questions' array. Each item should have:
    - 'id': question id
    - 'issues': array of strings describing issues found (empty if none)
    - 'suggestions': array of objects { 'field': 'questiontext'|'answer:ID'|'feedback:ID', 'original': '...', 'suggested': '...' }
    
    Only suggest changes for actual errors or significant improvements.";

    $settings->add(new admin_setting_configtextarea(
        'local_questions/gemini_prompt',
        get_string('gemini_prompt', 'local_questions'),
        get_string('gemini_prompt_desc', 'local_questions'),
        $default_prompt
    ));
}

