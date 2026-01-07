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
        $eventdata = $event->get_data();
        $questionid = $eventdata['objectid'];
        $userid = $eventdata['userid'];
        $contextid = $eventdata['contextid'];
        
        // Log to Moodle's debugging in development mode.
        debugging("local_questions: Question ID {$questionid} created by user {$userid} in context {$contextid}", DEBUG_DEVELOPER);
        
        // Future: Add to custom statistics table or trigger notifications.
    }

    /**
     * Callback for question_deleted event.
     *
     * @param \core\event\question_deleted $event
     */
    public static function question_deleted(\core\event\question_deleted $event): void {
        $eventdata = $event->get_data();
        $questionid = $eventdata['objectid'];
        
        debugging("local_questions: Question ID {$questionid} was deleted", DEBUG_DEVELOPER);
    }

    /**
     * Callback for question_updated event.
     *
     * @param \core\event\question_updated $event
     */
    public static function question_updated(\core\event\question_updated $event): void {
        $eventdata = $event->get_data();
        $questionid = $eventdata['objectid'];
        
        debugging("local_questions: Question ID {$questionid} was updated", DEBUG_DEVELOPER);
    }
}
