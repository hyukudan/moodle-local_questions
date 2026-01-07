<?php
/**
 * Event observer class.
 *
 * @package    local_questions
 * @copyright  2026 Sergio C.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_questions;

defined('MOODLE_INTERNAL') || die();

/**
 * Class observer
 */
class observer {

    /**
     * Callback for question_created event.
     *
     * @param \core\event\question_created $event
     */
    public static function question_created(\core\event\question_created $event): void {
        // Security check: Ensure user has capability to view questions.
        // Although this is an event observer, we might want to ensure context.
        // In Moodle observers often run in the context of the action, but defensive coding is good.
        if (!is_siteadmin() && !has_capability('local/questions:view', \context_system::instance())) {
           // We typically don't block events based on caps, but we can return early if not relevant.
           // However, for auditing, we might want to log regardless.
        }

        // Log the question creation or perform actions.
        $data = $event->get_data();
        // Custom logic here...
    }
}
