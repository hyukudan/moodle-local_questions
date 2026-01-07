<?php
/**
 * Event for when a question flag is created.
 *
 * @package    local_questions
 * @copyright  2026 Sergio C.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_questions\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Event triggered when a user flags a question.
 */
class flag_created extends \core\event\base {

    /**
     * Init method.
     */
    protected function init() {
        $this->data['objecttable'] = 'local_questions_flags';
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
    }

    /**
     * Returns localised general event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventflagcreated', 'local_questions');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '{$this->relateduserid}' flagged the question with id '{$this->other['questionid']}' " .
               "for reason '{$this->other['reason']}'.";
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
        if (!isset($this->other['reason'])) {
            throw new \coding_exception('The \'reason\' value must be set in other.');
        }
    }
}
