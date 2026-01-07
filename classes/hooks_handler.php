<?php
/**
 * Hooks handler class.
 *
 * @package    local_questions
 * @copyright  2026 Sergio C.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_questions;

defined('MOODLE_INTERNAL') || die();

/**
 * Class hooks_handler
 */
class hooks_handler {

    /**
     * Callback for after_question_created hook.
     *
     * @param \core_question\hook\after_question_created $hook
     */
    public static function after_question_created(\core_question\hook\after_question_created $hook): void {
        // Here we could implement custom logic after a question is created.
        $question = $hook->get_question();
        // Log or process question...
    }
}
