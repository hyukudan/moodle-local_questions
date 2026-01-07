<?php
namespace local_questions\external;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/externallib.php");

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use context_system;

class question_editor extends external_api {

    public static function save_question_field_parameters() {
        return new external_function_parameters([
            'questionid' => new external_value(PARAM_INT, 'The question ID'),
            'field' => new external_value(PARAM_NOTAGS, 'The field to update (e.g. questiontext)'),
            'value' => new external_value(PARAM_RAW, 'The new value'),
        ]);
    }

    public static function save_question_field($questionid, $field, $value) {
        global $DB, $USER;

        $params = self::validate_parameters(self::save_question_field_parameters(), [
            'questionid' => $questionid,
            'field' => $field,
            'value' => $value,
        ]);

        $context = context_system::instance();
        self::validate_context($context);
        require_capability('local/questions:manage', $context);

        // Parse field.
        if ($params['field'] === 'questiontext' || $params['field'] === 'generalfeedback') {
            $qUpdate = new \stdClass();
            $qUpdate->id = $params['questionid'];
            $qUpdate->{$params['field']} = $params['value'];
            $qUpdate->timemodified = time();
            $qUpdate->modifiedby = $USER->id;
            $DB->update_record('question', $qUpdate);

        } else if (preg_match('/^(answer|feedback):(\d+)$/', $params['field'], $matches)) {
            $type = $matches[1];
            $answerid = $matches[2];

            // Validate answer belongs to question.
            $answer = $DB->get_record('question_answers', ['id' => $answerid, 'question' => $params['questionid']], '*', MUST_EXIST);

            $aUpdate = new \stdClass();
            $aUpdate->id = $answer->id;
            $aUpdate->{$type} = $params['value'];
            $DB->update_record('question_answers', $aUpdate);

            // Touch question modification time too.
            $qUpdate = new \stdClass();
            $qUpdate->id = $params['questionid'];
            $qUpdate->timemodified = time();
            $qUpdate->modifiedby = $USER->id;
            $DB->update_record('question', $qUpdate);

        } else if ($params['field'] === 'correctanswer') {
            // Change which answer is correct.
            // Set the selected answer to fraction 1.0 and all others to -0.3333333.
            $correctid = (int)$params['value'];

            // Validate answer belongs to question.
            $DB->get_record('question_answers', ['id' => $correctid, 'question' => $params['questionid']], 'id', MUST_EXIST);

            // Get all answers for this question.
            $answers = $DB->get_records('question_answers', ['question' => $params['questionid']]);

            foreach ($answers as $answer) {
                $aUpdate = new \stdClass();
                $aUpdate->id = $answer->id;
                if ($answer->id == $correctid) {
                    $aUpdate->fraction = 1.0;
                } else {
                    $aUpdate->fraction = -0.3333333;
                }
                $DB->update_record('question_answers', $aUpdate);
            }

            // Touch question modification time.
            $qUpdate = new \stdClass();
            $qUpdate->id = $params['questionid'];
            $qUpdate->timemodified = time();
            $qUpdate->modifiedby = $USER->id;
            $DB->update_record('question', $qUpdate);

        } else {
             throw new \moodle_exception('invalidfield', 'local_questions');
        }

        return ['success' => true];
    }

    public static function save_question_field_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether the update was successful'),
        ]);
    }
}
