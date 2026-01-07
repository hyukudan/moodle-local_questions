<?php
namespace local_questions\external;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/externallib.php");

use external_api;
use external_function_parameters;
use external_value;
use external_multiple_structure;
use external_single_structure;
use context_system;
use local_questions\ai\gemini_client;

class ai_service extends external_api {

    public static function analyze_batch_parameters() {
        return new external_function_parameters([
            'questionids' => new external_multiple_structure(
                new external_value(PARAM_INT, 'Question ID')
            )
        ]);
    }

    public static function analyze_batch($questionids) {
        global $DB, $CFG;

        $params = self::validate_parameters(self::analyze_batch_parameters(), [
            'questionids' => $questionids
        ]);

        $context = context_system::instance();
        self::validate_context($context);
        require_capability('local/questions:manage', $context); // Only managers should run AI

        if (empty($params['questionids'])) {
            return ['status' => 'error', 'message' => 'No questions selected'];
        }

        // Fetch questions + answers.
        list($insql, $inparams) = $DB->get_in_or_equal($params['questionids']);
        $questions = $DB->get_records_select('question', "id $insql", $inparams);

        foreach ($questions as $q) {
            $q->answers = $DB->get_records('question_answers', ['question' => $q->id]);
        }

        try {
            $client = new gemini_client();
            $result = $client->analyze_questions($questions);
            
            return [
                'status' => 'success',
                'dataset' => json_encode($result),
                'message' => ''
            ];

        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    public static function analyze_batch_returns() {
        return new external_single_structure([
            'status' => new external_value(PARAM_ALPHA, 'Status: success or error'),
            'dataset' => new external_value(PARAM_RAW, 'JSON encoded analysis results', VALUE_OPTIONAL),
            'message' => new external_value(PARAM_TEXT, 'Error message if any', VALUE_OPTIONAL)
        ]);
    }
}
