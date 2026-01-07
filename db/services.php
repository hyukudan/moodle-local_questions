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
];

$services = [
    'local_questions' => [
        'functions' => [
            'local_questions_save_question_field',
        ],
        'requiredcapability' => 'local/questions:view',
        'restrictedusers' => 0,
        'enabled' => 1,
    ],
];
