<?php
/**
 * Event for when a question flag is resolved.
 *
 * @package    local_questions
 * @copyright  2026 Sergio C.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_questions\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Event triggered when a flagged question is resolved.
 */
class flag_resolved extends \core\event\base {

    /**
     * Init method.
     */
    protected function init() {
        $this->data['objecttable'] = 'local_questions_flag_status';
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * Returns localised general event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventflagresolved', 'local_questions');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '{$this->relateduserid}' resolved the flag for question with id '{$this->other['questionid']}' " .
               "with resolution '{$this->other['resolution']}'.";
    }

    /**
     * Custom validation.
     *
     * @throws \coding_exception
     */
    protected function validate_data() {
        parent::validate_data();
        if (!isset($this->other['questionid'])) {
            throw new \coding_exception('The \'questionid\' value must be set in other.');
        }
        if (!isset($this->other['resolution'])) {
            throw new \coding_exception('The \'resolution\' value must be set in other.');
        }
    }
}
