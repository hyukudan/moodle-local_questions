<?php
namespace local_questions\ai;

defined('MOODLE_INTERNAL') || die();

/**
 * Gemini AI Client.
 *
 * @package    local_questions
 * @copyright  2026 Sergio C.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class gemini_client {

    /** @var string API endpoint template. */
    private const API_URL = 'https://generativelanguage.googleapis.com/v1beta/models/{model}:generateContent?key={key}';

    /**
     * Analyze a batch of questions using Gemini.
     *
     * @param array $questions Array of question objects/arrays.
     * @return array The analysis result (JSON decoded).
     * @throws \moodle_exception If API key is missing or request fails.
     */
    public function analyze_questions(array $questions): array {
        $apikey = get_config('local_questions', 'gemini_apikey');
        if (empty($apikey)) {
            throw new \moodle_exception('gemini_apikey_missing', 'local_questions');
        }

        $model = get_config('local_questions', 'gemini_model') ?: 'gemini-1.5-flash';
        $systemPrompt = get_config('local_questions', 'gemini_prompt');

        // Prepare context data (only essential fields to save tokens).
        $questionsData = [];
        foreach ($questions as $q) {
            $qData = [
                'id' => $q->id,
                'text' => strip_tags($q->questiontext),
                'answers' => []
            ];
            foreach ($q->answers as $a) {
                $qData['answers'][] = [
                    'id' => $a->id,
                    'text' => strip_tags($a->answer),
                    'feedback' => strip_tags($a->feedback ?? '')
                ];
            }
            $questionsData[] = $qData;
        }

        $userPrompt = "Please analyze the following questions:\n" . json_encode($questionsData);

        $payload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [['text' => $systemPrompt . "\n\n" . $userPrompt]]
                ]
            ],
            'generationConfig' => [
                'responseMimeType' => 'application/json'
            ]
        ];

        $url = str_replace(['{model}', '{key}'], [$model, $apikey], self::API_URL);
        
        return $this->call_api($url, $payload);
    }

    /**
     * Execute the API call.
     *
     * @param string $url
     * @param array $payload
     * @return array
     */
    private function call_api(string $url, array $payload): array {
        $curl = new \curl();
        $options = [
            'CURLOPT_HTTPHEADER' => ['Content-Type: application/json']
        ];
        
        $response = $curl->post($url, json_encode($payload), $options);
        
        if ($curl->get_errno()) {
             throw new \moodle_exception('gemini_api_error', 'local_questions', '', $curl->error);
        }

        $data = json_decode($response, true);
        
        if (isset($data['error'])) {
            throw new \moodle_exception('gemini_api_error', 'local_questions', '', $data['error']['message']);
        }

        // Extract JSON from response candidate.
        if (!empty($data['candidates'][0]['content']['parts'][0]['text'])) {
            $text = $data['candidates'][0]['content']['parts'][0]['text'];
            // Clean markdown code blocks if present.
            $text = str_replace(['```json', '```'], '', $text);
            return json_decode($text, true) ?: [];
        }

        return [];
    }
}
