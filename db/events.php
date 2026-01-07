<?php
/**
 * Event observers for local_questions.
 *
 * @package    local_questions
 * @copyright  2026 Sergio C.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname' => '\core\event\question_created',
        'callback'  => '\local_questions\observer::question_created',
    ],
    [
        'eventname' => '\core\event\question_deleted',
        'callback'  => '\local_questions\observer::question_deleted',
    ],
    [
        'eventname' => '\core\event\question_updated',
        'callback'  => '\local_questions\observer::question_updated',
    ],
];
