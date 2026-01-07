<?php
/**
 * External services definition.
 *
 * @package    local_questions
 * @copyright  2026 Sergio C.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$functions = [
    'local_questions_save_question_field' => [
        'classname' => 'local_questions\\external\\question_editor',
        'methodname' => 'save_question_field',
        'description' => 'Updates a specific field of a question.',
        'type' => 'write',
        'ajax' => true,
    ],
    'local_questions_analyze_batch' => [
        'classname' => 'local_questions\\external\\ai_service',
        'methodname' => 'analyze_batch',
        'description' => 'Analyze a batch of questions using AI.',
        'type' => 'write',
        'ajax' => true,
    ],

    // Flag services.
    'local_questions_submit_flag' => [
        'classname' => 'local_questions\\external\\flag_service',
        'methodname' => 'submit_flag',
        'description' => 'Submit a flag/report for a question.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/questions:flag',
    ],
    'local_questions_check_flag_status' => [
        'classname' => 'local_questions\\external\\flag_service',
        'methodname' => 'check_flag_status',
        'description' => 'Check if user has flagged a question.',
        'type' => 'read',
        'ajax' => true,
    ],
    'local_questions_get_flagged_questions' => [
        'classname' => 'local_questions\\external\\flag_service',
        'methodname' => 'get_flagged_questions',
        'description' => 'Get list of flagged questions for review.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'local/questions:reviewflags',
    ],
    'local_questions_get_flag_details' => [
        'classname' => 'local_questions\\external\\flag_service',
        'methodname' => 'get_flag_details',
        'description' => 'Get detailed flag information for a question.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'local/questions:reviewflags',
    ],
    'local_questions_update_flag_status' => [
        'classname' => 'local_questions\\external\\flag_service',
        'methodname' => 'update_flag_status',
        'description' => 'Resolve or dismiss a flagged question.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/questions:resolveflags',
    ],
];

$services = [
    'local_questions' => [
        'functions' => [
            'local_questions_save_question_field',
            'local_questions_analyze_batch',
            'local_questions_submit_flag',
            'local_questions_check_flag_status',
            'local_questions_get_flagged_questions',
            'local_questions_get_flag_details',
            'local_questions_update_flag_status',
        ],
        'requiredcapability' => 'local/questions:view',
        'restrictedusers' => 0,
        'enabled' => 1,
    ],
];
