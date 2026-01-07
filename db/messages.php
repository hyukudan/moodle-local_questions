<?php
/**
 * Message provider definitions for local_questions.
 *
 * @package    local_questions
 * @copyright  2026 Sergio C.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$messageproviders = [
    // Notification when a flag is resolved.
    'flagresolved' => [
        'capability' => 'local/questions:flag',
        'defaults' => [
            'popup' => MESSAGE_PERMITTED + MESSAGE_DEFAULT_ENABLED,
            'email' => MESSAGE_PERMITTED,
        ],
    ],

    // Notification when a flag is dismissed.
    'flagdismissed' => [
        'capability' => 'local/questions:flag',
        'defaults' => [
            'popup' => MESSAGE_PERMITTED + MESSAGE_DEFAULT_ENABLED,
            'email' => MESSAGE_DISALLOWED,
        ],
    ],

    // Notification to reviewers about new flags.
    'newflag' => [
        'capability' => 'local/questions:reviewflags',
        'defaults' => [
            'popup' => MESSAGE_PERMITTED + MESSAGE_DEFAULT_ENABLED,
            'email' => MESSAGE_PERMITTED,
        ],
    ],
];
