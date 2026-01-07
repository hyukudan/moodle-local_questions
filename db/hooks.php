<?php
/**
 * Hooks for local_questions.
 *
 * @package    local_questions
 * @copyright  2026 Sergio C.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$callbacks = [
    [
        'hook' => \core_question\hook\after_question_created::class,
        'callback' => [\local_questions\hooks_handler::class, 'after_question_created'],
    ],
];
